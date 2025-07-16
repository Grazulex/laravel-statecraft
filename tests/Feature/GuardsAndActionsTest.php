<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Contracts\Action;
use Grazulex\LaravelStatecraft\Contracts\Guard;
use Grazulex\LaravelStatecraft\Support\StateMachineManager;
use Grazulex\LaravelStatecraft\Support\YamlStateMachineLoader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Tests\Fixtures\Order;

uses(RefreshDatabase::class);

// Mock Guards for testing
class TestCanSubmitGuard implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        $email = $model->getAttribute('customer_email');
        $items = $model->getAttribute('items');

        return ! empty($email) && ! empty($items) && is_array($items) && count($items) > 0;
    }
}

class TestIsManagerGuard implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        $user = Auth::user();

        return $user && ($user->is_manager ?? false);
    }
}

class TestHasMinimumAmountGuard implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        return ($model->getAttribute('amount') ?? 0) >= 100;
    }
}

// Mock Actions for testing
class TestNotifyReviewerAction implements Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        Log::info("Order #{$model->id} transitioned from {$from} to {$to}");
        Log::info("Notification sent to reviewer for order #{$model->id}");

        $model->setAttribute('reviewed_at', null);
        $model->setAttribute('reviewer_id', null);
    }
}

class TestSendConfirmationEmailAction implements Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        Log::info("Order #{$model->id} approved - sending confirmation email");

        $email = $model->getAttribute('customer_email') ?? '';
        Log::info("Confirmation email sent to {$email}");

        $model->setAttribute('approved_at', now());
        $model->setAttribute('approved_by', Auth::id());
    }
}

class TestProcessPaymentAction implements Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        Log::info("Processing payment for order #{$model->id}");
        Log::info("Payment processed successfully for order #{$model->id}");

        $model->setAttribute('payment_status', 'paid');
        $model->setAttribute('payment_processed_at', now());
    }
}

beforeEach(function () {
    // Bind test guards and actions to container
    app()->bind('TestCanSubmitGuard', TestCanSubmitGuard::class);
    app()->bind('TestIsManagerGuard', TestIsManagerGuard::class);
    app()->bind('TestHasMinimumAmountGuard', TestHasMinimumAmountGuard::class);
    app()->bind('TestNotifyReviewerAction', TestNotifyReviewerAction::class);
    app()->bind('TestSendConfirmationEmailAction', TestSendConfirmationEmailAction::class);
    app()->bind('TestProcessPaymentAction', TestProcessPaymentAction::class);
});

describe('Guards', function () {
    test('CanSubmit guard allows submission when order has required fields', function () {
        $order = new Order([
            'customer_email' => 'test@example.com',
            'items' => [['name' => 'Product 1', 'price' => 100]],
        ]);

        $guard = new TestCanSubmitGuard();
        $result = $guard->check($order, 'draft', 'pending');

        expect($result)->toBeTrue();
    });

    test('CanSubmit guard blocks submission when order lacks required fields', function () {
        $order = new Order([
            'customer_email' => '',
            'items' => [],
        ]);

        $guard = new TestCanSubmitGuard();
        $result = $guard->check($order, 'draft', 'pending');

        expect($result)->toBeFalse();
    });

    test('IsManager guard allows transition when user is manager', function () {
        Auth::shouldReceive('user')->andReturn((object) ['is_manager' => true]);

        $order = new Order();
        $guard = new TestIsManagerGuard();
        $result = $guard->check($order, 'pending', 'approved');

        expect($result)->toBeTrue();
    });

    test('IsManager guard blocks transition when user is not manager', function () {
        Auth::shouldReceive('user')->andReturn((object) ['is_manager' => false]);

        $order = new Order();
        $guard = new TestIsManagerGuard();
        $result = $guard->check($order, 'pending', 'approved');

        expect($result)->toBeFalse();
    });

    test('IsManager guard blocks transition when user is not authenticated', function () {
        Auth::shouldReceive('user')->andReturn(null);

        $order = new Order();
        $guard = new TestIsManagerGuard();
        $result = $guard->check($order, 'pending', 'approved');

        expect($result)->toBeFalse();
    });

    test('HasMinimumAmount guard allows transition when amount is sufficient', function () {
        $order = new Order(['amount' => 150]);
        $guard = new TestHasMinimumAmountGuard();
        $result = $guard->check($order, 'approved', 'paid');

        expect($result)->toBeTrue();
    });

    test('HasMinimumAmount guard blocks transition when amount is insufficient', function () {
        $order = new Order(['amount' => 50]);
        $guard = new TestHasMinimumAmountGuard();
        $result = $guard->check($order, 'approved', 'paid');

        expect($result)->toBeFalse();
    });
});

