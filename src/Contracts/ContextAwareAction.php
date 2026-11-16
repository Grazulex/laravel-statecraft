<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * An action that receives the context passed to the transition
 * (for example a reason, an actor id or any custom payload).
 */
interface ContextAwareAction extends Action
{
    /**
     * Execute an action during a transition.
     *
     * @param  array<string, mixed>  $context
     */
    public function execute(Model $model, string $from, string $to, array $context = []): void;
}
