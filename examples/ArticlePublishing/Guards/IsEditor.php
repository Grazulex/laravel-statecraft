<?php

declare(strict_types=1);

namespace Examples\ArticlePublishing\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class IsEditor implements Guard
{
    /**
     * Check if the current user has editor permissions.
     */
    public function check(Model $model, string $from, string $to): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }
        
        // Check if user has editor role
        // This assumes you're using a role-based system
        // Adjust according to your authorization system
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('editor');
        }
        
        // Alternative: check for specific permission
        if (method_exists($user, 'can')) {
            return $user->can('edit_articles');
        }
        
        // Fallback: check for is_editor attribute
        return $user->is_editor ?? false;
    }
}