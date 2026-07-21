<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_provider_mappings', function (Blueprint $table) {
            $table->id();
            // Métier « vidéo » = épisode dans cette app (FK vers episodes.id)
            $table->foreignId('video_id')->nullable()->constrained('episodes')->nullOnDelete();
            $table->string('video_lang', 32)->nullable();
            $table->string('content_type', 64)->nullable();
            $table->unsignedBigInteger('content_id')->nullable();
            $table->string('source_provider', 32);
            $table->string('source_asset_id', 128)->nullable();
            $table->text('source_playback_url')->nullable();
            $table->string('target_provider', 32)->default('bunny');
            $table->string('target_library_id', 64)->nullable();
            $table->string('target_video_guid', 64)->nullable();
            $table->string('target_cdn_hostname', 255)->nullable();
            $table->text('target_hls_url')->nullable();
            $table->string('migration_status', 32)->default('pending');
            $table->text('migration_error')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('migrated_at')->nullable();
            $table->timestamps();

            $table->unique(['video_id', 'video_lang']);
            $table->index('migration_status');
            $table->index('target_video_guid');
            $table->index(['content_type', 'content_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_provider_mappings');
    }
};
