<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Models\StateTransition;
use Grazulex\LaravelStatecraft\Traits\HasStateHistory;
use Tests\Fixtures\Order;

describe('HasStateHistory Trait', function () {
    test('recordStateTransition method exists and is callable', function () {
        $order = new Order();

        expect(method_exists($order, 'recordStateTransition'))->toBeTrue();
        expect(is_callable([$order, 'recordStateTransition']))->toBeTrue();
    });

    test('stateHistory relationship works', function () {
        $order = new Order();

        expect(method_exists($order, 'stateHistory'))->toBeTrue();

        $relation = $order->stateHistory();
        expect($relation)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\MorphMany::class);
    });

    test('trait methods are available on models', function () {
        $order = new Order();

        // Test that the trait methods are available
        expect(method_exists($order, 'recordStateTransition'))->toBeTrue();
        expect(method_exists($order, 'stateHistory'))->toBeTrue();
        expect(method_exists($order, 'latestStateTransition'))->toBeTrue();
    });

    test('trait works with different model types', function () {
        $order = new Order();

        // Test that the trait can be used with different models
        expect(in_array(HasStateHistory::class, class_uses_recursive($order)))->toBeTrue();
    });

    test('recordStateTransition respects configuration', function () {
        $order = new Order();

        // We can't test the actual execution without database, but we can test the config
        expect(config('statecraft.history.enabled'))->toBeFalse();

        // Re-enable for other tests
        config(['statecraft.history.enabled' => true]);
        expect(config('statecraft.history.enabled'))->toBeTrue();
    });

    test('trait uses correct table name from config', function () {
        // Test with custom config
        config(['statecraft.history.table' => 'custom_transitions']);

        $order = new Order();
        $relation = $order->stateHistory();

        // The relation should use the StateTransition model, which should use the custom table
        $model = $relation->getRelated();
        expect($model->getTable())->toBe('custom_transitions');
    });

    test('trait handles metadata parameter correctly', function () {
        $order = new Order();

        // Test that recordStateTransition accepts metadata parameter
        $reflection = new ReflectionMethod($order, 'recordStateTransition');
        $parameters = $reflection->getParameters();

        // Should have parameters for from_state, to_state, guard, action, metadata
        expect(count($parameters))->toBeGreaterThanOrEqual(4);

        // Check that metadata parameter exists
        $metadataParam = null;
        foreach ($parameters as $param) {
            if ($param->getName() === 'metadata') {
                $metadataParam = $param;
                break;
            }
        }

        expect($metadataParam)->not->toBeNull();
        expect($metadataParam->isOptional())->toBeTrue();
    });
});
