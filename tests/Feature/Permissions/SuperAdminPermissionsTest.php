<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;

beforeEach(function (): void {
    $this->admin = User::factory()->superAdmin()->create();
    $this->admin->assignRole(RoleEnum::SUPER_ADMIN->value);
});

it('super admin has all permissions', function (): void {
    foreach (PermissionEnum::cases() as $permission) {
        expect($this->admin->can($permission->value))->toBeTrue();
    }
});

it('super admin has borrower permissions', function (): void {
    expect($this->admin->can(PermissionEnum::BORROWER_LOANS_REQUEST->value))->toBeTrue()
        ->and($this->admin->can(PermissionEnum::BORROWER_LOANS_VIEW->value))->toBeTrue();
});

it('super admin has lender permissions', function (): void {
    expect($this->admin->can(PermissionEnum::LENDER_WALLET_MANAGE->value))->toBeTrue()
        ->and($this->admin->can(PermissionEnum::LENDER_LOANS_BID->value))->toBeTrue();
});

it('super admin has back office permissions', function (): void {
    expect($this->admin->can(PermissionEnum::BACKOFFICE_USERS_APPROVE->value))->toBeTrue()
        ->and($this->admin->can(PermissionEnum::BACKOFFICE_LOANS_REVIEW->value))->toBeTrue();
});

it('super admin has admin-only permissions', function (): void {
    expect($this->admin->can(PermissionEnum::ADMIN_USERS_DELETE->value))->toBeTrue()
        ->and($this->admin->can(PermissionEnum::ADMIN_SYSTEM_CONFIGURE->value))->toBeTrue()
        ->and($this->admin->can(PermissionEnum::ADMIN_REPORTS_FULL->value))->toBeTrue();
});
