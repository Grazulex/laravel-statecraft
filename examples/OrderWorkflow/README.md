# Order Workflow Examples

This directory contains examples of how to use Laravel Statecraft with Guards and Actions to create complex workflows.

## Files Structure

```
examples/OrderWorkflow/
├── Guards/
│   ├── CanSubmit.php           # Validates order before submission
│   ├── HasMinimumAmount.php    # Checks minimum order amount
│   └── IsManager.php           # Validates user permissions
├── Actions/
│   ├── NotifyReviewer.php      # Sends notification to reviewer
│   ├── ProcessPayment.php      # Handles payment processing
│   └── SendConfirmationEmail.php # Sends confirmation email
├── Models/
│   └── Order.php               # Example Order model
├── advanced-order-workflow.yaml # Complex workflow with full class names
├── simple-order-workflow.yaml  # Simple workflow with method names
└── README.md                   # This file
```

## Usage Examples

### 1. Using Full Class Names (Recommended)

```yaml
- from: draft
  to: pending
  guard: Examples\OrderWorkflow\Guards\CanSubmit
  action: Examples\OrderWorkflow\Actions\NotifyReviewer
```

### 2. Using Method Names (Simplified)

```yaml
- from: draft
  to: pending
  guard: canSubmit
  action: notifyReviewer
```

## Guards

### CanSubmit Guard
Validates that an order has all required fields before allowing submission.

**Requirements:**
- `customer_email` must not be empty
- `items` must not be empty
- `items` array must have at least one element

### IsManager Guard
Checks if the current authenticated user is a manager.

**Requirements:**
- User must be authenticated
- User must have `is_manager` attribute set to `true`

### HasMinimumAmount Guard
Validates that an order meets the minimum amount requirement.

**Requirements:**
- Order `amount` must be >= 100

## Actions

### NotifyReviewer Action
Sends a notification to the review team when an order is submitted.

**What it does:**
- Logs the transition
- Simulates sending email to reviewer
- Clears previous review data

### SendConfirmationEmail Action
Sends a confirmation email to the customer when order is approved.

**What it does:**
- Logs the approval
- Simulates sending email to customer
- Records approval timestamp and user

### ProcessPayment Action
Handles payment processing when order is approved.

**What it does:**
- Logs payment processing
- Simulates payment gateway integration
- Updates payment status and timestamp

## Model Integration

The `Order` model demonstrates how to integrate with Laravel Statecraft:

```php
use HasStateMachine, HasStateHistory;

protected function getStateMachineDefinitionName(): string
{
    return 'advanced-order-workflow';
}
```

## Available Methods

Once the trait is added to your model, you get these methods automatically:

```php
$order = new Order();

// Check if transitions are allowed
$order->canSubmit();     // draft -> pending
$order->canApprove();    // pending -> approved
$order->canReject();     // pending -> rejected

// Execute transitions
$order->submit();        // draft -> pending (with guards/actions)
$order->approve();       // pending -> approved (with guards/actions)
$order->reject();        // pending -> rejected (with guards/actions)

// Get current state and available transitions
$order->getCurrentState();
$order->getAvailableTransitions();
```

## Testing

The workflow includes comprehensive tests in `tests/Feature/GuardsAndActionsTest.php`:

- Guard validation tests
- Action execution tests
- Full workflow integration tests

## Key Features Demonstrated

1. **Dynamic Guard Resolution**: Guards are resolved from the container
2. **Dynamic Action Execution**: Actions are executed during transitions
3. **State History**: All transitions are recorded automatically
4. **Event Dispatching**: Events are fired before and after transitions
5. **Flexible Configuration**: Support for both class names and method names
6. **Comprehensive Testing**: Full test coverage for guards and actions

## Extending the Examples

To create your own guards and actions:

1. **Create a Guard:**
```php
class MyGuard implements \Grazulex\LaravelStatecraft\Contracts\Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        // Your validation logic here
        return true;
    }
}
```

2. **Create an Action:**
```php
class MyAction implements \Grazulex\LaravelStatecraft\Contracts\Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        // Your action logic here
    }
}
```

3. **Register in your YAML:**
```yaml
- from: state_a
  to: state_b
  guard: App\Guards\MyGuard
  action: App\Actions\MyAction
```

The Laravel container will automatically resolve and execute your guards and actions!
