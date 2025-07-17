<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition;
use Grazulex\LaravelStatecraft\Support\StateMachineManager;
use Tests\Fixtures\Guards\HasMinimumAmount;
use Tests\Fixtures\Guards\IsManager;
use Tests\Fixtures\Guards\IsUrgent;
use Tests\Fixtures\Guards\IsVIP;
use Tests\Fixtures\Order;

describe('StateMachineManager with Guard Expressions', function () {

    beforeEach(function () {
        // Bind guards to container
        app()->bind(IsManager::class, IsManager::class);
        app()->bind(HasMinimumAmount::class, HasMinimumAmount::class);
        app()->bind(IsVIP::class, IsVIP::class);
        app()->bind(IsUrgent::class, IsUrgent::class);
    });

    test('StateMachineManager supports AND guard expressions', function () {
        $definition = new StateMachineDefinition(
            'TestWorkflow',
            Order::class,
            ['pending', 'approved', 'rejected'],
            'pending',
            [
                [
                    'from' => 'pending',
                    'to' => 'approved',
                    'guard' => [
                        'and' => [
                            IsManager::class,
                            HasMinimumAmount::class,
                        ],
                    ],
                ],
            ]
        );

        $manager = new StateMachineManager($definition);

        $order = new Order();
        $order->state = 'pending';
        $order->user_id = 1;
        $order->amount = 1000;
        $order->user_role = 'manager'; // Use user_role instead of is_manager

        // Both conditions are true
        expect($manager->canTransition($order, 'approved'))->toBeTrue();

        // Make one condition false
        $order->user_role = 'user';
        expect($manager->canTransition($order, 'approved'))->toBeFalse();

        // Make the other condition false
        $order->user_role = 'manager';
        $order->amount = 50; // Below minimum
        expect($manager->canTransition($order, 'approved'))->toBeFalse();
    });

    test('StateMachineManager supports OR guard expressions', function () {
        $definition = new StateMachineDefinition(
            'TestWorkflow',
            Order::class,
            ['pending', 'approved', 'rejected'],
            'pending',
            [
                [
                    'from' => 'pending',
                    'to' => 'approved',
                    'guard' => [
                        'or' => [
                            IsManager::class,
                            IsVIP::class,
                        ],
                    ],
                ],
            ]
        );

        $manager = new StateMachineManager($definition);

        $order = new Order();
        $order->state = 'pending';
        $order->user_id = 1;
        $order->user_role = 'user'; // Not manager
        $order->is_vip = true;

        // One condition is true (IsVIP)
        expect($manager->canTransition($order, 'approved'))->toBeTrue();

        // Make the true condition false
        $order->is_vip = false;
        expect($manager->canTransition($order, 'approved'))->toBeFalse();

        // Make the other condition true
        $order->user_role = 'manager';
        expect($manager->canTransition($order, 'approved'))->toBeTrue();
    });

    test('StateMachineManager supports NOT guard expressions', function () {
        $definition = new StateMachineDefinition(
            'TestWorkflow',
            Order::class,
            ['pending', 'approved', 'rejected'],
            'pending',
            [
                [
                    'from' => 'pending',
                    'to' => 'approved',
                    'guard' => [
                        'not' => IsManager::class,
                    ],
                ],
            ]
        );

        $manager = new StateMachineManager($definition);

        $order = new Order();
        $order->state = 'pending';
        $order->user_id = 1;
        $order->user_role = 'user'; // Not manager

        // IsManager is false, so NOT IsManager is true
        expect($manager->canTransition($order, 'approved'))->toBeTrue();

        // Make IsManager true
        $order->user_role = 'manager';
        expect($manager->canTransition($order, 'approved'))->toBeFalse();
    });

    test('StateMachineManager supports nested guard expressions', function () {
        $definition = new StateMachineDefinition(
            'TestWorkflow',
            Order::class,
            ['pending', 'approved', 'rejected'],
            'pending',
            [
                [
                    'from' => 'pending',
                    'to' => 'approved',
                    'guard' => [
                        'and' => [
                            IsManager::class,
                            [
                                'or' => [
                                    IsVIP::class,
                                    IsUrgent::class,
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $manager = new StateMachineManager($definition);

        $order = new Order();
        $order->state = 'pending';
        $order->user_id = 1;
        $order->user_role = 'manager';
        $order->is_vip = false;
        $order->is_urgent = true;

        // IsManager is true AND (IsVIP is false OR IsUrgent is true) = true
        expect($manager->canTransition($order, 'approved'))->toBeTrue();

        // Make IsManager false
        $order->user_role = 'user';
        expect($manager->canTransition($order, 'approved'))->toBeFalse();

        // Make IsManager true again but both IsVIP and IsUrgent false
        $order->user_role = 'manager';
        $order->is_urgent = false;
        expect($manager->canTransition($order, 'approved'))->toBeFalse();
    });

    test('StateMachineManager can execute transitions with guard expressions', function () {
        $definition = new StateMachineDefinition(
            'TestWorkflow',
            Order::class,
            ['pending', 'approved', 'rejected'],
            'pending',
            [
                [
                    'from' => 'pending',
                    'to' => 'approved',
                    'guard' => [
                        'and' => [
                            IsManager::class,
                            HasMinimumAmount::class,
                        ],
                    ],
                ],
            ]
        );

        $manager = new StateMachineManager($definition);

        $order = new Order();
        $order->state = 'pending';
        $order->user_id = 1;
        $order->amount = 1000;
        $order->user_role = 'manager';

        // Should be able to execute the transition
        $manager->transition($order, 'approved');
        expect($order->state)->toBe('approved');
    });

    test('StateMachineManager still supports simple guard strings', function () {
        $definition = new StateMachineDefinition(
            'TestWorkflow',
            Order::class,
            ['pending', 'approved', 'rejected'],
            'pending',
            [
                [
                    'from' => 'pending',
                    'to' => 'approved',
                    'guard' => IsManager::class, // Simple string guard
                ],
            ]
        );

        $manager = new StateMachineManager($definition);

        $order = new Order();
        $order->state = 'pending';
        $order->user_id = 1;
        $order->user_role = 'manager';

        expect($manager->canTransition($order, 'approved'))->toBeTrue();

        $order->user_role = 'user';
        expect($manager->canTransition($order, 'approved'))->toBeFalse();
    });
});
