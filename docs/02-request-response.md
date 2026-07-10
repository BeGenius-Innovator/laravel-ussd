# Étape 2 & 3 : Requête et Réponse USSD

## Problème

Les passerelles USSD (Orange, Moov, Africa's Talking, etc.) envoient toutes des payloads HTTP différents. Sans abstraction, le code métier serait pollué par des détails d'implémentation propres à chaque opérateur.

## Solution : UssdRequest

`UssdRequest` encapsule la requête entrante dans un objet typé et unifié.

### Différence entre HTTP Request et USSD Request

| Aspect | HTTP Request | USSD Request |
|--------|-------------|--------------|
| Source | Navigateur/Client HTTP | Passerelle télécom |
| Format | JSON/Form Data | Payload opérateur |
| Session | Cookies/Session Laravel | sessionId fourni par gateway |
| Contexte | Page web | Menu/Screen |
| Entrée | Variable | Text concaténé ("1\*2\*5000") |

```php
// Payload typique d'une passerelle
{
    "sessionId": "ABC123",
    "phoneNumber": "22670000000",
    "network": "ORANGE",
    "text": "1*5000",
    "serviceCode": "*123#"
}
```

### Architecture

```
HTTP Request (gateway)
    │
    ▼
UssdDriver::parseRequest()  ← Implémentation spécifique
    │
    ▼
UssdRequest                 ← Objet unifié
    │
    ▼
UssdEngine::handle()
```

### Méthodes clés

- `input()` : Retourne le dernier segment après `*`
- `inputs()` : Retourne tous les segments en tableau
- `isNewSession()` : Vrai si text est vide (première requête)
- `fromHttpRequest()` : Factory method utilisant le driver

## Solution : UssdResponse

### Protocole USSD : CON vs END

Le protocole USSD utilise deux types de réponses :

**CON (Continue)** : La session reste ouverte. L'utilisateur voit le message et peut répondre.

```
CON Bienvenue
1. Consulter solde
2. Transférer argent
```

**END (End)** : La session est terminée. L'utilisateur voit le message puis l'écran USSD se ferme.

```
END Merci d'avoir utilisé notre service.
```

Les 3 premiers caractères de la réponse sont critiques : la passerelle les lit pour déterminer le comportement. Un mauvais préfixe peut causer des erreurs.

```php
// Création
UssdResponse::continue("Message");  // → "CON Message\n"
UssdResponse::end("Message");       // → "END Message\n"

// Conversion HTTP
$response->toHttpResponse();        // → Response 200, Content-Type: text/plain
```

### Pourquoi une abstraction ?

1. **Normalisation** : Toutes les réponses suivent le même format
2. **Testabilité** : On peut tester les réponses sans passerelle
3. **Évolutivité** : Si un opérateur nécessite un format spécial, on peut étendre
4. **Sécurité** : Évite les erreurs de formatage (mauvais préfixe CON/END)
