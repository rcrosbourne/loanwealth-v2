<?php

declare(strict_types=1);

use App\Enums\UserStatus;

it('has correct user status values', function (): void {
    expect(UserStatus::cases())->toHaveCount(4)
        ->and(UserStatus::Active->value)->toBe('active')
        ->and(UserStatus::Inactive->value)->toBe('inactive')
        ->and(UserStatus::Suspended->value)->toBe('suspended')
        ->and(UserStatus::Blocked->value)->toBe('blocked');
});

it('has correct status labels', function (): void {
    expect(UserStatus::Active->label())->toBe('Active')
        ->and(UserStatus::Inactive->label())->toBe('Inactive')
        ->and(UserStatus::Suspended->label())->toBe('Suspended')
        ->and(UserStatus::Blocked->label())->toBe('Blocked');
});

it('can check if status is active', function (): void {
    expect(UserStatus::Active->isActive())->toBeTrue()
        ->and(UserStatus::Inactive->isActive())->toBeFalse()
        ->and(UserStatus::Suspended->isActive())->toBeFalse()
        ->and(UserStatus::Blocked->isActive())->toBeFalse();
});
