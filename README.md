# catApi

Application multi-services autour de l'API publique [The Cat API](https://api.thecatapi.com). Elle expose des informations sur les races de chats via une architecture en deux couches : un gateway Express qui délègue à une API Symfony, elle-même consommatrice de l'API externe.

```
Client
  │
  ▼ :3000
Express (gateway)
  │
  ▼ :8000
Symfony API
  │
  ▼ (externe)
The Cat API
```

## Technologies

| Couche | Techno |
|---|---|
| Gateway | Node.js 22, Express 5 |
| API | PHP 8.3, Symfony 7.4, Doctrine ORM |
| Base de données (local) | PostgreSQL 16 |
| Containerisation | Docker, Docker Compose |
| CI/CD | GitHub Actions → Docker Hub |

## Prérequis

- [Docker](https://docs.docker.com/get-docker/) et Docker Compose

## Lancer le projet

```bash
# Cloner le dépôt
git clone <url-du-repo>
cd mini-projet

# Démarrer les deux services
docker compose up --build
```

Les services sont disponibles sur :

- **Express gateway** → `http://localhost:3000`
- **Symfony API** → `http://localhost:8000`

## Endpoints

| Méthode | URL | Description |
|---|---|---|
| GET | `/cats` | Liste des races de chats |

Exemple : `curl http://localhost:3000/cats`

## Structure

```
mini-projet/
├── docker-compose.yml       # orchestration des services
├── express-app/             # gateway Node.js/Express
└── symfony-api/             # API PHP/Symfony
```

## CI/CD

Le pipeline GitHub Actions (`.github/workflows/ci.yml`) se déclenche à chaque push sur `main` :

1. **test** — exécute les tests PHPUnit (Symfony) et Jest (Express)
2. **docker** — build et push les images sur Docker Hub (`cat-symfony`, `cat-express`)

Secrets requis dans le dépôt GitHub : `DOCKERHUB_USERNAME`, `DOCKERHUB_TOKEN`.

## Sous-projets

- [express-app/README.md](express-app/README.md)
- [symfony-api/README.md](symfony-api/README.md)
