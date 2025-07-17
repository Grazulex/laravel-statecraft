# Laravel Statecraft - Documentation

This documentation covers all aspects of Laravel Statecraft, a package for state machine management in Laravel.

## Documentation Structure

### Core Documentation
- **[CONSOLE_COMMANDS.md](CONSOLE_COMMANDS.md)** - Guide complet des commandes console
- **[GUARD_EXPRESSIONS.md](GUARD_EXPRESSIONS.md)** - Expressions de garde avancées
- **[GUARDS_AND_ACTIONS.md](GUARDS_AND_ACTIONS.md)** - Guards et actions personnalisées
- **[EVENTS.md](EVENTS.md)** - Système d'événements
- **[TESTING.md](TESTING.md)** - Tests et assertions
- **[CONFIGURATION.md](CONFIGURATION.md)** - Configuration du package
- **[HISTORY.md](HISTORY.md)** - Historique des versions
- **[user_workflow.md](user_workflow.md)** - Guide utilisateur détaillé

## Quick Start

1. **Installation and Configuration**
   - See [CONFIGURATION.md](CONFIGURATION.md)

2. **Create your first state machine**
   ```bash
   php artisan statecraft:make order-workflow
   ```

3. **Use the new console commands**
   ```bash
   php artisan statecraft:list
   php artisan statecraft:validate --all
   ```

4. **Explore examples**
   - See the [../examples](../examples) folder

## Main Features

### Console Commands
Pour une documentation complète des commandes, consultez [CONSOLE_COMMANDS.md](CONSOLE_COMMANDS.md).

Commandes disponibles :
- `statecraft:list` - Liste les définitions
- `statecraft:show` - Affiche une définition
- `statecraft:export` - Export vers différents formats
- `statecraft:validate` - Valide les définitions
- `statecraft:make` - Génère une définition YAML
- `statecraft:generate` - Génère les classes PHP

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
