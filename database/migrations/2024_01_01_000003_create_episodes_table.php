<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained()->onDelete('cascade');
            $table->integer('episode_number');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('video_url'); // URL du fichier vidéo
            $table->string('video_type')->default('local'); // local, cdn, external
            $table->integer('duration')->nullable(); // en secondes
            $table->boolean('is_free')->default(false); // Épisode gratuit
            $table->boolean('is_premium_only')->default(false); // Nécessite abonnement
            $table->integer('unlock_coins')->default(0); // Coins nécessaires pour débloquer
            $table->integer('views_count')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['series_id', 'episode_number']);
            $table->index('series_id');
            $table->index('is_free');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
