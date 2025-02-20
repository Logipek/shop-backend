# 🏪 Système de Gestion d'Inventaire pour Commerçants

## 🚀 Présentation du Projet

Application web de gestion d'inventaire conçue pour les petits commerçants, permettant de gérer efficacement les stocks, les produits et les utilisateurs.

## 📋 Objectifs du Projet

L'objectif principal est de fournir aux petits commerçants un outil simple, sécurisé et efficace pour gérer leur inventaire, leurs produits et leurs utilisateurs.

## ✨ Fonctionnalités

### 🔐 Authentification et Sécurité
- [x] Inscription des utilisateurs
- [x] Connexion sécurisée
- [x] Authentification JWT
- [x] Système de refresh token
- [x] Gestion des rôles utilisateurs
- [ ] Authentification multi-facteurs
- [ ] Réinitialisation de mot de passe

### 📦 Gestion des Produits
- [x] Création de produits
- [x] Modification des produits
- [x] Suppression de produits
- [x] Liste des produits
- [ ] Catégorisation des produits
- [ ] Gestion des variantes de produits

### 📊 Gestion des Stocks
- [ ] Suivi des niveaux de stock
- [ ] Alertes de stock bas
- [ ] Historique des mouvements de stock
- [ ] Gestion des approvisionnements

### 🛒 Gestion des Commandes
- [ ] Création de commandes
- [ ] Suivi des commandes
- [ ] Gestion des statuts de commande
- [ ] Génération de factures
- [ ] Historique des commandes

## 🔐 Système d'Authentification

### Tokens
- **Access Token** : Durée de vie courte (1 heure)
- **Refresh Token** : Durée de vie longue (30 jours)
- Contient les informations utilisateur (ID, rôle)

### Rôles
- Rôle 1 : Utilisateur standard
- Rôle 2 : Administrateur
- Rôle 3 : Super Administrateur (optionnel)

## 🛠 Architecture Technique

### Backend
- **Langage** : PHP
- **Authentification** : JWT
- **Base de données** : MySQL
- **Architecture** : MVC (Modèle-Vue-Contrôleur)

### Sécurité
- Hashage des mots de passe (BCrypt)
- Validation et sanitization des données
- Middleware de vérification des rôles
- Protection contre les injections SQL

## 🔑 Endpoints Principaux

### 🔐 Authentification
- `POST /auth/register` : Inscription
- `POST /auth/login` : Connexion
- `POST /auth/refresh` : Renouvellement du token
- `POST /auth/update-role` : Mise à jour du rôle utilisateur

### 📦 Produits
- `POST /products/add` : Ajouter un produit
- `GET /products` : Lister les produits
- `GET /products/:id` : Détails d'un produit
- `PATCH /products/:id` : Modifier un produit
- `DELETE /products/:id` : Supprimer un produit

### 🛒 Commandes (Futures)
- `POST /orders/create` : Créer une commande
- `GET /orders` : Lister les commandes
- `GET /orders/:id` : Détails d'une commande
- `PATCH /orders/:id` : Modifier le statut d'une commande
- `DELETE /orders/:id` : Annuler une commande

## 📦 Prérequis Techniques

### Serveur
- PHP 8.0+
- MySQL 5.7+
- Extension PDO
- Composer

### Variables d'Environnement
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASSWORD`
- `JWT_ACCESS_SECRET`
- `JWT_REFRESH_SECRET`
- `PASSWORD_SALT`

## 📊 Modèle de Données

### Utilisateurs
| Champ | Type | Description |
|-------|------|-------------|
| id | INT | Identifiant unique |
| email | VARCHAR | Adresse email |
| password | VARCHAR | Mot de passe hashé |
| role | INT | Rôle utilisateur |
| created_at | TIMESTAMP | Date de création |
| updated_at | TIMESTAMP | Date de mise à jour |

### Produits
| Champ | Type | Description |
|-------|------|-------------|
| id | INT | Identifiant unique |
| name | VARCHAR | Nom du produit |
| quantity | INT | Quantité en stock |
| price | DECIMAL | Prix |
| category | VARCHAR | Catégorie |
| code_product | VARCHAR | Code produit |
| created_at | TIMESTAMP | Date de création |
| updated_at | TIMESTAMP | Date de mise à jour |

## 🚧 Limitations Connues

- Durée de vie limitée des tokens
- Besoin de refresh manuel des tokens après changement de rôle
- Gestion manuelle des permissions

## 🔜 Évolutions Futures

### Fonctionnalités
- Système de gestion des stocks avancé
- Module de commandes complet
- Gestion des approvisionnements
- Rapports et statistiques
- Interface d'administration détaillée

### Améliorations Techniques
- Système de cache pour les tokens
- Gestion fine des permissions
- Logs d'audit complets
- Intégration de notifications
- Tests unitaires et d'intégration
- Documentation API complète (Swagger/OpenAPI)

## 📖 Guide d'Installation

### Cloner le projet
```
git clone https://github.com/Logipek/shop-backend.git
```

### Installer les dépendances
```
composer install
```

### Configurer les variables d'environnement
```
cp .env.example .env
```
### Éditer .env avec vos configurations

### Initialiser la base de données
```
php bin/migrate.php
```
### Lancer le serveur
```
php -S localhost:8000
```
## 🤝 Contribution

1. Forker le projet
2. Créer une branche de fonctionnalité (`git checkout -b feature/AmeliorationX`)
3. Commiter vos modifications (`git commit -am 'Ajout fonctionnalité X'`)
4. Pusher la branche (`git push origin feature/AmeliorationX`)
5. Créer une Pull Request

## 🐛 Rapport de Bugs

Pour rapporter un bug, merci d'ouvrir une issue sur le dépôt GitHub avec :
- Description détaillée
- Étapes de reproduction
- Version du logiciel
- Captures d'écran (si possible)

## 📄 Licence

[Licence MIT]

---

**Note technique :** Projet de gestion d'inventaire modulaire, sécurisé et évolutif.
