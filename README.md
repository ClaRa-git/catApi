# catApi

Application web multi-services autour de l'API publique [The Cat API](https://api.thecatapi.com).
Elle permet de parcourir les races de chats, de les **noter**, de les mettre en **favoris** et
de suivre leur nombre de **vues**.

L'architecture repose sur deux services applicatifs et une base de données :

- un service **Express** qui sert le **frontend web** et fait office de *gateway / BFF* en relayant les appels vers l'API Symfony ;
- une **API Symfony** qui consomme The Cat API et persiste les données métier (notes, favoris, vues) dans PostgreSQL.

```
Navigateur
  │  (pages HTML + appels fetch)
  ▼ :3000
Express  (frontend + gateway / BFF)
  │  (axios, en-tête X-Session-Id)
  ▼ :8000
Symfony API  ──►  PostgreSQL   (notes, favoris, vues)
  │
  ▼ (externe)
The Cat API
```

## Technologies

| Couche | Techno |
|---|---|
| Frontend | HTML / CSS / JavaScript (vanilla), servi par Express |
| Gateway / BFF | Node.js 22, Express 5 |
| API | PHP 8.3, Symfony 7.4, Doctrine ORM + Migrations |
| Base de données | PostgreSQL 16 (SQLite en tests) |
| Containerisation | Docker, Docker Compose |
| CI/CD | GitHub Actions → Docker Hub |

## Prérequis

- [Docker](https://docs.docker.com/get-docker/) et Docker Compose

## Récupérer et démarrer le projet

```bash
# 1. Cloner le dépôt
git clone <url-du-repo>
cd mini-projet

# 2. Démarrer toute la stack (frontend + API + base de données)
docker compose up --build
```

Au premier lancement, Compose construit les images, démarre PostgreSQL et attend qu'elle soit saine
(`healthcheck`) avant de lancer Symfony, puis Express.

Une fois les services démarrés :

- **Application web** → http://localhost:3000
- **API Symfony** (accès direct, debug) → http://localhost:8000
- **PostgreSQL** → `localhost:5432` (base `app`, utilisateur `app`)

Pour arrêter : `Ctrl+C` puis `docker compose down` (ajouter `-v` pour supprimer aussi le volume de données).

> Chaque sous-projet peut aussi être lancé seul, hors Docker. Voir leurs README respectifs.

## Endpoints exposés (via Express)

| Méthode | URL | Description |
|---|---|---|
| GET | `/` | Page d'accueil |
| GET | `/breeds` | Catalogue des races |
| GET | `/breeds/:id` | Fiche détaillée d'une race |
| GET | `/cats` | Liste des races (avec note moyenne + favori) |
| GET | `/cats/:id` | Détail d'une race |
| GET/POST | `/cats/:id/ratings` | Lire / soumettre une note (1–5) |
| POST | `/cats/:id/favorites` | Basculer le statut favori |
| GET | `/cats/:id/favorites` | Statut favori d'une race |
| GET | `/cats/favorites` | Liste des favoris de la session |
| GET/POST | `/cats/:id/views` | Lire / incrémenter le compteur de vues |

Exemple : `curl http://localhost:3000/cats`

Les notes et favoris sont rattachés à une **session anonyme** identifiée par un cookie `session_id`
(posé automatiquement par Express et propagé à Symfony via l'en-tête `X-Session-Id`).

## Structure

```
mini-projet/
├── docker-compose.yml        # orchestration des 3 services
├── express-app/              # frontend + gateway Node.js/Express
├── symfony-api/              # API PHP/Symfony + Doctrine
├── scripts/                  # utilitaires base de données
│   ├── backup.sh             # dump PostgreSQL → scripts/backups/
│   └── restore.sh            # restauration d'un dump
└── .github/workflows/ci.yml  # pipeline CI/CD
```

## Sauvegarde / restauration de la base

```bash
# Sauvegarde (crée scripts/backups/backup_<timestamp>.sql)
./scripts/backup.sh

# Restauration depuis un dump
./scripts/restore.sh scripts/backups/backup_XXXXXX.sql
```

Ces scripts s'appuient sur `docker compose exec database` ; la stack doit donc être démarrée.

## CI/CD

Le pipeline GitHub Actions (`.github/workflows/ci.yml`) se déclenche sur **push** et **pull request** vers `main` :

1. **test** — installe PHP 8.3 et Node 22, puis exécute les tests PHPUnit (Symfony) et Jest (Express).
2. **docker** — uniquement sur push `main` : build et push des images sur Docker Hub
   (`cat-symfony` et `cat-express`, taguées `latest` et `<sha>`).

Secrets requis dans le dépôt GitHub : `DOCKERHUB_USERNAME`, `DOCKERHUB_PASSWORD`.


## Sous-projets

- [express-app/README.md](express-app/README.md)
- [symfony-api/README.md](symfony-api/README.md)