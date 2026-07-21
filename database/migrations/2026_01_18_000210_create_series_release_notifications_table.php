<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series_release_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('series')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable(); // fallback (si un jour on autorise les invités)
            $table->string('locale', 10)->nullable();
            $table->timestamp('notified_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['series_id', 'user_id']);
            $table->index(['series_id', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series_release_notifications');
    }
};

