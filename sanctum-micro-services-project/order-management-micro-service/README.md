## Order management service

Microservice Laravel gérant les commandes (`orders`). Ne gère pas ses propres utilisateurs : l'authentification est déléguée à `user-management-micro-service` via un appel interne (introspection de token).

### Configuration

Variables d'environnement spécifiques à ce service (`.env`) :

| Variable                       | Défaut                       | Description                                                              |
| ------------------------------- | ----------------------------- | -------------------------------------------------------------------------- |
| `AUTH_SERVICE_BASE_URL`         | `http://127.0.0.1:2000`       | URL de base de `user-management-micro-service`                            |
| `AUTH_SERVICE_INTERNAL_API_KEY` | `default_internal_api_key`    | Clé envoyée en `X-Internal-Api-Key` lors de l'appel d'introspection       |
| `CACHE_STORE`                   | `database`                    | Utilisé pour cacher les tokens validés et les listes de commandes par utilisateur |

Cette clé doit être identique à `INTERNAL_API_KEY` configurée dans `user-management-micro-service` (voir son README).

### Authentification inter-services

Ce service ne valide pas les tokens Bearer lui-même : le middleware `interservice.auth` (`App\Http\Middleware\AuthenticateMicroservice`) délègue la vérification à `user-management-micro-service` via `App\Services\AuthServiceClient`, qui appelle `POST {AUTH_SERVICE_BASE_URL}/api/auth/introspect`.

- Le résultat de l'introspection est mis en cache par token (`auth_service_token_<hash>`) pour `services.auth_service.cache_duration` (5 minutes par défaut), pour éviter un appel HTTP à chaque requête.
- Si le token est invalide/expiré/absent → `401 Unauthorized`.
- Si valide, l'utilisateur introspecté est injecté dans la requête via `$request->attributes->get('user')`, consommé ensuite par `OrderController`.

### Routes disponibles

Toutes les routes ci-dessous sont préfixées par `/api` (défini dans `bootstrap/app.php`).

#### Publiques

| Méthode | URI | Description                          |
| ------- | --- | -------------------------------------- |
| GET     | `/` | Ping du service (middleware `web`)     |

#### Protégée par `auth:sanctum`

| Méthode | URI     | Description                                |
| ------- | ------- | --------------------------------------------- |
| GET     | `/user` | Retourne l'utilisateur Sanctum local (debug)  |

> Cette route utilise le guard Sanctum local du service, distinct du flux d'authentification inter-services ci-dessus. Ce service n'ayant pas ses propres utilisateurs/tokens Sanctum en pratique, elle sert surtout de route de diagnostic.

#### Protégées par `interservice.auth` (token Bearer validé via `user-management-service`)

| Méthode | URI             | Contrôleur              | Description                                |
| ------- | --------------- | ------------------------ | --------------------------------------------- |
| GET     | `/orders`       | `OrderController@index`  | Liste les commandes de l'utilisateur courant  |
| POST    | `/orders/create` | `OrderController@store` | Crée une commande pour l'utilisateur courant  |
| GET     | `/orders/{id}`  | `OrderController@show`  | Détail d'une commande par id                  |

Header requis : `Authorization: Bearer <token>` (token émis par `user-management-micro-service`).

**`GET /orders`**

Liste mise en cache 60s par utilisateur (clé `user_orders_<user_id>`), invalidée automatiquement à la création d'une commande (`store`). Réponse :
```json
{
  "message": "Orders retrieved successfully",
  "data_count": 138,
  "data": [
    {
      "id": 1,
      "user_id": 11,
      "order_number": "ORD-...",
      "grand_total": "CA$858.39",
      "currency": "CAD",
      "shipping_address": "string",
      "billing_address": "string",
      "payment_method": "Credit Card",
      "created_at": "2026-08-05T06:47:00+00:00",
      "updated_at": "2026-08-05T06:49:35+00:00"
    }
  ]
}
```

**`POST /orders/create`** — body attendu (`user_id` est déduit automatiquement de l'utilisateur authentifié, pas besoin de l'envoyer) :
```json
{
  "total_amount": "numeric",
  "tax_amount": "numeric",
  "shipping_amount": "numeric",
  "discount_amount": "numeric (optionnel)",
  "currency": "string (3 caractères)",
  "shipping_address": "string",
  "billing_address": "string",
  "payment_method": "credit_card | debit_card | paypal"
}
```
Réponse `201` : `{ message, data: <Order> }`. `grand_total` est calculé côté serveur (`total + tax + shipping - discount`), toute valeur envoyée est ignorée.

**`GET /orders/{id}`** — Réponse `200` : `{ message, data: <Order> }`. `404` si la commande n'existe pas.

> ⚠️ `show()` ne filtre pas par utilisateur courant : n'importe quel appelant authentifié peut consulter n'importe quelle commande par id (IDOR potentiel).

### Enums

- **`App\Enums\PaymentMethod`** (backed `string`) : `CREDIT_CARD = 'credit_card'`, `DEBIT_CARD = 'debit_card'`, `PAYPAL = 'paypal'` — aligné sur la colonne SQL `orders.payment_method`.
- **`App\Enums\OrderStatus`** (backed `string`) : `PENDING = 'pending'`, `PROCESSING = 'processing'`, `COMPLETED = 'completed'`, `CANCELLED = 'cancelled'` — aligné sur la colonne SQL `orders.status`.

### Autres routes (framework, non applicatives)

| Méthode  | URI  | Description                                    |
| -------- | ---- | ------------------------------------------------- |
| GET/HEAD | `up` | Health check (utilisé par les orchestrateurs)      |
