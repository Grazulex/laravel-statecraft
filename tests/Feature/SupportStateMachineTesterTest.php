<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Support\StateMachineTester;
use Tests\Fixtures\TestOrder;

describe('StateMachineTester Integration', function () {
    beforeEach(function () {
        config(['statecraft.state_machines_path' => __DIR__.'/../Fixtures/yaml']);
    });

    test('only manager can approve order', function () {
        $order = new TestOrder();
        $order->state = 'pending';
        $order->amount = 1000; // Sufficient amount

        // Mock a regular user scenario - should be blocked
        $order->user_role = 'user';
        StateMachineTester::assertTransitionBlocked($order, 'approved');

        // Mock a manager scenario - should be allowed
        $order->user_role = 'manager';
        StateMachineTester::assertTransitionAllowed($order, 'approved');
    });

    test('order workflow transitions work correctly', function () {
        $order = new TestOrder();

        // Start in draft state
        $order->state = 'draft';
        StateMachineTester::assertInState($order, 'draft');

        // From draft, should be able to go to pending
        StateMachineTester::assertTransitionAllowed($order, 'pending');

        // From draft, should NOT be able to go directly to completed
        StateMachineTester::assertTransitionBlocked($order, 'completed');

        // Check available transitions from draft
        $transitions = StateMachineTester::transitionsFor($order);
        expect($transitions)->toHaveKey('draft');
        expect($transitions['draft'])->toContain('pending');
        expect($transitions['draft'])->not->toContain('completed');
    });

    test('order state progression follows business rules', function () {
        $order = new TestOrder();

        // Draft -> Pending
        $order->state = 'draft';
        StateMachineTester::assertTransitionAllowed($order, 'pending');

        // Pending -> Approved (with sufficient amount AND manager role)
        $order->state = 'pending';
        $order->amount = 1000; // Sufficient amount
        $order->user_role = 'manager'; // Manager role
        StateMachineTester::assertTransitionAllowed($order, 'approved');

        // Pending -> Rejected (insufficient amount OR not manager)
        $order->amount = 50; // Insufficient amount
        $order->user_role = 'user'; // Not manager
        StateMachineTester::assertTransitionBlocked($order, 'approved');
        StateMachineTester::assertTransitionAllowed($order, 'rejected');
    });

    test('completed orders cannot be modified', function () {
        $order = new TestOrder();
        $order->state = 'completed';

        // Completed orders should not allow any transitions
        StateMachineTester::assertTransitionBlocked($order, 'pending');
        StateMachineTester::assertTransitionBlocked($order, 'approved');
        StateMachineTester::assertTransitionBlocked($order, 'rejected');
        StateMachineTester::assertTransitionBlocked($order, 'draft');

        // Check that no transitions are available
        $transitions = StateMachineTester::transitionsFor($order);
        expect($transitions['completed'])->toBeEmpty();
    });

    test('cancelled orders cannot be reactivated', function () {
        $order = new TestOrder();
        $order->state = 'cancelled';

        // Cancelled orders should not allow transitions back to active states
        StateMachineTester::assertTransitionBlocked($order, 'draft');
        StateMachineTester::assertTransitionBlocked($order, 'pending');
        StateMachineTester::assertTransitionBlocked($order, 'approved');

        // Check available transitions
        $transitions = StateMachineTester::transitionsFor($order);
        expect($transitions['cancelled'])->toBeEmpty();
    });

    test('assertHasAvailableTransitions works with complex scenarios', function () {
        $order = new TestOrder();

        // Draft state should have specific transitions
        $order->state = 'draft';
        StateMachineTester::assertHasAvailableTransitions($order, ['pending', 'cancelled']);

        // Pending state should have different transitions based on guards
        $order->state = 'pending';
        $order->amount = 50; // Insufficient amount
        $order->user_role = 'user'; // Not manager
        StateMachineTester::assertHasAvailableTransitions($order, ['rejected', 'cancelled']);

        // Approved state should have limited transitions
        $order->state = 'approved';
        StateMachineTester::assertHasAvailableTransitions($order, ['completed', 'cancelled']);
    });

    test('transitionsFor provides comprehensive state information', function () {
        $order = new TestOrder();
        $order->state = 'draft';

        $transitions = StateMachineTester::transitionsFor($order);

        // Should return array with current state as key
        expect($transitions)->toBeArray();
        expect($transitions)->toHaveKey('draft');
        expect($transitions['draft'])->toBeArray();

        // Each transition target should be a valid state
        $validStates = ['draft', 'pending', 'approved', 'rejected', 'completed', 'cancelled'];
        foreach ($transitions['draft'] as $transition) {
            expect($transition)->toBeIn($validStates);
        }
    });

    test('error messages are descriptive', function () {
        $order = new TestOrder();
        $order->state = 'completed';

        // Test that error messages are helpful when transition should be allowed but isn't
        try {
            StateMachineTester::assertTransitionAllowed($order, 'pending');
            expect(false)->toBeTrue(); // Should not reach here
        } catch (PHPUnit\Framework\AssertionFailedError $e) {
            expect($e->getMessage())->toContain("Expected transition to 'pending' to be allowed, but it was blocked");
        }

        // Test with invalid state - this should NOT throw an exception since canNonexistent returns false
        StateMachineTester::assertTransitionBlocked($order, 'nonexistent');
        expect(true)->toBeTrue(); // Should succeed
    });
});
