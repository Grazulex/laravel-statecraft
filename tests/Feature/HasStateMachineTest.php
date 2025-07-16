<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Support\StateMachineManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\Order;

uses(RefreshDatabase::class);

describe('HasStateMachine Trait', function () {
    beforeEach(function () {
        // Set up the correct path for test YAML files
        config(['statecraft.state_machines_path' => __DIR__.'/../Fixtures/yaml']);
    });

    test('dynamic method calls work for state transitions', function () {
        $order = new Order(['state' => 'draft']);

        // Test that the submit method exists and works
        expect($order->canSubmit())->toBeTrue();

        // Execute the transition
        $result = $order->submit();

        expect($result)->toBe($order); // Should return the model
        expect($order->state)->toBe('pending');
    });

    test('dynamic can methods work correctly', function () {
        $order = new Order(['state' => 'draft']);

        // Test can methods for valid transitions
        expect($order->canSubmit())->toBeTrue();
        expect($order->canApprove())->toBeFalse(); // Not from draft
        expect($order->canReject())->toBeFalse(); // Not from draft

        // Transition to pending
        $order->submit();

        // Now test from pending state
        expect($order->canSubmit())->toBeFalse(); // Already submitted
        expect($order->canApprove())->toBeTrue();
        expect($order->canReject())->toBeTrue();
    });

    test('getCurrentState returns correct state', function () {
        $order = new Order(['state' => 'draft']);
        expect($order->getCurrentState())->toBe('draft');

        $order->submit();
        expect($order->getCurrentState())->toBe('pending');
    });

    test('getAvailableTransitions returns correct transitions', function () {
        $order = new Order(['state' => 'draft']);
        $transitions = $order->getAvailableTransitions();

        expect($transitions)->toHaveCount(1);
        expect($transitions[0]['to'])->toBe('pending');

        $order->submit();
        $transitions = $order->getAvailableTransitions();

        expect($transitions)->toHaveCount(2);
        $transitionTos = array_column($transitions, 'to');
        expect($transitionTos)->toContain('approved');
        expect($transitionTos)->toContain('rejected');
    });

    test('initializeState sets initial state when empty', function () {
        $order = new Order(); // No state set
        $order->initializeState();

        expect($order->state)->toBe('draft');
    });

    test('initializeState does not override existing state', function () {
        $order = new Order(['state' => 'pending']);
        $order->initializeState();

        expect($order->state)->toBe('pending'); // Should not change
    });

    test('methodToState converts method names to states', function () {
        $order = new Order(['state' => 'draft']);

        // Test common method to state conversions
        expect($order->canSubmit())->toBeTrue();
        expect($order->canApprove())->toBeFalse();
        expect($order->canReject())->toBeFalse();

        // Test after transition
        $order->submit();
        expect($order->canApprove())->toBeTrue();
        expect($order->canReject())->toBeTrue();
    });

    test('invalid method calls are passed to parent', function () {
        $order = new Order(['state' => 'draft']);

        // Test that invalid method calls throw BadMethodCallException
        $this->expectException(BadMethodCallException::class);
        $order->nonExistentMethod();
    });

    test('getStateMachineDefinitionName returns correct name', function () {
        $order = new Order(['state' => 'draft']);

        // Use reflection to access protected method
        $reflection = new ReflectionClass($order);
        $method = $reflection->getMethod('getStateMachineDefinitionName');
        $method->setAccessible(true);

        $result = $method->invoke($order);
        expect($result)->toBe('OrderWorkflow');
    });

    test('getStateMachineManager returns manager instance', function () {
        $order = new Order(['state' => 'draft']);

        // Use reflection to access protected method
        $reflection = new ReflectionClass($order);
        $method = $reflection->getMethod('getStateMachineManager');
        $method->setAccessible(true);

        $manager = $method->invoke($order);
        expect($manager)->toBeInstanceOf(StateMachineManager::class);
    });

    test('bootHasStateMachine initializes state on creating', function () {
        // Mock the creating event
        $order = new Order();

        // Simulate the creating event
        $order->initializeState();

        expect($order->state)->toBe('draft');
    });

    test('state machine caching works', function () {
        $order = new Order(['state' => 'draft']);

        // Test that calling state machine methods twice works consistently
        $state1 = $order->getCurrentState();
        $state2 = $order->getCurrentState();

        expect($state1)->toBe($state2); // Should be the same state
    });

    test('complex method to state conversion patterns', function () {
        $order = new Order(['state' => 'draft']);

        // Test various method patterns that should be converted
        $reflection = new ReflectionClass($order);
        $method = $reflection->getMethod('methodToState');
        $method->setAccessible(true);

        // Test common patterns
        expect($method->invoke($order, 'submit'))->toBe('pending');
        expect($method->invoke($order, 'approve'))->toBe('approved');
        expect($method->invoke($order, 'reject'))->toBe('rejected');
        expect($method->invoke($order, 'publish'))->toBe('published');
        expect($method->invoke($order, 'archive'))->toBe('archived');
        expect($method->invoke($order, 'activate'))->toBe('active');
        expect($method->invoke($order, 'deactivate'))->toBe('inactive');
        expect($method->invoke($order, 'complete'))->toBe('completed');
        expect($method->invoke($order, 'cancel'))->toBe('cancelled');

        // Test fallback for unknown methods
        expect($method->invoke($order, 'customMethod'))->toBe('custom_method');
    });

    test('transitions work with different states', function () {
        $order = new Order(['state' => 'pending']);

        // Test approve transition
        expect($order->canApprove())->toBeTrue();
        $order->approve();
        expect($order->state)->toBe('approved');

        // Create another order for reject test
        $order2 = new Order(['state' => 'pending']);
        expect($order2->canReject())->toBeTrue();
        $order2->reject();
        expect($order2->state)->toBe('rejected');
    });

    test('method resolution handles camelCase properly', function () {
        $order = new Order(['state' => 'draft']);

        $reflection = new ReflectionClass($order);
        $method = $reflection->getMethod('methodToState');
        $method->setAccessible(true);

        // Test camelCase to snake_case conversion
        expect($method->invoke($order, 'camelCaseMethod'))->toBe('camel_case_method');
        expect($method->invoke($order, 'longMethodName'))->toBe('long_method_name');
    });
});
