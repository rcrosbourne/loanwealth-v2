<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;

it('has all borrower permissions', function (): void {
    expect(Permission::BorrowerLoansRequest->value)->toBe('borrower.loans.request')
        ->and(Permission::BorrowerLoansView->value)->toBe('borrower.loans.view')
        ->and(Permission::BorrowerProfileManage->value)->toBe('borrower.profile.manage')
        ->and(Permission::BorrowerDocumentsUpload->value)->toBe('borrower.documents.upload');
});

it('has all lender permissions', function (): void {
    expect(Permission::LenderWalletManage->value)->toBe('lender.wallet.manage')
        ->and(Permission::LenderLoansBid->value)->toBe('lender.loans.bid')
        ->and(Permission::LenderPortfolioView->value)->toBe('lender.portfolio.view')
        ->and(Permission::LenderAutoinvestManage->value)->toBe('lender.autoinvest.manage');
});

it('has all back office permissions', function (): void {
    expect(Permission::BackOfficeUsersApprove->value)->toBe('backoffice.users.approve')
        ->and(Permission::BackOfficeUsersBlock->value)->toBe('backoffice.users.block')
        ->and(Permission::BackOfficeLoansReview->value)->toBe('backoffice.loans.review')
        ->and(Permission::BackOfficeReportsView->value)->toBe('backoffice.reports.view');
});

it('has all admin permissions', function (): void {
    expect(Permission::AdminUsersDelete->value)->toBe('admin.users.delete')
        ->and(Permission::AdminSystemConfigure->value)->toBe('admin.system.configure')
        ->and(Permission::AdminReportsFull->value)->toBe('admin.reports.full');
});

it('can get permissions by role', function (): void {
    $borrowerPerms = Permission::forRole(Role::Borrower);

    expect($borrowerPerms)->toBeArray()
        ->toHaveCount(4)
        ->and($borrowerPerms[0])->toBe('borrower.loans.request');
});

it('can get all permission values', function (): void {
    $values = Permission::values();

    expect($values)->toBeArray()
        ->toHaveCount(count(Permission::cases()));
});

it('can get permission group', function (): void {
    expect(Permission::BorrowerLoansRequest->group())->toBe('borrower')
        ->and(Permission::LenderWalletManage->group())->toBe('lender')
        ->and(Permission::BackOfficeUsersApprove->group())->toBe('backoffice')
        ->and(Permission::AdminUsersDelete->group())->toBe('admin');
});

it('can get permission action', function (): void {
    expect(Permission::BorrowerLoansRequest->action())->toBe('loans.request')
        ->and(Permission::LenderWalletManage->action())->toBe('wallet.manage')
        ->and(Permission::BackOfficeUsersApprove->action())->toBe('users.approve')
        ->and(Permission::AdminUsersDelete->action())->toBe('users.delete');
});
