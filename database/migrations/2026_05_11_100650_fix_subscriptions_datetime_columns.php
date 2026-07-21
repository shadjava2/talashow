<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mutualisé MySQL / MariaDB (sql_mode strict) : des TIMESTAMP NOT NULL peuvent
 * provoquer « Invalid default value for 'ends_at' » lors d'un ALTER (ex. ajout d'index).
 * DATETIME évite les règles implicites problématiques des TIMESTAMP.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscriptions')) {
            return;
        }

        // Nettoyer d’éventuelles dates zéro (legacy) avant conversion
        try {
            DB::statement("UPDATE `subscriptions` SET `ends_at` = `starts_at` WHERE `ends_at` < '1980-01-01 00:00:00'");
            DB::statement("UPDATE `subscriptions` SET `starts_at` = NOW() WHERE `starts_at` < '1980-01-01 00:00:00'");
        } catch (\Throwable) {
        }

        try {
            DB::statement('ALTER TABLE `subscriptions` MODIFY `starts_at` DATETIME NOT NULL');
            DB::statement('ALTER TABLE `subscriptions` MODIFY `ends_at` DATETIME NOT NULL');
        } catch (\Throwable) {
            // Colonnes déjà en DATETIME ou schéma différent
        }

        if (Schema::hasColumn('subscriptions', 'cancelled_at')) {
            try {
                DB::statement('ALTER TABLE `subscriptions` MODIFY `cancelled_at` DATETIME NULL');
            } catch (\Throwable) {
            }
        }
    }

    public function down(): void
    {
        // Pas de retour arrière automatique (risque sur données prod).
    }
};
