<?php
/**
 * Database.php
 * Handles database connection and provides query methods
 */
namespace Config;

class Database {
    private $host;
    private $dbName;
    private $username;
    private $password;
    private $conn;
    private static $instance = null;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        // Load configuration from config.php
        require_once __DIR__ . '/config.php';
        $this->host = DB_HOST;
        $this->dbName = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->connect();
    }

    /**
     * Get Database instance (Singleton pattern)
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     * 
     * @return void
     */
    private function connect() {
        try {
            $this->conn = new \PDO(
                "mysql:host={$this->host};dbname={$this->dbName}",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        } catch (\PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get database connection
     * 
     * @return \PDO
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Execute a query with parameters
     * 
     * @param string $query
     * @param array $params
     * @return \PDOStatement
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " Query: " . $query);
            throw new \Exception("Database query error: " . $e->getMessage());
        }
    }

    /**
     * Get a single record
     * 
     * @param string $query
     * @param array $params
     * @return array|null
     */
    public function fetch($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetch();
    }

    /**
     * Get multiple records
     * 
     * @param string $query
     * @param array $params
     * @return array
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get the last inserted ID
     * 
     * @return string
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public function rollBack() {
        return $this->conn->rollBack();
    }
}