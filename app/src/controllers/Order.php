<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Models\OrderModel;
use App\Utils\{Route, HttpException};
use App\Middlewares\AuthMiddleware;

class Order extends Controller {
  protected object $order;

  public function __construct($params) {
    $this->order = new OrderModel();
    parent::__construct($params);
  }

  #[Route("GET", "/orders", middlewares: [AuthMiddleware::class])]
  public function getOrders() {
    $limit = isset($this->params['limit']) ? intval($this->params['limit']) : null;
    return $this->order->getAll($limit);
  }

  #[Route("GET", "/orders/:id", middlewares: [AuthMiddleware::class])]
  public function getOrder() {
    try {
      return $this->order->getById(intval($this->params["id"]));
    } catch (\Exception $e) {
      throw new HttpException($e->getMessage(), 404);
    }
  }

  #[Route("POST", "/orders", middlewares: [AuthMiddleware::class])]
  public function createOrder() {
    try {
      return $this->order->create($this->body);
    } catch (\Exception $e) {
      throw new HttpException($e->getMessage(), 400);
    }
  }

  #[Route("PATCH", "/orders/:id/status", middlewares: ["role:admin,manager"])]
  public function updateOrderStatus() {
    try {
      if (empty($this->body['status'])) {
        throw new HttpException("Statut non spécifié", 400);
      }
      return $this->order->updateStatus(intval($this->params["id"]), $this->body['status']);
    } catch (\Exception $e) {
      throw new HttpException($e->getMessage(), 400);
    }
  }

  #[Route("POST", "/orders/:id/return", middlewares: [AuthMiddleware::class])]
  public function processReturn() {
    try {
      if (!isset($this->body['items']) || !is_array($this->body['items'])) {
        throw new HttpException("Items non spécifiés", 400);
      }
      return $this->order->processReturn(intval($this->params["id"]), $this->body['items']);
    } catch (\Exception $e) {
      throw new HttpException($e->getMessage(), 400);
    }
  }
} 