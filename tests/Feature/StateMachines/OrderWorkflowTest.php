<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Support\StateMachineManager;
use Grazulex\LaravelStatecraft\Support\YamlStateMachineLoader;
use Tests\Fixtures\Order;

describe('YamlStateMachineLoader', function () {
    it('can load a YAML state machine definition', function () {
        $loader = new YamlStateMachineLoader(__DIR__ . '/../../Fixtures/yaml');
        $definition = $loader->load('order_workflow');

        expect($definition->getName())->toBe('OrderWorkflow');
        expect($definition->getModel())->toBe('Tests\Fixtures\Order');
        expect($definition->getStates())->toBe(['draft', 'pending', 'approved', 'rejected']);
        expect($definition->getInitial())->toBe('draft');
    });

    it('can load all definitions from directory', function () {
        $loader = new YamlStateMachineLoader(__DIR__ . '/../../Fixtures/yaml');
        $definitions = $loader->loadAll();

        expect($definitions)->toHaveKey('order_workflow');
        expect($definitions['order_workflow']->getName())->toBe('OrderWorkflow');
    });
});

describe('StateMachineManager', function () {
    it('can check if a transition is valid', function () {
        $loader = new YamlStateMachineLoader(__DIR__ . '/../../Fixtures/yaml');
        $definition = $loader->load('order_workflow');
        $manager = new StateMachineManager($definition);

        $order = new Order(['name' => 'Test Order', 'state' => 'draft']);

        expect($manager->canTransition($order, 'pending'))->toBeTrue();
        expect($manager->canTransition($order, 'approved'))->toBeFalse();
        expect($manager->canTransition($order, 'rejected'))->toBeFalse();
    });

    it('can execute a transition', function () {
        $loader = new YamlStateMachineLoader(__DIR__ . '/../../Fixtures/yaml');
        $definition = $loader->load('order_workflow');
        $manager = new StateMachineManager($definition);

        $order = new Order(['name' => 'Test Order', 'state' => 'draft']);

        $manager->transition($order, 'pending');

        expect($order->state)->toBe('pending');
        expect($manager->canTransition($order, 'approved'))->toBeTrue();
        expect($manager->canTransition($order, 'rejected'))->toBeTrue();
    });

    it('can get available transitions', function () {
        $loader = new YamlStateMachineLoader(__DIR__ . '/../../Fixtures/yaml');
        $definition = $loader->load('order_workflow');
        $manager = new StateMachineManager($definition);

        $order = new Order(['name' => 'Test Order', 'state' => 'pending']);

        $transitions = $manager->getAvailableTransitions($order);

        expect($transitions)->toHaveCount(2);
        expect($transitions[0]['to'])->toBe('approved');
        expect($transitions[1]['to'])->toBe('rejected');
    });

    it('can initialize a model with initial state', function () {
        $loader = new YamlStateMachineLoader(__DIR__ . '/../../Fixtures/yaml');
        $definition = $loader->load('order_workflow');
        $manager = new StateMachineManager($definition);

        $order = new Order(['name' => 'Test Order']);
        $manager->initialize($order);

        expect($order->state)->toBe('draft');
    });
});
