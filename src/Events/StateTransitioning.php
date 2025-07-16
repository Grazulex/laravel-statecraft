<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class StateTransitioning
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Model $model;

    public string $from;

    public string $to;

    public ?string $guard;

    public ?string $action;

    public function __construct(Model $model, string $from, string $to, ?string $guard = null, ?string $action = null)
    {
        $this->model = $model;
        $this->from = $from;
        $this->to = $to;
        $this->guard = $guard;
        $this->action = $action;
    }
}
