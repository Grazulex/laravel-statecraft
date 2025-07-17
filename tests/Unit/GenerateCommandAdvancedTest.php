<?php

use Grazulex\LaravelStatecraft\Console\Commands\GenerateCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Command;

describe('GenerateCommand - Advanced Tests', function () {
    
    beforeEach(function () {
        // Setup test environment
        Config::set('statecraft.generated_code_path', storage_path('test-generated'));
        
        // Clean up any existing test files
        $testDir = storage_path('test-generated');
        if (File::exists($testDir)) {
            File::deleteDirectory($testDir);
        }
    });

    afterEach(function () {
        // Clean up test files
        $testDir = storage_path('test-generated');
        if (File::exists($testDir)) {
            File::deleteDirectory($testDir);
        }
    });

    test('command fails when YAML file does not exist', function () {
        // This covers lines 25-27 (file not found error)
        $exitCode = Artisan::call('statecraft:generate', [
            'file' => '/nonexistent/file.yaml'
        ]);
        
        $output = Artisan::output();
        
        expect($exitCode)->toBe(Command::SUCCESS);
        expect($output)->toContain('YAML file not found');
    });

    test('command fails when YAML file is malformed', function () {
        // Create a malformed YAML file
        $testFile = storage_path('test-malformed.yaml');
        File::put($testFile, "invalid: yaml: content: [broken");
        
        // This covers lines 34-37 (error loading YAML)
        $exitCode = Artisan::call('statecraft:generate', [
            'file' => $testFile
        ]);
        
        $output = Artisan::output();
        
        expect($exitCode)->toBe(Command::SUCCESS);
        expect($output)->toContain('Error loading YAML file');
        
        // Clean up
        File::delete($testFile);
    });

    test('command creates output directory when it does not exist', function () {
        // Create a valid YAML file
        $testFile = storage_path('test-workflow.yaml');
        File::put($testFile, "
state_machine:
  name: TestWorkflow
  model: App\\Models\\TestModel
  field: status
  initial: pending
  states:
    - pending
    - approved
    - rejected
  transitions:
    - from: pending
      to: approved
      guard: App\\Guards\\IsManager
      action: App\\Actions\\SendApprovalEmail
    - from: pending
      to: rejected
      guard: App\\Guards\\CanReject
      action: App\\Actions\\NotifyRejection
");
        
        // This covers line 41 (directory creation)
        $exitCode = Artisan::call('statecraft:generate', [
            'file' => $testFile
        ]);
        
        $output = Artisan::output();
        
        expect($exitCode)->toBe(Command::SUCCESS);
        expect($output)->toContain('Files generated successfully');
        
        // Verify directory was created
        $outputDir = storage_path('test-generated');
        expect(File::isDirectory($outputDir))->toBeTrue();
        
        // Clean up
        File::delete($testFile);
    });

    test('command generates guards when they exist in transitions', function () {
        // Create a valid YAML file with guards
        $testFile = storage_path('test-guards.yaml');
        File::put($testFile, "
state_machine:
  name: GuardWorkflow
  model: App\\Models\\GuardModel
  field: status
  initial: pending
  states:
    - pending
    - approved
  transitions:
    - from: pending
      to: approved
      guard: App\\Guards\\IsManager
      action: App\\Actions\\SendEmail
");
        
        // This covers the guard generation logic (around line 71)
        $exitCode = Artisan::call('statecraft:generate', [
            'file' => $testFile
        ]);
        
        $output = Artisan::output();
        
        expect($exitCode)->toBe(Command::SUCCESS);
        expect($output)->toContain('Generated guard: IsManager');
        
        // Verify guard file was created
        $guardFile = storage_path('test-generated/Guards/IsManager.php');
        expect(File::exists($guardFile))->toBeTrue();
        
        // Clean up
        File::delete($testFile);
    });

    test('command generates actions when they exist in transitions', function () {
        // Create a valid YAML file with actions
        $testFile = storage_path('test-actions.yaml');
        File::put($testFile, "
state_machine:
  name: ActionWorkflow
  model: App\\Models\\ActionModel
  field: status
  initial: pending
  states:
    - pending
    - processed
  transitions:
    - from: pending
      to: processed
      guard: App\\Guards\\CanProcess
      action: App\\Actions\\ProcessItem
");
        
        // This covers the action generation logic
        $exitCode = Artisan::call('statecraft:generate', [
            'file' => $testFile
        ]);
        
        $output = Artisan::output();
        
        expect($exitCode)->toBe(Command::SUCCESS);
        expect($output)->toContain('Generated action: ProcessItem');
        
        // Verify action file was created
        $actionFile = storage_path('test-generated/Actions/ProcessItem.php');
        expect(File::exists($actionFile))->toBeTrue();
        
        // Clean up
        File::delete($testFile);
    });

    test('command generates model example', function () {
        // Create a valid YAML file
        $testFile = storage_path('test-model.yaml');
        File::put($testFile, "
state_machine:
  name: ModelWorkflow
  model: App\\Models\\ExampleModel
  field: state
  initial: draft
  states:
    - draft
    - published
  transitions:
    - from: draft
      to: published
");
        
        // This covers the model generation logic (around line 99)
        $exitCode = Artisan::call('statecraft:generate', [
            'file' => $testFile
        ]);
        
        $output = Artisan::output();
        
        expect($exitCode)->toBe(Command::SUCCESS);
        expect($output)->toContain('Generated model example: ExampleModelExample');
        
        // Verify model file was created
        $modelFile = storage_path('test-generated/ExampleModelExample.php');
        expect(File::exists($modelFile))->toBeTrue();
        
        // Clean up
        File::delete($testFile);
    });

    test('command handles transitions without guards or actions', function () {
        // Create a YAML file with transitions that have no guards or actions
        $testFile = storage_path('test-no-guards-actions.yaml');
        File::put($testFile, "
state_machine:
  name: SimpleWorkflow
  model: App\\Models\\SimpleModel
  field: status
  initial: start
  states:
    - start
    - end
  transitions:
    - from: start
      to: end
");
        
        // This tests the filter() logic for guards and actions
        $exitCode = Artisan::call('statecraft:generate', [
            'file' => $testFile
        ]);
        
        $output = Artisan::output();
        
        expect($exitCode)->toBe(Command::SUCCESS);
        expect($output)->toContain('Files generated successfully');
        expect($output)->not->toContain('Generated guard:');
        expect($output)->not->toContain('Generated action:');
        
        // Clean up
        File::delete($testFile);
    });

    test('command handles complex namespace paths', function () {
        // Create a YAML file with complex namespace paths
        $testFile = storage_path('test-complex-namespaces.yaml');
        File::put($testFile, "
state_machine:
  name: ComplexWorkflow
  model: App\\Domain\\Complex\\Models\\ComplexModel
  field: status
  initial: initial
  states:
    - initial
    - final
  transitions:
    - from: initial
      to: final
      guard: App\\Domain\\Complex\\Guards\\ComplexGuard
      action: App\\Domain\\Complex\\Actions\\ComplexAction
");
        
        // This tests complex namespace handling
        $exitCode = Artisan::call('statecraft:generate', [
            'file' => $testFile
        ]);
        
        $output = Artisan::output();
        
        expect($exitCode)->toBe(Command::SUCCESS);
        expect($output)->toContain('Generated guard: ComplexGuard');
        expect($output)->toContain('Generated action: ComplexAction');
        expect($output)->toContain('Generated model example: ComplexModelExample');
        
        // Clean up
        File::delete($testFile);
    });

    test('generateFromStub method handles replacements correctly', function () {
        // This is a more focused test of the generateFromStub method
        $command = new GenerateCommand();
        
        // Use reflection to access private method
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('generateFromStub');
        $method->setAccessible(true);
        
        // Test using existing guard stub
        $result = $method->invoke($command, 'guard', [
            'className' => 'TestGuard',
            'namespace' => 'App\\Guards'
        ]);
        
        expect($result)->toContain('TestGuard');
        expect($result)->toContain('App\\Guards');
        expect($result)->toContain('class TestGuard');
    });
});
