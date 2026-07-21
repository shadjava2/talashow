<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class SecurityAuditService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function securityEvent(
        string $type,
        string $level = 'low',
        array $payload = [],
        ?Request $request = null,
    ): void {
        if (! Schema::hasTable('security_events')) {
            return;
        }

        $req = $request ?? request();
        $ua = $req?->userAgent();
        if (is_string($ua) && strlen($ua) > 500) {
            $ua = substr($ua, 0, 500);
        }

        try {
            SecurityEvent::query()->create([
                'ip' => $req?->ip(),
                'user_id' => Auth::id(),
                'route' => $req?->route()?->getName() ?? $req?->path(),
                'method' => $req?->method(),
                'user_agent' => $ua,
                'type' => substr($type, 0, 80),
                'level' => in_array($level, ['low', 'medium', 'high', 'critical'], true) ? $level : 'low',
                'payload' => self::truncatePayload($payload),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function adminActivity(string $action, array $meta = [], ?Request $request = null): void
    {
        if (! Schema::hasTable('admin_activity_logs')) {
            return;
        }

        $req = $request ?? request();
        $ua = $req?->userAgent();
        if (is_string($ua) && strlen($ua) > 500) {
            $ua = substr($ua, 0, 500);
        }

        try {
            AdminActivityLog::query()->create([
                'user_id' => Auth::id(),
                'action' => substr($action, 0, 120),
                'ip' => $req?->ip(),
                'user_agent' => $ua,
                'meta' => self::truncatePayload($meta),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected static function truncatePayload(array $payload): array
    {
        try {
            $json = json_encode($payload) ?: '{}';
            if (strlen($json) > 8000) {
                return ['_truncated' => true, 'preview' => substr($json, 0, 4000)];
            }

            return $payload;
        } catch (\Throwable) {
            return ['_encode_error' => true];
        }
    }
}
