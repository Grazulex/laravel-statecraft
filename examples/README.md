# Examples Overview

This directory contains practical examples demonstrating various Laravel Statecraft features and use cases.

## Available Examples

### 1. Order Workflow (Complete Example)
**Location**: [`examples/OrderWorkflow/`](OrderWorkflow/)

A comprehensive example showing:
- **Order management workflow** with multiple states
- **Guards implementation** (permission checks, validation)
- **Actions implementation** (notifications, processing)
- **Both simple and advanced YAML configurations**
- **Complete model integration** with traits
- **Practical use cases** and business logic

**States**: `draft → pending → approved/rejected → paid → shipped → delivered`

### 2. Article Publishing (Simple Example)
**Location**: [`examples/ArticlePublishing/`](ArticlePublishing/)

A straightforward example demonstrating:
- **Content publishing workflow**
- **Basic state transitions**
- **Simple guard conditions**
- **Publication actions**

**States**: `draft → review → published/rejected`

### 3. User Subscription (Event-Driven Example)
**Location**: [`examples/UserSubscription/`](UserSubscription/)

An example focusing on:
- **Event-driven state changes**
- **Payment processing integration**
- **Subscription lifecycle management**
- **Complex business rules**

**States**: `trial → active → suspended → cancelled`

### 4. Event Usage (Event-Driven Example)
**Location**: [`examples/EventUsage/`](EventUsage/)

An example focusing on:
- **Event-driven state change reactions**
- **Automatic notifications and logging**
- **External service integration**
- **Audit trail and metrics**
- **Testing event listeners**

**Features**: Comprehensive event handling patterns

## 🧩 Guard Expressions

All examples now support powerful guard expressions with AND/OR/NOT logic for complex business rules:

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

See `examples/OrderWorkflow/guard-expressions-workflow.yaml` for comprehensive examples.

### 5. More Examples Coming Soon

Additional examples are planned to demonstrate:
- **Document approval workflows**
- **Multi-step approval processes**
- **Role-based permission systems**
- **Advanced metadata handling**
- **Integration with external services**

## Quick Start

### 1. Choose an Example

Each example is self-contained and can be used as a starting point:

```bash
# Copy example files to your project
cp -r examples/OrderWorkflow/Guards app/Guards/
cp -r examples/OrderWorkflow/Actions app/Actions/
cp examples/OrderWorkflow/advanced-order-workflow.yaml database/state_machines/
cp examples/OrderWorkflow/guard-expressions-workflow.yaml database/state_machines/
```

### 2. Set Up Your Model

```php
use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Grazulex\LaravelStatecraft\Traits\HasStateHistory;

class Order extends Model
{
    use HasStateMachine, HasStateHistory;
    
    protected function getStateMachineDefinitionName(): string
    {
        return 'advanced-order-workflow';
    }
}
```

### 3. Use the State Machine

```php
$order = Order::create([
    'customer_email' => 'customer@example.com',
    'amount' => 150,
    'items' => [['name' => 'Product', 'price' => 150]]
]);

// Check available transitions
$order->getAvailableTransitions();

// Execute transitions
if ($order->canSubmit()) {
    $order->submit(); // Triggers guards and actions
}
```

## Example Features Comparison

| Feature | OrderWorkflow | ArticlePublishing | UserSubscription | EventUsage |
|---------|---------------|-------------------|------------------|------------|
| **States** | 8 states | 4 states | 4 states | Variable |
| **Guards** | 3 guards | 2 guards | 1 guard | N/A |
| **Actions** | 3 actions | 2 actions | 3 actions | N/A |
| **History** | ✅ | ✅ | ✅ | ✅ |
| **Events** | ✅ | ✅ | ✅ | ✅ |
| **Complexity** | Advanced | Simple | Medium | Simple |
| **Use Case** | E-commerce | CMS | SaaS | Events |

## Learning Path

