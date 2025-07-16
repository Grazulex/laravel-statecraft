<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Grazulex\LaravelStatecraft\Traits\HasStateHistory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasStateMachine, HasStateHistory;

    public $timestamps = false;

    protected $fillable = ['name', 'state'];

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
