# Events

Laravel Statecraft dispatches Laravel events during state transitions, allowing you to hook into the workflow lifecycle.

## Event Types

### StateTransitioning Event

Dispatched **before** a state transition occurs.

```php
use Grazulex\LaravelStatecraft\Events\StateTransitioning;

Event::listen(StateTransitioning::class, function (StateTransitioning $event) {
    // Access event properties
    $event->model;  // The model being transitioned
    $event->from;   // From state
    $event->to;     // To state
    $event->guard;  // Guard class name (if any)
    $event->action; // Action class name (if any)
});
```

### StateTransitioned Event

Dispatched **after** a state transition completes successfully.

```php
use Grazulex\LaravelStatecraft\Events\StateTransitioned;

Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    // Transition completed successfully
    $event->model;  // The model that was transitioned
    $event->from;   // From state
    $event->to;     // To state
    $event->guard;  // Guard class name (if any)
    $event->action; // Action class name (if any)
});
```

## Event Configuration

Events can be enabled/disabled in configuration:

```php
// config/statecraft.php
'events' => [
    'enabled' => true, // Set to false to disable events
],
```

## Listening to Events

### Using Event Listeners

**Create a listener**:
```bash
php artisan make:listener OrderStateTransitionListener
```

**Register the listener**:
```php
// app/Providers/EventServiceProvider.php
use Grazulex\LaravelStatecraft\Events\StateTransitioned;
use App\Listeners\OrderStateTransitionListener;

protected $listen = [
    StateTransitioned::class => [
        OrderStateTransitionListener::class,
    ],
];
```

**Implement the listener**:
```php
// app/Listeners/OrderStateTransitionListener.php
use Grazulex\LaravelStatecraft\Events\StateTransitioned;

class OrderStateTransitionListener
{
    public function handle(StateTransitioned $event): void
    {
        if ($event->model instanceof Order) {
            // Handle order state transitions
            $this->handleOrderTransition($event);
        }
    }
    
    private function handleOrderTransition(StateTransitioned $event): void
    {
        match ($event->to) {
            'approved' => $this->sendApprovalNotification($event->model),
            'rejected' => $this->sendRejectionNotification($event->model),
            'shipped' => $this->updateShippingStatus($event->model),
            default => null,
        };
    }
}
```

## Guard Expressions in Events

When using guard expressions, the event properties contain serialized information about the complex guard logic:

```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    // For simple guards
    if (is_string($event->guard)) {
        Log::info("Guard: {$event->guard}");
    }
    
    // For guard expressions
    if (is_array($event->guard)) {
        Log::info("Guard Expression: " . json_encode($event->guard));
        // Example output: {"and":["IsManager","HasMinimumAmount"]}
    }
});
```

### Guard Expression Event Examples

```php
// AND expression event
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    if (is_array($event->guard) && isset($event->guard['and'])) {
        $guards = $event->guard['and'];
        Log::info("All guards passed: " . implode(', ', $guards));
    }
});

// OR expression event  
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    if (is_array($event->guard) && isset($event->guard['or'])) {
        $guards = $event->guard['or'];
        Log::info("One of these guards passed: " . implode(', ', $guards));
    }
});

// NOT expression event
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    if (is_array($event->guard) && isset($event->guard['not'])) {
        $guard = $event->guard['not'];
        Log::info("Guard was false (inverted): {$guard}");
    }
});
```

### Using Closures

```php
// In a service provider
use Grazulex\LaravelStatecraft\Events\StateTransitioning;
use Grazulex\LaravelStatecraft\Events\StateTransitioned;

Event::listen(StateTransitioning::class, function (StateTransitioning $event) {
    Log::info("Transitioning {$event->model->id} from {$event->from} to {$event->to}");
});

Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    Log::info("Transitioned {$event->model->id} from {$event->from} to {$event->to}");
});
```

## Practical Examples

### Audit Logging

```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    AuditLog::create([
        'model_type' => get_class($event->model),
        'model_id' => $event->model->id,
        'action' => 'state_transition',
        'old_values' => ['state' => $event->from],
        'new_values' => ['state' => $event->to],
        'user_id' => auth()->id(),
        'guard' => is_array($event->guard) ? json_encode($event->guard) : $event->guard,
        'guard_type' => is_array($event->guard) ? 'expression' : 'simple',
        'action_class' => $event->action,
    ]);
});
```

### Notifications

```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    if ($event->model instanceof Order && $event->to === 'approved') {
        $event->model->customer->notify(new OrderApprovedNotification($event->model));
    }
});
```

### Cache Invalidation

