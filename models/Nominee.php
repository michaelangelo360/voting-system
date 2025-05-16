<?php
/**
 * Nominee.php
 * Nominee model for database operations
 */
namespace Models;

use Config\Database;

class Nominee {
    private $db;
    private $table = 'nominees';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all nominees
     * 
     * @return array
     */
    public function getAll() {
        $query = "SELECT n.*, c.name as category_name, e.name as event_name 
                 FROM {$this->table} n
                 JOIN categories c ON n.category_id = c.id
                 JOIN events e ON n.organizer_id = e.id
                 ORDER BY n.created_at DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get nominees by organizer ID
     * 
     * @param int $organizerId
     * @return array
     */
    public function getByOrganizerId($organizerId) {
        $query = "SELECT n.*, c.name as category_name 
                 FROM {$this->table} n
                 JOIN categories c ON n.category_id = c.id
                 WHERE n.organizer_id = ?
                 ORDER BY n.created_at DESC";
        return $this->db->fetchAll($query, [$organizerId]);
    }

    /**
     * Get nominees by category ID
     * 
     * @param int $categoryId
     * @return array
     */
    public function getByCategoryId($categoryId) {
        $query = "SELECT * FROM {$this->table} WHERE category_id = ? ORDER BY votes DESC, name ASC";
        return $this->db->fetchAll($query, [$categoryId]);
    }

    /**
     * Get nominee by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        $query = "SELECT n.*, c.name as category_name, e.name as event_name 
                 FROM {$this->table} n
                 JOIN categories c ON n.category_id = c.id
                 JOIN events e ON n.organizer_id = e.id
                 WHERE n.id = ?";
        return $this->db->fetch($query, [$id]);
    }

    /**
     * Get nominee by code
     * 
     * @param string $code
     * @return array|null
     */
    public function getByCode($code) {
        $query = "SELECT n.*, c.name as category_name, e.name as event_name, e.cost 
                 FROM {$this->table} n
                 JOIN categories c ON n.category_id = c.id
                 JOIN events e ON n.organizer_id = e.id
                 WHERE n.code = ?";
        return $this->db->fetch($query, [$code]);
    }

    /**
     * Create a new nominee
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
     * Update a nominee
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
     * Delete a nominee
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
     * Update nominee image
     * 
     * @param int $id
     * @param string $imageUrl
     * @return bool
     */
    public function updateImage($id, $imageUrl) {
        $query = "UPDATE {$this->table} SET image_url = ? WHERE id = ?";
        $this->db->query($query, [$imageUrl, $id]);
        
        return true;
    }

    /**
     * Update votes for a nominee
     * 
     * @param int $id
     * @param int $votes
     * @return bool
     */
    public function updateVotes($id, $votes) {
        $query = "UPDATE {$this->table} SET votes = votes + ? WHERE id = ?";
        $this->db->query($query, [$votes, $id]);
        
        return true;
    }

    /**
     * Check if nominee exists
     * 
     * @param int $id
     * @return bool
     */
    public function exists($id) {
        $query = "SELECT id FROM {$this->table} WHERE id = ?";
        $result = $this->db->fetch($query, [$id]);
        
        return !empty($result);
    }

    /**
     * Check if code exists
     * 
     * @param string $code
     * @param int $excludeId
     * @return bool
     */
    public function codeExists($code, $excludeId = null) {
        $query = "SELECT id FROM {$this->table} WHERE code = ?";
        $params = [$code];
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($query, $params);
        
        return !empty($result);
    }

    /**
     * Generate unique code
     * 
     * @param int $length
     * @return string
     */
    public function generateUniqueCode($length = 6) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[rand(0, $charactersLength - 1)];
            }
        } while ($this->codeExists($code));
        
        return $code;
    }
}