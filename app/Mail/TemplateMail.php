<?php

namespace App\Mail;

use App\Services\MailInlineAssetService;
use App\Services\MailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class TemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $eventKey,
        public array $data = []
    ) {
    }

    public function build()
    {
        /** @var MailTemplateService $svc */
        $svc = app(MailTemplateService::class);
        $rendered = $svc->render($this->eventKey, $this->data);

        $subject = $rendered['subject'] ?? 'Talashow';
        $html = $rendered['html'] ?? '<p>Template introuvable.</p>';

        return $this->subject($subject)
            ->html($html)
            ->withSymfonyMessage(function (Email $message) {
                /** @var MailInlineAssetService $assets */
                $assets = app(MailInlineAssetService::class);
                try {
                    $current = (string) ($message->getHtmlBody() ?? '');
                    if ($current === '') return;
                    $message->html($assets->inlineAllImages($message, $current));
                } catch (\Throwable) {
                    // best-effort: never break the email send if inline fails
                }
            });
    }
}

