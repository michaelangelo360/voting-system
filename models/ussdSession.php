<?php
/**
 * UssdSession.php
 * Model for USSD sessions
 */
namespace Models;

use Config\Database;

class UssdSession {
    private $db;
    private $table = 'ussd_sessions';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all USSD sessions
     * 
     * @return array
     */
    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get USSD session by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->fetch($query, [$id]);
    }

    /**
     * Get USSD session by session ID
     * 
     * @param string $sessionId
     * @return array|null
     */
    public function getBySessionId($sessionId) {
        $query = "SELECT * FROM {$this->table} WHERE session_id = ?";
        return $this->db->fetch($query, [$sessionId]);
    }

    /**
     * Get USSD sessions by MSISDN
     * 
     * @param string $msisdn
     * @return array
     */
    public function getByMsisdn($msisdn) {
        $query = "SELECT * FROM {$this->table} WHERE msisdn = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($query, [$msisdn]);
    }

    /**
     * Create a new USSD session
     * 
     * @param array $data
     * @return int Last insert ID
     */
    public function create($data) {
        // Prepare fields and values
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $fieldsList = implode(', ', $fields);
        $placeholdersList = implode(', ', $placeholders);
        
        // Build and execute query
        $query = "INSERT INTO {$this->table} ({$fieldsList}) VALUES ({$placeholdersList})";
        $this->db->query($query, array_values($data));
        
        return $this->db->lastInsertId();
    }

    /**
     * Update a USSD session
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        // Prepare fields and values
        $setParts = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            $setParts[] = "{$field} = ?";
            $values[] = $value;
        }
        
        $setClause = implode(', ', $setParts);
        
        // Add ID to values array
        $values[] = $id;
        
        // Build and execute query
        $query = "UPDATE {$this->table} SET {$setClause} WHERE id = ?";
        $this->db->query($query, $values);
        
        return true;
    }

    /**
     * Delete a USSD session
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $this->db->query($query, [$id]);
        
        return true;
    }

    /**
     * Clean up old sessions
     * 
     * @param int $minutes
     * @return int Number of deleted sessions
     */
    public function cleanupOldSessions($minutes = 60) {
        $query = "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)";
        $stmt = $this->db->query($query, [$minutes]);
        
        return $stmt->rowCount();
    }

    /**
     * Get active sessions count
     * 
     * @param int $minutes Time window in minutes
     * @return int
     */
    public function getActiveSessionsCount($minutes = 60) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)";
        $result = $this->db->fetch($query, [$minutes]);
        
        return $result['count'];
    }

    /**
     * Get sessions by level
     * 
     * @param int $level
     * @return array
     */
    public function getByLevel($level) {
        $query = "SELECT * FROM {$this->table} WHERE level = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($query, [$level]);
    }

    /**
     * Get sessions by nominee
     * 
     * @param int $nomineeId
     * @return array
     */
    public function getByNominee($nomineeId) {
        $query = "SELECT * FROM {$this->table} WHERE nominee_id = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($query, [$nomineeId]);
    }

    /**
     * Get sessions by reference
     * 
     * @param string $reference
     * @return array
     */
    public function getByReference($reference) {
        $query = "SELECT * FROM {$this->table} WHERE reference = ?";
        return $this->db->fetch($query, [$reference]);
    }
}