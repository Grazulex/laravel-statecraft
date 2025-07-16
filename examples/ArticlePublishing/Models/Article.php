<?php

declare(strict_types=1);

namespace Examples\ArticlePublishing\Models;

use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Grazulex\LaravelStatecraft\Traits\HasStateHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Article extends Model
{
    use HasFactory, HasStateMachine, HasStateHistory;
    
    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'author_id',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
    ];
    
    protected $casts = [
        'published_at' => 'datetime',
    ];
    
    /**
     * Get the state machine definition name.
     */
    protected function getStateMachineDefinitionName(): string
    {
        return 'simple-article-workflow';
    }
    
    /**
     * Get the author of the article.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
    
    /**
     * Check if the article is published.
     */
    public function isPublished(): bool
    {
        return $this->getCurrentState() === 'published';
    }
    
    /**
     * Check if the article is in review.
     */
    public function isInReview(): bool
    {
        return $this->getCurrentState() === 'review';
    }
    
    /**
     * Check if the article is rejected.
     */
    public function isRejected(): bool
    {
        return $this->getCurrentState() === 'rejected';
    }
    
    /**
     * Check if the article is a draft.
     */
    public function isDraft(): bool
    {
        return $this->getCurrentState() === 'draft';
    }
    
    /**
     * Scope to get only published articles.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
    
    /**
     * Scope to get articles by author.
     */
    public function scopeByAuthor($query, $authorId)
    {
        return $query->where('author_id', $authorId);
    }
    
    /**
     * Scope to get articles in review.
     */
    public function scopeInReview($query)
    {
        return $query->where('status', 'review');
    }
    
    /**
     * Get the URL for the article.
     */
    public function getUrlAttribute(): string
    {
        if ($this->isPublished()) {
            return route('articles.show', $this->slug);
        }
        
        return route('articles.preview', $this->slug);
    }
    
    /**
     * Get the route key name for URL binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    
    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($article) {
            // Auto-generate slug if not provided
            if (empty($article->slug)) {
                $article->slug = \Str::slug($article->title);
            }
        });
        
        static::updating(function ($article) {
            // Update slug if title changed
            if ($article->isDirty('title') && empty($article->slug)) {
                $article->slug = \Str::slug($article->title);
            }
        });
    }
}