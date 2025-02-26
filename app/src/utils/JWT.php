<?php

namespace App\Utils;

class JWT {
  private static string $secret = "your-secret-key"; // À remplacer par une clé secrète plus sécurisée
  
  public static function generate(array $payload): string {
    $header = json_encode([
      'typ' => 'JWT',
      'alg' => 'HS256'
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
    
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
  }
  
  public static function verify(string $jwt) {
    $tokenParts = explode('.', $jwt);
    
    if (count($tokenParts) != 3) {
      throw new \Exception("Invalid token format");
    }
    
    $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
    $signatureProvided = $tokenParts[2];
    
    // Vérifier la signature
    $base64UrlHeader = $tokenParts[0];
    $base64UrlPayload = $tokenParts[1];
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    if ($base64UrlSignature !== $signatureProvided) {
      throw new \Exception("Invalid signature");
    }
    
    return json_decode($payload);
  }
}