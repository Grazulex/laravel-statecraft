<?php

declare(strict_types=1);

namespace Examples\ArticlePublishing\Actions;

use Grazulex\LaravelStatecraft\Contracts\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class NotifyAuthor implements Action
{
    /**
     * Notify the author when article status changes.
     */
    public function execute(Model $model, string $from, string $to): void
    {
        Log::info("Article {$model->id} status changed", [
            'article_id' => $model->id,
            'title' => $model->title,
            'author_id' => $model->author_id,
            'from_state' => $from,
            'to_state' => $to,
        ]);
        
        // Get the appropriate notification message
        $message = $this->getNotificationMessage($model, $to);
        
        if ($message) {
            $this->sendNotificationToAuthor($model, $message, $to);
        }
        
        // Additional actions based on state
        match ($to) {
            'published' => $this->handlePublished($model),
            'rejected' => $this->handleRejected($model),
            default => null,
        };
    }
    
    private function getNotificationMessage(Model $model, string $state): ?string
    {
        return match ($state) {
            'published' => "Your article '{$model->title}' has been published!",
            'rejected' => "Your article '{$model->title}' has been rejected. Please review and resubmit.",
            'archived' => "Your article '{$model->title}' has been archived.",
            default => null,
        };
    }
    
    private function sendNotificationToAuthor(Model $model, string $message, string $state): void
    {
        // In a real application, you would:
        // 1. Find the author
        // 2. Send notification/email
        // 3. Update author's dashboard
        
        // Example implementation (commented out to avoid dependencies):
        
        /*
        $author = User::find($model->author_id);
        
        if ($author) {
            $notification = match ($state) {
                'published' => new ArticlePublishedNotification($model),
                'rejected' => new ArticleRejectedNotification($model),
                default => new ArticleStatusChangedNotification($model, $state),
            };
            
            $author->notify($notification);
        }
        */
        
        // Simple implementation for demonstration
        Log::info("Notification sent to author", [
            'article_id' => $model->id,
            'author_id' => $model->author_id,
            'message' => $message,
            'state' => $state,
        ]);
    }
    
    private function handlePublished(Model $model): void
    {
        // Update published timestamp
        $model->update(['published_at' => now()]);
        
        // Additional actions when published
        Log::info("Article published", [
            'article_id' => $model->id,
            'title' => $model->title,
            'published_at' => now(),
        ]);
    }
    
    private function handleRejected(Model $model): void
    {
        // Clear published timestamp
        $model->update(['published_at' => null]);
        
        // Additional actions when rejected
        Log::info("Article rejected", [
            'article_id' => $model->id,
            'title' => $model->title,
            'rejection_reason' => request('reason', 'No reason provided'),
        ]);
    }
}