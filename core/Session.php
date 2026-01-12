<?php
/**
 * Session Management Class
 * CampusX - College Management System
 * Handles all session operations
 */

class Session {
    
    /**
     * Start session if not already started
     */
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Set session variable
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session variable
     */
    public static function get($key, $default = null) {
        self::start();
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }
    
    /**
     * Check if session variable exists
     */
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session variable
     */
    public static function remove($key) {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    /**
     * Clear all session variables
     */
    public static function clear() {
        self::start();
        session_unset();
    }
    
    /**
     * Destroy session
     */
    public static function destroy() {
        self::start();
        session_unset();
        session_destroy();
    }
    
    /**
     * Regenerate session ID
     */
    public static function regenerate() {
        self::start();
        session_regenerate_id(true);
    }
    
    /**
     * Set flash message
     */
    public static function setFlash($type, $message) {
        self::start();
        $_SESSION['flash_' . $type] = $message;
    }
    
    /**
     * Get flash message and remove it
     */
    public static function getFlash($type) {
        self::start();
        $key = 'flash_' . $type;
        
        if (isset($_SESSION[$key])) {
            $message = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $message;
        }
        
        return null;
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return self::has('user_id') && !empty(self::get('user_id'));
    }
    
    /**
     * Get logged in user ID
     */
    public static function getUserId() {
        return self::get('user_id');
    }
    
    /**
     * Get logged in user role
     */
    public static function getUserRole() {
        return self::get('user_role');
    }
    
    /**
     * Get logged in user name
     */
    public static function getUserName() {
        return self::get('user_name');
    }
    
    /**
     * Set user session data
     */
    public static function setUserData($userId, $userRole, $userName, $userEmail) {
        self::start();
        self::set('user_id', $userId);
        self::set('user_role', $userRole);
        self::set('user_name', $userName);
        self::set('user_email', $userEmail);
        self::set('last_activity', time());
    }
    
    /**
     * Clear user session data
     */
    public static function clearUserData() {
        self::remove('user_id');
        self::remove('user_role');
        self::remove('user_name');
        self::remove('user_email');
        self::remove('last_activity');
    }
    
    /**
     * Check session timeout
     */
    public static function checkTimeout() {
        self::start();
        
        if (self::has('last_activity')) {
            $elapsed = time() - self::get('last_activity');
            
            if ($elapsed > SESSION_TIMEOUT) {
                self::destroy();
                return false;
            }
        }
        
        self::set('last_activity', time());
        return true;
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        return self::getUserRole() === $role;
    }
    
    /**
     * Require login
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . 'modules/authentication/login.php');
            exit();
        }
        
        // Check session timeout
        if (!self::checkTimeout()) {
            header('Location: ' . BASE_URL . 'modules/authentication/login.php?timeout=1');
            exit();
        }
    }
    
    /**
     * Require specific role
     */
    public static function requireRole($role) {
        self::requireLogin();
        
        if (!self::hasRole($role)) {
            header('Location: ' . BASE_URL . 'modules/authentication/unauthorized.php');
            exit();
        }
    }
    
    /**
     * Get all session data
     */
    public static function all() {
        self::start();
        return $_SESSION;
    }
}
?>