<?php

declare(strict_types=1);

namespace Examples\UserSubscription\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class ViolatedTerms implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        return $model->terms_violation_count > 0;
    }
}
