# symfony-api

API REST construite avec **Symfony 7.4**. Elle a deux responsabilités :

1. **Consommer The Cat API** ([thecatapi.com](https://api.thecatapi.com/v1/breeds)) pour récupérer et formater les races de chats ;
2. **Persister les données métier** — notes, favoris et compteurs de vues — dans PostgreSQL via Doctrine.

Les réponses sur les races sont enrichies à la volée : note moyenne agrégée et statut favori de la
session courante y sont injectés.

## Sessions

Les notes et favoris sont rattachés à une **session anonyme**, identifiée par l'en-tête
`X-Session-Id` envoyé par le service Express. Les endpoints qui dépendent de la session renvoient
une erreur `400` si cet en-tête est absent (selon le cas).

## Technologies

- **PHP** 8.3
- **Symfony** 7.4
- **Doctrine ORM** + **Doctrine Migrations**
- **Symfony HttpClient** — appels vers The Cat API
- **PostgreSQL** 16 (environnement local/Docker) — **SQLite** en environnement de test
- **PHPUnit** 12 — tests

## Structure

```
symfony-api/
├── src/
│   ├── Controller/
│   │   ├── CatController.php       # /cats et /cats/{id} (appel The Cat API + agrégats)
│   │   ├── RatingController.php    # /ratings/{breedId} (GET, POST)
│   │   ├── FavoriteController.php  # /favorites, /favorites/{breedId}
│   │   └── ViewsController.php     # /views/{breedId} (GET, POST)
│   └── Entity/
│       ├── BreedRating.php         # note (breed_id + session_id uniques)
│       ├── BreedFavorite.php       # favori (breed_id + session_id uniques)
│       └── BreedView.php           # compteur de vues par race
├── tests/
│   └── CatControllerTest.php       # WebTestCase, HttpClient mocké, schéma SQLite
├── migrations/
├── config/
├── Dockerfile
└── compose.yaml                    # PostgreSQL (lancement local uniquement)
```

## Variables d'environnement

Copiez `.env.dev` en `.env` et adaptez si nécessaire :

```bash
cp .env.dev .env
```

| Variable | Description |
|---|---|
| `DATABASE_URL` | DSN PostgreSQL (fourni par Docker Compose en conteneur) |
| `APP_ENV` | `dev`, `prod` ou `test` |
| `APP_SECRET` | Clé secrète Symfony |

> **À corriger :** la clé d'accès à The Cat API est aujourd'hui codée en dur dans
> `CatController.php`. Elle devrait être externalisée dans une variable d'environnement
> (ex. `CAT_API_KEY`) et lue via la configuration Symfony.

## Lancer en local (sans Docker)

Prérequis : PHP 8.3, Composer, Symfony CLI.

```bash
cd symfony-api
composer install

cp .env.dev .env

# Démarrer PostgreSQL via le compose dédié
docker compose up -d database

# Appliquer les migrations
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

Les tests bootent un client Symfony, **mockent The Cat API** (aucun appel réseau) et recréent un
schéma vierge sur **SQLite** (via `.env.test`).

## Endpoints

| Méthode | Route | Description |
|---|---|---|
| GET | `/cats` | Liste des races + note moyenne + favori de la session |
| GET | `/cats/{id}` | Détail complet d'une race (stats, note utilisateur, favori) |
| GET | `/ratings/{breedId}` | Note de la session + moyenne |
| POST | `/ratings/{breedId}` | Soumettre/mettre à jour une note (`{ "score": 1..5 }`) |
| GET | `/favorites` | Liste des favoris de la session |
| GET | `/favorites/{breedId}` | Statut favori d'une race |
| POST | `/favorites/{breedId}` | Basculer le statut favori |
| GET | `/views/{breedId}` | Lire le compteur de vues |
| POST | `/views/{breedId}` | Incrémenter le compteur de vues |

Exemple de réponse `/cats` :

```json
[
  {
    "id": "abys",
    "name": "Abyssinian",
    "origin": "Egypt",
    "temperament": "Active, Energetic, Independent, Intelligent, Gentle",
    "description": "The Abyssinian is easy to care for...",
    "image_id": "0XYvRd7oD",
    "avg_rating": 4.5,
    "is_favorite": false
  }
]
```

Les endpoints liés à la session attendent l'en-tête `X-Session-Id` (transmis par Express).