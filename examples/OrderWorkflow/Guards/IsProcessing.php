<?php

declare(strict_types=1);

namespace Examples\OrderWorkflow\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class IsProcessing implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        // Check if order is currently being processed
        return $model->processing_started_at !== null && $model->processing_completed_at === null;
    }
}
