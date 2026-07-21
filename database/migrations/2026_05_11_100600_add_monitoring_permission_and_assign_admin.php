<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $exists = DB::table('permissions')->where('key', 'monitoring.view')->exists();
        if (! $exists) {
            DB::table('permissions')->insert([
                'key' => 'monitoring.view',
                'name' => 'Monitoring & sécurité (tableau)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permId = (int) DB::table('permissions')->where('key', 'monitoring.view')->value('id');
        $adminRoleId = DB::table('roles')->where('key', 'admin')->value('id');
        if ($permId && $adminRoleId && Schema::hasTable('permission_role')) {
            DB::table('permission_role')->insertOrIgnore([
                'role_id' => $adminRoleId,
                'permission_id' => $permId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permId = DB::table('permissions')->where('key', 'monitoring.view')->value('id');
        if ($permId && Schema::hasTable('permission_role')) {
            DB::table('permission_role')->where('permission_id', $permId)->delete();
        }
        DB::table('permissions')->where('key', 'monitoring.view')->delete();
    }
};
