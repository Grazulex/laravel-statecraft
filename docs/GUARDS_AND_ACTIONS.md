# Guards et Actions Dynamiques - Implémentation Complète

## 🎯 Objectif Atteint

J'ai implémenté avec succès le système de **Guards et Actions dynamiques** pour Laravel Statecraft, permettant aux utilisateurs de définir leurs propres conditions et comportements dans les transitions YAML.

## ✅ Fonctionnalités Implémentées

### 1. **Contrats (Interfaces)**
- ✅ `Guard` interface pour les conditions
- ✅ `Action` interface pour les comportements
- ✅ Documentation complète avec PHPDoc

### 2. **Résolution Dynamique**
- ✅ Résolution automatique via le Container Laravel
- ✅ Validation d'interface pour les guards et actions
- ✅ Gestion d'erreurs appropriée

### 3. **Exemples Complets**
- ✅ 3 Guards d'exemple : `IsManager`, `CanSubmit`, `HasMinimumAmount`
- ✅ 3 Actions d'exemple : `NotifyReviewer`, `SendConfirmationEmail`, `ProcessPayment`
- ✅ Modèle `Order` d'exemple avec traits intégrés

### 4. **Configuration YAML**
- ✅ Support des classes complètes : `App\Guards\IsManager`
- ✅ Support des méthodes courtes : `canSubmit`, `notifyReviewer`
- ✅ 2 workflows d'exemple : simple et avancé

### 5. **Tests Complets**
- ✅ Tests unitaires pour chaque guard et action
- ✅ Tests d'intégration avec le StateMachineManager
- ✅ Tests de résolution dynamique
- ✅ **14 nouveaux tests** - tous passent ✅

## 🏗️ Architecture

### Flux de Résolution
```
YAML Definition → StateMachineManager → Container → Guard/Action Instance → Execution
```

### Exemple d'Utilisation

#### Dans le YAML :
```yaml
- from: draft
  to: pending
  guard: Examples\OrderWorkflow\Guards\CanSubmit
  action: Examples\OrderWorkflow\Actions\NotifyReviewer
```

#### Dans le Code :
```php
class Order extends Model {
    use HasStateMachine;
    
    // Méthodes auto-générées disponibles :
    $order->canSubmit();    // Vérifie si transition possible
    $order->submit();       // Exécute transition avec guard/action
}
```

## 📁 Structure des Fichiers

```
examples/OrderWorkflow/
├── Guards/
│   ├── IsManager.php           # Vérifie permissions utilisateur
│   ├── CanSubmit.php          # Valide données commande
│   └── HasMinimumAmount.php    # Vérifie montant minimum
├── Actions/
│   ├── NotifyReviewer.php      # Notification au reviewer
│   ├── SendConfirmationEmail.php # Email de confirmation
│   └── ProcessPayment.php      # Traitement paiement
├── Models/
│   └── Order.php               # Modèle d'exemple
├── advanced-order-workflow.yaml # Workflow complet
├── simple-order-workflow.yaml  # Workflow simplifié
└── README.md                   # Documentation détaillée
```

## 🔧 Utilisation

### 1. Créer un Guard
```php
class MyGuard implements \Grazulex\LaravelStatecraft\Contracts\Guard
{
    public function check(Model $model, string $from, string $to): bool
    {
        // Votre logique de validation
        return true;
    }
}
```

### 2. Créer une Action
```php
class MyAction implements \Grazulex\LaravelStatecraft\Contracts\Action
{
    public function execute(Model $model, string $from, string $to): void
    {
        // Votre logique d'action
        Log::info("Action exécutée !");
    }
}
```

### 3. Configurer dans le YAML
```yaml
transitions:
  - from: state_a
    to: state_b
    guard: App\Guards\MyGuard
    action: App\Actions\MyAction
```

## 🧪 Tests

### Coverage Complète
- **Guards** : 7 tests (validation des conditions)
- **Actions** : 3 tests (exécution des comportements)
- **Intégration** : 4 tests (résolution dynamique)
- **Total** : 14 tests, 27 assertions ✅

### Résultats
```
Tests:    53 passed (134 assertions)
Duration: 1.14s
```

## 🎨 Exemples Pratiques

### Workflow Simple
```yaml
# Transitions avec méthodes courtes
- from: draft
  to: pending
  guard: canSubmit
  action: notifyReviewer
```

### Workflow Avancé
```yaml
# Transitions avec classes complètes
- from: pending
  to: approved
  guard: Examples\OrderWorkflow\Guards\IsManager
  action: Examples\OrderWorkflow\Actions\SendConfirmationEmail
```

### Utilisation en Code
```php
$order = new Order([
    'customer_email' => 'client@example.com',
    'items' => [['name' => 'Produit 1', 'price' => 100]],
    'amount' => 100
]);

// Vérifications automatiques
if ($order->canSubmit()) {
    $order->submit(); // Exécute guard + action
}

// État et transitions disponibles
$order->getCurrentState();
$order->getAvailableTransitions();
```

## 🚀 Prochaines Étapes

L'implémentation est **complète et fonctionnelle** avec :
- ✅ Résolution dynamique des Guards et Actions
- ✅ Support complet des deux syntaxes YAML
- ✅ Exemples pratiques et documentation
- ✅ Tests complets (53 tests passent)
- ✅ Intégration parfaite avec le système existant

Le système est prêt pour la production et peut être étendu facilement avec de nouveaux Guards et Actions selon les besoins spécifiques de l'application ! 🎉
