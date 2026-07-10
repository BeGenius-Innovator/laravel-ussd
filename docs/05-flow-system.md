# Étape 6 : Système de Flow

## Problème

Certains services USSD nécessitent plusieurs étapes : collecter un destinataire, un montant, un PIN, puis confirmer. Sans structure, chaque étape est un sous-menu ou du code ad-hoc, rendant le flux difficile à suivre.

## Solution : Flow (Machine à États)

Le système de Flow implémente le **State pattern** (patron d'état) :

- **Flow** : Machine à états finie qui contient des Steps
- **Step** : Un état dans la machine, avec sa logique et validation
- **StepResult** : Résultat d'un step (prochain step, complétion, ou erreur)

### Architecture

```
Flow "transfer"
    │
    ├── Step 1: ask_recipient  → "Entrez le numéro du destinataire:"
    │         │
    │         ▼
    ├── Step 2: ask_amount     → "Entrez le montant:"
    │         │
    │         ▼
    ├── Step 3: ask_pin       → "Entrez votre code PIN:"
    │         │
    │         ▼
    └── Step 4: confirm       → "Transfert effectué!" (END)
```

### Machine à états

Un Flow est une **Finite State Machine (FSM)** :

1. Chaque Step est un **état**
2. L'entrée utilisateur déclenche une **transition**
3. Le flow se termine quand il atteint un état **terminal**

```
           ┌──────────┐
           │ Start    │
           └────┬─────┘
                │
                ▼
           ┌──────────┐
           │ Step 1   │ ← Validation possible avec retour
           └────┬─────┘
                │
                ▼
           ┌──────────┐
           │ Step 2   │
           └────┬─────┘
                │
                ▼
           ┌──────────┐
           │ Step N   │
           └────┬─────┘
                │
                ▼
           ┌──────────┐
           │ Complete │ (END)
           └──────────┘
```

### Création d'un Flow

```php
class TransferFlow extends Flow
{
    public function __construct()
    {
        parent::__construct('transfer', 'ask_recipient');

        $this->addStep(new class extends Step {
            public function name(): string
            {
                return 'ask_recipient';
            }

            public function handle(UssdContext $context): StepResult
            {
                return StepResult::next(
                    'ask_amount',
                    UssdResponse::continue('Entrez le numéro du destinataire:')
                );
            }
        });

        $this->addStep(new class extends Step {
            public function name(): string
            {
                return 'ask_amount';
            }

            public function validate(UssdContext $context): ?string
            {
                $amount = $context->input();
                if (!is_numeric($amount) || $amount <= 0) {
                    return 'Montant invalide. Entrez un nombre positif.';
                }
                return null;
            }

            public function handle(UssdContext $context): StepResult
            {
                $context->session()->set('amount', $context->input());

                return StepResult::next(
                    'confirm',
                    UssdResponse::continue(
                        "Confirmez le transfert:\n".
                        "Vers: ".$context->session()->get('recipient')."\n".
                        "Montant: ".$context->input()." FCFA\n".
                        "1. Confirmer\n".
                        "2. Annuler"
                    )
                );
            }
        });
    }
}
```

### Résultats de Step

| Type | Description |
|------|-------------|
| `StepResult::next()` | Passe à l'étape suivante |
| `StepResult::complete()` | Termine le flow (généralement END) |
| `StepResult::stay()` | Reste sur la même étape (ex: erreur validation) |

### Points forts

1. **Séparation claire** : Chaque étape est une classe isolée
2. **Testabilité** : On teste chaque étape indépendamment
3. **Validation intégrée** : Chaque étape peut définir ses règles
4. **Réutilisabilité** : Les steps peuvent être réutilisés entre flows
5. **Traçabilité** : La session stocke l'étape courante
