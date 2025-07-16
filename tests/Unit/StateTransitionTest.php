<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Models\StateTransition;
use Tests\Fixtures\Order;

describe('StateTransition Model', function () {
    test('fillable attributes are correctly set', function () {
        $transition = new StateTransition();

        $fillable = $transition->getFillable();
        expect($fillable)->toContain('from_state');
        expect($fillable)->toContain('to_state');
        expect($fillable)->toContain('guard');
        expect($fillable)->toContain('action');
        expect($fillable)->toContain('metadata');
    });

    test('getTable returns correct table name from config', function () {
        $transition = new StateTransition();

        // Test default table name
        expect($transition->getTable())->toBe('state_machine_history');

        // Test with custom config
        config(['statecraft.history.table' => 'custom_transitions']);
        $transition = new StateTransition();
        expect($transition->getTable())->toBe('custom_transitions');
    });

    test('metadata is cast to array', function () {
        $transition = new StateTransition();

        // Test the cast definition
        $casts = $transition->getCasts();
        expect($casts)->toHaveKey('metadata');
        expect($casts['metadata'])->toBe('array');
    });

    test('model uses correct attributes', function () {
        $attributes = [
            'from_state' => 'draft',
            'to_state' => 'pending',
            'guard' => 'TestGuard',
            'action' => 'TestAction',
            'metadata' => ['key' => 'value'],
        ];

        $transition = new StateTransition($attributes);

        // Set model_type and model_id directly since they're not fillable
        $transition->model_type = Order::class;
        $transition->model_id = 1;

        expect($transition->model_type)->toBe(Order::class);
        expect($transition->model_id)->toBe(1);
        expect($transition->from_state)->toBe('draft');
        expect($transition->to_state)->toBe('pending');
        expect($transition->guard)->toBe('TestGuard');
        expect($transition->action)->toBe('TestAction');
        expect($transition->metadata)->toBe(['key' => 'value']);
    });

    test('model handles null values correctly', function () {
        $attributes = [
            'model_type' => Order::class,
            'model_id' => 1,
            'from_state' => null,
            'to_state' => 'pending',
            'guard' => null,
            'action' => null,
            'metadata' => null,
        ];

        $transition = new StateTransition($attributes);

        expect($transition->from_state)->toBeNull();
        expect($transition->to_state)->toBe('pending');
        expect($transition->guard)->toBeNull();
        expect($transition->action)->toBeNull();
        expect($transition->metadata)->toBeNull();
    });

    test('model handles empty metadata correctly', function () {
        $attributes = [
            'model_type' => Order::class,
            'model_id' => 1,
            'from_state' => 'draft',
            'to_state' => 'pending',
            'metadata' => [],
        ];

        $transition = new StateTransition($attributes);
        expect($transition->metadata)->toBe([]);
    });

    test('model handles complex metadata correctly', function () {
        $complexMetadata = [
            'user_id' => 123,
            'nested' => [
                'key' => 'value',
                'array' => [1, 2, 3],
            ],
            'boolean' => true,
            'null_value' => null,
        ];

        $attributes = [
            'model_type' => Order::class,
            'model_id' => 1,
            'from_state' => 'draft',
            'to_state' => 'pending',
            'metadata' => $complexMetadata,
        ];

        $transition = new StateTransition($attributes);

        expect($transition->metadata)->toBe($complexMetadata);
        expect($transition->metadata['user_id'])->toBe(123);
        expect($transition->metadata['nested']['key'])->toBe('value');
        expect($transition->metadata['nested']['array'])->toBe([1, 2, 3]);
        expect($transition->metadata['boolean'])->toBe(true);
        expect($transition->metadata['null_value'])->toBeNull();
    });

    test('model has correct timestamps behavior', function () {
        $transition = new StateTransition();

        // Check that timestamps are enabled (default Laravel behavior)
        expect($transition->timestamps)->toBe(true);

        // Check that the created_at and updated_at attributes are in dates
        $dates = $transition->getDates();
        expect($dates)->toContain('created_at');
        expect($dates)->toContain('updated_at');
    });
});
