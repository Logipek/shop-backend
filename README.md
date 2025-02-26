# API de Gestion d'Inventaire pour Petits Commerçants

## Introduction

Cette API permet aux petits commerçants de gérer efficacement leurs inventaires, leurs ventes et leurs commandes en temps réel. Développée avec PHP et conteneurisée avec Docker, elle offre une solution complète pour le suivi des stocks et la gestion des commandes.

## Fonctionnalités

- **Gestion des produits**: Ajout, modification, suppression et consultation des produits
- **Suivi des stocks**: Mise à jour en temps réel des niveaux de stock
- **Gestion des commandes**: Création, suivi et traitement des commandes
- **Gestion des retours**: Traitement des retours de produits avec mise à jour automatique des stocks
- **Authentification sécurisée**: Système d'authentification JWT pour sécuriser l'accès à l'API

## Prérequis

- Docker et Docker Compose installés sur votre machine
- Composer pour la gestion des dépendances PHP

## Configuration

Avant de démarrer le projet, configurez vos variables d'environnement en copiant le fichier `.env.sample` vers `.env` :

```sh
cp .env.sample .env
```

Modifiez le fichier `.env` avec vos paramètres :

- `DB_NAME`: Nom de votre base de données MySQL
- `DB_USER`: Utilisateur MySQL
- `DB_PASSWORD`: Mot de passe de l'utilisateur MySQL
- `DB_ROOT_PASSWORD`: Mot de passe de l'utilisateur root MySQL
- `DB_PORT`: Port pour MySQL (par défaut 3306)
- `PHPMYADMIN_PORT`: Port pour phpMyAdmin (par défaut 8090)

## Installation

```sh
# Installation des dépendances PHP
cd app && composer install && cd ../

# Démarrage des conteneurs Docker
docker-compose up -d
```

## Endpoints de l'API

### Authentification

- `POST /auth/register` - Inscription d'un nouvel utilisateur
- `POST /auth/login` - Connexion et obtention d'un token JWT

### Produits

- `GET /products` - Liste de tous les produits
- `GET /products/:id` - Détails d'un produit spécifique
- `POST /products` - Création d'un nouveau produit
- `PUT /products/:id` - Mise à jour complète d'un produit
- `DELETE /products/:id` - Suppression d'un produit
- `PATCH /products/:id/stock` - Mise à jour du stock d'un produit

### Commandes

- `GET /orders` - Liste de toutes les commandes
- `GET /orders/:id` - Détails d'une commande spécifique
- `POST /orders` - Création d'une nouvelle commande
- `PATCH /orders/:id/status` - Mise à jour du statut d'une commande
- `POST /orders/:id/return` - Traitement d'un retour de commande

## Exemples d'utilisation

### Création d'un produit

```json
POST /products
{
  "name": "T-shirt",
  "description": "T-shirt en coton bio",
  "price": 19.99,
  "stock_quantity": 100
}
```

### Création d'une commande

```json
POST /orders
{
  "customer_name": "Jean Dupont",
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 3,
      "quantity": 1
    }
  ]
}
```

## Accès aux services

- API: [http://localhost](http://localhost)
- phpMyAdmin: [http://localhost:8090](http://localhost:8090)

## Arrêt du projet

```sh
docker-compose down
```

## Licence

Ce projet est sous licence MIT.
