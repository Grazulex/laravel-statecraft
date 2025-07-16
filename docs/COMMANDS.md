# Laravel Statecraft Commands

## Overview

Laravel Statecraft now includes improved Artisan commands for generating state machine definitions and PHP classes from YAML definitions. These commands use a stub-based approach and follow Laravel package naming conventions.

## Commands

### 1. `statecraft:make` - Generate YAML State Machine Definition

Creates a new YAML state machine definition file.

#### Usage
```bash
php artisan statecraft:make {name}
```

#### Examples
```bash
php artisan statecraft:make order-workflow
php artisan statecraft:make article-status
php artisan statecraft:make user-subscription
```

#### Generated File Structure
The command creates a YAML file at `database/state_machines/{name}.yaml` with:
- Basic state machine structure
- Model class path (auto-generated from name)
- Default states: `draft`, `published`
- Basic transitions
- Commented guard and action examples

#### Configuration
- Uses `statecraft.state_machines_path` config for output directory
- Defaults to `database/state_machines/` if not configured

### 2. `statecraft:generate` - Generate PHP Classes from YAML

Generates PHP classes (guards, actions, model examples) from an existing YAML definition.

#### Usage
```bash
php artisan statecraft:generate {yaml-file}
```

#### Examples
```bash
php artisan statecraft:generate database/state_machines/order-workflow.yaml
php artisan statecraft:generate storage/state_machines/custom-workflow.yaml
```

#### Generated Classes
- **Guards**: `app/StateMachines/Guards/{GuardName}.php`
- **Actions**: `app/StateMachines/Actions/{ActionName}.php`
- **Model Example**: `app/StateMachines/{ModelName}Example.php`

#### Configuration
- Uses `statecraft.generated_code_path` config for output directory
- Defaults to `app/StateMachines/` if not configured

## Stub-Based Generation

Both commands use stub files located in `src/Console/Commands/stubs/`:
- `state-machine.yaml.stub` - Template for YAML definitions
- `guard.php.stub` - Template for guard classes
- `action.php.stub` - Template for action classes  
- `model.php.stub` - Template for model examples

## Testing

Comprehensive test coverage includes:
- Command registration in service provider
- YAML file generation and validation
- PHP class generation from YAML
- Configuration path usage
- File structure validation
- Content validation

All tests pass: **39 tests with 107 assertions**

## Features

✅ **Stub-based generation** - Uses templates for consistent output
✅ **Configuration-driven** - Respects user-defined paths
✅ **Proper naming** - Follows Laravel package conventions
✅ **Comprehensive testing** - Full test coverage
✅ **Error handling** - Proper error messages and validation
✅ **Auto-discovery** - Automatically extracts guards and actions from YAML
✅ **Namespace support** - Proper namespace handling for generated classes
