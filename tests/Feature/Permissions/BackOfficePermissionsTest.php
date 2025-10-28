<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;

beforeEach(function (): void {
    $this->backOffice = User::factory()->backOffice()->create();
    $this->backOffice->assignRole(RoleEnum::BACK_OFFICE->value);
});

it('back office has correct permissions', function (): void {
    expect($this->backOffice->can(PermissionEnum::BACKOFFICE_USERS_APPROVE->value))->toBeTrue()
        ->and($this->backOffice->can(PermissionEnum::BACKOFFICE_USERS_BLOCK->value))->toBeTrue()
        ->and($this->backOffice->can(PermissionEnum::BACKOFFICE_LOANS_REVIEW->value))->toBeTrue()
        ->and($this->backOffice->can(PermissionEnum::BACKOFFICE_REPORTS_VIEW->value))->toBeTrue();
});

it('back office cannot access borrower permissions', function (): void {
    expect($this->backOffice->can(PermissionEnum::BORROWER_LOANS_REQUEST->value))->toBeFalse();
});

it('back office cannot access lender permissions', function (): void {
    expect($this->backOffice->can(PermissionEnum::LENDER_WALLET_MANAGE->value))->toBeFalse();
});

it('back office cannot access full admin permissions', function (): void {
    expect($this->backOffice->can(PermissionEnum::ADMIN_USERS_DELETE->value))->toBeFalse()
        ->and($this->backOffice->can(PermissionEnum::ADMIN_SYSTEM_CONFIGURE->value))->toBeFalse();
});
