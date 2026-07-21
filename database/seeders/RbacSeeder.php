<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        // Protection: ne jamais modifier la base en production (cPanel)
        if (app()->environment('production')) {
            $this->command?->warn('RbacSeeder désactivé en production.');
            return;
        }

        // Activation explicite seulement (évite toute exécution accidentelle)
        if (!filter_var(env('TALASHOW_ENABLE_SEEDING', false), FILTER_VALIDATE_BOOL)) {
            $this->command?->warn('RbacSeeder désactivé (TALASHOW_ENABLE_SEEDING=true pour l’activer en local).');
            return;
        }

        $roleAdmin = Role::updateOrCreate(['key' => 'admin'], ['name' => 'Admin']);
        $roleEditor = Role::updateOrCreate(['key' => 'editor'], ['name' => 'Editor']);
        $roleUser = Role::updateOrCreate(['key' => 'user'], ['name' => 'User']);

        $perms = [
            ['key' => 'adminapp.access', 'name' => 'Accès au backoffice Talashow'],
            ['key' => 'series.manage', 'name' => 'Gérer les séries'],
            ['key' => 'genres.manage', 'name' => 'Gérer les genres'],
            ['key' => 'payments.view', 'name' => 'Voir transactions & abonnements'],
            ['key' => 'settings.manage', 'name' => 'Gérer les paramètres'],
            ['key' => 'users.manage', 'name' => 'Gérer les utilisateurs / rôles'],
            ['key' => 'monitoring.view', 'name' => 'Monitoring & sécurité'],
        ];

        foreach ($perms as $p) {
            Permission::updateOrCreate(['key' => $p['key']], ['name' => $p['name']]);
        }

        $roleAdmin->permissions()->sync(Permission::query()->pluck('id')->all());
        $roleEditor->permissions()->sync(
            Permission::query()
                ->whereIn('key', ['adminapp.access', 'series.manage', 'genres.manage'])
                ->pluck('id')
                ->all()
        );
        $roleUser->permissions()->sync([]);

        // ⚠️ IMPORTANT: ne pas modifier des utilisateurs existants automatiquement.
        // L’assignation de rôles doit être faite via le backoffice / actions admin.
    }
}

