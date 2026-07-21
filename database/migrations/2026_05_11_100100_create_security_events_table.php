<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('security_events')) {
            return;
        }

        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('route', 255)->nullable()->index();
            $table->string('method', 16)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('type', 80)->index();
            $table->string('level', 16)->default('low')->index();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
