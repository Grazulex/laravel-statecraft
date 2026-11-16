<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * A guard that receives the context passed to the transition
 * (for example a reason, an actor id or any custom payload).
 */
interface ContextAwareGuard extends Guard
{
    /**
     * Check if a transition is allowed.
     *
     * @param  array<string, mixed>  $context
     */
    public function check(Model $model, string $from, string $to, array $context = []): bool;
}
