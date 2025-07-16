<?php

declare(strict_types=1);

namespace Examples\UserSubscription\Actions;

use Grazulex\LaravelStatecraft\Contracts\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CleanupSubscription implements Action
{
    /**
     * Clean up subscription data when cancelled.
     */
    public function execute(Model $model, string $from, string $to): void
    {
        Log::info("Cleaning up subscription {$model->id}", [
            'subscription_id' => $model->id,
            'user_id' => $model->user_id,
            'from_state' => $from,
            'to_state' => $to,
        ]);

        // Update cancellation timestamp
        $model->update(['cancelled_at' => now()]);

        // Send cancellation notification
        $this->sendCancellationNotification($model, $from);

        // Clean up related data
        $this->cleanupRelatedData($model);

        // Update user's subscription status
        $this->updateUserSubscriptionStatus($model);

        // Handle refunds if necessary
        $this->handleRefunds($model, $from);

        // Update subscription metadata
        $this->updateCancellationMetadata($model, $from);
    }

    private function sendCancellationNotification(Model $model, string $fromState): void
    {
        $message = match ($fromState) {
            'trial' => 'Your trial subscription has ended.',
            'active' => 'Your subscription has been cancelled.',
            'suspended' => 'Your subscription has been cancelled due to payment issues.',
            default => 'Your subscription has been cancelled.',
        };

        Log::info('Cancellation notification sent', [
            'subscription_id' => $model->id,
            'user_id' => $model->user_id,
            'message' => $message,
            'from_state' => $fromState,
        ]);

        // In a real application, you would:
        // $model->user->notify(new SubscriptionCancelledNotification($model, $fromState));
    }

    private function cleanupRelatedData(Model $model): void
    {
        // In a real application, you might need to:
        // - Cancel scheduled jobs
        // - Remove user from premium features
        // - Clean up temporary data
        // - Update external services

        Log::info("Cleaning up related data for subscription {$model->id}");

        // Example cleanup actions
        $this->revokeUserAccess($model);
        $this->cancelScheduledJobs($model);
    }

    private function revokeUserAccess(Model $model): void
    {
        // Revoke user access to premium features
        if ($model->user) {
            // In a real app, you might update user permissions
            // $model->user->revokePermission('premium_features');

            Log::info("User access revoked for subscription {$model->id}");
        }
    }

    private function cancelScheduledJobs(Model $model): void
    {
        // Cancel any scheduled jobs related to this subscription
        // In a real application, you might:
        // - Cancel scheduled payment retries
        // - Cancel renewal reminders
        // - Cancel billing jobs

        Log::info("Scheduled jobs cancelled for subscription {$model->id}");
    }

    private function updateUserSubscriptionStatus(Model $model): void
    {
        // Update user's subscription status
        if ($model->user) {
            // In a real app, you might update user's subscription status
            // $model->user->update(['subscription_status' => 'cancelled']);

            Log::info("User subscription status updated for subscription {$model->id}");
        }
    }

    private function handleRefunds(Model $model, string $fromState): void
    {
        // Handle refunds if cancellation is from active state
        if ($fromState === 'active' && $this->shouldProcessRefund($model)) {
            $this->processRefund($model);
        }
    }

    private function shouldProcessRefund(Model $model): bool
    {
        // Determine if refund should be processed
        // This depends on your business logic

        // Example: Refund if cancelled within 30 days
        if ($model->current_period_start && $model->current_period_start->diffInDays(now()) <= 30) {
            return true;
        }

        return false;
    }

    private function processRefund(Model $model): void
    {
        // In a real application, this would process actual refunds
        // through your payment provider

        Log::info("Processing refund for subscription {$model->id}", [
            'subscription_id' => $model->id,
            'amount' => $model->amount,
            'currency' => $model->currency,
        ]);

        // Example: Stripe refund
        // $stripe->refunds->create([
        //     'charge' => $model->last_charge_id,
        //     'amount' => $model->amount * 100, // Convert to cents
        // ]);
    }

    private function updateCancellationMetadata(Model $model, string $fromState): void
    {
        $metadata = $model->metadata ?? [];

        $metadata['cancellation'] = [
            'cancelled_at' => now()->toISOString(),
            'cancelled_from' => $fromState,
            'cancellation_reason' => $this->getCancellationReason($fromState),
        ];

        $model->update(['metadata' => $metadata]);
    }

    private function getCancellationReason(string $fromState): string
    {
        return match ($fromState) {
            'trial' => 'Trial expired without payment',
            'active' => 'User cancelled subscription',
            'suspended' => 'Payment failures exceeded limit',
            default => 'Unknown reason',
        };
    }
}
