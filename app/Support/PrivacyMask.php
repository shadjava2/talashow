<?php

namespace App\Support;

final class PrivacyMask
{
    public static function email(?string $email): string
    {
        $email = trim((string) $email);
        if ($email === '' || ! str_contains($email, '@')) {
            return '—';
        }
        [$local, $domain] = explode('@', $email, 2);
        $localLen = strlen($local);
        if ($localLen <= 2) {
            $masked = ($local[0] ?? '*').'*';
        } else {
            $masked = substr($local, 0, 2).str_repeat('*', max(1, $localLen - 2));
        }

        return $masked.'@'.$domain;
    }

    public static function ip(?string $ip): string
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return '—';
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                return $parts[0].'.'.$parts[1].'.'.$parts[2].'.xxx';
            }
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $head = array_slice($parts, 0, 4);

            return implode(':', $head).':…';
        }

        return substr($ip, 0, 8).'…';
    }
}
