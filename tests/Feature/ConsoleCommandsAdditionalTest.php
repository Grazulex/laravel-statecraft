<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Console\Commands\GenerateCommand;
use Grazulex\LaravelStatecraft\Console\Commands\MakeStateMachineCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

it('commands are properly registered', function () {
    $commands = [
        'statecraft:make' => MakeStateMachineCommand::class,
        'statecraft:generate' => GenerateCommand::class,
    ];

    foreach ($commands as $signature => $class) {
        $command = app()->make($class);
        expect($command)->toBeInstanceOf($class);
        expect($command->getName())->toBe($signature);
    }
});

it('MakeStateMachineCommand creates directory if it does not exist', function () {
    $tempDir = sys_get_temp_dir().'/new_state_machines_'.time();
    config(['statecraft.state_machines_path' => $tempDir]);

    // Directory should not exist initially
    expect(File::isDirectory($tempDir))->toBeFalse();

    $this->artisan('statecraft:make', [
        'name' => 'TestWorkflow',
        '--model' => 'App\\Models\\Test',
        '--states' => 'draft,published',
        '--initial' => 'draft',
    ])->assertExitCode(0);

    // Directory should be created
    expect(File::isDirectory($tempDir))->toBeTrue();

    // File should be created
    expect(File::exists($tempDir.'/test_workflow.yaml'))->toBeTrue();
});

it('MakeStateMachineCommand with default options', function () {
    $tempDir = sys_get_temp_dir().'/default_options_'.time();
    config(['statecraft.state_machines_path' => $tempDir]);

    $this->artisan('statecraft:make', [
        'name' => 'TestWorkflow',
    ])->assertExitCode(0);

    $content = File::get($tempDir.'/test_workflow.yaml');
    expect($content)->toContain('name: TestWorkflow');
    expect($content)->toContain('model: App\\Models\\TestWorkflow');
    expect($content)->toContain('states: [draft, published]');
    expect($content)->toContain('initial: draft');
    expect($content)->toContain('from: draft');
    expect($content)->toContain('to: published');
});

it('MakeStateMachineCommand with custom model', function () {
    $tempDir = sys_get_temp_dir().'/custom_model_'.time();
    config(['statecraft.state_machines_path' => $tempDir]);

    $this->artisan('statecraft:make', [
        'name' => 'OrderWorkflow',
        '--model' => 'App\\Models\\Order',
    ])->assertExitCode(0);

    $content = File::get($tempDir.'/order_workflow.yaml');
    expect($content)->toContain('model: App\\Models\\Order');
});

it('MakeStateMachineCommand with custom states', function () {
    $tempDir = sys_get_temp_dir().'/custom_states_'.time();
    config(['statecraft.state_machines_path' => $tempDir]);

    $this->artisan('statecraft:make', [
        'name' => 'OrderWorkflow',
        '--states' => 'pending,processing,completed,cancelled',
        '--initial' => 'pending',
    ])->assertExitCode(0);

    $content = File::get($tempDir.'/order_workflow.yaml');
    expect($content)->toContain('states: [pending, processing, completed, cancelled]');
    expect($content)->toContain('initial: pending');
    expect($content)->toContain('from: pending');
    expect($content)->toContain('to: processing');
    expect($content)->toContain('from: processing');
    expect($content)->toContain('to: completed');
    expect($content)->toContain('from: completed');
    expect($content)->toContain('to: cancelled');
});

it('MakeStateMachineCommand shows error when file already exists', function () {
    $tempDir = sys_get_temp_dir().'/existing_file_'.time();
    config(['statecraft.state_machines_path' => $tempDir]);

    File::makeDirectory($tempDir, 0755, true);

    // Create existing file
    File::put($tempDir.'/test_workflow.yaml', 'existing content');

    $this->artisan('statecraft:make', [
        'name' => 'TestWorkflow',
    ])->expectsOutput('State machine test_workflow.yaml already exists!');
});

it('GenerateCommand exists and has correct signature', function () {
    $command = new GenerateCommand();

    expect($command)->toBeInstanceOf(GenerateCommand::class);
    expect($command->getName())->toBe('statecraft:generate');
});

// Test for GenerateFromYamlCommand removed as that command has been deprecated
