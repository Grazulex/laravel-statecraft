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
        'payment_status',
        'approved_at',
        'approved_by',
        'payment_processed_at',
        'reviewed_at',
        'reviewer_id',
    ];

    protected $attributes = [
        'state' => 'draft',
    ];

    // Override save method for testing
    public function save(array $options = [])
    {
        // Mock save - just return true for testing
        return true;
    }
}
