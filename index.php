<?php
/**
 * index.php
 * Entry point for the API
 */

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit;
}

// Load the autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load the configuration
require_once __DIR__ . '/config/config.php';

// Check if we're in maintenance mode
if (defined('MAINTENANCE_MODE') && MAINTENANCE_MODE) {
    header('HTTP/1.1 503 Service Unavailable');
    echo json_encode([
        'status' => 'error',
        'message' => 'System is under maintenance. Please try again later.'
    ]);
    exit;
}

// Parse the URL to get the route
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove the base path from the path
if ($basePath !== '/' && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Remove leading and trailing slashes
$path = trim($path, '/');

// Add API version prefix if not already in the path
if (!empty($path) && strpos($path, API_VERSION) !== 0) {
    $path = API_VERSION . '/' . $path;
}

// If path is empty, set it to the API version
if (empty($path)) {
    $path = API_VERSION;
}

// Parse the query string
$queryString = parse_url($requestUri, PHP_URL_QUERY);
parse_str($queryString ?? '', $queryParams);

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the request body for POST, PUT methods
$body = [];
if (in_array($method, ['POST', 'PUT'])) {
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        $input = file_get_contents('php://input');
        $body = json_decode($input, true) ?? [];
    } else {
        $body = $_POST;
    }
}

// Load files from $_FILES
$files = $_FILES;

// Create the request object
$request = [
    'method' => $method,
    'path' => $path,
    'query' => $queryParams,
    'body' => $body,
    'files' => $files,
    'headers' => getallheaders()
];

// Process the route
try {
    require_once __DIR__ . '/routes/api.php';
    
    // Route not found
    \Utils\Response::notFound('Endpoint not found');
} catch (\Exception $e) {
    if (DEBUG_MODE) {
        \Utils\Response::error('Server Error: ' . $e->getMessage(), 500);
    } else {
        \Utils\Response::serverError();
    }
}