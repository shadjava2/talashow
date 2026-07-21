<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('video_provider', 32)->nullable()->after('video_type');
            $table->string('external_video_id', 128)->nullable()->after('video_provider');
            $table->string('playback_url')->nullable()->after('video_url');
            $table->string('hls_url')->nullable()->after('playback_url');
            $table->string('video_status', 64)->nullable()->after('hls_url');
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn([
                'video_provider',
                'external_video_id',
                'playback_url',
                'hls_url',
                'video_status',
            ]);
        });
    }
};
