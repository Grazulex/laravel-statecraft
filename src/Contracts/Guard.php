<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Guard
{
    /**
     * Check if a transition is allowed.
     */
    public function check(Model $model, string $from, string $to): bool;
}
