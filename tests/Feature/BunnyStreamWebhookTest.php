<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Series;
use App\Models\VideoProviderMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BunnyStreamWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_invalid_signature_when_secret_configured(): void
    {
        config(['services.bunny_stream.webhook_secret' => 'testsecret']);

        $response = $this->postJson('/webhooks/bunny/stream', ['VideoGuid' => 'abc'], [
            'X-Bunny-Signature' => 'wrong',
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_accepts_valid_hmac_and_returns_ok_without_mapping(): void
    {
        config(['services.bunny_stream.webhook_secret' => 'testsecret']);

        $body = json_encode(['VideoGuid' => 'nonexistent-guid-123']);
        $sig = hash_hmac('sha256', $body, 'testsecret');

        $response = $this->call(
            'POST',
            '/webhooks/bunny/stream',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_BUNNY_SIGNATURE' => $sig],
            $body
        );

        $response->assertOk();
        $json = $response->json();
        $this->assertTrue($json['ok'] ?? false);
        $this->assertArrayHasKey('mapped', $json);
        $this->assertFalse($json['mapped']);
    }

    public function test_webhook_updates_mapping_when_bunny_reports_ready(): void
    {
        config([
            'services.bunny_stream.webhook_secret' => null,
            'services.bunny_stream.library_id' => '1',
            'services.bunny_stream.api_key' => 'key',
            'services.bunny_stream.cdn_hostname' => 'vz-test.b-cdn.net',
        ]);

        $series = Series::create([
            'title' => 'S',
            'slug' => 's-test-webhook',
            'is_active' => true,
        ]);

        $episode = Episode::create([
            'series_id' => $series->id,
            'episode_number' => 1,
            'title' => 'E1',
            'video_url' => 'https://example.invalid/x.m3u8',
            'video_type' => 'cloudflare',
            'is_free' => true,
        ]);

        $guid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
        VideoProviderMapping::create([
            'video_id' => $episode->id,
            'video_lang' => 'fr',
            'content_type' => 'episode',
            'content_id' => $episode->id,
            'source_provider' => 'cloudflare',
            'source_asset_id' => 'deadbeefdeadbeefdeadbeefdeadbeef',
            'source_playback_url' => 'https://customer-x.cloudflarestream.com/deadbeefdeadbeefdeadbeefdeadbeef/manifest/video.m3u8',
            'target_provider' => 'bunny',
            'target_library_id' => '1',
            'target_video_guid' => $guid,
            'target_cdn_hostname' => 'vz-test.b-cdn.net',
            'migration_status' => VideoProviderMapping::STATUS_PROCESSING,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'https://video.bunnycdn.com/library/1/videos/'.$guid => \Illuminate\Support\Facades\Http::response([
                'guid' => $guid,
                'status' => 4,
                'encodeProgress' => 100,
                'thumbnailFileName' => 'thumb.jpg',
            ], 200),
        ]);

        $response = $this->postJson('/webhooks/bunny/stream', ['VideoGuid' => $guid]);
        $response->assertOk();

        $mapping = VideoProviderMapping::where('target_video_guid', $guid)->first();
        $this->assertNotNull($mapping);
        $this->assertSame(VideoProviderMapping::STATUS_READY, $mapping->migration_status);
        $this->assertStringContainsString('playlist.m3u8', (string) $mapping->target_hls_url);
    }
}
