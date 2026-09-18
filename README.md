# Laravel Statecraft

<img src="new_logo.png" alt="Laravel Statecraft" width="200">

> [!TIP]
> **What Laravel Statecraft does for you** — Describe order, approval or onboarding workflows as state machines in YAML — states, transitions, guards and actions — instead of scattering `if ($status === ...)` checks across your codebase.
>
> **This package is free and maintained on my own time.** If it saves you hours, a small contribution helps me keep it going:
> [💖 GitHub Sponsors](https://github.com/sponsors/Grazulex) · [☕ Buy Me a Coffee](https://buymeacoffee.com/grazulex) · [PayPal](https://paypal.me/strauven)

Advanced State Machine implementation for Laravel applications. Declarative state management with support for conditions, actions, and complex workflows through YAML configuration.

[![Latest Version](https://img.shields.io/packagist/v/grazulex/laravel-statecraft.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-statecraft)
[![Total Downloads](https://img.shields.io/packagist/dt/grazulex/laravel-statecraft.svg?style=flat-square)](https://packagist.org/packages/grazulex/laravel-statecraft)
[![License](https://img.shields.io/github/license/grazulex/laravel-statecraft.svg?style=flat-square)](https://github.com/Grazulex/laravel-statecraft/blob/main/LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/php-v/grazulex/laravel-statecraft.svg?style=flat-square)](https://php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-12.x%20%7C%2013.x-ff2d20?style=flat-square&logo=laravel)](https://laravel.com/)
[![Tests](https://img.shields.io/github/actions/workflow/status/grazulex/laravel-statecraft/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/Grazulex/laravel-statecraft/actions)
[![Code Style](https://img.shields.io/badge/code%20style-pint-000000?style=flat-square&logo=laravel)](https://github.com/laravel/pint)

## 📖 Table of Contents

- [Overview](#overview)
- [✨ Features](#-features)
- [📦 Installation](#-installation)
- [🚀 Quick Start](#-quick-start)
- [📚 Complete Documentation](#-complete-documentation)
- [💡 Examples & Use Cases](#-examples--use-cases)
- [🔧 Requirements](#-requirements)
- [🧪 Testing](#-testing)
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

Detailed installation instructions are available in our wiki: **[📦 Installation & Setup](https://github.com/Grazulex/laravel-statecraft/wiki/Installation-&-Setup)**

Quick install via Composer:

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

For a complete getting started guide, visit: **[🚀 Basic Usage Guide](https://github.com/Grazulex/laravel-statecraft/wiki/Basic-Usage-Guide)**

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
  - name: shipped
    description: Order has been shipped

transitions:
  - name: pay
    from: pending
    to: paid
    guard: PaymentGuard
    action: ProcessPayment
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

// Transition to next state
$order->transition('pay'); // Moves to 'paid' state

// Check current state
echo $order->currentState(); // 'paid'
```

## 📚 Complete Documentation

Our comprehensive documentation is available in the GitHub Wiki:

### 🏗️ Core Concepts
- **[📄 YAML Configuration](https://github.com/Grazulex/laravel-statecraft/wiki/YAML-Configuration)** - Complete YAML syntax and options
- **[🛡️ Guards & Actions](https://github.com/Grazulex/laravel-statecraft/wiki/Guards-&-Actions)** - Pre/post transition logic
- **[🎯 Events System](https://github.com/Grazulex/laravel-statecraft/wiki/Events-System)** - State change events and listeners
- **[📊 State History](https://github.com/Grazulex/laravel-statecraft/wiki/State-History)** - Track and audit state changes

### 🔧 Advanced Topics
- **[⚙️ Configuration Guide](https://github.com/Grazulex/laravel-statecraft/wiki/Configuration-Guide)** - Package configuration options
- **[🎨 Console Commands](https://github.com/Grazulex/laravel-statecraft/wiki/Console-Commands)** - Artisan commands reference
- **[🧪 Testing Guide](https://github.com/Grazulex/laravel-statecraft/wiki/Testing-Guide)** - Testing your state machines

### 📖 Getting Started
- **[🏠 Wiki Home](https://github.com/Grazulex/laravel-statecraft/wiki/Home)** - Complete documentation homepage
- **[📦 Installation & Setup](https://github.com/Grazulex/laravel-statecraft/wiki/Installation-&-Setup)** - Detailed installation guide
- **[🚀 Basic Usage Guide](https://github.com/Grazulex/laravel-statecraft/wiki/Basic-Usage-Guide)** - Step-by-step tutorial

## 💡 Examples & Use Cases

Explore real-world implementations and patterns:

- **[📚 Examples Collection](https://github.com/Grazulex/laravel-statecraft/wiki/Examples-Collection)** - Complete examples overview
- **[📦 Order Workflow Example](https://github.com/Grazulex/laravel-statecraft/wiki/Order-Workflow-Example)** - E-commerce order processing
- **[📰 Article Publishing Example](https://github.com/Grazulex/laravel-statecraft/wiki/Article-Publishing-Example)** - Content management workflow
- **[💳 User Subscription Example](https://github.com/Grazulex/laravel-statecraft/wiki/User-Subscription-Example)** - Subscription lifecycle management
- **[🎯 Event Usage Example](https://github.com/Grazulex/laravel-statecraft/wiki/Event-Usage-Example)** - Advanced event handling

## 🔧 Requirements

- PHP 8.3 or higher
- Laravel 12.x or 13.x
- Optional: Redis for caching (recommended for production)

## 🧪 Testing

Comprehensive testing guide: **[🧪 Testing Guide](https://github.com/Grazulex/laravel-statecraft/wiki/Testing-Guide)**

```bash
composer test
composer test:coverage
composer test:types
```

## 🚀 Performance

Laravel Statecraft is optimized for production use with caching support and minimal overhead. See our **[⚙️ Configuration Guide](https://github.com/Grazulex/laravel-statecraft/wiki/Configuration-Guide)** for performance optimization tips.

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

Before contributing:
1. Read our [Code of Conduct](CODE_OF_CONDUCT.md)
2. Check the [issue tracker](https://github.com/Grazulex/laravel-statecraft/issues)
3. Review our **[📚 Complete Documentation](https://github.com/Grazulex/laravel-statecraft/wiki)**

## 🔒 Security

If you discover a security vulnerability, please send an e-mail via the [security policy](SECURITY.md). All security vulnerabilities will be promptly addressed.

## 💖 Support This Package

Laravel Statecraft is free, open source and maintained on my own time. If it saves you hours, here is how you can give back:

- ⭐ **Star the repository** — it helps other developers find it
- 🐦 **Share it** with your team and network
- 💖 **[Sponsor on GitHub](https://github.com/sponsors/Grazulex)**, **[buy me a coffee](https://buymeacoffee.com/grazulex)** or **[donate via PayPal](https://paypal.me/strauven)** — every contribution funds maintenance, new features and Laravel upgrades

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
