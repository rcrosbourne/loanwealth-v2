# Risk Scoring Engine Implementation Guide

## Overview

The Risk Scoring Engine is a critical component of the LoanWealth platform that evaluates borrower creditworthiness using multiple data points to generate a comprehensive risk profile. This guide provides detailed implementation specifications.

---

## Scoring Components

### 1. Traditional Credit Score (Weight: 30%)

```php
namespace App\Services\RiskEngine\Components;

class CreditScoreComponent implements RiskComponent
{
    private const WEIGHT = 0.30;
    private const MIN_SCORE = 300;
    private const MAX_SCORE = 850;

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

        return $normalized * self::WEIGHT;
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

        return $alternativeScore * self::WEIGHT;
    }
}
```

### 2. Debt-to-Income Ratio (Weight: 25%)

```php
class DebtToIncomeComponent implements RiskComponent
{
    private const WEIGHT = 0.25;
    private const OPTIMAL_RATIO = 0.20; // 20% or less is optimal
    private const MAX_ACCEPTABLE = 0.50; // 50% is maximum

    public function calculate(User $borrower): float
    {
        $monthlyIncome = $borrower->profile->monthly_income;
        $monthlyDebt = $this->calculateMonthlyDebt($borrower);

        if ($monthlyIncome <= 0) {
            return 0;
        }

        $ratio = $monthlyDebt / $monthlyIncome;

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

        return $score * self::WEIGHT;
    }

    private function calculateMonthlyDebt(User $borrower): float
    {
        return $borrower->debts->sum(function ($debt) {
            return $debt->monthly_payment;
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

class RiskScoringEngine
{
    private array $components;
    private PythonMLService $mlService;

    public function __construct(PythonMLService $mlService)
    {
        $this->mlService = $mlService;
        $this->components = [
            new CreditScoreComponent(),
            new DebtToIncomeComponent(),
            new EmploymentStabilityComponent(),
            new PaymentHistoryComponent(),
            new HouseholdFactorsComponent(),
            new AddressVerificationComponent(),
        ];
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

        // Determine risk grade
        $grade = $this->calculateGrade($baseScore);

        // Calculate interest rate
        $interestRate = $this->calculateInterestRate($grade, $baseScore);

        return new RiskProfile([
            'score' => $baseScore,
            'grade' => $grade,
            'interest_rate' => $interestRate,
            'component_scores' => $componentScores,
            'ml_adjustment' => $mlAdjustment,
            'factors' => $this->explainFactors($componentScores),
            'calculated_at' => now(),
        ]);
    }

    private function calculateGrade(float $score): string
    {
        return match(true) {
            $score >= 85 => 'A+',
            $score >= 75 => 'A',
            $score >= 65 => 'B',
            $score >= 55 => 'C',
            $score >= 45 => 'D',
            $score >= 35 => 'E',
            default => 'F',
        };
    }

    private function calculateInterestRate(string $grade, float $score): float
    {
        // Base rates by grade
        $baseRates = [
            'A+' => 5.0,
            'A' => 7.0,
            'B' => 9.0,
            'C' => 12.0,
            'D' => 16.0,
            'E' => 20.0,
            'F' => 25.0,
        ];

        $baseRate = $baseRates[$grade];

        // Market adjustment
        $marketAdjustment = $this->getMarketRateAdjustment();

        // Fine-tune within grade
        $gradeAdjustment = $this->calculateGradeAdjustment($grade, $score);

        return round($baseRate + $marketAdjustment + $gradeAdjustment, 2);
    }

    private function getMarketRateAdjustment(): float
    {
        // Fetch current market conditions
        $marketData = cache()->remember('market_rates', 3600, function() {
            return [
                'treasury_rate' => $this->fetchTreasuryRate(),
                'platform_liquidity' => $this->calculatePlatformLiquidity(),
                'default_rate_trend' => $this->getDefaultRateTrend(),
            ];
        });

        $adjustment = 0;

        // Adjust based on treasury rate changes
        $adjustment += ($marketData['treasury_rate'] - 3.0) * 0.5;

        // Adjust based on platform liquidity
        if ($marketData['platform_liquidity'] < 0.3) {
            $adjustment += 1.0; // Increase rates when liquidity is low
        }

        // Adjust based on default trends
        if ($marketData['default_rate_trend'] > 0) {
            $adjustment += 0.5;
        }

        return $adjustment;
    }
}
```

