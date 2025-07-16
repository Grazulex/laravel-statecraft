<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StateTransition extends Model
{
    protected $fillable = [
        'from_state',
        'to_state',
        'guard',
        'action',
        'metadata',
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
     * Get the table name based on configuration.
     */
    public function getTable(): string
    {
        return config('statecraft.history.table', 'state_machine_history');
    }
}
