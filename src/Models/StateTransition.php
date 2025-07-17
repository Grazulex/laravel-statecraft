<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $from_state
 * @property string $to_state
 * @property string|null $guard
 * @property string|null $action
 * @property array|null $metadata
 * @property string $state_machine
 * @property string $transition
 * @property string $model_type
 * @property int $model_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class StateTransition extends Model
{
    protected $fillable = [
        'from_state',
        'to_state',
        'guard',
        'action',
        'metadata',
        'state_machine',
        'transition',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the parent model that owns the state transition.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the "from" state (alias for from_state).
     */
    public function getFromAttribute(): ?string
    {
        return $this->from_state;
    }

    /**
     * Get the "to" state (alias for to_state).
     */
    public function getToAttribute(): string
    {
        return $this->to_state;
    }

    /**
     * Get custom data (alias for metadata).
     */
    public function getCustomDataAttribute(): ?array
    {
        return $this->metadata;
    }

    /**
     * Get the table name based on configuration.
     */
    public function getTable(): string
    {
        return config('statecraft.history.table', 'state_machine_history');
    }
}
