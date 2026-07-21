<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Frontend\EpisodeController;
use App\Http\Controllers\Frontend\PlaybackGateController;
use App\Http\Controllers\Webhooks\BunnyStreamWebhookController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\SeriesEngagementController;
use App\Http\Controllers\Frontend\ViewTrackingController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Payment\InvoiceController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\GenresController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\PaymentsController;
use App\Http\Controllers\Admin\PagesController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\VideoUploadController;
use App\Http\Controllers\Admin\MailTemplatesController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\VideoLanguagesController;
use App\Http\Controllers\Marketing\NewsletterController;
use App\Http\Controllers\Marketing\SeriesReleaseNotificationController;
use App\Http\Controllers\Marketing\EpisodeReleaseNotificationController;
use App\Models\Favorite;
use App\Models\SeriesLike;
use App\Models\WatchHistory;
use Illuminate\Support\Facades\Route;

// Favicon : redirection vers le logo pour éviter 404
Route::get('/favicon.ico', fn () => redirect(asset('logo.svg'), 302))->name('favicon');

// Routes publiques (cache navigateur court + anti-scraping léger)
Route::middleware(['throttle:catalog'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home')->middleware('cache.headers:public;max_age=60');
    Route::get('/browse', [HomeController::class, 'browse'])->name('browse')->middleware('cache.headers:public;max_age=60');
    Route::view('/application', 'frontend.application')->name('application');
    Route::get('/series/{slug}', [HomeController::class, 'showSeries'])->name('series.show');
    Route::post('/series/{slug}/notify', [SeriesReleaseNotificationController::class, 'store'])->name('series.notify');
    Route::post('/series/{series:slug}/favorite', [SeriesEngagementController::class, 'toggleFavorite'])->middleware('auth')->name('series.favorite');
    Route::post('/series/{series:slug}/like', [SeriesEngagementController::class, 'toggleLike'])->middleware('auth')->name('series.like');
    Route::get('/series/{seriesSlug}/episode/{episodeId}', [EpisodeController::class, 'show'])->name('episode.show');
    Route::get('/series/{seriesSlug}/episode/{episodeId}/playback', [EpisodeController::class, 'playback'])->name('episode.playback');
    Route::post('/series/{seriesSlug}/episode/{episodeId}/notify', [EpisodeReleaseNotificationController::class, 'store'])->name('episode.notify');
    Route::get('/page/{slug}', [PageController::class, 'show'])->name('page.show');
});

// Gate lecture (session + redirection Bunny TTL court) — hors groupe catalogue pour throttle dédié
Route::get('/playback/gate/{token}', [PlaybackGateController::class, '__invoke'])
    ->middleware(['throttle:playback-gate'])
    ->name('playback.gate');

// Abonnement / Recharge : redirection vers login si non connecté (évite 500)
Route::get('/payment/recharge', function () {
    if (!auth()->check()) {
        return redirect()->guest(route('login'));
    }
    return app(PaymentController::class)->showRecharge();
})->name('payment.recharge');

// Donation : redirection vers login si non connecté (évite 500)
Route::get('/donation', function () {
    if (!auth()->check()) {
        return redirect()->guest(route('login'));
    }
    return app(PaymentController::class)->showDonation();
})->name('payment.donation');

// Newsletter (marketing)
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->middleware('throttle:newsletter-subscribe')->name('newsletter.subscribe');
Route::post('/newsletter/resend', [NewsletterController::class, 'resend'])->middleware('throttle:newsletter-resend')->name('newsletter.resend');
Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// Pages légales (liens du footer)
Route::get('/conditions-utilisation', [PageController::class, 'showLegal'])->defaults('slug', 'conditions-utilisation')->name('legal.terms');
Route::get('/politique-confidentialite', [PageController::class, 'showLegal'])->defaults('slug', 'politique-confidentialite')->name('legal.privacy');
Route::get('/cookie-policy', [PageController::class, 'showLegal'])->defaults('slug', 'cookie-policy')->name('legal.cookies');

// Authentification
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register')->middleware('guest');
Route::post('/register', [AuthController::class, 'registerStart'])->middleware(['guest', 'throttle:register']);
Route::get('/verify-otp', [AuthController::class, 'showOtp'])->name('otp.form')->middleware('guest');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('otp.verify')->middleware(['guest', 'throttle:otp-verify']);
Route::post('/verify-otp/resend', [AuthController::class, 'resendOtp'])->name('otp.resend')->middleware(['guest', 'throttle:otp-resend']);

