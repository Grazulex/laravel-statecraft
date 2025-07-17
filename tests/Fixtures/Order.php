<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Grazulex\LaravelStatecraft\Traits\HasStateHistory;
use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasStateHistory, HasStateMachine;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'state',
        'customer_email',
        'items',
        'amount',
        'user_role',
        'payment_status',
        'approved_at',
        'approved_by',
        'payment_processed_at',
        'reviewed_at',
        'reviewer_id',
        'is_manager',
        'is_vip',
        'is_urgent',
    ];

    protected $attributes = [
        'state' => 'draft',
        'amount' => 0,
        'user_role' => 'user',
        'is_manager' => false,
        'is_vip' => false,
        'is_urgent' => false,
    ];

    // Override save method for testing
    public function save(array $options = [])
    {
        // Mock save - just return true for testing
        return true;
    }

    /**
     * Get the state machine definition name.
     */
    protected function getStateMachineDefinitionName(): string
    {
        return 'OrderWorkflow';
    }
}
