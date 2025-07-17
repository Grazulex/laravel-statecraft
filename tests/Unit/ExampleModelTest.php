<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Examples\ExampleModel;
use Grazulex\LaravelStatecraft\Traits\HasStateHistory;
use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

describe('ExampleModel', function () {

    beforeEach(function () {
        // Set correct path for example-workflow.yaml
        Config::set('statecraft.definitions_path', base_path('database/state_machines'));
    });

    test('ExampleModel uses HasStateMachine trait', function () {
        $model = new ExampleModel();
        expect($model)->toBeInstanceOf(Model::class);
        expect(class_uses($model))->toHaveKey(HasStateMachine::class);
    });

    test('ExampleModel has correct state machine definition name', function () {
        $model = new ExampleModel();

        // Create a reflection method to test protected method
        $reflection = new ReflectionClass($model);
        $method = $reflection->getMethod('getStateMachineDefinitionName');
        $method->setAccessible(true);

        expect($method->invoke($model))->toBe('example-workflow');
    });

    test('ExampleModel has correct fillable attributes', function () {
        $model = new ExampleModel();
        expect($model->getFillable())->toContain('name');
        expect($model->getFillable())->toContain('state');
    });

    test('ExampleModel can be instantiated', function () {
        $model = new ExampleModel();
        expect($model)->toBeInstanceOf(ExampleModel::class);
        expect($model)->toBeInstanceOf(Model::class);
    });

    test('ExampleModel has state machine methods available', function () {
        $model = new ExampleModel();
        expect(method_exists($model, 'getStateMachineManager'))->toBeTrue();
        expect(method_exists($model, 'getCurrentState'))->toBeTrue();
        expect(method_exists($model, 'getAvailableTransitions'))->toBeTrue();
    });

    test('ExampleModel uses HasStateHistory trait', function () {
        $model = new ExampleModel();
        expect(class_uses($model))->toHaveKey(HasStateHistory::class);
    });

    test('ExampleModel has state history methods available', function () {
        $model = new ExampleModel();
        expect(method_exists($model, 'stateHistory'))->toBeTrue();
        expect(method_exists($model, 'latestStateTransition'))->toBeTrue();
        expect(method_exists($model, 'recordStateTransition'))->toBeTrue();
    });

    test('ExampleModel table name is correct', function () {
        $model = new ExampleModel();
        expect($model->getTable())->toBe('example_models');
    });

    test('ExampleModel has correct attributes', function () {
        $model = new ExampleModel();
        $model->name = 'Test Example';
        $model->state = 'draft';

        expect($model->name)->toBe('Test Example');
        expect($model->state)->toBe('draft');
    });
});
