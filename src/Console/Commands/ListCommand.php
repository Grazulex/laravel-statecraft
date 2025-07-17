<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Console\Commands;

use Exception;
use Grazulex\LaravelStatecraft\Support\YamlStateMachineLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ListCommand extends Command
{
    protected $signature = 'statecraft:list {--path= : Custom path to search for YAML files}';

    protected $description = 'List all YAML state machine definitions';

    public function handle(): void
    {
        $path = $this->option('path') ?? config('statecraft.definitions_path', resource_path('statemachines'));

        if (! File::isDirectory($path)) {
            $this->error("Directory not found: {$path}");

            return;
        }

        $yamlFiles = File::glob($path.'/*.yaml');

        if (empty($yamlFiles)) {
            $this->warn("No YAML files found in {$path}");

            return;
        }

        $this->info("State Machine Definitions in {$path}:");
        $this->newLine();

        $loader = new YamlStateMachineLoader($path);
        $tableData = [];

        foreach ($yamlFiles as $file) {
            $filename = basename($file, '.yaml');

            try {
                $definition = $loader->load($filename);
                $tableData[] = [
                    'File' => $filename.'.yaml',
                    'Name' => $definition->getName(),
                    'Model' => $definition->getModel(),
                    'States' => count($definition->getStates()),
                    'Transitions' => count($definition->getTransitions()),
                    'Initial' => $definition->getInitial(),
                    'Field' => $definition->getField(),
                ];
            } catch (Exception $e) {
                $tableData[] = [
                    'File' => $filename.'.yaml',
                    'Name' => '<error>',
                    'Model' => '<error>',
                    'States' => '<error>',
                    'Transitions' => '<error>',
                    'Initial' => '<error>',
                    'Field' => '<error>',
                ];
            }
        }

        $this->table(
            ['File', 'Name', 'Model', 'States', 'Transitions', 'Initial', 'Field'],
            $tableData
        );

        $this->newLine();
        $this->info('Found '.count($yamlFiles).' definition(s)');
    }
}
