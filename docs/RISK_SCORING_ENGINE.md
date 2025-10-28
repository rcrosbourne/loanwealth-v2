# Risk Scoring Engine Implementation Guide

## Overview

The Risk Scoring Engine is a critical component of the LoanWealth platform that evaluates borrower creditworthiness using multiple data points to generate a comprehensive risk profile. The engine uses database-driven configuration for dynamic weight adjustment and scoring parameters, allowing real-time optimization without code deployments.

---

## Database Configuration Schema

### Configuration Tables

```sql
-- Risk component weights configuration
CREATE TABLE risk_component_configs (
    id BIGSERIAL PRIMARY KEY,
    component_key VARCHAR(50) UNIQUE NOT NULL,
    component_name VARCHAR(100) NOT NULL,
    weight DECIMAL(5,4) NOT NULL CHECK (weight >= 0 AND weight <= 1),
    is_active BOOLEAN DEFAULT true,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_by BIGINT REFERENCES users(id)
);

-- Risk grade configurations
CREATE TABLE risk_grade_configs (
    id BIGSERIAL PRIMARY KEY,
    grade VARCHAR(3) UNIQUE NOT NULL,
    min_score DECIMAL(5,2) NOT NULL,
    max_score DECIMAL(5,2) NOT NULL,
    base_interest_rate DECIMAL(5,2) NOT NULL,
    max_loan_amount_cents BIGINT NOT NULL,
    risk_multiplier DECIMAL(4,2) DEFAULT 1.0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fraud detection rules configuration
CREATE TABLE fraud_rule_configs (
    id BIGSERIAL PRIMARY KEY,
    rule_key VARCHAR(50) UNIQUE NOT NULL,
    rule_name VARCHAR(100) NOT NULL,
    severity_score INTEGER NOT NULL CHECK (severity_score >= 0 AND severity_score <= 100),
    is_active BOOLEAN DEFAULT true,
    parameters JSONB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Market adjustment configuration
CREATE TABLE market_adjustment_configs (
    id BIGSERIAL PRIMARY KEY,
    adjustment_type VARCHAR(50) NOT NULL,
    base_value DECIMAL(5,2),
    multiplier DECIMAL(4,2) DEFAULT 1.0,
    min_adjustment DECIMAL(5,2),
    max_adjustment DECIMAL(5,2),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Configuration change audit log
CREATE TABLE risk_config_audit_logs (
    id BIGSERIAL PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id BIGINT NOT NULL,
    field_name VARCHAR(50),
    old_value TEXT,
    new_value TEXT,
    changed_by BIGINT REFERENCES users(id),
    change_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_risk_component_active ON risk_component_configs(is_active);
CREATE INDEX idx_risk_grade_score ON risk_grade_configs(min_score, max_score);
CREATE INDEX idx_fraud_rule_active ON fraud_rule_configs(is_active);
CREATE INDEX idx_audit_log_table ON risk_config_audit_logs(table_name, created_at);
```

### Default Configuration Data

