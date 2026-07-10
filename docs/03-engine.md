# Étape 4 : USSD Engine

## Problème

Une application USSD doit orchestrer plusieurs étapes à chaque requête : parser la requête, charger la session, déterminer l'état courant, exécuter la logique, sauvegarder l'état, et retourner la réponse. Sans architecture claire, ce pipeline devient un code spaghetti.

## Solution : UssdEngine

Le `UssdEngine` est le **moteur d'orchestration** central. Il suit le **Pipeline pattern** : chaque étape est une phase clairement délimitée.

### Pipeline de traitement

```
Requête entrante (HTTP)
    │
    ▼
┌─────────────────────────────┐
│ 1. Parse Request (Driver)   │  Transforme HTTP → UssdRequest
└─────────────────────────────┘
    │
    ▼
┌─────────────────────────────┐
│ 2. Load/Create Session      │  SessionManager → UssdSession
└─────────────────────────────┘
    │
    ▼
┌─────────────────────────────┐
│ 3. Check Expiration         │  SessionExpiredException?
└─────────────────────────────┘
    │
    ▼
┌─────────────────────────────┐
│ 4. Resolve State            │  Menu ou Flow ?
└─────────────────────────────┘
    │
    ▼
┌─────────────────────────────┐
│ 5. Execute Action           │  Menu::render() / Flow::handle()
└─────────────────────────────┘
    │
    ▼
┌─────────────────────────────┐
│ 6. Save Session             │  SessionManager::save()
└─────────────────────────────┘
    │
    ▼
┌─────────────────────────────┐
│ 7. Return Response          │  UssdResponse → HTTP Response
└─────────────────────────────┘
```

### Principe de responsabilité unique

Chaque composant a un rôle précis :

| Composant | Responsabilité |
|-----------|---------------|
| `UssdDriver` | Parser la requête gateway → UssdRequest |
| `SessionManager` | Cycle de vie des sessions |
| `MenuManager` | Registre et résolution des menus |
| `Menu` | Définition et rendu d'un écran |
| `Flow` | Workflow multi-étapes |
| `UssdEngine` | **Orchestrer** les composants ci-dessus |

L'Engine **ne contient pas de logique métier**. Il se contente de coordonner.

### Gestion des erreurs

L'Engine capture trois types d'erreurs :

1. **Session expirée** → Réponse END avec message approprié
2. **Menu introuvable** → Exception technique (développeur)
3. **Erreur système** → Réponse END générique + log

```php
// Exemple d'utilisation

// Dans un ServiceProvider
Ussd::menu('main')
    ->title('Bienvenue')
    ->option('1', 'Solde', BalanceAction::class)
    ->option('2', 'Transfert', TransferFlow::class);

// Dans le controller
public function __invoke(Request $request)
{
    $ussdRequest = UssdRequest::fromHttpRequest($request, $this->driver);
    $ussdResponse = Ussd::handle($ussdRequest);

    return $ussdResponse->toHttpResponse();
}
```
