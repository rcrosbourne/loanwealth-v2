# Phase 1: Foundation with TDD - Packages, Permissions & Activity Log

## Test-Driven Development Approach
Write tests first → Run tests (they fail) → Implement code → Tests pass → Refactor

## Implementation Steps

### 1. Package Installation (5-10 minutes)

```bash
composer require laravel/pennant
composer require spatie/laravel-permission
composer require spatie/laravel-data
composer require moneyphp/money
composer require spatie/laravel-activitylog
```

**Post-installation:**
- Publish configurations and migrations for each package
- Run migrations

```bash
php artisan vendor:publish --provider="Laravel\Pennant\PennantServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
php artisan migrate
```

---

### 2. TDD Cycle 1: User Types & Enums (20 minutes)

#### Tests First:

**`tests/Unit/Enums/UserTypeTest.php`**
```php
it('has correct user type values', function () {
    expect(UserType::cases())->toHaveCount(4)
        ->and(UserType::BORROWER->value)->toBe('borrower')
        ->and(UserType::LENDER->value)->toBe('lender')
        ->and(UserType::BACK_OFFICE->value)->toBe('back_office')
        ->and(UserType::GLOBAL_ADMIN->value)->toBe('global_admin');
});

it('can get user type label', function () {
    expect(UserType::BORROWER->label())->toBe('Borrower')
        ->and(UserType::LENDER->label())->toBe('Lender');
});
```

**`tests/Unit/Enums/UserStatusTest.php`**
```php
it('has correct user status values', function () {
    expect(UserStatus::cases())->toHaveCount(4)
        ->and(UserStatus::ACTIVE->value)->toBe('active')
        ->and(UserStatus::INACTIVE->value)->toBe('inactive')
        ->and(UserStatus::SUSPENDED->value)->toBe('suspended')
        ->and(UserStatus::BLOCKED->value)->toBe('blocked');
});
```

**`tests/Feature/Auth/UserCreationTest.php`**
```php
it('can create user with borrower type', function () {
    $user = User::factory()->create([
        'type' => UserType::BORROWER,
    ]);

    expect($user->type)->toBeInstanceOf(UserType::class)
        ->and($user->type->value)->toBe('borrower');
});

it('can create users for all types', function ($type) {
    $user = User::factory()->create(['type' => $type]);

    expect($user->type)->toBe($type);
})->with([
    UserType::BORROWER,
    UserType::LENDER,
    UserType::BACK_OFFICE,
    UserType::GLOBAL_ADMIN,
]);
```

#### Implementation:

**`app/Enums/UserType.php`**
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum UserType: string
{
    case BORROWER = 'borrower';
    case LENDER = 'lender';
    case BACK_OFFICE = 'back_office';
    case GLOBAL_ADMIN = 'global_admin';

    public function label(): string
    {
        return match($this) {
            self::BORROWER => 'Borrower',
            self::LENDER => 'Lender',
            self::BACK_OFFICE => 'Back Office',
            self::GLOBAL_ADMIN => 'Global Admin',
        };
    }
}
```

**`app/Enums/UserStatus.php`**
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case BLOCKED = 'blocked';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::BLOCKED => 'Blocked',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
}
```

**Update `app/Models/User.php`**
```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'type' => UserType::class,
        'status' => UserStatus::class,
    ];
}
```

---

### 3. TDD Cycle 2: Roles & Permissions (30-40 minutes)

#### Tests First:

**`tests/Feature/Permissions/RoleCreationTest.php`**
```php
it('can create all required roles', function () {
    $roles = ['borrower', 'lender', 'back_office', 'global_admin'];

    foreach ($roles as $roleName) {
        $role = Role::create(['name' => $roleName]);
        expect($role->exists)->toBeTrue();
    }

    expect(Role::count())->toBe(4);
});

it('prevents duplicate role creation', function () {
    Role::create(['name' => 'borrower']);

    expect(fn() => Role::create(['name' => 'borrower']))
        ->toThrow(\Exception::class);
});
```

