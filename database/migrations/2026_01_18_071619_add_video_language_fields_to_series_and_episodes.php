<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Series: langues vidéo disponibles (codes) - ex: ["fr","en","ln"]
        Schema::table('series', function (Blueprint $table) {
            if (!Schema::hasColumn('series', 'video_languages')) {
                $table->json('video_languages')->nullable()->after('language');
            }
        });

        // Episodes: URLs par langue - ex: {"fr":"...m3u8","en":"...m3u8"}
        Schema::table('episodes', function (Blueprint $table) {
            if (!Schema::hasColumn('episodes', 'video_urls')) {
                $table->json('video_urls')->nullable()->after('video_url');
            }
        });

        // Backfill (best-effort): on construit series.video_languages à partir de series.language existant.
        if (Schema::hasTable('series')) {
            $seriesRows = DB::table('series')
                ->select(['id', 'language', 'video_languages'])
                ->get();

            foreach ($seriesRows as $row) {
                $lang = strtolower(trim((string) ($row->language ?? '')));
                if ($lang === '') $lang = 'fr';

                $existing = null;
                if (!empty($row->video_languages)) {
                    try {
                        $existing = json_decode((string) $row->video_languages, true, 512, JSON_THROW_ON_ERROR);
                    } catch (\Throwable) {
                        $existing = null;
                    }
                }

                $list = [];
                if (is_array($existing)) {
                    $list = array_values(array_filter(array_map(function ($v) {
                        $v = strtolower(trim((string) $v));
                        return $v !== '' ? $v : null;
                    }, $existing)));
                }

                if (!in_array($lang, $list, true)) {
                    $list[] = $lang;
                }
                $list = array_values(array_unique($list));

                DB::table('series')->where('id', $row->id)->update([
                    'video_languages' => json_encode($list),
                ]);
            }
        }

        // Backfill episodes.video_urls à partir de episodes.video_url + series.language
        if (Schema::hasTable('episodes') && Schema::hasTable('series')) {
            $episodeRows = DB::table('episodes')
                ->join('series', 'series.id', '=', 'episodes.series_id')
                ->select(['episodes.id', 'episodes.video_url', 'episodes.video_urls', 'series.language'])
                ->get();

            foreach ($episodeRows as $row) {
                $url = trim((string) ($row->video_url ?? ''));
                if ($url === '') continue;

                $lang = strtolower(trim((string) ($row->language ?? '')));
                if ($lang === '') $lang = 'fr';

                $map = [];
                if (!empty($row->video_urls)) {
                    try {
                        $decoded = json_decode((string) $row->video_urls, true, 512, JSON_THROW_ON_ERROR);
                        if (is_array($decoded)) $map = $decoded;
                    } catch (\Throwable) {
                        $map = [];
                    }
                }

                if (!isset($map[$lang]) || trim((string) $map[$lang]) === '') {
                    $map[$lang] = $url;
                }

                DB::table('episodes')->where('id', $row->id)->update([
                    'video_urls' => json_encode($map),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (Schema::hasColumn('episodes', 'video_urls')) {
                $table->dropColumn('video_urls');
            }
        });

        Schema::table('series', function (Blueprint $table) {
            if (Schema::hasColumn('series', 'video_languages')) {
                $table->dropColumn('video_languages');
            }
        });
    }
};
