<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | State Machines Path
    |--------------------------------------------------------------------------
    |
    | The path where your state machine YAML definitions are stored.
    | This can be an absolute path or relative to your Laravel base path.
    |
    */
    'state_machines_path' => database_path('state_machines'),

    /*
    |--------------------------------------------------------------------------
    | Default State Field
    |--------------------------------------------------------------------------
    |
    | The default field name to use for storing state on your models.
    | This can be overridden in individual state machine definitions.
    |
    */
    'default_state_field' => 'state',

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable state machine events.
    |
    */
    'events' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | History Tracking
    |--------------------------------------------------------------------------
    |
    | Enable automatic tracking of state transitions.
    |
    */
    'history' => [
        'enabled' => false,
        'table' => 'state_machine_history',
    ],

    /*
    |--------------------------------------------------------------------------
    | Generated Code Path
    |--------------------------------------------------------------------------
    |
    | The path where generated PHP classes (guards, actions, models)
    | are created by the statecraft:generate command.
    |
    */
    'generated_code_path' => app_path('StateMachines'),
];
