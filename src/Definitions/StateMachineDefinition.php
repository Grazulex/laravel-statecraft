<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Definitions;

use Grazulex\LaravelStatecraft\Exceptions\InvalidStateMachineDefinitionException;

final class StateMachineDefinition
{
    private string $name;

    private string $model;

    private array $states;

    private string $initial;

    private array $transitions;

    private string $field;

    public function __construct(
        string $name,
        string $model,
        array $states,
        string $initial,
        array $transitions = [],
        string $field = 'state'
    ) {
        $this->name = $name;
        $this->model = $model;
        $this->states = $states;
        $this->initial = $initial;
        $this->transitions = $this->parseTransitions($transitions);
        $this->field = $field;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getStates(): array
    {
        return $this->states;
    }

    public function getInitial(): string
    {
        return $this->initial;
    }

    public function getTransitions(): array
    {
        return $this->transitions;
    }

    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Get transitions from a specific state.
     */
    public function getTransitionsFrom(string $state): array
    {
        return array_filter($this->transitions, fn ($transition): bool => $transition['from'] === $state);
    }

    /**
     * Get a specific transition.
     */
    public function getTransition(string $from, string $to): ?array
    {
        foreach ($this->transitions as $transition) {
            if ($transition['from'] === $from && $transition['to'] === $to) {
                return $transition;
            }
        }

        return null;
    }

    /**
     * Check if a transition is valid.
     */
    public function isTransitionValid(string $from, string $to): bool
    {
        return $this->getTransition($from, $to) !== null;
    }

    /**
     * Check if a state is valid.
     */
    public function isStateValid(string $state): bool
    {
        return in_array($state, $this->states);
    }

    /**
     * Parse and validate transitions.
     */
    private function parseTransitions(array $transitions): array
    {
        $parsed = [];

        foreach ($transitions as $transition) {
            if (! isset($transition['from'], $transition['to'])) {
                throw new InvalidStateMachineDefinitionException('Transition must have "from" and "to" keys');
            }

            $from = $transition['from'];
            $to = $transition['to'];

            if (! $this->isStateValid($from)) {
                throw new InvalidStateMachineDefinitionException("Invalid 'from' state: {$from}");
            }

            if (! $this->isStateValid($to)) {
                throw new InvalidStateMachineDefinitionException("Invalid 'to' state: {$to}");
            }

            $parsed[] = [
                'from' => $from,
                'to' => $to,
                'guard' => $transition['guard'] ?? null,
                'action' => $transition['action'] ?? null,
            ];
        }

        return $parsed;
    }
}