```php
// database/seeders/RiskScoringConfigSeeder.php

use Illuminate\Database\Seeder;
use App\Models\RiskComponentConfig;
use App\Models\RiskGradeConfig;
use App\Models\FraudRuleConfig;

class RiskScoringConfigSeeder extends Seeder
{
    public function run(): void
    {
        // Seed component weights
        $components = [
            ['key' => 'credit_score', 'name' => 'Credit Score', 'weight' => 0.30],
            ['key' => 'debt_to_income', 'name' => 'Debt-to-Income Ratio', 'weight' => 0.25],
            ['key' => 'employment_stability', 'name' => 'Employment Stability', 'weight' => 0.15],
            ['key' => 'payment_history', 'name' => 'Payment History', 'weight' => 0.15],
            ['key' => 'household_factors', 'name' => 'Household Factors', 'weight' => 0.10],
            ['key' => 'address_verification', 'name' => 'Address Verification', 'weight' => 0.05],
        ];

        foreach ($components as $component) {
            RiskComponentConfig::updateOrCreate(
                ['component_key' => $component['key']],
                [
                    'component_name' => $component['name'],
                    'weight' => $component['weight'],
                    'is_active' => true,
                ]
            );
        }

        // Seed grade configurations
        $grades = [
            ['grade' => 'A+', 'min' => 85, 'max' => 100, 'rate' => 5.0, 'max_loan' => 5000000],
            ['grade' => 'A', 'min' => 75, 'max' => 84.99, 'rate' => 7.0, 'max_loan' => 4000000],
            ['grade' => 'B', 'min' => 65, 'max' => 74.99, 'rate' => 9.0, 'max_loan' => 3000000],
            ['grade' => 'C', 'min' => 55, 'max' => 64.99, 'rate' => 12.0, 'max_loan' => 2000000],
            ['grade' => 'D', 'min' => 45, 'max' => 54.99, 'rate' => 16.0, 'max_loan' => 1000000],
            ['grade' => 'E', 'min' => 35, 'max' => 44.99, 'rate' => 20.0, 'max_loan' => 500000],
            ['grade' => 'F', 'min' => 0, 'max' => 34.99, 'rate' => 25.0, 'max_loan' => 250000],
        ];

        foreach ($grades as $grade) {
            RiskGradeConfig::updateOrCreate(
                ['grade' => $grade['grade']],
                [
                    'min_score' => $grade['min'],
                    'max_score' => $grade['max'],
                    'base_interest_rate' => $grade['rate'],
                    'max_loan_amount_cents' => $grade['max_loan'] * 100,
                    'is_active' => true,
                ]
            );
        }

        // Seed fraud rules
        $fraudRules = [
            [
                'key' => 'velocity_check',
                'name' => 'Application Velocity Check',
                'severity' => 30,
                'parameters' => [
                    'max_applications' => 3,
                    'time_window_days' => 30,
                ]
            ],
            [
                'key' => 'identity_mismatch',
                'name' => 'Identity Data Mismatch',
                'severity' => 50,
                'parameters' => [
                    'check_trn' => true,
                    'check_address' => true,
                ]
            ],
            [
                'key' => 'suspicious_pattern',
                'name' => 'Suspicious Behavior Pattern',
                'severity' => 40,
                'parameters' => [
                    'min_completion_time' => 120, // seconds
                    'max_completion_time' => 3600,
                ]
            ],
        ];

        foreach ($fraudRules as $rule) {
            FraudRuleConfig::updateOrCreate(
                ['rule_key' => $rule['key']],
                [
                    'rule_name' => $rule['name'],
                    'severity_score' => $rule['severity'],
                    'parameters' => $rule['parameters'],
                    'is_active' => true,
                ]
            );
        }
    }
}
```

---

## Configuration Management Models

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskComponentConfig extends Model
{
    protected $fillable = [
        'component_key',
        'component_name',
        'weight',
        'is_active',
        'description',
        'updated_by',
    ];

    protected $casts = [
        'weight' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function ($model) {
            // Validate weights sum to 1.0
            $totalWeight = self::where('is_active', true)
                ->where('id', '!=', $model->id)
                ->sum('weight') + $model->weight;

            if ($model->is_active && abs($totalWeight - 1.0) > 0.001) {
                throw new \Exception('Active component weights must sum to 1.0');
            }

            // Log the change
            RiskConfigAuditLog::create([
                'table_name' => 'risk_component_configs',
                'record_id' => $model->id,
                'field_name' => 'weight',
                'old_value' => $model->getOriginal('weight'),
                'new_value' => $model->weight,
                'changed_by' => auth()->id(),
            ]);
        });
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function getActiveWeights(): array
    {
        return cache()->remember('risk_component_weights', 3600, function () {
            return self::where('is_active', true)
                ->pluck('weight', 'component_key')
                ->toArray();
        });
    }

    public static function refreshCache(): void
    {
        cache()->forget('risk_component_weights');
        self::getActiveWeights();
    }
}
```

---

## Scoring Components

### 1. Traditional Credit Score (Dynamic Weight)

```php
namespace App\Services\RiskEngine\Components;

use App\Models\RiskComponentConfig;

class CreditScoreComponent implements RiskComponent
{
    private const MIN_SCORE = 300;
    private const MAX_SCORE = 850;
    private const COMPONENT_KEY = 'credit_score';

    private float $weight;

    public function __construct()
    {
        $this->weight = $this->getConfiguredWeight();
    }

    public function calculate(User $borrower): float
    {
        $creditScore = $borrower->creditScores()
            ->latest()
            ->value('score');

        if (!$creditScore) {
            // No credit history - assign base score
            return $this->handleThinFile($borrower);
        }

        // Normalize to 0-100 scale
        $normalized = ($creditScore - self::MIN_SCORE) /
                     (self::MAX_SCORE - self::MIN_SCORE) * 100;

        return $normalized * $this->weight;
    }

    private function getConfiguredWeight(): float
    {
        $weights = RiskComponentConfig::getActiveWeights();
        return $weights[self::COMPONENT_KEY] ?? 0.30; // Default fallback
    }

