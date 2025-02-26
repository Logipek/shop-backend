<?php

namespace App\Middlewares;

use App\Utils\{HttpException, JWT};

class RoleMiddleware {
  private array $allowedRoles;

  public function __construct(array $allowedRoles = []) {
    $this->allowedRoles = $allowedRoles;
  }

  public function handle() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';

    if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
      throw new HttpException('Unauthorized: No token provided', 401);
    }

    $jwt = $matches[1];

    try {
      $payload = JWT::verify($jwt);
      
      // Vérifier si le token est de type access
      if (!isset($payload->type) || $payload->type !== 'access') {
        throw new HttpException('Unauthorized: Invalid token type', 401);
      }
      
      // Vérifier si le token a expiré
      if (isset($payload->exp) && $payload->exp < time()) {
        throw new HttpException('Unauthorized: Token expired', 401);
      }
      
      // Récupérer l'utilisateur
      $userModel = new \App\Models\UserModel();
      $user = $userModel->get($payload->user_id);
      
      // Vérifier si l'utilisateur existe
      if (!$user || !isset($user['id'])) {
        throw new HttpException('Unauthorized: User not found', 401);
      }
      
      // Vérifier le rôle de l'utilisateur
      if (!empty($this->allowedRoles) && !in_array($user['role'], $this->allowedRoles)) {
        throw new HttpException('Forbidden: Insufficient permissions', 403);
      }
      
      // Ajouter l'utilisateur à la requête pour une utilisation ultérieure
      $_SERVER['AUTH_USER'] = $user;
      
      return true;
    } catch (\Exception $e) {
      throw new HttpException('Unauthorized: ' . $e->getMessage(), 401);
    }
  }
} 