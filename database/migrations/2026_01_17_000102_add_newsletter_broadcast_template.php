<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mail_templates') || !Schema::hasTable('mail_template_bindings')) {
            return;
        }

        $key = 'marketing.newsletter_broadcast';
        $exists = DB::table('mail_templates')->where('key', $key)->exists();
        if ($exists) {
            return;
        }

        $html = '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' .
            '<title>Newsletter</title></head>' .
            '<body style="margin:0;padding:0;background:#0b1220;color:#ffffff;font-family:Arial,Helvetica,sans-serif;">' .
            '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0b1220;padding:24px 0;"><tr><td align="center">' .
            '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:92%;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.10);border-radius:16px;overflow:hidden;">' .
            '<tr><td style="padding:24px 24px 0 24px;text-align:center;">' .
            '<img src="{{logo_url}}" alt="{{app_name}}" style="height:44px;width:auto;border-radius:10px;display:inline-block;">' .
            '<h1 style="margin:16px 0 0 0;font-size:22px;line-height:1.3;">{{headline}}</h1>' .
            '</td></tr>' .
            '<tr><td style="padding:20px 24px;color:rgba(255,255,255,0.92);font-size:14px;line-height:1.7;">{{content_html}}</td></tr>' .
            '<tr><td style="padding:0 24px 22px 24px;text-align:center;color:rgba(255,255,255,0.55);font-size:12px;line-height:1.6;">' .
            'Vous recevez cet email car vous êtes inscrit(e) à la newsletter Talashow.<br>' .
            '<a href="{{unsubscribe_url}}" style="color:#93c5fd;text-decoration:underline;">Se désinscrire</a>' .
            '<div style="margin-top:10px;">© {{app_name}} — {{year}}</div>' .
            '</td></tr>' .
            '</table></td></tr></table></body></html>';

        $id = DB::table('mail_templates')->insertGetId([
            'key' => $key,
            'name' => 'Marketing — Newsletter (envoi)',
            'subject' => 'Talashow — Newsletter',
            'html' => $html,
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

    public function down(): void
    {
        // Ne supprime rien (migrations sûres en prod)
    }
};

