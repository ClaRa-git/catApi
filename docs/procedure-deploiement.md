# Procédure de déploiement — catApi

## 1. Environnements

| Environnement | Usage | Base de données | Déclenchement |
|---|---|---|---|
| **Local / développement** | poste du développeur | PostgreSQL (conteneur `database`) | manuel, `docker compose up --build` |
| **Test (CI)** | exécution automatique des tests | SQLite en mémoire | automatique, à chaque push / pull request |
| **Production** | mise à disposition de l'application | PostgreSQL (instance dédiée, hors scope de ce dépôt) | automatique, à chaque push sur `main`, après succès des tests |

## 2. Prérequis

- Docker et Docker Compose installés sur la machine cible.
- Accès à Docker Hub (images publiées sous `<DOCKERHUB_USERNAME>/cat-symfony` et `cat-express`).
- Variable d'environnement `DATABASE_URL` pointant vers l'instance PostgreSQL cible.
- Secrets GitHub configurés sur le dépôt : `DOCKERHUB_USERNAME`, `DOCKERHUB_PASSWORD`.

## 3. Étape 1 — Intégration continue (build & tests)

Déclenchée sur chaque `push` et `pull request` vers `main` (job `test` de `ci.yml`) :

1. Installation de PHP 8.3 et des dépendances Symfony (`composer install`).
2. Analyse statique du code PHP (`composer phpstan`).
3. Exécution de la suite PHPUnit (`php bin/phpunit`).
4. Installation de Node 22 et des dépendances Express (`npm ci`).
5. Analyse statique du code JS (`npm run lint`).
6. Exécution de la suite Jest (`npm test`).

Si une seule de ces étapes échoue, le pipeline s'arrête : aucune image n'est construite ni publiée.

## 4. Étape 2 — Construction et publication des images (CD)

Déclenchée uniquement si l'étape 1 réussit **et** que l'événement est un `push` sur `main`
(job `docker` de `ci.yml`) :

1. Authentification auprès de Docker Hub.
2. Construction de l'image `symfony-api` (contexte `./symfony-api`).
3. Construction de l'image `express-app` (contexte `./express-app`).
4. Publication des deux images avec deux tags : `latest` et `<sha du commit>`.
   Le tag `<sha>` garantit la traçabilité exacte entre un déploiement et le code source qui l'a produit,
   et permet un retour en arrière ciblé (voir §6).

## 5. Étape 3 — Déploiement sur l'environnement cible

Sur le serveur de production :

```bash
# 1. Récupérer les dernières images
docker pull <DOCKERHUB_USERNAME>/cat-symfony:latest
docker pull <DOCKERHUB_USERNAME>/cat-express:latest

# 2. (Re)démarrer la stack
docker compose pull
docker compose up -d

# 3. Appliquer les migrations de base de données
docker compose exec symfony php bin/console doctrine:migrations:migrate --no-interaction
```

> En production, `docker-compose.yml` doit être adapté pour utiliser les images publiées
> (`image: <DOCKERHUB_USERNAME>/cat-symfony:latest`) plutôt que `build:`, et `DATABASE_URL`
> doit pointer vers l'instance PostgreSQL réelle (et non le conteneur de développement).

## 6. Étape 4 — Vérifications post-déploiement (smoke tests)

À exécuter immédiatement après chaque déploiement :

1. **Santé de la base** : `docker compose ps` → le service `database` doit être `healthy`.
2. **Disponibilité de l'API** : `curl -f http://<host>:8000/cats` doit répondre `200` avec un
   tableau JSON non vide.
3. **Disponibilité du frontend** : `curl -f http://<host>:3000/` doit répondre `200`.
4. **Chaîne complète** : ouvrir `http://<host>:3000/breeds` dans un navigateur et vérifier
   l'affichage du catalogue.

Si l'une de ces vérifications échoue, procéder au rollback (§7) avant d'investiguer.

## 7. Procédure de rollback

Grâce au tag `<sha>` systématiquement publié à l'étape 2, un retour à la version précédente est
immédiat :

```bash
# Identifier le sha du dernier déploiement fonctionnel (historique Git ou tags Docker Hub)
docker pull <DOCKERHUB_USERNAME>/cat-symfony:<sha_precedent>
docker pull <DOCKERHUB_USERNAME>/cat-express:<sha_precedent>

# Redémarrer avec ces tags explicites (adapter docker-compose.yml ou surcharger via variable d'env)
docker compose up -d
```

Si le problème provient d'une migration de base de données, exécuter la migration `down`
correspondante (`doctrine:migrations:migrate <version_precedente>`) **avant** de redémarrer
les conteneurs applicatifs, afin d'éviter un schéma incohérent avec le code redéployé.

## 8. Sauvegarde et restauration des données

Indépendamment du déploiement applicatif, la base de données dispose de scripts dédiés
(`scripts/backup.sh`, `scripts/restore.sh`) :

```bash
# Sauvegarde avant un déploiement à risque
./scripts/backup.sh
# → crée scripts/backups/backup_<timestamp>.sql

# Restauration en cas de problème
./scripts/restore.sh scripts/backups/backup_<timestamp>.sql
```

Ces scripts s'appuient sur `docker compose exec database` ; la stack doit donc être démarrée
au moment de leur exécution. Il est recommandé d'effectuer une sauvegarde **avant** toute
migration de schéma en production.

## 9. Veille technologique et sécurité

Dans une démarche DevOps, un point de veille régulier doit être maintenu sur :
- les versions de PHP, Symfony, Node et Express (fins de support, CVE) ;
- les dépendances tierces (`composer audit`, `npm audit`) — à intégrer dans une prochaine
  itération du pipeline CI ;
- la rotation de la clé d'API The Cat API, actuellement codée en dur dans `CatController.php`
  et qui devrait être externalisée en variable d'environnement (`CAT_API_KEY`) avant toute mise
  en production réelle.