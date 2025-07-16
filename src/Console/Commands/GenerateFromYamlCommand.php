<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Console\Commands;

use Exception;
use Grazulex\LaravelStatecraft\Support\YamlStateMachineLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateFromYamlCommand extends Command
{
    protected $signature = 'state:generate-from-yaml {file : The YAML file path}
                          {--output= : Output directory for generated files}';

    protected $description = 'Generate PHP classes from a YAML state machine definition';

    public function handle(): void
    {
        $file = $this->argument('file');
        $outputDir = $this->option('output') ?? app_path('StateMachines');

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
        $this->generateTraitExample($definition, $outputDir);

        $this->info("Files generated successfully in {$outputDir}");
    }

    private function generateGuards($definition, string $outputDir): void
    {
        $guards = collect($definition->getTransitions())
            ->pluck('guard')
            ->filter()
            ->unique();

        foreach ($guards as $guard) {
            $className = class_basename($guard);
            $namespace = Str::beforeLast($guard, '\\');

            $content = $this->generateGuardClass($className, $namespace);

            $guardDir = $outputDir.'/Guards';
            if (! File::isDirectory($guardDir)) {
                File::makeDirectory($guardDir, 0755, true);
            }

            File::put($guardDir.'/'.$className.'.php', $content);
            $this->line("Generated guard: {$className}");
        }
    }

    private function generateActions($definition, string $outputDir): void
    {
        $actions = collect($definition->getTransitions())
            ->pluck('action')
            ->filter()
            ->unique();

        foreach ($actions as $action) {
            $className = class_basename($action);
            $namespace = Str::beforeLast($action, '\\');

            $content = $this->generateActionClass($className, $namespace);

            $actionDir = $outputDir.'/Actions';
            if (! File::isDirectory($actionDir)) {
                File::makeDirectory($actionDir, 0755, true);
            }

            File::put($actionDir.'/'.$className.'.php', $content);
            $this->line("Generated action: {$className}");
        }
    }

    private function generateTraitExample($definition, string $outputDir): void
    {
        $modelName = class_basename($definition->getModel());
        $content = $this->generateTraitUsageExample($modelName, $definition);

        File::put($outputDir.'/'.$modelName.'Example.php', $content);
        $this->line("Generated usage example: {$modelName}Example");
    }

    private function generateGuardClass(string $className, string $namespace): string
    {
        return "<?php

declare(strict_types=1);

namespace {$namespace};

use Grazulex\LaravelStatecraft\Contracts\Guard;
use Illuminate\Database\Eloquent\Model;

class {$className} implements Guard
{
    public function check(Model \$model, string \$from, string \$to): bool
    {
        // TODO: Implement your guard logic here
        return true;
    }
}
";
    }

    private function generateActionClass(string $className, string $namespace): string
    {
        return "<?php

declare(strict_types=1);

namespace {$namespace};

use Grazulex\LaravelStatecraft\Contracts\Action;
use Illuminate\Database\Eloquent\Model;

class {$className} implements Action
{
    public function execute(Model \$model, string \$from, string \$to): void
    {
        // TODO: Implement your action logic here
    }
}
";
    }

    private function generateTraitUsageExample(string $modelName, $definition): string
    {
        $namespace = Str::beforeLast($definition->getModel(), '\\');
        $states = implode("', '", $definition->getStates());

        return "<?php

declare(strict_types=1);

namespace {$namespace};

use Grazulex\LaravelStatecraft\Traits\HasStateMachine;
use Grazulex\LaravelStatecraft\Traits\HasStateHistory;
use Illuminate\Database\Eloquent\Model;

class {$modelName} extends Model
{
    use HasStateMachine, HasStateHistory;

    protected \$fillable = [
        'name',
        '{$definition->getField()}',
        // Add other fillable fields
    ];

    // Available states: '{$states}'
    // Available methods will be auto-generated based on your YAML definition
    
    /**
     * Override the state machine definition name if needed.
     */
    protected function getStateMachineDefinitionName(): string
    {
        return '{$definition->getName()}';
    }
}
";
    }
}
