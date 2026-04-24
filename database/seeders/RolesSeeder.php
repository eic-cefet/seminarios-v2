<?php

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        SpatieRole::firstOrCreate(['name' => Role::Admin->value, 'guard_name' => 'web']);
        SpatieRole::firstOrCreate(['name' => Role::Teacher->value, 'guard_name' => 'web']);
    }
}
