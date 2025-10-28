# Database Migration Plan

## Overview
This document outlines the database migration strategy for the LoanWealth platform, organized by implementation phases with sample Laravel migrations. The platform is designed for the Caribbean market (Jamaica and other territories) with money stored as cents and PHP enums for type management.

---

## Phase 1: Core User System Migrations

### 1.1 Update Users Table
```php
// database/migrations/2024_01_01_000001_add_p2p_fields_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type', 20)->after('email');
            $table->string('status', 20)->default('pending')->after('user_type');
            $table->string('kyc_status', 20)->default('pending')->after('status');
            $table->timestamp('kyc_verified_at')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason')->nullable();

            $table->index('user_type');
            $table->index('status');
            $table->index('kyc_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'user_type', 'status', 'kyc_status',
                'kyc_verified_at', 'terms_accepted_at',
                'suspended_at', 'suspension_reason'
            ]);
        });
    }
};
```

### 1.2 User Profiles Table
```php
// database/migrations/2024_01_01_000002_create_profiles_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->date('date_of_birth');

            // TRN (Tax Registration Number) for Caribbean market
            $table->string('trn_encrypted')->nullable();
            $table->string('trn_hash')->unique()->nullable();
            $table->string('tax_id_encrypted')->nullable();

            $table->string('phone');
            $table->string('phone_secondary')->nullable();

            // Address fields
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('parish_state'); // Parish for Jamaica, State for other territories
            $table->string('postal_code')->nullable(); // Optional as not all Caribbean countries use postal codes
            $table->string('country', 2)->default('JM'); // Jamaica default
            $table->integer('months_at_address')->default(0);

            // Employment - using string for enum casting
            $table->string('employment_status', 30);
            $table->string('employer_name')->nullable();
            $table->unsignedBigInteger('monthly_income_cents'); // Store in cents
            $table->integer('income_sources')->default(1);

            // Household
            $table->integer('household_size')->default(1);
            $table->integer('dependents')->default(0);

            // Metadata
            $table->json('additional_data')->nullable();
            $table->timestamps();

            $table->index('trn_hash');
            $table->index(['city', 'parish_state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
```

### 1.3 KYC Verifications Table
```php
// database/migrations/2024_01_01_000003_create_kyc_verifications_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kyc_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('verification_type', 30); // Cast to enum in model
            $table->string('status', 20); // Cast to enum in model
            $table->string('provider'); // e.g., 'jumio', 'onfido', 'manual'
            $table->string('provider_reference_id')->nullable();
            $table->json('provider_response')->nullable();
            $table->json('verified_data')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->unsignedBigInteger('verified_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'verification_type']);
            $table->index('status');
            $table->index('provider_reference_id');
            $table->foreign('verified_by_user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kyc_verifications');
    }
};
```

---

## Phase 2: Credit & Risk Assessment

### 2.1 Credit Scores Table
```php
// database/migrations/2024_01_02_000001_create_credit_scores_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('score');
            $table->string('bureau'); // 'creditinfo_jamaica', 'experian', etc.
            $table->string('bureau_reference_id')->nullable();
            $table->json('score_factors')->nullable();
            $table->json('credit_report_summary')->nullable();
            $table->unsignedBigInteger('total_debt_cents')->nullable(); // Store in cents
            $table->integer('open_accounts')->nullable();
            $table->integer('delinquent_accounts')->nullable();
            $table->timestamp('report_date');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'bureau']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_scores');
    }
};
```

### 2.2 Risk Profiles Table
```php
// database/migrations/2024_01_02_000002_create_risk_profiles_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('risk_score'); // Store as percentage * 100 (e.g., 8550 = 85.50%)
            $table->string('grade', 5); // Cast to enum in model
            $table->json('component_scores'); // Breakdown of scoring components
            $table->json('risk_factors'); // Detailed risk factors
            $table->integer('suggested_interest_rate'); // Store as basis points (e.g., 1250 = 12.50%)
            $table->unsignedBigInteger('max_loan_amount_cents'); // Store in cents
            $table->integer('ml_adjustment')->nullable(); // Basis points adjustment
            $table->boolean('requires_manual_review')->default(false);
            $table->string('manual_review_notes')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('user_id');
            $table->index('grade');
            $table->index('calculated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_profiles');
    }
};
```

