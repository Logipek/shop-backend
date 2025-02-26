<?php 

namespace App\Controllers;

use App\Controllers\Controller;
use App\Models\AuthModel;
use App\Utils\{Route, HttpException};

class Auth extends Controller {
  protected object $auth;

  public function __construct($params) {
    $this->auth = new AuthModel();
    parent::__construct($params);
  }


  #[Route("POST", "/auth/register")]
  public function register() {
      try {
          $data = $this->body;
          if (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name'])) {
              throw new HttpException("Champs obligatoires manquants", 400);
          }
          $user = $this->auth->register($data);
          return $user;
      } catch (\Exception $e) {
          throw new HttpException($e->getMessage(), 400);
      }
  }

  #[Route("POST", "/auth/login")]
  public function login() {
      try {
          $data = $this->body;
          if (empty($data['email']) || empty($data['password'])) {
              throw new HttpException("Missing email or password.", 400);
          }
          $token = $this->auth->login($data['email'], $data['password']);
          return $token;
      } catch (\Exception $e) {
          throw new HttpException($e->getMessage(), 401);
      }
  }

  #[Route("POST", "/auth/refresh")]
  public function refresh() {
      try {
          $data = $this->body;
          if (empty($data['refresh_token'])) {
              throw new HttpException("Missing refresh token", 400);
          }
          $tokens = $this->auth->refreshToken($data['refresh_token']);
          return $tokens;
      } catch (\Exception $e) {
          throw new HttpException($e->getMessage(), 401);
      }
  }
  
  #[Route("POST", "/auth/logout")]
  public function logout() {
      try {
          $data = $this->body;
          if (empty($data['refresh_token'])) {
              throw new HttpException("Missing refresh token", 400);
          }
          $result = $this->auth->logout($data['refresh_token']);
          return $result;
      } catch (\Exception $e) {
          throw new HttpException($e->getMessage(), 400);
      }
  }
}