<?php
/**
 * middleware.php
 * Middleware functions for request processing
 */

use Services\AuthService;
use Utils\Response;

/**
 * Authentication middleware
 * 
 * @param array $request
 * @param bool $checkAdmin
 * @return array
 */
function authMiddleware($request, $checkAdmin = false) {
    $authService = new AuthService();
    
    try {
        // Verify the token
        $tokenData = $authService->verifyRequest($request, $checkAdmin);
        
        // Add user data to request
        $request['user'] = $tokenData;
        
        return $request;
    } catch (\Exception $e) {
        // If token verification fails, AuthService will throw an exception
        // which will be caught by the global error handler
        Response::unauthorized('Authentication required');
    }
}

/**
 * Admin authentication middleware
 * 
 * @param array $request
 * @return array
 */
function adminAuthMiddleware($request) {
    return authMiddleware($request, true);
}

/**
 * CORS middleware
 * 
 * @param array $request
 * @return array
 */
function corsMiddleware($request) {
    // Set CORS headers
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        header("HTTP/1.1 200 OK");
        exit;
    }
    
    return $request;
}

/**
 * Content type middleware
 * 
 * @param array $request
 * @return array
 */
function contentTypeMiddleware($request) {
    // Set content type header
    header('Content-Type: application/json');
    
    return $request;
}

/**
 * Rate limiting middleware
 * 
 * @param array $request
 * @param int $limit Maximum requests per minute
 * @return array
 */
function rateLimitMiddleware($request, $limit = 60) {
    // Get client IP
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Use a simple in-memory cache (could be replaced with Redis/Memcached in production)
    $cache = new \Services\CacheService();
    
    // Get current count
    $key = "rate_limit:{$ip}:" . date('YmdHi'); // Key includes minute
    $count = $cache->get($key) ?? 0;
    
    // Increment count
    $cache->set($key, $count + 1, 60); // Expire in 60 seconds
    
    // Check if limit exceeded
    if ($count >= $limit) {
        Response::error('Rate limit exceeded. Please try again later.', 429);
    }
    
    // Add rate limit headers
    header("X-RateLimit-Limit: {$limit}");
    header("X-RateLimit-Remaining: " . max(0, $limit - $count - 1));
    
    return $request;
}

/**
 * Input sanitization middleware
 * 
 * @param array $request
 * @return array
 */
function sanitizeInputMiddleware($request) {
    // Sanitize query parameters
    if (isset($request['query']) && is_array($request['query'])) {
        foreach ($request['query'] as $key => $value) {
            // Sanitize the value
            $request['query'][$key] = filter_var($value, FILTER_SANITIZE_STRING);
        }
    }
    
    // Sanitize body parameters if it's not a file upload
    if (isset($request['body']) && is_array($request['body']) && empty($request['files'])) {
        $sanitized = [];
        
        foreach ($request['body'] as $key => $value) {
            if (is_array($value)) {
                // Recursively sanitize arrays
                $sanitized[$key] = sanitizeArray($value);
            } else {
                // Sanitize the value
                $sanitized[$key] = filter_var($value, FILTER_SANITIZE_STRING);
            }
        }
        
        $request['body'] = $sanitized;
    }
    
    return $request;
}

/**
 * Helper function to sanitize arrays recursively
 * 
 * @param array $array
 * @return array
 */
function sanitizeArray($array) {
    $result = [];
    
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result[$key] = sanitizeArray($value);
        } else {
            $result[$key] = filter_var($value, FILTER_SANITIZE_STRING);
        }
    }
    
    return $result;
}

/**
 * Logger middleware
 * 
 * @param array $request
 * @return array
 */
function loggerMiddleware($request) {
    // Don't log sensitive data
    $safeRequest = $request;
    
    // Remove sensitive data from the request
    if (isset($safeRequest['body']['password'])) {
        $safeRequest['body']['password'] = '******';
    }
    
    if (isset($safeRequest['body']['owner_password'])) {
        $safeRequest['body']['owner_password'] = '******';
    }
    
    // Create log entry
    $logEntry = [
        'time' => date('Y-m-d H:i:s'),
        'path' => $safeRequest['path'],
        'method' => $safeRequest['method'],
        'ip' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'request' => json_encode($safeRequest)
    ];
    
    // Log to file
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log(json_encode($logEntry) . PHP_EOL, 3, LOG_DIR . 'api_' . date('Y-m-d') . '.log');
    }
    
    return $request;
}

/**
 * Apply multiple middleware functions
 * 
 * @param array $request
 * @param array $middleware
 * @return array
 */
function applyMiddleware($request, $middleware) {
    foreach ($middleware as $func) {
        $request = call_user_func($func, $request);
    }
    
    return $request;
}