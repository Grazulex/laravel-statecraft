# Laravel Statecraft - Fichiers d'Exemple YAML

Ce dossier contient des fichiers d'exemple YAML qui démontrent les différentes fonctionnalités de Laravel Statecraft.

## Fichiers d'Exemple

### 📄 `example-workflow.yaml`
Exemple basique d'une machine d'état simple pour démonstration.

```yaml
state_machine:
  name: ExampleWorkflow
  model: Grazulex\LaravelStatecraft\Examples\ExampleModel
  states:
    - draft
    - pending
    - approved
    - rejected
  initial: draft
  transitions:
    - from: draft
      to: pending
    - from: pending
      to: approved
    - from: pending
      to: rejected
```

### 📄 `order.yaml`
Exemple d'un workflow de commande avec guards et actions.

```yaml
state_machine:
  name: OrderWorkflow
  model: App\Models\Order
  states:
    - draft
    - pending
    - approved
    - rejected
  initial: draft
  transitions:
    - from: draft
      to: pending
      guard: CanSubmit
      action: NotifyReviewer
    - from: pending
      to: approved
      guard:
        and:
          - IsManager
          - HasMinimumAmount
      action: SendApprovalEmail
    - from: pending
      to: rejected
      guard: IsManager
      action: SendRejectionEmail
```

### 📄 `user.yaml`
Exemple d'un workflow utilisateur avec états multiples.

```yaml
state_machine:
  name: UserWorkflow
  model: App\Models\User
  states:
    - inactive
    - active
    - pending
    - banned
  initial: inactive
  transitions:
    - from: inactive
      to: active
    - from: active
      to: pending
    - from: pending
      to: active
    - from: active
      to: banned
    - from: banned
      to: active
```

### 📄 `test.yaml`
Fichier de test simple pour les tests unitaires.

```yaml
state_machine:
  name: TestWorkflow
  model: App\Models\Test
  states:
    - draft
    - pending
    - approved
  initial: draft
  transitions:
    - from: draft
      to: pending
    - from: pending
      to: approved
```

## Utilisation

Ces fichiers peuvent être utilisés pour :

1. **Apprentissage** - Comprendre la syntaxe YAML
2. **Tests** - Tester les commandes console
3. **Démarrage rapide** - Base pour vos propres workflows

### Commandes Console

```bash
# Lister tous les exemples
php artisan statecraft:list --path=examples

# Afficher un exemple spécifique
php artisan statecraft:show order --path=examples

# Valider un exemple
php artisan statecraft:validate order --path=examples

# Exporter un exemple
php artisan statecraft:export order json --path=examples
```

## Exemples Plus Complets

Pour des exemples plus détaillés avec du code PHP complet, consultez les dossiers :

- **[OrderWorkflow/](OrderWorkflow/)** - Exemple complet de workflow de commande
- **[UserSubscription/](UserSubscription/)** - Workflow d'abonnement utilisateur
- **[ArticlePublishing/](ArticlePublishing/)** - Workflow de publication d'article
- **[EventUsage/](EventUsage/)** - Utilisation des événements

## Contribution

Pour ajouter de nouveaux exemples :

1. Créer un nouveau fichier YAML
2. Documenter la structure dans ce README
3. Ajouter des tests si nécessaire
4. Suivre les conventions de nommage existantes