**`tests/Feature/Permissions/PermissionAssignmentTest.php`**
```php
it('can assign permissions to role', function () {
    $role = Role::create(['name' => 'borrower']);
    $permission = Permission::create(['name' => 'borrower.loans.request']);

    $role->givePermissionTo($permission);

    expect($role->hasPermissionTo('borrower.loans.request'))->toBeTrue();
});

it('can assign user to role', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'borrower']);

    $user->assignRole($role);

    expect($user->hasRole('borrower'))->toBeTrue();
});
```

**`tests/Feature/Permissions/BorrowerPermissionsTest.php`**
```php
it('borrower has correct permissions', function () {
    $role = Role::findByName('borrower');

    expect($role->hasPermissionTo('borrower.loans.request'))->toBeTrue()
        ->and($role->hasPermissionTo('borrower.loans.view'))->toBeTrue()
        ->and($role->hasPermissionTo('borrower.profile.manage'))->toBeTrue()
        ->and($role->hasPermissionTo('borrower.documents.upload'))->toBeTrue();
});

it('borrower cannot access lender permissions', function () {
    $user = User::factory()->create();
    $user->assignRole('borrower');

    expect($user->can('lender.wallet.manage'))->toBeFalse()
        ->and($user->can('lender.loans.bid'))->toBeFalse();
});
```

**`tests/Feature/Permissions/LenderPermissionsTest.php`**
```php
it('lender has correct permissions', function () {
    $role = Role::findByName('lender');

    expect($role->hasPermissionTo('lender.wallet.manage'))->toBeTrue()
        ->and($role->hasPermissionTo('lender.loans.bid'))->toBeTrue()
        ->and($role->hasPermissionTo('lender.portfolio.view'))->toBeTrue()
        ->and($role->hasPermissionTo('lender.autoinvest.manage'))->toBeTrue();
});
```

**`tests/Feature/Permissions/BackOfficePermissionsTest.php`**
```php
it('back office has correct permissions', function () {
    $role = Role::findByName('back_office');

    expect($role->hasPermissionTo('backoffice.users.approve'))->toBeTrue()
        ->and($role->hasPermissionTo('backoffice.users.block'))->toBeTrue()
        ->and($role->hasPermissionTo('backoffice.loans.review'))->toBeTrue()
        ->and($role->hasPermissionTo('backoffice.reports.view'))->toBeTrue();
});
```

**`tests/Feature/Permissions/AdminPermissionsTest.php`**
```php
it('admin has all permissions', function () {
    $role = Role::findByName('global_admin');

    expect($role->hasPermissionTo('admin.users.delete'))->toBeTrue()
        ->and($role->hasPermissionTo('admin.system.configure'))->toBeTrue()
        ->and($role->hasPermissionTo('admin.reports.full'))->toBeTrue();
});

it('admin can perform all actions', function () {
    $user = User::factory()->create();
    $user->assignRole('global_admin');

    $permissions = Permission::all();

    foreach ($permissions as $permission) {
        expect($user->can($permission->name))->toBeTrue();
    }
});
```

#### Implementation:

**`database/seeders/RolePermissionSeeder.php`**
```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions structure
        $permissions = [
            // Borrower permissions
            'borrower.loans.request',
            'borrower.loans.view',
            'borrower.profile.manage',
            'borrower.documents.upload',

            // Lender permissions
            'lender.wallet.manage',
            'lender.loans.bid',
            'lender.portfolio.view',
            'lender.autoinvest.manage',

            // Back Office permissions
            'backoffice.users.approve',
            'backoffice.users.block',
            'backoffice.loans.review',
            'backoffice.reports.view',

            // Admin permissions
            'admin.users.delete',
            'admin.system.configure',
            'admin.reports.full',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $borrower = Role::create(['name' => 'borrower']);
        $borrower->givePermissionTo([
            'borrower.loans.request',
            'borrower.loans.view',
            'borrower.profile.manage',
            'borrower.documents.upload',
        ]);

        $lender = Role::create(['name' => 'lender']);
        $lender->givePermissionTo([
            'lender.wallet.manage',
            'lender.loans.bid',
            'lender.portfolio.view',
            'lender.autoinvest.manage',
        ]);

        $backOffice = Role::create(['name' => 'back_office']);
        $backOffice->givePermissionTo([
            'backoffice.users.approve',
            'backoffice.users.block',
            'backoffice.loans.review',
            'backoffice.reports.view',
        ]);

        $admin = Role::create(['name' => 'global_admin']);
        $admin->givePermissionTo(Permission::all());
    }
}
```

