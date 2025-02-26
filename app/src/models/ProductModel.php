<?php

namespace App\Models;

use App\Models\SqlConnect;
use \PDO;
use App\Utils\HttpException;

class ProductModel extends SqlConnect {
  private string $table = "products";
  
  public function getAll(?int $limit = null) {
    $query = "SELECT * FROM {$this->table}";
    
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
  
  public function getById(int $id) {
    $query = "SELECT * FROM $this->table WHERE id = :id";
    $req = $this->db->prepare($query);
    $req->execute(["id" => $id]);
    
    $product = $req->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
      throw new HttpException("Produit non trouvé", 404);
    }
    
    return $product;
  }
  
  public function create(array $data) {
    if (empty($data['name']) || empty($data['price']) || !isset($data['stock_quantity'])) {
      throw new HttpException("Données de produit incomplètes", 400);
    }
    
    $query = "INSERT INTO $this->table (name, description, price, stock_quantity) 
              VALUES (:name, :description, :price, :stock_quantity)";
    $req = $this->db->prepare($query);
    $req->execute([
      "name" => $data['name'],
      "description" => $data['description'] ?? '',
      "price" => $data['price'],
      "stock_quantity" => $data['stock_quantity']
    ]);
    
    $id = $this->db->lastInsertId();
    return $this->getById($id);
  }
  
  public function update(int $id, array $data) {
    // Vérifier si le produit existe
    $this->getById($id);
    
    $fields = [];
    $params = [];
    
    // Construire dynamiquement la requête
    foreach (['name', 'description', 'price', 'stock_quantity'] as $field) {
      if (isset($data[$field])) {
        $fields[] = "$field = :$field";
        $params[":$field"] = $data[$field];
      }
    }
    
    if (empty($fields)) {
      throw new HttpException("Aucune donnée fournie pour la mise à jour", 400);
    }
    
    $params[':id'] = $id;
    $query = "UPDATE $this->table SET " . implode(', ', $fields) . " WHERE id = :id";
    
    $req = $this->db->prepare($query);
    $req->execute($params);
    
    return $this->getById($id);
  }
  
  public function delete(int $id) {
    // Vérifier si le produit existe
    $this->getById($id);
    
    $query = "DELETE FROM $this->table WHERE id = :id";
    $req = $this->db->prepare($query);
    $req->execute(["id" => $id]);
    
    return ["message" => "Produit supprimé avec succès"];
  }
  
  public function updateStock(int $id, int $quantity) {
    try {
      // Utiliser FOR UPDATE pour verrouiller la ligne pendant la lecture
      $query = "SELECT stock_quantity FROM $this->table WHERE id = :id FOR UPDATE";
      $this->db->beginTransaction();
      $req = $this->db->prepare($query);
      $req->execute(["id" => $id]);
      
      $product = $req->fetch(PDO::FETCH_ASSOC);
      if (!$product) {
        $this->db->rollBack();
        throw new HttpException("Produit non trouvé", 404);
      }
      
      $newQuantity = $product['stock_quantity'] + $quantity;
      if ($newQuantity < 0) {
        $this->db->rollBack();
        throw new HttpException("Stock insuffisant", 400);
      }
      
      $query = "UPDATE $this->table SET stock_quantity = :quantity WHERE id = :id";
      $req = $this->db->prepare($query);
      $req->execute([
        "id" => $id,
        "quantity" => $newQuantity
      ]);
      
      $this->db->commit();
      return $this->getById($id);
    } catch (\Exception $e) {
      if ($this->db->inTransaction()) {
        $this->db->rollBack();
      }
      throw new HttpException($e->getMessage(), 400);
    }
  }
} 