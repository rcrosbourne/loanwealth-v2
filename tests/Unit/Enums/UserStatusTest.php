<?php

declare(strict_types=1);

use App\Enums\UserStatus;

it('has correct user status values', function (): void {
    expect(UserStatus::cases())->toHaveCount(4)
        ->and(UserStatus::ACTIVE->value)->toBe('active')
        ->and(UserStatus::INACTIVE->value)->toBe('inactive')
        ->and(UserStatus::SUSPENDED->value)->toBe('suspended')
        ->and(UserStatus::BLOCKED->value)->toBe('blocked');
});

it('has correct status labels', function (): void {
    expect(UserStatus::ACTIVE->label())->toBe('Active')
        ->and(UserStatus::INACTIVE->label())->toBe('Inactive')
        ->and(UserStatus::SUSPENDED->label())->toBe('Suspended')
        ->and(UserStatus::BLOCKED->label())->toBe('Blocked');
});

it('can check if status is active', function (): void {
    expect(UserStatus::ACTIVE->isActive())->toBeTrue()
        ->and(UserStatus::INACTIVE->isActive())->toBeFalse()
        ->and(UserStatus::SUSPENDED->isActive())->toBeFalse()
        ->and(UserStatus::BLOCKED->isActive())->toBeFalse();
});
