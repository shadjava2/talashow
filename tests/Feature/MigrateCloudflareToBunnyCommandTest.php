<?php

namespace Tests\Feature;

use App\Models\Episode;
use App\Models\Series;
use App\Models\VideoProviderMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MigrateCloudflareToBunnyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_is_idempotent_when_target_guid_already_set(): void
    {
        config([
            'services.bunny_stream.library_id' => '1',
            'services.bunny_stream.api_key' => 'key',
            'services.bunny_stream.cdn_hostname' => 'vz-test.b-cdn.net',
        ]);

        $series = Series::create([
            'title' => 'S',
            'slug' => 's-migrate-cmd',
            'is_active' => true,
            'video_languages' => ['fr'],
        ]);

        $cfUrl = 'https://customer-x.cloudflarestream.com/deadbeefdeadbeefdeadbeefdeadbeef/manifest/video.m3u8';
        $episode = Episode::create([
            'series_id' => $series->id,
            'episode_number' => 1,
            'title' => 'E1',
            'video_url' => $cfUrl,
            'video_urls' => ['fr' => $cfUrl],
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
            'source_playback_url' => $cfUrl,
            'target_provider' => 'bunny',
            'target_library_id' => '1',
            'target_video_guid' => $guid,
            'target_cdn_hostname' => 'vz-test.b-cdn.net',
            'migration_status' => VideoProviderMapping::STATUS_PROCESSING,
        ]);

        Http::fake([
            'https://video.bunnycdn.com/library/1/videos/'.$guid => Http::response([
                'guid' => $guid,
                'status' => 4,
                'encodeProgress' => 100,
            ], 200),
        ]);

        $this->artisan('video:migrate-cloudflare-to-bunny', ['--chunk' => 5])
            ->assertSuccessful();

        Http::assertSentCount(1);
    }
}
