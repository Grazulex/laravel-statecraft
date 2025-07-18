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

## Overview

<div style="background: linear-gradient(135deg, #FF9900 0%, #D2D200 25%, #88C600 50%, #00B470 75%, #FF9900 100%); border-radius: 15px; padding: 30px; margin: 20px 0; color: white; text-shadow: 2px 2px 4px rgba(0,0,0,0.7);">

**Laravel Statecraft** est la solution élégante pour gérer les **<span style="color: #FFE066;">machines d'état</span>** dans vos applications Laravel. Définissez vos workflows de manière **<span style="color: #B3FF66;">déclarative</span>** avec YAML, contrôlez les transitions avec des **<span style="color: #66FFB3;">guards</span>**, des **<span style="color: #66E6FF;">actions</span>**, et des **<span style="color: #FFB366;">événements</span>**.

✨ **Parfait pour** : workflows de commandes, validation de contenu, gestion d'utilisateurs, processus d'approbation
🚀 **Simplicité** : Configuration YAML intuitive + méthodes auto-générées  
🔧 **Flexibilité** : Guards complexes, actions personnalisées, historique des transitions

</div>

## <span style="color: #FF9900;">🚀 Features</span>

- 🔁 **<span style="color: #D2D200;">Declarative state machines</span>** for Eloquent models
- 🛡️ **<span style="color: #88C600;">Guard conditions</span>** with AND/OR/NOT logic expressions
- ⚙️ **<span style="color: #00B470;">Lifecycle actions</span>** on transitions
- 📦 **<span style="color: #FF9900;">Auto-generated methods</span>** like `canPublish()` and `publish()`
- 🧪 **<span style="color: #D2D200;">Built-in test support</span>** for transitions
- 🔔 **<span style="color: #88C600;">Laravel event support</span>** (`Transitioning`, `Transitioned`)
- 🧾 **<span style="color: #00B470;">Optional transition history tracking</span>**
- ⚙️ **<span style="color: #FF9900;">Comprehensive Artisan commands</span>** for YAML definitions and PHP classes
- 🔧 **<span style="color: #D2D200;">Configurable</span>** paths, events, and history tracking
- 🎯 **<span style="color: #88C600;">Dynamic resolution</span>** of guards and actions via Laravel container
- 🧩 **<span style="color: #00B470;">Complex guard expressions</span>** with nested conditional logic
- 📊 **<span style="color: #FF9900;">Export capabilities</span>** (JSON, Mermaid, Markdown)
- ✅ **<span style="color: #D2D200;">Validation system</span>** for YAML definitions
- 📝 **<span style="color: #88C600;">Comprehensive documentation</span>** and examples

## <span style="color: #D2D200;">📦 Installation</span>

### Configuration (Optional)

<div style="border-left: 5px solid #88C600; padding-left: 20px; background: rgba(136, 198, 0, 0.1); margin: 15px 0;">

Publish the configuration file and migrations:

```bash
# Publish configuration
php artisan vendor:publish --tag=statecraft-config

# Publish migrations (if using history tracking)
php artisan vendor:publish --tag=statecraft-migrations
php artisan migrate
```

The configuration file will be published to `config/statecraft.php` where you can customize:
- **<span style="color: #FF9900;">State machine definitions path</span>**
- **<span style="color: #D2D200;">Default state field name</span>**
- **<span style="color: #88C600;">Event system settings</span>**
- **<span style="color: #00B470;">History tracking options</span>**

</div>

## <span style="color: #88C600;">✨ Example: Order Workflow</span>

**<span style="color: #FF9900;">YAML Definition</span>**

<div style="border-left: 5px solid #D2D200; padding-left: 20px; background: rgba(210, 210, 0, 0.1); margin: 15px 0;">

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

</div>

## <span style="color: #00B470;">🧩 Guard Expressions</span>

### AND Logic - All conditions must be true
<div style="border-left: 5px solid #FF9900; padding-left: 20px; background: rgba(255, 153, 0, 0.1); margin: 15px 0;">

```yaml
- from: pending
  to: approved
  guard:
    and:
      - IsManager
      - HasMinimumAmount
```

</div>

### OR Logic - At least one condition must be true
<div style="border-left: 5px solid #D2D200; padding-left: 20px; background: rgba(210, 210, 0, 0.1); margin: 15px 0;">

```yaml
- from: pending
  to: approved
  guard:
    or:
      - IsManager
      - IsVIP
```

</div>

