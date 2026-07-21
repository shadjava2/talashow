<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TalashowResetViews extends Command
{
    protected $signature = 'talashow:reset-views {--force : Exécuter sans confirmation (destructif)}';

    protected $description = 'Remet les compteurs de vues à zéro et vide les tables de tracking (1 vue unique par user/épisode).';

    public function handle(): int
    {
        if (!$this->option('force')) {
            $this->error("Commande destructive. Relance avec --force si tu es sûr.");
            return self::FAILURE;
        }

        $this->info('Reset views: start...');

        $driver = DB::getDriverName();
        $disableFk = function () use ($driver) {
            if ($driver === 'mysql') DB::statement('SET FOREIGN_KEY_CHECKS=0');
            if ($driver === 'sqlite') DB::statement('PRAGMA foreign_keys = OFF');
        };
        $enableFk = function () use ($driver) {
            if ($driver === 'mysql') DB::statement('SET FOREIGN_KEY_CHECKS=1');
            if ($driver === 'sqlite') DB::statement('PRAGMA foreign_keys = ON');
        };

        $disableFk();
        try {
            if (Schema::hasTable('episode_views')) {
                DB::table('episode_views')->truncate();
            }
            // Table présente mais plus utilisée si tu veux strictement "par épisode"
            if (Schema::hasTable('series_views')) {
                DB::table('series_views')->truncate();
            }
        } finally {
            $enableFk();
        }

        // Remettre à 0 les compteurs agrégés
        if (Schema::hasColumn('episodes', 'views_count')) {
            DB::table('episodes')->update(['views_count' => 0]);
        }
        if (Schema::hasColumn('series', 'views_count')) {
            DB::table('series')->update(['views_count' => 0]);
        }

        $this->info('Reset views: done ✅');
        return self::SUCCESS;
    }
}

