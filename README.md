# h26-start-with-laravel-micro-service

Comprendre les microservices avec Laravel.

Ce dépôt contient trois microservices Laravel indépendants (chacun avec sa propre base de données, ses propres migrations et son propre `composer.json`), situés dans `sanctum-micro-services-project/` :

| Service                          | Rôle                                              | Port (dev) | README                                                                 |
| --------------------------------- | -------------------------------------------------- | ---------- | ------------------------------------------------------------------------ |
| `user-management-micro-service`   | Authentification (register/login/logout), gestion des utilisateurs, source de vérité des tokens Sanctum | `2000`     | [README](sanctum-micro-services-project/user-management-micro-service/README.md) |
| `order-management-micro-service`  | Gestion des commandes (`orders`)                    | `8000`     | [README](sanctum-micro-services-project/order-management-micro-service/README.md) |
| `payment-management-micro-service` | Gestion des paiements (`payments`), liés aux commandes | libre (ex: `8001`) | [README](sanctum-micro-services-project/payment-management-micro-service/README.md) |

## Architecture

- **`user-management`** est le seul service à posséder des utilisateurs et à émettre des tokens Sanctum (`POST /api/register`, `POST /api/login`). Il expose aussi une route interne `POST /api/auth/introspect` (protégée par une clé `X-Internal-Api-Key`) permettant aux autres services de valider un token sans dupliquer la logique d'authentification.
- **`order-management`** et **`payment-management`** n'ont pas d'utilisateurs propres. Leurs routes API sont protégées par un middleware `interservice.auth` qui délègue la validation du token Bearer à `user-management` via `/api/auth/introspect` (résultat mis en cache 5 min par token pour éviter un appel réseau à chaque requête).
- **`payment-management`** peut en plus interroger `order-management` (`GET /payments/{id}/order`) pour enrichir un paiement avec les détails de la commande associée, en repassant le même token Bearer de l'utilisateur.

```
Client
  │  POST /api/login (user-management:2000)
  ▼
token Bearer
  │
  ├──► order-management:8000   (interservice.auth ──► introspect sur user-management)
  │
  └──► payment-management:8001 (interservice.auth ──► introspect sur user-management)
                                        │
                                        └──► GET /orders/{id} sur order-management (même token)
```

Un seul token, obtenu une fois auprès de `user-management`, est donc réutilisable tel quel sur les trois services.

## Démarrage rapide

Pour chaque service (dans son propre dossier) :

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve --port=<port ci-dessus>
```

Variables d'environnement à faire correspondre entre services (mêmes valeurs des deux côtés) :

| Variable                          | Définie dans                        | Doit correspondre à                                      |
| ------------------------------------ | -------------------------------------- | ------------------------------------------------------------ |
| `INTERNAL_API_KEY`                   | `user-management` (`config/app.php`)   | `AUTH_SERVICE_INTERNAL_API_KEY` dans les deux autres services |
| `AUTH_SERVICE_BASE_URL`              | `order-management`, `payment-management` | URL réelle de `user-management` (`http://127.0.0.1:2000` par défaut) |
| `ORDER_SERVICE_BASE_URL`             | `payment-management`                   | URL réelle de `order-management` (`http://127.0.0.1:8000` par défaut) |

Chaque service utilise SQLite par défaut et le driver de cache `database` (utilisé à la fois pour le cache applicatif — listes paginées, résultats d'introspection de token — et pour les locks). Voir le README de chaque service pour le détail des routes, des enums et des formats de requête/réponse.
