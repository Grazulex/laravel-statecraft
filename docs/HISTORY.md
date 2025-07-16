# State History Tracking

Laravel Statecraft can automatically track state transitions, providing a complete audit trail of state changes for your models.

# Historique des transitions d'état

La fonctionnalité d'historique des transitions permet de suivre et d'enregistrer automatiquement toutes les transitions d'état effectuées sur vos modèles. Cela fournit une piste d'audit complète et permet d'analyser le comportement de votre application.

## Configuration

### Activation de l'historique

L'historique est désactivé par défaut. Pour l'activer, modifiez votre fichier de configuration :

```php
// config/statecraft.php
'history' => [
    'enabled' => true,
    'table' => 'state_machine_history',
],
```

### Migration

La migration pour la table d'historique est incluse dans le package :

```php
// database/migrations/2024_01_01_000000_create_state_machine_history_table.php
Schema::create('state_machine_history', function (Blueprint $table) {
    $table->id();
    $table->morphs('model');
    $table->string('state_machine');
    $table->string('transition');
    $table->string('from_state');
    $table->string('to_state');
    $table->json('metadata')->nullable();
    $table->timestamps();
    
    $table->index(['model_type', 'model_id']);
    $table->index('state_machine');
    $table->index(['from_state', 'to_state']);
});
```

## Utilisation

### Trait HasStateHistory

Pour activer l'historique sur un modèle, ajoutez le trait `HasStateHistory` :

```php
use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Grazulex\LaravelStatecraft\Traits\HasStateHistory;

class Order extends Model
{
    use HasStateMachine, HasStateHistory;
    
    // ... reste du modèle
}
```

### Enregistrement automatique

Lorsque l'historique est activé et que le modèle utilise le trait `HasStateHistory`, les transitions sont automatiquement enregistrées par le `StateMachineManager`.

### Accès à l'historique

#### Récupérer tout l'historique

```php
$order = Order::find(1);

// Récupérer toutes les transitions
$history = $order->stateHistory()->orderBy('created_at')->get();

foreach ($history as $transition) {
    echo "Transition: {$transition->from_state} → {$transition->to_state}";
    echo "Via: {$transition->transition}";
    echo "Machine: {$transition->state_machine}";
    echo "Date: {$transition->created_at}";
    
    if ($transition->metadata) {
        echo "Métadonnées: " . json_encode($transition->metadata);
    }
}
```

#### Récupérer la dernière transition

```php
$order = Order::find(1);

$latest = $order->latestStateTransition();
if ($latest) {
    echo "Dernière transition: {$latest->from_state} → {$latest->to_state}";
    echo "Effectuée le: {$latest->created_at}";
}
```

### Enregistrement manuel

Vous pouvez également enregistrer manuellement des transitions :

```php
$order = Order::find(1);

$order->recordStateTransition(
    'draft',           // État de départ
    'submitted',       // État d'arrivée
    'order_workflow',  // Nom de la machine d'état
    'submit',          // Nom de la transition
    [                  // Métadonnées (optionnel)
        'user_id' => auth()->id(),
        'comment' => 'Commande soumise par le client',
        'ip_address' => request()->ip(),
    ]
);
```

## Modèle StateTransition

### Attributs

- `id` : Identifiant unique
- `model_type` : Type du modèle (polymorphe)
- `model_id` : ID du modèle (polymorphe)
- `state_machine` : Nom de la machine d'état
- `transition` : Nom de la transition
- `from_state` : État de départ
- `to_state` : État d'arrivée
- `metadata` : Métadonnées JSON (optionnel)
- `created_at` : Date de création
- `updated_at` : Date de mise à jour

### Méthodes d'accès

Le modèle `StateTransition` fournit des méthodes d'accès pour faciliter la lecture :

```php
$transition = StateTransition::first();

// Alias pour les attributs
echo $transition->from;        // Équivalent à $transition->from_state
echo $transition->to;          // Équivalent à $transition->to_state
echo $transition->custom_data; // Équivalent à $transition->metadata
```

### Relations

```php
// Récupérer le modèle associé
$model = $transition->model;

// Récupérer les transitions d'un modèle
$transitions = $order->stateHistory;
```

## Requêtes avancées

### Filtrer par machine d'état

```php
$history = $order->stateHistory()
    ->where('state_machine', 'order_workflow')
    ->get();
```

### Filtrer par états

```php
$history = $order->stateHistory()
    ->where('from_state', 'draft')
    ->where('to_state', 'submitted')
    ->get();
```

### Filtrer par période

```php
$history = $order->stateHistory()
    ->whereBetween('created_at', [
        now()->subDays(30),
        now()
    ])
    ->get();
```

### Rechercher dans les métadonnées

