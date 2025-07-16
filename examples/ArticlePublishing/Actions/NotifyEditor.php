<?php

declare(strict_types=1);

namespace Examples\ArticlePublishing\Actions;

use Grazulex\LaravelStatecraft\Contracts\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyEditor implements Action
{
    /**
     * Notify editors when an article is submitted for review.
     */
    public function execute(Model $model, string $from, string $to): void
    {
        Log::info("Article {$model->id} submitted for review", [
            'article_id' => $model->id,
            'title' => $model->title,
            'author_id' => $model->author_id,
            'from_state' => $from,
            'to_state' => $to,
        ]);
        
        // In a real application, you would:
        // 1. Find all editors
        // 2. Send notifications/emails
        // 3. Update review queues
        
        // Example implementation (commented out to avoid dependencies):
        
        /*
        // Get all editors
        $editors = User::whereHas('roles', function ($query) {
            $query->where('name', 'editor');
        })->get();
        
        // Send notifications
        foreach ($editors as $editor) {
            $editor->notify(new ArticleSubmittedNotification($model));
        }
        
        // Or send email
        Mail::to($editors)
            ->send(new ArticleSubmittedMail($model));
        */
        
        // Simple implementation for demonstration
        $this->sendSimpleNotification($model, $to);
    }
    
    private function sendSimpleNotification(Model $model, string $state): void
    {
        // This would typically integrate with your notification system
        // For now, we'll just log the action
        Log::info("Notification sent to editors", [
            'article_id' => $model->id,
            'title' => $model->title,
            'new_state' => $state,
            'message' => "Article '{$model->title}' has been submitted for review",
        ]);
    }
}