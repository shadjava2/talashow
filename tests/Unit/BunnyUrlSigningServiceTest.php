<?php

namespace Tests\Unit;

use App\Services\Video\BunnyUrlSigningService;
use Tests\TestCase;

class BunnyUrlSigningServiceTest extends TestCase
{
    public function test_maybe_sign_hls_respects_expires_override(): void
    {
        config([
            'services.bunny_stream.signed_urls' => true,
            'services.bunny_stream.token_security_key' => 'test-secret-key',
            'services.bunny_stream.signed_url_ttl_seconds' => 7200,
        ]);

        $svc = new BunnyUrlSigningService;
        $url = 'https://vz-test.b-cdn.net/guid/playlist.m3u8';
        $fixed = time() + 300;

        $signed = $svc->maybeSignHlsUrl($url, $fixed);

        $this->assertStringContainsString('expires='.$fixed, $signed);
        $this->assertStringContainsString('token=', $signed);
    }
}
