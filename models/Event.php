<?php
/**
 * Event.php
 * Event model for database operations
 */
namespace Models;

use Config\Database;

class Event {
    private $db;
    private $table = 'events';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all events
     * 
     * @return array
     */
    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get event by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->db->fetch($query, [$id]);
    }

    /**
     * Create a new event
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
     * Update an event
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
     * Delete an event
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
     * Update event image
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
     * Verify admin credentials
     * 
     * @param string $email
     * @param string $password
     * @return array|null
     */
    public function verifyOrganizer($email, $password) {
        $query = "SELECT id, name, owner FROM {$this->table} WHERE owner = ? AND owner_password = ?";
        return $this->db->fetch($query, [$email, $password]);
    }

    /**
     * Check if event exists
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
     * Mark event as expired
     * 
     * @param int $id
     * @return bool
     */
    public function markAsExpired($id) {
        $query = "UPDATE {$this->table} SET expired = 1 WHERE id = ?";
        $this->db->query($query, [$id]);
        
        return true;
    }

    /**
     * Get event with categories and nominees
     * 
     * @param int $id
     * @return array
     */
    public function getEventWithDetails($id) {
        // Get event
        $event = $this->getById($id);
        
        if (!$event) {
            return null;
        }
        
        // Get categories
        $categoryModel = new Category();
        $categories = $categoryModel->getByOrganizerId($id);
        
        // Get nominees for each category
        $nomineeModel = new Nominee();
        foreach ($categories as &$category) {
            $category['nominees'] = $nomineeModel->getByCategoryId($category['id']);
        }
        
        $event['categories'] = $categories;
        
        return $event;
    }

    /**
     * Search events by name
     * 
     * @param string $search
     * @return array
     */
    public function search($search) {
        $query = "SELECT * FROM {$this->table} WHERE name LIKE ? ORDER BY created_at DESC";
        return $this->db->fetchAll($query, ["%{$search}%"]);
    }
}