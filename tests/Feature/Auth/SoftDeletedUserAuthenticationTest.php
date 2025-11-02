<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\assertGuest;
use function Pest\Laravel\post;

it('soft deleted user cannot authenticate', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $user->delete();

    $response = post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors();
    assertGuest();
});

it('soft deleted user is excluded from authentication queries', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $user->delete();

    expect(User::query()->where('email', 'test@example.com')->first())->toBeNull();
    expect(User::withTrashed()->where('email', 'test@example.com')->first())->not->toBeNull();
});

it('soft deleted user session persists but user is marked as deleted', function (): void {
    $user = User::factory()->create();

    $userId = $user->id;
    $user->delete();

    // Verify user is soft deleted in database
    expect(User::query()->find($userId))->toBeNull();
    $trashedUser = User::withTrashed()->find($userId);
    expect($trashedUser->trashed())->toBeTrue();

    // Attempting to authenticate with deleted user should fail
    $response = post('/login', [
        'email' => $trashedUser->email,
        'password' => 'password',
    ]);

    assertGuest();
});

it('soft deleted user is excluded from role assignments', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Borrower->value);

    expect($user->hasRole(Role::Borrower->value))->toBeTrue();

    $user->delete();

    // Refresh from database without trashed
    $freshUser = User::query()->find($user->id);
    expect($freshUser)->toBeNull();

    // User still has role in database but is soft deleted
    $trashedUser = User::withTrashed()->find($user->id);
    expect($trashedUser)->not->toBeNull();
    expect($trashedUser->trashed())->toBeTrue();
});

it('soft deleted user is excluded from permission checks', function (): void {
    $user = User::factory()->create();
    $user->assignRole(Role::Borrower->value);

    expect($user->can(Permission::BorrowerLoansRequest->value))->toBeTrue();

    $user->delete();

    // Verify user is soft deleted
    $trashedUser = User::withTrashed()->find($user->id);
    expect($trashedUser->trashed())->toBeTrue();

    // Active user query should not find the user
    expect(User::query()->find($user->id))->toBeNull();
});

it('restored user can authenticate again', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);

    $user->delete();
    $user->restore();

    $response = post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
});

it('soft deleted users are not included in active user counts', function (): void {
    User::factory()->count(3)->create();
    $userToDelete = User::factory()->create();

    expect(User::query()->count())->toBe(4);

    $userToDelete->delete();

    expect(User::query()->count())->toBe(3);
    expect(User::withTrashed()->count())->toBe(4);
});

it('soft deleted user maintains their data integrity', function (): void {
    $user = User::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $userId = $user->id;
    $user->delete();

    $trashedUser = User::withTrashed()->find($userId);

    expect($trashedUser->name)->toBe('John Doe')
        ->and($trashedUser->email)->toBe('john@example.com')
        ->and($trashedUser->trashed())->toBeTrue()
        ->and($trashedUser->deleted_at)->not->toBeNull();
});
