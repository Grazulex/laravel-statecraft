<?php

declare(strict_types=1);

namespace Examples\OrderWorkflow\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

/**
 * Guard to check if an order has minimum required amount.
 * This guard prevents small orders from being processed.
 */
class HasMinimumAmount implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        // Assuming the model has an 'amount' attribute
        $amount = $model->getAttribute('amount') ?? 0;

        // Minimum amount required is 100
        return $amount >= 100;
    }
}
