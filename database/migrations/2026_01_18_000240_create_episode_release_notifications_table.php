<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episode_release_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained('episodes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('locale', 10)->nullable();
            $table->timestamp('notified_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['episode_id', 'user_id']);
            $table->index(['episode_id', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_release_notifications');
    }
};

