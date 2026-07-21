<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::query()
            ->where('is_active', true)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)
                    ->orWhere('slug_fr', $slug)
                    ->orWhere('slug_en', $slug);
            })
            ->firstOrFail();
        return view('frontend.pages.show', compact('page'));
    }

    public function showLegal(string $slug)
    {
        return $this->show($slug);
    }
}