### 2.3 Risk Parameters Table
```php
// database/migrations/2024_01_02_000003_create_risk_parameters_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('category'); // 'weights', 'thresholds', 'rates'
            $table->string('value_type'); // 'float', 'integer', 'json'
            $table->text('value');
            $table->text('description')->nullable();
            $table->timestamp('effective_from');
            $table->timestamp('effective_until')->nullable();
            $table->unsignedBigInteger('updated_by_user_id');
            $table->timestamps();

            $table->index('category');
            $table->index(['effective_from', 'effective_until']);
            $table->foreign('updated_by_user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_parameters');
    }
};
```

---

## Phase 3: Loan Management

### 3.1 Loans Table
```php
// database/migrations/2024_01_03_000001_create_loans_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->string('loan_number')->unique();
            $table->foreignId('borrower_id')->constrained('users')->onDelete('restrict');
            $table->unsignedBigInteger('requested_amount_cents'); // Store in cents
            $table->unsignedBigInteger('approved_amount_cents')->nullable();
            $table->unsignedBigInteger('funded_amount_cents')->default(0);
            $table->integer('term_months');
            $table->integer('interest_rate'); // Store as basis points (1250 = 12.50%)
            $table->unsignedBigInteger('origination_fee_cents')->default(0);
            $table->string('status', 20)->default('draft'); // Cast to enum in model
            $table->string('purpose', 30); // Cast to enum in model
            $table->text('purpose_description');
            $table->string('risk_grade', 5)->nullable();
            $table->integer('risk_score')->nullable(); // Store as percentage * 100

            // Important dates
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('funding_deadline')->nullable();
            $table->timestamp('funded_at')->nullable();
            $table->timestamp('contracted_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamp('first_payment_date')->nullable();
            $table->timestamp('maturity_date')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Balances (all in cents)
            $table->unsignedBigInteger('principal_balance_cents')->default(0);
            $table->unsignedBigInteger('interest_accrued_cents')->default(0);
            $table->unsignedBigInteger('fees_balance_cents')->default(0);
            $table->unsignedBigInteger('total_paid_cents')->default(0);

            $table->timestamps();

            $table->index('loan_number');
            $table->index('borrower_id');
            $table->index('status');
            $table->index('risk_grade');
            $table->index(['funding_deadline', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
```

### 3.2 Loan Bids Table
```php
// database/migrations/2024_01_03_000002_create_loan_bids_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->foreignId('lender_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('amount_cents'); // Store in cents
            $table->integer('percentage_of_loan'); // Store as basis points (5000 = 50.00%)
            $table->string('status', 20); // Cast to enum in model
            $table->boolean('auto_invest')->default(false);
            $table->timestamp('placed_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->string('withdrawal_reason')->nullable();
            $table->timestamps();

            $table->index(['loan_id', 'status']);
            $table->index(['lender_id', 'status']);
            $table->unique(['loan_id', 'lender_id', 'status']); // One active bid per lender per loan
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_bids');
    }
};
```

### 3.3 Loan Schedules Table
```php
// database/migrations/2024_01_03_000003_create_loan_schedules_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->integer('payment_number');
            $table->date('due_date');
            $table->unsignedBigInteger('principal_amount_cents');
            $table->unsignedBigInteger('interest_amount_cents');
            $table->unsignedBigInteger('total_amount_cents');
            $table->unsignedBigInteger('principal_balance_cents'); // Balance after payment
            $table->string('status', 20); // Cast to enum in model
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('amount_paid_cents')->default(0);
            $table->timestamps();

            $table->index(['loan_id', 'due_date']);
            $table->index(['loan_id', 'status']);
            $table->unique(['loan_id', 'payment_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_schedules');
    }
};
```

---

## Phase 4: Wallet & Transactions

### 4.1 Wallets Table
```php
// database/migrations/2024_01_04_000001_create_wallets_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->string('wallet_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('available_balance_cents')->default(0);
            $table->unsignedBigInteger('pending_balance_cents')->default(0);
            $table->unsignedBigInteger('invested_balance_cents')->default(0);
            $table->unsignedBigInteger('reserved_balance_cents')->default(0);
            $table->unsignedBigInteger('total_deposits_cents')->default(0);
            $table->unsignedBigInteger('total_withdrawals_cents')->default(0);
            $table->unsignedBigInteger('total_earnings_cents')->default(0);
            $table->string('currency', 3)->default('JMD'); // Jamaican Dollar default
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('wallet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
```

