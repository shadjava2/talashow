<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'slug_fr',
        'slug_en',
        'title',
        'title_fr',
        'title_en',
        'content',
        'content_fr',
        'content_en',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function contentForLocale(?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();
        $loc = strtolower((string) $loc);

        if (str_starts_with($loc, 'en')) {
            return (string) ($this->content_en ?: $this->content_fr ?: $this->content ?: '');
        }
        return (string) ($this->content_fr ?: $this->content_en ?: $this->content ?: '');
    }

    public function slugForLocale(?string $locale = null): string
    {
        $loc = $locale ?: app()->getLocale();
        $loc = strtolower((string) $loc);

        if (str_starts_with($loc, 'en')) {
            return (string) ($this->slug_en ?: $this->slug_fr ?: $this->slug ?: '');
        }
        return (string) ($this->slug_fr ?: $this->slug_en ?: $this->slug ?: '');
    }
}