```php
$history = $order->stateHistory()
    ->whereJsonContains('metadata->user_id', 123)
    ->get();
```

## Intégration avec les événements

L'historique fonctionne parfaitement avec le système d'événements. Vous pouvez écouter les événements pour effectuer des actions supplémentaires :

```php
use Grazulex\LaravelStatecraft\Events\StateTransitioned;

Event::listen(StateTransitioned::class, function ($event) {
    // L'historique a déjà été enregistré à ce point
    $model = $event->model;
    $history = $model->latestStateTransition();
    
    // Effectuer des actions supplémentaires
    Log::info("Transition enregistrée", [
        'model' => get_class($model),
        'id' => $model->id,
        'transition' => $history->transition,
        'from' => $history->from_state,
        'to' => $history->to_state
    ]);
});
```

## Performance

### Indexation

La table d'historique est automatiquement indexée pour optimiser les performances :

- Index sur `model_type` et `model_id` (relation polymorphe)
- Index sur `state_machine` (filtrage par machine)
- Index sur `from_state` et `to_state` (filtrage par états)

### Purge de l'historique

Pour éviter l'accumulation excessive de données, vous pouvez mettre en place une purge périodique :

```php
// Supprimer l'historique plus ancien que 1 an
StateTransition::where('created_at', '<', now()->subYear())->delete();

// Garder seulement les 100 dernières transitions par modèle
StateTransition::whereIn('id', function ($query) {
    $query->select('id')
        ->from('state_machine_history')
        ->orderBy('created_at', 'desc')
        ->offset(100);
})->delete();
```

## Tests

### Vérifier l'enregistrement de l'historique

```php
test('history is recorded when enabled', function () {
    config(['statecraft.history.enabled' => true]);
    
    $order = Order::create(['name' => 'Test Order']);
    $order->recordStateTransition('draft', 'submitted', 'order_workflow', 'submit');
    
    expect($order->stateHistory)->toHaveCount(1);
    
    $history = $order->latestStateTransition();
    expect($history->from_state)->toBe('draft');
    expect($history->to_state)->toBe('submitted');
    expect($history->transition)->toBe('submit');
});
```

### Vérifier la désactivation de l'historique

```php
test('history is not recorded when disabled', function () {
    config(['statecraft.history.enabled' => false]);
    
    $order = Order::create(['name' => 'Test Order']);
    $order->recordStateTransition('draft', 'submitted', 'order_workflow', 'submit');
    
    expect($order->stateHistory)->toHaveCount(0);
});
```

## Dépannage

### L'historique n'est pas enregistré

1. Vérifiez que l'historique est activé dans la configuration
2. Assurez-vous que le modèle utilise le trait `HasStateHistory`
3. Vérifiez que la migration de la table d'historique a été exécutée
4. Confirmez que les transitions passent par le `StateMachineManager`

### Problèmes de performance

1. Vérifiez que les index sont correctement créés
2. Considérez la mise en place d'une purge périodique
3. Utilisez la pagination pour les grandes collections
4. Optimisez les requêtes avec des index composites si nécessaire

### Métadonnées non sauvegardées

1. Vérifiez que les métadonnées sont sérialisables en JSON
2. Assurez-vous que la colonne `metadata` est de type JSON
3. Confirmez que les données passées sont un tableau associatif

## Model Setup

Add the `HasStateHistory` trait to your model:

```php
use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Grazulex\LaravelStatecraft\Traits\HasStateHistory;

class Order extends Model
{
    use HasStateMachine, HasStateHistory;
    
    // ... rest of your model
}
```

## Using History Tracking

### Accessing History

```php
$order = Order::find(1);

// Get all state transitions
$history = $order->stateHistory();

// Get the collection of transitions
$transitions = $order->stateHistory()->get();

// Get transitions ordered by date
$chronological = $order->stateHistory()->orderBy('created_at')->get();

// Get the latest transition
$latest = $order->latestStateTransition();
```

### History Data Structure

Each history record contains:

```php
$transition = $order->latestStateTransition();

$transition->from_state;    // Previous state (null for initial state)
$transition->to_state;      // New state
$transition->guard;         // Guard class name (if any)
$transition->action;        // Action class name (if any)
$transition->metadata;      // Additional data (JSON)
$transition->created_at;    // When transition occurred
$transition->updated_at;    // When record was last updated
```

### Querying History

```php
// Get transitions to a specific state
$approvals = $order->stateHistory()
    ->where('to_state', 'approved')
    ->get();

// Get transitions from a specific state
$fromPending = $order->stateHistory()
    ->where('from_state', 'pending')
    ->get();

// Get transitions with specific guard
$managerTransitions = $order->stateHistory()
    ->where('guard', 'App\\Guards\\IsManager')
    ->get();

// Get transitions within date range
$recentTransitions = $order->stateHistory()
    ->where('created_at', '>=', now()->subDays(7))
    ->get();
```

