# Article Publishing Example

This example demonstrates a simple content publishing workflow using Laravel Statecraft.

## Overview

This example shows how to implement a basic article publishing system with:
- Content creation and editing
- Editorial review process
- Publication workflow
- Simple guards and actions

## States

- **draft**: Initial state, article being written
- **review**: Article submitted for editorial review
- **published**: Article is live and public
- **rejected**: Article rejected by editor

## Workflow

```
draft → review → published
   ↓        ↓
   ↓    rejected
   ↓        ↓
   └────────┘
```

## Files

- `simple-article-workflow.yaml` - Basic workflow configuration
- `advanced-article-workflow.yaml` - Advanced workflow with more features
- `Guards/` - Guard implementations
- `Actions/` - Action implementations
- `Models/Article.php` - Example model
- `tests/` - Test suite

## Quick Start

1. Copy the YAML file to your state machines directory:
```bash
cp simple-article-workflow.yaml database/state_machines/
```

2. Set up your Article model:
```php
use Grazulex\LaravelStatecraft\Traits\HasStateMachine;

class Article extends Model
{
    use HasStateMachine;
    
    protected function getStateMachineDefinitionName(): string
    {
        return 'simple-article-workflow';
    }
}
```

3. Use the workflow:
```php
$article = Article::create([
    'title' => 'My Article',
    'content' => 'Article content...',
    'author_id' => auth()->id(),
]);

// Submit for review
if ($article->canSubmitForReview()) {
    $article->submitForReview();
}

// Publish (as editor)
if ($article->canPublish()) {
    $article->publish();
}
```

## Guards

### IsEditor
Checks if the current user has editor permissions.

```php
class IsEditor implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        return auth()->user()?->hasRole('editor');
    }
}
```

### IsAuthorOrEditor
Allows either the author or an editor to perform the action.

```php
class IsAuthorOrEditor implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        $user = auth()->user();
        
        return $user && (
            $user->id === $model->author_id ||
            $user->hasRole('editor')
        );
    }
}
```

## Actions

### NotifyEditor
Sends notification to editors when article is submitted for review.

```php
class NotifyEditor implements Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        $editors = User::whereHas('roles', function ($query) {
            $query->where('name', 'editor');
        })->get();
        
        foreach ($editors as $editor) {
            $editor->notify(new ArticleSubmittedNotification($model));
        }
    }
}
```

### NotifyAuthor
Notifies the author when article status changes.

```php
class NotifyAuthor implements Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        $author = User::find($model->author_id);
        
        if ($author) {
            $notification = match ($to) {
                'published' => new ArticlePublishedNotification($model),
                'rejected' => new ArticleRejectedNotification($model),
                default => null,
            };
            
            if ($notification) {
                $author->notify($notification);
            }
        }
    }
}
```

## Usage Examples

### Basic Article Operations

```php
// Create article
$article = Article::create([
    'title' => 'Getting Started with Laravel',
    'content' => 'This is a comprehensive guide...',
    'author_id' => auth()->id(),
]);

// Check current state
$article->getCurrentState(); // 'draft'

// Check available transitions
$article->getAvailableTransitions(); // ['review']

// Submit for review
$article->submitForReview();
$article->getCurrentState(); // 'review'
```

### Editor Operations

```php
// As an editor, review the article
$article = Article::find(1);

if ($article->getCurrentState() === 'review') {
    // Check if we can publish
    if ($article->canPublish()) {
        $article->publish();
    }
    
    // Or reject
    if ($article->canReject()) {
        $article->reject();
    }
}
```

### Author Operations

```php
// Author can edit rejected articles
$article = Article::find(1);

if ($article->getCurrentState() === 'rejected') {
    // Make changes
    $article->update(['content' => 'Updated content...']);
    
    // Resubmit for review
    if ($article->canSubmitForReview()) {
        $article->submitForReview();
    }
}
```

## Testing

Run the test suite:

```bash
php artisan test examples/ArticlePublishing/tests/
```

### Test Examples

