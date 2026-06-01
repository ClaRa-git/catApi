# express-cat

Service Node.js/Express qui joue un double rôle :

1. **Serveur frontend** — il sert les pages web statiques de l'application (accueil, catalogue, fiche race) ;
2. **Gateway / BFF** — il reçoit les appels du navigateur et les relaie vers l'API Symfony, en y ajoutant l'identifiant de session.

Il constitue le **point d'entrée unique** côté client : le navigateur ne parle jamais directement à Symfony.

## Gestion de session

À chaque requête, Express vérifie la présence d'un cookie `session_id`. S'il est absent, un UUID est
généré et posé en cookie (`httpOnly`, 1 an). Cet identifiant est transmis à Symfony via l'en-tête
`X-Session-Id`, ce qui permet de rattacher notes et favoris à un visiteur anonyme.

## Technologies

- **Node.js** 22
- **Express** 5
- **axios** — appels HTTP vers Symfony
- **cors** — activation du CORS
- **cookie-parser** — lecture/écriture du cookie de session
- **uuid** — génération de l'identifiant de session
- **dotenv** — chargement des variables d'environnement
- **Jest** + **Supertest** — tests (dev)
- **nodemon** — rechargement automatique en développement (dev)

## Structure

```
express-app/
├── server.js                   # point d'entrée : démarre le serveur (port 3000)
├── app.js                      # configuration Express, middlewares, routage des pages
├── routes/
│   └── cats.js                 # déclaration des routes /cats/*
├── controllers/
│   ├── catsController.js       # liste + détail des races
│   ├── ratingsController.js    # lecture / soumission des notes
│   ├── favoritesController.js  # toggle / liste / statut des favoris
│   └── viewsController.js      # incrément / lecture des vues
├── frontend/
│   ├── index.html              # page d'accueil
│   ├── breeds.html             # catalogue des races
│   └── breed.html              # fiche détaillée d'une race
├── tests/
│   └── cats.test.js            # tests d'intégration (Supertest, axios mocké)
└── Dockerfile
```

## Variables d'environnement

Chargées via `dotenv` (fichier `.env` optionnel à la racine du service).

| Variable | Défaut | Description |
|---|---|---|
| `SYMFONY_URL` | `http://symfony:8000` | URL de base de l'API Symfony |

La valeur par défaut correspond au nom de service Docker. Pour un lancement **hors Docker**,
définissez `SYMFONY_URL=http://localhost:8000`.

## Lancer en local (sans Docker)

```bash
cd express-app
npm install

# développement avec rechargement automatique
npm run dev          # = nodemon server.js

# ou lancement simple
node server.js
```

Le serveur démarre sur le port **3000**.

## Lancer avec Docker

```bash
# depuis la racine du projet
docker compose up --build express
```

## Tests

```bash
npm test             # = jest --runInBand
```

Les tests utilisent Supertest pour simuler les requêtes HTTP sans démarrer de vrai serveur, et
mockent `axios` (pas d'appel réseau réel) ainsi que `uuid`. Ils sont exécutés en séquence (`--runInBand`).

## Endpoints

### Pages (HTML)

| Méthode | Route | Description |
|---|---|---|
| GET | `/` | Page d'accueil |
| GET | `/breeds` | Catalogue des races |
| GET | `/breeds/:id` | Fiche détaillée d'une race |

### API (proxy vers Symfony)

| Méthode | Route | Description |
|---|---|---|
| GET | `/cats` | Liste des races (note moyenne + favori inclus) |
| GET | `/cats/:id` | Détail d'une race |
| GET | `/cats/:id/ratings` | Note de l'utilisateur + moyenne |
| POST | `/cats/:id/ratings` | Soumettre une note (corps `{ "score": 1..5 }`) |
| POST | `/cats/:id/favorites` | Basculer le statut favori |
| GET | `/cats/:id/favorites` | Statut favori d'une race |
| GET | `/cats/favorites` | Liste des favoris de la session |
| POST | `/cats/:id/views` | Incrémenter le compteur de vues |
| GET | `/cats/:id/views` | Lire le compteur de vues |