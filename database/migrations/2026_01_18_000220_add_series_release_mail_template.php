<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mail_templates') || !Schema::hasTable('mail_template_bindings')) {
            return;
        }

        $key = 'content.series_published';

        $exists = DB::table('mail_templates')->where('key', $key)->exists();
        if (!$exists) {
            $id = DB::table('mail_templates')->insertGetId([
                'key' => $key,
                'name' => 'Contenu — Série disponible (publication)',
                'subject' => 'Talashow — {{series_title}} est disponible',
                'html' => $this->wrapper(
                    'Série disponible',
                    '<div style="background:rgba(0,0,0,0.25);border:1px solid rgba(255,255,255,0.12);border-radius:14px;padding:16px;">' .
                    '<p style="margin:0 0 12px 0;color:rgba(255,255,255,0.85);font-size:14px;line-height:1.6;">Bonne nouvelle ! La série <strong>{{series_title}}</strong> est maintenant disponible sur Talashow.</p>' .
                    '<div style="text-align:center;margin-top:14px;">' .
                    '<a href="{{series_url}}" style="display:inline-block;background:#dc2626;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:12px;font-weight:700;font-size:14px;">Voir la série</a>' .
                    '</div>' .
                    '<p style="margin:14px 0 0 0;color:rgba(255,255,255,0.55);font-size:12px;line-height:1.6;word-break:break-all;">Lien direct : {{series_url}}</p>' .
                    '</div>'
                ),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $bindExists = DB::table('mail_template_bindings')->where('event_key', $key)->exists();
            if (!$bindExists) {
                DB::table('mail_template_bindings')->insert([
                    'event_key' => $key,
                    'mail_template_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // no-op (ne pas supprimer les templates en prod)
    }

    private function wrapper(string $title, string $contentHtml): string
    {
        $app = '{{app_name}}';
        $logo = '{{logo_url}}';
        $year = '{{year}}';

        return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' .
            htmlspecialchars($title, ENT_QUOTES) .
            '</title></head><body style="margin:0;padding:0;background:#0b1220;color:#ffffff;font-family:Arial,Helvetica,sans-serif;">' .
            '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0b1220;padding:24px 0;"><tr><td align="center">' .
            '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:92%;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.10);border-radius:16px;overflow:hidden;">' .
            '<tr><td style="padding:24px 24px 0 24px;text-align:center;">' .
            '<img src="' . $logo . '" alt="' . $app . '" style="height:44px;width:auto;border-radius:10px;display:inline-block;">' .
            '<h1 style="margin:16px 0 0 0;font-size:22px;line-height:1.3;">' . htmlspecialchars($title, ENT_QUOTES) . '</h1>' .
            '</td></tr>' .
            '<tr><td style="padding:20px 24px;">' . $contentHtml . '</td></tr>' .
            '<tr><td style="padding:0 24px 22px 24px;text-align:center;color:rgba(255,255,255,0.45);font-size:12px;">© ' . $app . ' — ' . $year . '</td></tr>' .
            '</table></td></tr></table></body></html>';
    }
};