// Mot de passe oublié
Route::get('/forgot-password', [\App\Http\Controllers\Auth\PasswordController::class, 'showForgot'])
    ->name('password.request')
    ->middleware('guest');
Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordController::class, 'sendResetLink'])
    ->name('password.email')
    ->middleware(['guest', 'throttle:forgot-password']);
Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\PasswordController::class, 'showReset'])
    ->name('password.reset')
    ->middleware('guest');
Route::post('/reset-password', [\App\Http\Controllers\Auth\PasswordController::class, 'reset'])
    ->name('password.update')
    ->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Langue (FR/EN)
Route::get('/lang/{locale}', function (\Illuminate\Http\Request $request, string $locale) {
    $locale = strtolower(trim($locale));
    if (! in_array($locale, ['fr', 'en'], true)) {
        $locale = config('app.locale', 'fr');
    }

    $request->session()->put('locale', $locale);
    $request->session()->save();

    // Cookie en clair (non chiffré via EncryptCookies::$except) — survit aux redeploys / APP_KEY
    $minutes = 60 * 24 * 365;
    $cookie = cookie(
        'locale',
        $locale,
        $minutes,
        '/',
        null,
        $request->isSecure(),
        false, // httpOnly false: debug facile ; valeur non sensible (fr|en)
        false,
        'Lax'
    );

    // Preferer le referer interne ; sinon home
    $fallback = route('home');
    $target = url()->previous() ?: $fallback;
    if ($target === $request->fullUrl() || ! str_starts_with($target, $request->root())) {
        $target = $fallback;
    }

    return redirect()
        ->to($target)
        ->withCookie($cookie)
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate')
        ->header('Pragma', 'no-cache');
})->name('lang.switch');

// OAuth Social Login
Route::get('/auth/{provider}', [AuthController::class, 'redirectToProvider'])->middleware('throttle:oauth')->name('auth.provider');
Route::get('/auth/{provider}/callback', [AuthController::class, 'handleProviderCallback'])->middleware('throttle:oauth')->name('auth.callback');

// Routes authentifiées
Route::middleware(['auth', 'active'])->group(function () {
    // Profil utilisateur
    Route::get('/profile', function () {
        $user = auth()->user();

        $favorites = Favorite::query()
            ->where('user_id', $user->id)
            ->with(['series' => function ($q) {
                $q->select(['id', 'slug', 'title', 'poster', 'cover_image', 'likes_count', 'views_count']);
            }])
            ->latest()
            ->limit(12)
            ->get();

        $likedSeries = SeriesLike::query()
            ->where('user_id', $user->id)
            ->with(['series' => function ($q) {
                $q->select(['id', 'slug', 'title', 'poster', 'cover_image', 'likes_count', 'views_count']);
            }])
            ->latest()
            ->limit(12)
            ->get();

        // On prend plus large puis on déduplique par episode_id en PHP (dernier regardé gagnant)
        $recentlyWatched = WatchHistory::query()
            ->where('user_id', $user->id)
            ->with(['episode' => function ($q) {
                $q->select(['id', 'series_id', 'title', 'thumbnail', 'duration', 'episode_number', 'display_label'])
                    ->with(['series' => function ($q2) {
                        $q2->select(['id', 'slug', 'title', 'poster']);
                    }]);
            }])
            ->orderByDesc('watched_at')
            ->limit(60)
            ->get()
            ->unique('episode_id')
            ->take(12)
            ->values();

        return view('frontend.profile', compact('favorites', 'likedSeries', 'recentlyWatched'));
    })->name('profile');

    // Déblocage d'épisode
    Route::post('/episode/{episodeId}/unlock', [EpisodeController::class, 'unlock'])->name('episode.unlock');

    // Mise à jour de la progression
    Route::post('/episode/{episodeId}/progress', [EpisodeController::class, 'updateProgress'])->name('episode.progress');

    // Vue "YouTube-like" (1 vue unique par user, déclenchée au play)
    Route::post('/episode/{episode}/view', [ViewTrackingController::class, 'episode'])->name('episode.view');

    // Paiement (recharge et donation gérées en amont pour rediriger les invités vers login)
    Route::post('/payment/subscription', [PaymentController::class, 'createSubscription'])->name('payment.subscription');
    Route::post('/payment/coins', [PaymentController::class, 'purchaseCoins'])->name('payment.coins');

    // Facture / reçu (imprimable)
    Route::get('/transaction/{transaction}/invoice', [InvoiceController::class, 'show'])->name('transaction.invoice');

    // PayPal (Orders API)
    Route::post('/payment/paypal/create-order', [PaymentController::class, 'paypalCreateOrder'])->middleware('throttle:payment-paypal')->name('payment.paypal.create-order');
    Route::post('/payment/paypal/capture-order', [PaymentController::class, 'paypalCaptureOrder'])->middleware('throttle:payment-paypal')->name('payment.paypal.capture-order');
});

