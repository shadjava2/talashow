<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Series extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'title_fr',
        'title_en',
        'slug',
        'slug_fr',
        'slug_en',
        'description',
        'description_fr',
        'description_en',
        'poster',
        'cover_image',
        'trailer_url',
        'total_episodes',
        'duration',
        'release_year',
        'language',
        'video_languages',
        'genres',
        'tags',
        'is_exclusive',
        'is_featured',
        'is_trending',
        'views_count',
        'likes_count',
        'rating',
        'sort_order',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'genres' => 'array',
        'tags' => 'array',
        'video_languages' => 'array',
        'is_exclusive' => 'boolean',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function titleForLocale(?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();
        $loc = strtolower((string) $loc);

        if (str_starts_with($loc, 'en')) {
            return (string) ($this->title_en ?: $this->title_fr ?: $this->title ?: '');
        }
        return (string) ($this->title_fr ?: $this->title_en ?: $this->title ?: '');
    }

    public function descriptionForLocale(?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();
        $loc = strtolower((string) $loc);

        if (str_starts_with($loc, 'en')) {
            return (string) ($this->description_en ?: $this->description_fr ?: $this->description ?: '');
        }
        return (string) ($this->description_fr ?: $this->description_en ?: $this->description ?: '');
    }

    /**
     * Slug “SEO” localisé pour le frontend.
     * IMPORTANT: on conserve `slug` comme identifiant canonique (routes/relations existantes).
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

    /**
     * Visible sur le frontend (listing) = actif, même si programmé.
     * La lecture / disponibilité réelle est gérée par isPublished().
     */
    public function scopeFrontendVisible($query)
    {
        return $query->where('is_active', true);
    }

    public function isPublished(): bool
    {
        return $this->is_active && ($this->published_at === null || $this->published_at->lte(now()));
    }

    public function episodes()
    {
        return $this->hasMany(Episode::class)
            ->orderBy('sort_order')
            ->orderBy('episode_number');
    }

    public function activeEpisodes()
    {
        return $this->hasMany(Episode::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('episode_number');
    }

    public function freeEpisodes()
    {
        return $this->hasMany(Episode::class)
            ->where('is_free', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('episode_number');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function likes()
    {
        return $this->hasMany(SeriesLike::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