## Advanced Usage

### Custom Metadata

You can add custom metadata to transitions:

```php
// Custom guard with metadata
class ApprovalGuard implements Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        $isValid = auth()->user()->is_manager;
        
        // Add metadata during transition
        if ($isValid) {
            $model->recordStateTransition($from, $to, static::class, null, [
                'approved_by' => auth()->user()->id,
                'approval_reason' => request('reason'),
                'approval_timestamp' => now()->toISOString(),
            ]);
        }
        
        return $isValid;
    }
}
```

### Manual History Recording

```php
// Manually record a transition (if needed)
$order->recordStateTransition(
    fromState: 'draft',
    toState: 'pending',
    guard: 'App\\Guards\\CanSubmit',
    action: 'App\\Actions\\NotifyReviewer',
    metadata: [
        'submitted_by' => auth()->user()->id,
        'submission_note' => 'Urgent order',
        'ip_address' => request()->ip(),
    ]
);
```

## History Analysis

### State Duration Analysis

```php
class OrderAnalytics
{
    public function getAverageStateTime(string $state): float
    {
        $transitions = StateTransition::where('from_state', $state)
            ->with(['model' => function ($query) {
                $query->where('model_type', Order::class);
            }])
            ->get();
        
        $durations = [];
        
        foreach ($transitions as $transition) {
            $previousTransition = StateTransition::where('model_type', Order::class)
                ->where('model_id', $transition->model_id)
                ->where('to_state', $state)
                ->first();
            
            if ($previousTransition) {
                $duration = $transition->created_at->diffInHours($previousTransition->created_at);
                $durations[] = $duration;
            }
        }
        
        return count($durations) > 0 ? array_sum($durations) / count($durations) : 0;
    }
    
    public function getStateTransitionCounts(): array
    {
        return StateTransition::where('model_type', Order::class)
            ->selectRaw('from_state, to_state, count(*) as count')
            ->groupBy('from_state', 'to_state')
            ->get()
            ->mapWithKeys(function ($item) {
                return ["{$item->from_state} -> {$item->to_state}" => $item->count];
            })
            ->toArray();
    }
}
```

### Performance Metrics

```php
class OrderMetrics
{
    public function getApprovalRate(): float
    {
        $submitted = StateTransition::where('to_state', 'pending')
            ->where('model_type', Order::class)
            ->count();
        
        $approved = StateTransition::where('to_state', 'approved')
            ->where('model_type', Order::class)
            ->count();
        
        return $submitted > 0 ? ($approved / $submitted) * 100 : 0;
    }
    
    public function getProcessingTime(): array
    {
        $orders = Order::with(['stateHistory' => function ($query) {
            $query->orderBy('created_at');
        }])->get();
        
        $processingTimes = [];
        
        foreach ($orders as $order) {
            $submitted = $order->stateHistory->where('to_state', 'pending')->first();
            $completed = $order->stateHistory->whereIn('to_state', ['approved', 'rejected'])->first();
            
            if ($submitted && $completed) {
                $processingTimes[] = $completed->created_at->diffInHours($submitted->created_at);
            }
        }
        
        return [
            'average' => count($processingTimes) > 0 ? array_sum($processingTimes) / count($processingTimes) : 0,
            'min' => count($processingTimes) > 0 ? min($processingTimes) : 0,
            'max' => count($processingTimes) > 0 ? max($processingTimes) : 0,
        ];
    }
}
```

## History Reporting

### Generate Transition Report

```php
class StateHistoryReport
{
    public function generateReport(Model $model): array
    {
        $history = $model->stateHistory()
            ->orderBy('created_at')
            ->get();
        
        $report = [
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'total_transitions' => $history->count(),
            'current_state' => $model->getCurrentState(),
            'created_at' => $model->created_at,
            'timeline' => [],
        ];
        
        foreach ($history as $transition) {
            $report['timeline'][] = [
                'from' => $transition->from_state,
                'to' => $transition->to_state,
                'timestamp' => $transition->created_at->toISOString(),
                'guard' => $transition->guard,
                'action' => $transition->action,
                'metadata' => $transition->metadata,
            ];
        }
        
        return $report;
    }
    
    public function exportToJson(Model $model): string
    {
        return json_encode($this->generateReport($model), JSON_PRETTY_PRINT);
    }
}
```

### Audit Trail

