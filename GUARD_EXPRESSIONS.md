# Guard Expression Examples for Laravel Statecraft

## Basic Guard Expression Usage

Here are examples of how to use guard expressions in your YAML state machine definitions:

### 1. Simple Guards (existing functionality)
```yaml
state_machine:
  name: OrderWorkflow
  model: App\Models\Order
  states:
    - draft
    - pending
    - approved
    - rejected
  initial: draft
  transitions:
    - from: draft
      to: pending
      guard: IsManager
    - from: pending
      to: approved  
      guard: HasMinimumAmount
```

### 2. AND Logic - All conditions must be true
```yaml
state_machine:
  name: OrderWorkflow
  model: App\Models\Order
  states:
    - draft
    - pending
    - approved
    - rejected
  initial: draft
  transitions:
    - from: pending
      to: approved
      guard:
        and:
          - IsManager
          - HasMinimumAmount
```

### 3. OR Logic - At least one condition must be true
```yaml
state_machine:
  name: OrderWorkflow
  model: App\Models\Order
  states:
    - draft
    - pending
    - approved
    - rejected
  initial: draft
  transitions:
    - from: pending
      to: approved
      guard:
        or:
          - IsManager
          - IsVIP
```

### 4. NOT Logic - Condition must be false
```yaml
state_machine:
  name: OrderWorkflow
  model: App\Models\Order
  states:
    - draft
    - pending
    - approved
    - rejected
  initial: draft
  transitions:
    - from: pending
      to: approved
      guard:
        not: IsBlacklisted
```

### 5. Nested Expressions - Complex combinations
```yaml
state_machine:
  name: OrderWorkflow
  model: App\Models\Order
  states:
    - draft
    - pending
    - approved
    - rejected
  initial: draft
  transitions:
    - from: pending
      to: approved
      guard:
        and:
          - IsManager
          - or:
              - IsVIP
              - IsUrgent
```

## Creating Custom Guards

To create a custom guard, implement the `Guard` interface:

```php
<?php

namespace App\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class IsManager implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        return $model->user_role === 'manager';
    }
}
```

## How It Works

1. **Guard Expression Parser** - Automatically detects when a guard is an expression (array format with `and`, `or`, or `not` keys)
2. **Guard Expression Evaluator** - Recursively evaluates nested expressions using proper boolean logic
3. **Backward Compatibility** - Simple string guards continue to work as before
4. **Event Integration** - Guard expressions are serialized to JSON in events and state history

## Benefits

- **Flexible Logic** - Combine multiple conditions with AND/OR/NOT operations
- **Nested Expressions** - Create complex business rules with nested logic
- **Readable YAML** - Clean, declarative syntax for guard conditions
- **Dynamic Evaluation** - Guards are resolved and evaluated at runtime
- **Full Integration** - Works with events, state history, and all existing features

## Example Use Cases

1. **Approval Workflow** - Require both manager role AND minimum amount
2. **VIP Processing** - Allow either manager approval OR VIP customer status
3. **Security Checks** - Ensure user is NOT blacklisted before proceeding
4. **Complex Business Rules** - Combine multiple conditions with nested logic

The guard expression system provides a powerful way to define complex business rules directly in your YAML state machine definitions while maintaining backward compatibility with existing simple guards.
