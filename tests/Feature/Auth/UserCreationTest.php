<?php

declare(strict_types=1);

use App\Enums\UserType;
use App\Models\User;

it('can create user with borrower type', function (): void {
    $user = User::factory()->create([
        'type' => UserType::BORROWER,
    ]);

    expect($user->type)->toBeInstanceOf(UserType::class)
        ->and($user->type->value)->toBe('borrower');
});

it('can create users for all types', function ($type): void {
    $user = User::factory()->create(['type' => $type]);

    expect($user->type)->toBe($type);
})->with([
    UserType::BORROWER,
    UserType::LENDER,
    UserType::BACK_OFFICE,
    UserType::SUPER_ADMIN,
]);

it('user has default active status', function (): void {
    $user = User::factory()->create();

    expect($user->status->isActive())->toBeTrue();
});
