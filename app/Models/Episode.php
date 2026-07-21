<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Episode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'series_id',
        'episode_number',
        'display_label',
        'title',
        'title_fr',
        'title_en',
        'description',
        'description_fr',
        'description_en',
        'thumbnail',
        'video_url',
        'video_urls',
        'video_type',
        'duration',
        'is_free',
        'is_premium_only',
        'unlock_coins',
        'views_count',
        'sort_order',
        'is_active',
        'published_at',
        'notified_newsletter_at',
        'video_provider',
        'external_video_id',
        'playback_url',
        'hls_url',
        'video_status',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_premium_only' => 'boolean',
        'is_active' => 'boolean',
        'video_urls' => 'array',
        'published_at' => 'datetime',
        'notified_newsletter_at' => 'datetime',
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
     * UI label (Episode 1 / Épisode 1) that stays consistent with the current locale.
     * If display_label looks like a default label ("ep/episode/épisode + number"), we localize it.
     * Otherwise, we keep the custom display_label as-is.
     */
    public function labelForLocale(?string $locale = null): string
    {
        $loc = strtolower((string) ($locale ?: app()->getLocale()));
        $ui = (string) __('ui.labels.episode_singular', [], $loc);
        $num = (string) ($this->episode_number ?? '');

        $raw = trim((string) ($this->display_label ?? ''));
        if ($raw === '') {
            return trim($ui . ' ' . $num);
        }

        $rawLower = strtolower($raw);
        // If it starts like a generic episode label, treat it as "default-like" and localize.
        $defaultLike = preg_match('/^(ep|episode|épisode)\b/u', $rawLower) === 1;

        // Also treat exact match (localized label + number) as default-like.
        if (!$defaultLike && $num !== '' && preg_match('/\b' . preg_quote(strtolower($num), '/') . '\b/', $rawLower)) {
            $defaultLike = true;
        }

        return $defaultLike ? trim($ui . ' ' . $num) : $raw;
    }

    /**
     * Disponible = actif + (publication immédiate OU date atteinte).
     */
    public function isPublished(): bool
    {
        return $this->is_active && ($this->published_at === null || $this->published_at->lte(now()));
    }

    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function userEpisodes()
    {
        return $this->hasMany(UserEpisode::class);
    }

    public function watchHistory()
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function providerMappings()
    {
        return $this->hasMany(VideoProviderMapping::class, 'video_id');
    }

    public function isUnlockedForUser(?User $user = null): bool
    {
        if ($this->is_free) {
            return true;
        }

        if (! $user) {
            return false;
        }

        // Déblocage enregistré (pièces, abonnement, etc.) — prioritaire sur le flag VIP.
        $userEpisode = $this->userEpisodes()
            ->where('user_id', $user->id)
            ->where('is_unlocked', true)
            ->where(function ($q) {
                $q->where('unlock_method', '!=', 'coins')
                    ->orWhereNull('unlocked_until')
                    ->orWhere('unlocked_until', '>', now());
            })
            ->first();

        if ($userEpisode !== null) {
            return true;
        }

        if ($user->hasActiveSubscription()) {
            return true;
        }

        // VIP sans coût en pièces : abonnement uniquement (pas d’accès gratuit avec solde).
        if ($this->is_premium_only && $this->coinUnlockCost() <= 0) {
            return false;
        }

        return false;
    }

    /** Coût en pièces pour débloquer cet épisode (0 = pas de déblocage par pièces, sauf VIP sans montant → défaut). */
    public function coinUnlockCost(): int
    {
        $cost = max(0, (int) ($this->unlock_coins ?? 0));
        if ($cost === 0 && $this->is_premium_only) {
            return max(1, (int) config('app.default_vip_unlock_coins', 50));
        }

        return $cost;
    }

    public function canBeUnlockedByCoins(): bool
    {
        return ! $this->is_free && $this->coinUnlockCost() > 0;
    }
}
