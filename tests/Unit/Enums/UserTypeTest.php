<?php

declare(strict_types=1);

use App\Enums\UserType;

it('has correct user type values', function (): void {
    expect(UserType::cases())->toHaveCount(4)
        ->and(UserType::BORROWER->value)->toBe('borrower')
        ->and(UserType::LENDER->value)->toBe('lender')
        ->and(UserType::BACK_OFFICE->value)->toBe('back_office')
        ->and(UserType::SUPER_ADMIN->value)->toBe('super_admin');
});

it('can get user type label', function (): void {
    expect(UserType::BORROWER->label())->toBe('Borrower')
        ->and(UserType::LENDER->label())->toBe('Lender')
        ->and(UserType::BACK_OFFICE->label())->toBe('Back Office')
        ->and(UserType::SUPER_ADMIN->label())->toBe('Super Admin');
});