    private function handleThinFile(User $borrower): float
    {
        // For thin credit files, use alternative data
        $alternativeScore = 50; // Base score

        if ($borrower->hasVerifiedBankAccount()) {
            $alternativeScore += 10;
        }

        if ($borrower->hasStableEmployment()) {
            $alternativeScore += 15;
        }

        return $alternativeScore * $this->weight;
    }
}
```

### 2. Debt-to-Income Ratio (Dynamic Weight)

```php
class DebtToIncomeComponent implements RiskComponent
{
    private const OPTIMAL_RATIO = 0.20; // 20% or less is optimal
    private const MAX_ACCEPTABLE = 0.50; // 50% is maximum
    private const COMPONENT_KEY = 'debt_to_income';

    private float $weight;

    public function __construct()
    {
        $this->weight = $this->getConfiguredWeight();
    }

    public function calculate(User $borrower): float
    {
        $monthlyIncomeCents = $borrower->profile->monthly_income_cents;
        $monthlyDebtCents = $this->calculateMonthlyDebtCents($borrower);

        if ($monthlyIncomeCents <= 0) {
            return 0;
        }

        $ratio = $monthlyDebtCents / $monthlyIncomeCents;

        // Inverse scoring - lower ratio is better
        if ($ratio <= self::OPTIMAL_RATIO) {
            $score = 100;
        } elseif ($ratio >= self::MAX_ACCEPTABLE) {
            $score = 0;
        } else {
            // Linear interpolation
            $score = 100 - (($ratio - self::OPTIMAL_RATIO) /
                    (self::MAX_ACCEPTABLE - self::OPTIMAL_RATIO) * 100);
        }

        return $score * $this->weight;
    }

    private function getConfiguredWeight(): float
    {
        $weights = RiskComponentConfig::getActiveWeights();
        return $weights[self::COMPONENT_KEY] ?? 0.25;
    }

    private function calculateMonthlyDebtCents(User $borrower): int
    {
        return $borrower->debts->sum(function ($debt) {
            return $debt->monthly_payment_cents;
        });
    }
}
```

### 3. Employment Stability (Weight: 15%)

```php
class EmploymentStabilityComponent implements RiskComponent
{
    private const WEIGHT = 0.15;

    public function calculate(User $borrower): float
    {
        $employment = $borrower->employmentHistory()
            ->current()
            ->first();

        if (!$employment) {
            return 0;
        }

        $score = 0;
        $monthsEmployed = $employment->months_employed;

        // Employment duration scoring
        if ($monthsEmployed >= 36) {
            $score = 100;
        } elseif ($monthsEmployed >= 24) {
            $score = 85;
        } elseif ($monthsEmployed >= 12) {
            $score = 70;
        } elseif ($monthsEmployed >= 6) {
            $score = 50;
        } else {
            $score = 25;
        }

        // Adjust for employment type
        $score *= $this->getEmploymentTypeMultiplier($employment->type);

        return $score * self::WEIGHT;
    }

    private function getEmploymentTypeMultiplier(string $type): float
    {
        return match($type) {
            'full_time' => 1.0,
            'contract' => 0.9,
            'part_time' => 0.8,
            'self_employed' => 0.7,
            default => 0.5,
        };
    }
}
```

### 4. Payment History (Weight: 15%)

```php
class PaymentHistoryComponent implements RiskComponent
{
    private const WEIGHT = 0.15;

    public function calculate(User $borrower): float
    {
        $loans = $borrower->loans()
            ->whereIn('status', ['completed', 'active'])
            ->get();

        if ($loans->isEmpty()) {
            // New borrower - neutral score
            return 50 * self::WEIGHT;
        }

        $totalPayments = 0;
        $onTimePayments = 0;

        foreach ($loans as $loan) {
            $payments = $loan->payments()
                ->where('type', 'repayment')
                ->get();

            foreach ($payments as $payment) {
                $totalPayments++;
                if ($payment->wasOnTime()) {
                    $onTimePayments++;
                }
            }
        }

        $score = $totalPayments > 0
            ? ($onTimePayments / $totalPayments * 100)
            : 50;

        // Penalty for recent defaults
        if ($borrower->hasDefaultedLoan(months: 12)) {
            $score *= 0.5;
        }

        return $score * self::WEIGHT;
    }
}
```

### 5. Household Factors (Weight: 10%)

```php
class HouseholdFactorsComponent implements RiskComponent
{
    private const WEIGHT = 0.10;

    public function calculate(User $borrower): float
    {
        $profile = $borrower->profile;
        $score = 50; // Base score

        // Income per household member
        $incomePerPerson = $profile->monthly_income /
                          max($profile->household_size, 1);

        if ($incomePerPerson >= 3000) {
            $score += 25;
        } elseif ($incomePerPerson >= 2000) {
            $score += 15;
        } elseif ($incomePerPerson >= 1000) {
            $score += 5;
        }

        // Multiple income sources
        if ($profile->income_sources > 1) {
            $score += 15;
        }

        // Dependents consideration
        $dependentRatio = $profile->dependents /
                         max($profile->household_size, 1);

        if ($dependentRatio <= 0.25) {
            $score += 10;
        } elseif ($dependentRatio <= 0.5) {
            $score += 5;
        }

        return min($score, 100) * self::WEIGHT;
    }
}
```

### 6. Address Verification (Weight: 5%)

```php
class AddressVerificationComponent implements RiskComponent
{
    private const WEIGHT = 0.05;

