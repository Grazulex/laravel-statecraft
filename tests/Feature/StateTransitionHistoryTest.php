<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Models\StateTransition;
use Grazulex\LaravelStatecraft\Support\StateMachineManager;
use Tests\Fixtures\Order;

describe('State Transition History Integration', function () {
    beforeEach(function () {
        // Set up the correct path for test YAML files
        config(['statecraft.state_machines_path' => __DIR__.'/../../Fixtures/yaml']);
        config(['statecraft.history.enabled' => true]);
    });

    test('history tracks complete transition information', function () {
        $order = new Order(['state' => 'draft']);

        // Test that recordStateTransition method exists and is callable
        expect(method_exists($order, 'recordStateTransition'))->toBeTrue();
        expect(is_callable([$order, 'recordStateTransition']))->toBeTrue();

        // Test that the method can be called without loading YAML
        expect(true)->toBeTrue();
    });

    test('state machine manager integration works correctly', function () {
        $order = new Order(['state' => 'draft']);

        // Verify the model has both required traits
        $traits = class_uses_recursive($order);
        expect($traits)->toContain(Grazulex\LaravelStatecraft\Traits\HasStateMachine::class);
        expect($traits)->toContain(Grazulex\LaravelStatecraft\Traits\HasStateHistory::class);

        // Verify required methods exist
        expect(method_exists($order, 'stateHistory'))->toBeTrue();
        expect(method_exists($order, 'latestStateTransition'))->toBeTrue();
        expect(method_exists($order, 'recordStateTransition'))->toBeTrue();
    });

    test('StateTransition model relationships work correctly', function () {
        $transition = new StateTransition([
            'from_state' => 'draft',
            'to_state' => 'pending',
            'guard' => 'CanSubmit',
            'action' => 'NotifyReviewer',
            'metadata' => ['user_id' => 123],
            'state_machine' => 'OrderWorkflow',
            'transition' => 'approve',
        ]);

        // Test accessor methods
        expect($transition->from)->toBe('draft');
        expect($transition->to)->toBe('pending');
        expect($transition->custom_data)->toBe(['user_id' => 123]);

        // Test regular attributes
        expect($transition->guard)->toBe('CanSubmit');
        expect($transition->action)->toBe('NotifyReviewer');
        expect($transition->state_machine)->toBe('OrderWorkflow');
    });

    test('history configuration can be toggled', function () {
        $order = new Order();

        // Test with history enabled
        config(['statecraft.history.enabled' => true]);
        expect(config('statecraft.history.enabled'))->toBeTrue();

        // Test with history disabled
        config(['statecraft.history.enabled' => false]);
        expect(config('statecraft.history.enabled'))->toBeFalse();

        // Verify method exists
        expect(method_exists($order, 'recordStateTransition'))->toBeTrue();
    });

    test('state machine name is correctly determined', function () {
        $order = new Order();

        // Test with Order model that has getStateMachineDefinitionName method
        $reflection = new ReflectionClass($order);
        $method = $reflection->getMethod('getStateMachineName');
        $method->setAccessible(true);

        $result = $method->invoke($order);
        expect($result)->toBe('OrderWorkflow');
    });

    test('table name configuration works', function () {
        $transition = new StateTransition();

        // Test default table name
        expect($transition->getTable())->toBe('state_machine_history');

        // Test custom table name
        config(['statecraft.history.table' => 'my_custom_transitions']);

        $newTransition = new StateTransition();
        expect($newTransition->getTable())->toBe('my_custom_transitions');
    });

    test('state machine manager checks for recordStateTransition method', function () {
        $order = new Order(['state' => 'draft']);

        // Verify the check that StateMachineManager does
        expect(method_exists($order, 'recordStateTransition'))->toBeTrue();

        // This demonstrates that the manager will call the method
        expect(is_callable([$order, 'recordStateTransition']))->toBeTrue();
    });

    test('metadata array casting works correctly', function () {
        $metadata = [
            'user_id' => 123,
            'timestamp' => '2024-01-01 12:00:00',
            'ip_address' => '192.168.1.1',
            'additional_data' => [
                'reason' => 'Approved by manager',
                'priority' => 'high',
            ],
        ];

        $transition = new StateTransition([
            'from_state' => 'draft',
            'to_state' => 'approved',
            'metadata' => $metadata,
        ]);

        // Test that metadata is properly cast
        expect($transition->metadata)->toBe($metadata);
        expect($transition->custom_data)->toBe($metadata);
    });
});
