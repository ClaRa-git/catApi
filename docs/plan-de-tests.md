# Plan de tests — catApi

## 1. Objectif et périmètre

Ce document décrit la stratégie de tests de l'application **catApi**, composée de deux services
(API Symfony et gateway/frontend Express) consommant l'API publique The Cat API et persistant les
notes, favoris et vues dans PostgreSQL.

Il couvre :
- les tests fonctionnels (unitaires et d'intégration) des deux services ;
- les tests de robustesse / sécurité sur la validation des entrées ;
- l'analyse statique de code (qualité) ;
- un jeu d'essai détaillé sur la fonctionnalité la plus représentative (`GET /cats`).

## 2. Environnement de tests

| Élément | Symfony API | Express |
|---|---|---|
| Framework de test | PHPUnit 12 (WebTestCase) | Jest 30 + Supertest 7 |
| Base de données | SQLite en mémoire (`.env.test`), schéma recréé avant chaque test | n/a (appels HTTP mockés) |
| Dépendances externes | The Cat API mockée via `MockHttpClient` / `MockResponse` (aucun appel réseau réel) | `axios` mocké via `jest.mock('axios')` |
| Génération d'identifiants | n/a | `uuid` mocké (`tests/__mocks__/uuid.js`) pour des sessions déterministes |
| Isolation | `--runInBand` (Jest) pour exécuter les tests en séquence | idem |

Ce choix d'environnement garantit des tests **reproductibles et rapides**, sans dépendance à la
disponibilité de The Cat API ni à une vraie base PostgreSQL.

## 3. Outils de qualité de code

| Outil | Cible | Rôle |
|---|---|---|
| PHPStan (niveau 5) | `symfony-api/src` | Détection des erreurs de typage, appels invalides, code mort |
| ESLint (config recommandée) | `express-app` (hors `node_modules`, `frontend`) | Détection des variables non déclarées/non utilisées, incohérences |
| PHPUnit | `symfony-api/tests` | Tests fonctionnels et de robustesse |
| Jest / Supertest | `express-app/tests` | Tests d'intégration HTTP |

Ces quatre outils sont exécutés automatiquement dans le pipeline CI (`.github/workflows/ci.yml`),
**avant** toute construction d'image Docker : un échec de lint ou de test bloque le déploiement.

## 4. Couverture fonctionnelle

| Fonctionnalité | Endpoint | Cas couverts | Type de test |
|---|---|---|---|
| Liste des races | `GET /cats` | succès, champs manquants → `null`, calcul de moyenne, favori absent sans session, liste vide, panne de l'API amont (500) | Intégration |
| Détail d'une race | `GET /cats/:id` | — *(non couvert à ce jour, voir §6 Écarts)* | — |
| Notation | `POST /ratings/:id` | session absente, score hors plage (haut/bas), score non numérique, JSON malformé, score valide, re-soumission (mise à jour et non duplication) | Intégration + robustesse |
| Lecture de note | `GET /ratings/:id` | note utilisateur + moyenne après ré-soumission | Intégration |
| Favoris | `POST/GET /favorites/:id`, `GET /favorites` | — *(non couvert à ce jour, voir §6 Écarts)* | — |
| Vues | `POST/GET /views/:id` | — *(non couvert à ce jour, voir §6 Écarts)* | — |
| Gateway Express `/cats` | `GET /cats` | succès, structure de réponse, pose du cookie `session_id`, non-repose si déjà présent, panne de Symfony (500) | Intégration |

## 5. Jeu d'essai détaillé — `GET /cats`

Fonctionnalité retenue comme la plus représentative car elle agrège trois sources de données
(API externe, notes, favoris) et illustre la gestion des cas limites.

### Données en entrée (mock The Cat API)

```json
[
  { "id": "abys", "name": "Abyssinian", "origin": "Egypt",
    "temperament": "Active, Energetic", "description": "An ancient breed.",
    "reference_image_id": "img-abys", "life_span": "14 - 15" },
  { "id": "beng", "name": "Bengal", "temperament": "Alert",
    "description": "A wild look." }
]
```

Deux notes sont injectées en base avant l'appel : `abys` / session `sess-1` → 4, `abys` / session
`sess-2` → 5. Aucune note n'est injectée pour `beng`.

### Données attendues

| Champ | `abys` | `beng` |
|---|---|---|
| `id` | `abys` | `beng` |
| `origin` | `Egypt` | `null` *(absent à la source)* |
| `image_id` | `img-abys` | `null` *(absent à la source)* |
| `avg_rating` | `4.5` *(moyenne de 4 et 5)* | `null` *(aucune note)* |
| `is_favorite` | `false` *(pas de session dans la requête)* | `false` |
| `life_span` | *absent de la réponse* | *absent de la réponse* |

### Données obtenues

Conformes à l'attendu — voir `symfony-api/tests/CatControllerTest.php` :
- `testBreedExposesExpectedFieldsOnly` : vérifie la liste exacte des 8 clés exposées et l'absence
  de `life_span` (champ non mappé par le contrôleur, donc filtré).
- `testMissingSourceFieldsBecomeNull` : vérifie que `origin` et `image_id` valent `null` pour `beng`.
- `testAverageRatingIsComputed` : vérifie `avg_rating = 4.5` pour `abys` et `null` pour `beng`.
- `testIsFavoriteFalseWithoutSession` : vérifie `is_favorite = false` en l'absence d'en-tête de session.

### Analyse des écarts

Aucun écart constaté entre attendu et obtenu sur ce jeu d'essai. Le seul point de vigilance
identifié est l'absence de test sur le cas où `reference_image_id` est présent mais que l'image
n'existe plus côté CDN (résolution faite côté frontend via un système de repli, non testé côté API).

## 6. Tests de robustesse / sécurité

Réalisés dans `symfony-api/tests/RatingControllerTest.php`, ils visent l'endpoint d'écriture
`POST /ratings/:id`, point d'entrée le plus exposé (entrée utilisateur libre) :

| Cas testé | Entrée | Résultat attendu | Constat |
|---|---|---|---|
| Absence d'authentification | pas d'en-tête `X-Session-Id` | `400` + message d'erreur | Conforme |
| Score hors plage haute | `score: 99` | `400` | Conforme |
| Score hors plage basse | `score: 0` | `400` | Conforme |
| Score non numérique / tentative d'injection | `score: "'; DROP TABLE breed_rating; --"` | `400`, pas d'erreur 500, pas d'exécution SQL | Conforme — le cast `(int)` neutralise la charge avant toute requête SQL paramétrée |
| Corps JSON malformé | `{not-valid-json` | `400`, pas d'erreur 500 | Conforme |
| Score valide | `score: 5` | `200`, note persistée | Conforme |
| Re-soumission (anti-duplication) | deux notes successives, même session | la seconde remplace la première (contrainte unique `breed_id` + `session_id`) | Conforme |

**Conclusion** : la couche de persistance utilise systématiquement des requêtes paramétrées
(Doctrine DBAL), ce qui élimine le risque d'injection SQL classique. La validation applicative
(`(int)` cast + plage 1–5) empêche l'enregistrement de données incohérentes.

## 7. Écarts et limites identifiés (axes d'amélioration)

- Les endpoints `/favorites/*` et `/views/*` ne disposent pas encore de tests automatisés côté
  Symfony — à couvrir avant mise en production.
- `GET /cats/:id` (détail d'une race) n'est pas testé côté API.
- Aucun test de charge / performance n'a été réalisé.
- La clé d'API The Cat API est actuellement codée en dur dans `CatController.php` ; elle devrait
  être externalisée (variable d'environnement) — point de sécurité à traiter avant toute mise en
  production réelle.

## 8. Critères de sortie

Le déploiement (job `docker` du pipeline CI) n'est déclenché **que si** :
1. PHPStan ne remonte aucune erreur ;
2. ESLint ne remonte aucune erreur ;
3. l'intégralité de la suite PHPUnit passe ;
4. l'intégralité de la suite Jest passe ;
5. le push a lieu sur la branche `main`.