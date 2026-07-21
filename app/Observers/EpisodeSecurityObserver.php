<?php

namespace App\Observers;

use App\Models\Episode;
use App\Services\SecurityAuditService;

class EpisodeSecurityObserver
{
    public function created(Episode $episode): void
    {
        SecurityAuditService::adminActivity('episode.created', [
            'episode_id' => $episode->id,
            'series_id' => $episode->series_id,
        ]);
    }

    public function updated(Episode $episode): void
    {
        if ($episode->wasChanged()) {
            SecurityAuditService::adminActivity('episode.updated', [
                'episode_id' => $episode->id,
                'series_id' => $episode->series_id,
                'changed' => array_keys($episode->getChanges()),
            ]);
        }
    }

    public function deleted(Episode $episode): void
    {
        SecurityAuditService::adminActivity('episode.deleted', [
            'episode_id' => $episode->id,
            'series_id' => $episode->series_id,
        ]);
    }
}
