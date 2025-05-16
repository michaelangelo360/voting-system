<?php
/**
 * Logger.php
 * Utility for application logging
 */
namespace Utils;

class Logger {
    private $logDir;
    private $logFile;
    private $logLevel;
    
    // Log levels (PSR-3 compatible)
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    
    // Map log levels to numeric values for comparison
    private $logLevelMap = [
        self::EMERGENCY => 0,
        self::ALERT     => 1,
        self::CRITICAL  => 2,
        self::ERROR     => 3,
        self::WARNING   => 4,
        self::NOTICE    => 5,
        self::INFO      => 6,
        self::DEBUG     => 7
    ];
    
    /**
     * Constructor
     * 
     * @param string $logDir Log directory
     * @param string $logLevel Minimum log level
     */
    public function __construct($logDir = null, $logLevel = null) {
        $this->logDir = $logDir ?? (defined('LOG_DIR') ? LOG_DIR : __DIR__ . '/../logs/');
        $this->logLevel = $logLevel ?? (defined('DEBUG_MODE') && DEBUG_MODE ? self::DEBUG : self::INFO);
        
        // Create log directory if it doesn't exist
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
        
        // Set default log file
        $this->setLogFile('app_' . date('Y-m-d') . '.log');
    }
    
    /**
     * Set the active log file
     * 
     * @param string $filename
     * @return void
     */
    public function setLogFile($filename) {
        $this->logFile = $this->logDir . $filename;
    }
    
    /**
     * Set the log level
     * 
     * @param string $level
     * @return void
     */
    public function setLogLevel($level) {
        if (isset($this->logLevelMap[$level])) {
            $this->logLevel = $level;
        }
    }
    
    /**
     * Log a message
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function log($level, $message, array $context = []) {
        // Check if level is valid
        if (!isset($this->logLevelMap[$level])) {
            return false;
        }
        
        // Check if we should log this level
        if ($this->logLevelMap[$level] > $this->logLevelMap[$this->logLevel]) {
            return false;
        }
        
        // Replace placeholders in message
        $message = $this->interpolate($message, $context);
        
        // Format log entry
        $entry = $this->formatLogEntry($level, $message, $context);
        
        // Write to file
        return $this->writeToFile($entry);
    }
    
    /**
     * Format a log entry
     * 
     * @param string $level
     * @param string $message
     * @param array $context
     * @return string
     */
    private function formatLogEntry($level, $message, array $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        
        // Format context as JSON (if not empty)
        $contextJson = !empty($context) ? ' ' . json_encode($context) : '';
        
        // Build log entry
        return "[{$timestamp}] [{$levelUpper}] {$message}{$contextJson}" . PHP_EOL;
    }
    
    /**
     * Replace placeholders in message with context values
     * 
     * @param string $message
     * @param array $context
     * @return string
     */
    private function interpolate($message, array $context = []) {
        // Build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            // Skip if value is not a string or object with __toString
            if (!is_string($val) && !method_exists($val, '__toString')) {
                continue;
            }
            
            $replace['{' . $key . '}'] = $val;
        }
        