### NOT Logic - Condition must be false
<div style="border-left: 5px solid #88C600; padding-left: 20px; background: rgba(136, 198, 0, 0.1); margin: 15px 0;">

```yaml
- from: pending
  to: approved
  guard:
    not: IsBlacklisted
```

</div>

### Nested Expressions - Complex combinations
<div style="border-left: 5px solid #00B470; padding-left: 20px; background: rgba(0, 180, 112, 0.1); margin: 15px 0;">

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

</div>

**<span style="color: #FF9900;">Key Features:</span>**
- 🔄 **<span style="color: #D2D200;">Backward Compatible</span>** - Simple string guards still work
- 🎯 **<span style="color: #88C600;">Dynamic Evaluation</span>** - Guards resolved at runtime
- 🧩 **<span style="color: #00B470;">Nested Logic</span>** - Complex business rules supported
- 📊 **<span style="color: #FF9900;">Event Integration</span>** - Expressions serialized in events and history
- ⚡ **<span style="color: #D2D200;">Boolean Logic</span>** - AND/OR/NOT operations with short-circuit evaluation

### Basic Model Setup

<div style="border-left: 5px solid #88C600; padding-left: 20px; background: rgba(136, 198, 0, 0.1); margin: 15px 0;">

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

</div>

### Using the State Machine

<div style="border-left: 5px solid #00B470; padding-left: 20px; background: rgba(0, 180, 112, 0.1); margin: 15px 0;">

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

</div>

### With History Tracking

<div style="border-left: 5px solid #FF9900; padding-left: 20px; background: rgba(255, 153, 0, 0.1); margin: 15px 0;">

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

</div>

---

## <span style="color: #88C600;">⚙️ Custom Guard</span>

<div style="border-left: 5px solid #D2D200; padding-left: 20px; background: rgba(210, 210, 0, 0.1); margin: 15px 0;">

```php
class IsManager implements \Grazulex\LaravelStatecraft\Contracts\Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        return auth()->user()?->is_manager;
    }
}
```

</div>

---

## <span style="color: #00B470;">🔍 Custom Action</span>

<div style="border-left: 5px solid #FF9900; padding-left: 20px; background: rgba(255, 153, 0, 0.1); margin: 15px 0;">

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

</div>

---

## <span style="color: #D2D200;">📜 Transition History (optional)</span>

<div style="border-left: 5px solid #88C600; padding-left: 20px; background: rgba(136, 198, 0, 0.1); margin: 15px 0;">

```php
$order->stateHistory(); // → returns a collection of past transitions
```

</div>

---

## <span style="color: #FF9900;">✅ Artisan Commands</span>

### Generate YAML Definition

<div style="border-left: 5px solid #D2D200; padding-left: 20px; background: rgba(210, 210, 0, 0.1); margin: 15px 0;">

```bash
php artisan statecraft:make order-workflow
php artisan statecraft:make article-status --states=draft,review,published --initial=draft
```

</div>

### Generate PHP Classes from YAML

<div style="border-left: 5px solid #88C600; padding-left: 20px; background: rgba(136, 198, 0, 0.1); margin: 15px 0;">

```bash
php artisan statecraft:generate database/state_machines/order-workflow.yaml
```

This generates:
- **<span style="color: #FF9900;">Guard classes</span>** in `app/StateMachines/Guards/`
- **<span style="color: #D2D200;">Action classes</span>** in `app/StateMachines/Actions/`
- **<span style="color: #88C600;">Model examples</span>** in `app/StateMachines/`

</div>

### List and Inspect Definitions

<div style="border-left: 5px solid #00B470; padding-left: 20px; background: rgba(0, 180, 112, 0.1); margin: 15px 0;">

```bash
# List all YAML definitions
php artisan statecraft:list

# Show definition details
php artisan statecraft:show order-workflow

# Validate definitions
php artisan statecraft:validate --all
```

</div>

### Export to Different Formats

<div style="border-left: 5px solid #FF9900; padding-left: 20px; background: rgba(255, 153, 0, 0.1); margin: 15px 0;">

```bash
# Export to JSON, Mermaid, or Markdown
php artisan statecraft:export order-workflow json
php artisan statecraft:export order-workflow mermaid
php artisan statecraft:export order-workflow md --output=docs/workflow.md
```

</div>

### Command Options

**<span style="color: #D2D200;">statecraft:make</span>** supports additional options:

```bash
php artisan statecraft:make order-workflow --model=App\\Models\\Order --states=draft,pending,approved --initial=draft
```

