# Testing

Laravel Statecraft provides comprehensive testing utilities to help you test state machine functionality in your applications.

## Test Utilities

The `StateMachineTester` class provides assertions for testing state machine behavior:

```php
use Grazulex\LaravelStatecraft\Testing\StateMachineTester;
```

## Available Assertions

### assertTransitionAllowed

Tests that a specific transition is allowed from the current state:

```php
StateMachineTester::assertTransitionAllowed($model, 'approved');
StateMachineTester::assertTransitionAllowed($model, 'rejected', 'Custom failure message');
```

### assertTransitionBlocked

Tests that a specific transition is blocked from the current state:

```php
StateMachineTester::assertTransitionBlocked($model, 'approved');
StateMachineTester::assertTransitionBlocked($model, 'shipped', 'Should not be able to ship unpaid orders');
```

### assertInState

Tests that a model is in a specific state:

```php
StateMachineTester::assertInState($model, 'pending');
StateMachineTester::assertInState($order, 'draft', 'New orders should start in draft state');
```

### assertHasAvailableTransitions

Tests that a model has specific available transitions:

```php
StateMachineTester::assertHasAvailableTransitions($model, ['approved', 'rejected']);
StateMachineTester::assertHasAvailableTransitions($order, ['pending'], 'Draft orders should only be able to go to pending');
```

### assertCanExecuteMethod

Tests that a model can execute a specific transition method:

```php
StateMachineTester::assertCanExecuteMethod($model, 'approve');
StateMachineTester::assertCanExecuteMethod($order, 'submit', 'Should be able to submit valid orders');
```

### assertCannotExecuteMethod

Tests that a model cannot execute a specific transition method:

```php
StateMachineTester::assertCannotExecuteMethod($model, 'approve');
StateMachineTester::assertCannotExecuteMethod($order, 'ship', 'Cannot ship unpaid orders');
```

## Testing Examples

### Basic State Machine Testing

```php
use Tests\TestCase;
use Grazulex\LaravelStatecraft\Testing\StateMachineTester;

class OrderStateMachineTest extends TestCase
{
    public function test_new_order_starts_in_draft_state(): void
    {
        $order = Order::factory()->create();
        
        StateMachineTester::assertInState($order, 'draft');
    }
    
    public function test_draft_order_can_be_submitted(): void
    {
        $order = Order::factory()->create(['status' => 'draft']);
        
        StateMachineTester::assertTransitionAllowed($order, 'pending');
        StateMachineTester::assertCanExecuteMethod($order, 'submit');
    }
    
    public function test_draft_order_cannot_be_approved(): void
    {
        $order = Order::factory()->create(['status' => 'draft']);
        
        StateMachineTester::assertTransitionBlocked($order, 'approved');
        StateMachineTester::assertCannotExecuteMethod($order, 'approve');
    }
    
    public function test_pending_order_available_transitions(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);
        
        StateMachineTester::assertHasAvailableTransitions($order, ['approved', 'rejected']);
    }
}
```

### Testing Guards

```php
class OrderGuardTest extends TestCase
{
    public function test_order_cannot_be_approved_without_manager_role(): void
    {
        $user = User::factory()->create(['is_manager' => false]);
        $this->actingAs($user);
        
        $order = Order::factory()->create(['status' => 'pending']);
        
        StateMachineTester::assertTransitionBlocked($order, 'approved');
        StateMachineTester::assertCannotExecuteMethod($order, 'approve');
    }
    
    public function test_order_can_be_approved_with_manager_role(): void
    {
        $user = User::factory()->create(['is_manager' => true]);
        $this->actingAs($user);
        
        $order = Order::factory()->create(['status' => 'pending']);
        
        StateMachineTester::assertTransitionAllowed($order, 'approved');
        StateMachineTester::assertCanExecuteMethod($order, 'approve');
    }
    
    public function test_order_cannot_be_submitted_without_required_fields(): void
    {
        $order = Order::factory()->create([
            'status' => 'draft',
            'customer_email' => null, // Missing required field
        ]);
        
        StateMachineTester::assertTransitionBlocked($order, 'pending');
        StateMachineTester::assertCannotExecuteMethod($order, 'submit');
    }
}
```

