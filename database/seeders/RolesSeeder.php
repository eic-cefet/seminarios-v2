<?php

namespace Database\Seeders;

use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as SpatieRole;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        SpatieRole::firstOrCreate(['name' => Role::Admin->value, 'guard_name' => 'web']);
        SpatieRole::firstOrCreate(['name' => Role::Teacher->value, 'guard_name' => 'web']);
    }
}