**Create middleware `app/Http/Middleware/RoleMiddleware.php`**
```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!$request->user()) {
            abort(401);
        }

        if (!$request->user()->hasAnyRole($roles)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
```

---

### 4. TDD Cycle 3: Activity Logging (20-30 minutes)

#### Tests First:

**`tests/Feature/ActivityLog/UserActivityTest.php`**
```php
use Spatie\Activitylog\Models\Activity;

it('logs user creation', function () {
    $user = User::factory()->create(['name' => 'John Doe']);

    $activity = Activity::latest()->first();

    expect($activity->description)->toBe('created')
        ->and($activity->subject_type)->toBe(User::class)
        ->and($activity->subject_id)->toBe($user->id);
});

it('logs user updates', function () {
    $user = User::factory()->create();

    $user->update(['name' => 'Jane Doe']);

    $activity = Activity::where('description', 'updated')
        ->where('subject_id', $user->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('attributes.name'))->toBe('Jane Doe');
});

it('logs user deletion', function () {
    $user = User::factory()->create();
    $userId = $user->id;

    $user->delete();

    $activity = Activity::where('description', 'deleted')
        ->where('subject_id', $userId)
        ->first();

    expect($activity)->not->toBeNull();
});
```

**`tests/Feature/ActivityLog/PermissionActivityTest.php`**
```php
it('logs permission changes', function () {
    $user = User::factory()->create();

    $user->assignRole('borrower');

    $activity = Activity::where('description', 'role_assigned')
        ->where('subject_id', $user->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->properties->get('role'))->toBe('borrower');
});

it('logs permission revocation', function () {
    $user = User::factory()->create();
    $user->assignRole('borrower');

    $user->removeRole('borrower');

    $activity = Activity::where('description', 'role_removed')
        ->where('subject_id', $user->id)
        ->first();

    expect($activity)->not->toBeNull();
});
```

**`tests/Feature/ActivityLog/CauserTrackingTest.php`**
```php
it('tracks who performed the action', function () {
    $admin = User::factory()->create();
    $admin->assignRole('global_admin');

    $this->actingAs($admin);

    $user = User::factory()->create();

    $activity = Activity::latest()->first();

    expect($activity->causer_id)->toBe($admin->id)
        ->and($activity->causer_type)->toBe(User::class);
});
```

#### Implementation:

**Update `app/Models/User.php`**
```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class User extends Authenticatable
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'type', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

**Create custom activity logger for role changes**
```php
// In User model or observer
protected static function booted(): void
{
    static::created(function ($user) {
        activity()
            ->performedOn($user)
            ->causedBy(auth()->user())
            ->log('created');
    });
}
```

---

### 5. TDD Cycle 4: Money Handling (20 minutes)

#### Tests First:

**`tests/Unit/Casts/MoneyCastTest.php`**
```php
use App\Casts\MoneyCast;
use Money\Money;
use Money\Currency;

it('converts dollars to cents when setting', function () {
    $cast = new MoneyCast();
    $value = $cast->set(null, 'amount', 100.50, []);

    expect($value)->toBe(10050); // cents
});

it('converts cents to money object when getting', function () {
    $cast = new MoneyCast();
    $money = $cast->get(null, 'amount', 10050, []);

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->getAmount())->toBe('10050')
        ->and($money->getCurrency()->getCode())->toBe('JMD');
});
```

**`tests/Feature/Money/MoneyStorageTest.php`**
```php
it('stores money as cents in database', function () {
    $loan = Loan::factory()->create([
        'amount' => 10000.00, // $10,000 JMD
    ]);

    $dbValue = DB::table('loans')
        ->where('id', $loan->id)
        ->value('amount_cents');

    expect($dbValue)->toBe(1000000); // 10,000 * 100
});

