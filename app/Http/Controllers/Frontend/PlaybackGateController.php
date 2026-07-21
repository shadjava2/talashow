<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\VideoPlaybackSession;
use App\Services\SecurityAuditService;
use App\Services\Video\VideoPlaybackResolverService;
use App\Services\Video\VideoSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class PlaybackGateController extends Controller
{
    public function __invoke(Request $request, string $token, VideoPlaybackResolverService $resolver, VideoSecurityService $videoSecurity)
    {
        if (! config('video_security.playback_gate_enabled') || ! Schema::hasTable('video_playback_sessions')) {
            abort(404);
        }

        $session = VideoPlaybackSession::query()
            ->where('session_token', $token)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $session) {
            SecurityAuditService::securityEvent('playback_gate_denied', 'medium', [
                'reason' => 'invalid_or_expired_token',
            ], $request);

            abort(403);
        }

        if (config('video_security.require_login_for_playback') && ! $session->user_id) {
            SecurityAuditService::securityEvent('playback_gate_denied', 'medium', [
                'reason' => 'session_guest_not_allowed',
            ], $request);

            abort(403);
        }

        if ($session->user_id) {
            if (! Auth::check() || (int) Auth::id() !== (int) $session->user_id) {
                SecurityAuditService::securityEvent('playback_gate_denied', 'high', [
                    'reason' => 'user_mismatch',
                    'episode_id' => $session->episode_id,
                ], $request);

                abort(403);
            }
        } else {
            if ($session->guest_session_key !== session()->getId()) {
                SecurityAuditService::securityEvent('playback_gate_denied', 'medium', [
                    'reason' => 'guest_session_mismatch',
                    'episode_id' => $session->episode_id,
                ], $request);

                abort(403);
            }
        }

        $allowed = config('video_security.allowed_referrers', []);
        if (is_array($allowed) && $allowed !== []) {
            $ref = (string) $request->headers->get('referer');
            $ok = false;
            foreach ($allowed as $hostOrUrl) {
                $hostOrUrl = trim((string) $hostOrUrl);
                if ($hostOrUrl === '') {
                    continue;
                }
                if ($ref !== '' && str_contains($ref, $hostOrUrl)) {
                    $ok = true;
                    break;
                }
            }
            if (! $ok && $ref !== '') {
                SecurityAuditService::securityEvent('playback_gate_denied', 'low', [
                    'reason' => 'referrer_blocked',
                    'referrer' => $ref,
                ], $request);

                abort(403);
            }
        }

        $episode = Episode::query()->where('id', $session->episode_id)->where('is_active', true)->first();
        if (! $episode) {
            abort(404);
        }

        $user = Auth::user();
        if (! $episode->isUnlockedForUser($user) && ! $episode->is_free) {
            SecurityAuditService::securityEvent('playback_gate_denied', 'medium', [
                'reason' => 'episode_locked',
                'episode_id' => $episode->id,
            ], $request);

            abort(403);
        }

        $lang = (string) ($session->video_lang ?: 'fr');
        $series = $episode->series;
        if (! $series || ! $series->is_active) {
            abort(404);
        }

        $videoUrls = $episode->video_urls;
        $videoUrls = is_array($videoUrls) ? $videoUrls : [];
        $seriesLangs = $series->video_languages;
        $seriesLangs = is_array($seriesLangs) ? $seriesLangs : [];
        $seriesLangs = array_values(array_unique(array_filter(array_map(fn ($v) => strtolower(trim((string) $v)), $seriesLangs))));
        $seriesDefaultLang = $seriesLangs[0] ?? 'fr';
        if ($lang === '' || ! in_array($lang, $seriesLangs, true)) {
            $lang = $seriesDefaultLang;
        }

        if (empty($videoUrls) && ! empty($episode->video_url)) {
            $videoUrls[$seriesDefaultLang] = (string) $episode->video_url;
        }

        $selectedVideoUrl = trim((string) ($videoUrls[$lang] ?? ''));
        if ($selectedVideoUrl === '') {
            foreach ($seriesLangs as $code) {
                $u = trim((string) ($videoUrls[$code] ?? ''));
                if ($u !== '') {
                    $lang = $code;
                    $selectedVideoUrl = $u;
                    break;
                }
            }
        }

        config(['video.playback_driver' => 'bunny_embed']);
        $bunnyExpires = $videoSecurity->bunnyExpiresAtUnix();
        $meta = $resolver->resolve($episode, $lang, $selectedVideoUrl, $episode->thumbnail, $bunnyExpires);

        $target = trim((string) ($meta['embed_url'] ?? ''));
        if ($target === '') {
            SecurityAuditService::securityEvent('playback_gate_denied', 'medium', [
                'reason' => 'no_embed_url',
                'episode_id' => $episode->id,
            ], $request);

            abort(503);
        }

        if (config('video_security.playback_audit_enabled')) {
            SecurityAuditService::securityEvent('playback_access', 'low', [
                'episode_id' => $episode->id,
                'mode' => $meta['mode'] ?? '',
            ], $request);
        }

        return redirect()->away($target);
    }
}
