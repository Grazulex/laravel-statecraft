<?php

declare(strict_types=1);

namespace Examples\UserSubscription\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class IsAdminOverride implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        return auth()->user()?->is_admin === true && 
               request()->has('admin_override') && 
               request()->boolean('admin_override');
    }
}