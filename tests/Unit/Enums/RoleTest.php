<?php

declare(strict_types=1);

use App\Enums\Role;

it('has correct role values', function (): void {
    expect(Role::cases())->toHaveCount(4)
        ->and(Role::BORROWER->value)->toBe('borrower')
        ->and(Role::LENDER->value)->toBe('lender')
        ->and(Role::BACK_OFFICE->value)->toBe('back_office')
        ->and(Role::SUPER_ADMIN->value)->toBe('super_admin');
});

it('can get role label', function (): void {
    expect(Role::BORROWER->label())->toBe('Borrower')
        ->and(Role::LENDER->label())->toBe('Lender')
        ->and(Role::BACK_OFFICE->label())->toBe('Back Office')
        ->and(Role::SUPER_ADMIN->label())->toBe('Super Admin');
});

it('can get all role values as array', function (): void {
    $values = Role::values();

    expect($values)->toBeArray()
        ->toHaveCount(4)
        ->toContain('borrower', 'lender', 'back_office', 'super_admin');
});

it('can get all role names as array', function (): void {
    $names = Role::names();

    expect($names)->toBeArray()
        ->toHaveCount(4)
        ->toContain('BORROWER', 'LENDER', 'BACK_OFFICE', 'SUPER_ADMIN');
});
