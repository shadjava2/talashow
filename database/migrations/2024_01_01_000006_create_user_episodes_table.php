<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('episode_id')->constrained()->onDelete('cascade');
            $table->boolean('is_unlocked')->default(false);
            $table->string('unlock_method')->nullable(); // subscription, coins
            $table->integer('watch_progress')->default(0); // Position en secondes
            $table->boolean('is_completed')->default(false);
            $table->timestamp('watched_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'episode_id']);
            $table->index('user_id');
            $table->index('episode_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_episodes');
    }
};
