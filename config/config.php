<?php
/**
 * config.php
 * Configuration settings for the application
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'tickets0_allvotesgh');
define('DB_USER', 'tickets0_all_user');
define('DB_PASS', '4UPQ=dJ7cF6?');

// Application Settings
define('APP_NAME', 'AllVotesGH');
define('APP_URL', 'http://localhost/voting-system');
define('API_VERSION', 'v1');

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('EVENT_IMAGES_DIR', UPLOAD_DIR . 'events/');
define('CATEGORY_IMAGES_DIR', UPLOAD_DIR . 'categories/');
define('NOMINEE_IMAGES_DIR', UPLOAD_DIR . 'nominees/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// JWT Secret Key for Auth
define('JWT_SECRET', '5f2b5cdbe5194f10b3241568fe4e2b13'); // Change this to a secure random string

// Paystack Configuration
define('PAYSTACK_SECRET_KEY', 'sk_live_682733bdbcd986b24a49bca98b998267ae9d5ae5');
define('PAYSTACK_PUBLIC_KEY', 'pk_live_yourkeyhere');

// USSD Provider Configuration
define('USSD_PROVIDER_URL', 'https://ussd-provider-api-url.com');
define('USSD_PROVIDER_USERNAME', 'username');
define('USSD_PROVIDER_PASSWORD', 'password');

// Logging Configuration
define('LOG_DIR', __DIR__ . '/../logs/');
define('DEBUG_MODE', true);

// Create necessary directories if they don't exist
if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!file_exists(EVENT_IMAGES_DIR)) mkdir(EVENT_IMAGES_DIR, 0755, true);
if (!file_exists(CATEGORY_IMAGES_DIR)) mkdir(CATEGORY_IMAGES_DIR, 0755, true);
if (!file_exists(NOMINEE_IMAGES_DIR)) mkdir(NOMINEE_IMAGES_DIR, 0755, true);
if (!file_exists(LOG_DIR)) mkdir(LOG_DIR, 0755, true);

// Error handling
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set('Africa/Accra');