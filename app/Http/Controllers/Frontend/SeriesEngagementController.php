<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Series;
use App\Models\SeriesLike;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeriesEngagementController extends Controller
{
    public function toggleFavorite(Request $request, Series $series)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'auth_required'], 401);
        }

        $existing = Favorite::query()
            ->where('user_id', $user->id)
            ->where('series_id', $series->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['success' => true, 'favorited' => false]);
        }

        Favorite::create([
            'user_id' => $user->id,
            'series_id' => $series->id,
        ]);

        return response()->json(['success' => true, 'favorited' => true]);
    }

    public function toggleLike(Request $request, Series $series)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'auth_required'], 401);
        }

        try {
            return DB::transaction(function () use ($user, $series) {
                $existing = SeriesLike::query()
                    ->where('user_id', $user->id)
                    ->where('series_id', $series->id)
                    ->first();

                if ($existing) {
                    $existing->delete();
                    // garde-fou: pas de compteur négatif
                    Series::whereKey($series->id)->where('likes_count', '>', 0)->decrement('likes_count');
                    $fresh = Series::select(['id', 'likes_count'])->find($series->id);
                    return response()->json([
                        'success' => true,
                        'liked' => false,
                        'likes_count' => (int) ($fresh?->likes_count ?? 0),
                    ]);
                }

                SeriesLike::create([
                    'user_id' => $user->id,
                    'series_id' => $series->id,
                ]);
                Series::whereKey($series->id)->increment('likes_count');
                $fresh = Series::select(['id', 'likes_count'])->find($series->id);

                return response()->json([
                    'success' => true,
                    'liked' => true,
                    'likes_count' => (int) ($fresh?->likes_count ?? 0),
                ]);
            }, 3);
        } catch (QueryException $e) {
            // Si collision unique (double clic / latence) => on répond "OK" avec l'état actuel
            $existing = SeriesLike::query()
                ->where('user_id', $user->id)
                ->where('series_id', $series->id)
                ->exists();
            $fresh = Series::select(['id', 'likes_count'])->find($series->id);
            return response()->json([
                'success' => true,
                'liked' => $existing,
                'likes_count' => (int) ($fresh?->likes_count ?? 0),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'server_error'], 500);
        }
    }
}

