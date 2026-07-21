<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Series;
use App\Models\Genre;
use App\Models\Episode;
use App\Services\SettingsService;
use App\Services\MailTemplateService;
use App\Services\MailInlineAssetService;
use App\Services\PayPalService;
use App\Observers\HomeCacheObserver;
use App\Services\BunnyStorageService;
use App\Services\Video\BunnyApiClient;
use App\Services\Video\BunnyEmbedTokenService;
use App\Services\Video\BunnyStreamProvider;
use App\Services\Video\BunnyStreamService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SettingsService::class, fn () => new SettingsService());
        $this->app->singleton(PayPalService::class, fn () => new PayPalService($this->app->make(SettingsService::class)));
        $this->app->singleton(MailTemplateService::class, fn () => new MailTemplateService($this->app->make(SettingsService::class)));
        $this->app->singleton(MailInlineAssetService::class, fn () => new MailInlineAssetService($this->app->make(SettingsService::class)));
        $this->app->singleton(BunnyApiClient::class, fn () => BunnyApiClient::fromConfig());
        $this->app->singleton(BunnyEmbedTokenService::class, fn () => new BunnyEmbedTokenService());
        $this->app->singleton(BunnyStreamService::class, function ($app) {
            return new BunnyStreamService(
                $app->make(BunnyApiClient::class),
                $app->make(BunnyStreamProvider::class),
                $app->make(BunnyEmbedTokenService::class),
            );
        });
        $this->app->singleton(BunnyStorageService::class, fn () => new BunnyStorageService());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paramètres pilotables depuis la DB (backoffice) sans modifier .env.
        // Ne casse pas l'installation: si la table settings n'existe pas, on skip.
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            /** @var SettingsService $settings */
            $settings = $this->app->make(SettingsService::class);

            // Fuseau horaire (global) depuis la DB
            $tz = $settings->get('app_timezone');
            if (is_string($tz) && $tz !== '' && in_array($tz, \DateTimeZone::listIdentifiers(), true)) {
                config(['app.timezone' => $tz]);
                try {
                    date_default_timezone_set($tz);
                } catch (\Throwable) {
                    // ignore
                }
            }

            // SMTP (emails) depuis la DB
            $smtpHost = $settings->get('smtp_host');
            $smtpPort = $settings->get('smtp_port');
            $smtpEnc = $settings->get('smtp_encryption');
            $smtpUser = $settings->get('smtp_username');
            $smtpPass = $settings->getSecret('smtp_password');
            $fromAddr = $settings->get('mail_from_address');
            $fromName = $settings->get('mail_from_name');

            if ($smtpHost) config(['mail.mailers.smtp.host' => $smtpHost]);
            if ($smtpPort) config(['mail.mailers.smtp.port' => (int) $smtpPort]);
            if ($smtpEnc) {
                $enc = $smtpEnc === 'none' ? null : $smtpEnc;
                config(['mail.mailers.smtp.encryption' => $enc]);
            }
            if ($smtpUser) config(['mail.mailers.smtp.username' => $smtpUser]);
            if ($smtpPass) config(['mail.mailers.smtp.password' => $smtpPass]);
            if ($fromAddr) config(['mail.from.address' => $fromAddr]);
            if ($fromName) config(['mail.from.name' => $fromName]);

            // Vidéo : Bunny Stream + provider (forcé Bunny côté produit)
            $videoProvider = $settings->get('video_provider');
            if (is_string($videoProvider) && $videoProvider !== '') {
                config(['video.default_provider' => strtolower($videoProvider)]);
            }
            $videoFallback = $settings->get('video_fallback_provider');
            if (is_string($videoFallback) && $videoFallback !== '') {
                config(['video.fallback_provider' => strtolower($videoFallback)]);
            }

            $bsZone = $settings->get('bunny_storage_zone_name');
            $bsRegion = $settings->get('bunny_storage_region');
            $bsCdn = $settings->get('bunny_storage_cdn_url');
            $bsKey = $settings->getSecret('bunny_storage_api_key');
            $bsVerify = $settings->get('bunny_storage_verify_ssl');
            if ($bsZone) {
                config(['services.bunny_storage.zone_name' => $bsZone]);
            }
            if ($bsRegion) {
                config(['services.bunny_storage.region' => strtolower(trim((string) $bsRegion))]);
            }
            if ($bsCdn) {
                config(['services.bunny_storage.cdn_url' => rtrim((string) $bsCdn, '/')]);
            }
            if ($bsKey) {
                config(['services.bunny_storage.api_key' => $bsKey]);
            }
            if ($bsVerify !== null && trim((string) $bsVerify) !== '') {
                $parsed = filter_var($bsVerify, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($parsed !== null) {
                    config(['services.bunny_storage.verify_ssl' => $parsed]);
                }
            }

            $bunnyLib = $settings->get('bunny_stream_library_id');
            $bunnyHost = $settings->get('bunny_stream_cdn_hostname');
            $bunnyVerify = $settings->get('bunny_verify_ssl');
            $bunnySigned = $settings->get('bunny_stream_signed_urls');
            $bunnyApiKey = $settings->getSecret('bunny_stream_api_key');
            $bunnyWhSecret = $settings->getSecret('bunny_stream_webhook_secret');
            $bunnyTokenSec = $settings->getSecret('bunny_stream_token_security_key');
            $bunnyTokenKey = $settings->getSecret('bunny_stream_token_key');

            if ($bunnyLib) {
                config(['services.bunny_stream.library_id' => $bunnyLib]);
            }
            if ($bunnyHost) {
                $h = preg_replace('#^https?://#i', '', trim((string) $bunnyHost));
                $h = rtrim((string) $h, '/');
                config(['services.bunny_stream.cdn_hostname' => $h]);
            }
            if ($bunnyVerify !== null && trim((string) $bunnyVerify) !== '') {
                $bv = filter_var($bunnyVerify, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($bv !== null) {
                    config(['services.bunny_stream.verify_ssl' => $bv]);
                }
            }
            if ($bunnySigned !== null && trim((string) $bunnySigned) !== '') {
                $bs = filter_var($bunnySigned, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($bs !== null) {
                    config(['services.bunny_stream.signed_urls' => $bs]);
                }
            }
            if ($bunnyApiKey) {
                config(['services.bunny_stream.api_key' => $bunnyApiKey]);
            }
            if ($bunnyWhSecret) {
                config(['services.bunny_stream.webhook_secret' => $bunnyWhSecret]);
            }
            if ($bunnyTokenSec) {
                config(['services.bunny_stream.token_security_key' => $bunnyTokenSec]);
            }
            if ($bunnyTokenKey) {
                config(['services.bunny_stream.token_key' => $bunnyTokenKey]);
            }
        } catch (\Throwable $e) {
            // En prod: ne jamais casser le boot si la DB n'est pas prête.
            // En debug/local: on logge pour diagnostiquer facilement.
            if (config('app.debug') || app()->environment('local')) {
                report($e);
            }
        }

        // Invalidation du cache homepage / browse quand le contenu change (admin)
        Series::observe([HomeCacheObserver::class, \App\Observers\SeriesSecurityObserver::class]);
        Genre::observe(HomeCacheObserver::class);
        Episode::observe(\App\Observers\EpisodeSecurityObserver::class);
    }
}