**<span style="color: #88C600;">statecraft:generate</span>** uses configurable output paths:
- Configure output directory via `statecraft.generated_code_path`
- Defaults to `app/StateMachines/` if not configured

---

## <span style="color: #88C600;">🧪 Testing</span>

## <span style="color: #88C600;">🧪 Testing</span>

Use the built-in test utilities:

<div style="border-left: 5px solid #00B470; padding-left: 20px; background: rgba(0, 180, 112, 0.1); margin: 15px 0;">

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

</div>

### Testing Guard Expressions

<div style="border-left: 5px solid #FF9900; padding-left: 20px; background: rgba(255, 153, 0, 0.1); margin: 15px 0;">

Test **<span style="color: #D2D200;">complex guard expressions</span>** by setting up your models and authentication:

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

</div>

## <span style="color: #00B470;">🔔 Events</span>

Laravel Statecraft dispatches **<span style="color: #FF9900;">events during transitions</span>**:

<div style="border-left: 5px solid #D2D200; padding-left: 20px; background: rgba(210, 210, 0, 0.1); margin: 15px 0;">

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

</div>

---

## <span style="color: #D2D200;">📚 Documentation</span>

For **<span style="color: #FF9900;">comprehensive documentation</span>**, examples, and **<span style="color: #88C600;">advanced usage</span>**:

<div style="border-left: 5px solid #88C600; padding-left: 20px; background: rgba(136, 198, 0, 0.1); margin: 15px 0;">

- **[Console Commands](docs/CONSOLE_COMMANDS.md)** - <span style="color: #FF9900;">Console commands reference</span>  
- **[Guards and Actions](docs/GUARDS_AND_ACTIONS.md)** - <span style="color: #D2D200;">Dynamic guards and actions</span>
- **[Guard Expressions](docs/GUARD_EXPRESSIONS.md)** - <span style="color: #88C600;">AND/OR/NOT logic for guards</span>
- **[Configuration](docs/CONFIGURATION.md)** - <span style="color: #00B470;">Configuration options</span>
- **[Events](docs/EVENTS.md)** - <span style="color: #FF9900;">Event system usage</span>
- **[Testing](docs/TESTING.md)** - <span style="color: #D2D200;">Testing utilities</span>
- **[History](docs/HISTORY.md)** - <span style="color: #88C600;">State transition history</span>
- **[Examples](examples/)** - <span style="color: #00B470;">Practical examples and use cases</span>

</div>

## <span style="color: #88C600;">🎯 Next Steps</span>

<div style="border-left: 5px solid #00B470; padding-left: 20px; background: rgba(0, 180, 112, 0.1); margin: 15px 0;">

1. **<span style="color: #FF9900;">Quick Start</span>**: Check out the [OrderWorkflow example](examples/OrderWorkflow/)
2. **<span style="color: #D2D200;">Console Commands</span>**: Explore the [console commands](docs/CONSOLE_COMMANDS.md)
3. **<span style="color: #88C600;">Guard Expressions</span>**: See [guard-expressions-workflow.yaml](examples/OrderWorkflow/guard-expressions-workflow.yaml) for comprehensive examples
4. **<span style="color: #00B470;">Advanced Usage</span>**: Read the [Guards and Actions documentation](docs/GUARDS_AND_ACTIONS.md)
5. **<span style="color: #FF9900;">Configuration</span>**: Review the [Configuration guide](docs/CONFIGURATION.md)
6. **<span style="color: #D2D200;">Testing</span>**: Learn about [Testing utilities](docs/TESTING.md)

</div>

---

## <span style="color: #FF9900;">❤️ About</span>

<div style="border-left: 5px solid #D2D200; padding-left: 20px; background: rgba(210, 210, 0, 0.1); margin: 15px 0;">

Laravel-Statecraft is part of the **<span style="color: #88C600;">Grazulex Tools</span>** ecosystem:  
`Laravel-Arc` (DTOs) • `Laravel-Flowpipe` (Business Steps) • `Laravel-Statecraft` (State Machines)

> Designed for **<span style="color: #00B470;">clean, testable, and modular</span>** Laravel applications.

</div>

---

## <span style="color: #00B470;">🧙 Author</span>

<div style="border-left: 5px solid #FF9900; padding-left: 20px; background: rgba(255, 153, 0, 0.1); margin: 15px 0;">

**<span style="color: #D2D200;">Jean‑Marc Strauven</span>** / [@Grazulex](https://github.com/Grazulex)  
Blog: **<span style="color: #88C600;">[Open Source My Friend](https://opensourcemyfriend.hashnode.dev)</span>**

</div>