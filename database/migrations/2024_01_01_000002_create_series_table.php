<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('poster')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('trailer_url')->nullable();
            $table->integer('total_episodes')->default(0);
            $table->integer('duration')->nullable(); // en minutes
            $table->year('release_year')->nullable();
            $table->string('language')->default('fr');
            $table->json('genres')->nullable(); // ['Romance', 'Mafia', 'Marriage Before Love']
            $table->json('tags')->nullable();
            $table->boolean('is_exclusive')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->integer('views_count')->default(0);
            $table->integer('likes_count')->default(0);
            $table->decimal('rating', 3, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('slug');
            $table->index('is_featured');
            $table->index('is_trending');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
