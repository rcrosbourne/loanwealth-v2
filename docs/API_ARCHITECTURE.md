# API Architecture & Real-Time Features

## Overview
This document defines the API architecture for the LoanWealth platform, including RESTful endpoints, real-time WebSocket events via Laravel Reverb, and integration patterns.

---

## API Design Principles

### Core Principles
1. **RESTful Design**: Follow REST conventions for resource-based APIs
2. **Consistent Responses**: Standardized response format across all endpoints
3. **Versioning**: API versioning for backward compatibility
4. **Authentication**: Token-based authentication with Laravel Sanctum
5. **Rate Limiting**: Protect against abuse with intelligent rate limits
6. **Real-time Updates**: WebSocket events for live data

### Response Format
```json
{
  "success": true,
  "data": {
    // Response payload
  },
  "meta": {
    "timestamp": "2024-01-15T10:30:00Z",
    "version": "1.0",
    "request_id": "uuid-here"
  },
  "errors": [] // Only present when success is false
}
```

---

## Authentication & Authorization

### Authentication Flow
```php
// app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Requests\LoginRequest;
use App\Services\AuthenticationService;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthenticationService $auth)
    {
        $credentials = $request->validated();

        if (!$token = $auth->attempt($credentials)) {
            return $this->error('Invalid credentials', 401);
        }

        // Log security event
        event(new UserLoggedIn($request->user()));

        return $this->success([
            'user' => new UserResource($request->user()),
            'token' => $token,
            'permissions' => $request->user()->getAllPermissions(),
            'expires_at' => now()->addHours(24),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(['message' => 'Logged out successfully']);
    }

    public function refresh(Request $request)
    {
        $token = $request->user()->createToken('api-token');

        return $this->success([
            'token' => $token->plainTextToken,
            'expires_at' => now()->addHours(24),
        ]);
    }
}
```

### Middleware Configuration
```php
// app/Http/Kernel.php API Middleware Groups

'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
    \App\Http\Middleware\LogApiRequests::class,
    \App\Http\Middleware\CheckAccountStatus::class,
],

'api.authenticated' => [
    'auth:sanctum',
    'verified',
    'kyc.verified',
]
```

---

## Core API Endpoints

### User Management

```php
// routes/api/v1/users.php

Route::prefix('v1')->group(function () {
    // Profile endpoints
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/verify-identity', [KycController::class, 'verifyIdentity']);
    Route::post('/profile/upload-document', [DocumentController::class, 'upload']);

    // Credit information
    Route::get('/credit-score', [CreditController::class, 'show']);
    Route::post('/credit-score/refresh', [CreditController::class, 'refresh']);
    Route::get('/risk-profile', [RiskProfileController::class, 'show']);
});
```

### Borrower Endpoints

```php
// routes/api/v1/borrower.php

Route::prefix('v1/borrower')->middleware(['auth:sanctum', 'role:borrower'])->group(function () {
    // Loan management
    Route::get('/loans', [BorrowerLoanController::class, 'index']);
    Route::post('/loans', [BorrowerLoanController::class, 'store']);
    Route::get('/loans/{loan}', [BorrowerLoanController::class, 'show']);
    Route::put('/loans/{loan}', [BorrowerLoanController::class, 'update'])->middleware('can:update,loan');
    Route::post('/loans/{loan}/submit', [BorrowerLoanController::class, 'submit']);
    Route::post('/loans/{loan}/cancel', [BorrowerLoanController::class, 'cancel']);
    Route::post('/loans/{loan}/accept-terms', [BorrowerLoanController::class, 'acceptTerms']);

    // Loan calculator
    Route::post('/calculator', [LoanCalculatorController::class, 'calculate']);

    // Payments
    Route::get('/payments', [BorrowerPaymentController::class, 'index']);
    Route::get('/payments/schedule', [BorrowerPaymentController::class, 'schedule']);
    Route::post('/payments/upload-proof', [BorrowerPaymentController::class, 'uploadProof']);

    // Documents
    Route::get('/documents', [BorrowerDocumentController::class, 'index']);
    Route::post('/documents', [BorrowerDocumentController::class, 'upload']);
    Route::delete('/documents/{document}', [BorrowerDocumentController::class, 'destroy']);
});
```

