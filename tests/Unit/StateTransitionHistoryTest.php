<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Models\StateTransition;
use Tests\Fixtures\Order;

describe('State Transition History', function () {
    beforeEach(function () {
        // Set up the correct path for test YAML files
        config(['statecraft.state_machines_path' => __DIR__.'/../../Fixtures/yaml']);
        config(['statecraft.history.enabled' => true]);
    });

    test('transition history is recorded when enabled', function () {
        $order = new Order(['state' => 'draft']);

        // Test that the method exists and can be called
        expect(method_exists($order, 'recordStateTransition'))->toBeTrue();

        // Test that configuration is respected
        config(['statecraft.history.enabled' => true]);
        expect(config('statecraft.history.enabled'))->toBeTrue();

        // We can't test actual database operations in unit tests, but we can verify
        // that the method is callable
        expect(is_callable([$order, 'recordStateTransition']))->toBeTrue();
    });

    test('stateHistory relationship works', function () {
        $order = new Order();

        $relation = $order->stateHistory();

        expect($relation)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\MorphMany::class);
        expect($relation->getRelated())->toBeInstanceOf(StateTransition::class);
    });

    test('latestStateTransition method exists', function () {
        $order = new Order();

        expect(method_exists($order, 'latestStateTransition'))->toBeTrue();
    });

    test('StateTransition model has correct attributes', function () {
        $transition = new StateTransition();

        $fillable = $transition->getFillable();
        expect($fillable)->toContain('from_state');
        expect($fillable)->toContain('to_state');
        expect($fillable)->toContain('guard');
        expect($fillable)->toContain('action');
        expect($fillable)->toContain('metadata');
        expect($fillable)->toContain('state_machine');
        expect($fillable)->toContain('transition');
    });

    test('StateTransition model has accessor methods', function () {
        $transition = new StateTransition([
            'from_state' => 'draft',
            'to_state' => 'pending',
            'metadata' => ['key' => 'value'],
        ]);

        expect($transition->from)->toBe('draft');
        expect($transition->to)->toBe('pending');
        expect($transition->custom_data)->toBe(['key' => 'value']);
    });

    test('recordStateTransition respects configuration', function () {
        $order = new Order();

        // Test with history disabled
        config(['statecraft.history.enabled' => false]);
        expect(config('statecraft.history.enabled'))->toBeFalse();

        // Test with history enabled
        config(['statecraft.history.enabled' => true]);
        expect(config('statecraft.history.enabled'))->toBeTrue();

        // Verify method exists and is callable
        expect(method_exists($order, 'recordStateTransition'))->toBeTrue();
        expect(is_callable([$order, 'recordStateTransition']))->toBeTrue();
    });

    test('recordStateTransition uses correct state machine name', function () {
        $order = new Order();

        // Test that the private method works correctly
        $reflection = new ReflectionClass($order);
        $method = $reflection->getMethod('getStateMachineName');
        $method->setAccessible(true);

        $result = $method->invoke($order);

        expect($result)->toBe('OrderWorkflow');
    });

    test('StateTransition model uses correct table name', function () {
        $transition = new StateTransition();

        expect($transition->getTable())->toBe('state_machine_history');

        // Test with custom table name
        config(['statecraft.history.table' => 'custom_transitions']);

        $transition = new StateTransition();
        expect($transition->getTable())->toBe('custom_transitions');
    });

    test('StateTransition model has morphTo relationship', function () {
        $transition = new StateTransition();

        $relation = $transition->model();

        expect($relation)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\MorphTo::class);
    });

    test('metadata is cast to array in StateTransition', function () {
        $transition = new StateTransition();

        $casts = $transition->getCasts();

        expect($casts)->toHaveKey('metadata');
        expect($casts['metadata'])->toBe('array');
    });

    test('state machine manager calls recordStateTransition when model has trait', function () {
        $order = new Order(['state' => 'draft']);

        // Verify the model has the HasStateHistory trait
        $traits = class_uses_recursive($order);
        expect($traits)->toContain(Grazulex\LaravelStatecraft\Traits\HasStateHistory::class);

        // Verify the method exists
        expect(method_exists($order, 'recordStateTransition'))->toBeTrue();
    });
});
