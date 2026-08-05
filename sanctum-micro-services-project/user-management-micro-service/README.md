## User management service

### Routes disponibles

Toutes les routes ci-dessous sont préfixées par `/api` (défini dans `bootstrap/app.php`).

#### Publiques

| Méthode | URI         | Contrôleur                | Description                                                  |
| ------- | ----------- | ------------------------- | ------------------------------------------------------------ |
| GET     | `/`         | —                         | Ping du service (`User Management Micro Service is running`) |
| POST    | `/register` | `AuthController@register` | Crée un utilisateur et retourne un token Bearer              |
| POST    | `/login`    | `AuthController@login`    | Authentifie un utilisateur et retourne un token Bearer       |

**`POST /register`** — body attendu :

```json
{
    "name": "string",
    "email": "string (unique)",
    "username": "string (unique)",
    "password": "string (min 8, confirmed)",
    "password_confirmation": "string"
}
```

Réponse `201` : `{ message, user, token, token_type: "Bearer" }`

**`POST /login`** — body attendu :

```json
{
    "email": "string",
    "password": "string"
}
```

Réponse `200` : `{ message, user, token, token_type: "Bearer" }`. `401` si identifiants invalides.

#### Protégées par `auth:sanctum` (token Bearer requis)

| Méthode | URI       | Contrôleur              | Description                                          |
| ------- | --------- | ----------------------- | ---------------------------------------------------- |
| POST    | `/logout` | `AuthController@logout` | Révoque le token courant                             |
| GET     | `/me`     | `AuthController@me`     | Retourne l'utilisateur authentifié (sans `is_admin`) |
| GET     | `/user`   | closure                 | Retourne l'utilisateur authentifié (brut)            |

Header requis : `Authorization: Bearer <token>`

#### Protégées par `auth:sanctum` + `is_admin`

| Méthode | URI      | Contrôleur             | Description                 |
| ------- | -------- | ---------------------- | --------------------------- |
| GET     | `/users` | `UserController@index` | Liste tous les utilisateurs |

Nécessite un utilisateur authentifié avec `is_admin = true`, sinon `403 Unauthorized`.

#### Route interne (service-à-service)

| Méthode | URI                | Contrôleur                  | Description                                          |
| ------- | ------------------ | --------------------------- | ---------------------------------------------------- |
| POST    | `/auth/introspect` | `AuthController@introspect` | Vérifie la validité d'un token et retourne ses infos |

Protégée par le middleware `internal.api.auth` : nécessite le header `X-Internal-API-Key` égal à la valeur de `INTERNAL_API_KEY` (config `app.internal_api_key`). Destinée aux autres microservices (order-management, payment-management) pour valider un token émis par ce service, pas aux clients finaux.

Body attendu :

```json
{}
```

Le token est lu depuis le header `Authorization: Bearer <token>` (pas depuis le body).

Réponse `200` :

```json
{
    "valid": true,
    "token": "string",
    "abilities": ["..."],
    "expires_at": "datetime|null",
    "user": {}
}
```

`401` si token absent, invalide ou expiré.

### Autres routes (framework, non applicatives)

| Méthode  | URI                   | Statut     | Description                                   |
| -------- | --------------------- | ---------- | --------------------------------------------- |
| GET/HEAD | `sanctum/csrf-cookie` | Désactivée | Émission du cookie CSRF                       |
| GET/HEAD | `storage/{path}`      | Désactivée | Sert les fichiers du disque `local`           |
| PUT      | `storage/{path}`      | Désactivée | Upload sur le disque `local`                  |
| GET/HEAD | `up`                  | Active     | Health check (utilisé par les orchestrateurs) |

Ces routes sont enregistrées automatiquement par des service providers du framework (Sanctum, Filesystem), pas par `routes/api.php`. Elles ne servent à rien dans ce service (auth 100% Bearer, pas d'upload de fichiers exposé), donc elles ont été coupées à la source plutôt que filtrées après coup :

- **`sanctum/csrf-cookie`** — n'a de sens que pour l'auth par cookie de session (SPA sur le même domaine). Ce service n'utilisant que des tokens Bearer (`stateful` vide dans `config/sanctum.php`), la route est inutile. Désactivée via `config/sanctum.php` :

    ```php
    'routes' => false,
    ```

- **`storage/{path}` (GET et PUT)** — servent/uploadent les fichiers du disque `local` directement via une route HTTP. Ce service ne stocke ni ne sert aucun fichier, la route est donc coupée via `config/filesystems.php`, disque `local` :

    ```php
    'local' => [
        ...
        'serve' => false,
    ],
    ```

- **`up`** — laissée active volontairement : c'est le health check utilisé par l'orchestrateur (Docker/Kubernetes/load balancer) pour savoir si le service est vivant. La désactiver casserait la supervision. Configurable dans `bootstrap/app.php` via `health: '/up'`.
