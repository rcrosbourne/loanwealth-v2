<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use SensitiveParameter;

final readonly class CreateUser
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes, #[SensitiveParameter] string $password): User
    {
        // Extract type and status as they are not mass-assignable for security
        // Default to Borrower type and Active status if not provided
        $type = isset($attributes['type']) && $attributes['type'] instanceof UserType
            ? $attributes['type']
            : UserType::Borrower;

        $status = isset($attributes['status']) && $attributes['status'] instanceof UserStatus
            ? $attributes['status']
            : UserStatus::Active;

        unset($attributes['type'], $attributes['status']);

        // Create user with fillable attributes
        $user = new User([
            ...$attributes,
            'password' => Hash::make($password),
        ]);

        // Set type and status explicitly (not mass-assignable for security)
        $user->type = $type;
        $user->status = $status;

        $user->save();

        event(new Registered($user));

        return $user;
    }
}
