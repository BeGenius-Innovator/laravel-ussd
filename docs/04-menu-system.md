# Étape 5 : Système de Menu

## Problème

Sans système de menu structuré, la navigation USSD est gérée par des if/else éparpillés dans le code. Cela rend l'application difficile à maintenir, tester et faire évoluer.

```php
// ❌ Approche sans structure
if ($input === '1') {
    return "CON Solde: 5000 FCFA";
} elseif ($input === '2') {
    return "CON Entrez le montant:";
}
```

## Solution : Architecture Menu

Le système de menu utilise le **Composite pattern** combiné au **Registry pattern** :

- **Menu** : Un écran avec un titre et des options
- **MenuOption** : Une option sélectionnable (clé + label + action)
- **MenuManager** : Registre central qui stocke et résout les menus

### API Fluide (Builder Pattern)

```php
Ussd::menu('main')
    ->title("Bienvenue")
    ->option("1", "Solde", BalanceAction::class)
    ->option("2", "Transfert", TransferFlow::class)
    ->option("3", "Aide", nextMenu: 'help')
    ->option("4", "Quitter');
```

Chaque `option()` peut avoir :
- **Une action** : Classe invocable ou callable qui exécute la logique
- **Un prochain menu** : Navigation vers un autre menu

### Pourquoi éviter les if/else ?

1. **Maintenabilité** : Les menus sont déclaratifs, pas procéduraux
2. **Testabilité** : On peut tester un menu sans l'Engine
3. **Extensibilité** : Ajouter une option = ajouter une ligne
4. **Lisibilité** : La structure de l'application est visible en un coup d'œil
5. **Découplage** : La logique métier est dans des classes séparées

### Types d'options

| Type | Comportement | Exemple |
|------|-------------|---------|
| **Action** | Exécute une classe invocable | `BalanceAction::class` |
| **Navigation** | Redirige vers un autre menu | `nextMenu: 'help'` |
| **Flow** | Démarre un workflow multi-étapes | `TransferFlow::class` |
| **Callable** | Exécute une fonction anonyme | `fn($ctx) => ...` |

### Points d'extension

Pour ajouter un nouveau type d'action, étendez la logique dans `UssdEngine::processMenuSelection()`.
