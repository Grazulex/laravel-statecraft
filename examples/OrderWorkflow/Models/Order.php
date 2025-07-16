<?php

declare(strict_types=1);

namespace Examples\OrderWorkflow\Models;

use Grazulex\LaravelStatecraft\Traits\HasStateHistory;
use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Model;

/**
 * Example Order model showing how to use Laravel Statecraft with Guards and Actions.
 *
 * @property int $id
 * @property string $customer_email
 * @property array $items
 * @property float $amount
 * @property string $status
 * @property string $payment_status
 * @property \Carbon\Carbon $approved_at
 * @property int $approved_by
 * @property \Carbon\Carbon $payment_processed_at
 * @property \Carbon\Carbon $reviewed_at
 * @property int $reviewer_id
 */
class Order extends Model
{
    use HasStateHistory, HasStateMachine;

    protected $fillable = [
        'customer_email',
        'items',
        'amount',
        'status',
        'payment_status',
        'approved_at',
        'approved_by',
        'payment_processed_at',
        'reviewed_at',
        'reviewer_id',
    ];

    protected $casts = [
        'items' => 'array',
        'amount' => 'float',
        'approved_at' => 'datetime',
        'payment_processed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the total amount of the order.
     */
    public function getTotalAmount(): float
    {
        return $this->items ? array_sum(array_column($this->items, 'price')) : 0.0;
    }

    /**
     * Check if order has all required fields.
     */
    public function hasRequiredFields(): bool
    {
        return ! empty($this->customer_email) && ! empty($this->items);
    }

    /**
     * Check if order meets minimum amount requirement.
     */
    public function meetsMinimumAmount(): bool
    {
        return $this->amount >= 100;
    }

    /**
     * Override the state machine definition name.
     */
    protected function getStateMachineDefinitionName(): string
    {
        return 'advanced-order-workflow';
    }
}
