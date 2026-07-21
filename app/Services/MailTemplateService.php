<?php

namespace App\Services;

use App\Models\MailTemplateBinding;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class MailTemplateService
{
    public function __construct(
        private SettingsService $settings
    ) {
    }

    public function render(string $eventKey, array $data = []): array
    {
        if (!Schema::hasTable('mail_template_bindings') || !Schema::hasTable('mail_templates')) {
            return [
                'subject' => null,
                'html' => null,
            ];
        }

        $binding = MailTemplateBinding::query()
            ->with('template')
            ->where('event_key', $eventKey)
            ->first();

        $tpl = $binding?->template;
        if (!$tpl || !$tpl->is_active) {
            return [
                'subject' => null,
                'html' => null,
            ];
        }

        $logo = $this->settings->get('site_logo_url') ?: asset('logo.svg');
        $appName = (string) config('app.name', 'Talashow');

        $vars = array_merge([
            'app_name' => $appName,
            'logo_url' => $logo,
            'year' => (string) now()->year,
            'now' => now()->format('Y-m-d H:i:s'),
        ], $data);

        $subject = $this->interpolate((string) ($tpl->subject ?? ''), $vars);
        $html = $this->interpolate((string) ($tpl->html ?? ''), $vars);

        return [
            'subject' => $subject !== '' ? $subject : null,
            'html' => $html !== '' ? $html : null,
        ];
    }

    private function interpolate(string $input, array $vars): string
    {
        // Simple templating: remplace {{key}} et {{a.b}} sans exécuter de code (safe).
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/', function ($m) use ($vars) {
            $key = (string) $m[1];
            $val = Arr::get($vars, $key);
            if ($val === null) return '';
            return (string) $val;
        }, $input) ?? $input;
    }
}

