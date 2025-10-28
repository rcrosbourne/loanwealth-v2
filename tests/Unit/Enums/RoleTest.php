<?php

declare(strict_types=1);

use App\Enums\Role;

it('has correct role values', function (): void {
    expect(Role::cases())->toHaveCount(4)
        ->and(Role::Borrower->value)->toBe('borrower')
        ->and(Role::Lender->value)->toBe('lender')
        ->and(Role::BackOffice->value)->toBe('back_office')
        ->and(Role::SuperAdmin->value)->toBe('super_admin');
});

it('can get role label', function (): void {
    expect(Role::Borrower->label())->toBe('Borrower')
        ->and(Role::Lender->label())->toBe('Lender')
        ->and(Role::BackOffice->label())->toBe('Back Office')
        ->and(Role::SuperAdmin->label())->toBe('Super Admin');
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
        ->toContain('Borrower', 'Lender', 'BackOffice', 'SuperAdmin');
});
