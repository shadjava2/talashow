<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('episode_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable(); // Pour utilisateurs non connectés
            $table->integer('watch_time')->default(0); // Temps regardé en secondes
            $table->integer('duration')->nullable(); // Durée totale de l'épisode
            $table->boolean('is_completed')->default(false);
            $table->timestamp('watched_at');
            $table->timestamps();

            $table->index('user_id');
            $table->index('episode_id');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_history');
    }
};