```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    // Clear caches related to the model
    Cache::forget("model_stats_{$event->model->id}");
    Cache::tags(['orders', 'user_'.$event->model->user_id])->flush();
});
```

### Webhook Notifications

```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    if ($event->model instanceof Order) {
        Http::post(config('webhooks.order_state_changed'), [
            'order_id' => $event->model->id,
            'from_state' => $event->from,
            'to_state' => $event->to,
            'timestamp' => now()->toISOString(),
        ]);
    }
});
```

### Metrics and Analytics

```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    // Track state transition metrics
    Metrics::increment('state_transitions_total', [
        'model' => class_basename($event->model),
        'from' => $event->from,
        'to' => $event->to,
    ]);
    
    // Track transition duration if available
    if ($event->model->hasAttribute('state_changed_at')) {
        $duration = now()->diffInSeconds($event->model->state_changed_at);
        Metrics::histogram('state_transition_duration', $duration, [
            'model' => class_basename($event->model),
            'state' => $event->from,
        ]);
    }
});
```

## Model-Specific Events

### Custom Model Events

You can create model-specific events:

```php
// app/Events/OrderStateChanged.php
class OrderStateChanged
{
    public function __construct(
        public Order $order,
        public string $from,
        public string $to
    ) {}
}

// In your listener
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    if ($event->model instanceof Order) {
        event(new OrderStateChanged($event->model, $event->from, $event->to));
    }
});
```

### Model Observer Integration

```php
// app/Observers/OrderObserver.php
class OrderObserver
{
    public function __construct()
    {
        Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
            if ($event->model instanceof Order) {
                $this->handleStateTransition($event);
            }
        });
    }
    
    private function handleStateTransition(StateTransitioned $event): void
    {
        // Handle state-specific logic
        match ($event->to) {
            'approved' => $this->processApproval($event->model),
            'shipped' => $this->updateInventory($event->model),
            'delivered' => $this->completeOrder($event->model),
            default => null,
        };
    }
}
```

## Event Filtering

### Filter by Model Type

```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    if (!$event->model instanceof Order) {
        return; // Only handle Order transitions
    }
    
    // Handle order-specific logic
});
```

### Filter by State

```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    if (!in_array($event->to, ['approved', 'rejected'])) {
        return; // Only handle approval/rejection
    }
    
    // Handle approval/rejection logic
});
```

### Filter by Guard/Action

```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    if ($event->guard === 'App\\Guards\\ManagerApproval') {
        // Only handle manager-approved transitions
        $this->notifyManagement($event);
    }
});
```

## Event Queues

For heavy processing, queue event listeners:

```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    // Queue heavy processing
    ProcessStateTransition::dispatch($event->model, $event->from, $event->to);
});
```

**Job implementation**:
```php
class ProcessStateTransition implements ShouldQueue
{
    public function __construct(
        public Model $model,
        public string $from,
        public string $to
    ) {}
    
    public function handle(): void
    {
        // Heavy processing logic
        $this->updateExternalSystems();
        $this->generateReports();
        $this->sendNotifications();
    }
}
```

## Testing Events

### Asserting Events

```php
use Illuminate\Support\Facades\Event;

public function test_state_transition_dispatches_event(): void
{
    Event::fake();
    
    $order = Order::factory()->create(['status' => 'pending']);
    $order->approve();
    
    Event::assertDispatched(StateTransitioned::class, function ($event) {
        return $event->model instanceof Order 
            && $event->from === 'pending' 
            && $event->to === 'approved';
    });
}
```

### Disabling Events in Tests

```php
// In your test
config(['statecraft.events.enabled' => false]);

// Or use Event::fake()
Event::fake();
```

## Performance Considerations

- **Event Listeners**: Keep listeners lightweight
- **Queues**: Use queues for heavy processing
- **Filtering**: Filter events early to avoid unnecessary processing
- **Caching**: Cache frequently accessed data in listeners
- **Batching**: Batch similar operations when possible

## Error Handling

```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    try {
        // Your event handling logic
        $this->processTransition($event);
    } catch (Exception $e) {
        // Log error but don't fail the transition
        Log::error('State transition event handler failed', [
            'model' => get_class($event->model),
            'model_id' => $event->model->id,
            'from' => $event->from,
            'to' => $event->to,
            'error' => $e->getMessage(),
        ]);
    }
});
```

## Event Debugging

Enable event logging for debugging:

```php
Event::listen('*', function ($eventName, $data) {
    if (str_starts_with($eventName, 'Grazulex\\LaravelStatecraft\\Events\\')) {
        Log::debug("State machine event: {$eventName}", $data);
    }
});
```

Events provide a powerful way to extend Laravel Statecraft's functionality and integrate with your application's business logic.