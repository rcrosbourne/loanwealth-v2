<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;

beforeEach(function (): void {
    $this->lender = User::factory()->lender()->create();
    $this->lender->assignRole(RoleEnum::Lender->value);
});

it('lender has correct permissions', function (): void {
    expect($this->lender->can(PermissionEnum::LenderWalletManage->value))->toBeTrue()
        ->and($this->lender->can(PermissionEnum::LenderLoansBid->value))->toBeTrue()
        ->and($this->lender->can(PermissionEnum::LenderPortfolioView->value))->toBeTrue()
        ->and($this->lender->can(PermissionEnum::LenderAutoinvestManage->value))->toBeTrue();
});

it('lender cannot access borrower permissions', function (): void {
    expect($this->lender->can(PermissionEnum::BorrowerLoansRequest->value))->toBeFalse()
        ->and($this->lender->can(PermissionEnum::BorrowerDocumentsUpload->value))->toBeFalse();
});

it('lender cannot access back office permissions', function (): void {
    expect($this->lender->can(PermissionEnum::BackofficeUsersApprove->value))->toBeFalse();
});

it('lender cannot access admin permissions', function (): void {
    expect($this->lender->can(PermissionEnum::AdminUsersDelete->value))->toBeFalse();
});