```php
public function test_article_starts_in_draft_state(): void
{
    $article = Article::factory()->create();
    
    StateMachineTester::assertInState($article, 'draft');
}

public function test_author_can_submit_for_review(): void
{
    $author = User::factory()->create();
    $this->actingAs($author);
    
    $article = Article::factory()->create(['author_id' => $author->id]);
    
    StateMachineTester::assertTransitionAllowed($article, 'review');
    StateMachineTester::assertCanExecuteMethod($article, 'submitForReview');
}

public function test_editor_can_publish_article(): void
{
    $editor = User::factory()->create();
    $editor->assignRole('editor');
    $this->actingAs($editor);
    
    $article = Article::factory()->create(['status' => 'review']);
    
    StateMachineTester::assertTransitionAllowed($article, 'published');
    StateMachineTester::assertCanExecuteMethod($article, 'publish');
}
```

## Configuration

### Simple Configuration

```yaml
# simple-article-workflow.yaml
state_machine:
  name: simple-article-workflow
  model: App\Models\Article
  field: status
  states: [draft, review, published, rejected]
  initial: draft
  transitions:
    - from: draft
      to: review
      guard: Examples\ArticlePublishing\Guards\IsAuthorOrEditor
      action: Examples\ArticlePublishing\Actions\NotifyEditor
    
    - from: review
      to: published
      guard: Examples\ArticlePublishing\Guards\IsEditor
      action: Examples\ArticlePublishing\Actions\NotifyAuthor
    
    - from: review
      to: rejected
      guard: Examples\ArticlePublishing\Guards\IsEditor
      action: Examples\ArticlePublishing\Actions\NotifyAuthor
    
    - from: rejected
      to: review
      guard: Examples\ArticlePublishing\Guards\IsAuthorOrEditor
      action: Examples\ArticlePublishing\Actions\NotifyEditor
```

### Advanced Configuration

```yaml
# advanced-article-workflow.yaml
state_machine:
  name: advanced-article-workflow
  model: App\Models\Article
  field: status
  states: [draft, review, published, rejected, archived]
  initial: draft
  transitions:
    - from: draft
      to: review
      guard: Examples\ArticlePublishing\Guards\IsAuthorOrEditor
      action: Examples\ArticlePublishing\Actions\NotifyEditor
    
    - from: review
      to: published
      guard: Examples\ArticlePublishing\Guards\IsEditor
      action: Examples\ArticlePublishing\Actions\NotifyAuthor
    
    - from: review
      to: rejected
      guard: Examples\ArticlePublishing\Guards\IsEditor
      action: Examples\ArticlePublishing\Actions\NotifyAuthor
    
    - from: rejected
      to: review
      guard: Examples\ArticlePublishing\Guards\IsAuthorOrEditor
      action: Examples\ArticlePublishing\Actions\NotifyEditor
    
    - from: published
      to: archived
      guard: Examples\ArticlePublishing\Guards\IsEditor
      action: Examples\ArticlePublishing\Actions\ArchiveArticle
```

## Database Schema

```php
// Migration for articles table
Schema::create('articles', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->string('status')->default('draft');
    $table->foreignId('author_id')->constrained('users');
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
    
    $table->index(['status', 'published_at']);
    $table->index(['author_id', 'status']);
});
```

## Real-World Considerations

### SEO-Friendly URLs

```php
class Article extends Model
{
    use HasStateMachine;
    
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
    
    public function getUrlAttribute(): string
    {
        return $this->status === 'published' 
            ? route('articles.show', $this->slug)
            : route('articles.preview', $this->slug);
    }
}
```

### Content Versioning

```php
class Article extends Model
{
    use HasStateMachine;
    
    protected static function booted(): void
    {
        static::updating(function ($article) {
            if ($article->isDirty('content')) {
                $article->versions()->create([
                    'content' => $article->getOriginal('content'),
                    'version' => $article->versions()->count() + 1,
                ]);
            }
        });
    }
}
```

### Publishing Schedule

```php
class SchedulePublishing implements Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        if ($model->publish_at && $model->publish_at->isFuture()) {
            // Schedule publication
            PublishArticleJob::dispatch($model)->delay($model->publish_at);
        }
    }
}
```

This example provides a solid foundation for content management systems and demonstrates how Laravel Statecraft can simplify complex editorial workflows.