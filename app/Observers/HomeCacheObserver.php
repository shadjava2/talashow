<?php

namespace App\Observers;

use App\Models\Genre;
use App\Models\Series;
use Illuminate\Support\Facades\Cache;

/**
 * Invalide le cache homepage / browse quand les séries ou genres sont modifiés en admin.
 */
class HomeCacheObserver
{
    public function saved($model): void
    {
        $this->forgetRelevant($model);
    }

    public function deleted($model): void
    {
        $this->forgetRelevant($model);
    }

    private function forgetRelevant($model): void
    {
        if ($model instanceof Series) {
            Cache::forget('talashow.home.featured');
            Cache::forget('talashow.home.trending');
            Cache::forget('talashow.home.mustwatch');
        }
        if ($model instanceof Genre) {
            Cache::forget('talashow.home.genre_map');
            Cache::forget('talashow.browse.genres');
        }
    }
}
