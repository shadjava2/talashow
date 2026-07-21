<?php

namespace App\Console\Commands;

use App\Mail\TemplateMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TalashowSendNewsletterCampaigns extends Command
{
    protected $signature = 'talashow:send-newsletter-campaigns {--limit=200}';
    protected $description = 'Envoie les campagnes newsletter en lots (anti-timeout/anti-500).';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $limit = $limit > 0 ? min($limit, 2000) : 200;

        /** @var NewsletterCampaign|null $campaign */
        $campaign = NewsletterCampaign::query()
            ->whereIn('status', ['pending', 'sending'])
            ->whereNull('finished_at')
            ->orderBy('id')
            ->first();

        if (!$campaign) {
            $this->info('No campaign.');
            return Command::SUCCESS;
        }

        if ($campaign->status !== 'sending') {
            $campaign->status = 'sending';
            $campaign->started_at = $campaign->started_at ?: now();
            $campaign->save();
        }

        $afterId = (int) ($campaign->last_subscriber_id ?? 0);
        $subs = NewsletterSubscriber::query()
            ->whereNotNull('confirmed_at')
            ->whereNull('unsubscribed_at')
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($subs->count() === 0) {
            $campaign->status = 'sent';
            $campaign->finished_at = now();
            $campaign->save();
            $this->info("Campaign #{$campaign->id} finished. sent={$campaign->sent_count} failed={$campaign->failed_count}");
            return Command::SUCCESS;
        }

        foreach ($subs as $s) {
            try {
                $unsubscribeUrl = route('newsletter.unsubscribe', $s->unsubscribe_token);
                Mail::to($s->email)->send(new TemplateMail('marketing.newsletter_broadcast', [
                    // Compat templates
                    'newsletter_title' => $campaign->headline,
                    'newsletter_content' => $campaign->content_html,
                    'headline' => $campaign->headline,
                    'content_html' => $campaign->content_html,
                    'unsubscribe_url' => $unsubscribeUrl,
                ]));
                $campaign->sent_count++;
            } catch (\Throwable $e) {
                report($e);
                $campaign->failed_count++;
                $campaign->last_error = $e->getMessage();
            }

            $campaign->last_subscriber_id = $s->id;
        }

        $campaign->save();
        $this->info("Campaign #{$campaign->id} progressed to subscriber_id={$campaign->last_subscriber_id} sent={$campaign->sent_count} failed={$campaign->failed_count}");
        return Command::SUCCESS;
    }
}

