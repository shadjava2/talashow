<?php

namespace App\Services\Video;

use App\Models\Episode;
use App\Models\UserDevice;
use App\Models\VideoPlaybackSession;
use App\Services\SecurityAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class VideoSecurityService
{
    /**
     * Expiration Unix pour les jetons Bunny (iframe + HLS) lorsque le durcissement TTL est actif.
     */
    public function bunnyExpiresAtUnix(): ?int
    {
        if (! config('video_security.harden_bunny_ttl') || ! config('video_security.signed_urls_enabled')) {
            return null;
        }

        $candidates = [(int) config('video_security.token_expiration_seconds', 120)];

        if (config('services.bunny_stream.signed_urls')) {
            $candidates[] = (int) config('services.bunny_stream.signed_url_ttl_seconds', 3600);
        }

        if (filter_var((string) config('services.bunny_stream.embed_token_auth_enabled', false), FILTER_VALIDATE_BOOL)) {
            $candidates[] = (int) config('services.bunny_stream.embed_url_expiration', 3600);
        }

        $ttl = max(60, min($candidates));

        return time() + $ttl;
    }

    public function deviceFingerprint(Request $request): string
    {
        $parts = [
            (string) $request->userAgent(),
            (string) $request->header('Accept-Language'),
            (string) $request->header('Sec-CH-UA'),
        ];

        return hash('sha256', implode('|', $parts));
    }

    /**
     * URL du gate applicatif, ou null si désactivé / refus / pas de table.
     */
    public function issueGateUrl(Episode $episode, string $videoLang, Request $request): ?string
    {
        if (! config('video_security.playback_gate_enabled')) {
            return null;
        }

        if (! Schema::hasTable('video_playback_sessions')) {
            return null;
        }

        if (config('video_security.require_login_for_playback') && ! $request->user()) {
            SecurityAuditService::securityEvent('playback_gate_denied', 'medium', [
                'reason' => 'login_required',
                'episode_id' => $episode->id,
            ], $request);

            return null;
        }

        $user = $request->user();
        $guestKey = $user ? null : session()->getId();
        $fp = $this->deviceFingerprint($request);

        if ($user && Schema::hasTable('user_devices')) {
            $this->touchUserDevice($user->id, $fp, $request);
            if ($this->exceedsDeviceLimit($user->id)) {
                SecurityAuditService::securityEvent('playback_gate_denied', 'high', [
                    'reason' => 'max_devices',
                    'episode_id' => $episode->id,
                ], $request);

                return null;
            }
        }

        if ($user && $this->exceedsConcurrentLimit($user->id)) {
            $this->revokeOldestActiveSessions($user->id, 1);
        }

        $this->revokeExistingSessionForViewer($user?->id, $guestKey, $episode->id);

        $token = Str::random(64);
        $ttlMin = max(5, (int) config('video_security.playback_session_ttl_minutes', 360));

        VideoPlaybackSession::query()->create([
            'user_id' => $user?->id,
            'episode_id' => $episode->id,
            'guest_session_key' => $guestKey,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'device_fingerprint' => $fp,
            'video_lang' => strtolower(substr($videoLang, 0, 16)),
            'session_token' => $token,
            'playback_token_hash' => hash('sha256', $token),
            'started_at' => now(),
            'expires_at' => now()->addMinutes($ttlMin),
        ]);

        if (config('video_security.playback_audit_enabled')) {
            SecurityAuditService::securityEvent('playback_session_issued', 'low', [
                'episode_id' => $episode->id,
            ], $request);
        }

        return route('playback.gate', ['token' => $token], true);
    }

    protected function touchUserDevice(int $userId, string $fingerprint, Request $request): void
    {
        UserDevice::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'fingerprint_hash' => $fingerprint,
            ],
            [
                'last_ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                'last_seen_at' => now(),
            ]
        );
    }

    protected function exceedsDeviceLimit(int $userId): bool
    {
        $max = (int) config('video_security.max_devices', 5);
        if ($max <= 0) {
            return false;
        }

        $count = UserDevice::query()->where('user_id', $userId)->count();

        return $count > $max;
    }

    protected function exceedsConcurrentLimit(int $userId): bool
    {
        $max = (int) config('video_security.max_concurrent_streams', 2);
        if ($max <= 0) {
            return false;
        }

        return $this->activeSessionCountForUser($userId) >= $max;
    }

    protected function activeSessionCountForUser(int $userId): int
    {
        return VideoPlaybackSession::query()
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->count();
    }

    protected function revokeOldestActiveSessions(int $userId, int $howMany): void
    {
        $ids = VideoPlaybackSession::query()
            ->where('user_id', $userId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->orderBy('started_at')
            ->limit($howMany)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        VideoPlaybackSession::query()->whereIn('id', $ids)->update(['revoked_at' => now()]);
    }

    protected function revokeExistingSessionForViewer(?int $userId, ?string $guestKey, int $episodeId): void
    {
        $q = VideoPlaybackSession::query()
            ->where('episode_id', $episodeId)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());

        if ($userId) {
            $q->where('user_id', $userId);
        } else {
            $q->whereNull('user_id')->where('guest_session_key', $guestKey);
        }

        $q->update(['revoked_at' => now()]);
    }
}
