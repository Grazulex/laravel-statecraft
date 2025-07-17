<?php

declare(strict_types=1);

namespace Tests\Fixtures\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class HasMinimumAmount implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        return $model->amount >= 100;
    }
}
