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
        Schema::table('genres', function (Blueprint $table) {
            $table->string('name_fr')->nullable()->after('name');
            $table->string('name_en')->nullable()->after('name_fr');

            $table->string('slug_fr')->nullable()->after('slug');
            $table->string('slug_en')->nullable()->after('slug_fr');

            $table->unique('slug_fr', 'genres_slug_fr_unique');
            $table->unique('slug_en', 'genres_slug_en_unique');
        });

        DB::table('genres')->whereNull('name_fr')->update(['name_fr' => DB::raw('name')]);
        DB::table('genres')->whereNull('name_en')->update(['name_en' => DB::raw('name')]);
        DB::table('genres')->whereNull('slug_fr')->update(['slug_fr' => DB::raw('slug')]);
        DB::table('genres')->whereNull('slug_en')->update(['slug_en' => DB::raw('slug')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genres', function (Blueprint $table) {
            $table->dropUnique('genres_slug_fr_unique');
            $table->dropUnique('genres_slug_en_unique');

            $table->dropColumn([
                'name_fr',
                'name_en',
                'slug_fr',
                'slug_en',
            ]);
        });
    }
};
