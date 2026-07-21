<?php

namespace Tests\Unit;

use App\Models\Episode;
use App\Models\Series;
use App\Models\VideoProviderMapping;
use App\Services\Video\BunnyEmbedTokenService;
use App\Services\Video\BunnyStreamPlaybackUrlParser;
use App\Services\Video\BunnyUrlSigningService;
use App\Services\Video\VideoPlaybackResolverService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoPlaybackResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_legacy_hls_when_bunny_mapping_not_ready(): void
    {
        config(['video.default_provider' => 'bunny']);

        $series = Series::create([
            'title' => 'S',
            'slug' => 's-resolver-1',
            'is_active' => true,
            'video_languages' => ['fr'],
        ]);

        $legacyUrl = 'https://customer-x.cloudflarestream.com/deadbeefdeadbeefdeadbeefdeadbeef/manifest/video.m3u8';
        $episode = Episode::create([
            'series_id' => $series->id,
            'episode_number' => 1,
            'title' => 'E1',
            'video_url' => $legacyUrl,
            'video_type' => 'cloudflare',
            'is_free' => true,
        ]);

        VideoProviderMapping::create([
            'video_id' => $episode->id,
            'video_lang' => 'fr',
            'content_type' => 'episode',
            'content_id' => $episode->id,
            'source_provider' => 'legacy_local',
            'target_provider' => 'bunny',
            'migration_status' => VideoProviderMapping::STATUS_PROCESSING,
            'target_hls_url' => null,
        ]);

        $resolver = new VideoPlaybackResolverService(
            new BunnyUrlSigningService,
            new BunnyEmbedTokenService,
            new BunnyStreamPlaybackUrlParser,
        );
        $out = $resolver->resolve($episode, 'fr', $legacyUrl, null);

        $this->assertSame('hls', $out['provider']);
        $this->assertSame('hls', $out['mode']);
        $this->assertSame($legacyUrl, $out['hls_url']);
        $this->assertSame('', $out['embed_url']);
        $this->assertTrue($out['is_ready']);
    }

    public function test_uses_bunny_when_mapping_ready_and_default_bunny(): void
    {
        config(['video.default_provider' => 'bunny']);

        $series = Series::create([
            'title' => 'S',
            'slug' => 's-resolver-2',
            'is_active' => true,
            'video_languages' => ['fr'],
        ]);

        $legacyUrl = 'https://customer-x.cloudflarestream.com/deadbeefdeadbeefdeadbeefdeadbeef/manifest/video.m3u8';
        $bunnyHls = 'https://vz-test.b-cdn.net/guid/playlist.m3u8';

        $episode = Episode::create([
            'series_id' => $series->id,
            'episode_number' => 1,
            'title' => 'E1',
            'video_url' => $legacyUrl,
            'video_type' => 'cloudflare',
            'is_free' => true,
        ]);

        VideoProviderMapping::create([
            'video_id' => $episode->id,
            'video_lang' => 'fr',
            'content_type' => 'episode',
            'content_id' => $episode->id,
            'source_provider' => 'legacy_local',
            'target_provider' => 'bunny',
            'migration_status' => VideoProviderMapping::STATUS_READY,
            'target_hls_url' => $bunnyHls,
            'target_video_guid' => 'guid',
        ]);

        $resolver = new VideoPlaybackResolverService(
            new BunnyUrlSigningService,
            new BunnyEmbedTokenService,
            new BunnyStreamPlaybackUrlParser,
        );
        $out = $resolver->resolve($episode, 'fr', $legacyUrl, null);

        $this->assertSame('bunny', $out['provider']);
        $this->assertSame('hls', $out['mode']);
        $this->assertSame($bunnyHls, $out['hls_url']);
        $this->assertTrue($out['is_ready']);
    }

    public function test_bunny_embed_mode_returns_embed_url(): void
    {
        config([
            'video.default_provider' => 'bunny',
            'video.playback_driver' => 'bunny_embed',
            'video.bunny_iframe_url_style' => 'play',
            'services.bunny_stream.library_id' => '759',
            'services.bunny_stream.player_iframe_base' => 'https://player.mediadelivery.net/play',
            'services.bunny_stream.embed_token_auth_enabled' => false,
        ]);

        $series = Series::create([
            'title' => 'S',
            'slug' => 's-resolver-embed',
            'is_active' => true,
            'video_languages' => ['fr'],
        ]);

        $guid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';

        $episode = Episode::create([
            'series_id' => $series->id,
            'episode_number' => 1,
            'title' => 'E1',
            'video_url' => 'https://vz-test.b-cdn.net/'.$guid.'/playlist.m3u8',
            'video_provider' => 'bunny',
            'external_video_id' => $guid,
            'hls_url' => 'https://vz-test.b-cdn.net/'.$guid.'/playlist.m3u8',
            'video_status' => 'ready',
            'is_free' => true,
        ]);

        $resolver = new VideoPlaybackResolverService(
            new BunnyUrlSigningService,
            new BunnyEmbedTokenService,
            new BunnyStreamPlaybackUrlParser,
        );
        $out = $resolver->resolve($episode, 'fr', '', null);

        $this->assertSame('bunny', $out['provider']);
        $this->assertSame('embed', $out['mode']);
        $this->assertStringContainsString('player.mediadelivery.net/play/759/'.$guid, $out['embed_url']);
        $this->assertTrue($out['is_ready']);
    }

    public function test_bunny_embed_parses_guid_from_mediadelivery_play_url(): void
    {
        config([
            'video.default_provider' => 'bunny',
            'video.playback_driver' => 'bunny_embed',
            'video.bunny_iframe_url_style' => 'play',
            'services.bunny_stream.library_id' => '999',
            'services.bunny_stream.player_iframe_base' => 'https://player.mediadelivery.net/play',
            'services.bunny_stream.embed_token_auth_enabled' => false,
        ]);

        $series = Series::create([
            'title' => 'S',
            'slug' => 's-resolver-play-url',
            'is_active' => true,
            'video_languages' => ['fr'],
        ]);

        $playUrl = 'https://player.mediadelivery.net/play/624073/0f12a54a-b75f-40ae-b301-97451f07c885';

        $episode = Episode::create([
            'series_id' => $series->id,
            'episode_number' => 1,
            'title' => 'E1',
            'video_url' => '',
            'video_urls' => ['fr' => $playUrl],
            'video_type' => 'url',
            'is_free' => true,
        ]);

        $resolver = new VideoPlaybackResolverService(
            new BunnyUrlSigningService,
            new BunnyEmbedTokenService,
            new BunnyStreamPlaybackUrlParser,
        );
        $out = $resolver->resolve($episode, 'fr', $playUrl, null);

        $this->assertSame('bunny', $out['provider']);
        $this->assertSame('embed', $out['mode']);
        $this->assertStringContainsString('player.mediadelivery.net/play/624073/0f12a54a-b75f-40ae-b301-97451f07c885', $out['embed_url']);
        $this->assertTrue($out['is_ready']);
    }

    public function test_bunny_embed_parses_guid_from_b_cdn_hls_path(): void
    {
        config([
            'video.default_provider' => 'bunny',
            'video.playback_driver' => 'bunny_embed',
            'video.bunny_iframe_url_style' => 'play',
            'services.bunny_stream.library_id' => '624073',
            'services.bunny_stream.player_iframe_base' => 'https://player.mediadelivery.net/play',
            'services.bunny_stream.embed_token_auth_enabled' => false,
        ]);

        $guid = '0f12a54a-b75f-40ae-b301-97451f07c885';
        $hls = 'https://vz-feb6b958-5fe.b-cdn.net/'.$guid.'/playlist.m3u8';

        $series = Series::create([
            'title' => 'S',
            'slug' => 's-resolver-cdn-guid',
            'is_active' => true,
            'video_languages' => ['fr'],
        ]);

        $episode = Episode::create([
            'series_id' => $series->id,
            'episode_number' => 1,
            'title' => 'E1',
            'video_url' => '',
            'video_urls' => ['fr' => $hls],
            'video_type' => 'url',
            'is_free' => true,
        ]);

        $resolver = new VideoPlaybackResolverService(
            new BunnyUrlSigningService,
            new BunnyEmbedTokenService,
            new BunnyStreamPlaybackUrlParser,
        );
        $out = $resolver->resolve($episode, 'fr', $hls, null);

        $this->assertSame('embed', $out['mode']);
        $this->assertStringContainsString('player.mediadelivery.net/play/624073/'.$guid, $out['embed_url']);
        $this->assertTrue($out['is_ready']);
    }
}
