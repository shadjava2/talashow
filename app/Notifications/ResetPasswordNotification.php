<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Services\MailTemplateService;
use App\Services\MailInlineAssetService;
use Symfony\Component\Mime\Email;

class ResetPasswordNotification extends Notification
{
    public function __construct(public string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // IMPORTANT:
        // En dev, on peut accéder via un host temporaire (ex: trycloudflare.com) qui peut être
        // classé comme "phishing/spam" par certains SMTP. On permet donc de forcer le domaine
        // utilisé dans les liens emails via TALASHOW_MAIL_APP_URL (sinon on garde l'URL courante).
        $base = (string) env('TALASHOW_MAIL_APP_URL', '');
        $path = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false);
        $resetUrl = $base !== '' ? rtrim($base, '/') . $path : url($path);

        /** @var MailTemplateService $svc */
        $svc = app(MailTemplateService::class);
        $rendered = $svc->render('auth.password_reset', [
            'reset_url' => $resetUrl,
            'email' => $notifiable->getEmailForPasswordReset(),
            'name' => $notifiable->name ?? '',
        ]);

        return (new MailMessage)
            ->subject($rendered['subject'] ?? 'Talashow — Réinitialisation de mot de passe')
            ->view('emails.dynamic', [
                'html' => $rendered['html'] ?? '',
            ])
            ->withSymfonyMessage(function (Email $message) {
                /** @var MailInlineAssetService $assets */
                $assets = app(MailInlineAssetService::class);
                $current = (string) ($message->getHtmlBody() ?? '');
                if ($current === '') return;

                try {
                    $message->html($assets->inlineAllImages($message, $current));
                } catch (\Throwable) {
                    // best-effort: never break reset email if inline fails
                }
            });
    }
}

