<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('roles have correct permissions assigned', function (): void {
    $role = Role::findByName(RoleEnum::Borrower->value);

    expect($role->hasPermissionTo(PermissionEnum::BorrowerLoansRequest->value))->toBeTrue()
        ->and($role->hasPermissionTo(PermissionEnum::BorrowerLoansView->value))->toBeTrue();
});

it('can assign user to role', function (): void {
    $user = User::factory()->create();
    $role = Role::findByName(RoleEnum::Borrower->value);

    $user->assignRole($role);

    expect($user->hasRole(RoleEnum::Borrower->value))->toBeTrue();
});

it('user inherits permissions from role', function (): void {
    $user = User::factory()->create();
    $role = Role::findByName(RoleEnum::Borrower->value);

    $user->assignRole($role);

    expect($user->can(PermissionEnum::BorrowerLoansView->value))->toBeTrue();
});

it('can assign multiple roles to user', function (): void {
    $user = User::factory()->create();
    $borrowerRole = Role::findByName(RoleEnum::Borrower->value);
    $lenderRole = Role::findByName(RoleEnum::Lender->value);

    $user->assignRole([$borrowerRole, $lenderRole]);

    expect($user->hasRole(RoleEnum::Borrower->value))->toBeTrue()
        ->and($user->hasRole(RoleEnum::Lender->value))->toBeTrue();
});
