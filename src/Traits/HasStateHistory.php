<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Traits;

use Grazulex\LaravelStatecraft\Models\StateTransition;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasStateHistory
{
    /**
     * Get the state transition history for this model.
     */
    public function stateHistory(): MorphMany
    {
        return $this->morphMany(StateTransition::class, 'model');
    }

    /**
     * Get the latest state transition.
     */
    public function latestStateTransition(): ?StateTransition
    {
        /** @var StateTransition|null $result */
        $result = $this->stateHistory()->latest()->first();

        return $result;
    }

    /**
     * Record a state transition.
     */
    public function recordStateTransition(string $fromState, string $toState, ?string $guard = null, ?string $action = null, array $metadata = []): void
    {
        if (config('statecraft.history.enabled', false)) {
            $this->stateHistory()->create([
                'from_state' => $fromState,
                'to_state' => $toState,
                'guard' => $guard,
                'action' => $action,
                'metadata' => $metadata,
                'state_machine' => $this->getStateMachineName(),
                'transition' => $action, // Use action as transition name for now
            ]);
        }
    }

    /**
     * Get the state machine name for history tracking.
     */
    private function getStateMachineName(): string
    {
        // @phpstan-ignore-next-line
        if (method_exists($this, 'getStateMachineDefinitionName')) {
            return $this->getStateMachineDefinitionName();
        }

        return class_basename(static::class).'Workflow';
    }
}
