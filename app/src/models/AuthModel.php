<?php

namespace App\Models;

use App\Models\SqlConnect;
use App\Utils\{HttpException, JWT};
use \PDO;

class AuthModel extends SqlConnect {
  private string $table = "users";
  private string $tokenTable = "refresh_tokens";
  private int $accessTokenValidity = 900; // 15 minutes
  private int $refreshTokenValidity = 2592000; // 30 jours
  private string $passwordSalt = "sqidq7sà";
  
  public function register(array $data) {
    // Vérifier les champs obligatoires
    if (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name'])) {
      throw new HttpException("Champs obligatoires manquants", 400);
    }
    
    $query = "SELECT email FROM $this->table WHERE email = :email";
    $req = $this->db->prepare($query);
    $req->execute(["email" => $data["email"]]);
    
    if ($req->rowCount() > 0) {
      throw new HttpException("User already exists!", 400);
    }

    // Combine password with salt and hash it
    $saltedPassword = $data["password"] . $this->passwordSalt;
    $hashedPassword = password_hash($saltedPassword, PASSWORD_BCRYPT);

    // Create the user
    $query_add = "INSERT INTO $this->table (email, password, first_name, last_name, role) 
                  VALUES (:email, :password, :first_name, :last_name, :role)";
    $req2 = $this->db->prepare($query_add);
    $req2->execute([
      "email" => $data["email"],
      "password" => $hashedPassword,
      "first_name" => $data["first_name"],
      "last_name" => $data["last_name"],
      "role" => $data["role"] ?? 'employee'
    ]);

    $userId = $this->db->lastInsertId();

    // Generate tokens
    $tokens = $this->generateTokens($userId);

    return [
      'access_token' => $tokens['access_token'],
      'refresh_token' => $tokens['refresh_token'],
      'user' => [
        'id' => $userId,
        'email' => $data['email'],
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'role' => $data['role'] ?? 'employee'
      ]
    ];
  }

  public function login($email, $password) {
    $query = "SELECT * FROM $this->table WHERE email = :email";
    $req = $this->db->prepare($query);
    $req->execute(['email' => $email]);

    $user = $req->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Combine input password with salt and verify
        $saltedPassword = $password . $this->passwordSalt;
        
        if (password_verify($saltedPassword, $user['password'])) {
            // Generate tokens
            $tokens = $this->generateTokens($user['id']);
            
            return [
              'access_token' => $tokens['access_token'],
              'refresh_token' => $tokens['refresh_token'],
              'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role']
              ]
            ];
        }
    }

    throw new \Exception("Invalid credentials.");
  }
  
  public function refreshToken($refreshToken) {
    // Vérifier si le refresh token existe et est valide
    $query = "SELECT * FROM $this->tokenTable WHERE token = :token AND expires_at > NOW()";
    $req = $this->db->prepare($query);
    $req->execute(['token' => $refreshToken]);
    
    $tokenData = $req->fetch(PDO::FETCH_ASSOC);
    
    if (!$tokenData) {
      throw new HttpException("Invalid or expired refresh token", 401);
    }
    
    // Vérifier si l'utilisateur existe toujours
    $query = "SELECT * FROM $this->table WHERE id = :id";
    $req = $this->db->prepare($query);
    $req->execute(['id' => $tokenData['user_id']]);
    
    $user = $req->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
      // Supprimer le refresh token si l'utilisateur n'existe plus
      $this->revokeRefreshToken($refreshToken);
      throw new HttpException("User not found", 401);
    }
    
    // Révoquer l'ancien refresh token
    $this->revokeRefreshToken($refreshToken);
    
    // Générer de nouveaux tokens
    $tokens = $this->generateTokens($user['id']);
    
    return [
      'access_token' => $tokens['access_token'],
      'refresh_token' => $tokens['refresh_token'],
      'user' => [
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => $user['role']
      ]
    ];
  }
  
  public function logout($refreshToken) {
    // Révoquer le refresh token
    $this->revokeRefreshToken($refreshToken);
    
    return ['message' => 'Déconnexion réussie'];
  }
  
  private function revokeRefreshToken($token) {
    $query = "DELETE FROM $this->tokenTable WHERE token = :token";
    $req = $this->db->prepare($query);
    $req->execute(['token' => $token]);
  }
  
  private function generateTokens(string $userId) {
    // Générer un access token
    $accessPayload = [
      'user_id' => $userId,
      'exp' => time() + $this->accessTokenValidity,
      'type' => 'access'
    ];
    $accessToken = JWT::generate($accessPayload);
    
    // Générer un refresh token
    $refreshToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + $this->refreshTokenValidity);
    
    // Stocker le refresh token dans la base de données
    $query = "INSERT INTO $this->tokenTable (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
    $req = $this->db->prepare($query);
    $req->execute([
      'user_id' => $userId,
      'token' => $refreshToken,
      'expires_at' => $expiresAt
    ]);
    
    return [
      'access_token' => $accessToken,
      'refresh_token' => $refreshToken
    ];
  }
}