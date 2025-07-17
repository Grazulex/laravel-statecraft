<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Support\StateMachineTester;
use Tests\Fixtures\TestOrder;

describe('StateMachineTester (Support)', function () {
    beforeEach(function () {
        // Set up the correct path for test YAML files
        config(['statecraft.state_machines_path' => __DIR__.'/../Fixtures/yaml']);
    });

    test('assertTransitionAllowed works for allowed transitions', function () {
        $order = new TestOrder();
        $order->state = 'draft';

        // Test that the method exists and is static
        expect(method_exists(StateMachineTester::class, 'assertTransitionAllowed'))->toBeTrue();
        expect(is_callable([StateMachineTester::class, 'assertTransitionAllowed']))->toBeTrue();

        // The method should not throw an exception for valid transitions
        // Note: We can't test actual transitions without proper mocking
        // but we can test the method structure
        expect(fn () => StateMachineTester::assertTransitionAllowed($order, 'pending'))->not->toThrow(Exception::class);
    });

    test('assertTransitionBlocked works for blocked transitions', function () {
        $order = new TestOrder();
        $order->state = 'draft';

        // Test that the method exists and is static
        expect(method_exists(StateMachineTester::class, 'assertTransitionBlocked'))->toBeTrue();
        expect(is_callable([StateMachineTester::class, 'assertTransitionBlocked']))->toBeTrue();

        // The method should not throw an exception for blocked transitions
        expect(fn () => StateMachineTester::assertTransitionBlocked($order, 'completed'))->not->toThrow(Exception::class);
    });

    test('transitionsFor returns available transitions', function () {
        $order = new TestOrder();
        $order->state = 'draft';

        // Test that the method exists and is static
        expect(method_exists(StateMachineTester::class, 'transitionsFor'))->toBeTrue();
        expect(is_callable([StateMachineTester::class, 'transitionsFor']))->toBeTrue();

        // The method should return an array
        $transitions = StateMachineTester::transitionsFor($order);
        expect($transitions)->toBeArray();
        expect($transitions)->toHaveKey('draft');
    });

    test('assertInState works for correct states', function () {
        $order = new TestOrder();
        $order->state = 'draft';

        // Test that the method exists and is static
        expect(method_exists(StateMachineTester::class, 'assertInState'))->toBeTrue();
        expect(is_callable([StateMachineTester::class, 'assertInState']))->toBeTrue();

        // The method should not throw an exception for correct state
        expect(fn () => StateMachineTester::assertInState($order, 'draft'))->not->toThrow(Exception::class);
    });

    test('assertHasAvailableTransitions works correctly', function () {
        $order = new TestOrder();
        $order->state = 'draft';

        // Test that the method exists and is static
        expect(method_exists(StateMachineTester::class, 'assertHasAvailableTransitions'))->toBeTrue();
        expect(is_callable([StateMachineTester::class, 'assertHasAvailableTransitions']))->toBeTrue();

        // The method should not throw an exception for valid transitions
        expect(fn () => StateMachineTester::assertHasAvailableTransitions($order, ['pending', 'cancelled']))->not->toThrow(Exception::class);
    });

    test('methods fail with models that do not use HasStateMachine trait', function () {
        $model = new class extends Illuminate\Database\Eloquent\Model {};

        // All methods should fail with models that don't use the trait
        expect(fn () => StateMachineTester::assertTransitionAllowed($model, 'pending'))
            ->toThrow(PHPUnit\Framework\AssertionFailedError::class, 'Model must use HasStateMachine trait');

        expect(fn () => StateMachineTester::assertTransitionBlocked($model, 'pending'))
            ->toThrow(PHPUnit\Framework\AssertionFailedError::class, 'Model must use HasStateMachine trait');

        expect(fn () => StateMachineTester::transitionsFor($model))
            ->toThrow(PHPUnit\Framework\AssertionFailedError::class, 'Model must use HasStateMachine trait');

        expect(fn () => StateMachineTester::assertInState($model, 'draft'))
            ->toThrow(PHPUnit\Framework\AssertionFailedError::class, 'Model must use HasStateMachine trait');

        expect(fn () => StateMachineTester::assertHasAvailableTransitions($model, ['pending']))
            ->toThrow(PHPUnit\Framework\AssertionFailedError::class, 'Model must use HasStateMachine trait');
    });

    test('class is final and only has static methods', function () {
        $reflection = new ReflectionClass(StateMachineTester::class);

        // The class should be final
        expect($reflection->isFinal())->toBeTrue();

        // All public methods should be static
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            expect($method->isStatic())->toBeTrue();
        }
    });

    test('can method calls work correctly', function () {
        $order = new TestOrder();
        $order->state = 'draft';

        // Test that the canMethod generation works via __call
        // These methods don't exist statically but are handled by __call
        expect(is_callable([$order, 'canPending']))->toBeTrue();
        expect(is_callable([$order, 'canApproved']))->toBeTrue();

        // The can methods should return boolean values
        expect($order->canPending())->toBeBool();
        expect($order->canApproved())->toBeBool();
    });

    test('transitionsFor returns correct format', function () {
        $order = new TestOrder();
        $order->state = 'draft';

        $transitions = StateMachineTester::transitionsFor($order);

        // Should return array with current state as key
        expect($transitions)->toBeArray();
        expect($transitions)->toHaveKey('draft');
        expect($transitions['draft'])->toBeArray();

        // Each transition should be a string
        foreach ($transitions['draft'] as $transition) {
            expect($transition)->toBeString();
        }
    });
});