### Lender Endpoints

```php
// routes/api/v1/lender.php

Route::prefix('v1/lender')->middleware(['auth:sanctum', 'role:lender'])->group(function () {
    // Marketplace
    Route::get('/marketplace', [MarketplaceController::class, 'index']);
    Route::get('/marketplace/filters', [MarketplaceController::class, 'filters']);
    Route::get('/marketplace/loans/{loan}', [MarketplaceController::class, 'show']);

    // Bidding
    Route::post('/loans/{loan}/bid', [BidController::class, 'place']);
    Route::put('/bids/{bid}', [BidController::class, 'update']);
    Route::delete('/bids/{bid}', [BidController::class, 'withdraw']);
    Route::get('/my-bids', [BidController::class, 'index']);

    // Portfolio
    Route::get('/portfolio', [PortfolioController::class, 'summary']);
    Route::get('/portfolio/investments', [PortfolioController::class, 'investments']);
    Route::get('/portfolio/returns', [PortfolioController::class, 'returns']);
    Route::get('/portfolio/performance', [PortfolioController::class, 'performance']);

    // Wallet
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('/wallet/deposit', [WalletController::class, 'deposit']);
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);

    // Auto-invest
    Route::get('/auto-invest', [AutoInvestController::class, 'index']);
    Route::post('/auto-invest', [AutoInvestController::class, 'store']);
    Route::put('/auto-invest/{profile}', [AutoInvestController::class, 'update']);
    Route::delete('/auto-invest/{profile}', [AutoInvestController::class, 'destroy']);
    Route::post('/auto-invest/{profile}/toggle', [AutoInvestController::class, 'toggle']);
});
```

### Admin Endpoints

```php
// routes/api/v1/admin.php

Route::prefix('v1/admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // User management
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{user}', [AdminUserController::class, 'show']);
    Route::put('/users/{user}/status', [AdminUserController::class, 'updateStatus']);
    Route::post('/users/{user}/verify-kyc', [AdminUserController::class, 'verifyKyc']);
    Route::post('/users/{user}/block', [AdminUserController::class, 'block']);

    // Loan management
    Route::get('/loans', [AdminLoanController::class, 'index']);
    Route::get('/loans/{loan}', [AdminLoanController::class, 'show']);
    Route::put('/loans/{loan}/review', [AdminLoanController::class, 'review']);
    Route::post('/loans/{loan}/approve', [AdminLoanController::class, 'approve']);
    Route::post('/loans/{loan}/reject', [AdminLoanController::class, 'reject']);

    // Payment management
    Route::get('/payments', [AdminPaymentController::class, 'index']);
    Route::post('/payments/{payment}/verify', [AdminPaymentController::class, 'verify']);
    Route::post('/payments/{payment}/reverse', [AdminPaymentController::class, 'reverse']);

    // Risk management
    Route::get('/risk-parameters', [RiskParametersController::class, 'index']);
    Route::put('/risk-parameters', [RiskParametersController::class, 'update']);
    Route::get('/fraud-logs', [FraudController::class, 'index']);
    Route::post('/fraud-logs/{log}/review', [FraudController::class, 'review']);

    // Reports
    Route::get('/reports/dashboard', [ReportsController::class, 'dashboard']);
    Route::get('/reports/loans', [ReportsController::class, 'loans']);
    Route::get('/reports/users', [ReportsController::class, 'users']);
    Route::get('/reports/revenue', [ReportsController::class, 'revenue']);
    Route::get('/reports/export', [ReportsController::class, 'export']);
});
```

---

## Real-Time Features with Laravel Reverb

