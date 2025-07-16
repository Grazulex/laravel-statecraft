<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Examples;

use Grazulex\LaravelStatecraft\Traits\HasStateHistory;
use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Illuminate\Database\Eloquent\Model;

/**
 * Example model showing how to use Laravel Statecraft traits.
 * This class exists to demonstrate usage and satisfy static analysis.
 */
class ExampleModel extends Model
{
    use HasStateHistory, HasStateMachine;

    protected $fillable = ['name', 'state'];

    protected function getStateMachineDefinitionName(): string
    {
        return 'example-workflow';
    }
}
