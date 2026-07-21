<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingsService
{
    public function get(string $key, ?string $default = null): ?string
    {
        // On ne cache pas les secrets en clair.
        $all = Cache::remember('talashow.settings.public', 60, function () {
            return Setting::query()
                ->where('is_secret', false)
                ->pluck('value', 'key')
                ->toArray();
        });

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function set(string $key, ?string $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'is_secret' => false]
        );
        Cache::forget('talashow.settings.public');
    }

    public function getSecret(string $key, ?string $default = null): ?string
    {
        $row = Setting::query()->where('key', $key)->first();
        if (!$row) return $default;
        if (!$row->is_secret) return $row->value ?? $default;

        try {
            return $row->value ? Crypt::decryptString($row->value) : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    public function setSecret(string $key, ?string $plainValue): void
    {
        $encrypted = $plainValue === null ? null : Crypt::encryptString($plainValue);
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $encrypted, 'is_secret' => true]
        );
        // pas de cache secret
    }
}