    public function calculate(User $borrower): float
    {
        $score = 0;
        $address = $borrower->currentAddress;

        if (!$address) {
            return 0;
        }

        // Address verification status
        if ($address->is_verified) {
            $score += 50;
        }

        // Length of residence
        $monthsAtAddress = $address->months_at_address;

        if ($monthsAtAddress >= 36) {
            $score += 50;
        } elseif ($monthsAtAddress >= 24) {
            $score += 40;
        } elseif ($monthsAtAddress >= 12) {
            $score += 30;
        } elseif ($monthsAtAddress >= 6) {
            $score += 20;
        } else {
            $score += 10;
        }

        return $score * self::WEIGHT;
    }
}
```

---

## Machine Learning Enhancement

### Feature Engineering

```python
# Python ML Service for Advanced Scoring

import pandas as pd
import numpy as np
from sklearn.ensemble import GradientBoostingRegressor
from sklearn.preprocessing import StandardScaler

class AdvancedRiskScorer:
    def __init__(self):
        self.model = GradientBoostingRegressor(
            n_estimators=100,
            learning_rate=0.1,
            max_depth=3,
            random_state=42
        )
        self.scaler = StandardScaler()

    def prepare_features(self, borrower_data):
        features = {
            # Behavioral features
            'app_completion_time': borrower_data['app_completion_time'],
            'pages_visited': borrower_data['pages_visited'],
            'time_on_site': borrower_data['time_on_site'],

            # Financial velocity
            'income_trend': self.calculate_income_trend(borrower_data),
            'debt_trend': self.calculate_debt_trend(borrower_data),

            # Social features
            'referral_source': self.encode_referral(borrower_data['referral']),
            'communication_responsiveness': borrower_data['response_time'],

            # Document quality
            'document_completeness': borrower_data['docs_complete'],
            'document_clarity_score': borrower_data['doc_quality'],

            # Time-based features
            'day_of_week_applied': borrower_data['application_dow'],
            'hour_of_day_applied': borrower_data['application_hour'],
        }

        return pd.DataFrame([features])

    def predict_default_probability(self, borrower_data):
        features = self.prepare_features(borrower_data)
        features_scaled = self.scaler.transform(features)

        # Probability of default
        default_prob = self.model.predict_proba(features_scaled)[0][1]

        # Convert to risk score adjustment (-20 to +20 points)
        adjustment = (0.5 - default_prob) * 40

        return adjustment
```

### Integration with Laravel

```php
namespace App\Services\RiskEngine;

use App\Services\MachineLearning\PythonMLService;
use App\Models\RiskComponentConfig;
use App\Models\RiskGradeConfig;
use App\Models\MarketAdjustmentConfig;

class RiskScoringEngine
{
    private array $components;
    private PythonMLService $mlService;

    public function __construct(PythonMLService $mlService)
    {
        $this->mlService = $mlService;
        $this->initializeComponents();
    }

    private function initializeComponents(): void
    {
        // Only initialize active components from database
        $activeComponents = RiskComponentConfig::where('is_active', true)
            ->orderBy('weight', 'desc')
            ->get();

        $this->components = [];
        foreach ($activeComponents as $config) {
            $componentClass = $this->getComponentClass($config->component_key);
            if (class_exists($componentClass)) {
                $this->components[] = new $componentClass();
            }
        }
    }

    private function getComponentClass(string $key): string
    {
        $componentMap = [
            'credit_score' => CreditScoreComponent::class,
            'debt_to_income' => DebtToIncomeComponent::class,
            'employment_stability' => EmploymentStabilityComponent::class,
            'payment_history' => PaymentHistoryComponent::class,
            'household_factors' => HouseholdFactorsComponent::class,
            'address_verification' => AddressVerificationComponent::class,
        ];

        return $componentMap[$key] ?? '';
    }

