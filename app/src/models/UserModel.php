<?php

namespace App\Models;

use \PDO;
use stdClass;
use App\Utils\HttpException;

class UserModel extends SqlConnect {
    private $table = "users";
    public $authorized_fields_to_update = ['first_name', 'last_name', 'email', 'role'];

    public function add(array $data) {
      // Vérifier les champs obligatoires
      if (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name'])) {
        throw new HttpException("Champs obligatoires manquants", 400);
      }
      
      // Vérifier si l'email existe déjà
      $checkEmail = $this->db->prepare("SELECT id FROM $this->table WHERE email = :email");
      $checkEmail->execute(["email" => $data['email']]);
      if ($checkEmail->rowCount() > 0) {
        throw new HttpException("Cet email est déjà utilisé", 400);
      }
      
      // Hasher le mot de passe
      $passwordSalt = "sqidq7sà"; // Idéalement, cela devrait être dans une variable d'environnement
      $saltedPassword = $data["password"] . $passwordSalt;
      $hashedPassword = password_hash($saltedPassword, PASSWORD_BCRYPT);
      
      $query = "
        INSERT INTO $this->table (first_name, last_name, email, password, role)
        VALUES (:first_name, :last_name, :email, :password, :role)
      ";

      $req = $this->db->prepare($query);
      $req->execute([
        "first_name" => $data['first_name'],
        "last_name" => $data['last_name'],
        "email" => $data['email'],
        "password" => $hashedPassword,
        "role" => $data['role'] ?? 'employee' // Par défaut, un nouvel utilisateur est un employé
      ]);
      
      return $this->getLast();
    }

    public function delete(int $id) {
      $req = $this->db->prepare("DELETE FROM $this->table WHERE id = :id");
      $req->execute(["id" => $id]);
      return ["message" => "Utilisateur supprimé avec succès"];
    }

    public function get(int $id) {
      $query = "SELECT * FROM $this->table WHERE id = :id";
      $req = $this->db->prepare($query);
      $req->execute(["id" => $id]);
      
      $user = $req->fetch(PDO::FETCH_ASSOC);
      if (!$user) {
        return null;
      }
      
      // Ne pas renvoyer le mot de passe
      unset($user['password']);
      
      return $user;
    }

    public function getAll(?int $limit = null) {
      $query = "SELECT id, first_name, last_name, email, role, created_at FROM {$this->table}";
      
      if ($limit !== null) {
          $query .= " LIMIT :limit";
          $params = [':limit' => (int)$limit];
      } else {
          $params = [];
      }
      
      $req = $this->db->prepare($query);
      foreach ($params as $key => $value) {
          $req->bindValue($key, $value, PDO::PARAM_INT);
      }
      $req->execute();
      
      return $req->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLast() {
      $req = $this->db->prepare("SELECT id, first_name, last_name, email, role, created_at FROM $this->table ORDER BY id DESC LIMIT 1");
      $req->execute();

      return $req->rowCount() > 0 ? $req->fetch(PDO::FETCH_ASSOC) : new stdClass();
    }

    public function update(array $data, int $id) {
      $request = "UPDATE $this->table SET ";
      $params = [];
      $fields = [];
  
      # Prepare the query dynamically based on the provided data
      foreach ($data as $key => $value) {
          if (in_array($key, $this->authorized_fields_to_update)) {
              $fields[] = "$key = :$key";
              $params[":$key"] = $value;
          }
      }
  
      $params[':id'] = $id;
      $query = $request . implode(", ", $fields) . " WHERE id = :id";
  
      $req = $this->db->prepare($query);
      $req->execute($params);
      
      return $this->get($id);
    }
    
    public function updatePassword(int $id, string $oldPassword, string $newPassword) {
      // Récupérer l'utilisateur
      $req = $this->db->prepare("SELECT password FROM $this->table WHERE id = :id");
      $req->execute(["id" => $id]);
      $user = $req->fetch(PDO::FETCH_ASSOC);
      
      if (!$user) {
        throw new HttpException("Utilisateur non trouvé", 404);
      }
      
      // Vérifier l'ancien mot de passe
      $passwordSalt = "sqidq7sà";
      $saltedOldPassword = $oldPassword . $passwordSalt;
      
      if (!password_verify($saltedOldPassword, $user['password'])) {
        throw new HttpException("Ancien mot de passe incorrect", 400);
      }
      
      // Hasher le nouveau mot de passe
      $saltedNewPassword = $newPassword . $passwordSalt;
      $hashedNewPassword = password_hash($saltedNewPassword, PASSWORD_BCRYPT);
      
      // Mettre à jour le mot de passe
      $req = $this->db->prepare("UPDATE $this->table SET password = :password WHERE id = :id");
      $req->execute([
        "id" => $id,
        "password" => $hashedNewPassword
      ]);
      
      return ["message" => "Mot de passe mis à jour avec succès"];
    }
    
    public function getUserByEmail(string $email) {
      $req = $this->db->prepare("SELECT * FROM $this->table WHERE email = :email");
      $req->execute(["email" => $email]);
      
      return $req->fetch(PDO::FETCH_ASSOC);
    }
}