### Testing Guard Expressions

Laravel Statecraft supports complex guard expressions, and you can test them using standard Laravel testing patterns:

```php
class OrderGuardExpressionTest extends TestCase
{
    public function test_and_logic_requires_all_conditions(): void
    {
        $order = Order::factory()->create([
            'status' => 'pending',
            'amount' => 1000,
            'customer_blacklisted' => false,
        ]);
        
        $user = User::factory()->create(['is_manager' => true]);
        $this->actingAs($user);
        
        // Test AND expression: IsManager AND HasMinimumAmount
        // Both conditions should be true
        StateMachineTester::assertTransitionAllowed($order, 'approved');
        
        // Create non-manager user
        $nonManager = User::factory()->create(['is_manager' => false]);
        $this->actingAs($nonManager);
        
        // Now IsManager is false, so transition should be blocked
        StateMachineTester::assertTransitionBlocked($order, 'approved');
    }
    
    public function test_or_logic_requires_at_least_one_condition(): void
    {
        $order = Order::factory()->create([
            'status' => 'pending',
            'is_vip' => true,
            'customer_blacklisted' => false,
        ]);
        
        $nonManager = User::factory()->create(['is_manager' => false]);
        $this->actingAs($nonManager);
        
        // Test OR expression: IsManager OR IsVIP
        // IsManager is false but IsVIP is true, so transition should be allowed
        StateMachineTester::assertTransitionAllowed($order, 'approved');
        
        // Make both conditions false
        $order->update(['is_vip' => false]);
        StateMachineTester::assertTransitionBlocked($order, 'approved');
    }
    
    public function test_not_logic_requires_condition_to_be_false(): void
    {
        $order = Order::factory()->create([
            'status' => 'pending',
            'customer_blacklisted' => false,
        ]);
        
        // Test NOT expression: NOT IsBlacklisted
        // Customer is not blacklisted, so transition should be allowed
        StateMachineTester::assertTransitionAllowed($order, 'approved');
        
        // Make customer blacklisted
        $order->update(['customer_blacklisted' => true]);
        StateMachineTester::assertTransitionBlocked($order, 'approved');
    }
    
    public function test_nested_expressions(): void
    {
        $order = Order::factory()->create([
            'status' => 'pending',
            'is_vip' => false,
            'is_urgent' => true,
        ]);
        
        $manager = User::factory()->create(['is_manager' => true]);
        $this->actingAs($manager);
        
        // Test nested expression: IsManager AND (IsVIP OR IsUrgent)
        // IsManager = true AND (IsVIP = false OR IsUrgent = true)
        // = true AND (false OR true) = true AND true = true
        StateMachineTester::assertTransitionAllowed($order, 'approved');
        
        // Make nested OR condition false
        $order->update(['is_urgent' => false]);
        // IsManager = true AND (IsVIP = false OR IsUrgent = false)
        // = true AND (false OR false) = true AND false = false
        StateMachineTester::assertTransitionBlocked($order, 'approved');
    }
}
```

### Testing Guards with Mocks

When testing guard logic independently, you can use Laravel's mocking features:

```php
class OrderGuardMockTest extends TestCase
{
    public function test_with_mocked_guard(): void
    {
        $mockGuard = $this->createMock(IsManager::class);
        $mockGuard->method('check')->willReturn(true);
        
        $this->app->instance(IsManager::class, $mockGuard);
        
        $order = Order::factory()->pending()->create();
        
        StateMachineTester::assertTransitionAllowed($order, 'approved');
    }
    
    public function test_guard_with_dependency_injection(): void
    {
        // Mock the service that the guard depends on
        $mockUserService = $this->createMock(UserService::class);
        $mockUserService->method('isManager')->willReturn(true);
        
        $this->app->instance(UserService::class, $mockUserService);
        
        $order = Order::factory()->pending()->create();
        
        StateMachineTester::assertTransitionAllowed($order, 'approved');
    }
}
```

### Testing Actions

