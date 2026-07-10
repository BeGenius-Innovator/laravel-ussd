# Étape 9 : Tests

## Stratégie de test

Le package utilise **PHPUnit** avec **Orchestra Testbench** pour créer un environnement Laravel isolé.

### Pourquoi Orchestra Testbench ?

- Permet de tester un package sans application Laravel complète
- Fournit un conteneur Laravel minimal
- Charge automatiquement les Service Providers
- Supporte les migrations, config, etc.

### Tests inclus

#### 1. UssdResponseTest

Vérifie le format des réponses CON et END :

```php
// CON: "CON Welcome\n1. Balance\n"
// END: "END Thank you\n"
// Conversion HTTP avec Content-Type: text/plain
```

#### 2. UssdRequestTest

Vérifie le parsing des requêtes entrantes :

- Extraction du dernier input (`1*2*5000` → `5000`)
- Détection nouvelle session (text vide)
- Champs alternatifs (`msisdn`, `session_id`, `operator`)

#### 3. MenuTest (intégration)

Vérifie le système de menus :

- API fluide de création
- Recherche d'option par clé
- Navigation vs Action detection
- Rendu correct des options

#### 4. SessionManagerTest

Vérifie la gestion des sessions :

- Création/chargement
- Stockage/lecture des données
- Expiration
- Destruction

#### 5. FlowTest

Vérifie les workflows multi-étapes :

- Transitions step → step
- Completion du flow
- Validation avec erreur
- Step inexistant

#### 6. UssdEngineTest (intégration)

Vérifie le pipeline complet :

- Nouvelle session → Menu d'accueil
- Navigation dans les sous-menus
- Option invalide → Re-rendu du menu
- Retour au menu précédent

### Exécution des tests

```bash
composer test
# ou
./vendor/bin/phpunit
```

### Structure des tests

```
tests/
├── TestCase.php              ← Base (charge le package)
├── UssdResponseTest.php      ← Tests réponse
├── UssdRequestTest.php       ← Tests requête
├── MenuTest.php              ← Tests menus
├── SessionManagerTest.php    ← Tests sessions
├── FlowTest.php              ← Tests flows
└── UssdEngineTest.php        ← Tests intégration
```

### Configuration test

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="testing"/>
</php>
```

Le driver de session est automatiquement basculé sur `array` pour les tests (pas de base de données nécessaire).
