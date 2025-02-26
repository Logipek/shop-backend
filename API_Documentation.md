# 🚀 API Documentation

Cette documentation décrit les endpoints de l'API REST.

## 📑 Table des matières
- [Introduction](#introduction)
- [Authentification](#authentification)
- [Utilisateurs](#utilisateurs)
- [Produits](#produits)
- [Catégories](#catégories)
- [Commandes](#commandes)
- [Erreur et Codes HTTP](#erreur-et-codes-http)

---

## 🎯 Introduction

Cette API permet de gérer un système avec des utilisateurs, des produits, des catégories et des commandes.

- **Base URL** : `http://localhost:80/api`
- **Format des réponses** : JSON
- **Authentification** : JWT (JSON Web Tokens)

---

## 🔑 Authentification

### Connexion

```http
POST /auth/login
```

**Body :**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Réponse :**
```json
{
  "token": "your_jwt_token",
  "user": { "id": 1, "name": "John Doe", "email": "user@example.com" }
}
```

### Inscription

```http
POST /auth/register
```

**Body :**
```json
{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "password123"
}
```

**Réponse :**
```json
{
  "message": "Inscription réussie"
}
```

---

## 👥 Utilisateurs

### Liste des utilisateurs

```http
GET /users
Authorization: Bearer {token}
```

### Détails d'un utilisateur

```http
GET /users/{id}
Authorization: Bearer {token}
```

### Création d'un utilisateur

```http
POST /users
Authorization: Bearer {token}
Content-Type: application/json
```

**Body :**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "role": "admin"
}
```

---

## 🛍️ Produits

### Liste des produits

```http
GET /products
```

### Détails d'un produit

```http
GET /products/{id}
```

### Création d'un produit

```http
POST /products
Authorization: Bearer {token}
```

**Body :**
```json
{
  "name": "Produit X",
  "price": 99.99,
  "description": "Description du produit",
  "category_id": 1
}
```

---

## 📂 Catégories

### Liste des catégories

```http
GET /categories
```

### Création d'une catégorie

```http
POST /categories
Authorization: Bearer {token}
```

**Body :**
```json
{
  "name": "Catégorie X",
  "description": "Description de la catégorie"
}
```

---

## 📦 Commandes

### Liste des commandes

```http
GET /orders
Authorization: Bearer {token}
```

### Création d'une commande

```http
POST /orders
Authorization: Bearer {token}
```

**Body :**
```json
{
  "products": [
    { "product_id": 1, "quantity": 2 }
  ]
}
```

---

## ❌ Erreur et Codes HTTP

| Code | Description |
|------|------------|
| 400  | Requête invalide |
| 401  | Non authentifié |
| 403  | Non autorisé |
| 404  | Ressource non trouvée |
| 422  | Erreur de validation |
| 500  | Erreur serveur |
