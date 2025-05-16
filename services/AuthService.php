<?php
/**
 * AuthService.php
 * Service for authentication and authorization
 */
namespace Services;

use Utils\Response;

class AuthService {
    /**
     * Generate JWT token
     * 
     * @param array $data
     * @return string
     */
    public function generateToken($data) {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]));
        
        $payload = $this->base64UrlEncode(json_encode([
            'sub' => $data['id'],
            'email' => $data['email'],
            'is_super_admin' => $data['is_super_admin'] ?? false,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ]));
        
        $signature = $this->base64UrlEncode(hash_hmac(
            'sha256',
            $header . '.' . $payload,
            JWT_SECRET,
            true
        ));
        
        return $header . '.' . $payload . '.' . $signature;
    }
    
    /**
     * Verify JWT token
     * 
     * @param string $token
     * @return array|false
     */
    public function verifyToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        
        $expectedSignature = $this->base64UrlEncode(hash_hmac(
            'sha256',
            $header . '.' . $payload,
            JWT_SECRET,
            true
        ));
        
        // Verify signature
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }
        
        // Decode payload
        $payloadData = json_decode($this->base64UrlDecode($payload), true);
        
        // Check expiration
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return false;
        }
        
        return $payloadData;
    }
    
    /**
     * Verify request authorization
     * 
     * @param array $request
     * @param bool $checkAdmin Check if user is admin
     * @return array
     */
    public function verifyRequest($request, $checkAdmin = false) {
        // Check if Authorization header exists
        if (!isset($request['headers']['Authorization']) && !isset($request['headers']['authorization'])) {
            Response::unauthorized('Authorization header is required');
        }
        
        // Get token from header
        $authHeader = isset($request['headers']['Authorization']) 
            ? $request['headers']['Authorization'] 
            : $request['headers']['authorization'];
        
        $token = null;
        
        // Check if token is in Bearer format
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            Response::unauthorized('Invalid Authorization header format');
        }
        
        // Verify token
        $tokenData = $this->verifyToken($token);
        
        if (!$tokenData) {
            Response::unauthorized('Invalid or expired token');
        }
        
        // Check if admin
        if ($checkAdmin && (!isset($tokenData['is_super_admin']) || !$tokenData['is_super_admin'])) {
            Response::forbidden('Admin access required');
        }
        
        return $tokenData;
    }
    
    /**
     * Encode data for URL-safe base64
     * 
     * @param string $data
     * @return string
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Decode URL-safe base64 data
     * 
     * @param string $data
     * @return string
     */
    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Hash password
     * 
     * @param string $password
     * @return string
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    /**
     * Verify password
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}