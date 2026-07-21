<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PayPalService
{
    public function __construct(private SettingsService $settings)
    {
    }

    /**
     * PayPal TLS issues (cURL 35 / ssl3_get_record) arrivent souvent sur Windows
     * avec proxy/antivirus qui inspecte SSL. Forcer TLS1.2 + HTTP/1.1 est un workaround
     * fréquent et sans impact sur PayPal.
     */
    private function paypalHttp()
    {
        // Windows + OpenSSL: si curl.cainfo/openssl.cafile n'est pas configuré, cURL peut échouer (cURL 60).
        // On permet de pointer un CA bundle via env sans toucher php.ini.
        $caBundle =
            (string) (env('TALASHOW_CURL_CAINFO', '') ?: env('CURL_CA_BUNDLE', '') ?: env('SSL_CERT_FILE', ''));

        $options = [
            'curl' => [
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                // Certains réseaux/FAI ont des soucis IPv6 -> erreurs TLS aléatoires (cURL 35).
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ],
        ];

        // En dev uniquement, on autorise PAYPAL_VERIFY_SSL=false pour débloquer,
        // mais gardez TOUJOURS la vérification en production.
        if (app()->environment('local') && env('PAYPAL_VERIFY_SSL') === false) {
            $options['verify'] = false;
        } elseif ($caBundle !== '') {
            $options['verify'] = $caBundle;
        }

        return Http::withOptions($options)->timeout(20);
    }

    public function isConfigured(): bool
    {
        return (bool) $this->getClientId() && (bool) $this->getSecret();
    }

    public function getClientId(): ?string
    {
        return $this->settings->get('paypal_client_id');
    }

    public function getSecret(): ?string
    {
        return $this->settings->getSecret('paypal_client_secret');
    }

    public function getMode(): string
    {
        $m = strtolower((string) $this->settings->get('paypal_mode', 'sandbox'));
        return in_array($m, ['sandbox', 'live'], true) ? $m : 'sandbox';
    }

    public function getApiBase(): string
    {
        return $this->getMode() === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function getAccessToken(): string
    {
        $clientId = $this->getClientId();
        $secret = $this->getSecret();
        if (!$clientId || !$secret) {
            throw new \RuntimeException('PayPal n’est pas configuré.');
        }

        try {
            $resp = $this->paypalHttp()
                ->asForm()
                ->withBasicAuth($clientId, $secret)
                ->post($this->getApiBase() . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials',
                ]);
        } catch (ConnectionException $e) {
            // Ex: cURL error 35 (SSL routines: ssl3_get_record ...)
            throw new \RuntimeException('PayPal cURL connection error: ' . $e->getMessage(), 0, $e);
        }

        if (!$resp->successful()) {
            throw new \RuntimeException('PayPal OAuth error: ' . $resp->status() . ' ' . substr($resp->body(), 0, 200));
        }

        $token = $resp->json('access_token');
        if (!$token) {
            throw new \RuntimeException('PayPal OAuth: access_token manquant.');
        }
        return (string) $token;
    }

    public function createOrder(array $payload): array
    {
        $token = $this->getAccessToken();
        try {
            $resp = $this->paypalHttp()
                ->withToken($token)
                ->acceptJson()
                ->post($this->getApiBase() . '/v2/checkout/orders', $payload);
        } catch (ConnectionException $e) {
            throw new \RuntimeException('PayPal cURL connection error: ' . $e->getMessage(), 0, $e);
        }

        if (!$resp->successful()) {
            throw new \RuntimeException(
                'PayPal create order error: ' . $resp->status() . ' ' . substr($resp->body(), 0, 600),
                (int) $resp->status()
            );
        }
        return (array) $resp->json();
    }

    public function captureOrder(string $orderId): array
    {
        $token = $this->getAccessToken();
        try {
            $resp = $this->paypalHttp()
                ->withToken($token)
                ->acceptJson()
                // PayPal attend un body vide; en pratique, envoyer un JSON vide "{}" évite les erreurs
                // "MALFORMED_REQUEST_JSON" que certains environnements renvoient avec un body implicite (null/[]).
                ->withBody("{}", 'application/json')
                ->send('POST', $this->getApiBase() . '/v2/checkout/orders/' . $orderId . '/capture');
        } catch (ConnectionException $e) {
            throw new \RuntimeException('PayPal cURL connection error: ' . $e->getMessage(), 0, $e);
        }

        if (!$resp->successful()) {
            throw new \RuntimeException(
                'PayPal capture error: ' . $resp->status() . ' ' . substr($resp->body(), 0, 600),
                (int) $resp->status()
            );
        }
        return (array) $resp->json();
    }
}

