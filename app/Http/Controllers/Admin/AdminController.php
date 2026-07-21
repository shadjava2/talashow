<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\Series;
use App\Models\SeriesReleaseNotification;
use App\Models\Transaction;
use App\Models\User;
use App\Models\VideoLanguage;
use App\Services\BunnyStorageService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function __construct(private BunnyStorageService $bunnyStorage)
    {
        // Protection fine gérée par routes: adminapp + permissions
        $this->middleware(['auth', 'adminapp']);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function uploadMediaFile(\Illuminate\Http\UploadedFile $file, string $folder, array $meta = []): ?string
    {
        $r = $this->bunnyStorage->upload($file, $folder, $meta);

        return ($r['success'] ?? false) ? ($r['url'] ?? null) : null;
    }

    public function dashboard()
    {
        $totalSeries = Series::count();
        $activeSeries = Series::query()->where('is_active', true)->count();
        $totalEpisodes = Episode::count();
        $activeEpisodes = Episode::query()->where('is_active', true)->count();

        $stats = [
            'total_series' => $totalSeries,
            'active_series' => $activeSeries,
            'total_episodes' => $totalEpisodes,
            'active_episodes' => $activeEpisodes,
            'total_users' => User::count(),
            'total_revenue' => Transaction::where('status', 'completed')->sum('amount'),
            'active_subscriptions' => \App\Models\Subscription::where('is_active', true)->count(),
        ];

        $recent_series = Series::query()->withCount('episodes')->latest()->limit(5)->get();
        $recent_transactions = Transaction::query()->with('user')->latest()->limit(10)->get();

        return view('admin.dashboard', compact('stats', 'recent_series', 'recent_transactions'));
    }

    public function series()
    {
        // Ordre “homepage” d’abord : plus petit sort_order = plus haut, puis vedettes, puis récent
        $series = Series::withCount('episodes')
            ->orderBy('sort_order')
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.series.index', compact('series'));
    }

    /**
     * Place la série en 1ʳᵉ position du carrousel (vedette + ordre minimal).
     */
    public function promoteSeries(int $id)
    {
        $series = Series::findOrFail($id);
        $min = Series::query()->min('sort_order');
        $next = is_numeric($min) ? ((int) $min - 1) : 0;

        $series->is_featured = true;
        $series->sort_order = $next;
        $series->save();

        $this->forgetHomeCaches();

        return redirect()
            ->route('admin.series')
            ->with('success', 'Série placée en 1ʳᵉ position du carrousel (vedette).');
    }

    public function seriesNotifications(Request $request, $id)
    {
        $series = Series::findOrFail($id);

        $q = trim((string) $request->query('q', ''));
        $onlyPending = $request->query('pending', '1') !== '0'; // par défaut: pending only

        $items = SeriesReleaseNotification::query()
            ->where('series_id', $series->id)
            ->when($onlyPending, fn ($query) => $query->whereNull('notified_at'))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('email', 'like', "%{$q}%")
                        ->orWhere('locale', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($uq) use ($q) {
                            $uq->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        });
                });
            })
            ->with(['user' => function ($uq) {
                $uq->select(['id', 'name', 'email']);
            }])
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $total = SeriesReleaseNotification::query()->where('series_id', $series->id)->count();
        $pendingCount = SeriesReleaseNotification::query()
            ->where('series_id', $series->id)
            ->whereNull('notified_at')
            ->count();

        return view('admin.series.notifications', compact('series', 'items', 'q', 'onlyPending', 'total', 'pendingCount'));
    }

    public function createSeries()
    {
        $genres = Genre::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $videoLanguages = VideoLanguage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.series.create', compact('genres', 'videoLanguages'));
    }

    public function checkSeriesSlug(Request $request): JsonResponse
    {
        $slug = trim((string) $request->query('slug', ''));
        $ignoreId = (int) $request->query('ignore_id', 0);

        if ($slug === '') {
            return response()->json(['success' => true, 'exists' => false]);
        }

        $q = Series::withTrashed()->where('slug', $slug);
        if ($ignoreId > 0) {
            $q->where('id', '!=', $ignoreId);
        }

        return response()->json([
            'success' => true,
            'exists' => $q->exists(),
        ]);
    }

    public function storeSeries(Request $request)
    {
        $validated = $request->validate([
            'title_fr' => [
                'required',
                'string',
                'max:255',
                Rule::unique('series', 'title')->whereNull('deleted_at'),
            ],
            'title_en' => 'required|string|max:255',
            'description_fr' => 'required|string|min:10',
            'description_en' => 'required|string|min:10',
            // Bunny Storage : limite raisonnable côté backoffice.
            'poster' => 'nullable|image|max:10240',
            'cover_image' => 'nullable|image|max:10240',
            'poster_url' => 'nullable|url',
            'cover_image_url' => 'nullable|url',
            'trailer_url' => 'nullable|url',
            'release_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'video_languages' => 'nullable|array',
            'video_languages.*' => 'string|max:12',
            'genres' => 'nullable|array',
            'tags' => 'nullable|array',
            'is_exclusive' => 'boolean',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:-9999|max:99999',
            'published_mode' => 'nullable|in:immediate,scheduled',
            // datetime-local => "YYYY-MM-DDTHH:MM"
            'published_at' => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        // Les checkboxes non cochées ne sont pas envoyées: forcer false.
        $validated['is_exclusive'] = $request->boolean('is_exclusive');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_trending'] = $request->boolean('is_trending');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        // Contrôle de doublon (SELECT) avant enregistrement: message clair côté admin.
        // IMPORTANT: ne pas dépendre de mbstring (mb_strtolower) => évite des 500 sur certains serveurs.
        // On s'appuie sur la collation MySQL (souvent case-insensitive) + unicité de validation Laravel.
        $titleNormalized = trim((string) $validated['title_fr']);
        $duplicate = Series::query()
            ->whereNull('deleted_at')
            ->where('title', $titleNormalized)
            ->first();
        if ($duplicate) {
            return back()->withErrors([
                'title' => "Doublon détecté : une série avec ce titre existe déjà.",
            ])->withInput();
        }

        // Normaliser les champs qui peuvent être absents
        $validated['genres'] = $validated['genres'] ?? [];
        $validated['tags'] = $validated['tags'] ?? [];

        // Remplir les champs canoniques (compat) avec la version FR
        $validated['title'] = trim((string) $validated['title_fr']);
        $validated['description'] = trim((string) $validated['description_fr']);

        $list = $validated['video_languages'] ?? [];
        $list = is_array($list) ? $list : [];
        $list = array_values(array_filter(array_map(function ($v) {
            $v = strtolower(trim((string) $v));
            return $v !== '' ? $v : null;
        }, $list)));
        if (empty($list)) {
            return back()->withErrors([
                'video_languages' => "Sélectionne au moins une langue de lecture vidéo.",
            ])->withInput();
        }
        $list = array_values(array_unique($list));

        // Garde-fou: si la table des langues vidéo existe, on refuse les codes inconnus (message clair).
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('video_languages')) {
                $known = VideoLanguage::query()->whereIn('code', $list)->pluck('code')->all();
                $known = array_map(fn($v) => strtolower((string) $v), $known);
                $unknown = array_values(array_diff($list, $known));
                if (!empty($unknown)) {
                    return back()->withErrors([
                        'language' => "Langue(s) vidéo inconnue(s): " . implode(', ', $unknown) . ". Ajoute-les d'abord dans Admin → Langues vidéo.",
                    ])->withInput();
                }
            }
        } catch (\Throwable) {
            // no-op
        }

        $validated['video_languages'] = $list;

        // Contrôle anti-doublon SLUG avant insert (évite l'erreur 1062 côté UI).
        // Règle: si le slug généré à partir du titre existe déjà, on refuse l'insert et on demande un titre différent.
        $baseSlug = Str::slug($validated['title_fr']);
        $baseSlug = $baseSlug !== '' ? $baseSlug : ('serie-' . Str::lower(Str::random(6)));
        if (Series::withTrashed()->where('slug', $baseSlug)->exists()) {
            return back()->withErrors([
                'title_fr' => "Ce titre génère un identifiant (slug) déjà utilisé (“{$baseSlug}”). Modifiez légèrement le titre puis réessayez.",
            ])->withInput();
        }

        // Publication programmée (on prépare AVANT l'insert)
        $pubMode = (string) ($validated['published_mode'] ?? 'immediate');
        unset($validated['published_mode']);
        if ($pubMode === 'scheduled') {
            $when = $request->input('published_at');
            if (!$when) {
                return back()->withErrors(['published_at' => 'Veuillez choisir une date/heure de publication.'])->withInput();
            }
            try {
                $validated['published_at'] = \Illuminate\Support\Carbon::createFromFormat('Y-m-d\TH:i', (string) $when, config('app.timezone'));
            } catch (\Throwable) {
                return back()->withErrors(['published_at' => 'Format invalide. Utilisez une date + heure.'])->withInput();
            }
        } else {
            $validated['published_at'] = null; // immédiat
        }

        // Images: IMPORTANT: ne jamais laisser un UploadedFile dans $validated
        $posterFile = $request->file('poster');
        $posterUrl = $validated['poster_url'] ?? null;
        unset($validated['poster'], $validated['poster_url']);
        if ($posterUrl) {
            $validated['poster'] = $posterUrl;
        } elseif ($posterFile) {
            $url = $this->uploadMediaFile($posterFile, 'posters', ['type' => 'poster']);
            if (! $url) {
                return back()->withErrors([
                    'poster' => "Impossible d'uploader le poster. Vérifiez Bunny Storage (paramètres ou .env) puis réessayez.",
                ])->withInput();
            }
            $validated['poster'] = $url;
        }
        $validated['poster'] = $validated['poster'] ?? '/images/placeholders/placeholder.svg';

        $coverFile = $request->file('cover_image');
        $coverUrl = $validated['cover_image_url'] ?? null;
        unset($validated['cover_image'], $validated['cover_image_url']);
        if ($coverUrl) {
            $validated['cover_image'] = $coverUrl;
        } elseif ($coverFile) {
            $url = $this->uploadMediaFile($coverFile, 'covers', ['type' => 'cover']);
            if (! $url) {
                return back()->withErrors([
                    'cover_image' => "Impossible d'uploader le cover. Vérifiez Bunny Storage puis réessayez.",
                ])->withInput();
            }
            $validated['cover_image'] = $url;
        }
        $validated['cover_image'] = $validated['cover_image'] ?? '/images/placeholders/placeholder.svg';

        // Slug unique (évite 500 sur contrainte unique + gère aussi la concurrence / soft-deletes)
        $base = $baseSlug;

        $makeCandidate = function (int $attempt) use ($base): string {
            if ($attempt === 1) return $base;
            if ($attempt === 2) return $base . '-2';
            return $base . '-' . Str::lower(Str::random(6));
        };

        $created = null;
        $lastDup = false;
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $candidate = $makeCandidate($attempt);

            // best-effort: évite les collisions connues (inclut les soft-deleted)
            if (Series::withTrashed()->where('slug', $candidate)->exists()) {
                $lastDup = true;
                continue;
            }

            $validated['slug'] = $candidate;
            $validated['slug_fr'] = $candidate;
            // slug EN (best-effort): basé sur title_en, avec fallback sur slug FR si vide ou collision
            $slugEn = Str::slug((string) ($validated['title_en'] ?? ''));
            $slugEn = $slugEn !== '' ? $slugEn : $candidate;
            if (Series::withTrashed()->where('slug_en', $slugEn)->exists()) {
                $slugEn .= '-' . Str::lower(Str::random(4));
            }
            $validated['slug_en'] = $slugEn;

            try {
                $created = Series::create($validated);
                $lastDup = false;
                break;
            } catch (QueryException $e) {
                // MySQL duplicate key (1062) : retry avec un slug différent
                $msg = (string) ($e->getMessage() ?? '');
                $code = (int) ($e->errorInfo[1] ?? 0);
                if ($code === 1062 || str_contains($msg, 'Duplicate entry')) {
                    $lastDup = true;
                    continue;
                }
                throw $e;
            }
        }

        if (!$created) {
            if ($lastDup) {
                return back()->withErrors([
                    'title' => "Ce titre génère un identifiant (slug) déjà utilisé. Modifiez légèrement le titre puis réessayez.",
                ])->withInput();
            }
            return back()->withErrors([
                'title' => "Impossible d'enregistrer la série. Vérifiez les champs puis réessayez.",
            ])->withInput();
        }

        $this->forgetHomeCaches();

        return redirect()->route('admin.series')->with('success', 'Série créée avec succès!');
    }

    public function editSeries($id)
    {
        $series = Series::findOrFail($id);
        $genres = Genre::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $videoLanguages = VideoLanguage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.series.edit', compact('series', 'genres', 'videoLanguages'));
    }

    public function updateSeries(Request $request, $id)
    {
        $series = Series::findOrFail($id);

        $validated = $request->validate([
            'title_fr' => [
                'required',
                'string',
                'max:255',
                Rule::unique('series', 'title')->ignore($series->id)->whereNull('deleted_at'),
            ],
            'title_en' => 'required|string|max:255',
            'description_fr' => 'required|string|min:10',
            'description_en' => 'required|string|min:10',
            'poster' => 'nullable|image|max:10240',
            'cover_image' => 'nullable|image|max:10240',
            'poster_url' => 'nullable|url',
            'cover_image_url' => 'nullable|url',
            'trailer_url' => 'nullable|url',
            'release_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'video_languages' => 'nullable|array',
            'video_languages.*' => 'string|max:12',
            'genres' => 'nullable|array',
            'tags' => 'nullable|array',
            'is_exclusive' => 'boolean',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:-9999|max:99999',
            'published_mode' => 'nullable|in:immediate,scheduled',
            // datetime-local => "YYYY-MM-DDTHH:MM"
            'published_at' => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        // Les checkboxes non cochées ne sont pas envoyées: forcer false.
        $validated['is_exclusive'] = $request->boolean('is_exclusive');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_trending'] = $request->boolean('is_trending');
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? $series->sort_order ?? 0);

        // Contrôle de doublon (SELECT) AVANT update (message clair, zéro 500).
        // On exclut la série courante.
        $titleNormalized = trim((string) $validated['title_fr']);
        $duplicate = Series::query()
            ->whereNull('deleted_at')
            ->where('title', $titleNormalized)
            ->where('id', '!=', $series->id)
            ->first();
        if ($duplicate) {
            return back()->withErrors([
                'title_fr' => "Doublon détecté : une série avec ce titre existe déjà.",
            ])->withInput();
        }

        // Champs canoniques (compat) = FR
        $validated['title'] = trim((string) $validated['title_fr']);
        $validated['description'] = trim((string) $validated['description_fr']);

        $list = $validated['video_languages'] ?? ($series->video_languages ?? []);
        $list = is_array($list) ? $list : [];
        $list = array_values(array_filter(array_map(function ($v) {
            $v = strtolower(trim((string) $v));
            return $v !== '' ? $v : null;
        }, $list)));
        if (empty($list)) {
            return back()->withErrors([
                'video_languages' => "Sélectionne au moins une langue de lecture vidéo.",
            ])->withInput();
        }
        $list = array_values(array_unique($list));

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('video_languages')) {
                $known = VideoLanguage::query()->whereIn('code', $list)->pluck('code')->all();
                $known = array_map(fn($v) => strtolower((string) $v), $known);
                $unknown = array_values(array_diff($list, $known));
                if (!empty($unknown)) {
                    return back()->withErrors([
                        'language' => "Langue(s) vidéo inconnue(s): " . implode(', ', $unknown) . ". Ajoute-les d'abord dans Admin → Langues vidéo.",
                    ])->withInput();
                }
            }
        } catch (\Throwable) {
            // no-op
        }

        $validated['video_languages'] = $list;

        // Slug auto (non éditable): basé sur le titre.
        // Contrôle AVANT update pour éviter la contrainte unique DB (1062).
        $baseSlug = Str::slug($validated['title_fr']);
        $baseSlug = $baseSlug !== '' ? $baseSlug : ('serie-' . Str::lower(Str::random(6)));
        if (Series::withTrashed()->where('slug', $baseSlug)->where('id', '!=', $series->id)->exists()) {
            return back()->withErrors([
                'title_fr' => "Ce titre génère un identifiant (slug) déjà utilisé (“{$baseSlug}”). Modifiez légèrement le titre puis réessayez.",
            ])->withInput();
        }
        $validated['slug'] = $baseSlug;
        $validated['slug_fr'] = $baseSlug;
        // slug EN best-effort
        $slugEn = Str::slug((string) ($validated['title_en'] ?? ''));
        $slugEn = $slugEn !== '' ? $slugEn : $baseSlug;
        if (Series::withTrashed()->where('slug_en', $slugEn)->where('id', '!=', $series->id)->exists()) {
            $slugEn .= '-' . Str::lower(Str::random(4));
        }
        $validated['slug_en'] = $slugEn;

        // Publication programmée
        $pubMode = (string) ($validated['published_mode'] ?? 'immediate');
        unset($validated['published_mode']);
        if ($pubMode === 'scheduled') {
            $when = $request->input('published_at');
            if (!$when) {
                return back()->withErrors(['published_at' => 'Veuillez choisir une date/heure de publication.'])->withInput();
            }
            try {
                $validated['published_at'] = \Illuminate\Support\Carbon::createFromFormat('Y-m-d\TH:i', (string) $when, config('app.timezone'));
            } catch (\Throwable) {
                return back()->withErrors(['published_at' => 'Format invalide. Utilisez une date + heure.'])->withInput();
            }
        } else {
            $validated['published_at'] = null; // immédiat
        }

        $posterFile = $request->file('poster');
        $posterUrl = $validated['poster_url'] ?? null;
        unset($validated['poster'], $validated['poster_url']);
        if ($posterUrl) {
            $validated['poster'] = $posterUrl;
        } elseif ($posterFile) {
            $url = $this->uploadMediaFile($posterFile, 'posters', ['type' => 'poster', 'series_id' => $series->id]);
            if (! $url) {
                return back()->withErrors([
                    'poster' => "Impossible d'uploader le poster. Vérifiez Bunny Storage puis réessayez.",
                ])->withInput();
            }
            $validated['poster'] = $url;
        }

        $coverFile = $request->file('cover_image');
        $coverUrl = $validated['cover_image_url'] ?? null;
        unset($validated['cover_image'], $validated['cover_image_url']);
        if ($coverUrl) {
            $validated['cover_image'] = $coverUrl;
        } elseif ($coverFile) {
            $url = $this->uploadMediaFile($coverFile, 'covers', ['type' => 'cover', 'series_id' => $series->id]);
            if (! $url) {
                return back()->withErrors([
                    'cover_image' => "Impossible d'uploader le cover. Vérifiez Bunny Storage puis réessayez.",
                ])->withInput();
            }
            $validated['cover_image'] = $url;
        }

        try {
            $series->update($validated);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors([
                'title' => "Impossible d'enregistrer la série. Vérifiez les champs puis réessayez.",
            ])->withInput();
        }

        $this->forgetHomeCaches();

        return redirect()->route('admin.series')->with('success', 'Série mise à jour avec succès!');
    }

    public function deleteSeries($id)
    {
        $series = Series::findOrFail($id);
        $series->delete();
        $this->forgetHomeCaches();

        return redirect()->route('admin.series')->with('success', 'Série supprimée avec succès!');
    }

    public function episodes($seriesId)
    {
        $series = Series::findOrFail($seriesId);
        $episodes = Episode::where('series_id', $seriesId)
            ->orderBy('sort_order')
            ->orderBy('episode_number')
            ->get();

        return view('admin.episodes.index', compact('series', 'episodes'));
    }

    /**
     * Place l’épisode en tête de liste d’affichage (sans changer le n° d’épisode).
     */
    public function promoteEpisode(int $seriesId, int $episodeId)
    {
        $series = Series::findOrFail($seriesId);
        $episode = Episode::where('series_id', $series->id)->findOrFail($episodeId);

        $min = Episode::where('series_id', $series->id)->min('sort_order');
        $next = is_numeric($min) ? ((int) $min - 1) : 0;

        $episode->sort_order = $next;
        $episode->save();

        return redirect()
            ->route('admin.episodes', $series->id)
            ->with('success', 'Épisode placé en 1ʳᵉ position d’affichage.');
    }

    private function forgetHomeCaches(): void
    {
        foreach ([
            'talashow.home.featured',
            'talashow.home.new_releases.v2',
            'talashow.home.trending.v3',
            'talashow.home.mustwatch.v2',
            'talashow.home.genre_rows.v3',
        ] as $key) {
            Cache::forget($key);
        }
    }

    public function createEpisode($seriesId)
    {
        $series = Series::findOrFail($seriesId);
        $max = Episode::withTrashed()
            ->where('series_id', $seriesId)
            ->where('episode_number', '>', 0)
            ->max('episode_number');
        $nextEpisodeNumber = ($max ? ((int) $max + 1) : 1);

        $videoLanguages = VideoLanguage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->keyBy('code');

        return view('admin.episodes.create', compact('series', 'nextEpisodeNumber', 'videoLanguages'));
    }

    public function storeEpisode(Request $request, $seriesId)
    {
        $validated = $request->validate([
            'is_trailer' => 'boolean',
            'display_label' => 'nullable|string|max:60',
            'title_fr' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description_fr' => 'required|string|min:10',
            'description_en' => 'required|string|min:10',
            // Compat legacy
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:10240',
            'thumbnail_url' => 'nullable|url',
            // URLs par langue (HLS / direct)
            'video_urls' => 'nullable|array',
            'video_urls.*' => 'nullable|string|max:2048',
            // Compat legacy: on peut encore poster un seul champ
            'video_url' => 'nullable|string|max:2048',
            // Talashow: lecture via Bunny Stream (URLs stockées ici)
            'video_type' => 'nullable|string',
            'duration' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
            'is_free' => 'boolean',
            'is_premium_only' => 'boolean',
            'unlock_coins' => 'nullable|integer|min:0',
            'published_mode' => 'nullable|in:immediate,scheduled',
            // datetime-local => "YYYY-MM-DDTHH:MM"
            'published_at' => 'nullable|date_format:Y-m-d\TH:i',
        ]);

        // Numéro auto (serveur) — empêche les erreurs de saisie et garantit la cohérence.
        // Option: "Bande annonce" => épisode 0
        if ($request->boolean('is_trailer')) {
            $validated['episode_number'] = 0;
        } else {
            $max = Episode::withTrashed()
                ->where('series_id', $seriesId)
                ->where('episode_number', '>', 0)
                ->max('episode_number');
            $validated['episode_number'] = ($max ? ((int) $max + 1) : 1);
        }

        // Checkboxes: forcer true/false même quand décoché
        $validated['is_free'] = $request->boolean('is_free');
        $validated['is_premium_only'] = $request->boolean('is_premium_only');

        $validated['series_id'] = $seriesId;
        $validated['video_type'] = 'bunny';
        // Champs canoniques (compat) = FR
        $validated['title'] = trim((string) $validated['title_fr']);
        $validated['description'] = trim((string) $validated['description_fr']);
        $validated['video_url'] = trim((string) ($validated['video_url'] ?? ''));

        // Langues de la série
        $series = Series::findOrFail($seriesId);
        $seriesLangs = $series->video_languages;
        $seriesLangs = is_array($seriesLangs) ? $seriesLangs : [];
        $seriesLangs = array_values(array_unique(array_filter(array_map(fn($v) => strtolower(trim((string) $v)), $seriesLangs))));
        $seriesDefaultLang = $seriesLangs[0] ?? 'fr';

        // Construire la map des URLs
        $incoming = $validated['video_urls'] ?? [];
        $incoming = is_array($incoming) ? $incoming : [];
        $map = [];
        foreach ($incoming as $k => $v) {
            $key = strtolower(trim((string) $k));
            if ($key === '') continue;
            $map[$key] = trim((string) $v);
        }

        // Si legacy video_url est fourni, on l'applique sur la langue par défaut
        if ($validated['video_url'] !== '') {
            $map[$seriesDefaultLang] = $validated['video_url'];
        }

        // Nettoyage: ne garder que les langues de la série, valeurs non vides
        $clean = [];
        foreach ($seriesLangs as $code) {
            $u = trim((string) ($map[$code] ?? ''));
            if ($u !== '') $clean[$code] = $u;
        }
        $validated['video_urls'] = $clean;

        // Compat: conserver video_url = url de la langue par défaut (ou premier dispo)
        $validated['video_url'] = $clean[$seriesDefaultLang] ?? (count($clean) ? (string) array_values($clean)[0] : '');

        // Publication programmée de la vidéo
        $pubMode = (string) ($validated['published_mode'] ?? 'immediate');
        unset($validated['published_mode']);
        if ($pubMode === 'scheduled') {
            $when = $request->input('published_at');
            if (!$when) {
                return back()->withErrors(['published_at' => 'Veuillez choisir une date/heure de disponibilité.'])->withInput();
            }
            try {
                $validated['published_at'] = \Illuminate\Support\Carbon::createFromFormat('Y-m-d\TH:i', (string) $when, config('app.timezone'));
            } catch (\Throwable) {
                return back()->withErrors(['published_at' => 'Format invalide. Utilisez une date + heure.'])->withInput();
            }
        } else {
            $validated['published_at'] = null; // immédiat
        }

        $thumbFile = $request->file('thumbnail');
        $thumbUrl = $validated['thumbnail_url'] ?? null;
        unset($validated['thumbnail'], $validated['thumbnail_url']);
        if ($thumbUrl) {
            $validated['thumbnail'] = $thumbUrl;
        } elseif ($thumbFile) {
            $url = $this->uploadMediaFile($thumbFile, 'thumbnails', ['type' => 'thumb', 'series_id' => $seriesId]);
            if ($url) {
                $validated['thumbnail'] = $url;
            }
        }

        Episode::create($validated);

        // Mettre à jour le nombre total d'épisodes de la série
        $series->update(['total_episodes' => Episode::where('series_id', $seriesId)->count()]);

        return redirect()->route('admin.episodes', $seriesId)->with('success', 'Épisode créé avec succès!');
    }

    public function editEpisode($seriesId, $episodeId)
    {
        $series = Series::findOrFail($seriesId);
        $episode = Episode::where('series_id', $seriesId)->findOrFail($episodeId);
        $videoLanguages = VideoLanguage::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->keyBy('code');

        return view('admin.episodes.edit', compact('series', 'episode', 'videoLanguages'));
    }

    public function updateEpisode(Request $request, $seriesId, $episodeId)
    {
        $episode = Episode::where('series_id', $seriesId)->findOrFail($episodeId);

        $validated = $request->validate([
            'episode_number' => 'required|integer|min:0',
            'display_label' => 'nullable|string|max:60',
            'title_fr' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'description_fr' => 'required|string|min:10',
            'description_en' => 'required|string|min:10',
            // Compat legacy
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|image|max:10240',
            'thumbnail_url' => 'nullable|url',
            'video_urls' => 'nullable|array',
            'video_urls.*' => 'nullable|string|max:2048',
            'video_url' => 'nullable|string|max:2048',
            'video_type' => 'nullable|string',
            'duration' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
            'is_free' => 'boolean',
            'is_premium_only' => 'boolean',
            'unlock_coins' => 'nullable|integer|min:0',
            'published_mode' => 'nullable|in:immediate,scheduled',
            // datetime-local => "YYYY-MM-DDTHH:MM"
            'published_at' => 'nullable|date_format:Y-m-d\TH:i',
        ]);
        // Checkboxes: forcer true/false même quand décoché
        $validated['is_free'] = $request->boolean('is_free');
        $validated['is_premium_only'] = $request->boolean('is_premium_only');

        $validated['video_type'] = 'bunny';
        // Champs canoniques (compat) = FR
        $validated['title'] = trim((string) $validated['title_fr']);
        $validated['description'] = trim((string) $validated['description_fr']);
        $validated['video_url'] = trim((string) ($validated['video_url'] ?? $episode->video_url ?? ''));

        // Langues de la série
        $series = Series::findOrFail($seriesId);
        $seriesLangs = $series->video_languages;
        $seriesLangs = is_array($seriesLangs) ? $seriesLangs : [];
        $seriesLangs = array_values(array_unique(array_filter(array_map(fn($v) => strtolower(trim((string) $v)), $seriesLangs))));
        $seriesDefaultLang = $seriesLangs[0] ?? 'fr';

        // Merge incoming urls with existing
        $existing = $episode->video_urls;
        $existing = is_array($existing) ? $existing : [];
        $incoming = $validated['video_urls'] ?? [];
        $incoming = is_array($incoming) ? $incoming : [];

        $map = $existing;
        foreach ($incoming as $k => $v) {
            $key = strtolower(trim((string) $k));
            if ($key === '') continue;
            $map[$key] = trim((string) $v);
        }

        // Legacy single field: apply to default lang if filled
        if ($validated['video_url'] !== '') {
            $map[$seriesDefaultLang] = $validated['video_url'];
        }

        // Nettoyage: garder seulement langues de la série, valeurs non vides
        $clean = [];
        foreach ($seriesLangs as $code) {
            $u = trim((string) ($map[$code] ?? ''));
            if ($u !== '') $clean[$code] = $u;
        }
        $validated['video_urls'] = $clean;
        $validated['video_url'] = $clean[$seriesDefaultLang] ?? (count($clean) ? (string) array_values($clean)[0] : '');

        // Publication programmée de la vidéo
        $pubMode = (string) ($validated['published_mode'] ?? 'immediate');
        unset($validated['published_mode']);
        if ($pubMode === 'scheduled') {
            $when = $request->input('published_at');
            if (!$when) {
                return back()->withErrors(['published_at' => 'Veuillez choisir une date/heure de disponibilité.'])->withInput();
            }
            try {
                $validated['published_at'] = \Illuminate\Support\Carbon::createFromFormat('Y-m-d\TH:i', (string) $when, config('app.timezone'));
            } catch (\Throwable) {
                return back()->withErrors(['published_at' => 'Format invalide. Utilisez une date + heure.'])->withInput();
            }
        } else {
            $validated['published_at'] = null; // immédiat
        }

        $thumbFile = $request->file('thumbnail');
        $thumbUrl = $validated['thumbnail_url'] ?? null;
        unset($validated['thumbnail'], $validated['thumbnail_url']);
        if ($thumbUrl) {
            $validated['thumbnail'] = $thumbUrl;
        } elseif ($thumbFile) {
            $url = $this->uploadMediaFile($thumbFile, 'thumbnails', ['type' => 'thumb', 'series_id' => $seriesId, 'episode_id' => $episode->id]);
            if ($url) {
                $validated['thumbnail'] = $url;
            }
        }

        $episode->update($validated);

        return redirect()->route('admin.episodes', $seriesId)->with('success', 'Épisode mis à jour avec succès!');
    }

    public function deleteEpisode($seriesId, $episodeId)
    {
        $episode = Episode::where('series_id', $seriesId)->findOrFail($episodeId);
        $episode->delete();

        $series = Series::findOrFail($seriesId);
        $series->update(['total_episodes' => Episode::where('series_id', $seriesId)->count()]);

        return redirect()->route('admin.episodes', $seriesId)->with('success', 'Épisode supprimé avec succès!');
    }
}