    public function calculateScore(User $borrower): RiskProfile
    {
        // Calculate base score from components
        $baseScore = 0;
        $componentScores = [];

        foreach ($this->components as $component) {
            $score = $component->calculate($borrower);
            $baseScore += $score;
            $componentScores[$component->getName()] = $score;
        }

        // Apply ML adjustment if available
        $mlAdjustment = 0;
        if ($this->shouldUseMachineLearning($borrower)) {
            $mlAdjustment = $this->mlService->getRiskAdjustment($borrower);
            $baseScore = max(0, min(100, $baseScore + $mlAdjustment));
        }

        // Determine risk grade from database configuration
        $gradeConfig = $this->getGradeConfig($baseScore);
        $grade = $gradeConfig->grade;

        // Calculate interest rate from database configuration
        $interestRate = $this->calculateInterestRate($gradeConfig, $baseScore);

        return new RiskProfile([
            'score' => $baseScore,
            'grade' => $grade,
            'interest_rate' => $interestRate,
            'max_loan_amount_cents' => $gradeConfig->max_loan_amount_cents,
            'component_scores' => $componentScores,
            'ml_adjustment' => $mlAdjustment,
            'factors' => $this->explainFactors($componentScores),
            'calculated_at' => now(),
        ]);
    }

    private function getGradeConfig(float $score): RiskGradeConfig
    {
        return RiskGradeConfig::where('is_active', true)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->firstOrFail();
    }

    private function calculateInterestRate(RiskGradeConfig $gradeConfig, float $score): float
    {
        $baseRate = $gradeConfig->base_interest_rate;

        // Market adjustment from database
        $marketAdjustment = $this->getMarketRateAdjustment();

        // Apply risk multiplier from grade config
        $riskAdjustedRate = $baseRate * $gradeConfig->risk_multiplier;

        // Fine-tune within grade based on exact score
        $gradeRange = $gradeConfig->max_score - $gradeConfig->min_score;
        $scorePosition = ($score - $gradeConfig->min_score) / $gradeRange;
        $gradeFinetuning = (1 - $scorePosition) * 0.5; // 0 to 0.5% adjustment

        return round($riskAdjustedRate + $marketAdjustment + $gradeFinetuning, 2);
    }

    private function getMarketRateAdjustment(): float
    {
        // Fetch market adjustment configurations from database
        $adjustments = MarketAdjustmentConfig::where('is_active', true)->get();
        $totalAdjustment = 0;

        foreach ($adjustments as $config) {
            $value = $this->getMarketValue($config->adjustment_type);

            // Apply configured multiplier and constraints
            $adjustment = ($value - $config->base_value) * $config->multiplier;

            // Apply min/max constraints
            if ($config->min_adjustment !== null) {
                $adjustment = max($adjustment, $config->min_adjustment);
            }
            if ($config->max_adjustment !== null) {
                $adjustment = min($adjustment, $config->max_adjustment);
            }

            $totalAdjustment += $adjustment;
        }

        return $totalAdjustment;
    }

    private function getMarketValue(string $type): float
    {
        return cache()->remember("market_value_{$type}", 3600, function() use ($type) {
            return match($type) {
                'treasury_rate' => $this->fetchTreasuryRate(),
                'platform_liquidity' => $this->calculatePlatformLiquidity(),
                'default_rate_trend' => $this->getDefaultRateTrend(),
                default => 0,
            };
        });
    }
}
```

---

## Fraud Detection Integration

```php
namespace App\Services\RiskEngine\Fraud;

use App\Models\FraudRuleConfig;

class FraudDetectionService
{
    private array $rules;

    public function __construct()
    {
        $this->initializeRules();
    }

    private function initializeRules(): void
    {
        $activeRules = FraudRuleConfig::where('is_active', true)
            ->orderBy('severity_score', 'desc')
            ->get();

        $this->rules = [];
        foreach ($activeRules as $config) {
            $ruleClass = $this->getRuleClass($config->rule_key);
            if (class_exists($ruleClass)) {
                $this->rules[] = new $ruleClass($config);
            }
        }
    }

    private function getRuleClass(string $key): string
    {
        $ruleMap = [
            'velocity_check' => VelocityRule::class,
            'identity_mismatch' => IdentityMismatchRule::class,
            'suspicious_pattern' => SuspiciousPatternRule::class,
            'device_fingerprint' => DeviceFingerprintRule::class,
            'geolocation' => GeolocationRule::class,
        ];

        return $ruleMap[$key] ?? '';
    }

    public function evaluate(User $borrower, LoanRequest $request): FraudScore
    {
        $flags = [];
        $score = 0;

        foreach ($this->rules as $rule) {
            $result = $rule->check($borrower, $request);

            if ($result->isTriggered()) {
                $flags[] = $result;
                $score += $result->getSeverity();
            }
        }

        // Get thresholds from database or use defaults
        $manualReviewThreshold = config('fraud.manual_review_threshold', 50);
        $blockThreshold = config('fraud.block_threshold', 80);

        return new FraudScore([
            'score' => $score,
            'flags' => $flags,
            'risk_level' => $this->calculateRiskLevel($score),
            'requires_manual_review' => $score > $manualReviewThreshold,
            'block_application' => $score > $blockThreshold,
        ]);
    }
}