### Beginner: Start with Article Publishing
- Simple 4-state workflow
- Basic guards and actions
- Easy to understand and modify

### Intermediate: Event Usage
- Event-driven state change reactions
- Automatic notifications and integrations
- Testing event listeners

### Advanced: User Subscription or Order Workflow
- **User Subscription**: Event-driven state changes, payment integration patterns, complex business rules
- **Order Workflow**: Complex multi-state workflow, advanced guards with business logic, real-world e-commerce scenario

### Expert: Custom Examples
- Build your own examples
- Integrate with external services
- Complex business requirements

## Example Structure

Each example follows this structure:

```
ExampleName/
├── README.md                    # Detailed documentation
├── Guards/                      # Guard implementations
│   ├── GuardName.php
│   └── ...
├── Actions/                     # Action implementations
│   ├── ActionName.php
│   └── ...
├── Models/                      # Example model implementations
│   └── ModelName.php
├── simple-workflow.yaml         # Basic YAML configuration
├── advanced-workflow.yaml       # Advanced YAML configuration
└── tests/                       # Example tests
    └── ExampleTest.php
```

## Creating Your Own Example

### 1. Define Your States

```yaml
# database/state_machines/my-workflow.yaml
state_machine:
  name: my-workflow
  model: App\Models\MyModel
  field: status
  states: [state1, state2, state3]
  initial: state1
  transitions:
    - from: state1
      to: state2
      guard: App\Guards\MyGuard
      action: App\Actions\MyAction
```

### 2. Implement Guards

```php
use Grazulex\LaravelStatecraft\Contracts\Guard;

class MyGuard implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        // Your business logic here
        return true;
    }
}
```

### 3. Implement Actions

```php
use Grazulex\LaravelStatecraft\Contracts\Action;

class MyAction implements Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        // Your action logic here
    }
}
```

### 4. Set Up Your Model

```php
class MyModel extends Model
{
    use HasStateMachine, HasStateHistory;
    
    protected function getStateMachineDefinitionName(): string
    {
        return 'my-workflow';
    }
}
```

## Testing Examples

All examples include test suites:

```bash
# Run specific example tests
php artisan test --filter=OrderWorkflowTest

# Run all statecraft tests
php artisan test tests/Feature/StateMachine/
```

## Common Patterns

### 1. Permission-Based Guards

```php
class RequiresRole implements Guard
{
    public function __construct(private string $role) {}
    
    public function check(Model $model, string $from, string $to): bool
    {
        return auth()->user()?->hasRole($this->role);
    }
}
```

### 2. Validation Guards

```php
class ValidatesData implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        return $model->isValid();
    }
}
```

### 3. Notification Actions

```php
class SendNotification implements Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        $model->notify(new StateChangedNotification($from, $to));
    }
}
```

### 4. External Service Actions

```php
class UpdateExternalService implements Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        Http::post('https://api.example.com/webhook', [
            'model_id' => $model->id,
            'state' => $to,
        ]);
    }
}
```

## Best Practices

1. **Keep It Simple**: Start with basic examples and add complexity gradually
2. **Test Everything**: Use the provided test utilities to verify behavior
3. **Document Your States**: Clearly document what each state represents
4. **Use Meaningful Names**: Choose descriptive names for states, guards, and actions
5. **Handle Errors**: Implement proper error handling in guards and actions
6. **Consider Performance**: Be mindful of database queries in guards and actions
7. **Follow Conventions**: Use consistent naming and structure across your workflows

## Contributing Examples

To contribute a new example:

1. Create a new directory in `examples/`
2. Follow the standard structure
3. Include comprehensive documentation
4. Add tests for all functionality
5. Update this overview file

## Support

If you need help with examples:

1. Check the example's README file
2. Review the main documentation
3. Run the example tests
4. Open an issue on GitHub

Each example is designed to be educational and practical, showing real-world usage patterns that you can adapt to your specific needs.