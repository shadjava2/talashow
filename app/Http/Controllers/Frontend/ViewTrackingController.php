<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\EpisodeView;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ViewTrackingController extends Controller
{
    /**
     * YouTube-like: 1 seule vue "unique" par utilisateur (par épisode).
     * Les replays n'augmentent pas views_count, mais on garde un play_count en interne.
     */
    public function episode(Request $request, Episode $episode)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'auth_required'], 401);
        }

        try {
            $out = DB::transaction(function () use ($user, $episode) {
                $now = now();

                // --- Episode view (unique per user) ---
                $createdEpisodeView = false;
                $episodeView = EpisodeView::query()
                    ->where('user_id', $user->id)
                    ->where('episode_id', $episode->id)
                    ->lockForUpdate()
                    ->first();

                if (!$episodeView) {
                    $episodeView = EpisodeView::create([
                        'user_id' => $user->id,
                        'episode_id' => $episode->id,
                        'first_played_at' => $now,
                        'last_played_at' => $now,
                        'play_count' => 1,
                    ]);
                    $createdEpisodeView = true;
                } else {
                    $episodeView->last_played_at = $now;
                    $episodeView->play_count = (int) $episodeView->play_count + 1;
                    $episodeView->save();
                }

                if ($createdEpisodeView) {
                    Episode::whereKey($episode->id)->increment('views_count');
                }

                $freshEpisode = Episode::select(['id', 'views_count'])->find($episode->id);

                return [
                    'episode_views_count' => (int) ($freshEpisode?->views_count ?? 0),
                ];
            }, 3);

            return response()->json([
                'success' => true,
                ...$out,
            ]);
        } catch (QueryException $e) {
            // collision unique => on répond avec l'état courant (best-effort)
            $freshEpisode = Episode::select(['id', 'views_count'])->find($episode->id);
            return response()->json([
                'success' => true,
                'episode_views_count' => (int) ($freshEpisode?->views_count ?? 0),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'server_error'], 500);
        }
    }
}

