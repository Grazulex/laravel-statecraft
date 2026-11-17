<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Support;

use Grazulex\LaravelStatecraft\Contracts\Action;
use Grazulex\LaravelStatecraft\Contracts\ContextAwareAction;
use Grazulex\LaravelStatecraft\Contracts\ContextAwareGuard;
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
     *
     * @param  array<string, mixed>  $context  Context passed to context-aware guards
     */
    public function canTransition(Model $model, string $to, array $context = []): bool
    {
        return $this->explainTransition($model, $to, $context) === null;
    }

    /**
     * Explain why a transition is refused.
     *
     * Returns null when the transition is allowed, otherwise a human-readable
     * reason: the current state does not allow it, or a guard (named) or a
     * guard expression refused it.
     *
     * @param  array<string, mixed>  $context  Context passed to context-aware guards
     */
    public function explainTransition(Model $model, string $to, array $context = []): ?string
    {
        $currentState = $this->getCurrentState($model);

        if (! $this->definition->isTransitionValid($currentState, $to)) {
            if (! $this->definition->isStateValid($to)) {
                return "State '{$to}' is not defined in the state machine.";
            }

            return "No transition is defined from the current state '{$currentState}' to '{$to}'.";
        }

        $transition = $this->definition->getTransition($currentState, $to);
        $guardDefinition = $transition['guard'] ?? null;

        if (! $guardDefinition) {
            return null;
        }

        // Guard expressions (and/or/not) are evaluated by the parser
        if (GuardExpressionParser::isExpression($guardDefinition)) {
            $guardFunction = GuardExpressionParser::parse($guardDefinition, $currentState, $to);

            if ($guardFunction($model, $context)) {
                return null;
            }

            $expression = json_encode($guardDefinition, JSON_UNESCAPED_SLASHES);

            return "Guard expression {$expression} refused the transition from '{$currentState}' to '{$to}'.";
        }

        // Otherwise, resolve a single guard class
        $guard = $this->resolveGuard($guardDefinition);

        $allowed = $guard instanceof ContextAwareGuard
            ? $guard->check($model, $currentState, $to, $context)
            : $guard->check($model, $currentState, $to);

        if ($allowed) {
            return null;
        }

        return "Guard '{$guardDefinition}' refused the transition from '{$currentState}' to '{$to}'.";
    }

    /**
     * Execute a transition.
     *
     * @param  array<string, mixed>  $context  Context (reason, actor, payload...) passed to
     *                                         context-aware guards and actions, events and history
     */
    public function transition(Model $model, string $to, array $context = []): void
    {
        $currentState = $this->getCurrentState($model);

        if (! $this->canTransition($model, $to, $context)) {
            throw new InvalidTransitionException("Transition from {$currentState} to {$to} is not allowed");
        }

        $transition = $this->definition->getTransition($currentState, $to);

        // Fire transitioning event
        if (config('statecraft.events.enabled', true)) {
            $guardForEvent = is_array($transition['guard']) ? json_encode($transition['guard']) : $transition['guard'];
            Event::dispatch(new StateTransitioning($model, $currentState, $to, $guardForEvent, $transition['action'], $context));
        }

        // Execute action if defined
        if ($transition['action']) {
            $action = $this->resolveAction($transition['action']);

            if ($action instanceof ContextAwareAction) {
                $action->execute($model, $currentState, $to, $context);
            } else {
                $action->execute($model, $currentState, $to);
            }
        }

        // Update the model state
        $model->setAttribute($this->definition->getField(), $to);
        $model->save();

        // Record state transition history
        if (method_exists($model, 'recordStateTransition')) {
            $guardForHistory = is_array($transition['guard']) ? json_encode($transition['guard']) : $transition['guard'];
            $model->recordStateTransition($currentState, $to, $guardForHistory, $transition['action'], $context);
        }

        // Fire transitioned event
        if (config('statecraft.events.enabled', true)) {
            $guardForEvent = is_array($transition['guard']) ? json_encode($transition['guard']) : $transition['guard'];
            Event::dispatch(new StateTransitioned($model, $currentState, $to, $guardForEvent, $transition['action'], $context));
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
     *
     * @param  array<string, mixed>  $context  Context passed to context-aware guards
     */
    public function getAvailableTransitions(Model $model, array $context = []): array
    {
        $currentState = $this->getCurrentState($model);
        $transitions = $this->definition->getTransitionsFrom($currentState);

        $available = [];

        foreach ($transitions as $transition) {
            if ($this->canTransition($model, $transition['to'], $context)) {
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
        // Essayer d'abord le nom tel quel, puis avec le namespace par défaut si ça échoue
        try {
            $guard = app($guardClass);
        } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
            if (! str_contains($guardClass, '\\')) {
                $guardClass = 'App\\StateMachines\\Guards\\'.$guardClass;
                $guard = app($guardClass);
            } else {
                throw $e;
            }
        }

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
        // Essayer d'abord le nom tel quel, puis avec le namespace par défaut si ça échoue
        try {
            $action = app($actionClass);
        } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
            if (! str_contains($actionClass, '\\')) {
                $actionClass = 'App\\StateMachines\\Actions\\'.$actionClass;
                $action = app($actionClass);
            } else {
                throw $e;
            }
        }

        if (! $action instanceof Action) {
            throw new InvalidTransitionException("Action {$actionClass} must implement Action interface");
        }

        return $action;
    }
}
