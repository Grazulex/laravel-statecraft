<?php

declare(strict_types=1);

namespace Tests\Fixtures\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class IsUrgent implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        return $model->is_urgent ?? false;
    }
}