class VelocityRule implements FraudRule
{
    private FraudRuleConfig $config;

    public function __construct(FraudRuleConfig $config)
    {
        $this->config = $config;
    }

    public function check(User $borrower, LoanRequest $request): RuleResult
    {
        $params = $this->config->parameters;
        $maxApplications = $params['max_applications'] ?? 3;
        $timeWindowDays = $params['time_window_days'] ?? 30;

        $recentApplications = LoanRequest::where('borrower_id', $borrower->id)
            ->where('created_at', '>', now()->subDays($timeWindowDays))
            ->count();

        if ($recentApplications > $maxApplications) {
            return RuleResult::triggered(
                'Multiple loan applications detected',
                severity: $this->config->severity_score
            );
        }

        // Check for similar TRN/address combinations (Caribbean adaptation)
        $similarProfiles = User::where('id', '!=', $borrower->id)
            ->where(function($query) use ($borrower) {
                $query->where('trn_hash', $borrower->trn_hash)
                    ->orWhere('address_hash', $borrower->address_hash);
            })
            ->count();

        if ($similarProfiles > 0) {
            return RuleResult::triggered(
                'Duplicate identity markers detected',
                severity: $this->config->severity_score + 20
            );
        }

        return RuleResult::passed();
    }
}
```

---

## Testing the Risk Engine

```php
namespace Tests\Feature\RiskEngine;

use App\Models\User;
use App\Services\RiskEngine\RiskScoringEngine;

it('calculates correct risk score for prime borrower', function () {
    $borrower = User::factory()
        ->borrower()
        ->has(CreditScore::factory()->score(800))
        ->has(Employment::factory()->stable())
        ->create([
            'profile' => [
                'monthly_income' => 8000,
                'household_size' => 2,
                'dependents' => 0,
            ],
        ]);

    $engine = app(RiskScoringEngine::class);
    $profile = $engine->calculateScore($borrower);

    expect($profile->grade)->toBe('A+')
        ->and($profile->score)->toBeGreaterThan(85)
        ->and($profile->interest_rate)->toBeBetween(5.0, 7.0);
});

it('handles thin credit files appropriately', function () {
    $borrower = User::factory()
        ->borrower()
        ->noCreditHistory()
        ->has(Employment::factory()->stable())
        ->has(BankAccount::factory()->verified())
        ->create();

    $engine = app(RiskScoringEngine::class);
    $profile = $engine->calculateScore($borrower);

    expect($profile->grade)->toBeIn(['C', 'D'])
        ->and($profile->factors)->toContain('Limited credit history');
});

it('detects potential fraud patterns', function () {
    $borrower = User::factory()->borrower()->create();

    // Create multiple recent applications
    LoanRequest::factory()
        ->count(5)
        ->for($borrower)
        ->create(['created_at' => now()->subDays(10)]);

    $fraudService = app(FraudDetectionService::class);
    $fraudScore = $fraudService->evaluate(
        $borrower,
        LoanRequest::factory()->make()
    );

    expect($fraudScore->requires_manual_review)->toBeTrue()
        ->and($fraudScore->flags)->toHaveCount(1);
});
```

---

## Admin Configuration Management

### Admin UI Controllers

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RiskComponentConfig;
use App\Models\RiskGradeConfig;
use App\Models\FraudRuleConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RiskConfigurationController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/RiskConfiguration/Index', [
            'components' => RiskComponentConfig::with('updatedBy')->get(),
            'grades' => RiskGradeConfig::where('is_active', true)
                ->orderBy('min_score', 'desc')
                ->get(),
            'fraudRules' => FraudRuleConfig::where('is_active', true)->get(),
            'totalWeight' => RiskComponentConfig::where('is_active', true)
                ->sum('weight'),
        ]);
    }

    public function updateComponent(Request $request, RiskComponentConfig $component)
    {
        $validated = $request->validate([
            'weight' => 'required|numeric|min:0|max:1',
            'is_active' => 'required|boolean',
            'description' => 'nullable|string|max:500',
        ]);

        // Validate total weights if activating or changing weight
        if ($validated['is_active']) {
            $otherWeights = RiskComponentConfig::where('is_active', true)
                ->where('id', '!=', $component->id)
                ->sum('weight');

            if (abs(($otherWeights + $validated['weight']) - 1.0) > 0.001) {
                return back()->withErrors([
                    'weight' => 'Active component weights must sum to exactly 1.0'
                ]);
            }
        }

        $component->update($validated + ['updated_by' => auth()->id()]);
        RiskComponentConfig::refreshCache();

        return back()->with('success', 'Component weight updated successfully');
    }

    public function updateGrade(Request $request, RiskGradeConfig $grade)
    {
        $validated = $request->validate([
            'base_interest_rate' => 'required|numeric|min:0|max:100',
            'max_loan_amount_cents' => 'required|integer|min:0',
            'risk_multiplier' => 'required|numeric|min:0.1|max:2',
        ]);

        $grade->update($validated);

        return back()->with('success', 'Grade configuration updated successfully');
    }

    public function updateFraudRule(Request $request, FraudRuleConfig $rule)
    {
        $validated = $request->validate([
            'severity_score' => 'required|integer|min:0|max:100',
            'is_active' => 'required|boolean',
            'parameters' => 'nullable|array',
        ]);

        $rule->update($validated);

        return back()->with('success', 'Fraud rule updated successfully');
    }

    public function simulateScore(Request $request)
    {
        $validated = $request->validate([
            'borrower_id' => 'required|exists:users,id',
        ]);

        $borrower = User::findOrFail($validated['borrower_id']);
        $engine = app(RiskScoringEngine::class);
        $profile = $engine->calculateScore($borrower);

        return response()->json([
            'profile' => $profile,
            'components' => $profile->component_scores,
            'recommendation' => $this->getRecommendation($profile),
        ]);
    }
}
```

