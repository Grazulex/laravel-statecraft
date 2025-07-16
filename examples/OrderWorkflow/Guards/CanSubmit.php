<?php

declare(strict_types=1);

namespace Examples\OrderWorkflow\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

/**
 * Guard to check if an order can be submitted.
 * This guard checks if order has required fields filled.
 */
class CanSubmit implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        // Check if order has required fields
        $requiredFields = ['customer_email', 'items'];

        foreach ($requiredFields as $field) {
            if (empty($model->getAttribute($field))) {
                return false;
            }
        }

        // Check if order has at least one item
        $items = $model->getAttribute('items');
        if (is_array($items) && count($items) === 0) {
            return false;
        }

        return true;
    }
}