### 4.2 Wallet Transactions Table
```php
// database/migrations/2024_01_04_000002_create_wallet_transactions_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->string('type', 30); // Cast to enum in model
            $table->string('direction', 10); // 'credit' or 'debit'
            $table->unsignedBigInteger('amount_cents');
            $table->unsignedBigInteger('balance_before_cents');
            $table->unsignedBigInteger('balance_after_cents');
            $table->string('reference_type')->nullable(); // 'loan', 'payment', 'withdrawal_request'
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description');
            $table->string('status', 20); // Cast to enum in model
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'type']);
            $table->index('transaction_id');
            $table->index(['reference_type', 'reference_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
```

---

## Phase 5: Payments & Processing

### 5.1 Payments Table
```php
// database/migrations/2024_01_05_000001_create_payments_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference')->unique();
            $table->foreignId('loan_id')->constrained()->onDelete('restrict');
            $table->foreignId('schedule_id')->nullable()->constrained('loan_schedules');
            $table->unsignedBigInteger('amount_cents');
            $table->unsignedBigInteger('principal_amount_cents')->default(0);
            $table->unsignedBigInteger('interest_amount_cents')->default(0);
            $table->unsignedBigInteger('fee_amount_cents')->default(0);
            $table->string('type', 20); // Cast to enum in model
            $table->string('method', 20); // Cast to enum in model
            $table->string('status', 20); // Cast to enum in model
            $table->string('gateway')->nullable(); // 'stripe', 'manual', etc.
            $table->string('gateway_reference')->nullable();
            $table->json('gateway_response')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('processed_by_user_id')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['loan_id', 'status']);
            $table->index('payment_reference');
            $table->index('gateway_reference');
            $table->foreign('processed_by_user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
```

### 5.2 Payment Distributions Table
```php
// database/migrations/2024_01_05_000002_create_payment_distributions_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('lender_id')->constrained('users');
            $table->integer('investment_percentage'); // Store as basis points
            $table->unsignedBigInteger('principal_amount_cents');
            $table->unsignedBigInteger('interest_amount_cents');
            $table->unsignedBigInteger('fee_amount_cents')->default(0);
            $table->unsignedBigInteger('platform_fee_cents')->default(0);
            $table->unsignedBigInteger('net_amount_cents'); // Amount credited to lender
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions');
            $table->timestamp('distributed_at')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'lender_id']);
            $table->index('distributed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_distributions');
    }
};
```

---

## Phase 6: Documents & Contracts

### 6.1 Documents Table
```php
// database/migrations/2024_01_06_000001_create_documents_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->morphs('documentable'); // Polymorphic relation
            $table->foreignId('uploaded_by_user_id')->constrained('users');
            $table->string('type', 30); // Cast to enum in model
            $table->string('name');
            $table->string('file_path');
            $table->string('file_size');
            $table->string('mime_type');
            $table->string('hash')->nullable(); // For integrity verification
            $table->string('status', 20); // Cast to enum in model
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('verified_by_user_id')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['documentable_type', 'documentable_id']);
            $table->index('type');
            $table->index('status');
            $table->foreign('verified_by_user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
```

### 6.2 Loan Contracts Table
```php
// database/migrations/2024_01_06_000002_create_loan_contracts_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->string('contract_number')->unique();
            $table->string('template_version');
            $table->string('document_url');
            $table->string('status', 20); // Cast to enum in model
            $table->string('signing_provider'); // 'docusign', 'signnow'
            $table->string('envelope_id')->nullable(); // Provider's reference
            $table->json('signers'); // Array of signers and their status
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('signed_by_borrower_at')->nullable();
            $table->timestamp('signed_by_platform_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->json('contract_terms'); // Snapshot of loan terms
            $table->timestamps();

            $table->index('loan_id');
            $table->index('contract_number');
            $table->index('status');
            $table->index('envelope_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_contracts');
    }
};
```

---

## Phase 7: Feature Flags & Notifications

### 7.1 Laravel Pennant Feature Flags
```php
// database/migrations/2024_01_07_000001_create_features_table.php
// This migration is automatically created by Laravel Pennant

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('scope');
            $table->text('value');
            $table->timestamps();

            $table->unique(['name', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
```

