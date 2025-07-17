<?php

declare(strict_types=1);

use Grazulex\LaravelStatecraft\Definitions\StateMachineDefinition;
use Grazulex\LaravelStatecraft\Exceptions\InvalidStateMachineDefinitionException;
use Grazulex\LaravelStatecraft\Support\YamlStateMachineLoader;
use Illuminate\Support\Facades\File;

describe('YamlStateMachineLoader - Advanced Tests', function () {
    beforeEach(function () {
        // Create a temporary directory for test files
        $this->tempDir = sys_get_temp_dir().'/statecraft_test_'.uniqid();
        File::makeDirectory($this->tempDir);
    });

    afterEach(function () {
        // Clean up temporary directory
        if (File::exists($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }
    });

    test('load throws exception when file does not exist', function () {
        $loader = new YamlStateMachineLoader($this->tempDir);

        expect(function () use ($loader) {
            $loader->load('nonexistent');
        })->toThrow(InvalidStateMachineDefinitionException::class, 'State machine file not found:');
    });

    test('loadAll returns empty array when directory does not exist', function () {
        $nonExistentDir = sys_get_temp_dir().'/statecraft_nonexistent_'.uniqid();
        $loader = new YamlStateMachineLoader($nonExistentDir);

        $result = $loader->loadAll();
        expect($result)->toBeArray();
        expect($result)->toBeEmpty();
    });

    test('parseDefinition throws exception when state_machine key is missing', function () {
        $yamlContent = <<<YAML
# Missing state_machine root key
name: test
model: App\Models\Test
states: [draft, published]
initial: draft
YAML;

        File::put($this->tempDir.'/invalid.yaml', $yamlContent);

        $loader = new YamlStateMachineLoader($this->tempDir);

        expect(function () use ($loader) {
            $loader->load('invalid');
        })->toThrow(InvalidStateMachineDefinitionException::class, "Missing 'state_machine' root key in invalid");
    });

    test('validateStructure throws exception when required keys are missing', function () {
        $testCases = [
            'name' => [
                'content' => <<<YAML
state_machine:
  # Missing name
  model: App\Models\Test
  states: [draft, published]
  initial: draft
YAML,
                'expected_error' => "Missing required key 'name' in missing_name",
            ],
            'model' => [
                'content' => <<<'YAML'
state_machine:
  name: test
  # Missing model
  states: [draft, published]
  initial: draft
YAML,
                'expected_error' => "Missing required key 'model' in missing_model",
            ],
            'states' => [
                'content' => <<<YAML
state_machine:
  name: test
  model: App\Models\Test
  # Missing states
  initial: draft
YAML,
                'expected_error' => "Missing required key 'states' in missing_states",
            ],
            'initial' => [
                'content' => <<<YAML
state_machine:
  name: test
  model: App\Models\Test
  states: [draft, published]
  # Missing initial
YAML,
                'expected_error' => "Missing required key 'initial' in missing_initial",
            ],
        ];

        foreach ($testCases as $key => $testCase) {
            $filename = "missing_{$key}";
            File::put($this->tempDir."/{$filename}.yaml", $testCase['content']);

            $loader = new YamlStateMachineLoader($this->tempDir);

            expect(function () use ($loader, $filename) {
                $loader->load($filename);
            })->toThrow(InvalidStateMachineDefinitionException::class, $testCase['expected_error']);
        }
    });

    test('validateStructure throws exception when states is not an array', function () {
        $yamlContent = <<<YAML
state_machine:
  name: test
  model: App\Models\Test
  states: "not an array"
  initial: draft
YAML;

        File::put($this->tempDir.'/invalid_states.yaml', $yamlContent);

        $loader = new YamlStateMachineLoader($this->tempDir);

        expect(function () use ($loader) {
            $loader->load('invalid_states');
        })->toThrow(InvalidStateMachineDefinitionException::class, "'states' must be an array in invalid_states");
    });

    test('validateStructure throws exception when initial state is not in states list', function () {
        $yamlContent = <<<YAML
state_machine:
  name: test
  model: App\Models\Test
  states: [draft, published]
  initial: nonexistent
YAML;

        File::put($this->tempDir.'/invalid_initial.yaml', $yamlContent);

        $loader = new YamlStateMachineLoader($this->tempDir);

        expect(function () use ($loader) {
            $loader->load('invalid_initial');
        })->toThrow(InvalidStateMachineDefinitionException::class, "Initial state 'nonexistent' not found in states list in invalid_initial");
    });

    test('getFilePath handles filenames with and without .yaml extension', function () {
        $loader = new YamlStateMachineLoader($this->tempDir);

        // Use reflection to access the private method
        $reflection = new ReflectionClass($loader);
        $method = $reflection->getMethod('getFilePath');
        $method->setAccessible(true);

        // Test without .yaml extension
        $result1 = $method->invoke($loader, 'test');
        expect($result1)->toBe($this->tempDir.'/test.yaml');

        // Test with .yaml extension
        $result2 = $method->invoke($loader, 'test.yaml');
        expect($result2)->toBe($this->tempDir.'/test.yaml');
    });

    test('load handles malformed YAML gracefully', function () {
        $malformedYaml = <<<YAML
state_machine:
  name: test
  model: App\Models\Test
  states: [draft, published
  # Missing closing bracket - malformed YAML
  initial: draft
YAML;

        File::put($this->tempDir.'/malformed.yaml', $malformedYaml);

        $loader = new YamlStateMachineLoader($this->tempDir);

        expect(function () use ($loader) {
            $loader->load('malformed');
        })->toThrow(Exception::class); // Should throw a YAML parsing exception
    });

    test('loadAll processes multiple files correctly', function () {
        // Create multiple valid YAML files
        $file1Content = <<<YAML
state_machine:
  name: test1
  model: App\Models\Test1
  states: [draft, published]
  initial: draft
YAML;

        $file2Content = <<<YAML
state_machine:
  name: test2
  model: App\Models\Test2
  states: [pending, approved, rejected]
  initial: pending
YAML;

        File::put($this->tempDir.'/test1.yaml', $file1Content);
        File::put($this->tempDir.'/test2.yaml', $file2Content);

        $loader = new YamlStateMachineLoader($this->tempDir);
        $result = $loader->loadAll();

        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
        expect($result)->toHaveKeys(['test1', 'test2']);
        expect($result['test1'])->toBeInstanceOf(StateMachineDefinition::class);
        expect($result['test2'])->toBeInstanceOf(StateMachineDefinition::class);
    });

    test('load parses valid YAML with all optional fields', function () {
        $yamlContent = <<<YAML
state_machine:
  name: complete-test
  model: App\Models\CompleteTest
  states: [draft, review, published, archived]
  initial: draft
  field: status
  transitions:
    - from: draft
      to: review
      guard: canReview
    - from: review
      to: published
      action: publishAction
YAML;

        File::put($this->tempDir.'/complete.yaml', $yamlContent);

        $loader = new YamlStateMachineLoader($this->tempDir);
        $definition = $loader->load('complete');

        expect($definition)->toBeInstanceOf(StateMachineDefinition::class);
        expect($definition->getName())->toBe('complete-test');
        expect($definition->getModel())->toBe('App\Models\CompleteTest');
        expect($definition->getStates())->toBe(['draft', 'review', 'published', 'archived']);
        expect($definition->getInitial())->toBe('draft');
        expect($definition->getField())->toBe('status');
        expect($definition->getTransitions())->toHaveCount(2);
    });

    test('load uses default field when not specified', function () {
        $yamlContent = <<<YAML
state_machine:
  name: default-field
  model: App\Models\Test
  states: [draft, published]
  initial: draft
YAML;

        File::put($this->tempDir.'/default_field.yaml', $yamlContent);

        $loader = new YamlStateMachineLoader($this->tempDir);
        $definition = $loader->load('default_field');

        expect($definition->getField())->toBe('state');
    });

    test('constructor uses default path when none provided', function () {
        $loader = new YamlStateMachineLoader();

        // Use reflection to access the private property
        $reflection = new ReflectionClass($loader);
        $property = $reflection->getProperty('basePath');
        $property->setAccessible(true);

        $basePath = $property->getValue($loader);
        expect($basePath)->toBe(database_path('state_machines'));
    });

    test('constructor uses custom path when provided', function () {
        $customPath = '/custom/path';
        $loader = new YamlStateMachineLoader($customPath);

        // Use reflection to access the private property
        $reflection = new ReflectionClass($loader);
        $property = $reflection->getProperty('basePath');
        $property->setAccessible(true);

        $basePath = $property->getValue($loader);
        expect($basePath)->toBe($customPath);
    });
});
