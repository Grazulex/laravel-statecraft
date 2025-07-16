<?php

declare(strict_types=1);

namespace Examples\EventUsage;

use Grazulex\LaravelStatecraft\Events\StateTransitioned;
use Grazulex\LaravelStatecraft\Events\StateTransitioning;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Tests\Fixtures\Order;

/**
 * Exemple d'utilisation des événements dans une application Laravel
 */
class EventUsageExample
{
    public function registerEventListeners(): void
    {
        // 1. Logging des transitions
        Event::listen(StateTransitioning::class, function (StateTransitioning $event) {
            Log::info('State transition starting', [
                'model' => get_class($event->model),
                'model_id' => $event->model->id ?? 'new',
                'from' => $event->from,
                'to' => $event->to,
                'guard' => $event->guard,
                'action' => $event->action,
            ]);
        });

        Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
            Log::info('State transition completed', [
                'model' => get_class($event->model),
                'model_id' => $event->model->id ?? 'new',
                'from' => $event->from,
                'to' => $event->to,
                'guard' => $event->guard,
                'action' => $event->action,
            ]);
        });

        // 2. Notifications spécifiques aux commandes
        Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
            if ($event->model instanceof Order) {
                $this->handleOrderStateChange($event);
            }
        });

        // 3. Debugging - Ray integration
        if (function_exists('ray')) {
            Event::listen(StateTransitioning::class, function (StateTransitioning $event) {
                ray("🔄 Transition: {$event->from} → {$event->to}")
                    ->color('blue')
                    ->label('State Machine');
            });

            Event::listen(StateTransitioned::class, function (StateTransitioned $event) {
                ray("✅ Completed: {$event->from} → {$event->to}")
                    ->color('green')
                    ->label('State Machine');
            });
        }
    }

    private function handleOrderStateChange(StateTransitioned $event): void
    {
        $order = $event->model;

        match ($event->to) {
            'pending' => $this->notifyReviewer($order),
            'approved' => $this->notifyCustomer($order, 'approved'),
            'rejected' => $this->notifyCustomer($order, 'rejected'),
            'shipped' => $this->notifyShipping($order),
            'delivered' => $this->completeOrder($order),
            default => null,
        };
    }

    private function notifyReviewer(Order $order): void
    {
        Log::info("📧 Notifying reviewer for order #{$order->id}");
        // Notification::route('mail', 'reviewer@company.com')
        //     ->notify(new OrderPendingReviewNotification($order));
    }

    private function notifyCustomer(Order $order, string $status): void
    {
        Log::info("📧 Notifying customer for order #{$order->id} - Status: {$status}");
        // if ($order->customer_email) {
        //     Notification::route('mail', $order->customer_email)
        //         ->notify(new OrderStatusChangedNotification($order, $status));
        // }
    }

    private function notifyShipping(Order $order): void
    {
        Log::info("📦 Notifying shipping for order #{$order->id}");
        // Http::post(config('shipping.webhook_url'), [
        //     'order_id' => $order->id,
        //     'status' => 'shipped',
        //     'tracking_number' => $order->tracking_number,
        // ]);
    }

    private function completeOrder(Order $order): void
    {
        Log::info("🎉 Order #{$order->id} completed successfully");
        // Analytics::track('order_completed', [
        //     'order_id' => $order->id,
        //     'total_amount' => $order->total_amount,
        //     'customer_id' => $order->customer_id,
        // ]);
    }
}

/**
 * Exemple de service provider pour enregistrer les événements
 */
class StateMachineEventServiceProvider
{
    public function boot(): void
    {
        $eventUsage = new EventUsageExample();
        $eventUsage->registerEventListeners();
    }
}

/**
 * Exemple d'utilisation dans EventServiceProvider de Laravel
 */
class ExampleEventServiceProvider
{
    protected $listen = [
        StateTransitioning::class => [
            'App\Listeners\LogTransitionStart',
            'App\Listeners\ValidateTransition',
        ],
        StateTransitioned::class => [
            'App\Listeners\LogTransitionEnd',
            'App\Listeners\SendNotifications',
            'App\Listeners\UpdateAnalytics',
        ],
    ];
}

/**
 * Exemple de listener dédié
 */
class LogTransitionStartListener
{
    public function handle(StateTransitioning $event): void
    {
        Log::info('State transition starting', [
            'model' => get_class($event->model),
            'from' => $event->from,
            'to' => $event->to,
            'guard' => $event->guard,
            'action' => $event->action,
            'timestamp' => now()->toISOString(),
        ]);
    }
}

/**
 * Exemple de listener avec queue
 */
class SendNotificationsListener
{
    public function handle(StateTransitioned $event): void
    {
        // Queue heavy operations
        if ($event->model instanceof Order) {
            dispatch(new SendOrderNotificationJob($event->model, $event->from, $event->to));
        }
    }
}
