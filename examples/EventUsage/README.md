# Exemples d'utilisation des événements Laravel Statecraft

## Usage basique avec closures

```php
use Grazulex\LaravelStatecraft\Events\StateTransitioning;
use Grazulex\LaravelStatecraft\Events\StateTransitioned;
use Illuminate\Support\Facades\Event;

// Écouter les transitions en cours
Event::listen(StateTransitioning::class, function (StateTransitioning $event) {
    ray("🔄 Transition: {$event->from} → {$event->to}");
    
    // Accès aux propriétés
    $model = $event->model;  // Le modèle en transition
    $from = $event->from;    // État source
    $to = $event->to;        // État destination
    $guard = $event->guard;  // Nom du guard (si présent)
    $action = $event->action; // Nom de l'action (si présente)
});

// Écouter les transitions terminées
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    Log::info("✅ Transition terminée: {$event->from} → {$event->to}", [
        'model' => get_class($event->model),
        'model_id' => $event->model->id,
    ]);
});
```

## Usage avec EventServiceProvider

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

## Exemples pratiques

### 1. Audit logging
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

### 2. Notifications automatiques
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

### 3. Intégration avec des services externes
```php
Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
    if ($event->model instanceof Order && $event->to === 'approved') {
        // Déclencher le processus de paiement
        PaymentService::processPayment($event->model);
        
        // Notifier un webhook
        Http::post(config('webhooks.order_approved'), [
            'order_id' => $event->model->id,
            'from_state' => $event->from,
            'to_state' => $event->to,
            'timestamp' => now()->toISOString(),
        ]);
    }
});
```

### 4. Métriques et analytics
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

Les événements peuvent être désactivés via la configuration :

```php
// config/statecraft.php
'events' => [
    'enabled' => false, // Désactive les événements
],
```

## Tests

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

## Bonnes pratiques

1. **Maintenez les listeners légers** - Utilisez des queues pour les opérations lourdes
2. **Filtrez tôt** - Vérifiez le type de modèle dès le début du listener
3. **Gestion d'erreurs** - Encapsulez la logique dans des try-catch
4. **Tests** - Testez vos listeners de manière isolée
5. **Documentation** - Documentez les effets de bord de vos listeners