### Admin Dashboard View

```jsx
// resources/js/Pages/Admin/RiskConfiguration/Index.jsx
import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function RiskConfiguration({ components, grades, fraudRules, totalWeight }) {
    const [activeTab, setActiveTab] = useState('components');

    return (
        <AdminLayout title="Risk Scoring Configuration">
            <div className="bg-white shadow rounded-lg">
                <div className="border-b border-gray-200">
                    <nav className="flex -mb-px">
                        <button
                            onClick={() => setActiveTab('components')}
                            className={`px-6 py-3 ${activeTab === 'components' ? 'border-b-2 border-indigo-500' : ''}`}
                        >
                            Component Weights
                        </button>
                        <button
                            onClick={() => setActiveTab('grades')}
                            className={`px-6 py-3 ${activeTab === 'grades' ? 'border-b-2 border-indigo-500' : ''}`}
                        >
                            Grade Configuration
                        </button>
                        <button
                            onClick={() => setActiveTab('fraud')}
                            className={`px-6 py-3 ${activeTab === 'fraud' ? 'border-b-2 border-indigo-500' : ''}`}
                        >
                            Fraud Rules
                        </button>
                    </nav>
                </div>

                <div className="p-6">
                    {activeTab === 'components' && (
                        <ComponentWeights
                            components={components}
                            totalWeight={totalWeight}
                        />
                    )}
                    {activeTab === 'grades' && (
                        <GradeConfiguration grades={grades} />
                    )}
                    {activeTab === 'fraud' && (
                        <FraudRules rules={fraudRules} />
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}

function ComponentWeights({ components, totalWeight }) {
    const weightSum = Math.abs(totalWeight - 1.0) < 0.001;

    return (
        <div>
            {!weightSum && (
                <div className="bg-red-50 p-4 rounded mb-4">
                    <p className="text-red-800">
                        Warning: Active weights sum to {(totalWeight * 100).toFixed(1)}%.
                        They must sum to exactly 100%.
                    </p>
                </div>
            )}

            <div className="space-y-4">
                {components.map(component => (
                    <ComponentWeightForm key={component.id} component={component} />
                ))}
            </div>
        </div>
    );
}

function ComponentWeightForm({ component }) {
    const { data, setData, put, processing, errors } = useForm({
        weight: component.weight,
        is_active: component.is_active,
        description: component.description || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/admin/risk-config/components/${component.id}`);
    };

    return (
        <form onSubmit={handleSubmit} className="border rounded p-4">
            <div className="flex items-center justify-between mb-4">
                <h3 className="font-medium">{component.component_name}</h3>
                <label className="flex items-center">
                    <input
                        type="checkbox"
                        checked={data.is_active}
                        onChange={e => setData('is_active', e.target.checked)}
                        className="mr-2"
                    />
                    Active
                </label>
            </div>

            <div className="grid grid-cols-2 gap-4">
                <div>
                    <label className="block text-sm font-medium mb-1">
                        Weight (Current: {(component.weight * 100).toFixed(1)}%)
                    </label>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        max="1"
                        value={data.weight}
                        onChange={e => setData('weight', parseFloat(e.target.value))}
                        disabled={!data.is_active}
                        className="w-full border rounded px-3 py-2"
                    />
                    {errors.weight && (
                        <p className="text-red-600 text-sm mt-1">{errors.weight}</p>
                    )}
                </div>

                <div>
                    <label className="block text-sm font-medium mb-1">Description</label>
                    <input
                        type="text"
                        value={data.description}
                        onChange={e => setData('description', e.target.value)}
                        className="w-full border rounded px-3 py-2"
                    />
                </div>
            </div>

            <button
                type="submit"
                disabled={processing}
                className="mt-4 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"
            >
                Update Configuration
            </button>
        </form>
    );
}
```

### Configuration API Routes

```php
// routes/web.php (Admin routes)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/risk-config', [RiskConfigurationController::class, 'index'])
        ->name('admin.risk-config.index');

    Route::put('/risk-config/components/{component}', [RiskConfigurationController::class, 'updateComponent'])
        ->name('admin.risk-config.components.update');

    Route::put('/risk-config/grades/{grade}', [RiskConfigurationController::class, 'updateGrade'])
        ->name('admin.risk-config.grades.update');

    Route::put('/risk-config/fraud-rules/{rule}', [RiskConfigurationController::class, 'updateFraudRule'])
        ->name('admin.risk-config.fraud-rules.update');

    Route::post('/risk-config/simulate', [RiskConfigurationController::class, 'simulateScore'])
        ->name('admin.risk-config.simulate');
});

