<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Traits;

use Grazulex\LaravelStatecraft\Support\StateMachineManager;
use Grazulex\LaravelStatecraft\Support\YamlStateMachineLoader;
use Illuminate\Support\Str;

trait HasStateMachine
{
    private ?StateMachineManager $stateMachineManager = null;

    /**
     * Handle dynamic method calls for state transitions.
     */
    public function __call($method, $parameters)
    {
        $manager = $this->getStateMachineManager();

        // Optional transition context given as first argument (e.g. $order->approve(['reason' => '...']))
        $context = isset($parameters[0]) && is_array($parameters[0]) ? $parameters[0] : [];

        // Handle canTransitionTo methods (e.g., canApprove, canReject)
        if (str_starts_with($method, 'can')) {
            $state = $this->methodToState(mb_substr($method, 3));

            return $manager->canTransition($this, $state, $context);
        }

        // Handle transition methods (e.g., approve, reject, publish)
        $state = $this->methodToState($method);

        // Check if this is a valid transition
        if ($manager->canTransition($this, $state, $context)) {
            $manager->transition($this, $state, $context);

            return $this;
        }

        // If not a state machine method, call parent
        return parent::__call($method, $parameters);
    }

    /**
     * Get the current state of the model.
     */
    public function getCurrentState(): string
    {
        return $this->getStateMachineManager()->getCurrentState($this);
    }

    /**
     * Get available transitions from the current state.
     *
     * @param  array<string, mixed>  $context  Context passed to context-aware guards
     */
    public function getAvailableTransitions(array $context = []): array
    {
        return $this->getStateMachineManager()->getAvailableTransitions($this, $context);
    }

    /**
     * Check if the model can transition to a specific state.
     *
     * @param  array<string, mixed>  $context  Context passed to context-aware guards
     */
    public function canTransitionTo(string $state, array $context = []): bool
    {
        return $this->getStateMachineManager()->canTransition($this, $state, $context);
    }

    /**
     * Explain why the model cannot transition to a specific state.
     *
     * Returns null when the transition is allowed, otherwise the reason it is
     * refused (current state not allowing it, failing guard or guard expression).
     *
     * @param  array<string, mixed>  $context  Context passed to context-aware guards
     */
    public function explainTransition(string $state, array $context = []): ?string
    {
        return $this->getStateMachineManager()->explainTransition($this, $state, $context);
    }

    /**
     * Transition the model to a specific state.
     *
     * @param  array<string, mixed>  $context  Context (reason, actor, payload...) passed to
     *                                         context-aware guards and actions, events and history
     */
    public function transition(string $state, array $context = []): bool
    {
        if ($this->canTransitionTo($state, $context)) {
            $this->getStateMachineManager()->transition($this, $state, $context);

            return true;
        }

        return false;
    }

    /**
     * Initialize the model with the initial state.
     */
    public function initializeState(): void
    {
        $this->getStateMachineManager()->initialize($this);
    }

    /**
     * Boot the trait - called when the model is booted.
     */
    protected static function bootHasStateMachine(): void
    {
        static::creating(function ($model): void {
            if ($model->getAttribute($model->getStateMachineManager()->getDefinition()->getField()) === null) {
                $model->initializeState();
            }
        });
    }

    /**
     * Get the state machine definition name.
     * Override this method to customize the state machine name.
     */
    protected function getStateMachineDefinitionName(): string
    {
        // Convention: OrderWorkflow for Order model
        return class_basename(static::class).'Workflow';
    }

    /**
     * Get the state machine manager for this model.
     */
    protected function getStateMachineManager(): StateMachineManager
    {
        if ($this->stateMachineManager === null) {
            $loader = new YamlStateMachineLoader();
            $definitionName = Str::snake($this->getStateMachineDefinitionName());
            $definition = $loader->load($definitionName);

            $this->stateMachineManager = new StateMachineManager($definition);
        }

        return $this->stateMachineManager;
    }

    /**
     * Convert a method name to a state name.
     */
    private function methodToState(string $method): string
    {
        // Convert camelCase to snake_case and handle common patterns
        $state = Str::snake($method);

        // Handle common verb patterns
        $patterns = [
            'submit' => 'pending',
            'approve' => 'approved',
            'reject' => 'rejected',
            'publish' => 'published',
            'archive' => 'archived',
            'activate' => 'active',
            'deactivate' => 'inactive',
            'complete' => 'completed',
            'cancel' => 'cancelled',
        ];

        return $patterns[$state] ?? $state;
    }
}
