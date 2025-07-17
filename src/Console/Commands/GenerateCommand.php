<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Console\Commands;

use Exception;
use Grazulex\LaravelStatecraft\Support\YamlStateMachineLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateCommand extends Command
{
    protected $signature = 'statecraft:generate {file : The YAML file path}';

    protected $description = 'Generate PHP classes from a YAML state machine definition';

    public function handle(): void
    {
        $file = $this->argument('file');
        $outputDir = config('statecraft.generated_code_path', app_path('StateMachines'));

        if (! File::exists($file)) {
            $this->error("YAML file not found: {$file}");

            return;
        }

        try {
            $loader = new YamlStateMachineLoader(dirname($file));
            $filename = basename($file, '.yaml');
            $definition = $loader->load($filename);
        } catch (Exception $e) {
            $this->error("Error loading YAML file: {$e->getMessage()}");

            return;
        }

        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $this->generateGuards($definition, $outputDir);
        $this->generateActions($definition, $outputDir);
        $this->generateModelExample($definition, $outputDir);

        $this->info("Files generated successfully in {$outputDir}");
    }

    private function generateGuards(\Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition $definition, string $outputDir): void
    {
        $guards = collect($definition->getTransitions())
            ->map(function ($transition): string|array|null {
                $guard = $transition['guard'] ?? null;
                if (is_string($guard)) {
                    return $guard;
                }
                if (is_array($guard)) {
                    return $this->extractGuardsFromExpression($guard);
                }

                return null;
            })
            ->flatten()
            ->filter()
            ->unique();

        foreach ($guards as $guard) {
            $className = class_basename($guard);
            $namespace = Str::beforeLast($guard, '\\');

            $content = $this->generateFromStub('guard', [
                'className' => $className,
                'namespace' => $namespace,
            ]);

            $guardDir = $outputDir.'/Guards';
            if (! File::isDirectory($guardDir)) {
                File::makeDirectory($guardDir, 0755, true);
            }

            File::put($guardDir.'/'.$className.'.php', $content);
            $this->line("Generated guard: {$className}");
        }
    }

    private function generateActions(\Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition $definition, string $outputDir): void
    {
        $actions = collect($definition->getTransitions())
            ->map(function ($transition) {
                return $transition['action'] ?? null;
            })
            ->filter()
            ->unique();

        foreach ($actions as $action) {
            $className = class_basename($action);
            $namespace = Str::beforeLast($action, '\\');

            $content = $this->generateFromStub('action', [
                'className' => $className,
                'namespace' => $namespace,
            ]);

            $actionDir = $outputDir.'/Actions';
            if (! File::isDirectory($actionDir)) {
                File::makeDirectory($actionDir, 0755, true);
            }

            File::put($actionDir.'/'.$className.'.php', $content);
            $this->line("Generated action: {$className}");
        }
    }

    private function generateModelExample(\Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition $definition, string $outputDir): void
    {
        $modelName = class_basename($definition->getModel());
        $namespace = Str::beforeLast($definition->getModel(), '\\');

        $content = $this->generateFromStub('model', [
            'className' => $modelName,
            'namespace' => $namespace,
            'field' => $definition->getField(),
            'states' => implode("', '", $definition->getStates()),
            'stateMachineName' => $definition->getName(),
        ]);

        File::put($outputDir.'/'.$modelName.'Example.php', $content);
        $this->line("Generated model example: {$modelName}Example");
    }

    private function generateFromStub(string $stubName, array $replacements): string
    {
        $stub = File::get(__DIR__."/stubs/{$stubName}.php.stub");

        foreach ($replacements as $key => $value) {
            $stub = str_replace("{{ \${$key} }}", $value, $stub);
        }

        return $stub;
    }

    private function extractGuardsFromExpression(array $expression): array
    {
        $guards = [];

        foreach ($expression as $key => $value) {
            if (in_array($key, ['and', 'or', 'not'])) {
                if (is_array($value)) {
                    foreach ($value as $item) {
                        if (is_string($item)) {
                            $guards[] = $item;
                        } elseif (is_array($item)) {
                            $guards = array_merge($guards, $this->extractGuardsFromExpression($item));
                        }
                    }
                } elseif (is_string($value)) {
                    $guards[] = $value;
                }
            }
        }

        return $guards;
    }
}
