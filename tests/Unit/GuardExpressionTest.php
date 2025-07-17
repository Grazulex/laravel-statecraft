<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Guards\GuardExpression;
use Grazulex\LaravelStatecraft\Support\GuardExpressionParser;
use Tests\Fixtures\Guards\HasMinimumAmount;
use Tests\Fixtures\Guards\IsManager;
use Tests\Fixtures\Guards\IsUrgent;
use Tests\Fixtures\Guards\IsVIP;
use Tests\Fixtures\Order;

describe('Guard Expressions', function () {

    beforeEach(function () {
        // Bind guards to container
        app()->bind(IsManager::class, IsManager::class);
        app()->bind(HasMinimumAmount::class, HasMinimumAmount::class);
        app()->bind(IsVIP::class, IsVIP::class);
        app()->bind(IsUrgent::class, IsUrgent::class);
    });

    test('GuardExpression evaluates AND logic correctly', function () {
        $order = new Order();
        $order->user_id = 1;
        $order->amount = 1000;
        $order->user_role = 'manager'; // Use user_role instead of is_manager

        $expression = new GuardExpression([
            'and' => [
                IsManager::class,
                HasMinimumAmount::class,
            ],
        ], 'pending', 'approved');

        // Both conditions are true
        expect($expression->evaluate($order))->toBeTrue();

        // Make one condition false
        $order->user_role = 'user';
        expect($expression->evaluate($order))->toBeFalse();
    });

    test('GuardExpression evaluates OR logic correctly', function () {
        $order = new Order();
        $order->user_id = 1;
        $order->amount = 50; // Too low for HasMinimumAmount (needs >= 100)
        $order->user_role = 'manager'; // Use user_role instead of is_manager

        $expression = new GuardExpression([
            'or' => [
                IsManager::class,
                HasMinimumAmount::class,
            ],
        ], 'pending', 'approved');

        // One condition is true (IsManager)
        expect($expression->evaluate($order))->toBeTrue();

        // Make the true condition false
        $order->user_role = 'user';
        expect($expression->evaluate($order))->toBeFalse();

        // Make the other condition true
        $order->amount = 1000;
        expect($expression->evaluate($order))->toBeTrue();
    });

    test('GuardExpression evaluates NOT logic correctly', function () {
        $order = new Order();
        $order->user_id = 1;
        $order->user_role = 'user'; // Not a manager

        $expression = new GuardExpression([
            'not' => IsManager::class,
        ], 'pending', 'approved');

        // IsManager is false, so NOT IsManager is true
        expect($expression->evaluate($order))->toBeTrue();

        // Make IsManager true
        $order->user_role = 'manager';
        expect($expression->evaluate($order))->toBeFalse();
    });

    test('GuardExpression evaluates nested expressions correctly', function () {
        $order = new Order();
        $order->user_id = 1;
        $order->amount = 1000;
        $order->user_role = 'manager'; // Use user_role instead of is_manager
        $order->is_vip = false;
        $order->is_urgent = true;

        $expression = new GuardExpression([
            'and' => [
                IsManager::class,
                [
                    'or' => [
                        IsVIP::class,
                        IsUrgent::class,
                    ],
                ],
            ],
        ], 'pending', 'approved');

        // IsManager is true AND (IsVIP is false OR IsUrgent is true) = true
        expect($expression->evaluate($order))->toBeTrue();

        // Make IsManager false
        $order->user_role = 'user';
        expect($expression->evaluate($order))->toBeFalse();

        // Make IsManager true again but both IsVIP and IsUrgent false
        $order->user_role = 'manager';
        $order->is_urgent = false;
        expect($expression->evaluate($order))->toBeFalse();
    });

    test('GuardExpressionParser detects expressions correctly', function () {
        expect(GuardExpressionParser::isExpression(['and' => ['Guard1', 'Guard2']]))->toBeTrue();
        expect(GuardExpressionParser::isExpression(['or' => ['Guard1', 'Guard2']]))->toBeTrue();
        expect(GuardExpressionParser::isExpression(['not' => 'Guard1']))->toBeTrue();
        expect(GuardExpressionParser::isExpression('SimpleGuard'))->toBeFalse();
        expect(GuardExpressionParser::isExpression(['invalid' => 'structure']))->toBeFalse();
    });

    test('GuardExpressionParser validates expressions correctly', function () {
        // Valid expressions
        expect(GuardExpressionParser::validateExpression([
            'and' => ['Guard1', 'Guard2'],
        ]))->toBeTrue();

        expect(GuardExpressionParser::validateExpression([
            'or' => ['Guard1', ['and' => ['Guard2', 'Guard3']]],
        ]))->toBeTrue();

        expect(GuardExpressionParser::validateExpression([
            'not' => 'Guard1',
        ]))->toBeTrue();

        // Invalid expressions
        expect(GuardExpressionParser::validateExpression([
            'invalid' => 'structure',
        ]))->toBeFalse();

        expect(GuardExpressionParser::validateExpression([
            'and' => 'should_be_array',
        ]))->toBeFalse();
    });

    test('GuardExpressionParser parses simple guard strings', function () {
        $order = new Order();
        $order->user_id = 1;
        $order->user_role = 'manager';

        $guardFunction = GuardExpressionParser::parse(IsManager::class, 'pending', 'approved');

        expect($guardFunction($order))->toBeTrue();

        $order->user_role = 'user';
        expect($guardFunction($order))->toBeFalse();
    });

    test('GuardExpressionParser parses complex expressions', function () {
        $order = new Order();
        $order->user_id = 1;
        $order->amount = 1000;
        $order->user_role = 'manager';
        $order->is_vip = true;

        $guardFunction = GuardExpressionParser::parse([
            'and' => [
                IsManager::class,
                [
                    'or' => [
                        IsVIP::class,
                        HasMinimumAmount::class,
                    ],
                ],
            ],
        ], 'pending', 'approved');

        expect($guardFunction($order))->toBeTrue();

        // Make all conditions false
        $order->user_role = 'user';
        $order->is_vip = false;
        $order->amount = 50;
        expect($guardFunction($order))->toBeFalse();
    });

    test('GuardExpression throws exception for invalid guard type', function () {
        $order = new Order();

        $expression = new GuardExpression([
            'and' => [
                123, // Invalid guard type
            ],
        ], 'pending', 'approved');

        expect(fn () => $expression->evaluate($order))
            ->toThrow(InvalidArgumentException::class, 'Invalid guard type');
    });

    test('GuardExpression throws exception for invalid expression format', function () {
        $order = new Order();

        $expression = new GuardExpression([
            'invalid' => 'structure',
        ], 'pending', 'approved');

        expect(fn () => $expression->evaluate($order))
            ->toThrow(InvalidArgumentException::class, 'Invalid guard expression format');
    });

    test('GuardExpression throws exception for non-Guard class', function () {
        $order = new Order();

        $expression = new GuardExpression([
            'and' => [
                'InvalidClass', // Class that doesn't implement Guard
            ],
        ], 'pending', 'approved');

        expect(fn () => $expression->evaluate($order))
            ->toThrow(InvalidArgumentException::class);
    });
});
