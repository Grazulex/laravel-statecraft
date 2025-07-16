<?php

declare(strict_types=1);

namespace Examples\UserSubscription\Actions;

use Grazulex\LaravelStatecraft\Contracts\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProcessPayment implements Action
{
    /**
     * Process payment when activating subscription.
     */
    public function execute(Model $model, string $from, string $to): void
    {
        Log::info("Processing payment for subscription {$model->id}", [
            'subscription_id' => $model->id,
            'user_id' => $model->user_id,
            'amount' => $model->amount,
            'currency' => $model->currency,
            'payment_method' => $model->payment_method,
            'from_state' => $from,
            'to_state' => $to,
        ]);
        
        try {
            // Process the payment
            $paymentResult = $this->processPaymentWithProvider($model);
            
            if ($paymentResult['success']) {
                // Update subscription with payment information
                $this->updateSubscriptionAfterPayment($model, $paymentResult);
                
                // Send welcome email if first activation
                if ($from === 'trial') {
                    $this->sendWelcomeEmail($model);
                }
                
                // Reset failed attempts
                $model->update(['reactivation_attempts' => 0]);
                
                Log::info("Payment processed successfully for subscription {$model->id}");
            } else {
                throw new \Exception($paymentResult['error']);
            }
            
        } catch (\Exception $e) {
            Log::error("Payment processing failed for subscription {$model->id}", [
                'error' => $e->getMessage(),
                'subscription_id' => $model->id,
            ]);
            
            // In a real application, you might want to prevent the state transition
            throw $e;
        }
    }
    
    private function processPaymentWithProvider(Model $model): array
    {
        // In a real application, this would integrate with:
        // - Stripe: $stripe->paymentIntents->create()
        // - PayPal: PayPal API calls
        // - Other payment providers
        
        // Mock payment processing
        try {
            // Simulate payment processing delay
            // sleep(1);
            
            // Mock successful payment
            $transactionId = 'txn_' . uniqid();
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'amount' => $model->amount,
                'currency' => $model->currency,
                'processed_at' => now(),
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    private function updateSubscriptionAfterPayment(Model $model, array $paymentResult): void
    {
        $model->update([
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'last_payment_at' => now(),
            'metadata' => array_merge($model->metadata ?? [], [
                'last_transaction_id' => $paymentResult['transaction_id'],
                'payment_processed_at' => $paymentResult['processed_at']->toISOString(),
            ]),
        ]);
    }
    
    private function sendWelcomeEmail(Model $model): void
    {
        // In a real application, this would send an actual email
        // Mail::to($model->user)->send(new WelcomeEmail($model));
        
        Log::info("Welcome email sent to user {$model->user_id} for subscription {$model->id}");
    }
}