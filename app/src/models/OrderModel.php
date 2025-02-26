<?php

namespace App\Models;

use App\Models\SqlConnect;
use App\Models\ProductModel;
use \PDO;
use App\Utils\HttpException;

class OrderModel extends SqlConnect {
  private string $table = "orders";
  private string $itemsTable = "order_items";
  
  public function getAll(?int $limit = null) {
    $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
    
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
    
    $orders = $req->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les items pour chaque commande
    foreach ($orders as &$order) {
      $order['items'] = $this->getOrderItems($order['id']);
    }
    
    return $orders;
  }
  
  public function getById(int $id) {
    $query = "SELECT * FROM $this->table WHERE id = :id";
    $req = $this->db->prepare($query);
    $req->execute(["id" => $id]);
    
    $order = $req->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
      throw new HttpException("Commande non trouvée", 404);
    }
    
    // Récupérer les items de la commande
    $order['items'] = $this->getOrderItems($id);
    
    return $order;
  }
  
  private function getOrderItems(int $orderId) {
    $query = "SELECT oi.*, p.name as product_name 
              FROM $this->itemsTable oi
              JOIN products p ON oi.product_id = p.id
              WHERE oi.order_id = :order_id";
    $req = $this->db->prepare($query);
    $req->execute(["order_id" => $orderId]);
    
    return $req->fetchAll(PDO::FETCH_ASSOC);
  }
  
  public function create(array $data) {
    error_log("Début de la méthode create");
    
    if (empty($data['customer_name']) || empty($data['items'])) {
      error_log("Données incomplètes");
      throw new HttpException("Données de commande incomplètes", 400);
    }
    
    error_log("Conversion des items");
    // Convertir les objets stdClass en tableaux si nécessaire
    $items = $data['items'];
    if (is_array($items)) {
      foreach ($items as $key => $item) {
        if (is_object($item)) {
          $items[$key] = (array) $item;
        }
      }
    } else {
      error_log("Items ne sont pas un tableau");
      throw new HttpException("Les items doivent être un tableau", 400);
    }
    
    try {
      error_log("Création de la commande");
      // Créer la commande sans transaction
      $query = "INSERT INTO $this->table (customer_name, status, total_amount) 
                VALUES (:customer_name, :status, :total_amount)";
      $req = $this->db->prepare($query);
      $req->execute([
        "customer_name" => $data['customer_name'],
        "status" => $data['status'] ?? 'pending',
        "total_amount" => 0 // Sera mis à jour après l'ajout des items
      ]);
      
      $orderId = $this->db->lastInsertId();
      error_log("Commande créée avec ID: " . $orderId);
      
      $totalAmount = 0;
      $productModel = new ProductModel();
      
      // Ajouter les items de la commande un par un
      error_log("Ajout des items");
      foreach ($items as $item) {
        error_log("Traitement de l'item: " . json_encode($item));
        // Convertir en tableau si c'est un objet
        if (is_object($item)) {
          $item = (array) $item;
        }
        
        if (empty($item['product_id']) || empty($item['quantity'])) {
          error_log("Données d'item incomplètes");
          throw new HttpException("Données d'item incomplètes", 400);
        }
        
        // Vérifier le stock disponible et mettre à jour dans une transaction séparée
        error_log("Vérification du stock pour le produit ID: " . $item['product_id']);
        $product = $productModel->getById($item['product_id']);
        
        // Vérifier si le stock est suffisant avant de le mettre à jour
        if ($product['stock_quantity'] < $item['quantity']) {
          error_log("Stock insuffisant");
          throw new HttpException("Stock insuffisant pour le produit: " . $product['name'], 400);
        }
        
        // Ajouter l'item
        error_log("Ajout de l'item à la commande");
        $query = "INSERT INTO $this->itemsTable (order_id, product_id, quantity, unit_price) 
                  VALUES (:order_id, :product_id, :quantity, :unit_price)";
        $req = $this->db->prepare($query);
        $req->execute([
          "order_id" => $orderId,
          "product_id" => $item['product_id'],
          "quantity" => $item['quantity'],
          "unit_price" => $product['price']
        ]);
        
        // Mettre à jour le stock dans une transaction séparée
        error_log("Mise à jour du stock");
        try {
          $productModel->updateStock($item['product_id'], -$item['quantity']);
        } catch (\Exception $e) {
          error_log("Erreur lors de la mise à jour du stock: " . $e->getMessage());
          // Si la mise à jour du stock échoue, on continue avec les autres items
          // mais on garde une trace de l'erreur
        }
        
        // Calculer le montant total
        $totalAmount += $product['price'] * $item['quantity'];
      }
      
      // Mettre à jour le montant total de la commande
      error_log("Mise à jour du montant total");
      $query = "UPDATE $this->table SET total_amount = :total_amount WHERE id = :id";
      $req = $this->db->prepare($query);
      $req->execute([
        "id" => $orderId,
        "total_amount" => $totalAmount
      ]);
      
      error_log("Récupération de la commande complète");
      return $this->getById($orderId);
    } catch (\Exception $e) {
      error_log("Erreur: " . $e->getMessage());
      // Pas besoin de rollback car nous n'avons pas de transaction globale
      throw new HttpException($e->getMessage(), 400);
    }
  }
  
  public function updateStatus(int $id, string $status) {
    $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
    
    if (!in_array($status, $validStatuses)) {
      throw new HttpException("Statut invalide", 400);
    }
    
    // Vérifier si la commande existe
    $this->getById($id);
    
    $query = "UPDATE $this->table SET status = :status WHERE id = :id";
    $req = $this->db->prepare($query);
    $req->execute([
      "id" => $id,
      "status" => $status
    ]);
    
    return $this->getById($id);
  }
  
  public function processReturn(int $id, array $items) {
    if (empty($items)) {
      throw new HttpException("Aucun item spécifié pour le retour", 400);
    }
    
    // Convertir les objets stdClass en tableaux si nécessaire
    if (is_array($items)) {
      foreach ($items as $key => $item) {
        if (is_object($item)) {
          $items[$key] = (array) $item;
        }
      }
    } else {
      throw new HttpException("Les items doivent être un tableau", 400);
    }
    
    try {
      $this->db->beginTransaction();
      
      $order = $this->getById($id);
      if ($order['status'] === 'returned') {
        throw new HttpException("Cette commande a déjà été retournée", 400);
      }
      
      $productModel = new ProductModel();
      
      foreach ($items as $item) {
        // Convertir en tableau si c'est un objet
        if (is_object($item)) {
          $item = (array) $item;
        }
        
        if (empty($item['product_id']) || empty($item['quantity'])) {
          throw new HttpException("Données d'item incomplètes", 400);
        }
        
        // Vérifier si l'item fait partie de la commande
        $found = false;
        foreach ($order['items'] as $orderItem) {
          if ($orderItem['product_id'] == $item['product_id']) {
            if ($orderItem['quantity'] < $item['quantity']) {
              throw new HttpException("Quantité de retour supérieure à la quantité commandée", 400);
            }
            $found = true;
            break;
          }
        }
        
        if (!$found) {
          throw new HttpException("Produit non trouvé dans la commande", 400);
        }
        
        // Remettre en stock
        $productModel->updateStock($item['product_id'], $item['quantity']);
      }
      
      // Mettre à jour le statut de la commande
      $this->updateStatus($id, 'returned');
      
      $this->db->commit();
      
      return $this->getById($id);
    } catch (\Exception $e) {
      $this->db->rollBack();
      throw new HttpException($e->getMessage(), 400);
    }
  }
} 