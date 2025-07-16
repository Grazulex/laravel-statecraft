<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Support;

use Grazulex\LaravelStatecraft\Contracts\Action;
use Grazulex\LaravelStatecraft\Contracts\Guard;
use Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition;
use Grazulex\LaravelStatecraft\Events\StateTransitioned;
use Grazulex\LaravelStatecraft\Events\StateTransitioning;
use Grazulex\LaravelStatecraft\Exceptions\InvalidTransitionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;

final class StateMachineManager
{
    private StateMachineDefinition $definition;

    public function __construct(StateMachineDefinition $definition)
    {
        $this->definition = $definition;
    }

    /**
     * Check if a transition is allowed.
     */
    public function canTransition(Model $model, string $to): bool
    {
        $currentState = $this->getCurrentState($model);

        if (! $this->definition->isTransitionValid($currentState, $to)) {
            return false;
        }

        $transition = $this->definition->getTransition($currentState, $to);

        if ($transition['guard']) {
            $guard = $this->resolveGuard($transition['guard']);

            return $guard->check($model, $currentState, $to);
        }

        return true;
    }

    /**
     * Execute a transition.
     */
    public function transition(Model $model, string $to): void
    {
        $currentState = $this->getCurrentState($model);

        if (! $this->canTransition($model, $to)) {
            throw new InvalidTransitionException("Transition from {$currentState} to {$to} is not allowed");
        }

        $transition = $this->definition->getTransition($currentState, $to);

        // Fire transitioning event
        if (config('statecraft.events.enabled', true)) {
            Event::dispatch(new StateTransitioning($model, $currentState, $to, $transition['guard'], $transition['action']));
        }

        // Execute action if defined
        if ($transition['action']) {
            $action = $this->resolveAction($transition['action']);
            $action->execute($model, $currentState, $to);
        }

        // Update the model state
        $model->setAttribute($this->definition->getField(), $to);
        $model->save();

        // Record state transition history
        if (method_exists($model, 'recordStateTransition')) {
            $model->recordStateTransition($currentState, $to, $transition['guard'], $transition['action']);
        }

        // Fire transitioned event
        if (config('statecraft.events.enabled', true)) {
            Event::dispatch(new StateTransitioned($model, $currentState, $to, $transition['guard'], $transition['action']));
        }
    }

    /**
     * Get the current state of the model.
     */
    public function getCurrentState(Model $model): string
    {
        return $model->getAttribute($this->definition->getField()) ?? $this->definition->getInitial();
    }

    /**
     * Get available transitions from current state.
     */
    public function getAvailableTransitions(Model $model): array
    {
        $currentState = $this->getCurrentState($model);
        $transitions = $this->definition->getTransitionsFrom($currentState);

        $available = [];

        foreach ($transitions as $transition) {
            if ($this->canTransition($model, $transition['to'])) {
                $available[] = $transition;
            }
        }

        return $available;
    }

    /**
     * Initialize the model with the initial state.
     */
    public function initialize(Model $model): void
    {
        if (! $model->getAttribute($this->definition->getField())) {
            $model->setAttribute($this->definition->getField(), $this->definition->getInitial());
        }
    }

    /**
     * Get the state machine definition.
     */
    public function getDefinition(): StateMachineDefinition
    {
        return $this->definition;
    }

    /**
     * Resolve a guard class.
     */
    private function resolveGuard(string $guardClass): Guard
    {
        $guard = app($guardClass);

        if (! $guard instanceof Guard) {
            throw new InvalidTransitionException("Guard {$guardClass} must implement Guard interface");
        }

        return $guard;
    }

    /**
     * Resolve an action class.
     */
    private function resolveAction(string $actionClass): Action
    {
        $action = app($actionClass);

        if (! $action instanceof Action) {
            throw new InvalidTransitionException("Action {$actionClass} must implement Action interface");
        }

        return $action;
    }
}
