<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Testing;

use Grazulex\LaravelStatecraft\Support\StateMachineManager;
use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Assert;

class StateMachineTester
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

        $manager = self::getStateMachineManager($model);
        $canTransition = $manager->canTransition($model, $toState);

        Assert::assertTrue(
            $canTransition,
            $message !== '' && $message !== '0' ? $message : "Transition from '{$manager->getCurrentState($model)}' to '{$toState}' should be allowed"
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

        $manager = self::getStateMachineManager($model);
        $canTransition = $manager->canTransition($model, $toState);

        Assert::assertFalse(
            $canTransition,
            $message !== '' && $message !== '0' ? $message : "Transition from '{$manager->getCurrentState($model)}' to '{$toState}' should be blocked"
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

        $manager = self::getStateMachineManager($model);
        $currentState = $manager->getCurrentState($model);

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

        $manager = self::getStateMachineManager($model);
        $availableTransitions = $manager->getAvailableTransitions($model);
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

        $canMethod = 'can'.ucfirst($method);

        Assert::assertTrue(
            $model->$canMethod(),
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

        $canMethod = 'can'.ucfirst($method);

        Assert::assertFalse(
            $model->$canMethod(),
            $message !== '' && $message !== '0' ? $message : "Model should not be able to execute '{$method}' method"
        );
    }

    /**
     * Get the state machine manager from a model with the trait.
     *
     * @param  Model  $model  Model that uses HasStateMachine trait
     */
    private static function getStateMachineManager(Model $model): StateMachineManager
    {
        /** @var callable $getManager */
        $getManager = [$model, 'getStateMachineManager'];

        return call_user_func($getManager);
    }
}