### 7.2 Notifications Table
```php
// database/migrations/2024_01_07_000002_create_notifications_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notification_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'loan_funded', 'payment_received', etc.
            $table->string('channel'); // 'database', 'mail', 'sms'
            $table->string('subject');
            $table->text('content');
            $table->json('data')->nullable();
            $table->string('priority', 10); // Cast to enum in model
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index('type');
            $table->index('notification_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
```

### 7.3 Audit Logs Table
```php
// database/migrations/2024_01_07_000003_create_audit_logs_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action'); // 'create', 'update', 'delete', 'view'
            $table->string('entity_type'); // Model class name
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('user_id');
            $table->index('action');
            $table->index('performed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

---

## Phase 8: Advanced Features

### 8.1 Auto Invest Profiles
```php
// database/migrations/2024_01_08_000001_create_auto_invest_profiles_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_invest_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lender_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('min_investment_cents')->default(2500); // $25 in cents
            $table->unsignedBigInteger('max_investment_cents')->default(100000); // $1000 in cents
            $table->integer('max_portfolio_allocation'); // Store as basis points (1000 = 10%)
            $table->json('risk_grades'); // ['A+', 'A', 'B']
            $table->integer('min_interest_rate')->nullable(); // Basis points
            $table->integer('max_interest_rate')->nullable(); // Basis points
            $table->json('loan_purposes')->nullable(); // Specific purposes to invest in
            $table->integer('min_term_months')->nullable();
            $table->integer('max_term_months')->nullable();
            $table->integer('priority')->default(0); // For multiple profiles
            $table->timestamps();

            $table->index(['lender_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_invest_profiles');
    }
};
```

### 8.2 Fraud Detection Logs
```php
// database/migrations/2024_01_08_000002_create_fraud_detection_logs_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fraud_detection_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject'); // User or LoanRequest
            $table->integer('fraud_score'); // Store as percentage * 100
            $table->string('risk_level', 20); // Cast to enum in model
            $table->json('triggered_rules');
            $table->json('detection_data');
            $table->boolean('requires_review');
            $table->boolean('was_blocked');
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('review_decision', 20)->nullable(); // Cast to enum in model
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('risk_level');
            $table->index('requires_review');
            $table->foreign('reviewed_by_user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_detection_logs');
    }
};
```

### 8.3 Currency Exchange Rates (for multi-currency support)
```php
// database/migrations/2024_01_08_000003_create_currency_rates_table.php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3); // JMD, TTD, BBD, etc.
            $table->string('to_currency', 3);
            $table->decimal('rate', 20, 10); // High precision for accuracy
            $table->string('provider'); // 'bank_of_jamaica', 'xe', etc.
            $table->timestamp('effective_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency', 'effective_at']);
            $table->index(['from_currency', 'to_currency']);
            $table->index('effective_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
```

---

## PHP Enum Classes

### User Enums
```php
// app/Enums/UserType.php
namespace App\Enums;

enum UserType: string
{
    case BORROWER = 'borrower';
    case LENDER = 'lender';
    case BACK_OFFICE = 'back_office';
    case GLOBAL_ADMIN = 'global_admin';
}

// app/Enums/UserStatus.php
namespace App\Enums;

enum UserStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case BLOCKED = 'blocked';
    case CLOSED = 'closed';
}

// app/Enums/KycStatus.php
namespace App\Enums;

enum KycStatus: string
{
    case PENDING = 'pending';
    case IN_REVIEW = 'in_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
}
```

### Loan Enums
```php
// app/Enums/LoanStatus.php
namespace App\Enums;

enum LoanStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case FUNDING = 'funding';
    case FUNDED = 'funded';
    case CONTRACTED = 'contracted';
    case DISBURSED = 'disbursed';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case DEFAULTED = 'defaulted';
    case WRITTEN_OFF = 'written_off';
}

// app/Enums/LoanPurpose.php
namespace App\Enums;

enum LoanPurpose: string
{
    case DEBT_CONSOLIDATION = 'debt_consolidation';
    case HOME_IMPROVEMENT = 'home_improvement';
    case MEDICAL = 'medical';
    case EDUCATION = 'education';
    case BUSINESS = 'business';
    case AUTO = 'auto';
    case OTHER = 'other';
}
```

### Model Casting Example
```php
// app/Models/User.php
namespace App\Models;

