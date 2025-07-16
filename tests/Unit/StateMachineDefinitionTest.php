<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition;
use Grazulex\LaravelStatecraft\Exceptions\InvalidStateMachineDefinitionException;

describe('StateMachineDefinition', function () {
    it('can create a basic definition', function () {
        $definition = new StateMachineDefinition(
            name: 'TestWorkflow',
            model: 'App\Models\Test',
            states: ['draft', 'published'],
            initial: 'draft'
        );

        expect($definition->getName())->toBe('TestWorkflow');
        expect($definition->getModel())->toBe('App\Models\Test');
        expect($definition->getStates())->toBe(['draft', 'published']);
        expect($definition->getInitial())->toBe('draft');
        expect($definition->getField())->toBe('state');
    });

    it('can create a definition with custom field', function () {
        $definition = new StateMachineDefinition(
            name: 'TestWorkflow',
            model: 'App\Models\Test',
            states: ['draft', 'published'],
            initial: 'draft',
            field: 'status'
        );

        expect($definition->getField())->toBe('status');
    });

    it('can validate states in transitions', function () {
        expect(fn () => new StateMachineDefinition(
            name: 'TestWorkflow',
            model: 'App\Models\Test',
            states: ['draft', 'published'],
            initial: 'draft',
            transitions: [
                ['from' => 'draft', 'to' => 'invalid'],
            ]
        ))->toThrow(InvalidStateMachineDefinitionException::class);
    });

    it('can get transitions from a state', function () {
        $definition = new StateMachineDefinition(
            name: 'TestWorkflow',
            model: 'App\Models\Test',
            states: ['draft', 'pending', 'published'],
            initial: 'draft',
            transitions: [
                ['from' => 'draft', 'to' => 'pending'],
                ['from' => 'pending', 'to' => 'published'],
                ['from' => 'pending', 'to' => 'draft'],
            ]
        );

        $transitions = $definition->getTransitionsFrom('pending');

        expect($transitions)->toHaveCount(2);

        $transitionTos = array_column($transitions, 'to');
        expect($transitionTos)->toContain('published');
        expect($transitionTos)->toContain('draft');
    });

    it('can check if transition is valid', function () {
        $definition = new StateMachineDefinition(
            name: 'TestWorkflow',
            model: 'App\Models\Test',
            states: ['draft', 'published'],
            initial: 'draft',
            transitions: [
                ['from' => 'draft', 'to' => 'published'],
            ]
        );

        expect($definition->isTransitionValid('draft', 'published'))->toBeTrue();
        expect($definition->isTransitionValid('published', 'draft'))->toBeFalse();
    });
});
