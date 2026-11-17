<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Support\YamlStateMachineLoader;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->validatePath = sys_get_temp_dir().'/statecraft_validate_'.uniqid();
    File::makeDirectory($this->validatePath, 0755, true);

    $this->writeYaml = function (string $name, string $content): void {
        File::put($this->validatePath.'/'.$name.'.yaml', $content);
    };
});

afterEach(function () {
    File::deleteDirectory($this->validatePath);
});

describe('statecraft:validate stricter checks', function () {
    test('a valid definition with existing guards passes', function () {
        ($this->writeYaml)('valid-machine', <<<'YAML'
state_machine:
  name: valid-machine
  model: Tests\Fixtures\Order
  states: [pending, approved, rejected]
  initial: pending
  transitions:
    - from: pending
      to: approved
      guard: Tests\Fixtures\Guards\IsManager
    - from: pending
      to: rejected
      guard:
        and:
          - Tests\Fixtures\Guards\IsManager
          - not: Tests\Fixtures\Guards\IsVIP
YAML);

        $this->artisan('statecraft:validate', ['file' => 'valid-machine', '--path' => $this->validatePath])
            ->expectsOutputToContain('valid-machine.yaml is valid')
            ->assertExitCode(0);
    });

    test('it flags guard and action classes that do not exist', function () {
        ($this->writeYaml)('missing-classes', <<<'YAML'
state_machine:
  name: missing-classes
  model: Tests\Fixtures\Order
  states: [pending, approved]
  initial: pending
  transitions:
    - from: pending
      to: approved
      guard: App\Guards\DoesNotExistGuard
      action: App\Actions\DoesNotExistAction
YAML);

        $this->artisan('statecraft:validate', ['file' => 'missing-classes', '--path' => $this->validatePath])
            ->expectsOutputToContain("Guard class 'App\\Guards\\DoesNotExistGuard' does not exist")
            ->expectsOutputToContain("Action class 'App\\Actions\\DoesNotExistAction' does not exist")
            ->assertExitCode(0);
    });

    test('it flags missing guards nested in guard expressions', function () {
        ($this->writeYaml)('missing-nested-guard', <<<'YAML'
state_machine:
  name: missing-nested-guard
  model: Tests\Fixtures\Order
  states: [pending, approved]
  initial: pending
  transitions:
    - from: pending
      to: approved
      guard:
        or:
          - Tests\Fixtures\Guards\IsManager
          - not: App\Guards\NestedMissingGuard
YAML);

        $this->artisan('statecraft:validate', ['file' => 'missing-nested-guard', '--path' => $this->validatePath])
            ->expectsOutputToContain("Guard class 'App\\Guards\\NestedMissingGuard' does not exist")
            ->assertExitCode(0);
    });

    test('it accepts actions bound in the container', function () {
        $this->app->bind('App\\Actions\\BoundAction', fn () => new stdClass());

        ($this->writeYaml)('bound-action', <<<'YAML'
state_machine:
  name: bound-action
  model: Tests\Fixtures\Order
  states: [pending, approved]
  initial: pending
  transitions:
    - from: pending
      to: approved
      action: App\Actions\BoundAction
YAML);

        $this->artisan('statecraft:validate', ['file' => 'bound-action', '--path' => $this->validatePath])
            ->expectsOutputToContain('bound-action.yaml is valid')
            ->assertExitCode(0);
    });

    test('it flags sink states that are not declared as final', function () {
        ($this->writeYaml)('sink-state', <<<'YAML'
state_machine:
  name: sink-state
  model: Tests\Fixtures\Order
  states: [pending, approved, stuck]
  initial: pending
  final: [approved]
  transitions:
    - from: pending
      to: approved
    - from: pending
      to: stuck
YAML);

        $this->artisan('statecraft:validate', ['file' => 'sink-state', '--path' => $this->validatePath])
            ->expectsOutputToContain("State 'stuck' is a sink state: it has no outgoing transitions and is not declared as final")
            ->doesntExpectOutputToContain("State 'stuck' has no path to a final state")
            ->assertExitCode(0);
    });

    test('it flags states that cannot reach a final state', function () {
        ($this->writeYaml)('trapped-loop', <<<'YAML'
state_machine:
  name: trapped-loop
  model: Tests\Fixtures\Order
  states: [pending, review, rework, done]
  initial: pending
  transitions:
    - from: pending
      to: done
    - from: pending
      to: review
    - from: review
      to: rework
    - from: rework
      to: review
YAML);

        $this->artisan('statecraft:validate', ['file' => 'trapped-loop', '--path' => $this->validatePath])
            ->expectsOutputToContain("State 'review' has no path to a final state")
            ->expectsOutputToContain("State 'rework' has no path to a final state")
            ->doesntExpectOutputToContain("State 'pending' has no path to a final state")
            ->assertExitCode(0);
    });

    test('it flags declared final states that are not defined', function () {
        ($this->writeYaml)('unknown-final', <<<'YAML'
state_machine:
  name: unknown-final
  model: Tests\Fixtures\Order
  states: [pending, approved]
  initial: pending
  final: [approved, archived]
  transitions:
    - from: pending
      to: approved
YAML);

        $this->artisan('statecraft:validate', ['file' => 'unknown-final', '--path' => $this->validatePath])
            ->expectsOutputToContain("Final state 'archived' is not defined in states")
            ->assertExitCode(0);
    });

    test('a purely cyclic machine without final states is not flagged', function () {
        ($this->writeYaml)('toggle', <<<'YAML'
state_machine:
  name: toggle
  model: Tests\Fixtures\Order
  states: ['off', 'on']
  initial: 'off'
  transitions:
    - from: 'off'
      to: 'on'
    - from: 'on'
      to: 'off'
YAML);

        $this->artisan('statecraft:validate', ['file' => 'toggle', '--path' => $this->validatePath])
            ->expectsOutputToContain('toggle.yaml is valid')
            ->assertExitCode(0);
    });
});

describe('final states in definitions', function () {
    test('the loader reads the optional final key', function () {
        ($this->writeYaml)('with-final', <<<'YAML'
state_machine:
  name: with-final
  model: Tests\Fixtures\Order
  states: [pending, approved]
  initial: pending
  final: [approved]
  transitions:
    - from: pending
      to: approved
YAML);

        $definition = (new YamlStateMachineLoader($this->validatePath))->load('with-final');

        expect($definition->getFinalStates())->toBe(['approved']);
    });

    test('final states default to an empty list', function () {
        ($this->writeYaml)('without-final', <<<'YAML'
state_machine:
  name: without-final
  model: Tests\Fixtures\Order
  states: [pending, approved]
  initial: pending
  transitions:
    - from: pending
      to: approved
YAML);

        $definition = (new YamlStateMachineLoader($this->validatePath))->load('without-final');

        expect($definition->getFinalStates())->toBe([]);
    });
});
