<?php

declare(strict_types=1);

namespace Examples\OrderWorkflow\Actions;

use Grazulex\LaravelStatecraft\Contracts\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Action to send confirmation email when order is approved.
 * This action sends an email to the customer.
 */
class SendConfirmationEmail implements Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        // Log the transition
        Log::info("Order #{$model->id} approved - sending confirmation email");

        // In a real application, you would send an email
        // Mail::to($model->customer_email)->send(new OrderApprovedMail($model));

        // For this example, we'll just log the action
        Log::info("Confirmation email sent to {$model->customer_email}");

        // Update model attributes
        $model->setAttribute('approved_at', now());
        $model->setAttribute('approved_by', Auth::id());
    }
}
