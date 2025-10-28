<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const string GUARD_NAME = 'web';

    public function up(): void
    {
        // Reset cached roles and permissions
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create all permissions from enum
        foreach (PermissionEnum::cases() as $permissionEnum) {
            Permission::findOrCreate($permissionEnum->value, self::GUARD_NAME);
        }

        // Create roles and assign their permissions
        foreach (RoleEnum::cases() as $roleEnum) {
            $role = Role::findOrCreate($roleEnum->value, self::GUARD_NAME);

            // Get permissions for this role
            $permissions = PermissionEnum::forRole($roleEnum);

            // Assign permissions to role
            $role->syncPermissions($permissions);
        }
    }

    public function down(): void
    {
        // Reset cached roles and permissions
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Delete all roles
        foreach (RoleEnum::cases() as $roleEnum) {
            Role::query()->where('name', $roleEnum->value)->delete();
        }

        // Delete all permissions
        foreach (PermissionEnum::cases() as $permissionEnum) {
            Permission::query()->where('name', $permissionEnum->value)->delete();
        }
    }
};
