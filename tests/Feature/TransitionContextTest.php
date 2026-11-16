<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Contracts\Action;
use Grazulex\LaravelStatecraft\Contracts\ContextAwareAction;
use Grazulex\LaravelStatecraft\Contracts\ContextAwareGuard;
use Grazulex\LaravelStatecraft\Contracts\Guard;
use Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition;
use Grazulex\LaravelStatecraft\Events\StateTransitioned;
use Grazulex\LaravelStatecraft\Events\StateTransitioning;
use Grazulex\LaravelStatecraft\Models\StateTransition;
use Grazulex\LaravelStatecraft\Support\StateMachineManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\Fixtures\Order;

/**
 * Build a manager with a single draft -> pending transition.
 */
function contextTestManager(string|array|null $guard = null, ?string $action = null): StateMachineManager
{
    return new StateMachineManager(new StateMachineDefinition(
        name: 'context-workflow',
        model: Order::class,
        states: ['draft', 'pending'],
        initial: 'draft',
        transitions: [
            ['from' => 'draft', 'to' => 'pending', 'guard' => $guard, 'action' => $action],
        ],
    ));
}

/**
 * Attach a manager to a model using the HasStateMachine trait.
 */
function attachContextTestManager(Order $order, StateMachineManager $manager): void
{
    $property = new ReflectionProperty(Order::class, 'stateMachineManager');
    $property->setValue($order, $manager);
}

/**
 * Guard requiring a "reason" key in the context.
 */
function contextTestReasonGuard(): ContextAwareGuard
{
    return new class implements ContextAwareGuard
    {
        public array $received = [];

        public function check(Model $model, string $from, string $to, array $context = []): bool
        {
            $this->received = $context;

            return ! empty($context['reason']);
        }
    };
}

/**
 * Action recording the context it receives.
 */
function contextTestRecordingAction(): ContextAwareAction
{
    return new class implements ContextAwareAction
    {
        public ?array $received = null;

        public function execute(Model $model, string $from, string $to, array $context = []): void
        {
            $this->received = $context;
        }
    };
}

/**
 * Order recording calls to recordStateTransition.
 */
function contextTestRecordingOrder(): Order
{
    return new class(['state' => 'draft']) extends Order
    {
        public array $recorded = [];

        public function recordStateTransition(string $fromState, string $toState, ?string $guard = null, ?string $action = null, array $metadata = []): void
        {
            $this->recorded[] = [
                'from' => $fromState,
                'to' => $toState,
                'metadata' => $metadata,
            ];
        }
    };
}

