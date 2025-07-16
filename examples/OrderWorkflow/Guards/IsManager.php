<?php

declare(strict_types=1);

namespace Examples\OrderWorkflow\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Guard to check if the current user is a manager.
 * This guard prevents non-managers from approving orders.
 */
class IsManager implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        // Check if user is authenticated and is a manager
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Assuming user has an 'is_manager' attribute or role
        return $user->is_manager ?? false;
    }
}