use App\Enums\UserType;
use App\Enums\UserStatus;
use App\Enums\KycStatus;

class User extends Authenticatable
{
    protected $casts = [
        'user_type' => UserType::class,
        'status' => UserStatus::class,
        'kyc_status' => KycStatus::class,
        'email_verified_at' => 'datetime',
        'kyc_verified_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];
}
```

---

## Money Handling with Money Library

### Installation
```bash
composer require moneyphp/money
composer require cknow/laravel-money
```

### Model Casts Example
```php
// app/Models/Loan.php
namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\LoanStatus;
use App\Enums\LoanPurpose;

class Loan extends Model
{
    protected $casts = [
        'requested_amount_cents' => MoneyCast::class,
        'approved_amount_cents' => MoneyCast::class,
        'funded_amount_cents' => MoneyCast::class,
        'origination_fee_cents' => MoneyCast::class,
        'principal_balance_cents' => MoneyCast::class,
        'interest_accrued_cents' => MoneyCast::class,
        'fees_balance_cents' => MoneyCast::class,
        'total_paid_cents' => MoneyCast::class,
        'status' => LoanStatus::class,
        'purpose' => LoanPurpose::class,
        'interest_rate' => 'integer', // Basis points
    ];

    // Accessor for interest rate as percentage
    public function getInterestRatePercentAttribute(): float
    {
        return $this->interest_rate / 100;
    }

    // Mutator for interest rate from percentage
    public function setInterestRatePercentAttribute(float $value): void
    {
        $this->attributes['interest_rate'] = (int) ($value * 100);
    }
}
```

### Custom Money Cast
```php
// app/Casts/MoneyCast.php
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

        // Determine currency from model or default to JMD
        $currency = $model->currency ?? 'JMD';

        return new Money($value, new Currency($currency));
    }

    public function set($model, string $key, $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return (int) $value->getAmount();
        }

        // If passing a float/int, assume it's already in cents
        return (int) $value;
    }
}
```

---

## Laravel Pennant Feature Flags Usage

### Feature Definition
```php
// app/Providers/AppServiceProvider.php
use Laravel\Pennant\Feature;
use App\Models\User;

public function boot()
{
    Feature::define('auto-invest', function (User $user) {
        return $user->user_type === UserType::LENDER &&
               $user->wallet->available_balance_cents > 10000;
    });

    Feature::define('advanced-risk-scoring', function (User $user) {
        return $user->created_at > now()->subDays(30);
    });

    Feature::define('multi-currency', function () {
        return config('app.env') === 'production';
    });

    Feature::define('secondary-market', function () {
        return false; // Not yet implemented
    });
}
```

### Usage in Code
```php
// In controllers or services
if (Feature::active('auto-invest')) {
    // Show auto-invest features
}

// In Blade templates
@feature('multi-currency')
    <select name="currency">
        <option value="JMD">JMD</option>
        <option value="USD">USD</option>
    </select>
@endfeature
```

---

## Database Optimization for Caribbean Market

### Regional Indexes
```sql
-- Optimized for Caribbean countries
CREATE INDEX idx_profiles_caribbean ON profiles(country, parish_state, city);

-- TRN lookups
CREATE INDEX idx_profiles_trn ON profiles(trn_hash);

-- Currency-specific queries
CREATE INDEX idx_wallets_currency ON wallets(currency, user_id);

-- Regional loan analysis
CREATE INDEX idx_loans_by_region ON loans(borrower_id, status)
    WHERE status IN ('active', 'completed');
```

### Partitioning Strategy for Multi-Territory
```sql
-- Partition by country if expanding to multiple Caribbean territories
ALTER TABLE profiles
PARTITION BY LIST (country) (
    PARTITION p_jamaica VALUES IN ('JM'),
    PARTITION p_trinidad VALUES IN ('TT'),
    PARTITION p_barbados VALUES IN ('BB'),
    PARTITION p_others VALUES IN (DEFAULT)
);
```

---

This migration plan has been updated for the Caribbean market with TRN support, money stored as cents, PHP enums with casts, and Laravel Pennant for feature flags.