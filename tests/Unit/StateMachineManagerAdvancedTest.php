<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Contracts\Action;
use Grazulex\LaravelStatecraft\Contracts\Guard;
use Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition;
use Grazulex\LaravelStatecraft\Events\StateTransitioned;
use Grazulex\LaravelStatecraft\Events\StateTransitioning;
use Grazulex\LaravelStatecraft\Exceptions\InvalidTransitionException;
use Grazulex\LaravelStatecraft\Support\StateMachineManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\Fixtures\Order;

describe('StateMachineManager - Advanced Tests', function () {
    beforeEach(function () {
        // Create a test definition
        $this->definition = new StateMachineDefinition(
            name: 'test-workflow',
            model: Order::class,
            states: ['draft', 'pending', 'approved', 'rejected'],
            initial: 'draft',
            transitions: [
                [
                    'from' => 'draft',
                    'to' => 'pending',
                    'guard' => 'TestGuard',
                    'action' => 'TestAction',
                ],
                [
                    'from' => 'pending',
                    'to' => 'approved',
                    'guard' => null,
                    'action' => null,
                ],
                [
                    'from' => 'pending',
                    'to' => 'rejected',
                    'guard' => null,
                    'action' => null,
                ],
            ]
        );

        $this->manager = new StateMachineManager($this->definition);
    });

    test('canTransition returns false when guard check fails', function () {
        // Create a guard that always returns false
        $guard = new class implements Guard
        {
            public function check(Model $model, string $from, string $to): bool
            {
                return false;
            }
        };

        $this->app->bind('TestGuard', fn () => $guard);

        $order = new Order(['state' => 'draft']);

        $result = $this->manager->canTransition($order, 'pending');
        expect($result)->toBeFalse();
    });

    test('transition throws exception when transition is not allowed', function () {
        // Create a guard that always returns false
        $guard = new class implements Guard
        {
            public function check(Model $model, string $from, string $to): bool
            {
                return false;
            }
        };

        $this->app->bind('TestGuard', fn () => $guard);

        $order = new Order(['state' => 'draft']);

        expect(function () use ($order) {
            $this->manager->transition($order, 'pending');
        })->toThrow(InvalidTransitionException::class, 'Transition from draft to pending is not allowed');
    });

    test('transition dispatches events when enabled', function () {
        Event::fake();

        // Create a guard that always returns true
        $guard = new class implements Guard
        {
            public function check(Model $model, string $from, string $to): bool
            {
                return true;
            }
        };

        // Create an action
        $action = new class implements Action
        {
            public function execute(Model $model, string $from, string $to): void
            {
                // Do nothing
            }
        };

        $this->app->bind('TestGuard', fn () => $guard);
        $this->app->bind('TestAction', fn () => $action);

        config(['statecraft.events.enabled' => true]);

        $order = new Order(['state' => 'draft']);

        $this->manager->transition($order, 'pending');

        Event::assertDispatched(StateTransitioning::class);
        Event::assertDispatched(StateTransitioned::class);
    });

    test('transition skips events when disabled', function () {
        Event::fake();

        // Create a guard that always returns true
        $guard = new class implements Guard
        {
            public function check(Model $model, string $from, string $to): bool
            {
                return true;
            }
        };

        // Create an action
        $action = new class implements Action
        {
            public function execute(Model $model, string $from, string $to): void
            {
                // Do nothing
            }
        };

        $this->app->bind('TestGuard', fn () => $guard);
        $this->app->bind('TestAction', fn () => $action);

        config(['statecraft.events.enabled' => false]);

        $order = new Order(['state' => 'draft']);

        $this->manager->transition($order, 'pending');

        Event::assertNotDispatched(StateTransitioning::class);
        Event::assertNotDispatched(StateTransitioned::class);
    });

    test('transition works without guard and action', function () {
        $order = new Order(['state' => 'pending']);

        $this->manager->transition($order, 'approved');

        expect($order->state)->toBe('approved');
    });

    test('getCurrentState returns initial state when model has no state', function () {
        $order = new Order(); // No state set

        $currentState = $this->manager->getCurrentState($order);
        expect($currentState)->toBe('draft');
    });

    test('initialize sets initial state when model has no state', function () {
        $order = new Order(); // No state set

        $this->manager->initialize($order);

        expect($order->getAttribute('state'))->toBe('draft');
    });

    test('initialize does not override existing state', function () {
        $order = new Order(['state' => 'pending']);

        $this->manager->initialize($order);

        expect($order->getAttribute('state'))->toBe('pending');
    });

    test('resolveGuard throws exception when guard does not implement Guard interface', function () {
        // Create a separate definition for this test
        $invalidDefinition = new StateMachineDefinition(
            name: 'invalid-workflow',
            model: Order::class,
            states: ['draft', 'pending'],
            initial: 'draft',
            transitions: [
                [
                    'from' => 'draft',
                    'to' => 'pending',
                    'guard' => 'InvalidGuard',
                    'action' => null,
                ],
            ]
        );

        $invalidManager = new StateMachineManager($invalidDefinition);

        // Bind a class that doesn't implement Guard
        $this->app->bind('InvalidGuard', fn () => new stdClass());

        $order = new Order(['state' => 'draft']);

        expect(function () use ($invalidManager, $order) {
            $invalidManager->canTransition($order, 'pending');
        })->toThrow(InvalidTransitionException::class, 'Guard InvalidGuard must implement Guard interface');
    });

    test('resolveAction throws exception when action does not implement Action interface', function () {
        // Create a separate definition for this test
        $invalidDefinition = new StateMachineDefinition(
            name: 'invalid-workflow',
            model: Order::class,
            states: ['draft', 'pending'],
            initial: 'draft',
            transitions: [
                [
                    'from' => 'draft',
                    'to' => 'pending',
                    'guard' => 'ValidGuard',
                    'action' => 'InvalidAction',
                ],
            ]
        );

        $invalidManager = new StateMachineManager($invalidDefinition);

        // Create a guard that always returns true
        $guard = new class implements Guard
        {
            public function check(Model $model, string $from, string $to): bool
            {
                return true;
            }
        };

        $this->app->bind('ValidGuard', fn () => $guard);
        $this->app->bind('InvalidAction', fn () => new stdClass());

        $order = new Order(['state' => 'draft']);

        expect(function () use ($invalidManager, $order) {
            $invalidManager->transition($order, 'pending');
        })->toThrow(InvalidTransitionException::class, 'Action InvalidAction must implement Action interface');
    });

    test('transition calls recordStateTransition when method exists', function () {
        // Create a mock order with recordStateTransition method
        $order = Mockery::mock(Order::class)->makePartial();
        $order->shouldReceive('getAttribute')->with('state')->andReturn('pending');
        $order->shouldReceive('setAttribute')->with('state', 'approved');
        $order->shouldReceive('save');
        $order->shouldReceive('recordStateTransition')
            ->once()
            ->with('pending', 'approved', null, null);

        $this->manager->transition($order, 'approved');
    });

    test('getAvailableTransitions returns only allowed transitions', function () {
        // Create a guard that allows only specific transitions
        $guard = new class implements Guard
        {
            public function check(Model $model, string $from, string $to): bool
            {
                return $to === 'approved'; // Only allow approved
            }
        };

        $this->app->bind('TestGuard', fn () => $guard);

        $order = new Order(['state' => 'draft']);

        $availableTransitions = $this->manager->getAvailableTransitions($order);

        expect($availableTransitions)->toHaveCount(0); // No transitions from draft are allowed

        // Test from pending state (has transitions to approved and rejected, but guard only allows approved)
        $order->setAttribute('state', 'pending');
        $availableTransitions = $this->manager->getAvailableTransitions($order);

        // Should have 2 transitions (approved and rejected) because rejected has no guard
        expect($availableTransitions)->toHaveCount(2);

        // Check that approved is in the list
        $transitionsTo = array_column($availableTransitions, 'to');
        expect($transitionsTo)->toContain('approved');
        expect($transitionsTo)->toContain('rejected');
    });

    test('getDefinition returns the state machine definition', function () {
        $definition = $this->manager->getDefinition();

        expect($definition)->toBe($this->definition);
        expect($definition->getName())->toBe('test-workflow');
    });

    test('transition with action executes action correctly', function () {
        $actionExecuted = false;

        // Create a guard that always returns true
        $guard = new class implements Guard
        {
            public function check(Model $model, string $from, string $to): bool
            {
                return true;
            }
        };

        // Create an action that sets a flag
        $action = new class implements Action
        {
            public static bool $executed = false;

            public function execute(Model $model, string $from, string $to): void
            {
                self::$executed = true;
            }
        };

        $this->app->bind('TestGuard', fn () => $guard);
        $this->app->bind('TestAction', fn () => $action);

        $order = new Order(['state' => 'draft']);

        $this->manager->transition($order, 'pending');

        expect($action::$executed)->toBeTrue();
    });

    test('canTransition with null guard returns true', function () {
        $order = new Order(['state' => 'pending']);

        $result = $this->manager->canTransition($order, 'approved');
        expect($result)->toBeTrue();
    });
});
