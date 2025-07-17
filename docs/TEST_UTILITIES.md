# Test Utilities - StateMachineTester

La classe `StateMachineTester` fournit des utilitaires pour tester les transitions de machine à états sans exécuter réellement les transitions.

## Utilisation de base

### Assertions de transition

```php
use Grazulex\LaravelStatecraft\Testing\StateMachineTester;

// Vérifier qu'une transition est autorisée
StateMachineTester::assertTransitionAllowed($order, 'approved');

// Vérifier qu'une transition est bloquée
StateMachineTester::assertTransitionBlocked($order, 'rejected');

// Vérifier l'état actuel
StateMachineTester::assertInState($order, 'pending');
```

### Vérification des transitions disponibles

```php
// Vérifier les transitions disponibles
StateMachineTester::assertHasAvailableTransitions($order, ['approved', 'rejected']);

// Obtenir toutes les transitions disponibles
$transitions = StateMachineTester::transitionsFor($order);
// Retourne : ['pending' => ['approved', 'rejected']]
```

## Exemples d'utilisation

### Test d'autorisation utilisateur

```php
test('only manager can approve order', function () {
    $order = Order::factory()->create(['state' => 'pending']);

    // Utilisateur normal ne peut pas approuver
    loginAsRegularUser();
    StateMachineTester::assertTransitionBlocked($order, 'approved');

    // Manager peut approuver
    loginAsManager();
    StateMachineTester::assertTransitionAllowed($order, 'approved');
});
```

### Test de workflow complexe

```php
test('order workflow follows business rules', function () {
    $order = Order::factory()->create(['state' => 'draft']);

    // Vérifier l'état initial
    StateMachineTester::assertInState($order, 'draft');

    // Vérifier les transitions possibles depuis draft
    StateMachineTester::assertTransitionAllowed($order, 'pending');
    StateMachineTester::assertTransitionBlocked($order, 'completed');

    // Vérifier toutes les transitions disponibles
    $transitions = StateMachineTester::transitionsFor($order);
    expect($transitions['draft'])->toContain('pending');
    expect($transitions['draft'])->not->toContain('completed');
});
```

### Test de garde (Guard)

```php
test('minimum amount guard works correctly', function () {
    $order = Order::factory()->create([
        'state' => 'pending',
        'amount' => 50 // Montant insuffisant
    ]);

    // Ne peut pas être approuvé avec un montant insuffisant
    StateMachineTester::assertTransitionBlocked($order, 'approved');

    // Peut être approuvé avec un montant suffisant
    $order->amount = 1000;
    StateMachineTester::assertTransitionAllowed($order, 'approved');
});
```

### Test d'états finaux

```php
test('completed orders cannot be modified', function () {
    $order = Order::factory()->create(['state' => 'completed']);

    // Aucune transition possible depuis completed
    StateMachineTester::assertTransitionBlocked($order, 'pending');
    StateMachineTester::assertTransitionBlocked($order, 'approved');
    StateMachineTester::assertTransitionBlocked($order, 'rejected');

    // Vérifier qu'aucune transition n'est disponible
    $transitions = StateMachineTester::transitionsFor($order);
    expect($transitions['completed'])->toBeEmpty();
});
```

## Méthodes disponibles

### `assertTransitionAllowed(Model $model, string $to): void`
Vérifie qu'une transition vers l'état spécifié est autorisée.

### `assertTransitionBlocked(Model $model, string $to): void`
Vérifie qu'une transition vers l'état spécifié est bloquée.

### `assertInState(Model $model, string $expectedState): void`
Vérifie que le modèle est dans l'état spécifié.

### `assertHasAvailableTransitions(Model $model, array $expectedTransitions): void`
Vérifie que le modèle a exactement les transitions spécifiées disponibles.

### `transitionsFor(Model $model): array`
Retourne toutes les transitions disponibles pour le modèle sous la forme `['état_actuel' => ['état1', 'état2']]`.

## Prérequis

- Le modèle doit utiliser le trait `HasStateMachine`
- Une machine à états doit être définie pour le modèle
- Les tests doivent inclure PHPUnit pour les assertions

## Messages d'erreur

Les assertions fournissent des messages d'erreur descriptifs :

```php
// Si la transition échoue
"Expected transition to 'approved' to be allowed, but it was blocked."

// Si l'état ne correspond pas
"Model should be in state 'pending' but is in 'draft'"

// Si les transitions ne correspondent pas
"Available transitions should be [approved, rejected] but found [pending]"
```
