# Guards and Actions - Dynamic Implementation

## Overview

Laravel Statecraft provides a powerful system for **dynamic Guards and Actions**, allowing users to define custom conditions and behaviors in YAML transitions.

## Features Implemented

### 1. **Contracts (Interfaces)**
- ✅ `Guard` interface for conditions
- ✅ `Action` interface for behaviors
- ✅ Complete documentation with PHPDoc

### 2. **Dynamic Resolution**
- ✅ Automatic resolution via Laravel Container
- ✅ Interface validation for guards and actions
- ✅ Proper error handling

### 3. **Complete Examples**
- ✅ 3 Guard examples: `IsManager`, `CanSubmit`, `HasMinimumAmount`
- ✅ 3 Action examples: `NotifyReviewer`, `SendConfirmationEmail`, `ProcessPayment`
- ✅ Example `Order` model with integrated traits

### 4. **YAML Configuration**
- ✅ Support for full class names: `App\Guards\IsManager`
- ✅ Support for short methods: `canSubmit`, `notifyReviewer`
- ✅ 2 example workflows: simple and advanced

### 5. **Complete Tests**
- ✅ Unit tests for each guard and action
- ✅ Integration tests with StateMachineManager
- ✅ Dynamic resolution tests
- ✅ **14 new tests** - all passing ✅

## 🏗️ Architecture

### Resolution Flow
```
YAML Definition → StateMachineManager → Container → Guard/Action Instance → Execution
```

### Usage Example

#### In YAML:
```yaml
- from: draft
  to: pending
  guard: Examples\OrderWorkflow\Guards\CanSubmit
  action: Examples\OrderWorkflow\Actions\NotifyReviewer
```

#### In Code:
```php
class Order extends Model {
    use HasStateMachine;
    
    // Auto-generated methods available:
    $order->canSubmit();    // Check if transition is possible
    $order->submit();       // Execute transition with guard/action
}
```

## 📁 File Structure

```
examples/OrderWorkflow/
├── Guards/
│   ├── IsManager.php           # Check user permissions
│   ├── CanSubmit.php          # Validate order data
│   └── HasMinimumAmount.php    # Check minimum amount
├── Actions/
│   ├── NotifyReviewer.php      # Notify reviewer
│   ├── SendConfirmationEmail.php # Send confirmation email
│   └── ProcessPayment.php      # Process payment
├── Models/
│   └── Order.php               # Example model
├── advanced-order-workflow.yaml # Complete workflow
├── simple-order-workflow.yaml  # Simplified workflow
└── README.md                   # Detailed documentation
```

## 🔧 Usage

### 1. Create a Guard
```php
class MyGuard implements \Grazulex\LaravelStatecraft\Contracts\Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        // Your validation logic
        return true;
    }
}
```

### 2. Create an Action
```php
class MyAction implements \Grazulex\LaravelStatecraft\Contracts\Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        // Your action logic
        Log::info("Action executed!");
    }
}
```

### 3. Configure in YAML
```yaml
transitions:
  - from: state_a
    to: state_b
    guard: App\Guards\MyGuard
    action: App\Actions\MyAction
```

## 🧪 Tests

### Complete Coverage
- **Guards**: 7 tests (condition validation)
- **Actions**: 3 tests (behavior execution)
- **Integration**: 4 tests (dynamic resolution)
- **Total**: 14 tests, 27 assertions ✅

### Results
```
Tests:    53 passed (134 assertions)
Duration: 1.14s
```

## 🎨 Practical Examples

### Simple Workflow
```yaml
# Transitions with short methods
- from: draft
  to: pending
  guard: canSubmit
  action: notifyReviewer
```

### Advanced Workflow
```yaml
# Transitions with full class names
- from: pending
  to: approved
  guard: Examples\OrderWorkflow\Guards\IsManager
  action: Examples\OrderWorkflow\Actions\SendConfirmationEmail
```

### Code Usage
```php
$order = new Order([
    'customer_email' => 'client@example.com',
    'items' => [['name' => 'Product 1', 'price' => 100]],
    'amount' => 100
]);

// Automatic checks
if ($order->canSubmit()) {
    $order->submit(); // Execute guard + action
}

// State and available transitions
$order->getCurrentState();
$order->getAvailableTransitions();
```

