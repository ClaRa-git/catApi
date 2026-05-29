# symfony-api

API REST construite avec Symfony 7.4. Elle consomme l'API publique [The Cat API](https://api.thecatapi.com/v1/breeds) et retourne les données formatées sur les races de chats.

## Technologies

- **PHP** 8.3
- **Symfony** 7.4
- **Doctrine ORM** + **Doctrine Migrations**
- **PostgreSQL** 16 (environnement local via `compose.yaml`)
- **PHPUnit** 12 — tests
- **Symfony HttpClient** — appels vers The Cat API

## Structure

```
symfony-api/
├── src/
│   └── Controller/
│       └── CatController.php   # appel The Cat API + formatage
├── tests/
│   └── CatControllerTest.php
├── migrations/
├── config/
├── Dockerfile
└── compose.yaml                # PostgreSQL (local uniquement)
```

## Variables d'environnement

Copiez `.env.dev` en `.env` et adaptez si nécessaire :

```bash
cp .env.dev .env
```

Variables clés :

| Variable | Description |
|---|---|
| `DATABASE_URL` | DSN PostgreSQL |
| `APP_ENV` | `dev` ou `prod` |
| `APP_SECRET` | Clé secrète Symfony |

## Lancer en local (sans Docker)

Prérequis : PHP 8.3, Composer, Symfony CLI.

```bash
cd symfony-api
composer install

cp .env.dev .env

# Démarrer PostgreSQL (optionnel, si vous utilisez Doctrine)
docker compose up -d database

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Démarrer le serveur
symfony server:start
# ou
php -S 0.0.0.0:8000 -t public
```

Le serveur démarre sur le port **8000**.

## Lancer avec Docker

```bash
# depuis la racine du projet
docker compose up --build symfony
```

## Tests

```bash
php bin/phpunit
```

## Endpoints

| Méthode | Route | Description |
|---|---|---|
| GET | `/cats` | Liste des races (name, origin, temperament, description) |

Exemple de réponse :

```json
[
  {
    "name": "Abyssinian",
    "origin": "Egypt",
    "temperament": "Active, Energetic, Independent, Intelligent, Gentle",
    "description": "The Abyssinian is easy to care for..."
  }
]
```
