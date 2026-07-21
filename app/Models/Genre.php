<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $fillable = [
        'name',
        'name_fr',
        'name_en',
        'slug',
        'slug_fr',
        'slug_en',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function nameForLocale(?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();
        $loc = strtolower((string) $loc);

        if (str_starts_with($loc, 'en')) {
            return (string) ($this->name_en ?: $this->name_fr ?: $this->name ?: '');
        }
        return (string) ($this->name_fr ?: $this->name_en ?: $this->name ?: '');
    }

    /**
     * Slug localisé pour la navigation (paramètre browse).
     * IMPORTANT: on conserve `slug` comme slug canonique “interne” (stocké dans series.genres JSON).
     */
    public function slugForLocale(?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();
        $loc = strtolower((string) $loc);

        if (str_starts_with($loc, 'en')) {
            return (string) ($this->slug_en ?: $this->slug_fr ?: $this->slug ?: '');
        }
        return (string) ($this->slug_fr ?: $this->slug_en ?: $this->slug ?: '');
    }

    public function matchesAnySlug(string $slug): bool
    {
        $s = (string) $slug;
        return $s !== '' && ($s === $this->slug || $s === $this->slug_fr || $s === $this->slug_en);
    }
}