// Routes de paiement (publiques pour webhooks)
Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->middleware('throttle:webhooks')->name('payment.webhook');
Route::post('/webhooks/bunny/stream', BunnyStreamWebhookController::class)->middleware('throttle:webhooks')->name('webhooks.bunny.stream');

// Backoffice (type wp-admin) - accès uniquement par URL
Route::prefix('talashow-admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    })->name('home');

    Route::get('/login', [AdminAuthController::class, 'showLogin'])->middleware('guest')->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware(['guest', 'throttle:admin-login'])->name('login.post');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->middleware('auth')->name('logout');
});

// Routes admin (protégées) - nécessite la permission adminapp.access
Route::prefix('talashow-admin')->middleware(['auth', 'active', 'adminapp', 'throttle:admin'])->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::middleware(['perm:monitoring.view'])->group(function () {
        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
        Route::post('/monitoring/action/{action}', [MonitoringController::class, 'action'])->name('monitoring.action');
        Route::post('/monitoring/connectivity', [MonitoringController::class, 'connectivity'])->name('monitoring.connectivity');
    });

    // Gestion des séries
    Route::middleware(['perm:series.manage'])->group(function () {
        Route::get('/series', [AdminController::class, 'series'])->name('series');
        Route::get('/series/create', [AdminController::class, 'createSeries'])->name('series.create');
        Route::get('/series/check-slug', [AdminController::class, 'checkSeriesSlug'])->name('series.check-slug');
        Route::post('/series', [AdminController::class, 'storeSeries'])->name('series.store');
        Route::get('/series/{id}/edit', [AdminController::class, 'editSeries'])->name('series.edit');
        Route::put('/series/{id}', [AdminController::class, 'updateSeries'])->name('series.update');
        Route::delete('/series/{id}', [AdminController::class, 'deleteSeries'])->name('series.delete');
        Route::post('/series/{id}/promote', [AdminController::class, 'promoteSeries'])->name('series.promote');
        Route::get('/series/{id}/notifications', [AdminController::class, 'seriesNotifications'])->name('series.notifications');

        // Gestion des épisodes
        Route::get('/series/{seriesId}/episodes', [AdminController::class, 'episodes'])->name('episodes');
        Route::get('/series/{seriesId}/episodes/create', [AdminController::class, 'createEpisode'])->name('episodes.create');
        Route::post('/series/{seriesId}/episodes', [AdminController::class, 'storeEpisode'])->name('episodes.store');
        Route::get('/series/{seriesId}/episodes/{episodeId}/edit', [AdminController::class, 'editEpisode'])->name('episodes.edit');
        Route::put('/series/{seriesId}/episodes/{episodeId}', [AdminController::class, 'updateEpisode'])->name('episodes.update');
        Route::delete('/series/{seriesId}/episodes/{episodeId}', [AdminController::class, 'deleteEpisode'])->name('episodes.delete');
        Route::post('/series/{seriesId}/episodes/{episodeId}/promote', [AdminController::class, 'promoteEpisode'])->name('episodes.promote');
    });

    // Genres (classements)
    Route::middleware(['perm:genres.manage'])->group(function () {
        Route::get('/genres', [GenresController::class, 'index'])->name('genres.index');
        Route::get('/genres/create', [GenresController::class, 'create'])->name('genres.create');
        Route::post('/genres', [GenresController::class, 'store'])->name('genres.store');
        Route::get('/genres/{id}/edit', [GenresController::class, 'edit'])->name('genres.edit');
        Route::put('/genres/{id}', [GenresController::class, 'update'])->name('genres.update');
        Route::delete('/genres/{id}', [GenresController::class, 'destroy'])->name('genres.destroy');
    });

    // Paramètres (Admin only)
    Route::middleware(['perm:settings.manage'])->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/test-email', [SettingsController::class, 'sendTestEmail'])->name('settings.test-email');

        // Langues vidéo (doublage/audio)
        Route::get('/video-languages', [VideoLanguagesController::class, 'index'])->name('video-languages.index');
        Route::get('/video-languages/create', [VideoLanguagesController::class, 'create'])->name('video-languages.create');
        Route::post('/video-languages', [VideoLanguagesController::class, 'store'])->name('video-languages.store');
        Route::get('/video-languages/{id}/edit', [VideoLanguagesController::class, 'edit'])->name('video-languages.edit');
        Route::put('/video-languages/{id}', [VideoLanguagesController::class, 'update'])->name('video-languages.update');
        Route::delete('/video-languages/{id}', [VideoLanguagesController::class, 'destroy'])->name('video-languages.destroy');

        // Newsletter (marketing) - gestion / export (CSV)
        Route::get('/newsletter', [AdminNewsletterController::class, 'index'])->name('newsletter.index');
        Route::get('/newsletter/compose', [AdminNewsletterController::class, 'compose'])->name('newsletter.compose');
        Route::post('/newsletter/send-test', [AdminNewsletterController::class, 'sendTest'])->name('newsletter.send-test');
        Route::post('/newsletter/send', [AdminNewsletterController::class, 'sendNow'])->name('newsletter.send');
        Route::get('/newsletter/export', [AdminNewsletterController::class, 'export'])->name('newsletter.export');

        // Pages (contenu éditable)
        Route::get('/pages', [PagesController::class, 'index'])->name('pages.index');
        Route::get('/pages/{id}/edit', [PagesController::class, 'edit'])->name('pages.edit');
        Route::put('/pages/{id}', [PagesController::class, 'update'])->name('pages.update');

        // Templates emails (éditables)
        Route::get('/mail-templates', [MailTemplatesController::class, 'index'])->name('mail-templates.index');
        Route::get('/mail-templates/create', [MailTemplatesController::class, 'create'])->name('mail-templates.create');
        Route::post('/mail-templates', [MailTemplatesController::class, 'store'])->name('mail-templates.store');
        Route::get('/mail-templates/{id}/edit', [MailTemplatesController::class, 'edit'])->name('mail-templates.edit');
        Route::put('/mail-templates/{id}', [MailTemplatesController::class, 'update'])->name('mail-templates.update');
        Route::delete('/mail-templates/{id}', [MailTemplatesController::class, 'destroy'])->name('mail-templates.destroy');
    });

    // Upload vidéo Cloudflare Stream
    Route::post('/episodes/{episodeId}/upload-video', [VideoUploadController::class, 'upload'])->name('episodes.upload');
    Route::post('/episodes/{episodeId}/upload-from-url', [VideoUploadController::class, 'uploadFromUrl'])->name('episodes.upload-url');
    Route::get('/episodes/{episodeId}/video-status', [VideoUploadController::class, 'checkStatus'])->name('episodes.video-status');

    // Upload image Cloudflare Images (poster/cover/thumbnail) - AJAX avec progression côté client
    Route::post('/media/upload-image', [MediaController::class, 'uploadImage'])->name('media.upload-image');

    // Paiements
    Route::middleware(['perm:payments.view'])->group(function () {
        Route::get('/payments/transactions', [PaymentsController::class, 'transactions'])->name('payments.transactions');
        Route::get('/payments/subscriptions', [PaymentsController::class, 'subscriptions'])->name('payments.subscriptions');
        Route::post('/payments/transactions/{transaction}/invoice/resend', [PaymentsController::class, 'resendInvoiceEmail'])->name('payments.transactions.invoice.resend');
    });

    // Utilisateurs / rôles (Admin only)
    Route::middleware(['perm:users.manage'])->group(function () {
        Route::get('/users', [UsersController::class, 'index'])->name('users.index');
        Route::put('/users/{id}/role', [UsersController::class, 'updateRole'])->name('users.update-role');
        Route::put('/users/{id}/toggle-active', [UsersController::class, 'toggleActive'])->name('users.toggle-active');
        Route::delete('/users/{id}', [UsersController::class, 'destroy'])->name('users.destroy');
    });
});

// 404 "pro" pour toute URL non matchée (web)
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
