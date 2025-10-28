<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;

it('has all borrower permissions', function (): void {
    expect(Permission::BORROWER_LOANS_REQUEST->value)->toBe('borrower.loans.request')
        ->and(Permission::BORROWER_LOANS_VIEW->value)->toBe('borrower.loans.view')
        ->and(Permission::BORROWER_PROFILE_MANAGE->value)->toBe('borrower.profile.manage')
        ->and(Permission::BORROWER_DOCUMENTS_UPLOAD->value)->toBe('borrower.documents.upload');
});

it('has all lender permissions', function (): void {
    expect(Permission::LENDER_WALLET_MANAGE->value)->toBe('lender.wallet.manage')
        ->and(Permission::LENDER_LOANS_BID->value)->toBe('lender.loans.bid')
        ->and(Permission::LENDER_PORTFOLIO_VIEW->value)->toBe('lender.portfolio.view')
        ->and(Permission::LENDER_AUTOINVEST_MANAGE->value)->toBe('lender.autoinvest.manage');
});

it('has all back office permissions', function (): void {
    expect(Permission::BACKOFFICE_USERS_APPROVE->value)->toBe('backoffice.users.approve')
        ->and(Permission::BACKOFFICE_USERS_BLOCK->value)->toBe('backoffice.users.block')
        ->and(Permission::BACKOFFICE_LOANS_REVIEW->value)->toBe('backoffice.loans.review')
        ->and(Permission::BACKOFFICE_REPORTS_VIEW->value)->toBe('backoffice.reports.view');
});

it('has all admin permissions', function (): void {
    expect(Permission::ADMIN_USERS_DELETE->value)->toBe('admin.users.delete')
        ->and(Permission::ADMIN_SYSTEM_CONFIGURE->value)->toBe('admin.system.configure')
        ->and(Permission::ADMIN_REPORTS_FULL->value)->toBe('admin.reports.full');
});

it('can get permissions by role', function (): void {
    $borrowerPerms = Permission::forRole(Role::BORROWER);

    expect($borrowerPerms)->toBeArray()
        ->toHaveCount(4)
        ->and($borrowerPerms[0])->toBe('borrower.loans.request');
});

it('can get all permission values', function (): void {
    $values = Permission::values();

    expect($values)->toBeArray()
        ->toHaveCount(15);
});

it('can get permission group', function (): void {
    expect(Permission::BORROWER_LOANS_REQUEST->group())->toBe('borrower')
        ->and(Permission::LENDER_WALLET_MANAGE->group())->toBe('lender')
        ->and(Permission::BACKOFFICE_USERS_APPROVE->group())->toBe('backoffice')
        ->and(Permission::ADMIN_USERS_DELETE->group())->toBe('admin');
});

it('can get permission action', function (): void {
    expect(Permission::BORROWER_LOANS_REQUEST->action())->toBe('loans.request')
        ->and(Permission::LENDER_WALLET_MANAGE->action())->toBe('wallet.manage')
        ->and(Permission::BACKOFFICE_USERS_APPROVE->action())->toBe('users.approve')
        ->and(Permission::ADMIN_USERS_DELETE->action())->toBe('users.delete');
});
