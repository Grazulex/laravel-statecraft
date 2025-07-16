<?php

declare(strict_types=1);

namespace Examples\UserSubscription\Models;

use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Grazulex\LaravelStatecraft\Traits\HasStateHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory, HasStateMachine, HasStateHistory;
    
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'payment_method',
        'amount',
        'currency',
        'trial_ends_at',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'last_payment_at',
        'reactivation_attempts',
        'metadata',
    ];
    
    protected $casts = [
        'amount' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'cancelled_at' => 'datetime',
        'last_payment_at' => 'datetime',
        'reactivation_attempts' => 'integer',
        'metadata' => 'array',
    ];
    
    /**
     * Get the state machine definition name.
     */
    protected function getStateMachineDefinitionName(): string
    {
        return 'subscription-workflow';
    }
    
    /**
     * Get the user who owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the plan for this subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
    
    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        return $this->getCurrentState() === 'active';
    }
    
    /**
     * Check if the subscription is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->getCurrentState() === 'trial';
    }
    
    /**
     * Check if the subscription is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->getCurrentState() === 'suspended';
    }
    
    /**
     * Check if the subscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->getCurrentState() === 'cancelled';
    }
    
    /**
     * Check if the trial has expired.
     */
    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isPast();
    }
    
    /**
     * Check if the current period has ended.
     */
    public function isPeriodEnded(): bool
    {
        return $this->current_period_end && $this->current_period_end->isPast();
    }
    
    /**
     * Get the days remaining in the current period.
     */
    public function daysRemainingInPeriod(): int
    {
        if (!$this->current_period_end) {
            return 0;
        }
        
        return max(0, now()->diffInDays($this->current_period_end));
    }
    
    /**
     * Get the days remaining in trial.
     */
    public function daysRemainingInTrial(): int
    {
        if (!$this->trial_ends_at) {
            return 0;
        }
        
        return max(0, now()->diffInDays($this->trial_ends_at));
    }
    
    /**
     * Scope to get active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    /**
     * Scope to get trial subscriptions.
     */
    public function scopeOnTrial($query)
    {
        return $query->where('status', 'trial');
    }
    
    /**
     * Scope to get suspended subscriptions.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }
    
    /**
     * Scope to get cancelled subscriptions.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
    
    /**
     * Scope to get expired trials.
     */
    public function scopeExpiredTrials($query)
    {
        return $query->where('status', 'trial')
            ->where('trial_ends_at', '<', now());
    }
    
    /**
     * Scope to get subscriptions ending soon.
     */
    public function scopeEndingSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
            ->where('current_period_end', '<=', now()->addDays($days));
    }
    
    /**
     * Get the subscription's revenue.
     */
    public function getTotalRevenueAttribute(): float
    {
        // Calculate total revenue based on history
        $payments = $this->stateHistory()
            ->where('to_state', 'active')
            ->count();
        
        return $this->amount * $payments;
    }
    
    /**
     * Get the subscription's lifetime in days.
     */
    public function getLifetimeInDaysAttribute(): int
    {
        $start = $this->created_at;
        $end = $this->cancelled_at ?? now();
        
        return $start->diffInDays($end);
    }
    
    /**
     * Check if the subscription can be reactivated.
     */
    public function canBeReactivated(): bool
    {
        return $this->isSuspended() && $this->reactivation_attempts < 3;
    }
    
    /**
     * Get the next billing date.
     */
    public function getNextBillingDateAttribute(): ?string
    {
        if (!$this->isActive() || !$this->current_period_end) {
            return null;
        }
        
        return $this->current_period_end->addMonth()->toDateString();
    }
    
    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($subscription) {
            // Set default currency if not provided
            if (!$subscription->currency) {
                $subscription->currency = 'USD';
            }
            
            // Set trial end date if not provided
            if (!$subscription->trial_ends_at && $subscription->status === 'trial') {
                $subscription->trial_ends_at = now()->addDays(14);
            }
        });
    }
}