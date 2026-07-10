# Étape 1 : Service Provider, Facade et Configuration

## Problème

Un package Laravel doit s'intégrer proprement dans le cycle de vie d'une application Laravel. Sans mécanisme d'enregistrement, l'application ne peut pas découvrir automatiquement les services proposés par le package.

## Solution

Laravel fournit les **Service Providers** comme point d'entrée standard pour les packages. Un Service Provider a deux responsabilités :

1. **Register()** : Lier des classes dans le conteneur de services (IoC Container)
2. **Boot()** : Exécuter des actions après que tous les providers sont enregistrés

## Choix techniques

### Service Provider (`UssdServiceProvider`)

Le provider enregistre :

- **UssdDriver Contract → DefaultUssdDriver** : Le driver qui parse les requêtes entrantes des différentes passerelles
- **SessionDriver Contract → DatabaseSessionDriver** : Le driver de stockage des sessions USSD
- **SessionManager (singleton)** : Gère le cycle de vie des sessions (création, récupération, expiration)
- **MenuManager (singleton)** : Enregistre et résout les menus
- **UssdEngine (singleton, clé `ussd.engine`)** : Le moteur central qui orchestre requête → réponse

Pourquoi des singletons ?
- Une session USSD étant liée à une requête HTTP unique, le contexte ne change pas
- Le MenuManager doit accumuler les définitions de menus avant de les résoudre
- L'Engine maintient l'état de l'orchestration

### Auto-Discovery Laravel

Dans `composer.json`, la section `extra.laravel.providers` permet à Laravel ≥5.5 de découvrir automatiquement le provider :

```json
"extra": {
    "laravel": {
        "providers": [
            "BeGenius\\Ussd\\UssdServiceProvider"
        ]
    }
}
```

### Facade (`Ussd`)

La Facade `BeGenius\Ussd\Facades\Ussd` donne accès à l'instance `ussd.engine` via un appel statique :

```php
Ussd::handle($request);
```

C'est une syntaxe plus courte que `app('ussd.engine')->handle($request)`. La Facade ne fait que rediriger l'appel statique vers l'instance réelle résolue par le conteneur.

### Configuration (`config/ussd.php`)

Fichier publié via `php artisan vendor:publish --tag=ussd-config`. Contient tous les réglages :

- `default_driver` : Driver USSD à utiliser (ex: orange, moov, default)
- `session_driver` : Stockage des sessions (database, redis, array)
- `session_lifetime` : Durée de vie d'une session en minutes
- `session_table` : Nom de la table de sessions
- `routes_prefix` : Préfixe des URLs USSD
- `default_menu` : Menu d'accueil
- `simulator_enabled` : Activer/désactiver le simulateur
- `max_input_length` : Longueur max des entrées (182 caractères)

La méthode `mergeConfigFrom()` dans `register()` permet aux valeurs du package de servir de défauts, tout en laissant l'utilisateur les surcharger via son propre fichier `config/ussd.php`.

## Exemple d'utilisation

```php
// L'utilisateur n'a rien à faire (auto-discovery)
// Optionnel : publier la configuration
php artisan vendor:publish --tag=ussd-config

// Utilisation via Facade
use BeGenius\Ussd\Facades\Ussd;

Ussd::menu('main')
    ->title('Menu principal')
    ->option('1', 'Solde', BalanceAction::class);
```

## Architecture

```
UssdServiceProvider
    │
    ├── register()
    │   ├── mergeConfigFrom() → ussd.php
    │   ├── UssdDriver (interface → implémentation)
    │   ├── SessionDriver (interface → implémentation)
    │   ├── SessionManager (singleton)
    │   ├── MenuManager (singleton)
    │   └── UssdEngine (singleton, 'ussd.engine')
    │
    └── boot()
        ├── vendor:publish (config, migrations)
        ├── loadMigrationsFrom()
        └── loadRoutesFrom()
```
