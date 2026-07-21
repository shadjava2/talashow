<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Favorite;
use App\Models\Series;
use App\Models\SeriesLike;
use App\Models\UserEpisode;
use App\Models\VideoLanguage;
use App\Models\WatchHistory;
use App\Services\EpisodeCoinUnlockService;
use App\Services\SecurityAuditService;
use App\Services\Video\VideoPlaybackResolverService;
use App\Services\Video\VideoSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class EpisodeController extends Controller
{
    public function show(Request $request, $seriesSlug, $episodeId)
    {
        // Brouillon = invisible ; programmé = renvoyer vers la page série ("Disponible le ...")
        $series = Series::where('slug', $seriesSlug)->where('is_active', true)->firstOrFail();
        if (!$series->isPublished()) {
            return redirect()->route('series.show', $series->slug);
        }
        $episode = Episode::where('id', $episodeId)
            ->where('series_id', $series->id)
            ->where('is_active', true)
            ->with('series')
            ->firstOrFail();

        // Épisode programmé: page dédiée (Disponible le ... + Notifier moi)
        if (!$episode->isPublished()) {
            return view('frontend.episode.scheduled', compact('episode', 'series'));
        }

        $allEpisodes = Episode::where('series_id', $series->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('episode_number')
            ->get();

        // ---- Langues vidéo (sélection de playback) ----
        $seriesLangs = $series->video_languages;
        $seriesLangs = is_array($seriesLangs) ? $seriesLangs : [];
        $seriesLangs = array_values(array_unique(array_filter(array_map(fn($v) => strtolower(trim((string) $v)), $seriesLangs))));
        $seriesDefaultLang = $seriesLangs[0] ?? 'fr';

        $requestedLang = strtolower(trim((string) $request->query('vlang', '')));
        $explicitLang = $requestedLang !== '';
        if ($explicitLang && !in_array($requestedLang, $seriesLangs, true)) {
            $requestedLang = '';
            $explicitLang = false;
        }

        $selectedVideoLang = $explicitLang ? $requestedLang : $seriesDefaultLang;

        $videoUrls = $episode->video_urls;
        $videoUrls = is_array($videoUrls) ? $videoUrls : [];
        if (empty($videoUrls) && !empty($episode->video_url)) {
            $videoUrls[$seriesDefaultLang] = (string) $episode->video_url;
        }

        $selectedVideoUrl = trim((string) ($videoUrls[$selectedVideoLang] ?? ''));

        // Si aucune langue n'est demandée explicitement, on peut fallback sur 1 langue dispo (robustesse legacy).
        if (!$explicitLang && $selectedVideoUrl === '') {
            foreach ($seriesLangs as $code) {
                $u = trim((string) ($videoUrls[$code] ?? ''));
                if ($u !== '') {
                    $selectedVideoLang = $code;
                    $selectedVideoUrl = $u;
                    break;
                }
            }
        }

        // Page épisode : uniquement lecteur Bunny (iframe /play/…), pas de Video.js / HLS custom.
        config(['video.playback_driver' => 'bunny_embed']);
        $playbackMeta = app(VideoPlaybackResolverService::class)->resolve(
            $episode,
            $selectedVideoLang,
            $selectedVideoUrl,
            $episode->thumbnail
        );
        $selectedVideoUrl = (string) ($playbackMeta['hls_url'] ?? $selectedVideoUrl);

        // Labels des langues (depuis la table admin) - best-effort
        $videoLangLabels = [];
        try {
            if (Schema::hasTable('video_languages')) {
                $rows = VideoLanguage::query()
                    ->whereIn('code', $seriesLangs)
                    ->get(['code', 'name', 'native_name']);
                foreach ($rows as $r) {
                    $videoLangLabels[strtolower((string) $r->code)] = [
                        'name' => (string) $r->name,
                        'native_name' => (string) ($r->native_name ?? ''),
                    ];
                }
            }
        } catch (\Throwable) {
            $videoLangLabels = [];
        }

        $videoLangOptions = array_map(function ($code) use ($videoLangLabels, $videoUrls) {
            $meta = $videoLangLabels[$code] ?? null;
            $label = $meta ? ($meta['name'] ?: strtoupper($code)) : strtoupper($code);
            $native = $meta ? ($meta['native_name'] ?? '') : '';
            $available = trim((string) ($videoUrls[$code] ?? '')) !== '';
            return [
                'code' => $code,
                'label' => $label,
                'native' => $native,
                'available' => $available,
            ];
        }, $seriesLangs);

        $user = Auth::user();
        if ($user && $episode->coinUnlockCost() > 0 && $user->total_coins >= $episode->coinUnlockCost()) {
            app(EpisodeCoinUnlockService::class)->unlockWithCoinsIfNeeded($user, $episode);
            $user->refresh();
        }
        $isUnlocked = $episode->isUnlockedForUser($user);
        $hasSubscription = false;
        if ($user) {
            $hasSubscription = $user->hasActiveSubscription();
        }

        if (config('video_security.playback_gate_enabled')
            && ($isUnlocked || $episode->is_free)
            && (($playbackMeta['mode'] ?? '') === 'embed')
            && trim((string) ($playbackMeta['embed_url'] ?? '')) !== '') {
            $gate = app(VideoSecurityService::class)->issueGateUrl(
                $episode,
                $selectedVideoLang,
                $request
            );
            if ($gate !== null) {
                $playbackMeta['embed_url'] = $gate;
            }
        }

        $coinsNeeded = $episode->coinUnlockCost();
        $canUnlockWithCoins = $user && ! $isUnlocked && $coinsNeeded > 0 && $user->total_coins >= $coinsNeeded;
        $canUnlockWithSubscription = $user && ! $isUnlocked && $episode->is_premium_only && $coinsNeeded <= 0 && $hasSubscription;
        $canUnlock = $canUnlockWithCoins || $canUnlockWithSubscription;
        $needsMoreCoins = $user && ! $isUnlocked && $coinsNeeded > 0 && $user->total_coins < $coinsNeeded;
        $needsSubscriptionOnly = $user && ! $isUnlocked && $episode->is_premium_only && $coinsNeeded <= 0 && ! $hasSubscription;
        $unlockedEpisodeIds = [];
        $coinUnlocks = [];
        $currentCoinUnlockUntil = null;
        if ($user) {
            // Optimisation: une seule requête pour savoir quels épisodes sont déjà débloqués (coins non expirés).
            $unlockedEpisodeIds = UserEpisode::where('user_id', $user->id)
                ->where('is_unlocked', true)
                ->where(function ($q) {
                    $q->where('unlock_method', '!=', 'coins')
                      ->orWhereNull('unlocked_until')
                      ->orWhere('unlocked_until', '>', now());
                })
                ->pluck('episode_id')
                ->all();

            // Map des expirations coin par épisode (pour badges UI)
            $coinUnlocks = UserEpisode::where('user_id', $user->id)
                ->where('is_unlocked', true)
                ->where('unlock_method', 'coins')
                ->whereNotNull('unlocked_until')
                ->pluck('unlocked_until', 'episode_id')
                ->toArray();

            $currentCoinUnlockUntil = $coinUnlocks[$episode->id] ?? null;
        }

        // Récupérer la progression de lecture
        $watchProgress = 0;
        if (Auth::check()) {
            $userEpisode = UserEpisode::where('user_id', Auth::id())
                ->where('episode_id', $episode->id)
                ->first();

            if ($userEpisode) {
                $watchProgress = $userEpisode->watch_progress;
            }
        } else {
            // Pour les utilisateurs non connectés, utiliser session_id
            $sessionId = session()->getId();
            $history = WatchHistory::where('episode_id', $episode->id)
                ->where('session_id', $sessionId)
                ->orderBy('watched_at', 'desc')
                ->first();

            if ($history) {
                $watchProgress = $history->watch_time;
            }
        }

        // IMPORTANT: vues "YouTube-like" gérées au moment du playback (endpoint /episode/{id}/view).
        // On n'incrémente plus au simple chargement de page.

        $isFavorited = false;
        $isLiked = false;
        if ($user) {
            $isFavorited = Favorite::query()
                ->where('user_id', $user->id)
                ->where('series_id', $series->id)
                ->exists();
            $isLiked = SeriesLike::query()
                ->where('user_id', $user->id)
                ->where('series_id', $series->id)
                ->exists();
        }

        // Épisode suivant : URLs pour prefetch (page + HLS résolu comme l’épisode courant)
        $resolver = app(VideoPlaybackResolverService::class);
        $epsCollection = $allEpisodes->values();
        $curEpIdx = $epsCollection->search(fn ($e) => $e->id === $episode->id);
        $nextEpisodeModel = ($curEpIdx !== false) ? $epsCollection->get($curEpIdx + 1) : null;
        $nextEpisodeUrl = $nextEpisodeModel
            ? route('episode.show', [$series->slug, $nextEpisodeModel->id])
            : null;
        $nextVideoPrefetchUrl = null;
        if ($nextEpisodeModel) {
            $nMap = is_array($nextEpisodeModel->video_urls) ? $nextEpisodeModel->video_urls : [];
            if ($nMap === [] && ! empty($nextEpisodeModel->video_url)) {
                $nMap[$seriesDefaultLang] = (string) $nextEpisodeModel->video_url;
            }
            $nextRaw = trim((string) ($nMap[$selectedVideoLang] ?? ''));
            if ($nextRaw === '' && ! $explicitLang) {
                foreach ($seriesLangs as $code) {
                    $u = trim((string) ($nMap[$code] ?? ''));
                    if ($u !== '') {
                        $nextRaw = $u;
                        break;
                    }
                }
            }
            if ($nextRaw !== '') {
                $nextMeta = $resolver->resolve(
                    $nextEpisodeModel,
                    $selectedVideoLang,
                    $nextRaw,
                    $nextEpisodeModel->thumbnail
                );
                $nextVideoPrefetchUrl = (string) ($nextMeta['hls_url'] ?? '');
            }
        }

        return view('frontend.episode.show', compact(
            'episode',
            'series',
            'allEpisodes',
            'isUnlocked',
            'canUnlock',
            'watchProgress',
            'hasSubscription',
            'unlockedEpisodeIds',
            'coinUnlocks',
            'currentCoinUnlockUntil',
            'isFavorited',
            'isLiked',
            'selectedVideoLang',
            'selectedVideoUrl',
            'explicitLang',
            'videoLangOptions',
            'playbackMeta',
            'nextEpisodeUrl',
            'nextVideoPrefetchUrl',
            'coinsNeeded',
            'needsMoreCoins',
            'needsSubscriptionOnly',
        ));
    }

    /**
     * Métadonnées de lecture résolues (HLS, poster, provider) — sans secrets.
     */
    public function playback(Request $request, $seriesSlug, $episodeId)
    {
        $series = Series::where('slug', $seriesSlug)->where('is_active', true)->firstOrFail();
        if (! $series->isPublished()) {
            return response()->json(['message' => 'Série indisponible'], 404);
        }
        $episode = Episode::where('id', $episodeId)
            ->where('series_id', $series->id)
            ->where('is_active', true)
            ->firstOrFail();

        if (! $episode->isPublished()) {
            return response()->json(['message' => 'Épisode indisponible'], 404);
        }

        $user = Auth::user();
        if ($user && $episode->coinUnlockCost() > 0 && $user->total_coins >= $episode->coinUnlockCost()) {
            app(EpisodeCoinUnlockService::class)->unlockWithCoinsIfNeeded($user, $episode);
            $user->refresh();
        }
        if (! $episode->isUnlockedForUser($user)) {
            SecurityAuditService::securityEvent('playback_denied', 'medium', [
                'episode_id' => $episode->id,
                'series_slug' => $seriesSlug,
            ], $request);

            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $seriesLangs = $series->video_languages;
        $seriesLangs = is_array($seriesLangs) ? $seriesLangs : [];
        $seriesLangs = array_values(array_unique(array_filter(array_map(fn ($v) => strtolower(trim((string) $v)), $seriesLangs))));
        $seriesDefaultLang = $seriesLangs[0] ?? 'fr';

        $requestedLang = strtolower(trim((string) $request->query('vlang', '')));
        if ($requestedLang !== '' && ! in_array($requestedLang, $seriesLangs, true)) {
            $requestedLang = '';
        }
        $selectedVideoLang = $requestedLang !== '' ? $requestedLang : $seriesDefaultLang;

        $videoUrls = $episode->video_urls;
        $videoUrls = is_array($videoUrls) ? $videoUrls : [];
        if (empty($videoUrls) && ! empty($episode->video_url)) {
            $videoUrls[$seriesDefaultLang] = (string) $episode->video_url;
        }

        $selectedVideoUrl = trim((string) ($videoUrls[$selectedVideoLang] ?? ''));
        if ($selectedVideoUrl === '') {
            foreach ($seriesLangs as $code) {
                $u = trim((string) ($videoUrls[$code] ?? ''));
                if ($u !== '') {
                    $selectedVideoLang = $code;
                    $selectedVideoUrl = $u;
                    break;
                }
            }
        }

        config(['video.playback_driver' => 'bunny_embed']);
        $meta = app(VideoPlaybackResolverService::class)->resolve(
            $episode,
            $selectedVideoLang,
            $selectedVideoUrl,
            $episode->thumbnail
        );

        if (config('video_security.playback_gate_enabled')
            && (($meta['mode'] ?? '') === 'embed')
            && trim((string) ($meta['embed_url'] ?? '')) !== '') {
            $gate = app(VideoSecurityService::class)->issueGateUrl(
                $episode,
                $selectedVideoLang,
                $request
            );
            if ($gate !== null) {
                $meta['embed_url'] = $gate;
            }
        }

        return response()->json([
            'provider' => $meta['provider'],
            'mode' => $meta['mode'] ?? 'hls',
            'hls_url' => $meta['hls_url'],
            'embed_url' => $meta['embed_url'] ?? '',
            'poster' => $meta['poster'],
            'subtitles' => $meta['subtitles'],
            'is_ready' => $meta['is_ready'],
        ]);
    }

    public function unlock(Request $request, $episodeId)
    {
        $episode = Episode::findOrFail($episodeId);
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être connecté pour débloquer un épisode.',
            ], 401);
        }

        $result = app(EpisodeCoinUnlockService::class)->unlockManually($user, $episode);

        if (! $result['success']) {
            $status = str_contains($result['message'], 'abonnement') ? 403 : 400;
            $payload = [
                'success' => false,
                'message' => $result['message'],
            ];
            if ($user->total_coins < (int) ($episode->unlock_coins ?? 0)) {
                $payload['redirect_to'] = route('payment.recharge');
            }

            return response()->json($payload, $status);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'coins_remaining' => $result['coins_remaining'] ?? $user->fresh()->total_coins,
        ]);
    }

    public function updateProgress(Request $request, $episodeId)
    {
        $validated = $request->validate([
            'progress' => 'required|integer|min:0',
            'duration' => 'nullable|integer',
        ]);

        $episode = Episode::findOrFail($episodeId);
        $user = Auth::user();

        if ($user) {
            UserEpisode::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'episode_id' => $episode->id,
                ],
                [
                    'watch_progress' => $validated['progress'],
                    'is_completed' => isset($validated['duration']) && $validated['progress'] >= ($validated['duration'] * 0.9),
                ]
            );

            // Enregistrer dans l'historique
            WatchHistory::create([
                'user_id' => $user->id,
                'episode_id' => $episode->id,
                'watch_time' => $validated['progress'],
                'duration' => $validated['duration'] ?? null,
                'is_completed' => isset($validated['duration']) && $validated['progress'] >= ($validated['duration'] * 0.9),
                'watched_at' => now(),
            ]);
        } else {
            // Pour les utilisateurs non connectés
            $sessionId = session()->getId();
            WatchHistory::create([
                'session_id' => $sessionId,
                'episode_id' => $episode->id,
                'watch_time' => $validated['progress'],
                'duration' => $validated['duration'] ?? null,
                'is_completed' => isset($validated['duration']) && $validated['progress'] >= ($validated['duration'] * 0.9),
                'watched_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
