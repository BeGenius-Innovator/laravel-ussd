# Étape 7 : Gestion de Session

## Problème

Une interaction USSD implique plusieurs requêtes HTTP consécutives. Sans mécanisme de session, chaque requête est isolée et ne peut pas savoir où l'utilisateur s'est arrêté.

## Solution : SessionManager

### Pourquoi une session USSD est nécessaire

```
Temps
│
├── Requête 1 : text=""          → Session créée, menu affiché
├── Requête 2 : text="1"         → Session lue, navigation
├── Requête 3 : text="1*5000"    → Session lue, données stockées
├── Requête 4 : text="1*5000*1234" → Session lue, validation
│
└── Session supprimée ou expirée
```

La session stocke :
- **session_id** : Identifiant unique fourni par la passerelle
- **phone_number** : MSISDN de l'utilisateur
- **current_state** : Menu ou étape de flow en cours
- **data** : Données temporaires (montant, destinataire, etc.)

### Durée de vie USSD

Les sessions USSD ont une durée de vie très courte :
- Généralement **60 à 120 secondes** d'inactivité
- Après expiration, l'utilisateur doit recomposer le code
- Le package permet de configurer cette durée via `session_lifetime`

### Architecture

```
SessionManager
    │
    ├── loadOrCreate() → Cherche ou crée une session
    ├── save()         → Persiste l'état
    ├── destroy()      → Supprime (après END)
    │
    ▼
SessionDriver (Interface)
    │
    ├── DatabaseSessionDriver  ← Production (recommandé)
    │     - Table: ussd_sessions
    │     - Supporte load-balanced environments
    │
    ├── ArraySessionDriver    ← Tests uniquement
    │     - Stockage mémoire
    │     - Perdu entre requêtes
    │
    └── (Votre driver Redis, etc.)
```

### Session Data

La session peut stocker des données temporaires pendant un flow :

```php
$context->session()->set('amount', '5000');
$context->session()->set('recipient', '22671234567');

$amount = $context->session()->get('amount');
$hasAmount = $context->session()->has('amount');
$context->session()->forget('amount');
```

### Migration

Le package inclut une migration pour créer la table `ussd_sessions` :

```bash
php artisan vendor:publish --tag=ussd-migrations
php artisan migrate
```

La table contient :
- `id` : Clé primaire
- `session_id` : Identifiant unique (indexé)
- `phone_number` : Numéro de l'abonné (indexé)
- `network` : Code opérateur
- `current_state` : État courant
- `data` : JSON de données temporaires
- `created_at` / `updated_at` : Timestamps