// routes/api.php (API endpoints for external access)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin')->group(function () {
    Route::get('/risk-config/export', [RiskConfigApiController::class, 'export']);
    Route::post('/risk-config/import', [RiskConfigApiController::class, 'import']);
    Route::post('/risk-config/validate', [RiskConfigApiController::class, 'validate']);
    Route::post('/risk-config/rollback/{version}', [RiskConfigApiController::class, 'rollback']);
});
```

### Configuration Validation Service

```php
namespace App\Services\RiskEngine;

use App\Models\RiskComponentConfig;
use App\Models\RiskGradeConfig;

class ConfigurationValidator
{
    public function validateWeights(): array
    {
        $errors = [];

        $totalWeight = RiskComponentConfig::where('is_active', true)->sum('weight');

        if (abs($totalWeight - 1.0) > 0.001) {
            $errors[] = "Active component weights sum to {$totalWeight}, must equal 1.0";
        }

        return $errors;
    }

    public function validateGrades(): array
    {
        $errors = [];

        $grades = RiskGradeConfig::where('is_active', true)
            ->orderBy('min_score')
            ->get();

        $lastMax = -1;
        foreach ($grades as $grade) {
            if ($grade->min_score <= $lastMax) {
                $errors[] = "Grade {$grade->grade} overlaps with previous grade";
            }

            if ($grade->min_score >= $grade->max_score) {
                $errors[] = "Grade {$grade->grade} has invalid score range";
            }

            $lastMax = $grade->max_score;
        }

        // Check for complete coverage 0-100
        if ($grades->first()->min_score > 0) {
            $errors[] = "No grade covers scores below {$grades->first()->min_score}";
        }

        if ($grades->last()->max_score < 100) {
            $errors[] = "No grade covers scores above {$grades->last()->max_score}";
        }

        return $errors;
    }

    public function validateAll(): array
    {
        return [
            'weights' => $this->validateWeights(),
            'grades' => $this->validateGrades(),
            'valid' => empty($this->validateWeights()) && empty($this->validateGrades()),
        ];
    }
}
```

---

## Monitoring and Analytics

```sql
-- Key Risk Metrics Dashboard Queries

-- Grade distribution
SELECT
    grade,
    COUNT(*) as loan_count,
    AVG(amount) as avg_loan_amount,
    AVG(interest_rate) as avg_rate,
    SUM(CASE WHEN status = 'defaulted' THEN 1 ELSE 0 END)::float /
        COUNT(*) as default_rate
FROM loans
JOIN risk_profiles ON loans.borrower_id = risk_profiles.user_id
WHERE loans.created_at > NOW() - INTERVAL '90 days'
GROUP BY grade
ORDER BY grade;

-- Score component analysis
SELECT
    AVG((component_scores->>'credit_score')::float) as avg_credit,
    AVG((component_scores->>'debt_to_income')::float) as avg_dti,
    AVG((component_scores->>'employment')::float) as avg_employment,
    AVG((component_scores->>'payment_history')::float) as avg_history
FROM risk_profiles
WHERE calculated_at > NOW() - INTERVAL '30 days';

-- Fraud detection effectiveness
SELECT
    DATE(created_at) as date,
    COUNT(*) as total_applications,
    SUM(CASE WHEN fraud_score > 50 THEN 1 ELSE 0 END) as flagged,
    SUM(CASE WHEN fraud_score > 80 THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN manually_approved THEN 1 ELSE 0 END) as manual_approvals
FROM loan_requests
WHERE created_at > NOW() - INTERVAL '7 days'
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

This comprehensive risk scoring engine provides a robust foundation for evaluating borrower creditworthiness while maintaining flexibility for future enhancements and market adaptations.