```php
class AuditTrail
{
    public function getOrderAuditTrail(Order $order): Collection
    {
        return $order->stateHistory()
            ->orderBy('created_at')
            ->get()
            ->map(function ($transition) {
                return [
                    'timestamp' => $transition->created_at->format('Y-m-d H:i:s'),
                    'action' => $this->formatTransition($transition),
                    'user' => $this->getUserInfo($transition),
                    'details' => $transition->metadata,
                ];
            });
    }
    
    private function formatTransition(StateTransition $transition): string
    {
        if ($transition->from_state === null) {
            return "Order created in {$transition->to_state} state";
        }
        
        return "State changed from {$transition->from_state} to {$transition->to_state}";
    }
    
    private function getUserInfo(StateTransition $transition): ?array
    {
        if (isset($transition->metadata['user_id'])) {
            $user = User::find($transition->metadata['user_id']);
            return $user ? ['id' => $user->id, 'name' => $user->name] : null;
        }
        
        return null;
    }
}
```

## Database Optimizations

### Indexes

The default migration includes these indexes:

```php
$table->index(['model_type', 'model_id']);
$table->index('created_at');
```

For better performance with large datasets, consider additional indexes:

```php
// Custom migration for additional indexes
Schema::table('state_machine_history', function (Blueprint $table) {
    $table->index(['model_type', 'to_state']);
    $table->index(['model_type', 'from_state']);
    $table->index(['model_type', 'created_at']);
    $table->index(['guard']);
    $table->index(['action']);
});
```

### Partitioning

For very large datasets, consider table partitioning:

```php
// Monthly partitioning example
Schema::create('state_machine_history_202401', function (Blueprint $table) {
    // Same structure as main table
});

// Custom loader for partitioned tables
class PartitionedStateHistory
{
    public function getHistoryForMonth(Model $model, string $month): Collection
    {
        $tableName = "state_machine_history_{$month}";
        
        return DB::table($tableName)
            ->where('model_type', get_class($model))
            ->where('model_id', $model->id)
            ->orderBy('created_at')
            ->get();
    }
}
```

## Data Retention

### Cleanup Old History

```php
// Command to clean up old history
class CleanupStateHistory extends Command
{
    protected $signature = 'statecraft:cleanup-history {--days=90}';
    
    public function handle(): void
    {
        $days = $this->option('days');
        $cutoff = now()->subDays($days);
        
        $deleted = StateTransition::where('created_at', '<', $cutoff)->delete();
        
        $this->info("Deleted {$deleted} old state history records");
    }
}
```

### Archival Strategy

```php
class StateHistoryArchiver
{
    public function archiveOldHistory(int $days = 365): void
    {
        $cutoff = now()->subDays($days);
        
        $records = StateTransition::where('created_at', '<', $cutoff)->get();
        
        // Archive to separate table or external storage
        foreach ($records->chunk(1000) as $chunk) {
            DB::table('state_history_archive')->insert($chunk->toArray());
        }
        
        // Remove from main table
        StateTransition::where('created_at', '<', $cutoff)->delete();
    }
}
```

## Performance Considerations

### Conditional History

Enable history only for specific models:

```php
class Order extends Model
{
    use HasStateMachine, HasStateHistory;
    
    public function recordStateTransition(
        string $fromState,
        string $toState,
        ?string $guard = null,
        ?string $action = null,
        array $metadata = []
    ): void {
        // Only record history for important orders
        if ($this->amount > 1000) {
            parent::recordStateTransition($fromState, $toState, $guard, $action, $metadata);
        }
    }
}
```

### Batch Operations

For bulk operations, consider disabling history temporarily:

```php
// Disable history for bulk operations
config(['statecraft.history.enabled' => false]);

// Perform bulk operations
Order::chunk(100, function ($orders) {
    foreach ($orders as $order) {
        $order->approve();
    }
});

// Re-enable history
config(['statecraft.history.enabled' => true]);
```

## Testing History

```php
class OrderHistoryTest extends TestCase
{
    public function test_history_is_recorded(): void
    {
        config(['statecraft.history.enabled' => true]);
        
        $order = Order::factory()->create();
        $order->submit();
        
        $history = $order->stateHistory()->get();
        
        $this->assertCount(1, $history);
        $this->assertEquals('draft', $history->first()->from_state);
        $this->assertEquals('pending', $history->first()->to_state);
    }
    
    public function test_history_includes_metadata(): void
    {
        config(['statecraft.history.enabled' => true]);
        
        $order = Order::factory()->create();
        $order->recordStateTransition('draft', 'pending', null, null, [
            'user_id' => 1,
            'reason' => 'Test submission',
        ]);
        
        $transition = $order->latestStateTransition();
        
        $this->assertEquals(1, $transition->metadata['user_id']);
        $this->assertEquals('Test submission', $transition->metadata['reason']);
    }
}
```

History tracking provides valuable insights into your state machine workflows and helps maintain a complete audit trail for compliance and analysis purposes.