### WebSocket Connection Setup

```javascript
// resources/js/services/websocket.js

import Echo from 'laravel-echo';
import Reverb from '@laravel/reverb';

window.Reverb = Reverb;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`,
        },
    },
});

export default echo;
```

### Channel Configuration

```php
// routes/channels.php

use App\Models\User;
use App\Models\Loan;

// Private user channel for notifications
Broadcast::channel('user.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

// Loan funding channel for real-time bidding
Broadcast::channel('loan.{loan}.funding', function (User $user, Loan $loan) {
    return $user->canViewLoan($loan);
});

// Marketplace updates channel
Broadcast::channel('marketplace', function (User $user) {
    return $user->hasRole('lender');
});

// Admin monitoring channel
Broadcast::channel('admin.monitoring', function (User $user) {
    return $user->hasRole(['admin', 'back_office']);
});

// Portfolio updates for lenders
Broadcast::channel('portfolio.{userId}', function (User $user, $userId) {
    return (int) $user->id === (int) $userId && $user->hasRole('lender');
});
```

### Broadcasting Events

```php
// app/Events/LoanFundingUpdated.php

namespace App\Events;

use App\Models\Loan;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LoanFundingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Loan $loan,
        public array $fundingData
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('loan.' . $this->loan->id . '.funding'),
            new PrivateChannel('marketplace'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'funding.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'loan_id' => $this->loan->id,
            'funded_amount' => $this->loan->funded_amount,
            'funded_percentage' => $this->loan->getFundedPercentage(),
            'remaining_amount' => $this->loan->getRemainingAmount(),
            'bid_count' => $this->loan->bids()->count(),
            'expires_at' => $this->loan->funding_deadline,
            'latest_bid' => $this->fundingData['latest_bid'] ?? null,
        ];
    }
}
```

### React Component Integration

```jsx
// resources/js/Components/Loan/FundingProgress.jsx

import React, { useEffect, useState } from 'react';
import echo from '@/services/websocket';

export default function FundingProgress({ loan }) {
    const [fundingData, setFundingData] = useState({
        funded_amount: loan.funded_amount,
        funded_percentage: loan.funded_percentage,
        bid_count: loan.bid_count,
    });

    useEffect(() => {
        // Subscribe to loan-specific channel
        const channel = echo.private(`loan.${loan.id}.funding`);

        channel.listen('.funding.updated', (e) => {
            setFundingData({
                funded_amount: e.funded_amount,
                funded_percentage: e.funded_percentage,
                bid_count: e.bid_count,
            });

            // Show notification
            if (e.latest_bid) {
                showNotification(`New bid: $${e.latest_bid.amount}`);
            }
        });

        // Subscribe to bid events
        channel.listen('.bid.placed', (e) => {
            updateLocalState(e);
        });

        channel.listen('.bid.withdrawn', (e) => {
            updateLocalState(e);
        });

        return () => {
            echo.leave(`loan.${loan.id}.funding`);
        };
    }, [loan.id]);

    return (
        <div className="funding-progress">
            <div className="progress-bar">
                <div
                    className="progress-fill"
                    style={{ width: `${fundingData.funded_percentage}%` }}
                />
            </div>
            <div className="funding-stats">
                <span>${fundingData.funded_amount} raised</span>
                <span>{fundingData.bid_count} bids</span>
                <span>{fundingData.funded_percentage}% funded</span>
            </div>
        </div>
    );
}
```

### Real-Time Notifications

```php
// app/Notifications/LoanFundedNotification.php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class LoanFundedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Loan $loan
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'loan_funded',
            'title' => 'Your loan has been fully funded!',
            'message' => "Loan #{$this->loan->loan_number} has reached 100% funding.",
            'loan_id' => $this->loan->id,
            'action_url' => route('loans.show', $this->loan),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'loan_funded',
            'loan_id' => $this->loan->id,
            'loan_number' => $this->loan->loan_number,
            'funded_amount' => $this->loan->funded_amount,
            'message' => "Your loan #{$this->loan->loan_number} has been fully funded.",
        ];
    }
}
```

---

## API Rate Limiting

```php
// app/Providers/RouteServiceProvider.php

