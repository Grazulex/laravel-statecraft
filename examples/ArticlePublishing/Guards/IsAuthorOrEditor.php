<?php

declare(strict_types=1);

namespace Examples\ArticlePublishing\Guards;

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class IsAuthorOrEditor implements Guard
{
    /**
     * Check if the current user is either the author or an editor.
     */
    public function check(Model $model, string $from, string $to): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }
        
        // Check if user is the author
        if ($user->id === $model->author_id) {
            return true;
        }
        
        // Check if user has editor permissions
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