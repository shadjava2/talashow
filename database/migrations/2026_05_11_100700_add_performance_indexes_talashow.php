<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function indexExists(string $table, string $indexName): bool
    {
        if (! Schema::hasTable($table) || ! preg_match('/^[a-z0-9_]+$/i', $table)) {
            return false;
        }

        $rows = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName]);

        return count($rows) > 0;
    }

    protected function safeIndex(string $table, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    protected function dropIndexSafe(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    public function up(): void
    {
        $this->safeIndex('users', ['is_active'], 'talashow_users_is_active_idx');
        $this->safeIndex('series', ['is_active', 'published_at'], 'talashow_series_active_published_idx');
        $this->safeIndex('episodes', ['series_id', 'is_active', 'published_at'], 'talashow_episodes_series_active_pub_idx');
        $this->safeIndex('watch_history', ['user_id', 'watched_at'], 'talashow_watch_hist_user_watched_idx');
        $this->safeIndex('favorites', ['series_id'], 'talashow_favorites_series_id_idx');
        $this->safeIndex('transactions', ['user_id', 'status'], 'talashow_tx_user_status_idx');
        $this->safeIndex('subscriptions', ['user_id', 'is_active', 'ends_at'], 'talashow_sub_user_active_ends_idx');
        if (Schema::hasTable('newsletter_campaigns')) {
            $this->safeIndex('newsletter_campaigns', ['status', 'created_at'], 'talashow_nl_campaign_status_created_idx');
        }
    }

    public function down(): void
    {
        $this->dropIndexSafe('newsletter_campaigns', 'talashow_nl_campaign_status_created_idx');
        $this->dropIndexSafe('subscriptions', 'talashow_sub_user_active_ends_idx');
        $this->dropIndexSafe('transactions', 'talashow_tx_user_status_idx');
        $this->dropIndexSafe('favorites', 'talashow_favorites_series_id_idx');
        $this->dropIndexSafe('watch_history', 'talashow_watch_hist_user_watched_idx');
        $this->dropIndexSafe('episodes', 'talashow_episodes_series_active_pub_idx');
        $this->dropIndexSafe('series', 'talashow_series_active_published_idx');
        $this->dropIndexSafe('users', 'talashow_users_is_active_idx');
    }
};
