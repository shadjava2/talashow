<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('video_languages')) {
            return;
        }

        $defaults = [
            ['code' => 'fr', 'name' => 'Français', 'native_name' => 'Français', 'sort_order' => 10],
            ['code' => 'en', 'name' => 'Anglais', 'native_name' => 'English', 'sort_order' => 20],
            ['code' => 'ln', 'name' => 'Lingala', 'native_name' => 'Lingála', 'sort_order' => 30],
            ['code' => 'sw', 'name' => 'Swahili', 'native_name' => 'Kiswahili', 'sort_order' => 40],
            ['code' => 'pt', 'name' => 'Portugais', 'native_name' => 'Português', 'sort_order' => 50],
            ['code' => 'es', 'name' => 'Espagnol', 'native_name' => 'Español', 'sort_order' => 60],
            ['code' => 'ar', 'name' => 'Arabe', 'native_name' => 'العربية', 'sort_order' => 70],
        ];

        foreach ($defaults as $row) {
            DB::table('video_languages')->updateOrInsert(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'native_name' => $row['native_name'],
                    'sort_order' => (int) $row['sort_order'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // no-op: on ne supprime pas les langues (peuvent être utilisées en prod)
    }
};
