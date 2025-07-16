<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
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
