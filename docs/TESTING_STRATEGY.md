# LoanWealth Testing Strategy Document

## Executive Summary

This document outlines the comprehensive testing strategy for the LoanWealth P2P lending platform, covering all aspects from unit testing to production monitoring. Our testing approach ensures platform reliability, security, and compliance while maintaining rapid development velocity.

---

## Table of Contents

1. [Testing Philosophy](#testing-philosophy)
2. [Testing Types & Layers](#testing-types--layers)
3. [Testing Tools & Setup](#testing-tools--setup)
4. [Test Coverage Requirements](#test-coverage-requirements)
5. [Critical Test Scenarios](#critical-test-scenarios)
6. [Test Data Management](#test-data-management)
7. [CI/CD Integration](#cicd-integration)
8. [Performance Testing](#performance-testing)
9. [Security Testing](#security-testing)
10. [Browser Testing](#browser-testing)
11. [Testing Best Practices](#testing-best-practices)

---

## Testing Philosophy

### Core Principles

1. **Test-Driven Development (TDD)**: Write tests before implementation for critical features
2. **Comprehensive Coverage**: Aim for >80% code coverage on business logic
3. **Fast Feedback**: Tests should run quickly to enable rapid iteration
4. **Meaningful Tests**: Each test should validate specific business requirements
5. **Maintainable Tests**: Keep tests DRY and well-organized
6. **Production Parity**: Test environments should mirror production

### Testing Pyramid

```
         /\
        /  \        E2E Tests (5%)
       /    \       - Critical user journeys
      /──────\      - Payment flows
     /        \
    /  Browser \    Browser Tests (10%)
   /   Tests    \   - UI interactions
  /──────────────\  - Multi-browser support
 /                \
/  Feature Tests   \ Feature Tests (25%)
\                  / - API endpoints
 \────────────────/  - Integration points
  \              /
   \ Unit Tests /    Unit Tests (60%)
    \          /     - Business logic
     \────────/      - Isolated components
      \      /       - Utility functions
       \    /
        \  /
         \/
```

---

## Testing Types & Layers

### 1. Unit Tests (60% of tests)

**Purpose**: Test individual components in isolation

```php
// tests/Unit/Services/RiskEngine/CreditScoreComponentTest.php

use App\Services\RiskEngine\Components\CreditScoreComponent;
use App\Models\User;
use App\Models\CreditScore;

it('calculates normalized credit score correctly', function () {
    $borrower = User::factory()->borrower()->create();
    CreditScore::factory()->create([
        'user_id' => $borrower->id,
        'score' => 750,
    ]);

    $component = new CreditScoreComponent();
    $score = $component->calculate($borrower);

    // (750 - 300) / (850 - 300) * 100 * 0.30 weight
    expect($score)->toBe(24.545454545454547);
});

it('handles thin credit files appropriately', function () {
    $borrower = User::factory()
        ->borrower()
        ->has(BankAccount::factory()->verified())
        ->has(Employment::factory()->stable())
        ->create();

    $component = new CreditScoreComponent();
    $score = $component->calculate($borrower);

    // Base 50 + 10 (bank) + 15 (employment) = 75 * 0.30 weight
    expect($score)->toBe(22.5);
});
```

### 2. Feature Tests (25% of tests)

**Purpose**: Test complete features and API endpoints

```php
// tests/Feature/Borrower/LoanApplicationTest.php

use App\Models\User;
use App\Models\Loan;
use App\Events\LoanSubmitted;
use App\Jobs\CalculateRiskScore;

it('allows eligible borrower to submit loan application', function () {
    Event::fake();
    Queue::fake();

    $borrower = User::factory()
        ->borrower()
        ->verified()
        ->create();

    $response = $this->actingAs($borrower)
        ->postJson('/api/v1/borrower/loans', [
            'amount' => 10000,
            'term_months' => 12,
            'purpose' => 'debt_consolidation',
            'purpose_description' => 'Consolidating credit cards',
        ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'id',
                'loan_number',
                'status',
                'risk_assessment_pending',
            ]
        ]);

    expect($borrower->loans)->toHaveCount(1);

    Event::assertDispatched(LoanSubmitted::class);
    Queue::assertPushed(CalculateRiskScore::class);
});

it('prevents unverified users from applying for loans', function () {
    $borrower = User::factory()
        ->borrower()
        ->unverified()
        ->create();

    $response = $this->actingAs($borrower)
        ->postJson('/api/v1/borrower/loans', [
            'amount' => 10000,
            'term_months' => 12,
            'purpose' => 'debt_consolidation',
        ]);

    $response->assertForbidden()
        ->assertJson([
            'message' => 'KYC verification required',
        ]);
});
```

### 3. Integration Tests (10% of tests)

**Purpose**: Test integration with external services

```php
// tests/Integration/CreditBureauTest.php

use App\Services\CreditBureau\CreditBureauService;
use App\Models\User;

it('successfully fetches credit score from bureau API', function () {
    $user = User::factory()->borrower()->create([
        'profile' => [
            'ssn_encrypted' => encrypt('123-45-6789'),
        ],
    ]);

    $service = app(CreditBureauService::class);
    $result = $service->fetchCreditScore($user);

    expect($result)
        ->toBeInstanceOf(CreditScoreResult::class)
        ->score->toBeGreaterThan(300)
        ->score->toBeLessThanOrEqual(850)
        ->bureau->toBeIn(['experian', 'equifax', 'transunion']);

    // Verify data was stored
    expect($user->creditScores()->latest()->first())
        ->score->toBe($result->score);
});

it('handles credit bureau API failures gracefully', function () {
    Http::fake([
        'credit-bureau.com/*' => Http::response([], 500),
    ]);

    $user = User::factory()->borrower()->create();
    $service = app(CreditBureauService::class);

    expect(fn() => $service->fetchCreditScore($user))
        ->toThrow(CreditBureauException::class);

    // Verify fallback to manual review
    expect($user->fresh()->requires_manual_review)->toBeTrue();
});
```

### 4. Browser Tests (5% of tests)

**Purpose**: Test critical user journeys in real browsers using Pest 4

```php
// tests/Browser/BorrowerJourneyTest.php

use App\Models\User;
use App\Models\Loan;

it('completes full borrower journey from application to funding', function () {
    $borrower = User::factory()->borrower()->verified()->create();

    $this->browse(function ($browser) use ($borrower) {
        $browser->loginAs($borrower)
            ->visit('/dashboard')
            ->assertSee('Welcome back')
            ->click('@apply-for-loan')
            ->waitForText('Loan Application')
            ->type('amount', '5000')
            ->select('term', '12')
            ->select('purpose', 'debt_consolidation')
            ->type('description', 'Paying off credit cards')
            ->click('@calculate-rate')
            ->waitForText('Estimated Interest Rate')
            ->assertSee('between 9% and 12%')
            ->click('@submit-application')
            ->waitForText('Application Submitted')
            ->assertSee('Your loan is now under review');

        // Verify loan was created
        $loan = Loan::where('borrower_id', $borrower->id)->first();
        expect($loan)->not->toBeNull()
            ->status->toBe('submitted');
    });
});

it('handles multiple browser sessions for real-time bidding', function () {
    $loan = Loan::factory()->approved()->inFunding()->create();
    $lender1 = User::factory()->lender()->withWalletBalance(5000)->create();
    $lender2 = User::factory()->lender()->withWalletBalance(5000)->create();

    $this->browse(function ($first, $second) use ($loan, $lender1, $lender2) {
        // First lender views loan
        $first->loginAs($lender1)
            ->visit("/marketplace/loans/{$loan->id}")
            ->assertSee($loan->amount)
            ->assertSee('0% funded');

        // Second lender views same loan
        $second->loginAs($lender2)
            ->visit("/marketplace/loans/{$loan->id}")
            ->assertSee('0% funded');

        // First lender places bid
        $first->type('bid_amount', '1000')
            ->click('@place-bid')
            ->waitForText('Bid placed successfully');

        // Second lender should see update in real-time
        $second->waitForText('10% funded')
            ->assertSee('1 bid');

        // Second lender places bid
        $second->type('bid_amount', '2000')
            ->click('@place-bid')
            ->waitForText('Bid placed successfully');

        // First lender sees update
        $first->waitForText('30% funded')
            ->assertSee('2 bids');
    });
});
```

---

## Testing Tools & Setup

### Core Testing Stack

```json
{
  "testing": {
    "framework": "Pest 4.1",
    "browser": "Pest Browser Testing",
    "assertions": "Pest Expectations",
    "coverage": "PCOV",
    "mocking": "Mockery",
    "factories": "Laravel Factories",
    "database": "SQLite (in-memory)",
    "api": "Laravel HTTP Tests"
  }
}
```

### Test Environment Configuration

```env
# .env.testing
APP_ENV=testing
APP_DEBUG=true
APP_KEY=base64:test_key_for_testing_only

DB_CONNECTION=sqlite
DB_DATABASE=:memory:

CACHE_DRIVER=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
MAIL_MAILER=array
BROADCAST_DRIVER=log

# External services (mocked)
CREDIT_BUREAU_MOCK=true
PAYMENT_GATEWAY_MOCK=true
DOCUMENT_SIGNING_MOCK=true

# Feature flags for testing
FEATURE_AUTO_INVEST=true
FEATURE_FRAUD_DETECTION=true
FEATURE_ML_SCORING=false
```

### PHPUnit Configuration

```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         processIsolation="false"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Browser">
            <directory>tests/Browser</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">app/</directory>
        </include>
        <exclude>
            <directory>app/Console/Kernel.php</directory>
            <directory>app/Exceptions</directory>
        </exclude>
        <report>
            <html outputDirectory="build/coverage"/>
            <text outputFile="build/coverage.txt"/>
            <clover outputFile="build/clover.xml"/>
        </report>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

---

## Test Coverage Requirements

### Minimum Coverage Targets

| Component | Required Coverage | Critical Areas |
|-----------|------------------|----------------|
| Models | 80% | Relationships, scopes, accessors |
| Actions | 95% | All business logic actions |
| Services | 90% | Risk engine, payment processing |
| Controllers | 75% | Request validation, responses |
| Jobs | 85% | Queue processing logic |
| Events/Listeners | 80% | Event handling |
| Middleware | 90% | Security, authentication |
| Utilities | 100% | Helper functions |

### Coverage Reporting

```bash
# Generate coverage report
php artisan test --coverage --min=80

# Generate detailed HTML report
php artisan test --coverage-html=build/coverage

# Check specific directories
php artisan test --coverage --path=app/Actions
```

---

## Critical Test Scenarios

### 1. Risk Assessment Engine

```php
// tests/Feature/RiskEngine/RiskAssessmentTest.php

dataset('borrower_profiles', [
    'prime' => [
        'credit_score' => 800,
        'debt_to_income' => 0.20,
        'employment_months' => 60,
        'expected_grade' => 'A+',
        'expected_rate_range' => [5.0, 7.0],
    ],
    'subprime' => [
        'credit_score' => 600,
        'debt_to_income' => 0.45,
        'employment_months' => 6,
        'expected_grade' => 'D',
        'expected_rate_range' => [16.0, 20.0],
    ],
    'thin_file' => [
        'credit_score' => null,
        'debt_to_income' => 0.25,
        'employment_months' => 24,
        'expected_grade' => 'C',
        'expected_rate_range' => [12.0, 16.0],
    ],
]);

it('correctly assesses borrower risk profiles', function ($profile) {
    $borrower = User::factory()
        ->borrower()
        ->withProfile($profile)
        ->create();

    $engine = app(RiskScoringEngine::class);
    $assessment = $engine->assess($borrower);

    expect($assessment->grade)->toBe($profile['expected_grade'])
        ->and($assessment->interest_rate)->toBeBetween(...$profile['expected_rate_range']);
})->with('borrower_profiles');
```

### 2. Payment Distribution

```php
// tests/Feature/Payments/PaymentDistributionTest.php

it('correctly distributes payment to multiple lenders', function () {
    // Setup: Loan with 3 lenders
    $loan = Loan::factory()->active()->create([
        'amount' => 10000,
        'interest_rate' => 10,
        'term_months' => 12,
    ]);

    $lenders = [
        ['user' => User::factory()->lender()->create(), 'amount' => 5000],  // 50%
        ['user' => User::factory()->lender()->create(), 'amount' => 3000],  // 30%
        ['user' => User::factory()->lender()->create(), 'amount' => 2000],  // 20%
    ];

    foreach ($lenders as $lender) {
        LoanBid::factory()->accepted()->create([
            'loan_id' => $loan->id,
            'lender_id' => $lender['user']->id,
            'amount' => $lender['amount'],
        ]);
    }

    // Monthly payment (principal + interest)
    $monthlyPayment = 879.16; // Calculated for $10k, 10% APR, 12 months
    $interestPortion = 83.33;
    $principalPortion = 795.83;

    // Process payment
    $payment = Payment::factory()->create([
        'loan_id' => $loan->id,
        'amount' => $monthlyPayment,
        'principal_amount' => $principalPortion,
        'interest_amount' => $interestPortion,
    ]);

    $distributor = app(PaymentDistributor::class);
    $distributor->distribute($payment);

    // Verify distributions
    $distributions = $payment->distributions;

    expect($distributions)->toHaveCount(3);

    // Lender 1 (50%)
    expect($distributions[0])
        ->principal_amount->toBe(397.92)  // 50% of principal
        ->interest_amount->toBe(41.67)     // 50% of interest
        ->platform_fee->toBe(4.17);        // 10% of interest as fee

    // Lender 2 (30%)
    expect($distributions[1])
        ->principal_amount->toBe(238.75)
        ->interest_amount->toBe(25.00)
        ->platform_fee->toBe(2.50);

    // Lender 3 (20%)
    expect($distributions[2])
        ->principal_amount->toBe(159.17)
        ->interest_amount->toBe(16.67)
        ->platform_fee->toBe(1.67);

    // Verify wallet credits
    foreach ($lenders as $index => $lender) {
        $wallet = $lender['user']->wallet;
        $transaction = $wallet->transactions()
            ->where('reference_type', 'payment_distribution')
            ->latest()
            ->first();

        expect($transaction)->not->toBeNull()
            ->type->toBe('interest_payment')
            ->status->toBe('completed');
    }
});
```

### 3. Fraud Detection

```php
// tests/Feature/Fraud/FraudDetectionTest.php

it('detects velocity fraud patterns', function () {
    $fraudster = User::factory()->borrower()->create();

    // Create multiple loan applications in short timespan
    $applications = collect(range(1, 5))->map(fn() =>
        LoanRequest::factory()->create([
            'borrower_id' => $fraudster->id,
            'created_at' => now()->subHours(rand(1, 24)),
        ])
    );

    $detector = app(FraudDetectionService::class);
    $result = $detector->analyze($fraudster, $applications->last());

    expect($result)
        ->risk_level->toBe('high')
        ->requires_manual_review->toBeTrue()
        ->triggered_rules->toContain('velocity_check');
});

it('detects identity inconsistencies', function () {
    $user1 = User::factory()->borrower()->create([
        'profile' => ['ssn_hash' => hash('sha256', '123-45-6789')],
    ]);

    $user2 = User::factory()->borrower()->create([
        'profile' => ['ssn_hash' => hash('sha256', '123-45-6789')],
    ]);

    $detector = app(FraudDetectionService::class);
    $result = $detector->analyze($user2, LoanRequest::factory()->make());

    expect($result)
        ->risk_level->toBe('critical')
        ->was_blocked->toBeTrue()
        ->triggered_rules->toContain('duplicate_identity');
});
```

### 4. Wallet Operations

```php
// tests/Feature/Wallet/WalletOperationsTest.php

it('handles concurrent wallet transactions safely', function () {
    $wallet = Wallet::factory()->create(['available_balance' => 1000]);

    // Simulate concurrent withdrawals
    $jobs = collect(range(1, 10))->map(fn() =>
        new ProcessWithdrawal($wallet, 200)
    );

    // Only 5 should succeed (5 * 200 = 1000)
    $results = $jobs->map(fn($job) => rescue(fn() => $job->handle()));

    $successCount = $results->filter(fn($r) => $r === true)->count();
    $wallet->refresh();

    expect($successCount)->toBe(5)
        ->and($wallet->available_balance)->toBe(0)
        ->and($wallet->transactions()->count())->toBe(5);
});

it('maintains transaction integrity during failures', function () {
    $wallet = Wallet::factory()->create(['available_balance' => 1000]);

    DB::transaction(function () use ($wallet) {
        $wallet->debit(500);

        // Simulate failure
        throw new Exception('Payment gateway error');
    });

    // Wallet should remain unchanged
    expect($wallet->fresh()->available_balance)->toBe(1000);
});
```

---

## Test Data Management

### Factory Best Practices

```php
// database/factories/UserFactory.php

namespace Database\Factories;

use App\Models\User;
use App\Enums\UserType;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'user_type' => UserType::BORROWER,
            'status' => 'active',
            'kyc_status' => 'pending',
        ];
    }

    public function borrower(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => UserType::BORROWER,
        ])->has(Profile::factory()->borrower());
    }

    public function lender(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => UserType::LENDER,
        ])->has(Profile::factory()->lender())
          ->has(Wallet::factory());
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'kyc_status' => 'approved',
            'kyc_verified_at' => now(),
        ]);
    }

    public function withCreditScore(int $score): static
    {
        return $this->has(CreditScore::factory()->state([
            'score' => $score,
        ]));
    }

    public function withWalletBalance(float $amount): static
    {
        return $this->has(Wallet::factory()->state([
            'available_balance' => $amount,
        ]));
    }
}
```

### Seeders for Test Scenarios

```php
// database/seeders/TestScenarioSeeder.php

class TestScenarioSeeder extends Seeder
{
    public function run(): void
    {
        // Scenario 1: Loan in various stages
        $this->createLoanLifecycleScenario();

        // Scenario 2: Multiple lenders competing
        $this->createBiddingWarScenario();

        // Scenario 3: Payment distribution
        $this->createPaymentScenario();

        // Scenario 4: Default handling
        $this->createDefaultScenario();
    }

    private function createLoanLifecycleScenario(): void
    {
        $borrower = User::factory()->borrower()->verified()->create();

        // Draft loan
        Loan::factory()->draft()->for($borrower)->create();

        // Loan in funding
        Loan::factory()->inFunding()->for($borrower)->create();

        // Fully funded loan
        Loan::factory()->funded()->for($borrower)->create();

        // Active loan with payment history
        $activeLoan = Loan::factory()->active()->for($borrower)->create();
        Payment::factory()->count(6)->for($activeLoan)->create();
    }
}
```

---

## CI/CD Integration

### GitHub Actions Workflow

```yaml
# .github/workflows/test.yml

name: Test Suite

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_PASSWORD: password
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 5432:5432

      redis:
        image: redis:7
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pcov, redis
          coverage: pcov

      - name: Install Dependencies
        run: |
          composer install --no-interaction
          npm ci
          npm run build

      - name: Prepare Environment
        run: |
          cp .env.testing .env
          php artisan key:generate
          php artisan migrate --force

      - name: Run Unit Tests
        run: php artisan test --testsuite=Unit --parallel

      - name: Run Feature Tests
        run: php artisan test --testsuite=Feature --parallel

      - name: Run Integration Tests
        run: php artisan test --testsuite=Integration

      - name: Run Browser Tests
        run: php artisan test --testsuite=Browser

      - name: Generate Coverage Report
        run: php artisan test --coverage --min=80

      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          file: ./build/clover.xml
          fail_ci_if_error: true
```

### Pre-commit Hooks

```bash
#!/bin/bash
# .git/hooks/pre-commit

echo "Running pre-commit checks..."

# Run PHP linting
vendor/bin/pint --test
if [ $? -ne 0 ]; then
    echo "❌ PHP linting failed. Run 'vendor/bin/pint' to fix."
    exit 1
fi

# Run static analysis
vendor/bin/phpstan analyse
if [ $? -ne 0 ]; then
    echo "❌ Static analysis failed."
    exit 1
fi

# Run tests for changed files
CHANGED_FILES=$(git diff --cached --name-only --diff-filter=ACM | grep "\.php$")
if [ ! -z "$CHANGED_FILES" ]; then
    php artisan test --filter="$(echo $CHANGED_FILES | sed 's/.php//g' | sed 's/app/Tests/g')"
    if [ $? -ne 0 ]; then
        echo "❌ Tests failed."
        exit 1
    fi
fi

echo "✅ All checks passed!"
```

---

## Performance Testing

### Load Testing with Artillery

```yaml
# artillery/load-test.yml

config:
  target: "https://staging.loanwealth.com"
  phases:
    - duration: 60
      arrivalRate: 10
      name: "Warm up"
    - duration: 300
      arrivalRate: 50
      name: "Sustained load"
    - duration: 60
      arrivalRate: 100
      name: "Spike test"
  processor: "./processor.js"
  variables:
    authToken: "{{ $processEnvironment.AUTH_TOKEN }}"

scenarios:
  - name: "Borrower Journey"
    weight: 30
    flow:
      - post:
          url: "/api/v1/auth/login"
          json:
            email: "{{ $randomString() }}@test.com"
            password: "password"
          capture:
            - json: "$.data.token"
              as: "token"

      - get:
          url: "/api/v1/borrower/loans"
          headers:
            Authorization: "Bearer {{ token }}"

      - post:
          url: "/api/v1/borrower/calculator"
          headers:
            Authorization: "Bearer {{ token }}"
          json:
            amount: "{{ $randomNumber(1000, 50000) }}"
            term_months: "{{ $randomNumber(6, 36) }}"

  - name: "Lender Marketplace"
    weight: 50
    flow:
      - get:
          url: "/api/v1/lender/marketplace"
          headers:
            Authorization: "Bearer {{ authToken }}"

      - get:
          url: "/api/v1/lender/marketplace/loans/{{ $randomNumber(1, 100) }}"
          headers:
            Authorization: "Bearer {{ authToken }}"

  - name: "Real-time Bidding"
    weight: 20
    flow:
      - ws:
          url: "wss://staging.loanwealth.com/websocket"
          send:
            channel: "loan.123.funding"
            event: "subscribe"
```

### Performance Benchmarks

```php
// tests/Performance/CriticalPathTest.php

use Laravel\Octane\Testing\Concerns\ProvidesConcurrency;

it('handles concurrent loan applications', function () {
    $this->bootOctane();

    $results = $this->concurrently(function ($i) {
        return $this->postJson('/api/v1/borrower/loans', [
            'amount' => 10000,
            'term_months' => 12,
            'purpose' => 'test',
        ]);
    }, 100);

    $successCount = collect($results)
        ->filter(fn ($r) => $r->status() === 201)
        ->count();

    expect($successCount)->toBe(100);

    // Performance assertions
    $responseTimes = collect($results)->map(fn ($r) => $r->getHeader('X-Response-Time'));
    $avgResponseTime = $responseTimes->avg();
    $maxResponseTime = $responseTimes->max();

    expect($avgResponseTime)->toBeLessThan(200); // 200ms average
    expect($maxResponseTime)->toBeLessThan(500); // 500ms max
});
```

---

## Security Testing

### Security Test Suite

```php
// tests/Security/SecurityTest.php

it('prevents SQL injection attacks', function () {
    $maliciousInput = "1'; DROP TABLE users; --";

    $response = $this->getJson("/api/v1/loans?user_id={$maliciousInput}");

    $response->assertStatus(400);
    expect(Schema::hasTable('users'))->toBeTrue();
});

it('prevents XSS attacks', function () {
    $xssPayload = '<script>alert("XSS")</script>';

    $borrower = User::factory()->borrower()->create();

    $response = $this->actingAs($borrower)
        ->postJson('/api/v1/borrower/loans', [
            'purpose_description' => $xssPayload,
        ]);

    $loan = Loan::latest()->first();
    expect($loan->purpose_description)->toBe(e($xssPayload));
});

it('enforces rate limiting', function () {
    $user = User::factory()->create();

    // Attempt 61 requests (limit is 60 per minute)
    for ($i = 0; $i < 61; $i++) {
        $response = $this->actingAs($user)
            ->getJson('/api/v1/profile');
    }

    $response->assertStatus(429)
        ->assertJson(['message' => 'Too Many Attempts.']);
});

it('prevents unauthorized access to admin endpoints', function () {
    $regularUser = User::factory()->borrower()->create();

    $response = $this->actingAs($regularUser)
        ->getJson('/api/v1/admin/users');

    $response->assertForbidden();
});

it('encrypts sensitive data at rest', function () {
    $user = User::factory()->create([
        'profile' => [
            'ssn_encrypted' => encrypt('123-45-6789'),
        ],
    ]);

    // Check raw database value is encrypted
    $rawValue = DB::table('profiles')
        ->where('user_id', $user->id)
        ->value('ssn_encrypted');

    expect($rawValue)->not->toContain('123-45-6789');
    expect(decrypt($rawValue))->toBe('123-45-6789');
});
```

### Penetration Testing Checklist

```markdown
## Security Testing Checklist

### Authentication & Authorization
- [ ] Test for weak passwords
- [ ] Verify account lockout after failed attempts
- [ ] Test session timeout
- [ ] Verify JWT token expiration
- [ ] Test privilege escalation
- [ ] Verify RBAC enforcement

### Input Validation
- [ ] SQL injection testing
- [ ] XSS attack vectors
- [ ] Command injection
- [ ] Path traversal
- [ ] File upload validation
- [ ] API parameter tampering

### Data Protection
- [ ] Verify encryption at rest
- [ ] Test HTTPS enforcement
- [ ] Check for sensitive data in logs
- [ ] Verify PII masking
- [ ] Test data retention policies

### API Security
- [ ] Rate limiting enforcement
- [ ] CORS configuration
- [ ] API versioning
- [ ] Error message disclosure
- [ ] GraphQL specific attacks (if applicable)

### Financial Security
- [ ] Race condition in payments
- [ ] Decimal precision attacks
- [ ] Negative amount handling
- [ ] Transaction replay attacks
- [ ] Fund allocation integrity
```

---

## Browser Testing

### Multi-Browser Testing with Pest 4

```php
// tests/Browser/CrossBrowserTest.php

use Laravel\Dusk\Browser;

dataset('browsers', [
    'Chrome' => ['chrome'],
    'Firefox' => ['firefox'],
    'Safari' => ['safari'],
]);

it('works across different browsers', function (string $browser) {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->assertSee('LoanWealth')
            ->click('@sign-in')
            ->type('email', 'test@example.com')
            ->type('password', 'password')
            ->press('Sign In')
            ->assertPathIs('/dashboard');
    });
})->with('browsers');

