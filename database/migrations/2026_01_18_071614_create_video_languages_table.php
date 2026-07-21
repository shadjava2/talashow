<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('video_languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique(); // ex: fr, en, ln, sw
            $table->string('name', 80); // ex: Français, English, Lingala
            $table->string('native_name', 80)->nullable(); // ex: Français, English, Lingála
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_languages');
    }
};
