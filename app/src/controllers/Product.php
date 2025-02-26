<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Models\ProductModel;
use App\Utils\{Route, HttpException};
use App\Middlewares\AuthMiddleware;
use App\Middlewares\RoleMiddleware;

class Product extends Controller {
  protected object $product;

  public function __construct($params) {
    $this->product = new ProductModel();
    parent::__construct($params);
  }

  #[Route("GET", "/products", middlewares: [AuthMiddleware::class])]
  public function getProducts() {
    $limit = isset($this->params['limit']) ? intval($this->params['limit']) : null;
    return $this->product->getAll($limit);
  }

  #[Route("GET", "/products/:id", middlewares: [AuthMiddleware::class])]
  public function getProduct() {
    try {
      return $this->product->getById(intval($this->params["id"]));
    } catch (\Exception $e) {
      throw new HttpException($e->getMessage(), 404);
    }
  }

  #[Route("POST", "/products", middlewares: ["role:admin,manager"])]
  public function createProduct() {
    try {
      return $this->product->create($this->body);
    } catch (\Exception $e) {
      throw new HttpException($e->getMessage(), 400);
    }
  }

  #[Route("PUT", "/products/:id", middlewares: ["role:admin,manager"])]
  public function updateProduct() {
    try {
      return $this->product->update(intval($this->params["id"]), $this->body);
    } catch (\Exception $e) {
      throw new HttpException($e->getMessage(), 400);
    }
  }

  #[Route("DELETE", "/products/:id", middlewares: ["role:admin"])]
  public function deleteProduct() {
    try {
      return $this->product->delete(intval($this->params["id"]));
    } catch (\Exception $e) {
      throw new HttpException($e->getMessage(), 400);
    }
  }

  #[Route("PATCH", "/products/:id/stock", middlewares: ["role:admin,manager,employee"])]
  public function updateStock() {
    try {
      if (!isset($this->body['quantity'])) {
        throw new HttpException("Quantité non spécifiée", 400);
      }
      return $this->product->updateStock(intval($this->params["id"]), intval($this->body['quantity']));
    } catch (\Exception $e) {
      throw new HttpException($e->getMessage(), 400);
    }
  }
} 