describe('Actions', function () {
    test('NotifyReviewer action logs notification', function () {
        Log::shouldReceive('info')
            ->with('Order # transitioned from draft to pending')
            ->once();

        Log::shouldReceive('info')
            ->with('Notification sent to reviewer for order #')
            ->once();

        $order = new Order();
        $action = new TestNotifyReviewerAction();
        $action->execute($order, 'draft', 'pending');

        expect($order->getAttribute('reviewed_at'))->toBeNull();
        expect($order->getAttribute('reviewer_id'))->toBeNull();
    });

    test('SendConfirmationEmail action logs email and updates model', function () {
        Auth::shouldReceive('id')->andReturn(1);

        Log::shouldReceive('info')
            ->with('Order # approved - sending confirmation email')
            ->once();

        Log::shouldReceive('info')
            ->with('Confirmation email sent to test@example.com')
            ->once();

        $order = new Order();
        $order->setAttribute('customer_email', 'test@example.com');
        $action = new TestSendConfirmationEmailAction();
        $action->execute($order, 'pending', 'approved');

        expect($order->getAttribute('approved_at'))->not->toBeNull();
        expect($order->getAttribute('approved_by'))->toBe(1);
    });

    test('ProcessPayment action logs payment processing', function () {
        Log::shouldReceive('info')
            ->with('Processing payment for order #')
            ->once();

        Log::shouldReceive('info')
            ->with('Payment processed successfully for order #')
            ->once();

        $order = new Order(['amount' => 100]);
        $action = new TestProcessPaymentAction();
        $action->execute($order, 'approved', 'paid');

        expect($order->getAttribute('payment_status'))->toBe('paid');
        expect($order->getAttribute('payment_processed_at'))->not->toBeNull();
    });
});

describe('State Machine Integration', function () {
    test('guards are resolved and executed correctly', function () {
        // Create a test YAML definition
        $yamlContent = <<<YAML
state_machine:
  name: test-workflow
  model: Tests\Fixtures\Order
  field: state
  states: [draft, pending, approved]
  initial: draft
  transitions:
    - from: draft
      to: pending
      guard: TestCanSubmitGuard
    - from: pending
      to: approved
      guard: TestIsManagerGuard
YAML;

        $tempFile = tempnam(sys_get_temp_dir(), 'test_state_machine').'.yaml';
        file_put_contents($tempFile, $yamlContent);

        // Load the state machine
        $loader = new YamlStateMachineLoader(dirname($tempFile));
        $definition = $loader->load(basename($tempFile, '.yaml'));
        $manager = new StateMachineManager($definition);

        // Test with valid order
        $order = new Order([
            'customer_email' => 'test@example.com',
            'items' => [['name' => 'Product 1', 'price' => 100]],
            'state' => 'draft',
        ]);

        expect($manager->canTransition($order, 'pending'))->toBeTrue();

        // Test with invalid order
        $order->setAttribute('customer_email', '');
        expect($manager->canTransition($order, 'pending'))->toBeFalse();

        // Cleanup
        unlink($tempFile);
    });

    test('manager guard integration works correctly', function () {
        // Create a test YAML definition
        $yamlContent = <<<YAML
state_machine:
  name: test-workflow-manager
  model: Tests\Fixtures\Order
  field: state
  states: [pending, approved]
  initial: pending
  transitions:
    - from: pending
      to: approved
      guard: TestIsManagerGuard
YAML;

        $tempFile = tempnam(sys_get_temp_dir(), 'test_state_machine_manager').'.yaml';
        file_put_contents($tempFile, $yamlContent);

        // Load the state machine
        $loader = new YamlStateMachineLoader(dirname($tempFile));
        $definition = $loader->load(basename($tempFile, '.yaml'));
        $manager = new StateMachineManager($definition);

        // Test with manager user
        Auth::shouldReceive('user')->andReturn((object) ['is_manager' => true]);

        $order = new Order(['state' => 'pending']);
        expect($manager->canTransition($order, 'approved'))->toBeTrue();

        // Cleanup
        unlink($tempFile);
    });

    test('non-manager guard integration works correctly', function () {
        // Create a test YAML definition
        $yamlContent = <<<YAML
state_machine:
  name: test-workflow-non-manager
  model: Tests\Fixtures\Order
  field: state
  states: [pending, approved]
  initial: pending
  transitions:
    - from: pending
      to: approved
      guard: TestIsManagerGuard
YAML;

        $tempFile = tempnam(sys_get_temp_dir(), 'test_state_machine_non_manager').'.yaml';
        file_put_contents($tempFile, $yamlContent);

        // Load the state machine
        $loader = new YamlStateMachineLoader(dirname($tempFile));
        $definition = $loader->load(basename($tempFile, '.yaml'));
        $manager = new StateMachineManager($definition);

        // Test with non-manager user
        Auth::shouldReceive('user')->andReturn((object) ['is_manager' => false]);

        $order = new Order(['state' => 'pending']);
        expect($manager->canTransition($order, 'approved'))->toBeFalse();

        // Cleanup
        unlink($tempFile);
    });

    test('actions are resolved and executed correctly', function () {
        // Create a test YAML definition with actions
        $yamlContent = <<<YAML
state_machine:
  name: test-workflow-actions
  model: Tests\Fixtures\Order
  field: state
  states: [draft, pending, approved]
  initial: draft
  transitions:
    - from: draft
      to: pending
      action: TestNotifyReviewerAction
    - from: pending
      to: approved
      action: TestSendConfirmationEmailAction
YAML;

        $tempFile = tempnam(sys_get_temp_dir(), 'test_state_machine_actions').'.yaml';
        file_put_contents($tempFile, $yamlContent);

        // Load the state machine
        $loader = new YamlStateMachineLoader(dirname($tempFile));
        $definition = $loader->load(basename($tempFile, '.yaml'));
        $manager = new StateMachineManager($definition);

        // Test action execution
        $order = new Order(['state' => 'draft']);

        Log::shouldReceive('info')
            ->with('Order # transitioned from draft to pending')
            ->once();

        Log::shouldReceive('info')
            ->with('Notification sent to reviewer for order #')
            ->once();

        $manager->transition($order, 'pending');

        expect($order->getAttribute('state'))->toBe('pending');
        expect($order->getAttribute('reviewed_at'))->toBeNull();

        // Cleanup
        unlink($tempFile);
    });
});
