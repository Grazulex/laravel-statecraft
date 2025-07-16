<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StateTransitioned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Model $model;

    public string $from;

    public string $to;

    public function __construct(Model $model, string $from, string $to)
    {
        $this->model = $model;
        $this->from = $from;
        $this->to = $to;
    }
}