it('responsive design works on mobile devices', function () {
    $this->browse(function (Browser $browser) {
        // iPhone 14 Pro
        $browser->resize(393, 852)
            ->visit('/')
            ->assertVisible('@mobile-menu-button')
            ->click('@mobile-menu-button')
            ->assertVisible('@mobile-navigation');

        // iPad
        $browser->resize(820, 1180)
            ->visit('/')
            ->assertMissing('@mobile-menu-button')
            ->assertVisible('@desktop-navigation');

        // Desktop
        $browser->maximize()
            ->visit('/')
            ->assertVisible('@full-navigation');
    });
});
```

### Visual Regression Testing

```php
// tests/Browser/VisualRegressionTest.php

it('maintains visual consistency', function () {
    $this->browse(function (Browser $browser) {
        $pages = [
            '/' => 'homepage',
            '/marketplace' => 'marketplace',
            '/dashboard' => 'dashboard',
            '/loans/apply' => 'loan-application',
        ];

        foreach ($pages as $path => $name) {
            $browser->visit($path)
                ->pause(1000) // Wait for animations
                ->screenshot($name);

            // Compare with baseline
            $this->assertVisualSnapshot($name);
        }
    });
});
```

---

## Testing Best Practices

### 1. Test Organization

```
tests/
├── Unit/
│   ├── Actions/
│   ├── Models/
│   ├── Services/
│   └── Utilities/
├── Feature/
│   ├── Api/
│   ├── Auth/
│   ├── Borrower/
│   ├── Lender/
│   └── Admin/
├── Integration/
│   ├── CreditBureau/
│   ├── PaymentGateway/
│   └── DocumentSigning/
├── Browser/
│   ├── Journeys/
│   └── Components/
├── Performance/
├── Security/
└── Helpers/
    ├── Traits/
    └── Factories/