```php
class OrderActionTest extends TestCase
{
    public function test_approval_action_sends_notification(): void
    {
        Notification::fake();
        
        $user = User::factory()->create(['is_manager' => true]);
        $this->actingAs($user);
        
        $order = Order::factory()->create(['status' => 'pending']);
        
        $order->approve();
        
        StateMachineTester::assertInState($order, 'approved');
        Notification::assertSentTo($order->customer, OrderApprovedNotification::class);
    }
    
    public function test_submission_action_notifies_reviewers(): void
    {
        Mail::fake();
        
        $order = Order::factory()->create(['status' => 'draft']);
        
        $order->submit();
        
        StateMachineTester::assertInState($order, 'pending');
        Mail::assertQueued(OrderSubmittedMail::class);
    }
}
```

### Testing State History

```php
class OrderHistoryTest extends TestCase
{
    public function test_state_transitions_are_recorded(): void
    {
        config(['statecraft.history.enabled' => true]);
        
        $order = Order::factory()->create(['status' => 'draft']);
        
        // Perform transition
        $order->submit();
        
        // Check history
        $history = $order->stateHistory()->get();
        $this->assertCount(1, $history);
        
        $transition = $history->first();
        $this->assertEquals('draft', $transition->from_state);
        $this->assertEquals('pending', $transition->to_state);
    }
    
    public function test_latest_state_transition(): void
    {
        config(['statecraft.history.enabled' => true]);
        
        $order = Order::factory()->create(['status' => 'draft']);
        
        $order->submit();
        $order->approve();
        
        $latest = $order->latestStateTransition();
        $this->assertEquals('pending', $latest->from_state);
        $this->assertEquals('approved', $latest->to_state);
    }
}
```

### Testing Events

```php
class OrderEventTest extends TestCase
{
    public function test_state_transition_events_are_dispatched(): void
    {
        Event::fake();
        
        $order = Order::factory()->create(['status' => 'draft']);
        
        $order->submit();
        
        Event::assertDispatched(StateTransitioning::class, function ($event) use ($order) {
            return $event->model->is($order) 
                && $event->from === 'draft' 
                && $event->to === 'pending';
        });
        
        Event::assertDispatched(StateTransitioned::class, function ($event) use ($order) {
            return $event->model->is($order) 
                && $event->from === 'draft' 
                && $event->to === 'pending';
        });
    }
    
    public function test_events_can_be_disabled(): void
    {
        config(['statecraft.events.enabled' => false]);
        Event::fake();
        
        $order = Order::factory()->create(['status' => 'draft']);
        
        $order->submit();
        
        Event::assertNotDispatched(StateTransitioning::class);
        Event::assertNotDispatched(StateTransitioned::class);
    }
}
```

## Test Factories

Create factories for different states:

```php
// database/factories/OrderFactory.php
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_email' => $this->faker->email,
            'amount' => $this->faker->numberBetween(100, 1000),
            'status' => 'draft',
            'items' => [
                ['name' => 'Product 1', 'price' => 50],
                ['name' => 'Product 2', 'price' => 50],
            ],
        ];
    }
    
    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }
    
    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }
    
    public function rejected(): static
    {
        return $this->state(['status' => 'rejected']);
    }
}
```

**Usage**:
```php
$draftOrder = Order::factory()->create(); // Default draft state
$pendingOrder = Order::factory()->pending()->create();
$approvedOrder = Order::factory()->approved()->create();
```

## Integration Testing

### Testing Full Workflows

```php
class OrderWorkflowTest extends TestCase
{
    public function test_complete_order_approval_workflow(): void
    {
        $manager = User::factory()->create(['is_manager' => true]);
        $this->actingAs($manager);
        
        // Create order
        $order = Order::factory()->create();
        StateMachineTester::assertInState($order, 'draft');
        
        // Submit order
        $order->submit();
        StateMachineTester::assertInState($order, 'pending');
        
        // Approve order
        $order->approve();
        StateMachineTester::assertInState($order, 'approved');
        
        // Available transitions after approval
        StateMachineTester::assertHasAvailableTransitions($order, ['paid', 'cancelled']);
    }
    
    public function test_order_rejection_workflow(): void
    {
        $manager = User::factory()->create(['is_manager' => true]);
        $this->actingAs($manager);
        
        $order = Order::factory()->pending()->create();
        
        $order->reject();
        
        StateMachineTester::assertInState($order, 'rejected');
        StateMachineTester::assertHasAvailableTransitions($order, []); // No further transitions
    }
}
```

