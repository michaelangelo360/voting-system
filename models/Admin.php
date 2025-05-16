<?php
/**
 * Admin.php
 * Model for admin users
 */
namespace Models;

use Config\Database;
use Services\AuthService;

class Admin {
    private $db;
    private $table = 'admins';
    private $authService;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->authService = new AuthService();
    }

    /**
     * Get all admins
     * 
     * @return array
     */
    public function getAll() {
        $query = "SELECT id, email, is_super_admin, created_at FROM {$this->table} ORDER BY created_at DESC";
        return $this->db->fetchAll($query);
    }

    /**
     * Get admin by ID
     * 
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        $query = "SELECT id, email, is_super_admin, created_at FROM {$this->table} WHERE id = ?";
        return $this->db->fetch($query, [$id]);
    }

    /**
     * Get admin by email
     * 
     * @param string $email
     * @return array|null
     */
    public function getByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->db->fetch($query, [$email]);
    }

    /**
     * Create a new admin
     * 
     * @param array $data
     * @return int Last insert ID
     */
    public function create($data) {
        // Hash password
        if (isset($data['password'])) {
            $data['password'] = $this->authService->hashPassword($data['password']);
        }
        
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
     * Update an admin
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = $this->authService->hashPassword($data['password']);
        }
        
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
     * Delete an admin
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
     * Verify admin credentials
     * 
     * @param string $email
     * @param string $password
     * @return array|null
     */
    public function verifyCredentials($email, $password) {
        $admin = $this->getByEmail($email);
        
        if (!$admin) {
            return null;
        }
        
        if (!$this->authService->verifyPassword($password, $admin['password'])) {
            return null;
        }
        
        // Remove password from result
        unset($admin['password']);
        
        return $admin;
    }

    /**
     * Check if admin exists
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
     * Check if email exists
     * 
     * @param string $email
     * @param int $excludeId
     * @return bool
     */
    public function emailExists($email, $excludeId = null) {
        $query = "SELECT id FROM {$this->table} WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $query .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($query, $params);
        
        return !empty($result);
    }
}