        // Interpolate replacement values into the message
        return strtr($message, $replace);
    }
    
    /**
     * Write a log entry to file
     * 
     * @param string $entry
     * @return bool
     */
    private function writeToFile($entry) {
        return file_put_contents($this->logFile, $entry, FILE_APPEND | LOCK_EX) !== false;
    }
    
    /**
     * System is unusable
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function emergency($message, array $context = []) {
        return $this->log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Action must be taken immediately
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function alert($message, array $context = []) {
        return $this->log(self::ALERT, $message, $context);
    }
    
    /**
     * Critical conditions
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function critical($message, array $context = []) {
        return $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Runtime errors
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function error($message, array $context = []) {
        return $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Exceptional occurrences that are not errors
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function warning($message, array $context = []) {
        return $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Normal but significant events
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function notice($message, array $context = []) {
        return $this->log(self::NOTICE, $message, $context);
    }
    
    /**
     * Interesting events
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function info($message, array $context = []) {
        return $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Detailed debug information
     * 
     * @param string $message
     * @param array $context
     * @return bool
     */
    public function debug($message, array $context = []) {
        return $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log API request
     * 
     * @param array $request
     * @return bool
     */
    public function logApiRequest($request) {
        // Don't log sensitive data
        $safeRequest = $request;
        
        // Remove sensitive data from the request
        if (isset($safeRequest['body']['password'])) {
            $safeRequest['body']['password'] = '******';
        }
        
        if (isset($safeRequest['body']['owner_password'])) {
            $safeRequest['body']['owner_password'] = '******';
        }
        
        // Create context for log entry
        $context = [
            'path' => $safeRequest['path'],
            'method' => $safeRequest['method'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'query' => $safeRequest['query'] ?? [],
            'body' => $safeRequest['body'] ?? []
        ];
        
        // Set log file for API requests
        $this->setLogFile('api_' . date('Y-m-d') . '.log');
        
        // Log the request
        return $this->info('API Request', $context);
    }
    
    /**
     * Log API response
     * 
     * @param array $response
     * @param int $statusCode
     * @param string $path
     * @return bool
     */
    public function logApiResponse($response, $statusCode, $path) {
        // Create context for log entry
        $context = [
            'path' => $path,
            'status_code' => $statusCode,
            'response' => $response
        ];
        
        // Set log file for API requests
        $this->setLogFile('api_' . date('Y-m-d') . '.log');
        
        // Log the response
        return $this->info('API Response', $context);
    }
    
    /**
     * Log error with exception details
     * 
     * @param \Exception $exception
     * @param string $prefix
     * @return bool
     */
    public function logException(\Exception $exception, $prefix = 'Exception') {
        // Create context for log entry
        $context = [
            'class' => get_class($exception),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        // Set log file for errors
        $this->setLogFile('error_' . date('Y-m-d') . '.log');
        
        // Log the exception
        return $this->error($prefix . ': ' . $exception->getMessage(), $context);
    }
    
    /**
     * Log USSD request
     * 
     * @param array $request
     * @return bool
     */
    public function logUssdRequest($request) {
        // Create context for log entry
        $context = [
            'session_id' => $request['body']['sessionID'] ?? 'Unknown',
            'msisdn' => $request['body']['msisdn'] ?? 'Unknown',
            'network' => $request['body']['network'] ?? 'Unknown',
            'new_session' => $request['body']['newSession'] ?? false,
            'user_data' => $request['body']['userData'] ?? ''
        ];
        
        // Set log file for USSD requests
        $this->setLogFile('ussd_' . date('Y-m-d') . '.log');
        
        // Log the request
        return $this->info('USSD Request', $context);
    }
    
    /**
     * Log USSD response
     * 
     * @param array $response
     * @param string $sessionId
     * @return bool
     */
    public function logUssdResponse($response, $sessionId) {
        // Create context for log entry
        $context = [
            'session_id' => $sessionId,
            'message' => $response['message'] ?? '',
            'continue_session' => $response['continueSession'] ?? false
        ];
        
        // Set log file for USSD requests
        $this->setLogFile('ussd_' . date('Y-m-d') . '.log');
        
        // Log the response
        return $this->info('USSD Response', $context);
    }
    
    /**
     * Log payment transaction
     * 
     * @param array $transaction
     * @param string $status
     * @return bool
     */
    public function logPaymentTransaction($transaction, $status) {
        // Create context for log entry
        $context = [
            'reference' => $transaction['reference'] ?? 'Unknown',
            'amount' => $transaction['amount'] ?? 0,
            'status' => $status,
            'nominee_id' => $transaction['nominee_id'] ?? null,
            'votes' => $transaction['votes'] ?? 0,
            'msisdn' => $transaction['msisdn'] ?? ''
        ];
        
        // Set log file for payment transactions
        $this->setLogFile('payment_' . date('Y-m-d') . '.log');
        
        // Log the transaction
        return $this->info('Payment Transaction', $context);
    }
}