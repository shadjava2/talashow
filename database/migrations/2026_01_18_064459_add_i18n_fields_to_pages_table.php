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
        Schema::table('pages', function (Blueprint $table) {
            $table->string('title_fr')->nullable()->after('title');
            $table->string('title_en')->nullable()->after('title_fr');

            $table->longText('content_fr')->nullable()->after('content');
            $table->longText('content_en')->nullable()->after('content_fr');

            $table->string('slug_fr')->nullable()->after('slug');
            $table->string('slug_en')->nullable()->after('slug_fr');

            $table->unique('slug_fr', 'pages_slug_fr_unique');
            $table->unique('slug_en', 'pages_slug_en_unique');
        });

        DB::table('pages')->whereNull('title_fr')->update(['title_fr' => DB::raw('title')]);
        DB::table('pages')->whereNull('title_en')->update(['title_en' => DB::raw('title')]);
        DB::table('pages')->whereNull('content_fr')->update(['content_fr' => DB::raw('content')]);
        DB::table('pages')->whereNull('content_en')->update(['content_en' => DB::raw('content')]);
        DB::table('pages')->whereNull('slug_fr')->update(['slug_fr' => DB::raw('slug')]);
        DB::table('pages')->whereNull('slug_en')->update(['slug_en' => DB::raw('slug')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique('pages_slug_fr_unique');
            $table->dropUnique('pages_slug_en_unique');

            $table->dropColumn([
                'title_fr',
                'title_en',
                'content_fr',
                'content_en',
                'slug_fr',
                'slug_en',
            ]);
        });
    }
};
