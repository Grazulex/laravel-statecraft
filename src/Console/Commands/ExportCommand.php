<?php

declare(strict_types=1);

namespace Grazulex\LaravelStatecraft\Console\Commands;

use Exception;
use Grazulex\LaravelStatecraft\Support\YamlStateMachineLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ExportCommand extends Command
{
    protected $signature = 'statecraft:export {file : The YAML file name (without extension)} {format : Export format (json, mermaid, md)} {--path= : Custom path to search for YAML files} {--output= : Output file path}';

    protected $description = 'Export a YAML state machine definition to different formats';

    public function handle(): void
    {
        $filename = $this->argument('file');
        $format = $this->argument('format');
        $path = $this->option('path') ?? config('statecraft.definitions_path', resource_path('statemachines'));
        $outputPath = $this->option('output');

        // Add .yaml extension if not present
        if (! Str::endsWith($filename, '.yaml')) {
            $filename .= '.yaml';
        }

        $filePath = $path.'/'.$filename;

        if (! File::exists($filePath)) {
            $this->error("YAML file not found: {$filePath}");

            return;
        }

        if (! in_array($format, ['json', 'mermaid', 'md'])) {
            $this->error("Invalid format: {$format}. Supported formats: json, mermaid, md");

            return;
        }

        try {
            $loader = new YamlStateMachineLoader($path);
            $definition = $loader->load(basename($filename, '.yaml'));

            $content = $this->generateExport($definition, $format);

            if ($outputPath) {
                File::put($outputPath, $content);
                $this->info("Exported to: {$outputPath}");
            } else {
                $this->line($content);
            }
        } catch (Exception $e) {
            $this->error("Error exporting YAML file: {$e->getMessage()}");
        }
    }

    private function generateExport(\Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition $definition, string $format): string
    {
        switch ($format) {
            case 'json':
                return $this->exportToJson($definition);
            case 'mermaid':
                return $this->exportToMermaid($definition);
            case 'md':
                return $this->exportToMarkdown($definition);
            default:
                throw new Exception("Unsupported format: {$format}");
        }
    }

    private function exportToJson(\Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition $definition): string
    {
        $data = [
            'name' => $definition->getName(),
            'model' => $definition->getModel(),
            'field' => $definition->getField(),
            'initial' => $definition->getInitial(),
            'states' => $definition->getStates(),
            'transitions' => $definition->getTransitions(),
            'metadata' => [
                'exported_at' => now()->toISOString(),
                'exported_by' => 'Laravel Statecraft',
                'version' => '1.0.0',
            ],
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function exportToMermaid(\Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition $definition): string
    {
        $mermaid = "stateDiagram-v2\n";
        $mermaid .= "    title {$definition->getName()}\n\n";

        // Add initial state
        $mermaid .= "    [*] --> {$definition->getInitial()}\n";

        // Add transitions
        foreach ($definition->getTransitions() as $transition) {
            $from = $transition['from'];
            $to = $transition['to'];
            $label = '';

            if (isset($transition['guard'])) {
                $guard = is_array($transition['guard']) ? json_encode($transition['guard']) : $transition['guard'];
                $label .= "[{$guard}]";
            }

            if (isset($transition['action'])) {
                $label .= $label !== '' && $label !== '0' ? " / {$transition['action']}" : "[{$transition['action']}]";
            }

            $mermaid .= "    {$from} --> {$to}";
            if ($label !== '' && $label !== '0') {
                $mermaid .= " : {$label}";
            }
            $mermaid .= "\n";
        }

        // Add state descriptions
        $mermaid .= "\n    %% State descriptions\n";
        foreach ($definition->getStates() as $state) {
            $mermaid .= "    {$state} : {$state}\n";
        }

        return $mermaid;
    }

    private function exportToMarkdown(\Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition $definition): string
    {
        $md = "# {$definition->getName()}\n\n";

        // Basic information
        $md .= "## Basic Information\n\n";
        $md .= "- **Name**: {$definition->getName()}\n";
        $md .= "- **Model**: {$definition->getModel()}\n";
        $md .= "- **Field**: {$definition->getField()}\n";
        $md .= "- **Initial State**: {$definition->getInitial()}\n\n";

        // States
        $md .= "## States\n\n";
        foreach ($definition->getStates() as $state) {
            $indicator = $state === $definition->getInitial() ? ' *(initial)*' : '';
            $md .= "- `{$state}`{$indicator}\n";
        }
        $md .= "\n";

        // Transitions
        $md .= "## Transitions\n\n";
        $md .= "| From | To | Guard | Action |\n";
        $md .= "|------|----|----|--------|\n";

        foreach ($definition->getTransitions() as $transition) {
            $from = $transition['from'];
            $to = $transition['to'];
            $guard = $transition['guard'] ?? '';
            $action = $transition['action'] ?? '';

            if (is_array($guard)) {
                $guard = '`'.json_encode($guard).'`';
            } elseif ($guard) {
                $guard = "`{$guard}`";
            }

            if ($action) {
                $action = "`{$action}`";
            }

            $md .= "| `{$from}` | `{$to}` | {$guard} | {$action} |\n";
        }

        // Statistics
        $md .= "\n## Statistics\n\n";
        $md .= '- **States**: '.count($definition->getStates())."\n";
        $md .= '- **Transitions**: '.count($definition->getTransitions())."\n";

        $guardsCount = collect($definition->getTransitions())
            ->filter(fn ($t): bool => isset($t['guard']))
            ->count();
        $md .= "- **Guarded transitions**: {$guardsCount}\n";

        $actionsCount = collect($definition->getTransitions())
            ->filter(fn ($t): bool => isset($t['action']))
            ->count();
        $md .= "- **Transitions with actions**: {$actionsCount}\n";

        // Mermaid diagram
        $md .= "\n## State Diagram\n\n";
        $md .= "```mermaid\n";
        $md .= $this->exportToMermaid($definition);
        $md .= "```\n";

        $md .= "\n---\n";

        return $md.('*Generated by Laravel Statecraft on '.now()->format('Y-m-d H:i:s')."*\n");
    }
}
