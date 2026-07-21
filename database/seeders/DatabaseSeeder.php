<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * IMPORTANT (PROD / cPanel)
         * -----------------------
         * On NE veut JAMAIS réinsérer des “données par défaut” (users démo, pages, séries/épisodes, RBAC…)
         * sur un serveur déjà configuré. Même un `php artisan migrate --seed` ne doit pas toucher la DB live.
         */
        if (app()->environment('production')) {
            $this->command?->warn('Seeding désactivé en production (aucune donnée par défaut ne sera insérée).');
            return;
        }

        /**
         * Par défaut, le seeding est désactivé partout.
         * Pour l’activer en DEV/local uniquement, définir:
         * TALASHOW_ENABLE_SEEDING=true
         */
        if (!filter_var(env('TALASHOW_ENABLE_SEEDING', false), FILTER_VALIDATE_BOOL)) {
            $this->command?->warn('Seeding désactivé (TALASHOW_ENABLE_SEEDING=true pour l’activer en local).');
            return;
        }

        // Exemple: $this->call([RbacSeeder::class]);
    }
}
