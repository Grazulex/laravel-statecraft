<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Support;

use Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition;
use Grazulex\LaravelStatecraft\Exceptions\InvalidStateMachineDefinitionException;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

final class YamlStateMachineLoader
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? config('statecraft.state_machines_path', database_path('state_machines'));
    }

    public function load(string $filename): StateMachineDefinition
    {
        $filepath = $this->getFilePath($filename);

        if (! File::exists($filepath)) {
            throw new InvalidStateMachineDefinitionException("State machine file not found: {$filepath}");
        }

        $content = File::get($filepath);
        $data = Yaml::parse($content);

        return $this->parseDefinition($data, $filename);
    }

    public function loadAll(): array
    {
        if (! File::isDirectory($this->basePath)) {
            return [];
        }

        $definitions = [];
        $files = File::glob($this->basePath.'/*.yaml');

        foreach ($files as $file) {
            $filename = basename($file, '.yaml');
            $definitions[$filename] = $this->load($filename);
        }

        return $definitions;
    }

    private function parseDefinition(array $data, string $filename): StateMachineDefinition
    {
        $this->validateStructure($data, $filename);

        $stateMachine = $data['state_machine'];

        return new StateMachineDefinition(
            name: $stateMachine['name'],
            model: $stateMachine['model'],
            states: $stateMachine['states'],
            initial: $stateMachine['initial'],
            transitions: $stateMachine['transitions'] ?? [],
            field: $stateMachine['field'] ?? 'state'
        );
    }

    private function validateStructure(array $data, string $filename): void
    {
        if (! isset($data['state_machine'])) {
            throw new InvalidStateMachineDefinitionException("Missing 'state_machine' root key in {$filename}");
        }

        $stateMachine = $data['state_machine'];
        $required = ['name', 'model', 'states', 'initial'];

        foreach ($required as $key) {
            if (! isset($stateMachine[$key])) {
                throw new InvalidStateMachineDefinitionException("Missing required key '{$key}' in {$filename}");
            }
        }

        if (! is_array($stateMachine['states'])) {
            throw new InvalidStateMachineDefinitionException("'states' must be an array in {$filename}");
        }

        if (! in_array($stateMachine['initial'], $stateMachine['states'])) {
            throw new InvalidStateMachineDefinitionException("Initial state '{$stateMachine['initial']}' not found in states list in {$filename}");
        }
    }

    private function getFilePath(string $filename): string
    {
        if (str_ends_with($filename, '.yaml')) {
            return $this->basePath.'/'.$filename;
        }

        return $this->basePath.'/'.$filename.'.yaml';
    }
}