describe('Transition context', function () {
    test('context is passed to context-aware guards', function () {
        $guard = contextTestReasonGuard();
        $this->app->bind('ContextReasonGuard', fn () => $guard);

        $manager = contextTestManager('ContextReasonGuard');
        $order = new Order(['state' => 'draft']);

        expect($manager->canTransition($order, 'pending'))->toBeFalse();
        expect($manager->canTransition($order, 'pending', ['reason' => 'Customer paid']))->toBeTrue();
        expect($guard->received)->toBe(['reason' => 'Customer paid']);
    });

    test('context is passed to context-aware guards inside guard expressions', function () {
        $guard = contextTestReasonGuard();
        $plainGuard = new class implements Guard
        {
            public function check(Model $model, string $from, string $to): bool
            {
                return true;
            }
        };
        $this->app->bind('ContextReasonGuard', fn () => $guard);
        $this->app->bind('ContextPlainGuard', fn () => $plainGuard);

        $manager = contextTestManager(['and' => ['ContextPlainGuard', 'ContextReasonGuard']]);
        $order = new Order(['state' => 'draft']);

        expect($manager->canTransition($order, 'pending'))->toBeFalse();
        expect($manager->canTransition($order, 'pending', ['reason' => 'Approved']))->toBeTrue();
        expect($guard->received)->toBe(['reason' => 'Approved']);
    });

    test('context is passed to context-aware actions', function () {
        $action = contextTestRecordingAction();
        $this->app->bind('ContextRecordingAction', fn () => $action);

        $manager = contextTestManager(null, 'ContextRecordingAction');
        $order = new Order(['state' => 'draft']);

        $manager->transition($order, 'pending', ['reason' => 'Paid', 'actor_id' => 42]);

        expect($order->state)->toBe('pending');
        expect($action->received)->toBe(['reason' => 'Paid', 'actor_id' => 42]);
    });

    test('legacy guards and actions still work when a context is given', function () {
        $guard = new class implements Guard
        {
            public function check(Model $model, string $from, string $to): bool
            {
                return true;
            }
        };
        $action = new class implements Action
        {
            public bool $executed = false;

            public function execute(Model $model, string $from, string $to): void
            {
                $this->executed = true;
            }
        };
        $this->app->bind('ContextLegacyGuard', fn () => $guard);
        $this->app->bind('ContextLegacyAction', fn () => $action);

        $manager = contextTestManager('ContextLegacyGuard', 'ContextLegacyAction');
        $order = new Order(['state' => 'draft']);

        $manager->transition($order, 'pending', ['reason' => 'Ignored by legacy classes']);

        expect($order->state)->toBe('pending');
        expect($action->executed)->toBeTrue();
    });

    test('context is passed to the history as metadata', function () {
        $order = contextTestRecordingOrder();

        contextTestManager()->transition($order, 'pending', ['reason' => 'Paid', 'actor_id' => 7]);

        expect($order->recorded)->toBe([
            ['from' => 'draft', 'to' => 'pending', 'metadata' => ['reason' => 'Paid', 'actor_id' => 7]],
        ]);
    });

    test('history metadata is empty when no context is given', function () {
        $order = contextTestRecordingOrder();

        contextTestManager()->transition($order, 'pending');

        expect($order->recorded[0]['metadata'])->toBe([]);
    });

    test('context is persisted in the state history table', function () {
        $migration = require __DIR__.'/../../database/migrations/2024_01_01_000000_create_state_machine_history_table.php';
        $migration->up();
        config(['statecraft.history.enabled' => true]);

        $order = new Order(['state' => 'draft']);
        $order->setAttribute('id', 1);
        attachContextTestManager($order, contextTestManager());

        expect($order->transition('pending', ['reason' => 'Paid', 'actor_id' => 3]))->toBeTrue();

        /** @var StateTransition $record */
        $record = StateTransition::query()->firstOrFail();
        expect($record->from_state)->toBe('draft');
        expect($record->to_state)->toBe('pending');
        expect($record->metadata)->toBe(['reason' => 'Paid', 'actor_id' => 3]);
        expect($record->custom_data)->toBe(['reason' => 'Paid', 'actor_id' => 3]);
    });

    test('context is exposed on transition events', function () {
        Event::fake();
        config(['statecraft.events.enabled' => true]);

        $order = new Order(['state' => 'draft']);
        contextTestManager()->transition($order, 'pending', ['reason' => 'Paid']);

        Event::assertDispatched(StateTransitioning::class, fn (StateTransitioning $event) => $event->context === ['reason' => 'Paid']);
        Event::assertDispatched(StateTransitioned::class, fn (StateTransitioned $event) => $event->context === ['reason' => 'Paid']);
    });

    test('events have an empty context by default', function () {
        $order = new Order(['state' => 'draft']);

        expect((new StateTransitioning($order, 'draft', 'pending'))->context)->toBe([]);
        expect((new StateTransitioned($order, 'draft', 'pending'))->context)->toBe([]);
    });

    test('model transition and canTransitionTo accept a context', function () {
        $guard = contextTestReasonGuard();
        $this->app->bind('ContextReasonGuard', fn () => $guard);

        $order = new Order(['state' => 'draft']);
        attachContextTestManager($order, contextTestManager('ContextReasonGuard'));

        expect($order->canTransitionTo('pending'))->toBeFalse();
        expect($order->transition('pending'))->toBeFalse();
        expect($order->canTransitionTo('pending', ['reason' => 'Paid']))->toBeTrue();
        expect($order->getAvailableTransitions(['reason' => 'Paid']))->toHaveCount(1);
        expect($order->transition('pending', ['reason' => 'Paid']))->toBeTrue();
        expect($order->getCurrentState())->toBe('pending');
    });

    test('magic transition methods accept a context as first argument', function () {
        $guard = contextTestReasonGuard();
        $this->app->bind('ContextReasonGuard', fn () => $guard);

        $order = new Order(['state' => 'draft']);
        attachContextTestManager($order, contextTestManager('ContextReasonGuard'));

        expect($order->canSubmit(['reason' => 'Ready']))->toBeTrue();

        $order->submit(['reason' => 'Ready']);

        expect($order->getCurrentState())->toBe('pending');
        expect($guard->received)->toBe(['reason' => 'Ready']);
    });
});