```

### 2. Test Naming Conventions

```php
// ✅ Good test names
it('calculates interest rate based on risk profile')
it('prevents duplicate loan applications within 30 days')
it('distributes payments proportionally to lenders')

// ❌ Poor test names
it('works')
it('test loan')
it('handles errors')
```

### 3. Test Data Builders

```php
// tests/Helpers/Builders/LoanBuilder.php

class LoanBuilder
{
    private array $attributes = [];
    private User $borrower;
    private array $lenders = [];

    public function withBorrower(User $borrower): self
    {
        $this->borrower = $borrower;
        return $this;
    }

    public function withLenders(array $lenderAmounts): self
    {
        foreach ($lenderAmounts as $amount) {
            $this->lenders[] = [
                'lender' => User::factory()->lender()->create(),
                'amount' => $amount,
            ];
        }
        return $this;
    }

    public function inStatus(string $status): self
    {
        $this->attributes['status'] = $status;
        return $this;
    }

    public function build(): Loan
    {
        $loan = Loan::factory()
            ->for($this->borrower ?? User::factory()->borrower())
            ->create($this->attributes);

        foreach ($this->lenders as $lenderData) {
            LoanBid::factory()->accepted()->create([
                'loan_id' => $loan->id,
                'lender_id' => $lenderData['lender']->id,
                'amount' => $lenderData['amount'],
            ]);
        }

        return $loan;
    }
}
```

### 4. Custom Assertions

```php
// tests/Helpers/Assertions/LoanAssertions.php

