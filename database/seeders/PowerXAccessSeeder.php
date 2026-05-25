<?php

namespace Database\Seeders;

use App\Enums\PowerXPermission;
use App\Enums\PowerXRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PowerXAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PowerXPermission::cases() as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        foreach (PowerXRole::cases() as $role) {
            Role::query()
                ->firstOrCreate([
                    'name' => $role->value,
                    'guard_name' => 'web',
                ])
                ->syncPermissions(
                    collect($role->permissions())->map->value->all(),
                );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
