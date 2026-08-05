## Payment management service

Microservice Laravel gérant les paiements (`payments`). Ne gère pas ses propres utilisateurs (authentification déléguée à `user-management-micro-service`) et peut interroger `order-management-micro-service` pour enrichir un paiement avec les détails de la commande associée.

### Configuration

Variables d'environnement spécifiques à ce service (`.env`) :

| Variable                        | Défaut                     | Description                                                                 |
| --------------------------------- | ----------------------------- | -------------------------------------------------------------------------- |
| `AUTH_SERVICE_BASE_URL`           | `http://127.0.0.1:2000`       | URL de base de `user-management-micro-service` (introspection de token)     |
| `AUTH_SERVICE_INTERNAL_API_KEY`   | `default_internal_api_key`    | Clé envoyée en `X-Internal-Api-Key` lors de l'appel d'introspection         |
| `ORDER_SERVICE_BASE_URL`          | `http://127.0.0.1:8000`       | URL de base de `order-management-micro-service`                             |
| `ORDER_SERVICE_INTERNAL_API_KEY`  | `default_internal_api_key`    | Réservée pour un futur appel authentifié par clé interne vers `order-management` (non utilisée actuellement, voir note sous `GET /payments/{id}/order`) |
| `CACHE_STORE`                     | `database`                    | Utilisé pour cacher les tokens validés et les listes de paiements par utilisateur |

`AUTH_SERVICE_INTERNAL_API_KEY` doit être identique à `INTERNAL_API_KEY` configurée dans `user-management-micro-service`.

### Authentification inter-services

Comme `order-management`, ce service délègue la validation des tokens Bearer à `user-management-micro-service` via le middleware `interservice.auth` (`App\Http\Middleware\AuthenticateMicroservice`) et `App\Services\AuthServiceClient`, qui appelle `POST {AUTH_SERVICE_BASE_URL}/api/auth/introspect`.

- Résultat mis en cache par token (`auth_service_token_<hash>`), 5 minutes par défaut.
- Token invalide/expiré/absent → `401 Unauthorized`.
- Utilisateur introspecté injecté dans `$request->attributes->get('user')`, consommé par `PaymentController`.

### Routes disponibles

Toutes les routes ci-dessous sont préfixées par `/api` (défini dans `bootstrap/app.php`).

#### Publique

| Méthode | URI | Description       |
| ------- | --- | -------------------- |
| GET     | `/` | Ping du service       |

#### Protégées par `interservice.auth` (token Bearer validé via `user-management-service`)

| Méthode | URI                            | Contrôleur                      | Description                                                    |
| ------- | -------------------------------- | ---------------------------------- | ------------------------------------------------------------------ |
| GET     | `/user`                          | closure                           | Retourne l'utilisateur introspecté (debug)                        |
| GET     | `/payments`                      | `PaymentController@index`         | Liste les paiements de l'utilisateur courant                      |
| POST    | `/payments/create`               | `PaymentController@store`         | Crée un paiement pour l'utilisateur courant                       |
| GET     | `/payments/{id}`                 | `PaymentController@show`          | Détail d'un paiement par id (utilisateur courant seulement)       |
| GET     | `/payments/{id}/order`           | `PaymentController@showOrder`     | Détail d'un paiement + détails de la commande associée            |
| GET     | `/payments/number/{paymentNumber}` | `PaymentController@getByNumber` | Détail d'un paiement par `payment_number` (utilisateur courant seulement) |

Header requis : `Authorization: Bearer <token>` (token émis par `user-management-micro-service`).

`show()`, `showOrder()` et `getByNumber()` filtrent par `user_id` de l'utilisateur courant — un utilisateur ne peut pas consulter le paiement d'un autre.

**`GET /payments`**

Liste mise en cache 60s par utilisateur (clé `user_payments_<user_id>`), invalidée automatiquement à la création d'un paiement (`store`). Réponse :
```json
{
  "message": "Payments retrieved successfully",
  "data_count": 1,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "order_id": 1,
      "payment_number": "PAY-...",
      "amount": "99.90",
      "currency": "CAD",
      "payment_method": "PayPal",
      "status": "Pending",
      "created_at": "2026-08-05T07:51:39+00:00",
      "updated_at": "2026-08-05T07:51:39+00:00"
    }
  ]
}
```

**`POST /payments/create`** — body attendu (`user_id` est déduit automatiquement de l'utilisateur authentifié) :
```json
{
  "order_id": "integer",
  "amount": "numeric",
  "currency": "string (3 caractères)",
  "payment_method": "credit_card | debit_card | paypal"
}
```
Réponse `201` : `{ message, data: <Payment> }`. `status` n'est pas fourni par le client : il prend la valeur par défaut de la colonne SQL (`pending`).

**`GET /payments/{id}`** — Réponse `200` : `{ message, data: <Payment> }`. `404` si le paiement n'existe pas ou n'appartient pas à l'utilisateur courant.

**`GET /payments/number/{paymentNumber}`** — Réponse `200` : `{ message, data: <Payment> }`. `404` (`Payment not found: <paymentNumber>`) si le paiement n'existe pas ou n'appartient pas à l'utilisateur courant.

**`GET /payments/{id}/order`**

Récupère le paiement puis appelle `GET {ORDER_SERVICE_BASE_URL}/api/orders/{order_id}` sur `order-management-micro-service`, en repassant le **même token Bearer** que celui reçu par ce service (pas de clé interne dédiée). Ça fonctionne parce que `order-management` filtre aussi ses commandes par `user_id` : le token doit donc appartenir au même utilisateur propriétaire à la fois du paiement et de la commande.

Réponse `200` :
```json
{
  "message": "Order details retrieved successfully",
  "payment": {
    "id": 1,
    "payment_number": "PAY-...",
    "order_id": 1,
    "user_id": 1,
    "amount": "99.90",
    "currency": "CAD",
    "payment_method": "paypal",
    "status": "pending",
    "created_at": "...",
    "updated_at": "...",
    "order_details": { "message": "Order retrieved successfully", "data": { } }
  }
}
```
`404` si le paiement n'existe pas ou n'appartient pas à l'utilisateur courant. Si l'appel vers `order-management` échoue (commande introuvable, service indisponible, etc.), la réponse propage le même code HTTP et le corps brut de l'erreur (`{ message, error }`).

> `ORDER_SERVICE_INTERNAL_API_KEY` est déclarée en config mais n'est pas encore utilisée par `showOrder()` — l'appel ne passe que le token Bearer de l'utilisateur, pas de header `X-Internal-Api-Key`. À utiliser si `order-management` exige un jour cette clé pour les appels service-à-service purs (sans utilisateur).

### Enums

- **`App\Enums\PaymentMethod`** (backed `string`) : `CREDIT_CARD = 'credit_card'`, `DEBIT_CARD = 'debit_card'`, `PAYPAL = 'paypal'` — aligné sur la colonne SQL `payments.payment_method`, identique à `order-management`.
- **`App\Enums\PaymentStatus`** (backed `string`) : `PENDING = 'pending'`, `COMPLETED = 'completed'`, `FAILED = 'failed'`, `REFUNDED = 'refunded'` — aligné sur la colonne SQL `payments.status`.

### Autres routes (framework, non applicatives)

| Méthode  | URI  | Description                               |
| -------- | ---- | -------------------------------------------- |
| GET/HEAD | `up` | Health check (utilisé par les orchestrateurs) |
