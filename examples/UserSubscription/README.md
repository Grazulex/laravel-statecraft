# User Subscription Example

This example demonstrates a subscription lifecycle management system using Laravel Statecraft.

## Overview

This example shows how to implement a subscription system with:
- Trial period management
- Payment processing integration
- Subscription lifecycle tracking
- Event-driven state changes

## States

- **trial**: User is in trial period
- **active**: Subscription is active and paid
- **suspended**: Subscription suspended due to payment failure
- **cancelled**: Subscription cancelled by user or system

## Workflow

```
trial → active → suspended → cancelled
  ↓       ↓         ↓
  ↓       ↓         ↓
  ↓       ↓         active (payment resolved)
  ↓       cancelled
  cancelled
```

## Files

- `subscription-workflow.yaml` - Workflow configuration
- `Guards/` - Guard implementations
- `Actions/` - Action implementations
- `Models/Subscription.php` - Example model

## Quick Start

1. Copy the YAML file to your state machines directory:
```bash
cp subscription-workflow.yaml database/state_machines/
```

2. Set up your Subscription model:
```php
use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Grazulex\LaravelStatecraft\Traits\HasStateHistory;

class Subscription extends Model
{
    use HasStateMachine, HasStateHistory;
    
    protected function getStateMachineDefinitionName(): string
    {
        return 'subscription-workflow';
    }
}
```

3. Use the workflow:
```php
$subscription = Subscription::create([
    'user_id' => $user->id,
    'plan_id' => $plan->id,
    'trial_ends_at' => now()->addDays(14),
]);

// Activate subscription (after payment)
if ($subscription->canActivate()) {
    $subscription->activate();
}

// Suspend for failed payment
if ($subscription->canSuspend()) {
    $subscription->suspend();
}
```

## Guards

### TrialNotExpired
Checks if trial period hasn't expired yet.

### HasValidPayment
Verifies payment information is valid and current.

### CanProcessPayment
Ensures payment can be processed for activation.

## Actions

### SendWelcomeEmail
Sends welcome email when subscription is activated.

### ProcessPayment
Handles payment processing when activating subscription.

### NotifyPaymentFailure
Notifies user when payment fails and subscription is suspended.

### CleanupSubscription
Cleans up subscription data when cancelled.

## Usage Examples

### Trial to Active

```php
// User completes trial signup
$subscription = Subscription::create([
    'user_id' => $user->id,
    'plan_id' => $plan->id,
    'status' => 'trial',
    'trial_ends_at' => now()->addDays(14),
]);

// User provides payment information
$subscription->payment_method = 'card_xxx';
$subscription->save();

// Activate subscription
if ($subscription->canActivate()) {
    $subscription->activate(); // Processes payment and sends welcome email
}
```

### Payment Failure and Recovery

```php
// Payment fails - suspend subscription
$subscription = Subscription::find(1);

if ($subscription->canSuspend()) {
    $subscription->suspend(); // Notifies user of payment failure
}

// User updates payment method
$subscription->payment_method = 'card_yyy';
$subscription->save();

// Reactivate subscription
if ($subscription->canActivate()) {
    $subscription->activate(); // Processes payment and reactivates
}
```

### Cancellation

```php
// User cancels subscription
$subscription = Subscription::find(1);

if ($subscription->canCancel()) {
    $subscription->cancel(); // Cleans up and processes cancellation
}

// Or system cancels after failed reactivation attempts
if ($subscription->reactivation_attempts >= 3) {
    $subscription->cancel();
}
```

## Event Integration

This example heavily uses Laravel events for business logic:

```php
// Listen for subscription events
Event::listen(StateTransitioned::class, function ($event) {
    if ($event->model instanceof Subscription) {
        match ($event->to) {
            'active' => $this->handleSubscriptionActivated($event->model),
            'suspended' => $this->handleSubscriptionSuspended($event->model),
            'cancelled' => $this->handleSubscriptionCancelled($event->model),
        };
    }
});
```

## Configuration

```yaml
# subscription-workflow.yaml
state_machine:
  name: subscription-workflow
  model: App\Models\Subscription
  field: status
  states: [trial, active, suspended, cancelled]
  initial: trial
  transitions:
    # Trial to Active (user provides payment)
    - from: trial
      to: active
      guard: Examples\UserSubscription\Guards\HasValidPayment
      action: Examples\UserSubscription\Actions\ProcessPayment
    
    # Trial to Cancelled (trial expires without payment)
    - from: trial
      to: cancelled
      action: Examples\UserSubscription\Actions\CleanupSubscription
    
    # Active to Suspended (payment failure)
    - from: active
      to: suspended
      action: Examples\UserSubscription\Actions\NotifyPaymentFailure
    
    # Suspended to Active (payment recovered)
    - from: suspended
      to: active
      guard: Examples\UserSubscription\Guards\HasValidPayment
      action: Examples\UserSubscription\Actions\ProcessPayment
    
    # Suspended to Cancelled (failed to recover payment)
    - from: suspended
      to: cancelled
      action: Examples\UserSubscription\Actions\CleanupSubscription
    
    # Active to Cancelled (user cancels)
    - from: active
      to: cancelled
      action: Examples\UserSubscription\Actions\CleanupSubscription
```