protected function configureRateLimiting(): void
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    RateLimiter::for('auth', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });

    RateLimiter::for('payments', function (Request $request) {
        return Limit::perMinute(10)->by($request->user()->id);
    });

    RateLimiter::for('documents', function (Request $request) {
        return [
            Limit::perMinute(5)->by($request->user()->id),
            Limit::perDay(50)->by($request->user()->id),
        ];
    });

    RateLimiter::for('bidding', function (Request $request) {
        return Limit::perMinute(30)->by($request->user()->id);
    });
}
```

---

## Error Handling

```php
// app/Exceptions/Handler.php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $this->renderable(function (HttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'code' => $e->getStatusCode(),
                ], $e->getStatusCode());
            }
        });

        $this->renderable(function (\Exception $e, $request) {
            if ($request->is('api/*')) {
                $statusCode = method_exists($e, 'getStatusCode')
                    ? $e->getStatusCode()
                    : 500;

                $message = config('app.debug')
                    ? $e->getMessage()
                    : 'An error occurred processing your request';

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'code' => $statusCode,
                ], $statusCode);
            }
        });
    }
}
```

---

## API Documentation

### OpenAPI Specification

```yaml
# openapi.yaml
openapi: 3.0.0
info:
  title: LoanWealth API
  version: 1.0.0
  description: P2P Lending Platform API

servers:
  - url: https://api.loanwealth.com/api/v1
    description: Production server
  - url: https://staging.api.loanwealth.com/api/v1
    description: Staging server

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT

  schemas:
    Loan:
      type: object
      properties:
        id:
          type: integer
        loan_number:
          type: string
        amount:
          type: number
        term_months:
          type: integer
        interest_rate:
          type: number
        status:
          type: string
          enum: [draft, funding, active, completed]
        risk_grade:
          type: string
        funded_percentage:
          type: number

paths:
  /borrower/loans:
    get:
      summary: List borrower's loans
      security:
        - bearerAuth: []
      responses:
        '200':
          description: Successful response
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Loan'

  /borrower/loans/{id}:
    get:
      summary: Get loan details
      security:
        - bearerAuth: []
      parameters:
        - name: id
          in: path
          required: true
          schema:
            type: integer
      responses:
        '200':
          description: Loan details
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    $ref: '#/components/schemas/Loan'
```

---

## Testing API Endpoints

```php
// tests/Feature/Api/BorrowerLoanTest.php

use App\Models\User;
use App\Models\Loan;

it('allows borrower to create loan request', function () {
    $borrower = User::factory()->borrower()->create();

    $response = $this->actingAs($borrower)
        ->postJson('/api/v1/borrower/loans', [
            'amount' => 10000,
            'term_months' => 12,
            'purpose' => 'debt_consolidation',
            'purpose_description' => 'Consolidating credit card debt',
        ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'amount' => 10000,
                'status' => 'draft',
            ],
        ]);

    expect($borrower->loans)->toHaveCount(1);
});

it('broadcasts funding updates in real-time', function () {
    Event::fake([LoanFundingUpdated::class]);

    $loan = Loan::factory()->inFunding()->create();
    $lender = User::factory()->lender()->create();

    $response = $this->actingAs($lender)
        ->postJson("/api/v1/lender/loans/{$loan->id}/bid", [
            'amount' => 100,
        ]);

    $response->assertSuccessful();

    Event::assertDispatched(LoanFundingUpdated::class, function ($event) use ($loan) {
        return $event->loan->id === $loan->id;
    });
});
```

This comprehensive API architecture provides a solid foundation for the LoanWealth platform with RESTful endpoints, real-time features via Laravel Reverb, proper authentication, and extensive documentation.