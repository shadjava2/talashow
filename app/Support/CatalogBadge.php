<?php

namespace App\Support;

use App\Models\Series;
use Carbon\Carbon;

final class CatalogBadge
{
    /**
     * @return 'hot'|'new'|'exclusive'|null
     */
    public static function forSeries(Series $series): ?string
    {
        if ($series->is_trending) {
            return 'hot';
        }

        $created = $series->created_at;
        if ($created instanceof Carbon && $created->gte(now()->subDays(21))) {
            return 'new';
        }

        if ($series->is_featured) {
            return 'exclusive';
        }

        return null;
    }
}
