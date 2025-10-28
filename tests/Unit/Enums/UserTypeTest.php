<?php

declare(strict_types=1);

use App\Enums\UserType;

it('has correct user type values', function (): void {
    expect(UserType::cases())->toHaveCount(4)
        ->and(UserType::Borrower->value)->toBe('borrower')
        ->and(UserType::Lender->value)->toBe('lender')
        ->and(UserType::BackOffice->value)->toBe('back_office')
        ->and(UserType::SuperAdmin->value)->toBe('super_admin');
});

it('can get user type label', function (): void {
    expect(UserType::Borrower->label())->toBe('Borrower')
        ->and(UserType::Lender->label())->toBe('Lender')
        ->and(UserType::BackOffice->label())->toBe('Back Office')
        ->and(UserType::SuperAdmin->label())->toBe('Super Admin');
});
