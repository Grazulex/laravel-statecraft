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

### 1. Approval Workflow - Require both manager role AND minimum amount
```yaml
- from: pending
  to: approved
  guard:
    and:
      - IsManager
      - HasMinimumAmount
```

### 2. VIP Processing - Allow either manager approval OR VIP customer status
```yaml
- from: pending
  to: approved
  guard:
    or:
      - IsManager
      - IsVIP
```

### 3. Security Checks - Ensure user is NOT blacklisted before proceeding
```yaml
- from: pending
  to: approved
  guard:
    not: IsBlacklisted
```

### 4. Complex Business Rules - Combine multiple conditions with nested logic
```yaml
- from: pending
  to: approved
  guard:
    and:
      - IsManager
      - or:
          - IsVIP
          - and:
              - IsUrgent
              - HasMinimumAmount
```

### 5. Real-World Order Processing Example
```yaml
state_machine:
  name: OrderWorkflow
  model: App\Models\Order
  states: [draft, pending, approved, rejected, processing, shipped]
  initial: draft
  transitions:
    # Simple approval for small orders
    - from: pending
      to: approved
      guard:
        and:
          - not: IsBlacklisted
          - or:
              - IsSmallOrder
              - IsManager
    
    # Complex approval for large orders
    - from: pending
      to: approved
      guard:
        and:
          - IsManager
          - HasMinimumAmount
          - not: IsBlacklisted
          - or:
              - IsVIP
              - IsUrgent
    
    # Automatic processing for trusted customers
    - from: approved
      to: processing
      guard:
        or:
          - IsVIP
          - and:
              - IsTrustedCustomer
              - IsSmallOrder
```

### 6. User Permission System
```yaml
- from: draft
  to: published
  guard:
    or:
      - IsAuthor
      - and:
          - IsEditor
          - not: IsArticleExpired
      - and:
          - IsAdmin
          - not: IsArticleBlocked
```

### 7. Financial Transaction Approval
```yaml
- from: pending
  to: approved
  guard:
    and:
      - not: IsFraudulent
      - or:
          - IsLowAmount
          - and:
              - IsManager
              - HasValidSignature
          - and:
              - IsDirector
              - IsHighPriorityCustomer
```

## Error Handling

When a guard expression fails, the system provides detailed error messages:

```php
try {
    $order->approve();
} catch (GuardExpressionException $e) {
    // Handle guard expression evaluation errors
    echo "Guard expression failed: " . $e->getMessage();
}
```

## Testing Guard Expressions

You can test guard expressions using the built-in testing support:

```php
use Grazulex\LaravelStatecraft\Testing\StateMachineTester;

// Test a complex guard expression
$tester = new StateMachineTester($order);
$tester->mockGuard('IsManager', true)
       ->mockGuard('HasMinimumAmount', false)
       ->mockGuard('IsVIP', true);

// This should pass because (IsManager AND HasMinimumAmount) OR IsVIP
// = (true AND false) OR true = false OR true = true
$tester->assertCanTransition('approved');
```

## Performance Considerations

Guard expressions are evaluated lazily and short-circuit when possible:

- **AND expressions** stop at the first `false` result
- **OR expressions** stop at the first `true` result  
- **NOT expressions** evaluate once and negate the result
- **Nested expressions** are only evaluated if their parent expression requires it

This ensures optimal performance even with complex nested logic.

The guard expression system provides a powerful way to define complex business rules directly in your YAML state machine definitions while maintaining backward compatibility with existing simple guards.
