<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;

beforeEach(function (): void {
    $this->admin = User::factory()->superAdmin()->create();
    $this->admin->assignRole(RoleEnum::SuperAdmin->value);
});

it('super admin has all permissions', function (): void {
    foreach (PermissionEnum::cases() as $permission) {
        expect($this->admin->can($permission->value))->toBeTrue();
    }
});

it('super admin has borrower permissions', function (): void {
    expect($this->admin->can(PermissionEnum::BorrowerLoansRequest->value))->toBeTrue()
        ->and($this->admin->can(PermissionEnum::BorrowerLoansView->value))->toBeTrue();
});

it('super admin has lender permissions', function (): void {
    expect($this->admin->can(PermissionEnum::LenderWalletManage->value))->toBeTrue()
        ->and($this->admin->can(PermissionEnum::LenderLoansBid->value))->toBeTrue();
});

it('super admin has back office permissions', function (): void {
    expect($this->admin->can(PermissionEnum::BackofficeUsersApprove->value))->toBeTrue()
        ->and($this->admin->can(PermissionEnum::BackofficeLoansReview->value))->toBeTrue();
});

it('super admin has admin-only permissions', function (): void {
    expect($this->admin->can(PermissionEnum::AdminUsersDelete->value))->toBeTrue()
        ->and($this->admin->can(PermissionEnum::AdminSystemConfigure->value))->toBeTrue()
        ->and($this->admin->can(PermissionEnum::AdminReportsFull->value))->toBeTrue();
});
