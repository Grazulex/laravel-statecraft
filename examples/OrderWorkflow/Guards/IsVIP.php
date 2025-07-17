<?php

declare(strict_types=1);

namespace Examples\OrderWorkflow\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class IsVIP implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        return $model->is_vip === true;
    }
}