---

## Fraud Detection Integration

```php
namespace App\Services\RiskEngine\Fraud;

class FraudDetectionService
{
    private array $rules;

    public function __construct()
    {
        $this->rules = [
            new VelocityRule(),          // Multiple applications
            new IdentityMismatchRule(),  // Inconsistent data
            new SuspiciousPatternRule(), // Known fraud patterns
            new DeviceFingerprintRule(), // Device tracking
            new GeolocationRule(),       // Location anomalies
        ];
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

        return new FraudScore([
            'score' => $score,
            'flags' => $flags,
            'risk_level' => $this->calculateRiskLevel($score),
            'requires_manual_review' => $score > 50,
            'block_application' => $score > 80,
        ]);
    }
}

class VelocityRule implements FraudRule
{
    public function check(User $borrower, LoanRequest $request): RuleResult
    {
        $recentApplications = LoanRequest::where('borrower_id', $borrower->id)
            ->where('created_at', '>', now()->subDays(30))
            ->count();

        if ($recentApplications > 3) {
            return RuleResult::triggered(
                'Multiple loan applications detected',
                severity: 30
            );
        }

        // Check for similar SSN/address combinations
        $similarProfiles = User::where('id', '!=', $borrower->id)
            ->where(function($query) use ($borrower) {
                $query->where('ssn_hash', $borrower->ssn_hash)
                    ->orWhere('address_hash', $borrower->address_hash);
            })
            ->count();

        if ($similarProfiles > 0) {
            return RuleResult::triggered(
                'Duplicate identity markers detected',
                severity: 50
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

## Configuration Management

```php
// config/risk-engine.php
return [
    'weights' => [
        'credit_score' => env('RISK_WEIGHT_CREDIT', 0.30),
        'debt_to_income' => env('RISK_WEIGHT_DTI', 0.25),
        'employment' => env('RISK_WEIGHT_EMPLOYMENT', 0.15),
        'payment_history' => env('RISK_WEIGHT_HISTORY', 0.15),
        'household' => env('RISK_WEIGHT_HOUSEHOLD', 0.10),
        'address' => env('RISK_WEIGHT_ADDRESS', 0.05),
    ],

    'grades' => [
        'A+' => ['min_score' => 85, 'base_rate' => 5.0, 'max_loan' => 50000],
        'A'  => ['min_score' => 75, 'base_rate' => 7.0, 'max_loan' => 40000],
        'B'  => ['min_score' => 65, 'base_rate' => 9.0, 'max_loan' => 30000],
        'C'  => ['min_score' => 55, 'base_rate' => 12.0, 'max_loan' => 20000],
        'D'  => ['min_score' => 45, 'base_rate' => 16.0, 'max_loan' => 10000],
        'E'  => ['min_score' => 35, 'base_rate' => 20.0, 'max_loan' => 5000],
    ],

    'fraud_detection' => [
        'enabled' => env('FRAUD_DETECTION_ENABLED', true),
        'manual_review_threshold' => 50,
        'auto_reject_threshold' => 80,
        'velocity_check_days' => 30,
        'max_applications_per_period' => 3,
    ],

    'machine_learning' => [
        'enabled' => env('ML_SCORING_ENABLED', false),
        'service_url' => env('ML_SERVICE_URL'),
        'timeout' => 5, // seconds
        'cache_ttl' => 3600, // 1 hour
    ],
];
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