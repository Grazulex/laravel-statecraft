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

        // Handle canTransitionTo methods (e.g., canApprove, canReject)
        if (str_starts_with($method, 'can')) {
            $state = $this->methodToState(mb_substr($method, 3));

            return $manager->canTransition($this, $state);
        }

        // Handle transition methods (e.g., approve, reject, publish)
        $state = $this->methodToState($method);

        // Check if this is a valid transition
        if ($manager->canTransition($this, $state)) {
            $manager->transition($this, $state);

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
     */
    public function getAvailableTransitions(): array
    {
        return $this->getStateMachineManager()->getAvailableTransitions($this);
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