### Testing Error Conditions

```php
class OrderErrorTest extends TestCase
{
    public function test_invalid_transition_throws_exception(): void
    {
        $order = Order::factory()->create(['status' => 'draft']);
        
        $this->expectException(InvalidTransitionException::class);
        $this->expectExceptionMessage('Transition from draft to approved is not allowed');
        
        $order->approve(); // Should throw exception
    }
    
    public function test_guard_failure_prevents_transition(): void
    {
        $nonManager = User::factory()->create(['is_manager' => false]);
        $this->actingAs($nonManager);
        
        $order = Order::factory()->pending()->create();
        
        StateMachineTester::assertTransitionBlocked($order, 'approved');
        
        $this->expectException(InvalidTransitionException::class);
        $order->approve();
    }
}
```

## Performance Testing

```php
class StateMachinePerformanceTest extends TestCase
{
    public function test_bulk_state_transitions(): void
    {
        $orders = Order::factory()->count(100)->create();
        
        $start = microtime(true);
        
        foreach ($orders as $order) {
            $order->submit();
        }
        
        $duration = microtime(true) - $start;
        
        $this->assertLessThan(5.0, $duration, 'Bulk transitions should complete in under 5 seconds');
    }
}
```

## Test Configuration

### Test Environment Configuration

```php
// config/statecraft.php or in your tests
return [
    'events' => [
        'enabled' => env('STATECRAFT_EVENTS_ENABLED', !app()->environment('testing')),
    ],
    'history' => [
        'enabled' => env('STATECRAFT_HISTORY_ENABLED', app()->environment('testing')),
        'table' => 'state_machine_history',
    ],
];
```

### Database Setup for Testing

```php
// tests/TestCase.php
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable history for testing
        config(['statecraft.history.enabled' => true]);
        
        // Create history table
        Schema::create('state_machine_history', function (Blueprint $table) {
            $table->id();
            $table->morphs('model');
            $table->string('from_state')->nullable();
            $table->string('to_state');
            $table->string('guard')->nullable();
            $table->string('action')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
}
```

### Mocking and Stubbing

### Mocking Guards

```php
class OrderGuardMockTest extends TestCase
{
    public function test_with_mocked_guard(): void
    {
        $mockGuard = $this->createMock(IsManager::class);
        $mockGuard->method('check')->willReturn(true);
        
        $this->app->instance(IsManager::class, $mockGuard);
        
        $order = Order::factory()->pending()->create();
        
        StateMachineTester::assertTransitionAllowed($order, 'approved');
    }
}
```

### Mocking Actions

```php
class OrderActionMockTest extends TestCase
{
    public function test_with_mocked_action(): void
    {
        $mockAction = $this->createMock(SendConfirmationEmail::class);
        $mockAction->expects($this->once())->method('execute');
        
        $this->app->instance(SendConfirmationEmail::class, $mockAction);
        
        $order = Order::factory()->pending()->create();
        $order->approve();
        
        StateMachineTester::assertInState($order, 'approved');
    }
}
```

## Best Practices

1. **Use Factories**: Create model factories for different states
2. **Test Guards Separately**: Test guard logic independently
3. **Test Actions Separately**: Test action logic independently
4. **Mock External Services**: Mock external dependencies in actions
5. **Use Assertions**: Use StateMachineTester assertions for clarity
6. **Test Error Conditions**: Test invalid transitions and guard failures
7. **Performance Testing**: Test bulk operations and complex workflows
8. **Clean State**: Reset configuration between tests

These testing utilities provide comprehensive coverage for your state machine functionality, ensuring robust and reliable workflow behavior in your Laravel applications.