## 🚀 Next Steps

The implementation is **complete and functional** with:
- ✅ Dynamic resolution of Guards and Actions
- ✅ Complete support for both YAML syntaxes
- ✅ Practical examples and documentation
- ✅ Complete tests (53 tests passing)
- ✅ Perfect integration with existing system

The system is ready for production and can be easily extended with new Guards and Actions according to specific application needs! 🎉

## 🧩 Guard Expressions

Laravel Statecraft supports powerful guard expressions with AND/OR/NOT logic for complex business rules. This allows you to create sophisticated conditional logic directly in your YAML state machine definitions.

### AND Logic - All conditions must be true

```yaml
- from: pending
  to: approved
  guard:
    and:
      - Examples\OrderWorkflow\Guards\IsManager
      - Examples\OrderWorkflow\Guards\HasMinimumAmount
```

All specified guards must return `true` for the transition to be allowed.

### OR Logic - At least one condition must be true

```yaml
- from: pending
  to: approved
  guard:
    or:
      - Examples\OrderWorkflow\Guards\IsManager
      - Examples\OrderWorkflow\Guards\IsVIP
```

At least one of the specified guards must return `true` for the transition to be allowed.

### NOT Logic - Condition must be false

```yaml
- from: pending
  to: approved
  guard:
    not: Examples\OrderWorkflow\Guards\IsBlacklisted
```

The specified guard must return `false` for the transition to be allowed.

### Nested Expressions - Complex combinations

```yaml
- from: pending
  to: approved
  guard:
    and:
      - Examples\OrderWorkflow\Guards\IsManager
      - or:
          - Examples\OrderWorkflow\Guards\IsVIP
          - Examples\OrderWorkflow\Guards\IsUrgent
```

You can nest expressions to create complex business logic. This example requires the user to be a manager AND either VIP or urgent.

### Backward Compatibility

Simple string guards continue to work as before:

```yaml
- from: pending
  to: approved
  guard: Examples\OrderWorkflow\Guards\IsManager
```

### How It Works

1. **Guard Expression Parser** - Automatically detects when a guard is an expression (array format with `and`, `or`, or `not` keys)
2. **Guard Expression Evaluator** - Recursively evaluates nested expressions using proper boolean logic
3. **Dynamic Resolution** - Guards are resolved from Laravel container at runtime
4. **Event Integration** - Guard expressions are serialized to JSON in events and state history

### Benefits

- **Flexible Logic** - Combine multiple conditions with AND/OR/NOT operations
- **Nested Expressions** - Create complex business rules with nested logic
- **Readable YAML** - Clean, declarative syntax for guard conditions
- **Dynamic Evaluation** - Guards are resolved and evaluated at runtime
- **Full Integration** - Works with events, state history, and all existing features

## Guard Interface

```php
interface Guard
{
    /**
     * Check if the transition is allowed.
     *
     * @param Model $model The model being transitioned
     * @param string $from The current state
     * @param string $to The target state
     * @return bool True if transition is allowed, false otherwise
     */
    public function check(Model $model, string $from, string $to): bool;
}
```

## Action Interface

```php
interface Action
{
    /**
     * Execute the action during transition.
     *
     * @param Model $model The model being transitioned
     * @param string $from The current state
     * @param string $to The target state
     * @return void
     */
    public function execute(Model $model, string $from, string $to): void;
}
```

## Dynamic Resolution

Laravel Statecraft automatically resolves guards and actions using the Laravel service container. This means you can:

1. **Use dependency injection** in your guards and actions
2. **Bind interfaces** to implementations in your service provider
3. **Use singleton patterns** for shared resources
4. **Leverage Laravel's automatic resolution** for constructor parameters

### Example with Dependency Injection

```php
class NotifyReviewer implements Action
{
    public function __construct(
        private NotificationService $notifications,
        private UserRepository $users
    ) {}

    public function execute(Model $model, string $from, string $to): void
    {
        $reviewers = $this->users->getReviewers();
        $this->notifications->notifyReviewers($model, $reviewers);
    }
}
```
