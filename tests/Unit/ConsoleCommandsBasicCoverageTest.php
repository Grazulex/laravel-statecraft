<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Console\Commands\ExportCommand;
use Grazulex\LaravelStatecraft\Console\Commands\ListCommand;
use Grazulex\LaravelStatecraft\Console\Commands\ShowCommand;
use Grazulex\LaravelStatecraft\Console\Commands\ValidateCommand;

describe('Console Commands Basic Coverage', function () {
    test('ListCommand basic instantiation and signature', function () {
        $command = new ListCommand();
        expect($command->getName())->toBe('statecraft:list');
        expect($command->getDescription())->toBe('List all YAML state machine definitions');
        expect($command)->toBeInstanceOf(ListCommand::class);
    });

    test('ShowCommand basic instantiation and signature', function () {
        $command = new ShowCommand();
        expect($command->getName())->toBe('statecraft:show');
        expect($command->getDescription())->toBe('Show the content of a YAML state machine definition');
        expect($command)->toBeInstanceOf(ShowCommand::class);
    });

    test('ExportCommand basic instantiation and signature', function () {
        $command = new ExportCommand();
        expect($command->getName())->toBe('statecraft:export');
        expect($command->getDescription())->toBe('Export a YAML state machine definition to different formats');
        expect($command)->toBeInstanceOf(ExportCommand::class);
    });

    test('ValidateCommand basic instantiation and signature', function () {
        $command = new ValidateCommand();
        expect($command->getName())->toBe('statecraft:validate');
        expect($command->getDescription())->toBe('Validate YAML state machine definitions');
        expect($command)->toBeInstanceOf(ValidateCommand::class);
    });

    test('Commands can be instantiated without errors', function () {
        expect(new ListCommand())->toBeInstanceOf(ListCommand::class);
        expect(new ShowCommand())->toBeInstanceOf(ShowCommand::class);
        expect(new ExportCommand())->toBeInstanceOf(ExportCommand::class);
        expect(new ValidateCommand())->toBeInstanceOf(ValidateCommand::class);
    });

    test('Commands have proper parent class', function () {
        expect(new ListCommand())->toBeInstanceOf(Illuminate\Console\Command::class);
        expect(new ShowCommand())->toBeInstanceOf(Illuminate\Console\Command::class);
        expect(new ExportCommand())->toBeInstanceOf(Illuminate\Console\Command::class);
        expect(new ValidateCommand())->toBeInstanceOf(Illuminate\Console\Command::class);
    });
});
