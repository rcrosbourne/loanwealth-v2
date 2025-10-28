<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;

beforeEach(function (): void {
    $this->borrower = User::factory()->borrower()->create();
    $this->borrower->assignRole(RoleEnum::BORROWER->value);
});

it('borrower has correct permissions', function (): void {
    expect($this->borrower->can(PermissionEnum::BORROWER_LOANS_REQUEST->value))->toBeTrue()
        ->and($this->borrower->can(PermissionEnum::BORROWER_LOANS_VIEW->value))->toBeTrue()
        ->and($this->borrower->can(PermissionEnum::BORROWER_PROFILE_MANAGE->value))->toBeTrue()
        ->and($this->borrower->can(PermissionEnum::BORROWER_DOCUMENTS_UPLOAD->value))->toBeTrue();
});

it('borrower cannot access lender permissions', function (): void {
    expect($this->borrower->can(PermissionEnum::LENDER_WALLET_MANAGE->value))->toBeFalse()
        ->and($this->borrower->can(PermissionEnum::LENDER_LOANS_BID->value))->toBeFalse();
});

it('borrower cannot access back office permissions', function (): void {
    expect($this->borrower->can(PermissionEnum::BACKOFFICE_USERS_APPROVE->value))->toBeFalse()
        ->and($this->borrower->can(PermissionEnum::BACKOFFICE_LOANS_REVIEW->value))->toBeFalse();
});

it('borrower cannot access admin permissions', function (): void {
    expect($this->borrower->can(PermissionEnum::ADMIN_USERS_DELETE->value))->toBeFalse()
        ->and($this->borrower->can(PermissionEnum::ADMIN_SYSTEM_CONFIGURE->value))->toBeFalse();
});
