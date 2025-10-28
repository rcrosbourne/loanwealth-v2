<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;

beforeEach(function (): void {
    $this->backOffice = User::factory()->backOffice()->create();
    $this->backOffice->assignRole(RoleEnum::BackOffice->value);
});

it('back office has correct permissions', function (): void {
    expect($this->backOffice->can(PermissionEnum::BackofficeUsersApprove->value))->toBeTrue()
        ->and($this->backOffice->can(PermissionEnum::BackofficeUsersBlock->value))->toBeTrue()
        ->and($this->backOffice->can(PermissionEnum::BackofficeLoansReview->value))->toBeTrue()
        ->and($this->backOffice->can(PermissionEnum::BackofficeReportsView->value))->toBeTrue();
});

it('back office cannot access borrower permissions', function (): void {
    expect($this->backOffice->can(PermissionEnum::BorrowerLoansRequest->value))->toBeFalse();
});

it('back office cannot access lender permissions', function (): void {
    expect($this->backOffice->can(PermissionEnum::LenderWalletManage->value))->toBeFalse();
});

it('back office cannot access full admin permissions', function (): void {
    expect($this->backOffice->can(PermissionEnum::AdminUsersDelete->value))->toBeFalse()
        ->and($this->backOffice->can(PermissionEnum::AdminSystemConfigure->value))->toBeFalse();
});