expect()->extend('toBeFunded', function (float $percentage = 100) {
    $loan = $this->value;
    $fundedPercentage = ($loan->funded_amount / $loan->amount) * 100;

    expect($fundedPercentage)->toBe($percentage);
});

expect()->extend('toHaveGrade', function (string $grade) {
    $loan = $this->value;
    expect($loan->risk_grade)->toBe($grade);
});

// Usage
expect($loan)->toBeFunded(50)->toHaveGrade('B');
```

### 5. Test Helpers

```php
// tests/Helpers/TestHelper.php

class TestHelper
{
    public static function loginAs(string $role): User
    {
        $user = User::factory()->$role()->verified()->create();
        test()->actingAs($user);
        return $user;
    }

    public static function createFundedLoan(float $amount = 10000): Loan
    {
        return (new LoanBuilder())
            ->withLenders([
                $amount * 0.5,
                $amount * 0.3,
                $amount * 0.2,
            ])
            ->inStatus('funded')
            ->build();
    }

    public static function mockCreditBureau(int $score = 750): void
    {
        Http::fake([
            'credit-bureau.com/*' => Http::response([
                'score' => $score,
                'bureau' => 'experian',
                'factors' => ['payment_history', 'credit_utilization'],
            ]),
        ]);
    }
}
```

---

## Testing Checklist

### Before Each Sprint

```markdown
## Sprint Testing Checklist

