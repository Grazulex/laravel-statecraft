# Laravel Statecraft Event Usage Examples

## Basic Usage with Closures

```php
use Grazulex\LaravelStatecraft\Events\StateTransitioning;
use Grazulex\LaravelStatecraft\Events\StateTransitioned;
use Illuminate\Support\Facades\Event;

// Listen to transitions in progress
Event::listen(StateTransitioning::class, function (StateTransitioning $event) {
    ray("🔄 Transition: {$event->from} → {$event->to}");
    
    // Access properties
    $model = $event->model;  // The model being transitioned
    $from = $event->from;    // Source state
    $to = $event->to;        // Destination state
    $guard = $event->guard;  // Guard name or expression (if present)
    $action = $event->action; // Action name (if present)
    
    // Handle guard expressions
    if (is_array($guard)) {
        ray("🛡️ Guard Expression: " . json_encode($guard));
    } elseif (is_string($guard)) {
        ray("🛡️ Simple Guard: {$guard}");
    }
});

// Listen to completed transitions
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    Log::info("✅ Transition completed: {$event->from} → {$event->to}", [
        'model' => get_class($event->model),
        'model_id' => $event->model->id,
    ]);
});
```

## Usage with EventServiceProvider

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    \Grazulex\LaravelStatecraft\Events\StateTransitioning::class => [
        \App\Listeners\LogTransitionStart::class,
    ],
    \Grazulex\LaravelStatecraft\Events\StateTransitioned::class => [
        \App\Listeners\LogTransitionEnd::class,
        \App\Listeners\SendNotifications::class,
    ],
];
```

## Practical Examples

### 1. Audit Logging
```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    AuditLog::create([
        'model_type' => get_class($event->model),
        'model_id' => $event->model->id,
        'action' => 'state_transition',
        'old_values' => ['state' => $event->from],
        'new_values' => ['state' => $event->to],
        'user_id' => auth()->id(),
        'guard' => $event->guard,
        'action_class' => $event->action,
    ]);
});
```

### 2. Automatic Notifications
```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    if ($event->model instanceof Order) {
        match ($event->to) {
            'approved' => $event->model->customer->notify(new OrderApprovedNotification($event->model)),
            'shipped' => $event->model->customer->notify(new OrderShippedNotification($event->model)),
            'delivered' => $event->model->customer->notify(new OrderDeliveredNotification($event->model)),
            default => null,
        };
    }
});
```

### 3. External Service Integration
```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    if ($event->model instanceof Order && $event->to === 'approved') {
        // Trigger payment processing
        PaymentService::processPayment($event->model);
        
        // Notify webhook
        Http::post(config('webhooks.order_approved'), [
            'order_id' => $event->model->id,
            'from_state' => $event->from,
            'to_state' => $event->to,
            'timestamp' => now()->toISOString(),
        ]);
    }
});
```

### 4. Metrics and Analytics
```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    Metrics::increment('state_transitions_total', [
        'model' => class_basename($event->model),
        'from' => $event->from,
        'to' => $event->to,
    ]);
});
```

## Configuration

Events can be disabled via configuration:

```php
// config/statecraft.php
'events' => [
    'enabled' => false, // Disable events
],
```

## Testing

```php
public function test_events_are_dispatched(): void
{
    Event::fake();
    
    $order = Order::factory()->create(['status' => 'draft']);
    $order->submit();
    
    Event::assertDispatched(StateTransitioning::class);
    Event::assertDispatched(StateTransitioned::class);
}
```

## Best Practices

1. **Keep listeners lightweight** - Use queues for heavy operations
2. **Filter early** - Check model type at the beginning of the listener
3. **Error handling** - Wrap logic in try-catch blocks
4. **Testing** - Test your listeners in isolation
5. **Documentation** - Document side effects of your listeners

## Guard Expressions in Events

When using guard expressions, events contain the full expression structure for advanced handling:

```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    // Simple guard
    if (is_string($event->guard)) {
        Log::info("Guard: {$event->guard}");
    }
    
    // Guard expressions
    if (is_array($event->guard)) {
        $this->handleGuardExpression($event->guard, $event);
    }
});

private function handleGuardExpression(array $expression, StateTransitioned $event): void
{
    if (isset($expression['and'])) {
        Log::info("All guards passed: " . implode(', ', $expression['and']));
    } elseif (isset($expression['or'])) {
        Log::info("One of these guards passed: " . implode(', ', $expression['or']));
    } elseif (isset($expression['not'])) {
        Log::info("Guard was false (inverted): {$expression['not']}");
    }
}
```

### Metrics for Guard Expressions

```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    $guardType = is_array($event->guard) ? 'expression' : 'simple';
    
    Metrics::increment('guard_usage', [
        'type' => $guardType,
        'model' => class_basename($event->model),
    ]);
    
    // Track expression complexity
    if (is_array($event->guard)) {
        $complexity = $this->calculateComplexity($event->guard);
        Metrics::histogram('guard_expression_complexity', $complexity);
    }
});
```

## Advanced Usage

### Conditional Event Handling
```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    // Only handle Order models
    if (!$event->model instanceof Order) {
        return;
    }
    
    // Only handle specific transitions
    if ($event->from === 'pending' && $event->to === 'approved') {
        // Handle specific transition
    }
});
```

### Queued Event Listeners
```php
// app/Listeners/ProcessOrderApproval.php
class ProcessOrderApproval implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(StateTransitioned $event): void
    {
        if ($event->model instanceof Order && $event->to === 'approved') {
            // Heavy processing that should be queued
            ProcessPaymentJob::dispatch($event->model);
            UpdateInventoryJob::dispatch($event->model);
        }
    }
}
```

### Event Listener with Dependency Injection
```php
class OrderStateListener
{
    public function __construct(
        private NotificationService $notifications,
        private AuditService $audit
    ) {}

    public function handleTransitioning(StateTransitioning $event): void
    {
        $this->audit->logTransitionStart($event->model, $event->from, $event->to);
    }

    public function handleTransitioned(StateTransitioned $event): void
    {
        $this->audit->logTransitionEnd($event->model, $event->from, $event->to);
        $this->notifications->sendStateChangeNotification($event->model, $event->to);
    }
}
```
