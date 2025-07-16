<?php

declare(strict_types=1);

namespace Examples\UserSubscription\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class HasValidPayment implements Guard
{
    /**
     * Check if the subscription has valid payment information.
     */
    public function check(Model $model, string $from, string $to): bool
    {
        // Check if payment method is present
        if (empty($model->payment_method)) {
            return false;
        }
        
        // Check if payment method is valid (not expired, not declined, etc.)
        if (!$this->isPaymentMethodValid($model->payment_method)) {
            return false;
        }
        
        // Check if user has sufficient funds or valid payment method
        if (!$this->canProcessPayment($model)) {
            return false;
        }
        
        return true;
    }
    
    private function isPaymentMethodValid(string $paymentMethod): bool
    {
        // In a real application, you would validate with payment provider
        // For example, check if credit card is not expired, not declined, etc.
        
        // Simple validation - payment method should not be empty or invalid
        if (str_starts_with($paymentMethod, 'invalid_')) {
            return false;
        }
        
        // Mock validation - in real app, this would be Stripe/PayPal validation
        return !empty($paymentMethod) && str_starts_with($paymentMethod, 'card_');
    }
    
    private function canProcessPayment(Model $model): bool
    {
        // In a real application, you might:
        // 1. Check with payment provider if payment can be processed
        // 2. Validate user's payment limits
        // 3. Check for fraud indicators
        // 4. Verify subscription amount is valid
        
        // Basic validation
        if ($model->amount <= 0) {
            return false;
        }
        
        // Check if user is not blocked from payments
        if ($model->user && $model->user->payment_blocked) {
            return false;
        }
        
        return true;
    }
}