### Planning
- [ ] Review user stories for testability
- [ ] Identify test scenarios for each story
- [ ] Update test plan documentation
- [ ] Assign test writing responsibilities

### Development
- [ ] Write tests before implementation (TDD)
- [ ] Ensure all edge cases are covered
- [ ] Run tests locally before committing
- [ ] Update factories for new models
- [ ] Add integration tests for external services

### Review
- [ ] Code coverage meets targets (>80%)
- [ ] All tests pass in CI/CD pipeline
- [ ] Performance tests meet benchmarks
- [ ] Security tests pass
- [ ] Browser tests cover critical paths
```

### Before Release

```markdown
## Release Testing Checklist

### Functional Testing
- [ ] All unit tests passing
- [ ] All feature tests passing
- [ ] All integration tests passing
- [ ] Browser tests on all supported browsers
- [ ] Mobile responsiveness tested

### Performance Testing
- [ ] Load testing completed
- [ ] Response time benchmarks met
- [ ] Database query optimization verified
- [ ] API rate limits tested

### Security Testing
- [ ] Security test suite passed
- [ ] Penetration testing completed
- [ ] OWASP Top 10 verified
- [ ] Data encryption verified

### User Acceptance Testing
- [ ] UAT scenarios completed
- [ ] Edge cases tested
- [ ] Error handling verified
- [ ] Accessibility tested

