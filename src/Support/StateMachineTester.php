<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Support;

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
    public static function assertTransitionAllowed(Model $model, string $to): void
    {
        if (! in_array(HasStateMachine::class, class_uses_recursive($model))) {
            Assert::fail('Model must use HasStateMachine trait');
        }

        $canMethod = 'can'.ucfirst($to);

        Assert::assertTrue(
            $model->$canMethod(),
            "Expected transition to '$to' to be allowed, but it was blocked."
        );
    }

    /**
     * Assert that a transition is blocked.
     *
     * @param  Model  $model  Model that uses HasStateMachine trait
     */
    public static function assertTransitionBlocked(Model $model, string $to): void
    {
        if (! in_array(HasStateMachine::class, class_uses_recursive($model))) {
            Assert::fail('Model must use HasStateMachine trait');
        }

        $canMethod = 'can'.ucfirst($to);

        Assert::assertFalse(
            $model->$canMethod(),
            "Expected transition to '$to' to be blocked, but it was allowed."
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

        /** @phpstan-ignore-next-line method.notFound */
        $currentState = $model->getCurrentState();
        /** @phpstan-ignore-next-line method.notFound */
        $availableTransitions = $model->getAvailableTransitions();

        $transitions = [];
        foreach ($availableTransitions as $transition) {
            $transitions[] = $transition['to'];
        }

        return [$currentState => $transitions];
    }

    /**
     * Assert that a model is in a specific state.
     *
     * @param  Model  $model  Model that uses HasStateMachine trait
     */
    public static function assertInState(Model $model, string $expectedState): void
    {
        if (! in_array(HasStateMachine::class, class_uses_recursive($model))) {
            Assert::fail('Model must use HasStateMachine trait');
        }

        /** @phpstan-ignore-next-line method.notFound */
        $currentState = $model->getCurrentState();

        Assert::assertEquals(
            $expectedState,
            $currentState,
            "Model should be in state '{$expectedState}' but is in '{$currentState}'"
        );
    }

    /**
     * Assert that a model has specific available transitions.
     *
     * @param  Model  $model  Model that uses HasStateMachine trait
     * @param  array<string>  $expectedTransitions
     */
    public static function assertHasAvailableTransitions(Model $model, array $expectedTransitions): void
    {
        if (! in_array(HasStateMachine::class, class_uses_recursive($model))) {
            Assert::fail('Model must use HasStateMachine trait');
        }

        /** @phpstan-ignore-next-line method.notFound */
        $availableTransitions = $model->getAvailableTransitions();
        $availableStates = array_column($availableTransitions, 'to');

        sort($expectedTransitions);
        sort($availableStates);

        Assert::assertEquals(
            $expectedTransitions,
            $availableStates,
            'Available transitions should be ['.implode(', ', $expectedTransitions).'] but found ['.implode(', ', $availableStates).']'
        );
    }
}
