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
            foreach ([
                'talashow.home.featured',
                'talashow.home.featured.v4',
                'talashow.home.trending',
                'talashow.home.trending.v3',
                'talashow.home.trending.v4',
                'talashow.home.mustwatch',
                'talashow.home.mustwatch.v2',
                'talashow.home.mustwatch.v4',
                'talashow.home.new_releases.v2',
                'talashow.home.new_releases.v4',
                'talashow.home.genre_rows.v3',
            ] as $key) {
                Cache::forget($key);
            }
        }
        if ($model instanceof Genre) {
            Cache::forget('talashow.home.genre_map');
            Cache::forget('talashow.browse.genres');
        }
    }
}
