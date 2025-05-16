<?php
/**
 * Category.php
 * Model for categories
 */
namespace Models;

use Config\Database;

class Category {
    private $db;
    private $table = 'categories';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all categories
     * 
     * @return array
     */
    public function getAll() {
        $query = "SELECT c.*, e.name as event_name 
                 FROM {$this->table} c
                 JOIN events e ON c.organizer_id = e.id
                 ORDER BY c.created_at DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get categories by organizer ID
     * 
     * @param int $organizerId
     * @return array
     */
    public function getByOrganizerId($organizerId) {
        $query = "SELECT * FROM {$this->table} WHERE organizer_id = ? ORDER BY created_at DESC";
        return $this->db->fetchAll($query, [$organizerId]);
    }

    /**
     * Get category by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        $query = "SELECT c.*, e.name as event_name 
                 FROM {$this->table} c
                 JOIN events e ON c.organizer_id = e.id
                 WHERE c.id = ?";
        return $this->db->fetch($query, [$id]);
    }

    /**
     * Create a new category
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
     * Update a category
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
     * Delete a category
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
     * Update category image
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
     * Check if category exists
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
     * Get nominees count for category
     * 
     * @param int $id
     * @return int
     */
    public function getNomineesCount($id) {
        $query = "SELECT COUNT(*) as count FROM nominees WHERE category_id = ?";
        $result = $this->db->fetch($query, [$id]);
        
        return $result['count'];
    }

    /**
     * Get categories with nominees count
     * 
     * @param int $organizerId
     * @return array
     */
    public function getCategoriesWithNomineesCount($organizerId) {
        $query = "SELECT c.*, 
                     (SELECT COUNT(*) FROM nominees WHERE category_id = c.id) as nominees_count 
                 FROM {$this->table} c
                 WHERE c.organizer_id = ?
                 ORDER BY c.created_at DESC";
        return $this->db->fetchAll($query, [$organizerId]);
    }

    /**
     * Search categories by name
     * 
     * @param string $search
     * @param int $organizerId
     * @return array
     */
    public function search($search, $organizerId = null) {
        if ($organizerId) {
            $query = "SELECT * FROM {$this->table} WHERE name LIKE ? AND organizer_id = ? ORDER BY created_at DESC";
            return $this->db->fetchAll($query, ["%{$search}%", $organizerId]);
        } else {
            $query = "SELECT * FROM {$this->table} WHERE name LIKE ? ORDER BY created_at DESC";
            return $this->db->fetchAll($query, ["%{$search}%"]);
        }
    }
}