<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Series;
use App\Models\SeriesReleaseNotification;
use App\Models\UserEpisode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    private const CACHE_TTL_SECONDS = 180; // 3 min — moins de requêtes DB répétées

    private const HOME_ROW_LIMIT = 10;

    private const HOME_GENRE_ROW_LIMIT = 4;

    /** @return array<int, string> */
    private function seriesCardColumns(bool $withDescription = false): array
    {
        $cols = [
            'id', 'slug', 'title', 'title_fr', 'title_en',
            'poster', 'cover_image', 'genres', 'views_count', 'rating',
            'total_episodes', 'published_at', 'is_featured', 'is_trending', 'created_at',
        ];

        if ($withDescription) {
            array_splice($cols, 4, 0, ['description', 'description_fr', 'description_en']);
        }

        return $cols;
    }

    public function index()
    {
        $episodeCountSub = static function ($q): void {
            $q->where('is_active', true);
        };

        $cardCols = $this->seriesCardColumns();
        $heroCols = $this->seriesCardColumns(true);

        $featured = Cache::remember('talashow.home.featured.v4', self::CACHE_TTL_SECONDS, function () use ($episodeCountSub, $heroCols) {
            return Series::where('is_featured', true)
                ->frontendVisible()
                ->orderBy('sort_order')
                ->limit(5)
                ->withCount(['episodes as active_episodes_count' => $episodeCountSub])
                ->get($heroCols);
        });

        // Tendances : UNIQUEMENT les séries marquées is_trending (pas de remplissage auto)
        $trending = Cache::remember('talashow.home.trending.v4', self::CACHE_TTL_SECONDS, function () use ($episodeCountSub, $cardCols) {
            return Series::where('is_trending', true)
                ->frontendVisible()
                ->orderByDesc('views_count')
                ->orderByDesc('rating')
                ->limit(self::HOME_ROW_LIMIT)
                ->withCount(['episodes as active_episodes_count' => $episodeCountSub])
                ->get($cardCols);
        });

        // Nouveautés : publiées depuis moins de 3 mois (published_at, sinon created_at)
        $newReleases = Cache::remember('talashow.home.new_releases.v4', self::CACHE_TTL_SECONDS, function () use ($episodeCountSub, $cardCols) {
            $since = now()->subMonths(3);

            return Series::frontendVisible()
                ->where(function ($q) use ($since) {
                    $q->where(function ($q2) use ($since) {
                        $q2->whereNotNull('published_at')
                            ->where('published_at', '<=', now())
                            ->where('published_at', '>=', $since);
                    })->orWhere(function ($q2) use ($since) {
                        $q2->whereNull('published_at')
                            ->where('created_at', '>=', $since);
                    });
                })
                ->orderByRaw('COALESCE(published_at, created_at) DESC')
                ->limit(self::HOME_ROW_LIMIT + 10)
                ->withCount(['episodes as active_episodes_count' => $episodeCountSub])
                ->get($cardCols);
        });

        // À ne pas manquer : meilleures notes / vues
        $mustWatch = Cache::remember('talashow.home.mustwatch.v4', self::CACHE_TTL_SECONDS, function () use ($episodeCountSub, $cardCols) {
            return Series::frontendVisible()
                ->orderByDesc('rating')
                ->orderByDesc('views_count')
                ->limit(self::HOME_ROW_LIMIT + 10)
                ->withCount(['episodes as active_episodes_count' => $episodeCountSub])
                ->get($cardCols);
        });

        // Anti-doublons entre rangées (ordre : tendances → nouveautés → à ne pas manquer)
        $usedIds = $trending->pluck('id')->all();
        $newReleases = $newReleases
            ->reject(fn ($s) => in_array($s->id, $usedIds, true))
            ->take(self::HOME_ROW_LIMIT)
            ->values();
        $usedIds = array_merge($usedIds, $newReleases->pluck('id')->all());
        $mustWatch = $mustWatch
            ->reject(fn ($s) => in_array($s->id, $usedIds, true))
            ->take(self::HOME_ROW_LIMIT)
            ->values();

        $quickGenres = Cache::remember('talashow.home.quick_genres.v2', 300, function () {
            return Genre::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name', 'name_fr', 'name_en', 'slug', 'slug_fr', 'slug_en', 'sort_order']);
        });

        $genreNameMap = $this->genreNameMap();

        $genreRows = $this->buildGenreCatalogRows($episodeCountSub);

        return view('frontend.home', compact(
            'featured',
            'newReleases',
            'trending',
            'mustWatch',
            'genreNameMap',
            'genreRows',
            'quickGenres'
        ));
    }

    /** @return array<string, string> */
    private function genreNameMap(): array
    {
        return Cache::remember('talashow.home.genre_map', 600, function () {
            $genreNameMap = [];
            $allGenres = Genre::query()->where('is_active', true)->get(['id', 'slug', 'slug_fr', 'slug_en', 'name', 'name_fr', 'name_en']);
            foreach ($allGenres as $g) {
                $label = (string) $g->nameForLocale();
                $keys = [
                    (string) ($g->slug ?? ''),
                    (string) ($g->slug_fr ?? ''),
                    (string) ($g->slug_en ?? ''),
                    (string) ($g->name ?? ''),
                    (string) ($g->name_fr ?? ''),
                    (string) ($g->name_en ?? ''),
                ];
                foreach ($keys as $k) {
                    $k = strtolower(trim((string) $k));
                    if ($k !== '' && ! isset($genreNameMap[$k])) {
                        $genreNameMap[$k] = $label;
                    }
                }
            }

            return $genreNameMap;
        });
    }

    /**
     * @return array<int, array{genre: Genre, series: \Illuminate\Support\Collection, url: string}>
     */
    private function buildGenreCatalogRows(callable $episodeCountSub): array
    {
        $cols = $this->seriesCardColumns();

        return Cache::remember('talashow.home.genre_rows.v3', self::CACHE_TTL_SECONDS, function () use ($episodeCountSub, $cols) {
            $genres = Genre::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(self::HOME_GENRE_ROW_LIMIT)
                ->get();

            $rows = [];
            foreach ($genres as $genre) {
                $canonical = (string) $genre->slug;
                $series = Series::frontendVisible()
                    ->where(function ($q) use ($canonical, $genre) {
                        $q->whereJsonContains('genres', $canonical)
                            ->orWhereJsonContains('genres', $genre->name);
                    })
                    ->orderByDesc('views_count')
                    ->orderByDesc('rating')
                    ->limit(8)
                    ->withCount(['episodes as active_episodes_count' => $episodeCountSub])
                    ->get($cols);

                if ($series->count() >= 4) {
                    $rows[] = [
                        'genre' => $genre,
                        'series' => $series,
                        'url' => route('browse', ['genre' => $genre->slugForLocale()]),
                    ];
                }
            }

            return $rows;
        });
    }

    public function browse(Request $request)
    {
        $query = Series::frontendVisible();

        // Filtre par genre
        if ($request->has('genre') && $request->genre !== 'all') {
            $slug = (string) $request->genre;
            $genre = Genre::query()
                ->where('slug', $slug)
                ->orWhere('slug_fr', $slug)
                ->orWhere('slug_en', $slug)
                ->first();
            if ($genre) {
                $canonical = (string) $genre->slug; // series.genres stocke le slug canonique
                // Compat: anciennes données peuvent contenir le nom au lieu du slug.
                $query->where(function ($q) use ($canonical, $genre) {
                    $q->whereJsonContains('genres', $canonical)
                      ->orWhereJsonContains('genres', $genre->name);
                });
            } else {
                $query->whereJsonContains('genres', $slug);
            }
        }

        // Recherche
        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('title_fr', 'like', "%{$search}%")
                  ->orWhere('title_en', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('description_fr', 'like', "%{$search}%")
                  ->orWhere('description_en', 'like', "%{$search}%");
            });
        }

        $genres = Cache::remember('talashow.browse.genres', 300, function () {
            return Genre::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'name_fr', 'name_en', 'slug', 'slug_fr', 'slug_en', 'sort_order']);
        });

        $isCatalogMode = ! $request->filled('search')
            && (! $request->has('genre') || $request->genre === 'all' || $request->genre === '');

        $episodeCountSub = static function ($q): void {
            $q->where('is_active', true);
        };

        $newReleases = collect();
        $mustWatch = collect();
        $trending = collect();
        $genreRows = [];
        $genreNameMap = $this->genreNameMap();

        if ($isCatalogMode) {
            $cardCols = $this->seriesCardColumns();
            $rowLimit = self::HOME_ROW_LIMIT;
            $since = now()->subMonths(3);

            $trending = Series::where('is_trending', true)
                ->frontendVisible()
                ->orderByDesc('views_count')
                ->orderByDesc('rating')
                ->limit($rowLimit)
                ->withCount(['episodes as active_episodes_count' => $episodeCountSub])
                ->get($cardCols);

            $newReleases = Series::frontendVisible()
                ->where(function ($q) use ($since) {
                    $q->where(function ($q2) use ($since) {
                        $q2->whereNotNull('published_at')
                            ->where('published_at', '<=', now())
                            ->where('published_at', '>=', $since);
                    })->orWhere(function ($q2) use ($since) {
                        $q2->whereNull('published_at')
                            ->where('created_at', '>=', $since);
                    });
                })
                ->orderByRaw('COALESCE(published_at, created_at) DESC')
                ->limit($rowLimit + 10)
                ->withCount(['episodes as active_episodes_count' => $episodeCountSub])
                ->get($cardCols);

            $mustWatch = Series::frontendVisible()
                ->orderByDesc('rating')
                ->orderByDesc('views_count')
                ->limit($rowLimit + 10)
                ->withCount(['episodes as active_episodes_count' => $episodeCountSub])
                ->get($cardCols);

            $usedIds = $trending->pluck('id')->all();
            $newReleases = $newReleases
                ->reject(fn ($s) => in_array($s->id, $usedIds, true))
                ->take($rowLimit)
                ->values();
            $usedIds = array_merge($usedIds, $newReleases->pluck('id')->all());
            $mustWatch = $mustWatch
                ->reject(fn ($s) => in_array($s->id, $usedIds, true))
                ->take($rowLimit)
                ->values();

            $genreRows = $this->buildGenreCatalogRows($episodeCountSub);
            $series = $query->orderByDesc('created_at')->paginate(18);
        } else {
            $series = $query->orderBy('created_at', 'desc')->paginate(24);
        }

        return view('frontend.browse', compact(
            'series',
            'genres',
            'isCatalogMode',
            'newReleases',
            'mustWatch',
            'trending',
            'genreRows',
            'genreNameMap'
        ));
    }

    public function showSeries($slug)
    {
        // Brouillon = invisible (404).
        // Programmé (published_at futur) = page "Disponible le ..."
        $series = Series::where('slug', $slug)
            ->where('is_active', true)
            ->with(['episodes' => function($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('episode_number');
            }])
            ->firstOrFail();

        if (!$series->isPublished()) {
            $alreadyRequested = false;
            if (Auth::check()) {
                $alreadyRequested = SeriesReleaseNotification::query()
                    ->where('series_id', $series->id)
                    ->where('user_id', Auth::id())
                    ->exists();
            }
            return view('frontend.series.scheduled', compact('series', 'alreadyRequested'));
        }

        // IMPORTANT: vues "YouTube-like" = incrémentées au moment du playback d'un épisode.
        // On n'incrémente plus au simple affichage de la page série.

        $episodeUserStates = collect();
        if (Auth::check() && $series->episodes->isNotEmpty()) {
            $episodeUserStates = UserEpisode::query()
                ->where('user_id', Auth::id())
                ->whereIn('episode_id', $series->episodes->pluck('id'))
                ->get(['episode_id', 'watch_progress', 'is_completed', 'is_unlocked'])
                ->keyBy('episode_id');
        }

        return view('frontend.series.show', compact('series', 'episodeUserStates'));
    }
}
