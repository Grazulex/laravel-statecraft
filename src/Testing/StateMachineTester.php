<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Testing;

use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Assert;

final class StateMachineTester
{
    /**
     * Assert that a transition is allowed.
     *
     * @param  Model  $model  Model that uses HasStateMachine trait
     */
    public static function assertTransitionAllowed(Model $model, string $toState, string $message = ''): void
    {
        if (! in_array(HasStateMachine::class, class_uses_recursive($model))) {
            Assert::fail('Model must use HasStateMachine trait');
        }

        $canMethod = 'can' . self::stateToMethod($toState);
        $canTransition = $model->$canMethod();

        Assert::assertTrue(
            $canTransition,
            $message !== '' && $message !== '0' ? $message : "Expected transition to '$toState' to be allowed, but it was blocked."
        );
    }

    /**
     * Assert that a transition is blocked.
     *
     * @param  Model  $model  Model that uses HasStateMachine trait
     */
    public static function assertTransitionBlocked(Model $model, string $toState, string $message = ''): void
    {
        if (! in_array(HasStateMachine::class, class_uses_recursive($model))) {
            Assert::fail('Model must use HasStateMachine trait');
        }

        $canMethod = 'can' . self::stateToMethod($toState);
        $canTransition = $model->$canMethod();

        Assert::assertFalse(
            $canTransition,
            $message !== '' && $message !== '0' ? $message : "Expected transition to '$toState' to be blocked, but it was allowed."
        );
    }

    /**
     * Assert that a model is in a specific state.
     *
     * @param  Model  $model  Model that uses HasStateMachine trait
     */
    public static function assertInState(Model $model, string $expectedState, string $message = ''): void
    {
        if (! in_array(HasStateMachine::class, class_uses_recursive($model))) {
            Assert::fail('Model must use HasStateMachine trait');
        }

        $currentState = $model->getCurrentState();

        Assert::assertEquals(
            $expectedState,
            $currentState,
            $message !== '' && $message !== '0' ? $message : "Model should be in state '{$expectedState}' but is in '{$currentState}'"
        );
    }

    /**
     * Assert that a model has specific available transitions.
     *
     * @param  Model  $model  Model that uses HasStateMachine trait
     * @param  array<string>  $expectedTransitions
     */
    public static function assertHasAvailableTransitions(Model $model, array $expectedTransitions, string $message = ''): void
    {
        if (! in_array(HasStateMachine::class, class_uses_recursive($model))) {
            Assert::fail('Model must use HasStateMachine trait');
        }

        $availableTransitions = $model->getAvailableTransitions();
        $availableStates = array_column($availableTransitions, 'to');

        sort($expectedTransitions);
        sort($availableStates);

        Assert::assertEquals(
            $expectedTransitions,
            $availableStates,
            $message !== '' && $message !== '0' ? $message : 'Available transitions should be ['.implode(', ', $expectedTransitions).'] but found ['.implode(', ', $availableStates).']'
        );
    }

    /**
     * Assert that a model can execute a specific method.
     */
    public static function assertCanExecuteMethod(Model $model, string $method, string $message = ''): void
    {
        if (! in_array(HasStateMachine::class, class_uses_recursive($model))) {
            Assert::fail('Model must use HasStateMachine trait');
        }

        $canMethod = 'can' . self::stateToMethod($method);
        $canTransition = $model->$canMethod();

        Assert::assertTrue(
            $canTransition,
            $message !== '' && $message !== '0' ? $message : "Model should be able to execute '{$method}' method"
        );
    }

    /**
     * Assert that a model cannot execute a specific method.
     */
    public static function assertCannotExecuteMethod(Model $model, string $method, string $message = ''): void
    {
        if (! in_array(HasStateMachine::class, class_uses_recursive($model))) {
            Assert::fail('Model must use HasStateMachine trait');
        }

        $canMethod = 'can' . self::stateToMethod($method);
        $canTransition = $model->$canMethod();

        Assert::assertFalse(
            $canTransition,
            $message !== '' && $message !== '0' ? $message : "Model should not be able to execute '{$method}' method"
        );
    }

    /**
     * Get all available transitions for a model.
     *
     * @param  Model  $model  Model that uses HasStateMachine trait
     * @return array<string, array<string>>
     */
    public static function transitionsFor(Model $model): array
    {
        if (! in_array(HasStateMachine::class, class_uses_recursive($model))) {
            Assert::fail('Model must use HasStateMachine trait');
        }

        $currentState = $model->getCurrentState();
        $availableTransitions = $model->getAvailableTransitions();

        $transitions = [];
        foreach ($availableTransitions as $transition) {
            $transitions[] = $transition['to'];
        }

        return [$currentState => $transitions];
    }

    /**
     * Convert a state name to the corresponding method name.
     */
    private static function stateToMethod(string $state): string
    {
        // Handle common state patterns by converting to verb form
        $stateToVerb = [
            'pending' => 'Submit',
            'approved' => 'Approve',
            'rejected' => 'Reject',
            'published' => 'Publish',
            'archived' => 'Archive',
            'active' => 'Activate',
            'inactive' => 'Deactivate',
            'completed' => 'Complete',
            'cancelled' => 'Cancel',
        ];

        if (isset($stateToVerb[$state])) {
            return $stateToVerb[$state];
        }

        // Default: convert to PascalCase
        return str_replace('_', '', ucwords($state, '_'));
    }

}
