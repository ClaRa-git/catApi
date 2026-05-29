# express-cat

Gateway HTTP écrit en Node.js/Express. Il reçoit les requêtes du client et les proxifie vers l'API Symfony. Il constitue le point d'entrée unique de l'application.

## Technologies

- **Node.js** 22
- **Express** 5
- **axios** — appels HTTP vers Symfony
- **cors** — activation du CORS
- **Jest** + **Supertest** — tests
- **nodemon** — rechargement automatique en développement

## Structure

```
express-app/
├── app.js                  # point d'entrée, configuration Express
├── routes/
│   └── cats.js             # déclaration des routes /cats
├── controllers/
│   └── catsController.js   # logique métier, appel vers Symfony
├── tests/
│   └── cats.test.js        # tests d'intégration
└── Dockerfile
```

## Variables d'environnement

Aucune variable requise par défaut. L'URL de Symfony (`http://symfony:8000`) est câblée dans le contrôleur pour le mode Docker. Adaptez-la si vous lancez le service hors Docker.

## Lancer en local (sans Docker)

```bash
cd express-app
npm install

# développement avec rechargement automatique
npm run dev

# ou lancement simple
node app.js
```

Le serveur démarre sur le port **3000**.

## Lancer avec Docker

```bash
# depuis la racine du projet
docker compose up --build express
```

## Tests

```bash
npm test
```

Jest exécute les tests en séquence (`--runInBand`). Les tests utilisent Supertest pour simuler les requêtes HTTP sans démarrer de vrai serveur.

## Endpoints

| Méthode | Route | Description |
|---|---|---|
| GET | `/cats` | Retourne la liste des races (proxifié depuis Symfony) |
