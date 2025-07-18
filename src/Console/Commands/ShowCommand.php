<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Console\Commands;

use Exception;
use Grazulex\LaravelStatecraft\Support\YamlStateMachineLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ShowCommand extends Command
{
    protected $signature = 'statecraft:show {file : The YAML file name (without extension)} {--path= : Custom path to search for YAML files} {--raw : Show raw YAML content}';

    protected $description = 'Show the content of a YAML state machine definition';

    public function handle(): void
    {
        $filename = $this->argument('file');
        $path = $this->option('path') ?? config('statecraft.state_machines_path', database_path('state_machines'));
        $showRaw = $this->option('raw');

        // Add .yaml extension if not present
        if (! Str::endsWith($filename, '.yaml')) {
            $filename .= '.yaml';
        }

        $filePath = $path.'/'.$filename;

        if (! File::exists($filePath)) {
            $this->error("YAML file not found: {$filePath}");

            return;
        }

        if ($showRaw) {
            $this->showRawContent($filePath);

            return;
        }

        try {
            $loader = new YamlStateMachineLoader($path);
            $definition = $loader->load(basename($filename, '.yaml'));
            $this->showParsedContent($definition, $filePath);
        } catch (Exception $e) {
            $this->error("Error loading YAML file: {$e->getMessage()}");
            $this->newLine();
            $this->warn('Showing raw content instead:');
            $this->newLine();
            $this->showRawContent($filePath);
        }
    }

    private function showRawContent(string $filePath): void
    {
        $content = File::get($filePath);
        $this->info("Raw YAML content of {$filePath}:");
        $this->newLine();
        $this->line($content);
    }

    private function showParsedContent(\Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition $definition, string $filePath): void
    {
        $this->info("State Machine Definition: {$filePath}");
        $this->newLine();

        // Basic Information
        $this->line('<comment>Basic Information:</comment>');
        $this->line("  Name: {$definition->getName()}");
        $this->line("  Model: {$definition->getModel()}");
        $this->line("  Field: {$definition->getField()}");
        $this->line("  Initial State: {$definition->getInitial()}");
        $this->newLine();

        // States
        $this->line('<comment>States:</comment>');
        foreach ($definition->getStates() as $state) {
            $indicator = $state === $definition->getInitial() ? ' (initial)' : '';
            $this->line("  - {$state}{$indicator}");
        }
        $this->newLine();

        // Transitions
        $this->line('<comment>Transitions:</comment>');
        $transitions = $definition->getTransitions();

        if ($transitions === []) {
            $this->line('  No transitions defined');
        } else {
            foreach ($transitions as $transition) {
                $guard = $transition['guard'] ?? null;
                $action = $transition['action'] ?? null;

                $guardInfo = $guard ? $this->formatGuard($guard) : '';
                $actionInfo = $action ? " → {$action}" : '';

                $this->line("  {$transition['from']} → {$transition['to']}{$guardInfo}{$actionInfo}");
            }
        }
        $this->newLine();

        // Statistics
        $this->line('<comment>Statistics:</comment>');
        $this->line('  States: '.count($definition->getStates()));
        $this->line('  Transitions: '.count($definition->getTransitions()));

        $guardsCount = collect($definition->getTransitions())
            ->filter(fn ($t): bool => isset($t['guard']))
            ->count();
        $this->line("  Guarded transitions: {$guardsCount}");

        $actionsCount = collect($definition->getTransitions())
            ->filter(fn ($t): bool => isset($t['action']))
            ->count();
        $this->line("  Transitions with actions: {$actionsCount}");
    }

    private function formatGuard($guard): string
    {
        if (is_string($guard)) {
            return " [guard: {$guard}]";
        }

        if (is_array($guard)) {
            return ' [guard: '.json_encode($guard).']';
        }

        return ' [guard: unknown]';
    }
}
