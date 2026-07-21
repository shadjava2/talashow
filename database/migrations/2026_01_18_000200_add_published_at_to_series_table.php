<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            if (!Schema::hasColumn('series', 'published_at')) {
                $table->timestamp('published_at')->nullable()->index()->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            if (Schema::hasColumn('series', 'published_at')) {
                $table->dropIndex(['published_at']);
                $table->dropColumn('published_at');
            }
        });
    }
};

