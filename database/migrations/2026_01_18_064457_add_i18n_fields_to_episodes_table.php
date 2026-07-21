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
        Schema::table('episodes', function (Blueprint $table) {
            $table->string('title_fr')->nullable()->after('title');
            $table->string('title_en')->nullable()->after('title_fr');

            $table->text('description_fr')->nullable()->after('description');
            $table->text('description_en')->nullable()->after('description_fr');
        });

        DB::table('episodes')->whereNull('title_fr')->update(['title_fr' => DB::raw('title')]);
        DB::table('episodes')->whereNull('title_en')->update(['title_en' => DB::raw('title')]);
        DB::table('episodes')->whereNull('description_fr')->update(['description_fr' => DB::raw('description')]);
        DB::table('episodes')->whereNull('description_en')->update(['description_en' => DB::raw('description')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn([
                'title_fr',
                'title_en',
                'description_fr',
                'description_en',
            ]);
        });
    }
};