it('retrieves money as Money object', function () {
    $loan = Loan::factory()->create([
        'amount' => 5000.00,
    ]);

    expect($loan->amount)->toBeInstanceOf(Money::class)
        ->and($loan->amount->getAmount())->toBe('500000');
});
```

**`tests/Feature/Money/MoneyDisplayTest.php`**
```php
it('formats money for display', function () {
    $money = Money::JMD(125050); // $1,250.50 JMD

    expect($money->formatted())->toBe('JMD 1,250.50');
});
```

#### Implementation:

**`app/Casts/MoneyCast.php`**
```php
<?php

declare(strict_types=1);

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Money\Money;
use Money\Currency;

class MoneyCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return new Money($value, new Currency('JMD'));
    }

    public function set($model, string $key, $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return (int) $value->getAmount();
        }

        // Convert float/int dollars to cents
        return (int) ($value * 100);
    }
}
```

**Create Money helper macros**
```php
// In AppServiceProvider::boot()
Money::macro('formatted', function () {
    $formatter = new \Money\Formatter\IntlMoneyFormatter(
        new \NumberFormatter('en_JM', \NumberFormatter::CURRENCY),
        new \Money\Currencies\ISOCurrencies()
    );

    return $formatter->format($this);
});
```

---

### 6. TDD Cycle 5: Feature Flags (15 minutes)

#### Tests First:

**`tests/Feature/FeatureFlags/FeatureFlagTest.php`**
```php
use Laravel\Pennant\Feature;

it('has auto-invest feature flag', function () {
    Feature::define('auto-invest', fn () => true);

    expect(Feature::active('auto-invest'))->toBeTrue();
});

it('can toggle feature flags', function () {
    Feature::define('secondary-market', fn () => false);

    expect(Feature::active('secondary-market'))->toBeFalse();

    Feature::activate('secondary-market');

    expect(Feature::active('secondary-market'))->toBeTrue();
});
```

**`tests/Feature/FeatureFlags/UserFeatureAccessTest.php`**
```php
it('can enable feature for specific user', function () {
    $user = User::factory()->create();

    Feature::define('early-repayment', fn ($user) => $user->type === UserType::BORROWER);

    expect(Feature::for($user)->active('early-repayment'))->toBeTrue();
});

it('denies feature for non-eligible users', function () {
    $lender = User::factory()->create(['type' => UserType::LENDER]);

    Feature::define('loan-application', fn ($user) => $user->type === UserType::BORROWER);

    expect(Feature::for($lender)->active('loan-application'))->toBeFalse();
});
```

#### Implementation:

**Define features in `app/Providers/AppServiceProvider.php`**
```php
use Laravel\Pennant\Feature;

public function boot(): void
{
    Feature::define('auto-invest', fn () => config('features.auto_invest', false));
    Feature::define('secondary-market', fn () => config('features.secondary_market', false));
    Feature::define('early-repayment', fn () => config('features.early_repayment', true));
    Feature::define('two-factor-auth', fn (User $user) => $user->type === UserType::GLOBAL_ADMIN);
}
```

**Create config file `config/features.php`**
```php
<?php

return [
    'auto_invest' => env('FEATURE_AUTO_INVEST', false),
    'secondary_market' => env('FEATURE_SECONDARY_MARKET', false),
    'early_repayment' => env('FEATURE_EARLY_REPAYMENT', true),
];
```

---

### 7. User Model & Factories (20 minutes)

#### Tests First:

**`tests/Feature/Models/UserModelTest.php`**
```php
it('uses modern attribute accessors', function () {
    $user = User::factory()->create(['name' => 'john doe']);

    expect($user->name)->toBe('John Doe'); // Capitalized via accessor
});

