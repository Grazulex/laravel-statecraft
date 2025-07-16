<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Contracts\Action;
use Grazulex\LaravelStatecraft\Contracts\Guard;
use Grazulex\LaravelStatecraft\Events\StateTransitioned;
use Grazulex\LaravelStatecraft\Events\StateTransitioning;
use Grazulex\LaravelStatecraft\Support\StateMachineManager;
use Grazulex\LaravelStatecraft\Support\YamlStateMachineLoader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Fixtures\Order;

uses(RefreshDatabase::class);

describe('Events', function () {
    beforeEach(function () {
        // Enable events
        config(['statecraft.events.enabled' => true]);
    });

    test('StateTransitioning event is dispatched before transition', function () {
        Event::fake();

        // Use StateMachineManager directly with the test YAML
        $loader = new YamlStateMachineLoader(__DIR__.'/../Fixtures/yaml');
        $definition = $loader->load('order_workflow');
        $manager = new StateMachineManager($definition);

        $order = new Order(['state' => 'draft']);
        $manager->transition($order, 'pending');

        Event::assertDispatched(StateTransitioning::class, function ($event) use ($order) {
            return $event->model === $order
                && $event->from === 'draft'
                && $event->to === 'pending';
        });
    });

    test('StateTransitioned event is dispatched after transition', function () {
        Event::fake();

        // Use StateMachineManager directly with the test YAML
        $loader = new YamlStateMachineLoader(__DIR__.'/../Fixtures/yaml');
        $definition = $loader->load('order_workflow');
        $manager = new StateMachineManager($definition);

        $order = new Order(['state' => 'draft']);
        $manager->transition($order, 'pending');

        Event::assertDispatched(StateTransitioned::class, function ($event) use ($order) {
            return $event->model === $order
                && $event->from === 'draft'
                && $event->to === 'pending';
        });
    });

    test('events include guard and action information', function () {
        Event::fake();

        // Create a test guard and action
        $testGuard = new class implements Guard
        {
            public function check(Model $model, string $from, string $to): bool
            {
                return true;
            }
        };

        $testAction = new class implements Action
        {
            public function execute(Model $model, string $from, string $to): void
            {
                // Do nothing
            }
        };

        // Bind to container
        app()->bind('TestCanSubmitGuard', fn () => $testGuard);
        app()->bind('TestNotifyReviewerAction', fn () => $testAction);

        // Create a test YAML with guard and action
        $yamlContent = <<<YAML
state_machine:
  name: test-events
  model: Tests\Fixtures\Order
  field: state
  states: [draft, pending]
  initial: draft
  transitions:
    - from: draft
      to: pending
      guard: TestCanSubmitGuard
      action: TestNotifyReviewerAction
YAML;

        $tempFile = tempnam(sys_get_temp_dir(), 'test_events').'.yaml';
        file_put_contents($tempFile, $yamlContent);

        // Load and test
        $loader = new YamlStateMachineLoader(dirname($tempFile));
        $definition = $loader->load(basename($tempFile, '.yaml'));
        $manager = new StateMachineManager($definition);

        $order = new Order(['state' => 'draft']);
        $manager->transition($order, 'pending');

        // Check that events include guard and action
        Event::assertDispatched(StateTransitioning::class, function ($event) {
            return $event->guard === 'TestCanSubmitGuard'
                && $event->action === 'TestNotifyReviewerAction';
        });

        Event::assertDispatched(StateTransitioned::class, function ($event) {
            return $event->guard === 'TestCanSubmitGuard'
                && $event->action === 'TestNotifyReviewerAction';
        });

        // Cleanup
        unlink($tempFile);
    });

    test('events can be disabled via configuration', function () {
        config(['statecraft.events.enabled' => false]);
        Event::fake();

        // Use StateMachineManager directly with the test YAML
        $loader = new YamlStateMachineLoader(__DIR__.'/../Fixtures/yaml');
        $definition = $loader->load('order_workflow');
        $manager = new StateMachineManager($definition);

        $order = new Order(['state' => 'draft']);
        $manager->transition($order, 'pending');

        Event::assertNotDispatched(StateTransitioning::class);
        Event::assertNotDispatched(StateTransitioned::class);
    });

    test('events can be listened to with closures', function () {
        $transitioningFired = false;
        $transitionedFired = false;

        Event::listen(StateTransitioning::class, function ($event) use (&$transitioningFired) {
            $transitioningFired = true;
            expect($event->from)->toBe('draft');
            expect($event->to)->toBe('pending');
        });

        Event::listen(StateTransitioned::class, function ($event) use (&$transitionedFired) {
            $transitionedFired = true;
            expect($event->from)->toBe('draft');
            expect($event->to)->toBe('pending');
        });

        // Use StateMachineManager directly with the test YAML
        $loader = new YamlStateMachineLoader(__DIR__.'/../Fixtures/yaml');
        $definition = $loader->load('order_workflow');
        $manager = new StateMachineManager($definition);

        $order = new Order(['state' => 'draft']);
        $manager->transition($order, 'pending');

        expect($transitioningFired)->toBeTrue();
        expect($transitionedFired)->toBeTrue();
    });

    test('events fire in correct order', function () {
        $eventOrder = [];

        Event::listen(StateTransitioning::class, function () use (&$eventOrder) {
            $eventOrder[] = 'transitioning';
        });

        Event::listen(StateTransitioned::class, function () use (&$eventOrder) {
            $eventOrder[] = 'transitioned';
        });

        // Use StateMachineManager directly with the test YAML
        $loader = new YamlStateMachineLoader(__DIR__.'/../Fixtures/yaml');
        $definition = $loader->load('order_workflow');
        $manager = new StateMachineManager($definition);

        $order = new Order(['state' => 'draft']);
        $manager->transition($order, 'pending');

        expect($eventOrder)->toBe(['transitioning', 'transitioned']);
    });
});
