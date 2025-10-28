<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;

beforeEach(function (): void {
    $this->borrower = User::factory()->borrower()->create();
    $this->borrower->assignRole(RoleEnum::Borrower->value);
});

it('borrower has correct permissions', function (): void {
    expect($this->borrower->can(PermissionEnum::BorrowerLoansRequest->value))->toBeTrue()
        ->and($this->borrower->can(PermissionEnum::BorrowerLoansView->value))->toBeTrue()
        ->and($this->borrower->can(PermissionEnum::BorrowerProfileManage->value))->toBeTrue()
        ->and($this->borrower->can(PermissionEnum::BorrowerDocumentsUpload->value))->toBeTrue();
});

it('borrower cannot access lender permissions', function (): void {
    expect($this->borrower->can(PermissionEnum::LenderWalletManage->value))->toBeFalse()
        ->and($this->borrower->can(PermissionEnum::LenderLoansBid->value))->toBeFalse();
});

it('borrower cannot access back office permissions', function (): void {
    expect($this->borrower->can(PermissionEnum::BackofficeUsersApprove->value))->toBeFalse()
        ->and($this->borrower->can(PermissionEnum::BackofficeLoansReview->value))->toBeFalse();
});

it('borrower cannot access admin permissions', function (): void {
    expect($this->borrower->can(PermissionEnum::AdminUsersDelete->value))->toBeFalse()
        ->and($this->borrower->can(PermissionEnum::AdminSystemConfigure->value))->toBeFalse();
});
