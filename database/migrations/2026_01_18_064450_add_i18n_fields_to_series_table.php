<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->string('title_fr')->nullable()->after('title');
            $table->string('title_en')->nullable()->after('title_fr');

            $table->text('description_fr')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_fr');

            $table->string('slug_fr')->nullable()->after('slug');
            $table->string('slug_en')->nullable()->after('slug_fr');

            $table->unique('slug_fr', 'series_slug_fr_unique');
            $table->unique('slug_en', 'series_slug_en_unique');
        });

        // Backfill depuis les champs existants (compat)
        DB::table('series')->whereNull('title_fr')->update(['title_fr' => DB::raw('title')]);
        DB::table('series')->whereNull('title_en')->update(['title_en' => DB::raw('title')]);
        DB::table('series')->whereNull('description_fr')->update(['description_fr' => DB::raw('description')]);
        DB::table('series')->whereNull('description_en')->update(['description_en' => DB::raw('description')]);
        DB::table('series')->whereNull('slug_fr')->update(['slug_fr' => DB::raw('slug')]);
        DB::table('series')->whereNull('slug_en')->update(['slug_en' => DB::raw('slug')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->dropUnique('series_slug_fr_unique');
            $table->dropUnique('series_slug_en_unique');

            $table->dropColumn([
                'title_fr',
                'title_en',
                'description_fr',
                'description_en',
                'slug_fr',
                'slug_en',
            ]);
        });
    }
};
