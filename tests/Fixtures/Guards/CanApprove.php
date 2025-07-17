<?php

declare(strict_types=1);

namespace Tests\Fixtures\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class CanApprove implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        // Must have minimum amount AND be a manager
        return $model->amount >= 100 && $model->user_role === 'manager';
    }
}
