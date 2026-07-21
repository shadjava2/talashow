<?php

namespace App\Observers;

use App\Models\Series;
use App\Services\SecurityAuditService;

class SeriesSecurityObserver
{
    public function created(Series $series): void
    {
        SecurityAuditService::adminActivity('series.created', [
            'series_id' => $series->id,
            'slug' => $series->slug,
        ]);
    }

    public function updated(Series $series): void
    {
        if ($series->wasChanged()) {
            SecurityAuditService::adminActivity('series.updated', [
                'series_id' => $series->id,
                'slug' => $series->slug,
                'changed' => array_keys($series->getChanges()),
            ]);
        }
    }

    public function deleted(Series $series): void
    {
        SecurityAuditService::adminActivity('series.deleted', [
            'series_id' => $series->id,
            'slug' => $series->slug,
        ]);
    }
}
