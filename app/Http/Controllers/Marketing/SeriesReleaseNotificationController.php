<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Models\SeriesReleaseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SeriesReleaseNotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, string $slug)
    {
        $series = Series::query()
            ->where('slug', $slug)
            ->where('is_active', true) // brouillon = invisible
            ->firstOrFail();

        if ($series->isPublished()) {
            return back()->with('success', "Cette série est déjà disponible.");
        }

        $user = Auth::user();
        SeriesReleaseNotification::query()->updateOrCreate(
            ['series_id' => $series->id, 'user_id' => $user->id],
            ['locale' => app()->getLocale()]
        );

        return back()->with('success', "Parfait ✅ On vous notifiera dès que la série sera disponible.");
    }
}