## Database Schema

```php
// Migration for subscriptions table
Schema::create('subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->foreignId('plan_id')->constrained();
    $table->string('status')->default('trial');
    $table->string('payment_method')->nullable();
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3)->default('USD');
    $table->timestamp('trial_ends_at')->nullable();
    $table->timestamp('current_period_start')->nullable();
    $table->timestamp('current_period_end')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    $table->integer('reactivation_attempts')->default(0);
    $table->json('metadata')->nullable();
    $table->timestamps();
    
    $table->index(['user_id', 'status']);
    $table->index(['status', 'current_period_end']);
});
```

## Testing

```php
public function test_trial_can_be_activated_with_payment(): void
{
    $subscription = Subscription::factory()->create([
        'status' => 'trial',
        'payment_method' => 'card_xxx',
    ]);
    
    StateMachineTester::assertTransitionAllowed($subscription, 'active');
    StateMachineTester::assertCanExecuteMethod($subscription, 'activate');
}

public function test_active_subscription_can_be_suspended(): void
{
    $subscription = Subscription::factory()->create(['status' => 'active']);
    
    StateMachineTester::assertTransitionAllowed($subscription, 'suspended');
    StateMachineTester::assertCanExecuteMethod($subscription, 'suspend');
}

public function test_subscription_lifecycle(): void
{
    $subscription = Subscription::factory()->create();
    
    // Start in trial
    StateMachineTester::assertInState($subscription, 'trial');
    
    // Activate
    $subscription->payment_method = 'card_xxx';
    $subscription->save();
    $subscription->activate();
    
    StateMachineTester::assertInState($subscription, 'active');
    
    // Suspend
    $subscription->suspend();
    
    StateMachineTester::assertInState($subscription, 'suspended');
    
    // Cancel
    $subscription->cancel();
    
    StateMachineTester::assertInState($subscription, 'cancelled');
}
```

## Business Logic Integration

### Automatic Trial Expiry

```php
// Command to handle trial expiry
class ExpireTrialSubscriptions extends Command
{
    public function handle(): void
    {
        $expiredTrials = Subscription::where('status', 'trial')
            ->where('trial_ends_at', '<', now())
            ->get();
        
        foreach ($expiredTrials as $subscription) {
            if ($subscription->canCancel()) {
                $subscription->cancel();
            }
        }
    }
}
```

### Payment Retry Logic

```php
// Job to retry failed payments
class RetryFailedPayment implements ShouldQueue
{
    public function __construct(
        private Subscription $subscription
    ) {}
    
    public function handle(): void
    {
        if ($this->subscription->status === 'suspended') {
            $this->subscription->increment('reactivation_attempts');
            
            if ($this->subscription->reactivation_attempts >= 3) {
                $this->subscription->cancel();
            } else {
                // Retry payment
                if ($this->subscription->canActivate()) {
                    $this->subscription->activate();
                }
            }
        }
    }
}
```

### Subscription Analytics

```php
class SubscriptionAnalytics
{
    public function getChurnRate(): float
    {
        $total = Subscription::count();
        $cancelled = Subscription::where('status', 'cancelled')->count();
        
        return $total > 0 ? ($cancelled / $total) * 100 : 0;
    }
    
    public function getTrialConversionRate(): float
    {
        $trials = Subscription::where('status', 'trial')->count();
        $active = Subscription::where('status', 'active')->count();
        
        return $trials > 0 ? ($active / $trials) * 100 : 0;
    }
}
```

## Real-World Integrations

### Stripe Integration

```php
class ProcessStripePayment implements Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        
        try {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $model->amount * 100, // Convert to cents
                'currency' => $model->currency,
                'customer' => $model->user->stripe_customer_id,
                'payment_method' => $model->payment_method,
                'confirm' => true,
            ]);
            
            $model->update([
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'subscription_id' => $model->id,
                'error' => $e->getMessage(),
            ]);
            
            throw new PaymentProcessingException('Payment failed');
        }
    }
}
```

This example demonstrates how Laravel Statecraft can handle complex business workflows with proper state management, event integration, and real-world payment processing scenarios.