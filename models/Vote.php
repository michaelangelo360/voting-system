<?php
/**
 * Vote.php
 * Model for vote records
 */
namespace Models;

use Config\Database;

class Vote {
    private $db;
    private $table = 'vote_records';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all vote records
     * 
     * @return array
     */
    public function getAll() {
        $query = "SELECT v.*, n.name as nominee_name, n.code as nominee_code
                 FROM {$this->table} v
                 JOIN nominees n ON v.nominee_id = n.id
                 ORDER BY v.created_at DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get vote record by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        $query = "SELECT v.*, n.name as nominee_name, n.code as nominee_code
                 FROM {$this->table} v
                 JOIN nominees n ON v.nominee_id = n.id
                 WHERE v.id = ?";
        return $this->db->fetch($query, [$id]);
    }

    /**
     * Get vote records by nominee ID
     * 
     * @param int $nomineeId
     * @return array
     */
    public function getByNomineeId($nomineeId) {
        $query = "SELECT * FROM {$this->table} WHERE nominee_id = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($query, [$nomineeId]);
    }

    /**
     * Get vote records by organizer ID
     * 
     * @param int $organizerId
     * @return array
     */
    public function getByOrganizerId($organizerId) {
        $query = "SELECT v.*, n.name as nominee_name, n.code as nominee_code
                 FROM {$this->table} v
                 JOIN nominees n ON v.nominee_id = n.id
                 JOIN categories c ON n.category_id = c.id
                 WHERE c.organizer_id = ?
                 ORDER BY v.created_at DESC";
        return $this->db->fetchAll($query, [$organizerId]);
    }

    /**
     * Create a new vote record
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
     * Delete a vote record
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
     * Get votes by transaction reference
     * 
     * @param string $reference
     * @return array
     */
    public function getByTransactionReference($reference) {
        $query = "SELECT * FROM {$this->table} WHERE transaction_reference = ?";
        return $this->db->fetch($query, [$reference]);
    }

    /**
     * Get votes statistics by date range
     * 
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getStatisticsByDateRange($startDate = null, $endDate = null) {
        $params = [];
        $dateFilter = '';
        
        if ($startDate && $endDate) {
            $dateFilter = "WHERE v.created_at BETWEEN ? AND ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        } else if ($startDate) {
            $dateFilter = "WHERE v.created_at >= ?";
            $params[] = $startDate . ' 00:00:00';
        } else if ($endDate) {
            $dateFilter = "WHERE v.created_at <= ?";
            $params[] = $endDate . ' 23:59:59';
        }
        
        $query = "SELECT 
                    DATE(v.created_at) as date,
                    COUNT(*) as vote_count,
                    SUM(v.vote_count) as total_votes
                FROM {$this->table} v
                {$dateFilter}
                GROUP BY DATE(v.created_at)
                ORDER BY date DESC";
        
        return $this->db->fetchAll($query, $params);
    }

    /**
     * Get votes statistics by nominee
     * 
     * @param int $organizerId
     * @return array
     */
    public function getStatisticsByNominee($organizerId) {
        $query = "SELECT 
                    n.id,
                    n.name,
                    n.code,
                    SUM(v.vote_count) as total_votes,
                    COUNT(*) as vote_records
                FROM {$this->table} v
                JOIN nominees n ON v.nominee_id = n.id
                JOIN categories c ON n.category_id = c.id
                WHERE c.organizer_id = ?
                GROUP BY n.id, n.name, n.code
                ORDER BY total_votes DESC";
        
        return $this->db->fetchAll($query, [$organizerId]);
    }

    /**
     * Get votes statistics by category
     * 
     * @param int $organizerId
     * @return array
     */
    public function getStatisticsByCategory($organizerId) {
        $query = "SELECT 
                    c.id,
                    c.name,
                    SUM(v.vote_count) as total_votes,
                    COUNT(DISTINCT n.id) as nominees_count
                FROM {$this->table} v
                JOIN nominees n ON v.nominee_id = n.id
                JOIN categories c ON n.category_id = c.id
                WHERE c.organizer_id = ?
                GROUP BY c.id, c.name
                ORDER BY total_votes DESC";
        
        return $this->db->fetchAll($query, [$organizerId]);
    }
}