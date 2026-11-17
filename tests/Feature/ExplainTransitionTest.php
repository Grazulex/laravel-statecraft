<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Contracts\ContextAwareGuard;
use Grazulex\LaravelStatecraft\Contracts\Guard;
use Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition;
use Grazulex\LaravelStatecraft\Support\StateMachineManager;
use Illuminate\Database\Eloquent\Model;
use Tests\Fixtures\Order;

/**
 * Build a manager with draft -> pending (guarded) and pending -> approved.
 */
function explainTestManager(string|array|null $guard = null): StateMachineManager
{
    return new StateMachineManager(new StateMachineDefinition(
        name: 'explain-workflow',
        model: Order::class,
        states: ['draft', 'pending', 'approved'],
        initial: 'draft',
        transitions: [
            ['from' => 'draft', 'to' => 'pending', 'guard' => $guard],
            ['from' => 'pending', 'to' => 'approved'],
        ],
    ));
}

function explainTestGuard(bool $result): Guard
{
    return new class($result) implements Guard
    {
        public function __construct(private bool $result) {}

        public function check(Model $model, string $from, string $to): bool
        {
            return $this->result;
        }
    };
}

describe('explainTransition', function () {
    test('returns null when the transition is allowed', function () {
        $order = new Order(['state' => 'draft']);

        expect(explainTestManager()->explainTransition($order, 'pending'))->toBeNull();
    });

    test('reports a current state that does not allow the transition', function () {
        $order = new Order(['state' => 'draft']);

        expect(explainTestManager()->explainTransition($order, 'approved'))
            ->toBe("No transition is defined from the current state 'draft' to 'approved'.");
    });

    test('reports an unknown target state', function () {
        $order = new Order(['state' => 'draft']);

        expect(explainTestManager()->explainTransition($order, 'shipped'))
            ->toBe("State 'shipped' is not defined in the state machine.");
    });

    test('reports the name of the failing guard', function () {
        $this->app->bind('ExplainRefusingGuard', fn () => explainTestGuard(false));

        $order = new Order(['state' => 'draft']);

        expect(explainTestManager('ExplainRefusingGuard')->explainTransition($order, 'pending'))
            ->toBe("Guard 'ExplainRefusingGuard' refused the transition from 'draft' to 'pending'.");
    });

    test('returns null when the guard passes', function () {
        $this->app->bind('ExplainPassingGuard', fn () => explainTestGuard(true));

        $order = new Order(['state' => 'draft']);

        expect(explainTestManager('ExplainPassingGuard')->explainTransition($order, 'pending'))->toBeNull();
    });

    test('reports the failing guard expression', function () {
        $this->app->bind('ExplainPassingGuard', fn () => explainTestGuard(true));
        $this->app->bind('ExplainRefusingGuard', fn () => explainTestGuard(false));

        $order = new Order(['state' => 'draft']);
        $manager = explainTestManager(['and' => ['ExplainPassingGuard', 'ExplainRefusingGuard']]);

        expect($manager->explainTransition($order, 'pending'))
            ->toBe('Guard expression {"and":["ExplainPassingGuard","ExplainRefusingGuard"]} refused the transition from \'draft\' to \'pending\'.');
    });

    test('passes the context to context-aware guards', function () {
        $guard = new class implements ContextAwareGuard
        {
            public function check(Model $model, string $from, string $to, array $context = []): bool
            {
                return ! empty($context['reason']);
            }
        };
        $this->app->bind('ExplainReasonGuard', fn () => $guard);

        $order = new Order(['state' => 'draft']);
        $manager = explainTestManager('ExplainReasonGuard');

        expect($manager->explainTransition($order, 'pending'))->toBeString();
        expect($manager->explainTransition($order, 'pending', ['reason' => 'Paid']))->toBeNull();
    });

    test('is consistent with canTransition', function () {
        $this->app->bind('ExplainRefusingGuard', fn () => explainTestGuard(false));

        $order = new Order(['state' => 'draft']);
        $manager = explainTestManager('ExplainRefusingGuard');

        expect($manager->canTransition($order, 'pending'))->toBeFalse();
        expect($manager->explainTransition($order, 'pending'))->not->toBeNull();
        expect($manager->canTransition($order, 'approved'))->toBeFalse();
        expect($manager->explainTransition($order, 'approved'))->not->toBeNull();
    });

    test('is available on models using the trait', function () {
        $this->app->bind('ExplainRefusingGuard', fn () => explainTestGuard(false));

        $order = new Order(['state' => 'draft']);
        $property = new ReflectionProperty(Order::class, 'stateMachineManager');
        $property->setValue($order, explainTestManager('ExplainRefusingGuard'));

        expect($order->transition('pending'))->toBeFalse();
        expect($order->explainTransition('pending'))
            ->toBe("Guard 'ExplainRefusingGuard' refused the transition from 'draft' to 'pending'.");
        expect($order->explainTransition('approved'))
            ->toBe("No transition is defined from the current state 'draft' to 'approved'.");
    });
});
