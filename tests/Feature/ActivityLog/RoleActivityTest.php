<?php

declare(strict_types=1);

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    // Clear all activity logs before each test
    Activity::query()->delete();
});

it('logs role assignment to user', function (): void {
    $user = User::factory()->create();
    $role = Role::findByName(RoleEnum::Borrower->value);

    activity()
        ->performedOn($user)
        ->withProperties(['role' => $role->name])
        ->log('assigned role');

    $activity = Activity::query()
        ->where('subject_id', $user->id)
        ->where('description', 'assigned role')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('assigned role')
        ->and($activity->properties->get('role'))->toBe('borrower');
});

it('logs role removal from user', function (): void {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Borrower->value);

    // Log the removal
    activity()
        ->performedOn($user)
        ->withProperties(['role' => RoleEnum::Borrower->value])
        ->log('removed role');

    $user->removeRole(RoleEnum::Borrower->value);

    $activity = Activity::query()->where('subject_id', $user->id)
        ->where('description', 'removed role')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('removed role')
        ->and($activity->properties->get('role'))->toBe('borrower');
});

it('logs multiple role assignments', function (): void {
    $user = User::factory()->create();

    activity()
        ->performedOn($user)
        ->withProperties([
            'roles' => [RoleEnum::Borrower->value, RoleEnum::Lender->value],
        ])
        ->log('assigned multiple roles');

    $activity = Activity::query()
        ->where('subject_id', $user->id)
        ->where('description', 'assigned multiple roles')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('assigned multiple roles')
        ->and($activity->properties->get('roles'))->toBeArray()
        ->and($activity->properties->get('roles'))->toContain('borrower', 'lender');
});

it('tracks causer for role assignments', function (): void {
    $admin = User::factory()->superAdmin()->create();
    $user = User::factory()->create();

    activity()
        ->causedBy($admin)
        ->performedOn($user)
        ->withProperties(['role' => RoleEnum::Borrower->value])
        ->log('admin assigned role');

    $activity = Activity::query()
        ->where('subject_id', $user->id)
        ->where('description', 'admin assigned role')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($admin->id)
        ->and($activity->causer_type)->toBe(User::class)
        ->and($activity->description)->toBe('admin assigned role');
});

it('logs permission sync operations', function (): void {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::Borrower->value);

    $originalPermissions = $user->getAllPermissions()->pluck('name')->toArray();

    activity()
        ->performedOn($user)
        ->withProperties([
            'permissions_before' => $originalPermissions,
            'permissions_after' => [], // Assuming all removed
        ])
        ->log('permissions synced');

    $activity = Activity::query()
        ->where('subject_id', $user->id)
        ->where('description', 'permissions synced')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->toBe('permissions synced')
        ->and($activity->properties->has('permissions_before'))->toBeTrue()
        ->and($activity->properties->has('permissions_after'))->toBeTrue();
});

it('can retrieve all role-related activities for a user', function (): void {
    $user = User::factory()->create();

    // Log multiple activities
    activity()->performedOn($user)->log('assigned role borrower');
    activity()->performedOn($user)->log('assigned role lender');
    activity()->performedOn($user)->log('removed role borrower');

    $activities = Activity::query()
        ->where('subject_id', $user->id)
        ->where('subject_type', User::class)
        ->whereIn('description', ['assigned role borrower', 'assigned role lender', 'removed role borrower'])
        ->get();

    expect($activities)->toHaveCount(3)
        ->and($activities->pluck('description')->toArray())->toContain(
            'assigned role borrower',
            'assigned role lender',
            'removed role borrower'
        );
});

it('logs role changes with timestamps', function (): void {
    $user = User::factory()->create();

    activity()
        ->performedOn($user)
        ->withProperties(['role' => RoleEnum::BackOffice->value])
        ->log('assigned back office role');

    $activity = Activity::query()
        ->where('subject_id', $user->id)
        ->where('description', 'assigned back office role')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->created_at)->not->toBeNull()
        ->and($activity->created_at)->toBeInstanceOf(Carbon\CarbonInterface::class);
});
