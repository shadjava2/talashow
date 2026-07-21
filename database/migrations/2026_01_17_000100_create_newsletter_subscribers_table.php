<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();

            // Double opt-in
            $table->string('confirm_token', 80)->nullable()->unique();
            $table->timestamp('confirmed_at')->nullable();

            // Désinscription (valable même si confirmé)
            $table->string('unsubscribe_token', 80)->unique();
            $table->timestamp('unsubscribed_at')->nullable();

            // Meta (audit / anti-abus)
            $table->string('locale', 10)->nullable();
            $table->string('source', 40)->nullable(); // ex: application_cta
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};

