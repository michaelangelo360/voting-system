<?php
/**
 * Validator.php
 * Utility for input validation
 */
namespace Utils;

class Validator {
    private $errors = [];
    
    /**
     * Validate data against rules
     * 
     * @param array $data
     * @param array $rules
     * @return bool
     */
    public function validate($data, $rules) {
        $this->errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            
            foreach ($fieldRules as $rule) {
                // Check if rule has parameters
                if (strpos($rule, ':') !== false) {
                    list($ruleName, $ruleParams) = explode(':', $rule, 2);
                    $ruleParams = explode(',', $ruleParams);
                } else {
                    $ruleName = $rule;
                    $ruleParams = [];
                }
                
                // Apply rule
                $this->applyRule($data, $field, $ruleName, $ruleParams);
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Apply a validation rule
     * 
     * @param array $data
     * @param string $field
     * @param string $rule
     * @param array $params
     * @return void
     */
    private function applyRule($data, $field, $rule, $params = []) {
        // Skip validation if field doesn't exist and rule is not 'required'
        if (!isset($data[$field]) && $rule !== 'required') {
            return;
        }
        
        switch ($rule) {
            case 'required':
                if (!isset($data[$field]) || trim($data[$field]) === '') {
                    $this->addError($field, 'The ' . $field . ' field is required');
                }
                break;
                
            case 'email':
                if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'The ' . $field . ' must be a valid email address');
                }
                break;
                
            case 'numeric':
                if (isset($data[$field]) && !is_numeric($data[$field])) {
                    $this->addError($field, 'The ' . $field . ' must be a number');
                }
                break;
                
            case 'integer':
                if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_INT)) {
                    $this->addError($field, 'The ' . $field . ' must be an integer');
                }
                break;
                
            case 'min':
                if (isset($data[$field])) {
                    if (is_string($data[$field]) && strlen($data[$field]) < $params[0]) {
                        $this->addError($field, 'The ' . $field . ' must be at least ' . $params[0] . ' characters');
                    } else if (is_numeric($data[$field]) && $data[$field] < $params[0]) {
                        $this->addError($field, 'The ' . $field . ' must be at least ' . $params[0]);
                    }
                }
                break;
                
            case 'max':
                if (isset($data[$field])) {
                    if (is_string($data[$field]) && strlen($data[$field]) > $params[0]) {
                        $this->addError($field, 'The ' . $field . ' may not be greater than ' . $params[0] . ' characters');
                    } else if (is_numeric($data[$field]) && $data[$field] > $params[0]) {
                        $this->addError($field, 'The ' . $field . ' may not be greater than ' . $params[0]);
                    }
                }
                break;
                
            case 'string':
                if (isset($data[$field]) && !is_string($data[$field])) {
                    $this->addError($field, 'The ' . $field . ' must be a string');
                }
                break;
                
            case 'date':
                if (isset($data[$field])) {
                    $date = date_parse($data[$field]);
                    if ($date['error_count'] > 0 || !checkdate($date['month'], $date['day'], $date['year'])) {
                        $this->addError($field, 'The ' . $field . ' must be a valid date');
                    }
                }
                break;
                
            case 'in':
                if (isset($data[$field]) && !in_array($data[$field], $params)) {
                    $this->addError($field, 'The selected ' . $field . ' is invalid');
                }
                break;
                
            case 'boolean':
                if (isset($data[$field]) && !is_bool($data[$field]) && $data[$field] !== '0' && $data[$field] !== '1' && $data[$field] !== 0 && $data[$field] !== 1) {
                    $this->addError($field, 'The ' . $field . ' must be a boolean');
                }
                break;
        }
    }
    
    /**
     * Add an error message
     * 
     * @param string $field
     * @param string $message
     * @return void
     */
    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Check if validation has errors
     * 
     * @return bool
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }
}