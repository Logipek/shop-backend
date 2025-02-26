<?php 

namespace App\Middlewares;

use App\Utils\{HttpException, JWT};

class AuthMiddleware {
    public function handle() {
        $headers = getallheaders();
        
        // Check if the Authorization header is set
        if (!isset($headers['Authorization'])) {
            // Return an appropriate response or throw an exception
            throw new HttpException('Unauthorized: No token provided', 401);
        }

        $authHeader = $headers['Authorization'];

        // Check if the Authorization header contains a bearer token
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            throw new HttpException('Unauthorized: No valid Bearer token', 401);
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
            
            // Ajouter l'ID de l'utilisateur à la requête pour une utilisation ultérieure
            $_SERVER['AUTH_USER_ID'] = $payload->user_id;
            
            return true;
        } catch (\Exception $e) {
            throw new HttpException('Unauthorized: ' . $e->getMessage(), 401);
        }
    }

    // Helper method to return an unauthorized response
    private function unauthorizedResponse() {
        // Here, you could return a response with a 401 status code and an error message
        echo json_encode(['error' => "Unauthorized"]);
        http_response_code(401);
        return false;
    }
}