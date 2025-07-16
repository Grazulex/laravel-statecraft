<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeStateMachineCommand extends Command
{
    protected $signature = 'statecraft:make {name : The name of the state machine}
                          {--model= : The model class name}
                          {--states= : Comma-separated list of states}
                          {--initial= : The initial state}';

    protected $description = 'Create a new state machine YAML definition';

    public function handle(): void
    {
        $name = $this->argument('name');
        $model = $this->option('model') ?? 'App\\Models\\'.Str::studly(Str::singular($name));
        $states = $this->option('states') ? explode(',', $this->option('states')) : ['draft', 'published'];
        $initial = $this->option('initial') ?? $states[0];
        $field = config('statecraft.default_state_field', 'state');

        $filename = Str::snake($name).'.yaml';
        $path = config('statecraft.state_machines_path', database_path('state_machines'));

        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $filePath = $path.'/'.$filename;

        if (File::exists($filePath)) {
            $this->error("State machine {$filename} already exists!");

            return;
        }

        $content = $this->generateYamlFromStub($name, $model, $states, $initial, $field);

        File::put($filePath, $content);

        $this->info("State machine created successfully at {$filePath}");
        $this->line("Don't forget to:");
        $this->line("• Add the HasStateMachine trait to your {$model} model");
        $this->line('• Run migrations if needed');
        $this->line('• Implement guards and actions if specified');
    }

    private function generateYamlFromStub(string $name, string $model, array $states, string $initial, string $field): string
    {
        $stub = File::get(__DIR__.'/stubs/state-machine.yaml.stub');

        $statesStr = '['.implode(', ', $states).']';

        // Generate basic transitions
        $transitions = '';
        for ($i = 0; $i < count($states) - 1; $i++) {
            $from = $states[$i];
            $to = $states[$i + 1];
            $transitions .= "    - from: {$from}\n";
            $transitions .= "      to: {$to}\n";
            $transitions .= "      # guard: YourGuardClass\n";
            $transitions .= "      # action: YourActionClass\n";
        }

        $fieldLine = $field !== 'state' ? "\n  field: {$field}" : '';

        return str_replace(
            ['{{ $name }}', '{{ $model }}', '{{ $states }}', '{{ $initial }}', '{{ $field }}', '{{ $transitions }}'],
            [$name, $model, $statesStr, $initial, $fieldLine, $transitions],
            $stub
        );
    }
}
