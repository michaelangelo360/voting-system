<?php
/**
 * CacheService.php
 * Simple cache service implementation
 */
namespace Services;

class CacheService {
    private $memoryCache = [];
    private $expiration = [];
    
    /**
     * Get a value from cache
     * 
     * @param string $key
     * @return mixed|null
     */
    public function get($key) {
        // Check if key exists and not expired
        if (isset($this->memoryCache[$key]) && 
            (!isset($this->expiration[$key]) || $this->expiration[$key] > time())) {
            return $this->memoryCache[$key];
        }
        
        // Key doesn't exist or expired
        return null;
    }
    
    /**
     * Set a value in cache
     * 
     * @param string $key
     * @param mixed $value
     * @param int $ttl Time to live in seconds
     * @return bool
     */
    public function set($key, $value, $ttl = 0) {
        $this->memoryCache[$key] = $value;
        
        // Set expiration if TTL is provided
        if ($ttl > 0) {
            $this->expiration[$key] = time() + $ttl;
        } else {
            // No expiration
            $this->expiration[$key] = 0;
        }
        
        return true;
    }
    
    /**
     * Delete a value from cache
     * 
     * @param string $key
     * @return bool
     */
    public function delete($key) {
        if (isset($this->memoryCache[$key])) {
            unset($this->memoryCache[$key]);
            
            if (isset($this->expiration[$key])) {
                unset($this->expiration[$key]);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Clear the entire cache
     * 
     * @return bool
     */
    public function clear() {
        $this->memoryCache = [];
        $this->expiration = [];
        
        return true;
    }
    
    /**
     * Check if a key exists in cache
     * 
     * @param string $key
     * @return bool
     */
    public function has($key) {
        return isset($this->memoryCache[$key]) && 
            (!isset($this->expiration[$key]) || $this->expiration[$key] > time());
    }
    
    /**
     * Increment a numeric value in cache
     * 
     * @param string $key
     * @param int $increment
     * @return int|false
     */
    public function increment($key, $increment = 1) {
        if (!$this->has($key)) {
            // Key doesn't exist, create it
            $this->set($key, $increment);
            return $increment;
        }
        
        $value = $this->get($key);
        
        // Check if value is numeric
        if (!is_numeric($value)) {
            return false;
        }
        
        $value += $increment;
        $this->set($key, $value);
        
        return $value;
    }
    
    /**
     * Decrement a numeric value in cache
     * 
     * @param string $key
     * @param int $decrement
     * @return int|false
     */
    public function decrement($key, $decrement = 1) {
        return $this->increment($key, -$decrement);
    }
    
    /**
     * Clean expired items from cache
     * 
     * @return int Number of items removed
     */
    public function cleanup() {
        $count = 0;
        $now = time();
        
        foreach ($this->expiration as $key => $expiry) {
            if ($expiry > 0 && $expiry <= $now) {
                $this->delete($key);
                $count++;
            }
        }
        
        return $count;
    }
}