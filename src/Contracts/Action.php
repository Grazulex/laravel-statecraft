<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Action
{
    /**
     * Execute an action during a transition.
     */
    public function execute(Model $model, string $from, string $to): void;
}
