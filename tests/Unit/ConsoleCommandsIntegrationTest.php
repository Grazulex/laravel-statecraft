<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Console\Commands\ExportCommand;
use Grazulex\LaravelStatecraft\Console\Commands\ListCommand;
use Grazulex\LaravelStatecraft\Console\Commands\ShowCommand;
use Grazulex\LaravelStatecraft\Console\Commands\ValidateCommand;
use Illuminate\Support\Facades\File;

describe('Console Commands Integration', function () {

    beforeEach(function () {
        // Create temporary directory for tests
        $this->tempDir = sys_get_temp_dir().'/statecraft_commands_test_'.uniqid();
        File::makeDirectory($this->tempDir);

        // Create test YAML file
        $yamlContent = <<<'YAML'
state_machine:
  name: TestWorkflow
  model: Tests\Fixtures\Order
  states:
    - draft
    - pending
    - approved
  initial: draft
  transitions:
    - from: draft
      to: pending
      guard: CanSubmit
    - from: pending
      to: approved
      guard: IsManager
      action: SendEmail
YAML;

        File::put($this->tempDir.'/test.yaml', $yamlContent);
    });

    afterEach(function () {
        // Clean up temporary directory
        if (File::isDirectory($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }
    });

    test('ListCommand can be instantiated', function () {
        $command = new ListCommand();
        expect($command)->toBeInstanceOf(ListCommand::class);
    });

    test('ShowCommand can be instantiated', function () {
        $command = new ShowCommand();
        expect($command)->toBeInstanceOf(ShowCommand::class);
    });

    test('ExportCommand can be instantiated', function () {
        $command = new ExportCommand();
        expect($command)->toBeInstanceOf(ExportCommand::class);
    });

    test('ValidateCommand can be instantiated', function () {
        $command = new ValidateCommand();
        expect($command)->toBeInstanceOf(ValidateCommand::class);
    });

    test('Commands are registered in service provider', function () {
        $application = $this->app->make(Illuminate\Contracts\Console\Kernel::class);
        $registeredCommands = $application->all();

        expect($registeredCommands)->toHaveKey('statecraft:list');
        expect($registeredCommands)->toHaveKey('statecraft:show');
        expect($registeredCommands)->toHaveKey('statecraft:export');
        expect($registeredCommands)->toHaveKey('statecraft:validate');
        expect($registeredCommands)->toHaveKey('statecraft:make');
        expect($registeredCommands)->toHaveKey('statecraft:generate');
    });

    test('Commands have correct signatures', function () {
        $listCommand = new ListCommand();
        $showCommand = new ShowCommand();
        $exportCommand = new ExportCommand();
        $validateCommand = new ValidateCommand();

        expect($listCommand->getName())->toBe('statecraft:list');
        expect($showCommand->getName())->toBe('statecraft:show');
        expect($exportCommand->getName())->toBe('statecraft:export');
        expect($validateCommand->getName())->toBe('statecraft:validate');
    });

    test('Commands have correct descriptions', function () {
        $listCommand = new ListCommand();
        $showCommand = new ShowCommand();
        $exportCommand = new ExportCommand();
        $validateCommand = new ValidateCommand();

        expect($listCommand->getDescription())->toBe('List all YAML state machine definitions');
        expect($showCommand->getDescription())->toBe('Show the content of a YAML state machine definition');
        expect($exportCommand->getDescription())->toBe('Export a YAML state machine definition to different formats');
        expect($validateCommand->getDescription())->toBe('Validate YAML state machine definitions');
    });
});
