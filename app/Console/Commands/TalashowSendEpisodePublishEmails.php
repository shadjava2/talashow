<?php

namespace App\Console\Commands;

use App\Mail\TemplateMail;
use App\Models\Episode;
use App\Models\EpisodeReleaseNotification;
use App\Models\NewsletterSubscriber;
use App\Services\SettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TalashowSendEpisodePublishEmails extends Command
{
    protected $signature = 'talashow:send-episode-publish-emails {--limit=200}';
    protected $description = 'Envoie les emails lors de la publication programmée d’un épisode (abonnés épisode + newsletter).';

    public function handle(SettingsService $settings): int
    {
        $limit = (int) $this->option('limit');
        $limit = $limit > 0 ? min($limit, 5000) : 200;

        // 1) Notifier les utilisateurs "abonnés à l’épisode" (bouton Notifier moi)
        $subs = EpisodeReleaseNotification::query()
            ->whereNull('notified_at')
            ->whereHas('episode', function ($q) {
                $q->where('is_active', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
            })
            ->with(['episode:id,title,series_id,published_at', 'episode.series:id,title,slug', 'user:id,email,name'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $sentEpisodeSubs = 0;
        foreach ($subs as $n) {
            $email = $n->user?->email;
            $episode = $n->episode;
            $series = $episode?->series;

            if (!$email || !$episode || !$series) {
                $n->notified_at = now();
                $n->save();
                continue;
            }

            $episodeUrl = route('episode.show', [$series->slug, $episode->id]);

            try {
                Mail::to($email)->send(new TemplateMail('content.episode_published', [
                    'series_title' => $series->title,
                    'episode_title' => $episode->title,
                    'episode_url' => $episodeUrl,
                ]));
                $sentEpisodeSubs++;
            } catch (\Throwable $e) {
                report($e);
                // best-effort: on marque quand même pour éviter boucle de spam si SMTP en panne
            }

            $n->notified_at = now();
            $n->save();
        }

        // 2) Newsletter: option activable via Settings (évite d’envoyer par défaut à toute la base)
        $sendNewsletter = filter_var($settings->get('notify_newsletter_on_episode_publish', 'false'), FILTER_VALIDATE_BOOL);
        $sentNewsletter = 0;

        if ($sendNewsletter) {
            // On ne blast que les épisodes programmés (published_at non null) pour éviter d’envoyer sur les anciens épisodes.
            $episodes = Episode::query()
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->whereNull('notified_newsletter_at')
                ->with('series:id,title,slug')
                ->orderBy('published_at')
                ->limit(10) // garde-fou: 10 épisodes max par run
                ->get();

            foreach ($episodes as $ep) {
                if (!$ep->series) {
                    $ep->notified_newsletter_at = now();
                    $ep->save();
                    continue;
                }

                $episodeUrl = route('episode.show', [$ep->series->slug, $ep->id]);

                $subscribers = NewsletterSubscriber::query()
                    ->whereNotNull('confirmed_at')
                    ->whereNull('unsubscribed_at')
                    ->orderBy('id')
                    ->limit($limit)
                    ->get();

                foreach ($subscribers as $s) {
                    $unsubscribeUrl = route('newsletter.unsubscribe', $s->unsubscribe_token);
                    try {
                        Mail::to($s->email)->send(new TemplateMail('marketing.episode_published', [
                            'series_title' => $ep->series->title,
                            'episode_title' => $ep->title,
                            'episode_url' => $episodeUrl,
                            'unsubscribe_url' => $unsubscribeUrl,
                        ]));
                        $sentNewsletter++;
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }

                $ep->notified_newsletter_at = now();
                $ep->save();
            }
        }

        $this->info("Episode-subs sent: {$sentEpisodeSubs} | Newsletter sent: {$sentNewsletter} | Newsletter enabled: " . ($sendNewsletter ? 'yes' : 'no'));
        return Command::SUCCESS;
    }
}

