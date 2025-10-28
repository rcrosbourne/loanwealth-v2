<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;

beforeEach(function (): void {
    $this->lender = User::factory()->lender()->create();
    $this->lender->assignRole(RoleEnum::LENDER->value);
});

it('lender has correct permissions', function (): void {
    expect($this->lender->can(PermissionEnum::LENDER_WALLET_MANAGE->value))->toBeTrue()
        ->and($this->lender->can(PermissionEnum::LENDER_LOANS_BID->value))->toBeTrue()
        ->and($this->lender->can(PermissionEnum::LENDER_PORTFOLIO_VIEW->value))->toBeTrue()
        ->and($this->lender->can(PermissionEnum::LENDER_AUTOINVEST_MANAGE->value))->toBeTrue();
});

it('lender cannot access borrower permissions', function (): void {
    expect($this->lender->can(PermissionEnum::BORROWER_LOANS_REQUEST->value))->toBeFalse()
        ->and($this->lender->can(PermissionEnum::BORROWER_DOCUMENTS_UPLOAD->value))->toBeFalse();
});

it('lender cannot access back office permissions', function (): void {
    expect($this->lender->can(PermissionEnum::BACKOFFICE_USERS_APPROVE->value))->toBeFalse();
});

it('lender cannot access admin permissions', function (): void {
    expect($this->lender->can(PermissionEnum::ADMIN_USERS_DELETE->value))->toBeFalse();
});
