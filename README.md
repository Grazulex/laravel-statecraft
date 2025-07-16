# 🧩 Laravel-Statecraft

**Elegant and testable state machines for Laravel.**  
Define entity workflows declaratively (YAML or PHP), and control transitions with guards, actions, and events.

---

## 🚀 Features

- 🔁 Declarative state machines for Eloquent models
- 🛡️ Guard conditions to validate transitions
- ⚙️ Lifecycle actions on transitions
- 📦 Auto-generated methods like `canPublish()` and `publish()`
- 🧪 Built-in test support for transitions
- 🔔 Laravel event support (`Transitioning`, `Transitioned`)
- 🧾 Optional transition history tracking
- ⚙️ Artisan generator for YAML or PHP definitions
- ✅ Optional integration with `Laravel-Flowpipe` to run flows on transitions

---

## 📦 Installation

```bash
composer require grazulex/laravel-statecraft
php artisan vendor:publish --tag=laravel-statecraft
```

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

---

## 🧑‍💻 Usage

```php
$order = Order::find(1);

if ($order->canApprove()) {
    $order->approve(); // Executes guard + action + state change
}
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

## ✅ Artisan Generator

```bash
php artisan state:make OrderWorkflow
# or
php artisan state:generate-from-yaml database/state_machines/order_workflow.yaml
```

---

## 🧪 Test Utilities

```php
use Grazulex\LaravelStatecraft\Support\StateMachineTester;

StateMachineTester::assertTransitionAllowed($order, 'pending');
StateMachineTester::assertTransitionBlocked($order, 'approved');
```

---

## 📚 Coming Soon

- 👥 Support for role-based transitions
- 🧬 Visual representation (Mermaid/Graphviz)
- 🧠 Integration with `Laravel-Flowpipe` for auto-triggered business flows
- 🧰 Support for multiple workflows per model

---

## ❤️ About

Laravel-Statecraft is part of the **Grazulex Tools** ecosystem:  
`Laravel-Arc` (DTOs) • `Laravel-Flowpipe` (Business Steps) • `Laravel-Statecraft` (State Machines)

> Designed for clean, testable, and modular Laravel applications.

---

## 🧙 Author

Jean‑Marc Strauven / [@Grazulex](https://github.com/Grazulex)  
Blog: [Open Source My Friend](https://opensourcemyfriend.hashnode.dev)