<?php

namespace App\Console\Commands;

use App\Mail\TemplateMail;
use App\Models\SeriesReleaseNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TalashowSendSeriesReleaseNotifications extends Command
{
    protected $signature = 'talashow:send-series-release-notifications {--limit=200}';
    protected $description = 'Envoie les emails "série disponible" aux utilisateurs ayant cliqué sur Notifier moi.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $limit = $limit > 0 ? min($limit, 2000) : 200;

        $rows = SeriesReleaseNotification::query()
            ->whereNull('notified_at')
            ->whereHas('series', function ($q) {
                $q->where('is_active', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
            })
            ->with(['series:id,title,slug,published_at', 'user:id,email,name'])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $sent = 0;

        foreach ($rows as $n) {
            $email = $n->user?->email ?: $n->email;
            if (!$email) {
                $n->notified_at = now();
                $n->save();
                continue;
            }

            $series = $n->series;
            if (!$series) {
                $n->notified_at = now();
                $n->save();
                continue;
            }

            $seriesUrl = route('series.show', $series->slug);
            $availableAt = $series->published_at?->toDateTimeString();

            try {
                Mail::to($email)->send(new TemplateMail('content.series_published', [
                    'series_title' => $series->title,
                    'series_url' => $seriesUrl,
                    'available_at' => $availableAt,
                ]));
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                // best-effort: continue
            }

            $n->notified_at = now();
            $n->save();
        }

        $this->info("Sent: {$sent} (checked: {$rows->count()})");
        return Command::SUCCESS;
    }
}

