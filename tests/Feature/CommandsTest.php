<?php

declare(strict_types=1);

test('MakeStateMachineCommand → it creates a YAML state machine definition', function () {
    // Test that the command creates a YAML file with proper structure
    $this->artisan('statecraft:make test-article')
        ->expectsOutput('State machine created successfully at '.database_path('state_machines/test-article.yaml'))
        ->expectsOutput('Don\'t forget to:')
        ->expectsOutput('• Add the HasStateMachine trait to your App\Models\TestArticle model')
        ->expectsOutput('• Run migrations if needed')
        ->expectsOutput('• Implement guards and actions if specified')
        ->assertExitCode(0);

    // Verify the file was created
    $yamlPath = database_path('state_machines/test-article.yaml');
    expect(file_exists($yamlPath))->toBeTrue();

    // Verify content structure
    $content = file_get_contents($yamlPath);
    expect($content)->toContain('name: test-article');
    expect($content)->toContain('model: App\Models\TestArticle');
    expect($content)->toContain('field:'); // Field is empty by default when 'state'
    expect($content)->toContain('states:');
    expect($content)->toContain('[draft, published]');
    expect($content)->toContain('initial: draft');
});

test('GenerateCommand → it generates PHP classes from YAML definition', function () {
    // First create a YAML definition with guards and actions
    $yamlPath = database_path('state_machines/test-product.yaml');
    $yamlContent = <<<YAML
state_machine:
  name: test-product
  model: App\Models\TestProduct
  field: status
  states: [draft, published, archived]
  initial: draft
  transitions:
    - from: draft
      to: published
      guard: App\Guards\CanPublish
      action: App\Actions\PublishAction
    - from: published
      to: archived
      action: App\Actions\ArchiveAction
YAML;

    // Create the directory if it doesn't exist
    if (! file_exists(dirname($yamlPath))) {
        mkdir(dirname($yamlPath), 0755, true);
    }

    file_put_contents($yamlPath, $yamlContent);

    // Run the generate command
    $outputDir = config('statecraft.generated_code_path', app_path('StateMachines'));
    $this->artisan('statecraft:generate '.$yamlPath)
        ->expectsOutput('Generated guard: CanPublish')
        ->expectsOutput('Generated action: PublishAction')
        ->expectsOutput('Generated action: ArchiveAction')
        ->expectsOutput('Generated model example: TestProductExample')
        ->expectsOutput('Files generated successfully in '.$outputDir)
        ->assertExitCode(0);

    // Verify generated files
    expect(file_exists($outputDir.'/Guards/CanPublish.php'))->toBeTrue();
    expect(file_exists($outputDir.'/Actions/PublishAction.php'))->toBeTrue();
    expect(file_exists($outputDir.'/Actions/ArchiveAction.php'))->toBeTrue();
    expect(file_exists($outputDir.'/TestProductExample.php'))->toBeTrue();

    // Verify guard class content
    $guardContent = file_get_contents($outputDir.'/Guards/CanPublish.php');
    expect($guardContent)->toContain('class CanPublish implements Guard');
    expect($guardContent)->toContain('public function check(Model $model, string $from, string $to): bool');

    // Verify action class content
    $actionContent = file_get_contents($outputDir.'/Actions/PublishAction.php');
    expect($actionContent)->toContain('class PublishAction implements Action');
    expect($actionContent)->toContain('public function execute(Model $model, string $from, string $to): void');

    // Verify model example content
    $modelContent = file_get_contents($outputDir.'/TestProductExample.php');
    expect($modelContent)->toContain('class TestProduct extends Model');
    expect($modelContent)->toContain('use HasStateMachine, HasStateHistory;');
    expect($modelContent)->toContain('return \'test-product\';');
});

test('Commands → they use proper configuration paths', function () {
    // Test that configuration paths are properly used
    config(['statecraft.state_machines_path' => base_path('custom/state_machines')]);
    config(['statecraft.generated_code_path' => base_path('custom/generated')]);

    $this->artisan('statecraft:make custom-test')
        ->expectsOutput('State machine created successfully at '.base_path('custom/state_machines/custom-test.yaml'))
        ->assertExitCode(0);

    expect(file_exists(base_path('custom/state_machines/custom-test.yaml')))->toBeTrue();
});

// Clean up after tests
afterEach(function () {
    // Clean up test files
    $paths = [
        database_path('state_machines'),
        app_path('StateMachines'),
        base_path('custom'),
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            // Simple recursive directory removal
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }

            rmdir($path);
        }
    }
});
