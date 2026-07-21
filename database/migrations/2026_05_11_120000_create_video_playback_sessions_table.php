<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('video_playback_sessions')) {
            return;
        }

        Schema::create('video_playback_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('episode_id')->constrained('episodes')->cascadeOnDelete();
            $table->string('guest_session_key', 128)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('device_fingerprint', 128)->nullable()->index();
            $table->string('video_lang', 16)->nullable();
            $table->string('session_token', 96)->unique();
            $table->string('playback_token_hash', 128)->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'episode_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_playback_sessions');
    }
};
