# Laravel Statecraft - YAML Example Files

This directory contains example YAML files that demonstrate the different features of Laravel Statecraft.

## Example Files

### 📄 `example-workflow.yaml`
Basic example of a simple state machine for demonstration.

```yaml
state_machine:
  name: ExampleWorkflow
  model: Grazulex\LaravelStatecraft\Examples\ExampleModel
  states:
    - draft
    - pending
    - approved
    - rejected
  initial: draft
  transitions:
    - from: draft
      to: pending
    - from: pending
      to: approved
    - from: pending
      to: rejected
```

### 📄 `order.yaml`
Example of an order workflow with guards and actions.

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
      guard: CanSubmit
    - from: pending
      to: approved
      guard:
        and:
          - IsManager
          - HasMinimumAmount
      action: SendApprovalEmail
    - from: pending
      to: rejected
      guard: IsManager
      action: SendRejectionEmail
```

### 📄 `user.yaml`
Example of a user workflow with multiple states.

```yaml
state_machine:
  name: UserWorkflow
  model: App\Models\User
  states:
    - inactive
    - active
    - pending
    - banned
  initial: inactive
  transitions:
    - from: inactive
      to: active
    - from: active
      to: pending
    - from: pending
      to: active
    - from: active
      to: banned
    - from: banned
      to: active
```

### 📄 `test.yaml`
Simple test file for unit testing.

```yaml
state_machine:
  name: TestWorkflow
  model: App\Models\Test
  states:
    - draft
    - pending
    - approved
  initial: draft
  transitions:
    - from: draft
      to: pending
    - from: pending
      to: approved
```

## Usage

These files can be used for:

1. **Learning** - Understanding YAML syntax
2. **Testing** - Testing console commands
3. **Quick Start** - Base for your own workflows

### Console Commands

```bash
# List all examples
php artisan statecraft:list --path=examples

# Show specific example
php artisan statecraft:show order --path=examples

# Validate example
php artisan statecraft:validate order --path=examples

# Export example
php artisan statecraft:export order json --path=examples
```

## More Complete Examples

For more detailed examples with complete PHP code, see the directories:

- **[OrderWorkflow/](OrderWorkflow/)** - Complete order workflow example
- **[UserSubscription/](UserSubscription/)** - User subscription workflow
- **[ArticlePublishing/](ArticlePublishing/)** - Article publishing workflow
- **[EventUsage/](EventUsage/)** - Event usage examples

## Contributing

To add new examples:

1. Create a new YAML file
2. Document the structure in this README
3. Add tests if necessary
4. Follow existing naming conventions
