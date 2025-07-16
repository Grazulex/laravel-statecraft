<?php

declare(strict_types=1);

namespace Examples\OrderWorkflow\Actions;

use Grazulex\LaravelStatecraft\Contracts\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Action to process payment when order is approved.
 * This action would typically integrate with a payment gateway.
 */
class ProcessPayment implements Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        // Log the transition
        Log::info("Processing payment for order #{$model->id}");

        // In a real application, you would integrate with a payment gateway
        // $paymentResult = PaymentGateway::charge($model->amount, $model->payment_method);

        // For this example, we'll simulate payment processing
        $paymentSuccessful = true; // Simulate success

        if ($paymentSuccessful) {
            Log::info("Payment processed successfully for order #{$model->id}");

            // Update model attributes
            $model->setAttribute('payment_status', 'paid');
            $model->setAttribute('payment_processed_at', now());
        } else {
            Log::error("Payment failed for order #{$model->id}");

            // In a real scenario, you might want to transition to a 'payment_failed' state
            $model->setAttribute('payment_status', 'failed');
        }
    }
}
