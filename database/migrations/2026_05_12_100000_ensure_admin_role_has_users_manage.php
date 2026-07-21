<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            return;
        }

        $adminRole = Role::query()->where('key', 'admin')->first();
        $perm = Permission::query()->where('key', 'users.manage')->first();

        if (! $adminRole || ! $perm) {
            return;
        }

        $adminRole->permissions()->syncWithoutDetaching([$perm->id]);
    }

    public function down(): void
    {
        // Pas de rollback : permission légitime pour le rôle admin.
    }
};
