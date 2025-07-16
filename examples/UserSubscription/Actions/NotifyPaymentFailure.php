<?php

declare(strict_types=1);

namespace Examples\UserSubscription\Actions;

use Grazulex\LaravelStatecraft\Contracts\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class NotifyPaymentFailure implements Action
{
    /**
     * Notify user when payment fails and subscription is suspended.
     */
    public function execute(Model $model, string $from, string $to): void
    {
        Log::info("Notifying user of payment failure for subscription {$model->id}", [
            'subscription_id' => $model->id,
            'user_id' => $model->user_id,
            'from_state' => $from,
            'to_state' => $to,
        ]);

        // Increment failed attempts
        $model->increment('reactivation_attempts');

        // Send notification to user
        $this->sendPaymentFailureNotification($model);

        // Schedule retry if haven't exceeded max attempts
        if ($model->reactivation_attempts < 3) {
            $this->schedulePaymentRetry($model);
        } else {
            $this->scheduleSubscriptionCancellation($model);
        }

        // Update subscription metadata
        $this->updateSubscriptionMetadata($model);
    }

    private function sendPaymentFailureNotification(Model $model): void
    {
        // In a real application, this would send actual notifications
        // $model->user->notify(new PaymentFailureNotification($model));

        $message = match ($model->reactivation_attempts) {
            1 => 'Your payment failed. We will retry in 3 days.',
            2 => 'Your payment failed again. We will retry one more time in 7 days.',
            default => 'Your payment failed multiple times. Your subscription will be cancelled soon.',
        };

        Log::info('Payment failure notification sent', [
            'subscription_id' => $model->id,
            'user_id' => $model->user_id,
            'message' => $message,
            'attempt' => $model->reactivation_attempts,
        ]);

        // In a real app, you might also:
        // - Send email notification
        // - Send SMS notification
        // - Create in-app notification
        // - Update user dashboard
    }

    private function schedulePaymentRetry(Model $model): void
    {
        // Schedule retry based on attempt number
        $retryDelay = match ($model->reactivation_attempts) {
            1 => now()->addDays(3),
            2 => now()->addDays(7),
            default => now()->addDays(14),
        };

        Log::info('Scheduling payment retry', [
            'subscription_id' => $model->id,
            'retry_at' => $retryDelay->toISOString(),
            'attempt' => $model->reactivation_attempts,
        ]);

        // In a real application, you would schedule a job:
        // RetryPaymentJob::dispatch($model)->delay($retryDelay);
    }

    private function scheduleSubscriptionCancellation(Model $model): void
    {
        // Schedule cancellation after final attempt
        $cancellationDelay = now()->addDays(30);

        Log::info('Scheduling subscription cancellation', [
            'subscription_id' => $model->id,
            'cancellation_at' => $cancellationDelay->toISOString(),
        ]);

        // In a real application, you would schedule a job:
        // CancelSubscriptionJob::dispatch($model)->delay($cancellationDelay);
    }

    private function updateSubscriptionMetadata(Model $model): void
    {
        $metadata = $model->metadata ?? [];

        $metadata['payment_failures'] = $metadata['payment_failures'] ?? [];
        $metadata['payment_failures'][] = [
            'failed_at' => now()->toISOString(),
            'attempt' => $model->reactivation_attempts,
            'reason' => 'Payment processing failed',
        ];

        $model->update(['metadata' => $metadata]);
    }
}
