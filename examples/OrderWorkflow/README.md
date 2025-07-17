# Order Workflow Examples

This directory contains examples of how to use Laravel Statecraft with Guards, Actions, and Guard Expressions to create complex workflows.

## Files Structure

```
examples/OrderWorkflow/
├── Guards/
│   ├── CanSubmit.php           # Validates order before submission
│   ├── HasMinimumAmount.php    # Checks minimum order amount
│   ├── IsManager.php           # Validates user permissions
│   ├── IsVIP.php              # Checks VIP customer status
│   ├── IsUrgent.php           # Checks urgent order flag
│   ├── IsBlacklisted.php      # Checks customer blacklist status
│   ├── IsCustomer.php         # Validates customer ownership
│   └── IsProcessing.php       # Checks processing status
├── Actions/
│   ├── NotifyReviewer.php      # Sends notification to reviewer
│   ├── ProcessPayment.php      # Handles payment processing
│   └── SendConfirmationEmail.php # Sends confirmation email
├── Models/
│   └── Order.php               # Example Order model
├── advanced-order-workflow.yaml     # Complex workflow with full class names
├── simple-order-workflow.yaml      # Simple workflow with method names
├── guard-expressions-workflow.yaml # Guard expressions examples
└── README.md                       # This file
```

## Workflow Examples

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

### 3. Using Guard Expressions (Advanced)

```yaml
# AND logic - All conditions must be true
- from: pending
  to: approved
  guard:
    and:
      - Examples\OrderWorkflow\Guards\IsManager
      - Examples\OrderWorkflow\Guards\HasMinimumAmount

# OR logic - At least one condition must be true
- from: pending
  to: processing
  guard:
    or:
      - Examples\OrderWorkflow\Guards\IsManager
      - Examples\OrderWorkflow\Guards\IsVIP

# NOT logic - Condition must be false
- from: pending
  to: rejected
  guard:
    not: Examples\OrderWorkflow\Guards\IsManager

# Nested expressions - Complex business logic
- from: approved
  to: processing
  guard:
    and:
      - Examples\OrderWorkflow\Guards\HasMinimumAmount
      - or:
          - Examples\OrderWorkflow\Guards\IsVIP
          - Examples\OrderWorkflow\Guards\IsUrgent
```

## Guard Expressions

Laravel Statecraft supports powerful guard expressions with AND/OR/NOT logic for complex business rules. See `guard-expressions-workflow.yaml` for comprehensive examples.

### Key Features:
- **AND Logic**: All conditions must be true
- **OR Logic**: At least one condition must be true
- **NOT Logic**: Condition must be false
- **Nested Expressions**: Complex combinations supported
- **Backward Compatibility**: Simple string guards still work

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

### IsVIP Guard
Checks if the customer is a VIP customer.

**Requirements:**
- Order `is_vip` attribute must be `true`

### IsUrgent Guard
Checks if the order is marked as urgent.

**Requirements:**
- Order `is_urgent` attribute must be `true`

### IsBlacklisted Guard
Checks if the customer is blacklisted.

**Requirements:**
- Order `customer_blacklisted` attribute must be `true`

### IsCustomer Guard
Validates that the current user is the customer who placed the order.

**Requirements:**
- User must be authenticated
- User ID must match order's `customer_id`

### IsProcessing Guard
Checks if the order is currently being processed.

**Requirements:**
- Order `processing_started_at` must not be null
- Order `processing_completed_at` must be null

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
