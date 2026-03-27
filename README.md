# EcoGarden API

API REST développée avec Symfony permettant la gestion des utilisateurs, la consultation de conseils de jardinage par mois, et la récupération de données météo selon la localisation de l'utilisateur.

---

## Prérequis

- PHP 8.1+
- Composer
- Symfony CLI
- MySQL / MariaDB

---

## Installation

```bash
git clone https://github.com/ton-pseudo/ecogarden-api.git
cd ecogarden-api

composer install

cp .env .env.local
# Renseigner DATABASE_URL dans .env.local
```

Créer la base de données et appliquer les migrations :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

Charger les fixtures :

```bash
php bin/console doctrine:fixtures:load
```

> Attention : cette commande supprime et réinitialise le contenu de la base de données.

### Comptes disponibles après chargement

| Email | Mot de passe | Rôle |
|---|---|---|
| admin@ecogarden.com | password | ROLE_ADMIN |
| user@ecogarden.com | password | ROLE_USER |

5 comptes supplémentaires sont générés aléatoirement avec le mot de passe `password`.

---

Lancer le serveur de développement :

```bash
symfony server:start
```

---

## Authentification

L'API utilise **JWT (JSON Web Token)**.

Obtenir un token :

```http
POST /login_check
Content-Type: application/json

{
  "username": "email@exemple.com",
  "password": "motdepasse"
}
```

Utiliser le token dans les requêtes protégées :

```
Authorization: Bearer <token>
```

---

## Endpoints

### Utilisateurs

| Méthode | Route | Description | Auth requise |
|---|---|---|---|
| `POST` | `/login_check` | Authentification, retourne un token JWT | Non |
| `POST` | `/user` | Créer un compte utilisateur | Non |
| `PUT` | `/user/{id}` | Modifier un utilisateur | Oui (ROLE_ADMIN) |
| `DELETE` | `/user/{id}` | Supprimer un utilisateur | Oui (ROLE_ADMIN) |

### Conseils

| Méthode | Route | Description | Auth requise |
|---|---|---|---|
| `GET` | `/conseil/` | Récupérer les conseils du mois en cours | Oui (ROLE_USER) |
| `GET` | `/conseil/{month}` | Récupérer les conseils d'un mois précis (1–12) | Oui (ROLE_USER) |
| `POST` | `/conseil/` | Ajouter un conseil | Oui (ROLE_ADMIN) |
| `PUT` | `/conseil/{id}` | Modifier un conseil | Oui (ROLE_ADMIN) |
| `DELETE` | `/conseil/{id}` | Supprimer un conseil | Oui (ROLE_ADMIN) |

### Météo

| Méthode | Route | Description | Auth requise |
|---|---|---|---|
| `GET` | `/weather/` | Météo de la ville de l'utilisateur connecté | Oui (ROLE_USER) |
| `GET` | `/weather/{city}` | Météo d'une ville spécifique | Non (ROLE_USER) |
| `DELETE` | `/weather/` | Vider le cache météo | Oui (ROLE_ADMIN) |

---

## Rôles

| Rôle | Permissions |
|---|---|
| `ROLE_USER` | Lecture des conseils et de la météo |
| `ROLE_ADMIN` | Toutes les opérations, y compris création, modification et suppression |

---

## Variables d'environnement

| Variable | Description |
|---|---|
| `DATABASE_URL` | URL de connexion à la base de données |
| `JWT_SECRET_KEY` | Chemin vers la clé privée JWT |
| `JWT_PUBLIC_KEY` | Chemin vers la clé publique JWT |
| `JWT_PASSPHRASE` | Passphrase de la clé JWT |