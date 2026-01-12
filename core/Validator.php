<?php
/**
 * Validator Class
 * CampusX - College Management System
 * Handles input validation
 */

class Validator {
    private $errors = [];
    private $data = [];
    
    /**
     * Constructor
     */
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    /**
     * Validate field
     */
    public function validate($field, $rules) {
        $value = $this->data[$field] ?? null;
        $rulesArray = explode('|', $rules);
        
        foreach ($rulesArray as $rule) {
            $this->applyRule($field, $value, $rule);
        }
        
        return $this;
    }
    
    /**
     * Apply validation rule
     */
    private function applyRule($field, $value, $rule) {
        // Parse rule and parameter
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $parameter = $parts[1] ?? null;
        
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, ucfirst($field) . ' is required');
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'Invalid email format');
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < $parameter) {
                    $this->addError($field, ucfirst($field) . " must be at least $parameter characters");
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > $parameter) {
                    $this->addError($field, ucfirst($field) . " must not exceed $parameter characters");
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, ucfirst($field) . ' must be numeric');
                }
                break;
                
            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, ucfirst($field) . ' must be an integer');
                }
                break;
                
            case 'alpha':
                if (!empty($value) && !ctype_alpha(str_replace(' ', '', $value))) {
                    $this->addError($field, ucfirst($field) . ' must contain only letters');
                }
                break;
                
            case 'alphanumeric':
                if (!empty($value) && !ctype_alnum(str_replace(' ', '', $value))) {
                    $this->addError($field, ucfirst($field) . ' must contain only letters and numbers');
                }
                break;
                
            case 'phone':
                if (!empty($value) && !preg_match('/^(98|97)\d{8}$/', $value)) {
                    $this->addError($field, 'Invalid phone number format');
                }
                break;
                
            case 'date':
                if (!empty($value) && !strtotime($value)) {
                    $this->addError($field, 'Invalid date format');
                }
                break;
                
            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'Invalid URL format');
                }
                break;
                
            case 'same':
                if (!empty($value) && $value !== ($this->data[$parameter] ?? null)) {
                    $this->addError($field, ucfirst($field) . " must match $parameter");
                }
                break;
                
            case 'different':
                if (!empty($value) && $value === ($this->data[$parameter] ?? null)) {
                    $this->addError($field, ucfirst($field) . " must be different from $parameter");
                }
                break;
                
            case 'in':
                $values = explode(',', $parameter);
                if (!empty($value) && !in_array($value, $values)) {
                    $this->addError($field, ucfirst($field) . ' must be one of: ' . implode(', ', $values));
                }
                break;
                
            case 'min_value':
                if (!empty($value) && is_numeric($value) && $value < $parameter) {
                    $this->addError($field, ucfirst($field) . " must be at least $parameter");
                }
                break;
                
            case 'max_value':
                if (!empty($value) && is_numeric($value) && $value > $parameter) {
                    $this->addError($field, ucfirst($field) . " must not exceed $parameter");
                }
                break;
        }
    }
    
    /**
     * Add error message
     */
    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !empty($this->errors);
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Get all errors
     */
    public function errors() {
        return $this->errors;
    }
    
    /**
     * Get first error for a field
     */
    public function getError($field) {
        return isset($this->errors[$field]) ? $this->errors[$field][0] : null;
    }
    
    /**
     * Get all error messages as flat array
     */
    public function getMessages() {
        $messages = [];
        foreach ($this->errors as $fieldErrors) {
            $messages = array_merge($messages, $fieldErrors);
        }
        return $messages;
    }
    
    /**
     * Static validation helper
     */
    public static function make($data, $rules) {
        $validator = new self($data);
        
        foreach ($rules as $field => $rule) {
            $validator->validate($field, $rule);
        }
        
        return $validator;
    }
    
    /**
     * Validate file upload
     */
    public function validateFile($field, $allowedTypes = [], $maxSize = null) {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return $this;
        }
        
        $file = $_FILES[$field];
        
        // Check upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->addError($field, 'File upload error');
            return $this;
        }
        
        // Check file size
        $maxSize = $maxSize ?? MAX_FILE_SIZE;
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1048576, 2);
            $this->addError($field, "File size must not exceed {$maxSizeMB}MB");
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedTypes)) {
                $this->addError($field, 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes));
            }
        }
        
        return $this;
    }
    
    /**
     * Validate image file
     */
    public function validateImage($field, $maxSize = null) {
        return $this->validateFile($field, ALLOWED_IMAGE_TYPES, $maxSize);
    }
    
    /**
     * Validate document file
     */
    public function validateDocument($field, $maxSize = null) {
        return $this->validateFile($field, ALLOWED_DOCUMENT_TYPES, $maxSize);
    }
    
    /**
     * Custom validation
     */
    public function custom($field, $callback, $message) {
        $value = $this->data[$field] ?? null;
        
        if (!$callback($value)) {
            $this->addError($field, $message);
        }
        
        return $this;
    }
}
?>