### Production Readiness
- [ ] Staging environment tested
- [ ] Rollback procedure tested
- [ ] Monitoring alerts configured
- [ ] Documentation updated
```

---

## Continuous Improvement

### Test Metrics Dashboard

```sql
-- Queries for test metrics monitoring

-- Test execution trends
SELECT
    DATE(created_at) as date,
    COUNT(*) as total_tests,
    SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
    AVG(execution_time) as avg_execution_time
FROM test_runs
WHERE created_at > NOW() - INTERVAL '30 days'
GROUP BY DATE(created_at);

-- Code coverage trends
SELECT
    DATE(measured_at) as date,
    overall_coverage,
    unit_coverage,
    feature_coverage,
    line_coverage,
    branch_coverage
FROM coverage_metrics
ORDER BY measured_at DESC
LIMIT 30;

-- Flaky test identification
SELECT
    test_name,
    COUNT(*) as total_runs,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failures,
    (SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END)::float / COUNT(*)) as failure_rate
FROM test_runs
WHERE created_at > NOW() - INTERVAL '7 days'
GROUP BY test_name
HAVING COUNT(*) > 10
    AND (SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END)::float / COUNT(*)) BETWEEN 0.1 AND 0.9
ORDER BY failure_rate DESC;
```

### Monthly Test Review Process

1. **Analyze test metrics**
   - Coverage trends
   - Test execution time
   - Flaky test identification

2. **Update test suite**
   - Remove obsolete tests
   - Add missing coverage
   - Optimize slow tests

3. **Review test infrastructure**
   - Update dependencies
   - Improve CI/CD pipeline
   - Enhance test data management

4. **Knowledge sharing**
   - Document new testing patterns
   - Share lessons learned
   - Update best practices

---

This comprehensive testing strategy ensures the LoanWealth platform maintains high quality, security, and reliability throughout development and into production.