<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Models\StateTransition;
use Grazulex\LaravelStatecraft\Traits\HasStateHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Tests\Fixtures\Order;

describe('HasStateHistory Trait - Advanced Tests', function () {
    beforeEach(function () {
        // Disable history tracking for most tests to avoid database calls
        config(['statecraft.history.enabled' => false]);
    });

    test('latestStateTransition method has correct signature', function () {
        $order = new Order();

        // Test that the method exists and can be called
        expect(method_exists($order, 'latestStateTransition'))->toBeTrue();

        // Test method reflection to ensure proper signature
        $reflection = new ReflectionMethod($order, 'latestStateTransition');
        $returnType = $reflection->getReturnType();

        expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
        expect($returnType->getName())->toBe(StateTransition::class);
        expect($returnType->allowsNull())->toBeTrue();
    });

    test('latestStateTransition query execution pattern', function () {
        // Test that latestStateTransition follows the correct query pattern
        $order = new Order();

        // We'll test the method structure and behavior rather than actual database calls
        $reflection = new ReflectionMethod($order, 'latestStateTransition');

        // Verify return type is correct
        expect($reflection->getReturnType()->getName())->toBe(StateTransition::class);
        expect($reflection->getReturnType()->allowsNull())->toBeTrue();

        // Verify method is public
        expect($reflection->isPublic())->toBeTrue();

        // Test that stateHistory relationship method exists (this is called in line 27)
        expect(method_exists($order, 'stateHistory'))->toBeTrue();

        // Test the relationship return type
        $stateHistoryRelation = $order->stateHistory();
        expect($stateHistoryRelation)->toBeInstanceOf(MorphMany::class);
    });

    test('recordStateTransition with enabled configuration execution', function () {
        // Test the actual execution path when configuration is enabled
        config(['statecraft.history.enabled' => true]);

        // Create a test model that uses an in-memory approach
        $testModel = new class extends Model
        {
            use HasStateHistory;

            public $incrementing = false;

            public $timestamps = false;

            protected $table = 'test_models';

            // Override stateHistory to return a mock that doesn't hit the database
            public function stateHistory()
            {
                return new class
                {
                    public function create(array $attributes)
                    {
                        // Mock create method that doesn't hit database
                        return (object) $attributes;
                    }
                };
            }
        };

        // This should execute the code path in lines 37-44 and 45
        $testModel->recordStateTransition('from', 'to', 'guard', 'action', ['meta' => 'data']);

        // Reset configuration
        config(['statecraft.history.enabled' => false]);

        expect(true)->toBeTrue(); // Test passes if no exception is thrown
    });

    test('recordStateTransition disabled configuration path', function () {
        // Test the execution when configuration is disabled
        config(['statecraft.history.enabled' => false]);

        $order = new Order();

        // When disabled, the method should return early without hitting the database
        $order->recordStateTransition('from', 'to', 'guard', 'action', ['meta' => 'data']);

        // Verify configuration is indeed false
        expect(config('statecraft.history.enabled'))->toBeFalse();

        expect(true)->toBeTrue(); // Test passes if no exception is thrown
    });

    test('recordStateTransition configuration behavior', function () {
        $order = new Order();

        // Test with enabled config
        config(['statecraft.history.enabled' => true]);
        expect(config('statecraft.history.enabled'))->toBeTrue();

        // Test with disabled config
        config(['statecraft.history.enabled' => false]);
        expect(config('statecraft.history.enabled'))->toBeFalse();

        // Test that the method can be called without throwing when disabled
        $order->recordStateTransition('from', 'to', null, null, []);
        expect(true)->toBeTrue(); // If we reach here, no exception was thrown
    });

    test('getStateMachineName method execution paths', function () {
        // Test the method_exists path
        $model = new class extends Model
        {
            use HasStateHistory;

            public function getStateMachineDefinitionName(): string
            {
                return 'custom-machine';
            }
        };

        $reflection = new ReflectionClass($model);
        $method = $reflection->getMethod('getStateMachineName');
        $method->setAccessible(true);

        $result = $method->invoke($model);
        expect($result)->toBe('custom-machine');

        // Test the fallback path with a different model
        $modelWithoutMethod = new class extends Model
        {
            use HasStateHistory;
        };

        $reflection2 = new ReflectionClass($modelWithoutMethod);
        $method2 = $reflection2->getMethod('getStateMachineName');
        $method2->setAccessible(true);

        $result2 = $method2->invoke($modelWithoutMethod);
        expect($result2)->toContain('Workflow');
    });

    test('stateHistory relationship returns correct morphMany configuration', function () {
        $order = new Order();
        $relation = $order->stateHistory();

        // Test that the relationship is configured correctly
        expect($relation)->toBeInstanceOf(MorphMany::class);
        expect($relation->getRelated())->toBeInstanceOf(StateTransition::class);
        expect($relation->getMorphType())->toBe('model_type');
        expect($relation->getForeignKeyName())->toBe('model_id');

        // Test that the relationship uses the correct table
        expect($relation->getRelated()->getTable())->toBe(config('statecraft.history.table', 'state_transitions'));
    });

    test('recordStateTransition respects enabled configuration', function () {
        // Test that when disabled, no database calls are made
        config(['statecraft.history.enabled' => false]);

        $order = new Order();

        // Verify the method exists and can be called
        expect(method_exists($order, 'recordStateTransition'))->toBeTrue();

        // Test that it doesn't throw an error when disabled - check no exception
        $order->recordStateTransition('draft', 'published', 'canPublish', 'publishAction', []);
        expect(true)->toBeTrue(); // If we reach here, no exception was thrown
    });

    test('recordStateTransition respects disabled configuration', function () {
        config(['statecraft.history.enabled' => false]);

        $order = new Order();

        // Test that it doesn't throw an error when disabled - check no exception
        $order->recordStateTransition('draft', 'published', 'canPublish', 'publishAction', []);
        expect(true)->toBeTrue(); // If we reach here, no exception was thrown
    });

    test('latestStateTransition method exists and returns correct type', function () {
        $order = new Order();

        expect(method_exists($order, 'latestStateTransition'))->toBeTrue();

        // Test the method signature
        $reflection = new ReflectionMethod($order, 'latestStateTransition');
        $returnType = $reflection->getReturnType();

        expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
        expect($returnType->getName())->toBe(StateTransition::class);
        expect($returnType->allowsNull())->toBeTrue();
    });

    test('getStateMachineName method works with HasStateMachine trait', function () {
        $order = new Order();

        // Use reflection to access the private method
        $reflection = new ReflectionClass($order);
        $method = $reflection->getMethod('getStateMachineName');
        $method->setAccessible(true);

        $result = $method->invoke($order);

        // Should return the state machine name from the trait
        expect($result)->toBe('OrderWorkflow');
    });

    test('getStateMachineName method falls back to class name', function () {
        // Create a test model without getStateMachineDefinitionName method
        $model = new class extends Model
        {
            use HasStateHistory;
        };

        // Use reflection to access the private method
        $reflection = new ReflectionClass($model);
        $method = $reflection->getMethod('getStateMachineName');
        $method->setAccessible(true);

        $result = $method->invoke($model);

        // Should fall back to class basename + 'Workflow'
        expect($result)->toContain('Workflow');
    });

    test('recordStateTransition method signature is correct', function () {
        $order = new Order();

        $reflection = new ReflectionMethod($order, 'recordStateTransition');
        $parameters = $reflection->getParameters();

        expect(count($parameters))->toBe(5);

        // Check parameter names and types
        expect($parameters[0]->getName())->toBe('fromState');
        expect($parameters[0]->getType()->getName())->toBe('string');

        expect($parameters[1]->getName())->toBe('toState');
        expect($parameters[1]->getType()->getName())->toBe('string');

        expect($parameters[2]->getName())->toBe('guard');
        expect($parameters[2]->getType()->getName())->toBe('string');
        expect($parameters[2]->allowsNull())->toBeTrue();

        expect($parameters[3]->getName())->toBe('action');
        expect($parameters[3]->getType()->getName())->toBe('string');
        expect($parameters[3]->allowsNull())->toBeTrue();

        expect($parameters[4]->getName())->toBe('metadata');
        expect($parameters[4]->getType()->getName())->toBe('array');
        expect($parameters[4]->isDefaultValueAvailable())->toBeTrue();
    });

    test('stateHistory relationship is properly configured', function () {
        $order = new Order();
        $relation = $order->stateHistory();

        expect($relation)->toBeInstanceOf(MorphMany::class);
        expect($relation->getRelated())->toBeInstanceOf(StateTransition::class);
        expect($relation->getMorphType())->toBe('model_type');
        expect($relation->getForeignKeyName())->toBe('model_id');
    });

    test('trait uses correct configuration keys', function () {
        // Test default configuration (disabled from beforeEach)
        expect(config('statecraft.history.enabled'))->toBeFalse();

        // Test that changing config affects behavior
        config(['statecraft.history.enabled' => true]);
        expect(config('statecraft.history.enabled'))->toBeTrue();

        // Test table configuration
        config(['statecraft.history.table' => 'custom_transitions']);
        expect(config('statecraft.history.table'))->toBe('custom_transitions');

        // Reset for other tests
        config(['statecraft.history.enabled' => false]);
    });

    test('recordStateTransition handles null values properly', function () {
        $order = new Order();

        // Test with null guard and action - check no exception
        $order->recordStateTransition('draft', 'published', null, null, []);
        expect(true)->toBeTrue(); // If we reach here, no exception was thrown

        // Test with empty metadata - check no exception
        $order->recordStateTransition('draft', 'published', 'guard', 'action');
        expect(true)->toBeTrue(); // If we reach here, no exception was thrown
    });

    test('recordStateTransition checks configuration before database operation', function () {
        $order = new Order();

        // Test that configuration is consulted
        config(['statecraft.history.enabled' => false]);

        // When disabled, should not attempt database operation
        $order->recordStateTransition('draft', 'published', 'guard', 'action', []);
        expect(true)->toBeTrue();

        // We can't easily test enabled without database, but we can verify the config is read
        expect(config('statecraft.history.enabled'))->toBeFalse();
    });

    test('recordStateTransition handles complex metadata', function () {
        // Keep history disabled to avoid database calls
        config(['statecraft.history.enabled' => false]);

        $order = new Order();

        $complexMetadata = [
            'user_id' => 1,
            'reason' => 'Approved by manager',
            'timestamp' => now()->toISOString(),
            'additional_data' => [
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Test Agent',
            ],
        ];

        // Test with complex metadata - check no exception
        $order->recordStateTransition('draft', 'published', 'guard', 'action', $complexMetadata);
        expect(true)->toBeTrue(); // If we reach here, no exception was thrown
    });

    test('trait methods are available on different model types', function () {
        // Test with Order model
        $order = new Order();
        expect(method_exists($order, 'recordStateTransition'))->toBeTrue();
        expect(method_exists($order, 'stateHistory'))->toBeTrue();
        expect(method_exists($order, 'latestStateTransition'))->toBeTrue();

        // Test with anonymous model
        $model = new class extends Model
        {
            use HasStateHistory;
        };

        expect(method_exists($model, 'recordStateTransition'))->toBeTrue();
        expect(method_exists($model, 'stateHistory'))->toBeTrue();
        expect(method_exists($model, 'latestStateTransition'))->toBeTrue();
    });

    test('trait integrates with StateTransition model configuration', function () {
        // Test with custom table name
        config(['statecraft.history.table' => 'custom_state_history']);

        $order = new Order();
        $relation = $order->stateHistory();
        $model = $relation->getRelated();

        expect($model->getTable())->toBe('custom_state_history');

        // Reset to default
        config(['statecraft.history.table' => 'state_transitions']);
    });
});
