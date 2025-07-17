<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Console\Commands;

use Exception;
use Grazulex\LaravelStatecraft\Support\YamlStateMachineLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ValidateCommand extends Command
{
    protected $signature = 'statecraft:validate {file? : The YAML file name (without extension)} {--path= : Custom path to search for YAML files} {--all : Validate all files in the directory}';

    protected $description = 'Validate YAML state machine definitions';

    public function handle(): void
    {
        $filename = $this->argument('file');
        $path = $this->option('path') ?? config('statecraft.definitions_path', resource_path('statemachines'));
        $validateAll = $this->option('all');

        if (! $filename && ! $validateAll) {
            $this->error('Please specify a file name or use --all to validate all files');

            return;
        }

        if (! File::isDirectory($path)) {
            $this->error("Directory not found: {$path}");

            return;
        }

        if ($validateAll) {
            $this->validateAllFiles($path);
        } else {
            $this->validateSingleFile($filename, $path);
        }
    }

    private function validateSingleFile(string $filename, string $path): void
    {
        // Add .yaml extension if not present
        if (! Str::endsWith($filename, '.yaml')) {
            $filename .= '.yaml';
        }

        $filePath = $path.'/'.$filename;

        if (! File::exists($filePath)) {
            $this->error("YAML file not found: {$filePath}");

            return;
        }

        $this->info("Validating: {$filename}");
        $this->newLine();

        $errors = $this->validateFile($filePath, basename($filename, '.yaml'), $path);

        if ($errors === []) {
            $this->info("✓ {$filename} is valid");
        } else {
            $this->error("✗ {$filename} has validation errors:");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }
    }

    private function validateAllFiles(string $path): void
    {
        $yamlFiles = File::glob($path.'/*.yaml');

        if (empty($yamlFiles)) {
            $this->warn("No YAML files found in {$path}");

            return;
        }

        $this->info('Validating '.count($yamlFiles)." file(s) in {$path}");
        $this->newLine();

        $validCount = 0;
        $invalidCount = 0;
        $allErrors = [];

        foreach ($yamlFiles as $filePath) {
            $filename = basename($filePath);
            $fileBasename = basename($filePath, '.yaml');

            $errors = $this->validateFile($filePath, $fileBasename, $path);

            if ($errors === []) {
                $this->info("✓ {$filename}");
                $validCount++;
            } else {
                $this->error("✗ {$filename}");
                foreach ($errors as $error) {
                    $this->line("  - {$error}");
                }
                $invalidCount++;
                $allErrors[$filename] = $errors;
            }
        }

        $this->newLine();
        $this->info('Validation Summary:');
        $this->info("  Valid: {$validCount}");
        $this->info("  Invalid: {$invalidCount}");
        $this->info('  Total: '.count($yamlFiles));

        if ($invalidCount > 0) {
            $this->newLine();
            $this->error("Validation failed for {$invalidCount} file(s)");
        }
    }

    private function validateFile(string $filePath, string $fileBasename, string $path): array
    {
        $errors = [];

        try {
            // Check if file is readable
            if (! File::isReadable($filePath)) {
                $errors[] = 'File is not readable';

                return $errors;
            }

            // Check if file is not empty
            if (File::size($filePath) === 0) {
                $errors[] = 'File is empty';

                return $errors;
            }

            // Try to load and parse the YAML
            $loader = new YamlStateMachineLoader($path);
            $definition = $loader->load($fileBasename);

            // Validate definition structure
            $errors = array_merge($errors, $this->validateDefinitionStructure($definition));

            // Validate business logic
            $errors = array_merge($errors, $this->validateBusinessLogic($definition));

            // Validate references
            $errors = array_merge($errors, $this->validateReferences($definition));

        } catch (Exception $e) {
            $errors[] = "Failed to load: {$e->getMessage()}";
        }

        return $errors;
    }

    private function validateDefinitionStructure(\Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition $definition): array
    {
        $errors = [];

        // Check if name is not empty
        if (in_array($definition->getName(), ['', '0'], true)) {
            $errors[] = 'Name is required';
        }

        // Check if model is not empty
        if (in_array($definition->getModel(), ['', '0'], true)) {
            $errors[] = 'Model is required';
        }

        // Check if states array is not empty
        if ($definition->getStates() === []) {
            $errors[] = 'At least one state is required';
        }

        // Check if initial state is not empty
        if (in_array($definition->getInitial(), ['', '0'], true)) {
            $errors[] = 'Initial state is required';
        }

        // Check if field is not empty
        if (in_array($definition->getField(), ['', '0'], true)) {
            $errors[] = 'Field is required';
        }

        return $errors;
    }

    private function validateBusinessLogic(\Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition $definition): array
    {
        $errors = [];
        $states = $definition->getStates();
        $initial = $definition->getInitial();

        // Check if initial state exists in states array
        if (! in_array($initial, $states)) {
            $errors[] = "Initial state '{$initial}' is not defined in states";
        }

        // Check transitions
        foreach ($definition->getTransitions() as $index => $transition) {
            $from = $transition['from'] ?? null;
            $to = $transition['to'] ?? null;

            if (! $from) {
                $errors[] = "Transition #{$index}: 'from' is required";

                continue;
            }

            if (! $to) {
                $errors[] = "Transition #{$index}: 'to' is required";

                continue;
            }

            // Check if from state exists
            if (! in_array($from, $states)) {
                $errors[] = "Transition #{$index}: 'from' state '{$from}' is not defined";
            }

            // Check if to state exists
            if (! in_array($to, $states)) {
                $errors[] = "Transition #{$index}: 'to' state '{$to}' is not defined";
            }

            // Check for self-transitions (optional warning)
            if ($from === $to) {
                // This is not necessarily an error, but could be flagged as a warning
                // $errors[] = "Transition #{$index}: Self-transition from '{$from}' to '{$to}'";
            }
        }

        // Check for unreachable states
        $reachableStates = [$initial];
        foreach ($definition->getTransitions() as $transition) {
            if (isset($transition['to']) && ! in_array($transition['to'], $reachableStates)) {
                $reachableStates[] = $transition['to'];
            }
        }

        foreach ($states as $state) {
            if (! in_array($state, $reachableStates)) {
                $errors[] = "State '{$state}' is not reachable from initial state";
            }
        }

        return $errors;
    }

    private function validateReferences(\Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition $definition): array
    {
        $errors = [];

        // Check if model class exists (basic string validation)
        $modelClass = $definition->getModel();
        if (! class_exists($modelClass)) {
            $errors[] = "Model class '{$modelClass}' does not exist";
        }

        // Validate guards and actions (check if they look like valid class names)
        foreach ($definition->getTransitions() as $index => $transition) {
            if (isset($transition['guard'])) {
                $guard = $transition['guard'];
                if (is_string($guard) && ! $this->isValidClassName($guard)) {
                    $errors[] = "Transition #{$index}: Guard '{$guard}' does not look like a valid class name";
                }
            }

            if (isset($transition['action'])) {
                $action = $transition['action'];
                if (! $this->isValidClassName($action)) {
                    $errors[] = "Transition #{$index}: Action '{$action}' does not look like a valid class name";
                }
            }
        }

        return $errors;
    }

    private function isValidClassName(string $className): bool
    {
        // Basic validation for class name format
        return preg_match('/^[A-Za-z_][A-Za-z0-9_\\\\]*$/', $className) === 1;
    }
}
