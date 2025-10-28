<?php

declare(strict_types=1);

use App\Enums\Role as RoleEnum;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('roles are seeded from migration', function (): void {
    expect(Role::query()->count())->toBe(4);

    foreach (RoleEnum::cases() as $roleEnum) {
        $role = Role::findByName($roleEnum->value);
        expect($role)->not->toBeNull()
            ->and($role->name)->toBe($roleEnum->value);
    }
});

it('prevents duplicate role creation', function (): void {
    expect(fn () => Role::create(['name' => RoleEnum::BORROWER->value]))
        ->toThrow(Exception::class);
});

it('role names match enum values', function (): void {
    $role = Role::findByName(RoleEnum::LENDER->value);

    expect($role->name)->toBe('lender')
        ->and($role->name)->toBe(RoleEnum::LENDER->value);
});
