<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
final class UserFactory extends Factory
{
    private static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'type' => UserType::BORROWER,
            'status' => UserStatus::ACTIVE,
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => Str::random(10),
            'two_factor_recovery_codes' => Str::random(10),
            'two_factor_confirmed_at' => now(),
        ];
    }

    public function unverified(): self
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    public function withoutTwoFactor(): self
    {
        return $this->state(fn (array $attributes): array => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function borrower(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => UserType::BORROWER,
        ]);
    }

    public function lender(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => UserType::LENDER,
        ]);
    }

    public function backOffice(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => UserType::BACK_OFFICE,
        ]);
    }

    public function superAdmin(): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => UserType::SUPER_ADMIN,
        ]);
    }

    public function suspended(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::SUSPENDED,
        ]);
    }

    public function blocked(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::BLOCKED,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => UserStatus::INACTIVE,
        ]);
    }
}
