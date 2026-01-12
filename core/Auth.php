<?php
/**
 * Authentication Class
 * CampusX - College Management System
 * Handles user authentication and authorization
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Session.php';

class Auth {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Login user
     */
    public function login($email, $password, $role) {
        // Validate input
        if (empty($email) || empty($password) || empty($role)) {
            return [
                'success' => false,
                'message' => 'All fields are required'
            ];
        }
        
        // Determine table based on role
        $table = $this->getRoleTable($role);
        
        if (!$table) {
            return [
                'success' => false,
                'message' => 'Invalid role'
            ];
        }
        
        // Get user from database
        $query = "SELECT * FROM $table WHERE email = ? AND status = ? LIMIT 1";
        $user = $this->db->selectOne($query, [$email, STATUS_ACTIVE], 'si');
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid email or password'
            ];
        }
        
        // Set session data
        $userName = isset($user['first_name']) 
            ? $user['first_name'] . ' ' . $user['last_name'] 
            : $user['name'];
            
        Session::setUserData(
            $user['id'],
            $role,
            $userName,
            $user['email']
        );
        
        // Regenerate session ID for security
        Session::regenerate();
        
        // Log activity
        $this->logActivity($user['id'], 'login', 'User logged in');
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'role' => $role,
            'user_id' => $user['id']
        ];
    }
    
    /**
     * Logout user
     */
    public function logout() {
        $userId = Session::getUserId();
        
        if ($userId) {
            $this->logActivity($userId, 'logout', 'User logged out');
        }
        
        Session::clearUserData();
        Session::destroy();
        
        return [
            'success' => true,
            'message' => 'Logged out successfully'
        ];
    }
    
    /**
     * Register new user
     */
    public function register($data, $role) {
        // Validate required fields
        $requiredFields = ['email', 'password', 'first_name', 'last_name'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'message' => ucfirst($field) . ' is required'
                ];
            }
        }
        
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Invalid email format'
            ];
        }
        
        // Check if email already exists
        $table = $this->getRoleTable($role);
        
        if ($this->db->exists($table, "email = ?", [$data['email']], 's')) {
            return [
                'success' => false,
                'message' => 'Email already exists'
            ];
        }
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_ALGO, ['cost' => PASSWORD_COST]);
        
        // Set default status
        $data['status'] = STATUS_ACTIVE;
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // Insert user
        $userId = $this->db->insertRecord($table, $data);
        
        if ($userId) {
            $this->logActivity($userId, 'register', 'User registered');
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user_id' => $userId
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Registration failed'
        ];
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Validate input
        if (empty($currentPassword) || empty($newPassword)) {
            return [
                'success' => false,
                'message' => 'All fields are required'
            ];
        }
        
        if (strlen($newPassword) < 6) {
            return [
                'success' => false,
                'message' => 'Password must be at least 6 characters'
            ];
        }
        
        // Get user role and table
        $role = Session::getUserRole();
        $table = $this->getRoleTable($role);
        
        // Get current password hash
        $user = $this->db->getById($table, $userId);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found'
            ];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }
        
        // Hash new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);
        
        // Update password
        $updated = $this->db->updateRecord(
            $table,
            ['password' => $newPasswordHash],
            'id = ?',
            [$userId]
        );
        
        if ($updated) {
            $this->logActivity($userId, 'password_change', 'Password changed');
            
            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to change password'
        ];
    }
    
    /**
     * Reset password (forgot password)
     */
    public function resetPassword($email, $role) {
        $table = $this->getRoleTable($role);
        
        // Check if user exists
        $user = $this->db->selectOne("SELECT * FROM $table WHERE email = ?", [$email], 's');
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Email not found'
            ];
        }
        
        // Generate random password
        $newPassword = substr(md5(time()), 0, 8);
        $newPasswordHash = password_hash($newPassword, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);
        
        // Update password
        $updated = $this->db->updateRecord(
            $table,
            ['password' => $newPasswordHash],
            'id = ?',
            [$user['id']]
        );
        
        if ($updated) {
            // Send email with new password
            $subject = 'Password Reset - ' . APP_NAME;
            $message = "Your new password is: $newPassword\n\nPlease change it after login.";
            
            // In production, use proper email service
            // mail($email, $subject, $message);
            
            $this->logActivity($user['id'], 'password_reset', 'Password reset requested');
            
            return [
                'success' => true,
                'message' => 'New password sent to your email',
                'temp_password' => $newPassword // Remove this in production
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to reset password'
        ];
    }
    
    /**
     * Check if user is authenticated
     */
    public function check() {
        return Session::isLoggedIn();
    }
    
    /**
     * Get current user data
     */
    public function user() {
        if (!$this->check()) {
            return null;
        }
        
        $userId = Session::getUserId();
        $role = Session::getUserRole();
        $table = $this->getRoleTable($role);
        
        return $this->db->getById($table, $userId);
    }
    
    /**
     * Get role table name
     */
    private function getRoleTable($role) {
        switch ($role) {
            case ROLE_ADMIN:
                return 'admins';
            case ROLE_TEACHER:
                return 'teachers';
            case ROLE_STUDENT:
                return 'students';
            default:
                return null;
        }
    }
    
    /**
     * Log user activity
     */
    private function logActivity($userId, $action, $details) {
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insertRecord('activity_logs', $data);
    }
}
?>