# Configuration

Laravel Statecraft provides flexible configuration options to customize behavior according to your needs.

## Publishing Configuration

To publish the configuration file:

```bash
php artisan vendor:publish --tag=statecraft-config
```

This creates `config/statecraft.php` with all available options.

## Configuration Options

### State Machines Path

**Default**: `database/state_machines`

```php
'state_machines_path' => database_path('state_machines'),
```

Defines where your YAML state machine definitions are stored. Can be absolute or relative to Laravel base path.

**Examples**:
```php
'state_machines_path' => storage_path('state_machines'),
'state_machines_path' => resource_path('workflows'),
'state_machines_path' => '/var/www/workflows',
```

### Default State Field

**Default**: `state`

```php
'default_state_field' => 'state',
```

The default database field name used to store the current state on your models. Can be overridden in individual YAML definitions.

**Examples**:
```php
'default_state_field' => 'status',
'default_state_field' => 'workflow_state',
'default_state_field' => 'current_step',
```

### Generated Code Path

**Default**: `app/StateMachines`

```php
'generated_code_path' => app_path('StateMachines'),
```

Directory where the `statecraft:generate` command creates PHP classes (guards, actions, model examples).

**Examples**:
```php
'generated_code_path' => app_path('Workflows'),
'generated_code_path' => base_path('src/StateMachines'),
```

### Events Configuration

**Default**: `true`

```php
'events' => [
    'enabled' => true,
],
```

Controls whether state machine events (`StateTransitioning`, `StateTransitioned`) are dispatched during transitions.

**Disable events**:
```php
'events' => [
    'enabled' => false,
],
```

### History Tracking

**Default**: `false`

```php
'history' => [
    'enabled' => false,
    'table' => 'state_machine_history',
],
```

Controls automatic tracking of state transitions.

**Enable history tracking**:
```php
'history' => [
    'enabled' => true,
    'table' => 'state_transitions', // Custom table name
],
```

## Environment-Specific Configuration

You can use environment variables for configuration:

```php
// config/statecraft.php
'state_machines_path' => env('STATECRAFT_PATH', database_path('state_machines')),
'events' => [
    'enabled' => env('STATECRAFT_EVENTS_ENABLED', true),
],
'history' => [
    'enabled' => env('STATECRAFT_HISTORY_ENABLED', false),
    'table' => env('STATECRAFT_HISTORY_TABLE', 'state_machine_history'),
],
```

**In your `.env` file**:
```env
STATECRAFT_PATH=/var/www/workflows
STATECRAFT_EVENTS_ENABLED=true
STATECRAFT_HISTORY_ENABLED=true
STATECRAFT_HISTORY_TABLE=order_transitions
```

## Per-Model Configuration

Individual models can override certain configuration options:

```php
class Order extends Model
{
    use HasStateMachine;
    
    protected function getStateMachineDefinitionName(): string
    {
        return 'order-workflow'; // Custom YAML file name
    }
}
```

## YAML Definition Configuration

Each YAML file can specify its own field name:

```yaml
state_machine:
  name: order-workflow
  model: App\Models\Order
  field: status  # Override default field
  states: [draft, pending, approved]
  initial: draft
  transitions:
    # ... transitions
```

## Runtime Configuration

You can modify configuration at runtime:

```php
// Temporarily disable events
config(['statecraft.events.enabled' => false]);

// Change state machines path
config(['statecraft.state_machines_path' => '/custom/path']);
```

## Testing Configuration

In your tests, you might want to:

```php
// Disable events for faster tests
config(['statecraft.events.enabled' => false]);

// Enable history for testing
config(['statecraft.history.enabled' => true]);

// Use in-memory database for history
config(['statecraft.history.table' => 'test_state_transitions']);
```

## Migration Configuration

When using history tracking, publish and run the migration:

```bash
php artisan vendor:publish --tag=statecraft-migrations
php artisan migrate
```

**Custom migration**:
```php
// Create custom migration
php artisan make:migration create_order_transitions_table

// In the migration
Schema::create('order_transitions', function (Blueprint $table) {
    $table->id();
    $table->morphs('model');
    $table->string('from_state')->nullable();
    $table->string('to_state');
    $table->string('guard')->nullable();
    $table->string('action')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
});

// Update configuration
config(['statecraft.history.table' => 'order_transitions']);
```

## Security Considerations

- **Path Security**: Ensure state machine files are not publicly accessible
- **Permissions**: Set appropriate file permissions on state machine directories
- **Validation**: Always validate user input before state transitions
- **Guards**: Use guards to enforce business rules and permissions

## Performance Considerations

- **Caching**: Consider caching state machine definitions in production
- **Events**: Disable events if not needed for better performance
- **History**: Enable history tracking only when needed
- **File System**: Use fast storage for state machine files

## Example: Multi-Environment Setup

```php
// config/statecraft.php
return [
    'state_machines_path' => env('STATECRAFT_PATH', match (app()->environment()) {
        'production' => storage_path('statecraft/production'),
        'staging' => storage_path('statecraft/staging'),
        'testing' => storage_path('statecraft/testing'),
        default => database_path('state_machines'),
    }),
    
    'events' => [
        'enabled' => env('STATECRAFT_EVENTS_ENABLED', !app()->environment('testing')),
    ],
    
    'history' => [
        'enabled' => env('STATECRAFT_HISTORY_ENABLED', app()->environment('production')),
        'table' => env('STATECRAFT_HISTORY_TABLE', 'state_machine_history'),
    ],
];
```

This configuration provides maximum flexibility while maintaining sensible defaults for different environments.