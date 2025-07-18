# Laravel Statecraft

<div align="center">
  <img src="new_logo.png" alt="Laravel Statecraft" width="100">
  <p><strong>Elegant and testable state machines for Laravel applications — Define entity workflows declaratively (YAML), and control transitions with guards, actions, and events.</strong></p>
  
  [![Latest Version](https://img.shields.io/packagist/v/grazulex/laravel-statecraft)](https://packagist.org/packages/grazulex/laravel-statecraft)
  [![Total Downloads](https://img.shields.io/packagist/dt/grazulex/laravel-statecraft)](https://packagist.org/packages/grazulex/laravel-statecraft)
  [![License](https://img.shields.io/github/license/grazulex/laravel-statecraft)](LICENSE.md)
  [![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue)](https://php.net)
  [![Laravel Version](https://img.shields.io/badge/laravel-%5E12.19-red)](https://laravel.com)
  [![Tests](https://github.com/Grazulex/laravel-statecraft/workflows/Tests/badge.svg)](https://github.com/Grazulex/laravel-statecraft/actions)
  [![Code Quality](https://github.com/Grazulex/laravel-statecraft/workflows/Code%20Quality/badge.svg)](https://github.com/Grazulex/laravel-statecraft/actions)
  [![Code Style](https://img.shields.io/badge/code%20style-pint-orange)](https://github.com/laravel/pint)
</div>

---

## 🚀 Features

- 🔁 **Declarative state machines** for Eloquent models
- 🛡️ **Guard conditions** with AND/OR/NOT logic expressions
- ⚙️ **Lifecycle actions** on transitions
- 📦 **Auto-generated methods** like `canPublish()` and `publish()`
- 🧪 **Built-in test support** for transitions
- 🔔 **Laravel event support** (`Transitioning`, `Transitioned`)
- 🧾 **Optional transition history tracking**
- ⚙️ **Comprehensive Artisan commands** for YAML definitions and PHP classes
- 🔧 **Configurable** paths, events, and history tracking
- 🎯 **Dynamic resolution** of guards and actions via Laravel container
- 🧩 **Complex guard expressions** with nested conditional logic
- 📊 **Export capabilities** (JSON, Mermaid, Markdown)
- ✅ **Validation system** for YAML definitions
- 📝 **Comprehensive documentation** and examples

---

## 📦 Installation

```bash
composer require grazulex/laravel-statecraft
```

### Configuration (Optional)

Publish the configuration file and migrations:

```bash
# Publish configuration
php artisan vendor:publish --tag=statecraft-config

# Publish migrations (if using history tracking)
php artisan vendor:publish --tag=statecraft-migrations
php artisan migrate
```

The configuration file will be published to `config/statecraft.php` where you can customize:
- State machine definitions path
- Default state field name
- Event system settings
- History tracking options

---

## ✨ Example: Order Workflow

**YAML Definition**

```yaml
state_machine:
  name: OrderWorkflow
  model: App\Models\Order
  states: [draft, pending, approved, rejected]
  initial: draft
  transitions:
    - from: draft
      to: pending
      guard: canSubmit
      action: notifyReviewer
    - from: pending
      to: approved
      guard: isManager
    - from: pending
      to: rejected
      action: refundCustomer
```

## 🧩 Guard Expressions

Laravel Statecraft supports powerful guard expressions with AND/OR/NOT logic for complex business rules:

### AND Logic - All conditions must be true
```yaml
- from: pending
  to: approved
  guard:
    and:
      - IsManager
      - HasMinimumAmount
```

### OR Logic - At least one condition must be true
```yaml
- from: pending
  to: approved
  guard:
    or:
      - IsManager
      - IsVIP
```

### NOT Logic - Condition must be false
```yaml
- from: pending
  to: approved
  guard:
    not: IsBlacklisted
```

### Nested Expressions - Complex combinations
```yaml
- from: pending
  to: approved
  guard:
    and:
      - IsManager
      - or:
          - IsVIP
          - IsUrgent
```

**Key Features:**
- 🔄 **Backward Compatible** - Simple string guards still work
- 🎯 **Dynamic Evaluation** - Guards resolved at runtime
- 🧩 **Nested Logic** - Complex business rules supported
- 📊 **Event Integration** - Expressions serialized in events and history
- ⚡ **Boolean Logic** - AND/OR/NOT operations with short-circuit evaluation

---

## 🧑‍💻 Usage

### Basic Model Setup

Add the trait to your model:

```php
use Grazulex\LaravelStatecraft\Traits\HasStateMachine;

class Order extends Model
{
    use HasStateMachine;
    
    protected function getStateMachineDefinitionName(): string
    {
        return 'order-workflow'; // YAML file name
    }
}
```

### Using the State Machine

```php
$order = Order::find(1);

// Check if transitions are allowed
if ($order->canApprove()) {
    $order->approve(); // Executes guard + action + state change
}

// Get current state and available transitions
$currentState = $order->getCurrentState();
$availableTransitions = $order->getAvailableTransitions();
```

### With History Tracking

```php
use Grazulex\LaravelStatecraft\Traits\HasStateHistory;

class Order extends Model
{
    use HasStateMachine, HasStateHistory;
    
    // ... rest of your model
}

// Access transition history
$history = $order->stateHistory();
$lastTransition = $order->latestStateTransition();
```

---

## ⚙️ Custom Guard

```php
class IsManager implements \Grazulex\LaravelStatecraft\Contracts\Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        return auth()->user()?->is_manager;
    }
}
```

---

## 🔍 Custom Action

```php
class NotifyReviewer implements \Grazulex\LaravelStatecraft\Contracts\Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        Notification::route('mail', 'review@team.com')
            ->notify(new OrderPendingNotification($model));
    }
}
```

---

## 📜 Transition History (optional)

```php
$order->stateHistory(); // → returns a collection of past transitions
```

---

## ✅ Artisan Commands

### Generate YAML Definition

```bash
php artisan statecraft:make order-workflow
php artisan statecraft:make article-status --states=draft,review,published --initial=draft
```

### Generate PHP Classes from YAML

```bash
php artisan statecraft:generate database/state_machines/order-workflow.yaml
```

This generates:
- Guard classes in `app/StateMachines/Guards/`
- Action classes in `app/StateMachines/Actions/`
- Model examples in `app/StateMachines/`

### List and Inspect Definitions

```bash
# List all YAML definitions
php artisan statecraft:list

# Show definition details
php artisan statecraft:show order-workflow

# Validate definitions
php artisan statecraft:validate --all
```

### Export to Different Formats

```bash
# Export to JSON, Mermaid, or Markdown
php artisan statecraft:export order-workflow json
php artisan statecraft:export order-workflow mermaid
php artisan statecraft:export order-workflow md --output=docs/workflow.md
```

### Command Options

**statecraft:make** supports additional options:

```bash
php artisan statecraft:make order-workflow --model=App\\Models\\Order --states=draft,pending,approved --initial=draft
```

**statecraft:generate** uses configurable output paths:
- Configure output directory via `statecraft.generated_code_path`
- Defaults to `app/StateMachines/` if not configured

---

## 🧪 Testing

Use the built-in test utilities:

```php
use Grazulex\LaravelStatecraft\Testing\StateMachineTester;

// Test transitions
StateMachineTester::assertTransitionAllowed($order, 'approved');
StateMachineTester::assertTransitionBlocked($order, 'rejected');

// Test states
StateMachineTester::assertInState($order, 'pending');
StateMachineTester::assertHasAvailableTransitions($order, ['approved', 'rejected']);

// Test methods
StateMachineTester::assertCanExecuteMethod($order, 'approve');
StateMachineTester::assertCannotExecuteMethod($order, 'reject');
```

### Testing Guard Expressions

Test complex guard expressions by setting up your models and authentication:

```php
// Test AND logic with actual conditions
$manager = User::factory()->create(['is_manager' => true]);
$order = Order::factory()->create(['amount' => 1000]);
$this->actingAs($manager);

// Both conditions true: IsManager AND HasMinimumAmount
StateMachineTester::assertTransitionAllowed($order, 'approved');

// Make one condition false
$nonManager = User::factory()->create(['is_manager' => false]);
$this->actingAs($nonManager);
StateMachineTester::assertTransitionBlocked($order, 'approved');

// Test OR logic with different conditions
$vipOrder = Order::factory()->create(['is_vip' => true]);
StateMachineTester::assertTransitionAllowed($vipOrder, 'approved');

// Test NOT logic
$blacklistedOrder = Order::factory()->create(['customer_blacklisted' => true]);
StateMachineTester::assertTransitionBlocked($blacklistedOrder, 'approved');
```
```

## 🔔 Events

Laravel Statecraft dispatches events during transitions:

```php
use Grazulex\LaravelStatecraft\Events\StateTransitioning;
use Grazulex\LaravelStatecraft\Events\StateTransitioned;

// Listen to state changes
Event::listen(StateTransitioning::class, function ($event) {
    // Before transition
    $event->model; // The model
    $event->from;  // From state
    $event->to;    // To state
    $event->guard; // Guard class (if any)
    $event->action; // Action class (if any)
});

Event::listen(StateTransitioned::class, function ($event) {
    // After transition
    Log::info("Order {$event->model->id} transitioned from {$event->from} to {$event->to}");
});
```

---

## 📚 Documentation

For comprehensive documentation, examples, and advanced usage:

- **[Console Commands](docs/CONSOLE_COMMANDS.md)** - Console commands reference  
- **[Guards and Actions](docs/GUARDS_AND_ACTIONS.md)** - Dynamic guards and actions
- **[Guard Expressions](docs/GUARD_EXPRESSIONS.md)** - AND/OR/NOT logic for guards
- **[Configuration](docs/CONFIGURATION.md)** - Configuration options
- **[Events](docs/EVENTS.md)** - Event system usage
- **[Testing](docs/TESTING.md)** - Testing utilities
- **[History](docs/HISTORY.md)** - State transition history
- **[Examples](examples/)** - Practical examples and use cases

## 🎯 Next Steps

1. **Quick Start**: Check out the [OrderWorkflow example](examples/OrderWorkflow/)
2. **Console Commands**: Explore the [console commands](docs/CONSOLE_COMMANDS.md)
3. **Guard Expressions**: See [guard-expressions-workflow.yaml](examples/OrderWorkflow/guard-expressions-workflow.yaml) for comprehensive examples
4. **Advanced Usage**: Read the [Guards and Actions documentation](docs/GUARDS_AND_ACTIONS.md)
5. **Configuration**: Review the [Configuration guide](docs/CONFIGURATION.md)
6. **Testing**: Learn about [Testing utilities](docs/TESTING.md)

---

## ❤️ About

Laravel-Statecraft is part of the **Grazulex Tools** ecosystem:  
`Laravel-Arc` (DTOs) • `Laravel-Flowpipe` (Business Steps) • `Laravel-Statecraft` (State Machines)

> Designed for clean, testable, and modular Laravel applications.

---

## 🧙 Author

Jean‑Marc Strauven / [@Grazulex](https://github.com/Grazulex)  
Blog: [Open Source My Friend](https://opensourcemyfriend.hashnode.dev)