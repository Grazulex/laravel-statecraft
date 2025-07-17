<?php

declare(strict_types=1);

namespace Examples\UserSubscription\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class PaymentFailed implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        return $model->last_payment_failed_at !== null;
    }
}