it('has proper relationships', function () {
    $user = User::factory()->create();

    expect($user->roles())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class)
        ->and($user->permissions())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
});

it('logs activity when created', function () {
    $user = User::factory()->create();

    expect(Activity::latest()->first()->subject_id)->toBe($user->id);
});
```

#### Implementation:

**Update `app/Models/User.php`**
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserType;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, LogsActivity, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'type' => UserType::class,
            'status' => UserStatus::class,
        ];
    }

    // Modern accessor example
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ucwords($value),
            set: fn (string $value) => strtolower($value),
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'type', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

**Update `database/factories/UserFactory.php`**
```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserType;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'type' => UserType::BORROWER,
            'status' => UserStatus::ACTIVE,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function borrower(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UserType::BORROWER,
        ]);
    }

    public function lender(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UserType::LENDER,
        ]);
    }

    public function backOffice(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UserType::BACK_OFFICE,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => UserType::GLOBAL_ADMIN,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::SUSPENDED,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::BLOCKED,
        ]);
    }
}
```

---

### 8. Integration Tests (15 minutes)

#### Tests First:

**`tests/Feature/Integration/UserRegistrationFlowTest.php`**
```php
it('completes full borrower registration flow', function () {
    // Create user
    $user = User::factory()->create([
        'type' => UserType::BORROWER,
        'status' => UserStatus::ACTIVE,
    ]);

    // Assign role
    $user->assignRole('borrower');

    // Verify permissions
    expect($user->can('borrower.loans.request'))->toBeTrue()
        ->and($user->can('lender.wallet.manage'))->toBeFalse();

    // Verify activity log
    $activities = Activity::where('subject_id', $user->id)->get();
    expect($activities)->toHaveCount(2); // Creation + role assignment
});
```

**`tests/Feature/Integration/PermissionMiddlewareTest.php`**
```php
it('allows borrower to access borrower routes', function () {
    $user = User::factory()->create();
    $user->assignRole('borrower');

    $response = $this->actingAs($user)->get('/borrower/dashboard');

    $response->assertOk();
});

it('denies lender access to borrower routes', function () {
    $user = User::factory()->create();
    $user->assignRole('lender');

    $response = $this->actingAs($user)->get('/borrower/dashboard');

    $response->assertForbidden();
});
```

---

## Test Execution Strategy

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Permissions/RoleCreationTest.php

# Run with coverage
php artisan test --coverage

# TDD mode - stop on first failure
php artisan test --stop-on-failure

# Filter by test name
php artisan test --filter="can create user with borrower type"
```

### Test Execution Order

1. **Unit Tests First** - Enums, Casts, Value Objects
2. **Feature Tests** - Permissions, Roles, Activity Logging
3. **Integration Tests** - Complete workflows

---

## Expected Test Coverage

- **Unit Tests**: Enums, Value Objects, Casts (100% coverage target)
- **Feature Tests**: Permissions, Activity Logging, User Management (>90% coverage)
- **Integration Tests**: Complete workflows (>80% coverage)

---

## Success Criteria

✅ All packages installed and configured
✅ All tests passing (100%)
✅ Roles and permissions seeded correctly
✅ Activity log capturing all important actions
✅ Money handling working with cents storage
✅ Feature flags controlling access
✅ User model with modern Laravel patterns
✅ Test coverage > 80%

---

## Estimated Time

**Total**: 2.5-3 hours with TDD approach

- Package Installation: 10 minutes
- Enums + Tests: 20 minutes
- Roles/Permissions + Tests: 40 minutes
- Activity Logging + Tests: 30 minutes
- Money Handling + Tests: 20 minutes
- Feature Flags + Tests: 15 minutes
- User Model/Factories + Tests: 20 minutes
- Integration Tests: 15 minutes

---

## Next Steps After Phase 1

1. **Phase 2**: User Onboarding
   - KYC integration
   - Profile management
   - Document uploads

2. **Phase 3**: Risk Engine Implementation
   - Based on RISK_SCORING_ENGINE.md
   - Database-driven configuration

3. **Phase 4**: Loan Management System
