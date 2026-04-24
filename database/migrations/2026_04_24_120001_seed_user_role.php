<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        SpatieRole::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        // Intentionally no-op: removing the role would orphan any assigned users.
    }
};
