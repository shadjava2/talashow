<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\EpisodeReleaseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EpisodeReleaseNotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, string $seriesSlug, int $episodeId)
    {
        $episode = Episode::query()
            ->where('id', $episodeId)
            ->where('is_active', true)
            ->with('series:id,slug,is_active')
            ->firstOrFail();

        // Série brouillon -> pas de notification
        if (!$episode->series || !$episode->series->is_active || $episode->series->slug !== $seriesSlug) {
            abort(404);
        }

        if ($episode->isPublished()) {
            return back()->with('success', "Cet épisode est déjà disponible.");
        }

        $user = Auth::user();
        EpisodeReleaseNotification::query()->updateOrCreate(
            ['episode_id' => $episode->id, 'user_id' => $user->id],
            ['locale' => app()->getLocale()]
        );

        return back()->with('success', "Parfait ✅ On vous notifiera dès que l’épisode sera disponible.");
    }
}

