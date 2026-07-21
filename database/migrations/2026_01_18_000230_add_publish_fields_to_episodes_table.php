<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (!Schema::hasColumn('episodes', 'published_at')) {
                $table->timestamp('published_at')->nullable()->index()->after('is_active');
            }
            if (!Schema::hasColumn('episodes', 'notified_newsletter_at')) {
                $table->timestamp('notified_newsletter_at')->nullable()->index()->after('published_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            if (Schema::hasColumn('episodes', 'notified_newsletter_at')) {
                $table->dropIndex(['notified_newsletter_at']);
                $table->dropColumn('notified_newsletter_at');
            }
            if (Schema::hasColumn('episodes', 'published_at')) {
                $table->dropIndex(['published_at']);
                $table->dropColumn('published_at');
            }
        });
    }
};

