<?php

declare(strict_types=1);

use App\Actions\CreateUser;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

it('may create a user', function (): void {
    Event::fake([Registered::class]);

    $action = app(CreateUser::class);

    $user = $action->handle([
        'name' => 'Test User',
        'email' => 'example@email.com',
        'type' => UserType::BORROWER,
        'status' => UserStatus::ACTIVE,
    ], 'password');

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('Test User')
        ->and($user->email)->toBe('example@email.com')
        ->and($user->type)->toBe(UserType::BORROWER)
        ->and($user->status)->toBe(UserStatus::ACTIVE)
        ->and($user->password)->not->toBe('password');

    Event::assertDispatched(Registered::class);
});
