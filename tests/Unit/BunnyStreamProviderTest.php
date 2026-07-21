<?php

namespace Tests\Unit;

use App\Services\Video\BunnyApiClient;
use App\Services\Video\BunnyStreamProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BunnyStreamProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.bunny_stream.library_id' => '123',
            'services.bunny_stream.api_key' => 'k',
            'services.bunny_stream.cdn_hostname' => 'vz-test.b-cdn.net',
            'services.bunny_stream.verify_ssl' => true,
        ]);
    }

    public function test_is_ready_when_status_four(): void
    {
        $client = new BunnyApiClient('123', 'k', true);
        $provider = new BunnyStreamProvider($client);

        $this->assertTrue($provider->isReady(['status' => 4]));
        $this->assertFalse($provider->isReady(['status' => 2]));
    }

    public function test_get_playback_urls_from_get_video_payload(): void
    {
        Http::fake([
            'https://video.bunnycdn.com/library/123/videos/g1' => Http::response([
                'guid' => 'g1',
                'status' => 4,
                'thumbnailFileName' => 'preview.jpg',
            ], 200),
        ]);

        $client = BunnyApiClient::fromConfig();
        $provider = new BunnyStreamProvider($client);
        $urls = $provider->getPlaybackUrls('g1');

        $this->assertStringEndsWith('/g1/playlist.m3u8', $urls['hls']);
        $this->assertStringContainsString('preview.jpg', (string) $urls['thumbnail']);
    }
}
