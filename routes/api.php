<?php
/**
 * api.php
 * Defines the routes for the API
 */

use Utils\Response;
use Controllers\AdminController;
use Controllers\OrganizerController;
use Controllers\UssdController;
use Controllers\PaymentController;

// Simple router implementation
$router = function($method, $pattern, $callback) use ($request) {
    global $matched;
    
    if ($matched) return;
    
    // Check if method matches
    if ($method !== $request['method'] && $method !== 'ANY') return;
    
    // Convert URL pattern to regex
    $pattern = str_replace('/', '\/', $pattern);
    $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^\/]+)', $pattern);
    $pattern = '/^' . $pattern . '$/';
    
    // Check if pattern matches
    if (preg_match($pattern, $request['path'], $matches)) {
        $matched = true;
        
        // Extract parameters from URL
        $params = array_filter($matches, function($key) {
            return !is_numeric($key);
        }, ARRAY_FILTER_USE_KEY);
        
        // Run the callback with request and params
        $callback($request, $params);
    }
};

// Flag to track if a route was matched
$matched = false;

// API home
$router('GET', API_VERSION, function($request, $params) {
    Response::success([
        'name' => APP_NAME,
        'version' => API_VERSION,
        'timestamp' => date('Y-m-d H:i:s')
    ], 'Welcome to ' . APP_NAME . ' API');
});

// Admin routes
$router('POST', API_VERSION . '/admin/login', function($request, $params) {
    $controller = new AdminController();
    $controller->login($request);
});

$router('GET', API_VERSION . '/admin/events', function($request, $params) {
    $controller = new AdminController();
    $controller->getEvents($request);
});

$router('POST', API_VERSION . '/admin/events', function($request, $params) {
    $controller = new AdminController();
    $controller->createEvent($request);
});

$router('PUT', API_VERSION . '/admin/events/{id}', function($request, $params) {
    $controller = new AdminController();
    $controller->updateEvent($request, $params['id']);
});

$router('DELETE', API_VERSION . '/admin/events/{id}', function($request, $params) {
    $controller = new AdminController();
    $controller->deleteEvent($request, $params['id']);
});

$router('POST', API_VERSION . '/admin/upload/event-image', function($request, $params) {
    $controller = new AdminController();
    $controller->uploadEventImage($request);
});

// Organizer routes
$router('POST', API_VERSION . '/organizer/categories', function($request, $params) {
    $controller = new OrganizerController();
    $controller->createCategory($request);
});

$router('GET', API_VERSION . '/organizer/categories', function($request, $params) {
    $controller = new OrganizerController();
    $controller->getCategories($request);
});

$router('PUT', API_VERSION . '/organizer/categories/{id}', function($request, $params) {
    $controller = new OrganizerController();
    $controller->updateCategory($request, $params['id']);
});

$router('DELETE', API_VERSION . '/organizer/categories/{id}', function($request, $params) {
    $controller = new OrganizerController();
    $controller->deleteCategory($request, $params['id']);
});

$router('POST', API_VERSION . '/organizer/upload/category-image', function($request, $params) {
    $controller = new OrganizerController();
    $controller->uploadCategoryImage($request);
});

$router('POST', API_VERSION . '/organizer/nominees', function($request, $params) {
    $controller = new OrganizerController();
    $controller->createNominee($request);
});

$router('GET', API_VERSION . '/organizer/nominees', function($request, $params) {
    $controller = new OrganizerController();
    $controller->getNominees($request);
});

$router('PUT', API_VERSION . '/organizer/nominees/{id}', function($request, $params) {
    $controller = new OrganizerController();
    $controller->updateNominee($request, $params['id']);
});

$router('DELETE', API_VERSION . '/organizer/nominees/{id}', function($request, $params) {
    $controller = new OrganizerController();
    $controller->deleteNominee($request, $params['id']);
});

$router('POST', API_VERSION . '/organizer/upload/nominee-image', function($request, $params) {
    $controller = new OrganizerController();
    $controller->uploadNomineeImage($request);
});

$router('POST', API_VERSION . '/organizer/vote', function($request, $params) {
    $controller = new OrganizerController();
    $controller->recordVote($request);
});

$router('GET', API_VERSION . '/organizer/vote-records', function($request, $params) {
    $controller = new OrganizerController();
    $controller->getVoteRecords($request);
});

// USSD routes
$router('POST', API_VERSION . '/ussd', function($request, $params) {
    $controller = new UssdController();
    $controller->handleUssdRequest($request);
});

// Payment routes
$router('POST', API_VERSION . '/payment/verify', function($request, $params) {
    $controller = new PaymentController();
    $controller->verifyTransaction($request);
});

$router('POST', API_VERSION . '/payment/process', function($request, $params) {
    $controller = new PaymentController();
    $controller->processPayment($request);
});