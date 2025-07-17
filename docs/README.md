# Laravel Statecraft - Documentation

This documentation covers all aspects of Laravel Statecraft, a package for state machine management in Laravel.

## Documentation Structure

### Main Files

- **[CONSOLE_COMMANDS.md](CONSOLE_COMMANDS.md)** - Console commands guide
- **[GUARD_EXPRESSIONS.md](GUARD_EXPRESSIONS.md)** - Guard expressions documentation
- **[GUARDS_AND_ACTIONS.md](GUARDS_AND_ACTIONS.md)** - Guards and actions guide
- **[CONFIGURATION.md](CONFIGURATION.md)** - Package configuration
- **[EVENTS.md](EVENTS.md)** - Event system
- **[HISTORY.md](HISTORY.md)** - Transition history
- **[TESTING.md](TESTING.md)** - Tests and assertions
- **[COMMANDS.md](COMMANDS.md)** - Existing commands guide

### Example Files

- **[user_workflow.md](user_workflow.md)** - User workflow example

## Quick Start

1. **Installation and Configuration**
   - See [CONFIGURATION.md](CONFIGURATION.md)

2. **Create your first state machine**
   - See [COMMANDS.md](COMMANDS.md)

3. **Use the new console commands**
   - See [CONSOLE_COMMANDS.md](CONSOLE_COMMANDS.md)

4. **Explore examples**
   - See the [../examples](../examples) folder

## Main Features

### Console Commands
- `statecraft:list` - List definitions
- `statecraft:show` - Show a definition
- `statecraft:export` - Export to different formats
- `statecraft:validate` - Validate definitions
- `statecraft:make` - Generate YAML definition
- `statecraft:generate` - Generate PHP classes

### Guards and Actions
- Simple guards and complex expressions
- Custom actions
- Automatic validation

### Event System
- Transition events
- Custom listeners
- Laravel integration

### Transition History
- Automatic recording
- Custom metadata
- History queries

## Contributing

To contribute to this documentation:

1. Follow naming conventions
2. Include practical examples
3. Maintain consistency with existing style
4. Test all code examples

## Support

For questions or issues:

- Open an issue on GitHub
- Consult the complete documentation
- See examples in the [../examples](../examples) folder
