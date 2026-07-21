<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('system_health_checks')) {
            return;
        }

        Schema::create('system_health_checks', function (Blueprint $table) {
            $table->id();
            $table->string('check_key', 80)->index();
            $table->string('status', 24)->default('unknown')->index();
            $table->json('payload')->nullable();
            $table->timestamp('checked_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_health_checks');
    }
};
