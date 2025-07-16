<?php

declare(strict_types=1);

namespace Examples\OrderWorkflow\Actions;

use Grazulex\LaravelStatecraft\Contracts\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Action to notify reviewer when order is submitted.
 * This action sends an email to the review team.
 */
class NotifyReviewer implements Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        // Log the transition
        Log::info("Order #{$model->id} transitioned from {$from} to {$to}");

        // In a real application, you would send an email
        // Mail::to('reviewer@company.com')->send(new OrderSubmittedMail($model));

        // For this example, we'll just log the action
        Log::info("Notification sent to reviewer for order #{$model->id}");

        // You could also update some model attributes
        $model->setAttribute('reviewed_at', null);
        $model->setAttribute('reviewer_id', null);
    }
}
