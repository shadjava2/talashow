<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('series_id')->constrained('series')->onDelete('cascade');
            $table->timestamp('first_played_at')->nullable();
            $table->timestamp('last_played_at')->nullable();
            $table->unsignedInteger('play_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'series_id']);
            $table->index('series_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series_views');
    }
};

