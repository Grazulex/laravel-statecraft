<?php

declare(strict_types=1);

namespace Examples\OrderWorkflow\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class IsCustomer implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        // Check if the current user is the customer who placed the order
        return auth()->check() && auth()->id() === $model->customer_id;
    }
}