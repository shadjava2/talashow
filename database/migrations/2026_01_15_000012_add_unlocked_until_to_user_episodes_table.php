<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_episodes', function (Blueprint $table) {
            $table->timestamp('unlocked_until')->nullable()->after('unlock_method');
        });

        // Backfill: pour les anciens déblocages par pièces, appliquer la règle 7 jours à partir de created_at.
        // (Évite que les démos restent débloquées à vie.)
        try {
            DB::statement("
                UPDATE user_episodes
                SET unlocked_until = DATE_ADD(created_at, INTERVAL 7 DAY)
                WHERE is_unlocked = 1
                  AND unlock_method = 'coins'
                  AND unlocked_until IS NULL
            ");
        } catch (\Throwable $e) {
            // no-op (selon driver/SQL mode)
        }
    }

    public function down(): void
    {
        Schema::table('user_episodes', function (Blueprint $table) {
            $table->dropColumn('unlocked_until');
        });
    }
};

