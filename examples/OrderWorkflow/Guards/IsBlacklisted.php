<?php

declare(strict_types=1);

namespace Examples\OrderWorkflow\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class IsBlacklisted implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        // Check if customer is blacklisted
        return $model->customer_blacklisted === true;
    }
}
