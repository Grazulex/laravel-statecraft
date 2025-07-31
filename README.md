# Laravel Statecraft

<img src="new_logo.png" alt="Laravel Statecraft" width="200">

Advanced State Machine implementation for Laravel applications. Declarative state management with support for conditions, actions, and complex workflows through YAML configuration.

[![Latest Version](https://img.shields.io/packagist/v/grazulex/laravel-statecraft.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-statecraft)
[![Total Downloads](https://img.shields.io/packagist/dt/grazulex/laravel-statecraft.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-statecraft)
[![License](https://img.shields.io/github/license/grazulex/laravel-statecraft.svg?style=flat-square)](https://github.com/Grazulex/laravel-statecraft/blob/main/LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/php-v/grazulex/laravel-statecraft.svg?style=flat-square)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-12.x-ff2d20?style=flat-square&logo=laravel)](https://laravel.com/)
[![Tests](https://img.shields.io/github/actions/workflow/status/grazulex/laravel-statecraft/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/Grazulex/laravel-statecraft/actions)
[![Code Style](https://img.shields.io/badge/code%20style-pint-000000?style=flat-square&logo=laravel)](https://github.com/laravel/pint)

## 📖 Table of Contents

- [Overview](#overview)
- [✨ Features](#-features)
- [📦 Installation](#-installation)
- [🚀 Quick Start](#-quick-start)
- [🔄 State Transitions](#-state-transitions)
- [🎯 Guards & Actions](#-guards--actions)
- [📋 YAML Configuration](#-yaml-configuration)
- [⚙️ Configuration](#️-configuration)
- [📚 Documentation](#-documentation)
- [💡 Examples](#-examples)
- [🧪 Testing](#-testing)
- [🔧 Requirements](#-requirements)
- [🚀 Performance](#-performance)
- [🤝 Contributing](#-contributing)
- [🔒 Security](#-security)
- [📄 License](#-license)

## Overview

Laravel Statecraft is a powerful state machine implementation for Laravel that provides declarative state management through YAML configuration. Build complex workflows with conditional transitions, guards, actions, and comprehensive state tracking.

**Perfect for order processing, user workflows, approval systems, and any application requiring sophisticated state management.**

### 🎯 Use Cases

Laravel Statecraft is perfect for:

- **Order Processing** - Complex e-commerce order workflows
- **User Registration** - Multi-step user onboarding flows
- **Approval Systems** - Document or request approval workflows  
- **Content Management** - Publishing and moderation workflows
- **Business Processes** - Any multi-state business logic

## ✨ Features

- 🚀 **Declarative Configuration** - Define state machines in YAML files
- 🔄 **Flexible Transitions** - Conditional transitions with guards and actions
- 🎯 **Event System** - Built-in events for state changes and transitions
- 📊 **State History** - Track all state changes with timestamps
- 🛡️ **Guards & Actions** - Pre/post transition validation and processing
- 🔗 **Model Integration** - Seamless Eloquent model integration
- 📋 **YAML Support** - Human-readable state machine definitions
- 🎨 **Artisan Commands** - CLI tools for state machine management
- ✅ **Validation** - Comprehensive state machine validation
- 📈 **Visualization** - Export state machines to Mermaid diagrams
- 🧪 **Test-Friendly** - Built-in testing utilities
- ⚡ **Performance** - Optimized for speed with caching support

## 📦 Installation

Install the package via Composer:

```bash
composer require grazulex/laravel-statecraft
```

> **💡 Auto-Discovery**  
> The service provider will be automatically registered thanks to Laravel's package auto-discovery.

Publish configuration:

```bash
php artisan vendor:publish --tag=statecraft-config
```

Publish migrations (if using history tracking):

```bash
php artisan vendor:publish --tag=statecraft-migrations
php artisan migrate
```

## 🚀 Quick Start

### 1. Create a State Machine Definition

```bash
php artisan statecraft:make OrderStateMachine --model=Order
```

### 2. Define Your State Machine in YAML

```yaml
# state-machines/OrderStateMachine.yaml
name: OrderStateMachine
model: App\Models\Order
initial_state: pending

states:
  - name: pending
    description: Order is pending payment
  - name: paid
    description: Order has been paid
  - name: processing
    description: Order is being processed
  - name: shipped
    description: Order has been shipped
  - name: delivered
    description: Order has been delivered
  - name: cancelled
    description: Order was cancelled

transitions:
  - name: pay
    from: pending
    to: paid
    guard: PaymentGuard
    action: ProcessPayment
  
  - name: process
    from: paid
    to: processing
    action: StartProcessing
  
  - name: ship
    from: processing
    to: shipped
    guard: InventoryGuard
    action: CreateShipment
```

### 3. Add the Trait to Your Model

```php
use Grazulex\LaravelStatecraft\HasStateMachine;

class Order extends Model
{
    use HasStateMachine;
    
    protected $stateMachine = 'OrderStateMachine';
}
```

### 4. Use State Transitions

```php
// Create a new order (starts in 'pending' state)
$order = Order::create(['total' => 100.00]);

// Check current state
echo $order->currentState(); // 'pending'

// Transition to next state
$order->transition('pay'); // Moves to 'paid' state

// Check available transitions
$availableTransitions = $order->availableTransitions();

// Get state history
$history = $order->stateHistory();
```

## 🔄 State Transitions

Laravel Statecraft provides flexible transition management:

```php
// Basic transition
$order->transition('pay');

// Transition with context data
$order->transition('ship', ['tracking_number' => 'ABC123']);

// Check if transition is possible
if ($order->canTransition('process')) {
    $order->transition('process');
}

// Bulk state operations
$orders = Order::inState('pending')->get();
foreach ($orders as $order) {
    if ($order->canTransition('pay')) {
        $order->transition('pay');
    }
}
```

## 🎯 Guards & Actions

### Guards (Pre-transition Validation)

```php
use Grazulex\LaravelStatecraft\Contracts\Guard;

class PaymentGuard implements Guard
{
    public function passes($model, string $transition, array $context = []): bool
    {
        // Check if payment is valid
        return $model->payment_status === 'completed';
    }
    
    public function message(): string
    {
        return 'Payment must be completed before processing order.';
    }
}
```

### Actions (Post-transition Processing)

```php
use Grazulex\LaravelStatecraft\Contracts\Action;

class ProcessPayment implements Action
{
    public function execute($model, string $transition, array $context = []): void
    {
        // Process payment logic
        $model->update([
            'payment_processed_at' => now(),
            'payment_id' => $context['payment_id'] ?? null,
        ]);
        
        // Send confirmation email
        Mail::to($model->user)->send(new PaymentConfirmed($model));
    }
}
```

## 📋 YAML Configuration

Advanced state machine configuration:

```yaml
# state-machines/AdvancedOrderStateMachine.yaml
name: AdvancedOrderStateMachine
model: App\Models\Order
initial_state: draft

states:
  - name: draft
    description: Order being prepared
  - name: pending_payment
    description: Waiting for payment

transitions:
  - name: submit_order
    from: draft
    to: pending_payment
    guard: OrderValidationGuard
    action: NotifyCustomer
  
  - name: process_payment
    from: pending_payment
    to: [paid, failed] # Conditional transitions
    conditions:
      - condition: "payment.status == 'success'"
        to: paid
        action: ProcessSuccessfulPayment
```

## ⚙️ Configuration

Laravel Statecraft works out of the box, but you can customize it:

```php
// config/statecraft.php
return [
    'state_machines_path' => base_path('state-machines'),
    'cache_enabled' => true,
    'history_enabled' => true,
];
```

## 📚 Documentation

For detailed documentation, examples, and advanced usage:

- 📚 [Full Documentation](https://github.com/Grazulex/laravel-statecraft/wiki)
- 🎯 [Examples](https://github.com/Grazulex/laravel-statecraft/wiki/Examples)
- 🔧 [Configuration](https://github.com/Grazulex/laravel-statecraft/wiki/Configuration)
- 🧪 [Testing](https://github.com/Grazulex/laravel-statecraft/wiki/Testing)
- 🎨 [Guards & Actions](https://github.com/Grazulex/laravel-statecraft/wiki/Guards-and-Actions)

## 💡 Examples

### Order Processing State Machine

```php
// Check order state and available actions
$order = Order::find(1);

if ($order->inState('pending')) {
    // Show payment form
    return view('orders.payment', compact('order'));
}

if ($order->inState('paid') && $order->canTransition('process')) {
    // Start processing
    $order->transition('process');
}

// Get transition history
$history = $order->stateHistory();
foreach ($history as $entry) {
    echo "{$entry->from_state} → {$entry->to_state} at {$entry->created_at}";
}
```

### User Registration Flow

```php
class UserRegistration extends Model
{
    use HasStateMachine;
    
    protected $stateMachine = 'UserRegistrationStateMachine';
}

// Registration workflow
$registration = UserRegistration::create(['email' => 'user@example.com']);
$registration->transition('send_verification'); // pending → email_sent
$registration->transition('verify_email');     // email_sent → verified  
$registration->transition('complete');         // verified → completed
```

Check out the [wiki](https://github.com/Grazulex/laravel-statecraft/wiki/Examples) for more examples.

## 🧪 Testing

Laravel Statecraft includes comprehensive testing utilities:

```php
use Grazulex\LaravelStatecraft\Testing\StateMachineTester;

public function test_order_payment_flow()
{
    $order = Order::factory()->create();
    
    // Test state machine flow
    StateMachineTester::make($order)
        ->assertCurrentState('pending')
        ->assertCanTransition('pay')
        ->assertCannotTransition('ship')
        ->transition('pay')
        ->assertCurrentState('paid')
        ->assertTransitionCount(1);
}
```

## 🔧 Requirements

- PHP: ^8.3
- Laravel: ^12.0
- Carbon: ^3.10

## 🚀 Performance

Laravel Statecraft is optimized for performance:

- **State Caching**: State machines are cached for better performance
- **Lazy Loading**: Guards and actions are loaded only when needed
- **Efficient Queries**: Optimized database queries for state operations
- **Memory Efficient**: Minimal memory footprint

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## 🔒 Security

If you discover a security vulnerability, please review our [Security Policy](SECURITY.md) before disclosing it.

## 📄 License

Laravel Statecraft is open-sourced software licensed under the [MIT license](LICENSE.md).

---

**Made with ❤️ for the Laravel community**

### Resources

- [📖 Documentation](https://github.com/Grazulex/laravel-statecraft/wiki)
- [💬 Discussions](https://github.com/Grazulex/laravel-statecraft/discussions)
- [🐛 Issue Tracker](https://github.com/Grazulex/laravel-statecraft/issues)
- [📦 Packagist](https://packagist.org/packages/grazulex/laravel-statecraft)

### Community Links

- [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) - Our code of conduct
- [CONTRIBUTING.md](CONTRIBUTING.md) - How to contribute
- [SECURITY.md](SECURITY.md) - Security policy
- [RELEASES.md](RELEASES.md) - Release notes and changelog
