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
        $path = $this->option('path') ?? config('statecraft.state_machines_path', database_path('state_machines'));
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

            // Validate final states, sink states and paths to a final state
            $errors = array_merge($errors, $this->validateTerminalStates($definition));

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

        // Validate guards and actions: class name format, then class existence
        foreach ($definition->getTransitions() as $index => $transition) {
            if (isset($transition['guard'])) {
                foreach ($this->extractGuardClasses($transition['guard']) as $guard) {
                    if (! $this->isValidClassName($guard)) {
                        $errors[] = "Transition #{$index}: Guard '{$guard}' does not look like a valid class name";
                    } elseif (! $this->classIsResolvable($guard, 'App\\StateMachines\\Guards\\')) {
                        $errors[] = "Transition #{$index}: Guard class '{$guard}' does not exist";
                    }
                }
            }

            if (isset($transition['action'])) {
                $action = $transition['action'];
                if (! is_string($action) || ! $this->isValidClassName($action)) {
                    $label = is_string($action) ? $action : (string) json_encode($action);
                    $errors[] = "Transition #{$index}: Action '{$label}' does not look like a valid class name";
                } elseif (! $this->classIsResolvable($action, 'App\\StateMachines\\Actions\\')) {
                    $errors[] = "Transition #{$index}: Action class '{$action}' does not exist";
                }
            }
        }

        return $errors;
    }

    /**
     * Validate final states, flag sink states and states with no path to a final state.
     *
     * Final states are the ones listed under the optional `final` key. When that key
     * is absent, every state without outgoing transitions is considered final.
     */
    private function validateTerminalStates(\Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition $definition): array
    {
        $errors = [];
        $states = array_map(strval(...), $definition->getStates());
        $declaredFinals = array_map(strval(...), $definition->getFinalStates());

        foreach ($declaredFinals as $final) {
            if (! in_array($final, $states, true)) {
                $errors[] = "Final state '{$final}' is not defined in states";
            }
        }

        // Build outgoing and incoming adjacency lists
        $outgoing = array_fill_keys($states, []);
        $incoming = array_fill_keys($states, []);
        foreach ($definition->getTransitions() as $transition) {
            $from = (string) $transition['from'];
            $to = (string) $transition['to'];
            if (! isset($outgoing[$from], $incoming[$to])) {
                continue;
            }
            $outgoing[$from][] = $to;
            $incoming[$to][] = $from;
        }

        if ($declaredFinals !== []) {
            $finals = array_values(array_intersect($states, $declaredFinals));
        } else {
            $finals = array_values(array_filter($states, fn (string $state): bool => $outgoing[$state] === []));
        }

        // Sink states: no outgoing transitions while not declared as final
        $sinks = [];
        if ($declaredFinals !== []) {
            foreach ($states as $state) {
                if ($outgoing[$state] === [] && ! in_array($state, $finals, true)) {
                    $sinks[] = $state;
                    $errors[] = "State '{$state}' is a sink state: it has no outgoing transitions and is not declared as final";
                }
            }
        }

        // A machine without any final state is purely cyclic by design: skip the path check
        if ($finals === []) {
            return $errors;
        }

        // Walk transitions backwards from the final states
        $canReachFinal = array_fill_keys($finals, true);
        $queue = $finals;
        while ($queue !== []) {
            $current = array_shift($queue);
            foreach ($incoming[$current] as $previous) {
                if (! isset($canReachFinal[$previous])) {
                    $canReachFinal[$previous] = true;
                    $queue[] = $previous;
                }
            }
        }

        foreach ($states as $state) {
            if (! isset($canReachFinal[$state]) && ! in_array($state, $sinks, true)) {
                $errors[] = "State '{$state}' has no path to a final state";
            }
        }

        return $errors;
    }

    /**
     * Extract every guard class name from a guard definition (string or and/or/not expression).
     *
     * @return array<int, string>
     */
    private function extractGuardClasses(mixed $guard): array
    {
        if (is_string($guard)) {
            return [$guard];
        }

        if (! is_array($guard)) {
            return [];
        }

        $classes = [];
        foreach ($guard as $value) {
            $classes = array_merge($classes, $this->extractGuardClasses($value));
        }

        return $classes;
    }

    /**
     * Check that a class exists or is bound in the container, falling back to the
     * default namespace for short class names (same resolution as at runtime).
     */
    private function classIsResolvable(string $className, string $defaultNamespace): bool
    {
        if (class_exists($className) || app()->bound($className)) {
            return true;
        }

        if (! str_contains($className, '\\')) {
            $fallback = $defaultNamespace.$className;

            return class_exists($fallback) || app()->bound($fallback);
        }

        return false;
    }

    private function isValidClassName(string $className): bool
    {
        // Basic validation for class name format
        return preg_match('/^[A-Za-z_][A-Za-z0-9_\\\\]*$/', $className) === 1;
    }
}
