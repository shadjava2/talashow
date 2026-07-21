<?php

namespace App\Console\Commands;

use App\Models\VideoPlaybackSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PruneVideoPlaybackSessionsCommand extends Command
{
    protected $signature = 'video:prune-playback-sessions {--hours=72 : Supprimer les sessions expirées ou révoquées depuis plus de N heures}';

    protected $description = 'Purge les enregistrements obsolètes de video_playback_sessions';

    public function handle(): int
    {
        if (! Schema::hasTable('video_playback_sessions')) {
            $this->warn('Table video_playback_sessions absente.');

            return self::SUCCESS;
        }

        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);

        $deleted = VideoPlaybackSession::query()
            ->where(function ($q) use ($cutoff) {
                $q->where('expires_at', '<', $cutoff)
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->whereNotNull('revoked_at')
                            ->where('revoked_at', '<', $cutoff);
                    });
            })
            ->delete();

        $this->info("Sessions supprimées : {$deleted}");

        return self::SUCCESS;
    }
}
