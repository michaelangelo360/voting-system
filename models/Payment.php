<?php
/**
 * Payment.php
 * Model for payment references
 */
namespace Models;

use Config\Database;

class Payment {
    private $db;
    private $table = 'payment_references';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all payment references
     * 
     * @return array
     */
    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get payment reference by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->fetch($query, [$id]);
    }

    /**
     * Get payment reference by reference
     * 
     * @param string $reference
     * @return array|null
     */
    public function getByReference($reference) {
        $query = "SELECT * FROM {$this->table} WHERE reference = ?";
        return $this->db->fetch($query, [$reference]);
    }

    /**
     * Create a new payment reference
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
     * Update payment reference status
     * 
     * @param int $id
     * @param int $status
     * @return bool
     */
    public function updateStatus($id, $status) {
        $query = "UPDATE {$this->table} SET status = ? WHERE id = ?";
        $this->db->query($query, [$status, $id]);
        
        return true;
    }

    /**
     * Get payment references by nominee ID
     * 
     * @param int $nomineeId
     * @return array
     */
    public function getByNomineeId($nomineeId) {
        $query = "SELECT * FROM {$this->table} WHERE nominee_id = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($query, [$nomineeId]);
    }

    /**
     * Get successful payment references
     * 
     * @return array
     */
    public function getSuccessful() {
        $query = "SELECT * FROM {$this->table} WHERE status = 1 ORDER BY created_at DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get pending payment references
     * 
     * @return array
     */
    public function getPending() {
        $query = "SELECT * FROM {$this->table} WHERE status = 0 ORDER BY created_at DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Delete a payment reference
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
     * Get payment statistics
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getStatistics($startDate = null, $endDate = null) {
        $params = [];
        $dateFilter = '';
        
        if ($startDate && $endDate) {
            $dateFilter = "WHERE created_at BETWEEN ? AND ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        } else if ($startDate) {
            $dateFilter = "WHERE created_at >= ?";
            $params[] = $startDate . ' 00:00:00';
        } else if ($endDate) {
            $dateFilter = "WHERE created_at <= ?";
            $params[] = $endDate . ' 23:59:59';
        }
        
        $query = "SELECT 
                    COUNT(*) AS total_transactions,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS successful_transactions,
                    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS pending_transactions,
                    SUM(votes) AS total_votes,
                    SUM(CASE WHEN status = 1 THEN votes ELSE 0 END) AS successful_votes
                FROM {$this->table} 
                {$dateFilter}";
        
        return $this->db->fetch($query, $params);
    }
}