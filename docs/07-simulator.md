# Étape 8 : Simulateur USSD

## Problème

Tester un service USSD avec une vraie passerelle télécom est lent et coûteux :
- Il faut un code USSD activé
- Chaque test consomme des ressources opérateur
- Le débogage est difficile sans voir l'interface

## Solution : Simulateur Web

Le package inclut un simulateur USSD qui émulé l'interface d'un téléphone.

### Fonctionnement

```
Navigateur (Simulateur)
    │
    ├── GET /ussd/simulator → Affiche l'interface téléphone
    │
    └── POST /ussd/simulator
              │
              ▼
         SimulatorController
              │
              ├── Crée une Request simulée
              ├── Appelle UssdEngine::handle()
              └── Affiche le résultat
```

### Différence entre simulateur et vraie gateway

| Aspect | Vraie Gateway | Simulateur |
|--------|---------------|------------|
| Source | Passerelle télécom | Navigateur web |
| Session | sessionId réel | sessionId généré (timestamp) |
| Réseau | SMS/GSM | HTTP simple |
| Coût | Payant | Gratuit |
| Débogage | Logs uniquement | Interface visuelle |
| Vitesse | 2-5 secondes/requête | Instantané |

### Activation

```env
# .env
USSD_SIMULATOR_ENABLED=true
```

Ou dans `config/ussd.php` :

```php
'simulator_enabled' => env('USSD_SIMULATOR_ENABLED', false),
```

⚠️ **Toujours désactiver en production.**

### Utilisation

1. Démarrer l'application Laravel
2. Ouvrir `http://localhost:8000/ussd/simulator`
3. L'interface téléphone s'affiche
4. Naviguer dans les menus comme sur un vrai téléphone
5. Le champ "text" simule les touches

### Interface

```
┌─────────────────────┐
│   USSD Simulator    │
│                     │
│  CON Welcome        │
│  1. Balance         │
│  2. Transfer        │
│                     │
├─────────────────────┤
│ [Input: 1     ] [→] │
│                     │
│ Session: abc123     │
└─────────────────────┘
```
