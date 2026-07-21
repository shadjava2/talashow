<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // ex: auth.otp, auth.password_reset, billing.subscription_activated
            $table->string('name');
            $table->string('subject')->nullable();
            $table->longText('html')->nullable(); // HTML complet (inclut logo, styles)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mail_template_bindings', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique(); // ex: auth.otp, auth.password_reset
            $table->foreignId('mail_template_id')->constrained('mail_templates')->onDelete('cascade');
            $table->timestamps();
        });

        // Templates par défaut (créés uniquement s'ils n'existent pas déjà). Ne remplace jamais.
        $defaults = [
            [
                'key' => 'auth.otp',
                'name' => 'Vérification — OTP (création de compte)',
                'subject' => 'Talashow — Code de vérification',
                'html' => $this->defaultOtpHtml(),
            ],
            [
                'key' => 'auth.password_reset',
                'name' => 'Sécurité — Mot de passe oublié',
                'subject' => 'Talashow — Réinitialisation de mot de passe',
                'html' => $this->defaultPasswordResetHtml(),
            ],
            [
                'key' => 'system.test_email',
                'name' => 'Système — Email de test (SMTP)',
                'subject' => 'Talashow — Test SMTP',
                'html' => $this->defaultTestHtml(),
            ],
            [
                'key' => 'billing.subscription_activated',
                'name' => 'Facturation — Abonnement activé',
                'subject' => 'Talashow — Abonnement activé',
                'html' => $this->defaultSimpleNoticeHtml('Votre abonnement est activé', 'Merci ! Votre abonnement Talashow est maintenant actif.'),
            ],
            [
                'key' => 'billing.subscription_cancelled',
                'name' => 'Facturation — Abonnement annulé',
                'subject' => 'Talashow — Abonnement annulé',
                'html' => $this->defaultSimpleNoticeHtml('Abonnement annulé', 'Votre abonnement Talashow a été annulé. Vous pouvez le réactiver à tout moment.'),
            ],
            [
                'key' => 'content.new_episode',
                'name' => 'Contenu — Nouvel épisode',
                'subject' => 'Talashow — Nouveau contenu disponible',
                'html' => $this->defaultSimpleNoticeHtml('Nouveau contenu', 'Un nouvel épisode est disponible sur Talashow.'),
            ],
            [
                'key' => 'marketing.newsletter',
                'name' => 'Marketing — Newsletter',
                'subject' => 'Talashow — Nouveautés',
                'html' => $this->defaultSimpleNoticeHtml('Nouveautés Talashow', 'Découvrez nos dernières nouveautés.'),
            ],
        ];

        foreach ($defaults as $tpl) {
            $exists = DB::table('mail_templates')->where('key', $tpl['key'])->exists();
            if ($exists) {
                continue;
            }

            $id = DB::table('mail_templates')->insertGetId([
                'key' => $tpl['key'],
                'name' => $tpl['name'],
                'subject' => $tpl['subject'],
                'html' => $tpl['html'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Binding par défaut: event_key == template key
            $bindExists = DB::table('mail_template_bindings')->where('event_key', $tpl['key'])->exists();
            if (!$bindExists) {
                DB::table('mail_template_bindings')->insert([
                    'event_key' => $tpl['key'],
                    'mail_template_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_template_bindings');
        Schema::dropIfExists('mail_templates');
    }

    private function wrapper(string $title, string $contentHtml): string
    {
        // Variables dispo: {{app_name}}, {{logo_url}}, {{year}}, {{title}}, {{content}}
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
            '<h1 style="margin:16px 0 0 0;font-size:22px;line-height:1.3;">' . $title . '</h1>' .
            '</td></tr>' .
            '<tr><td style="padding:20px 24px;">' . $contentHtml . '</td></tr>' .
            '<tr><td style="padding:0 24px 22px 24px;text-align:center;color:rgba(255,255,255,0.45);font-size:12px;">© ' . $app . ' — ' . $year . '</td></tr>' .
            '</table></td></tr></table></body></html>';
    }

    private function defaultOtpHtml(): string
    {
        $content = '<div style="background:rgba(0,0,0,0.25);border:1px solid rgba(255,255,255,0.12);border-radius:14px;padding:18px;text-align:center;">' .
            '<p style="margin:0 0 10px 0;color:rgba(255,255,255,0.80);font-size:14px;line-height:1.6;">Bonjour <strong>{{name}}</strong>, voici votre code OTP :</p>' .
            '<div style="margin-top:10px;font-size:34px;font-weight:800;letter-spacing:0.18em;color:#ffffff;">{{otp}}</div>' .
            '<p style="margin:14px 0 0 0;color:rgba(255,255,255,0.70);font-size:12px;line-height:1.6;">Ce code expire dans {{expires_minutes}} minutes.</p>' .
            '</div>' .
            '<p style="margin:14px 0 0 0;color:rgba(255,255,255,0.55);font-size:12px;line-height:1.6;text-align:center;">Si vous n’êtes pas à l’origine de cette demande, ignorez cet email.</p>';

        return $this->wrapper('Bienvenue sur Talashow', $content);
    }

    private function defaultPasswordResetHtml(): string
    {
        $content = '<div style="background:rgba(0,0,0,0.25);border:1px solid rgba(255,255,255,0.12);border-radius:14px;padding:16px;">' .
            '<p style="margin:0 0 14px 0;color:rgba(255,255,255,0.85);font-size:14px;line-height:1.6;">Cliquez ci-dessous pour choisir un nouveau mot de passe.</p>' .
            '<div style="text-align:center;margin-top:14px;">' .
            '<a href="{{reset_url}}" style="display:inline-block;background:#dc2626;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:12px;font-weight:700;font-size:14px;">Réinitialiser mon mot de passe</a>' .
            '</div>' .
            '<p style="margin:14px 0 0 0;color:rgba(255,255,255,0.70);font-size:12px;line-height:1.6;">Si vous n’avez pas demandé cette action, ignorez cet email.</p>' .
            '<p style="margin:10px 0 0 0;color:rgba(255,255,255,0.55);font-size:12px;line-height:1.6;word-break:break-all;">Lien direct : {{reset_url}}</p>' .
            '</div>';

        return $this->wrapper('Réinitialiser votre mot de passe', $content);
    }

    private function defaultTestHtml(): string
    {
        $content = '<div style="background:rgba(0,0,0,0.25);border:1px solid rgba(255,255,255,0.12);border-radius:14px;padding:16px;">' .
            '<p style="margin:0;color:rgba(255,255,255,0.85);font-size:14px;line-height:1.6;">✅ Ceci est un email de test. SMTP est correctement configuré.</p>' .
            '<p style="margin:10px 0 0 0;color:rgba(255,255,255,0.60);font-size:12px;line-height:1.6;">Date: {{now}}</p>' .
            '</div>';
        return $this->wrapper('Test SMTP', $content);
    }

    private function defaultSimpleNoticeHtml(string $title, string $message): string
    {
        $content = '<div style="background:rgba(0,0,0,0.25);border:1px solid rgba(255,255,255,0.12);border-radius:14px;padding:16px;">' .
            '<p style="margin:0;color:rgba(255,255,255,0.85);font-size:14px;line-height:1.6;">' . htmlspecialchars($message, ENT_QUOTES) . '</p>' .
            '</div>';
        return $this->wrapper($title, $content);
    }
};

