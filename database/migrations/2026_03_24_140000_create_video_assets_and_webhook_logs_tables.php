<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_assets', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('type')->default('episode');
            $table->string('title')->nullable();
            $table->string('slug')->nullable()->index();
            $table->string('bunny_video_guid')->nullable()->index();
            $table->string('bunny_library_id')->nullable();
            $table->string('bunny_collection_id')->nullable();
            $table->string('bunny_status')->nullable();
            $table->text('bunny_embed_url')->nullable();
            $table->text('bunny_play_url')->nullable();
            $table->text('bunny_thumbnail_url')->nullable();
            $table->text('bunny_hls_url')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedTinyInteger('encode_progress')->nullable();
            $table->boolean('is_public')->default(false);
            $table->string('visibility')->default('private');
            $table->string('processing_state')->default('draft');
            $table->json('meta_json')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['model_type', 'model_id']);
        });

        Schema::create('video_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('bunny_stream');
            $table->string('event_name')->nullable();
            $table->json('payload_json');
            $table->json('headers_json')->nullable();
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_webhook_logs');
        Schema::dropIfExists('video_assets');
    }
};
