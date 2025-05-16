<?php
/**
 * Response.php
 * Handles API responses
 */
namespace Utils;

class Response {
    /**
     * Send a successful response
     * 
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return void
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        self::send([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Send an error response
     * 
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return void
     */
    public static function error($message = 'Error', $code = 400, $errors = null) {
        $response = [
            'status' => 'error',
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        self::send($response, $code);
    }

    /**
     * Send a not found response
     * 
     * @param string $message
     * @return void
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }

    /**
     * Send an unauthorized response
     * 
     * @param string $message
     * @return void
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::error($message, 401);
    }

    /**
     * Send a forbidden response
     * 
     * @param string $message
     * @return void
     */
    public static function forbidden($message = 'Forbidden access') {
        self::error($message, 403);
    }

    /**
     * Send validation error response
     * 
     * @param array $errors
     * @param string $message
     * @return void
     */
    public static function validationError($errors, $message = 'Validation error') {
        self::error($message, 422, $errors);
    }

    /**
     * Send server error response
     * 
     * @param string $message
     * @return void
     */
    public static function serverError($message = 'Internal server error') {
        self::error($message, 500);
    }

    /**
     * Send JSON response
     * 
     * @param mixed $data
     * @param int $code
     * @return void
     */
    private static function send($data, $code) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}