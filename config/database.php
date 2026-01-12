<?php
/**
 * Database Configuration File
 * CampusX - College Management System
 * 
 * This file contains database connection settings
 * Modify these values according to your XAMPP setup
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// Database Configuration Constants
define('DB_HOST', 'localhost');        // Database host (usually localhost for XAMPP)
define('DB_USER', 'root');             // Database username (default for XAMPP)
define('DB_PASS', '');                 // Database password (empty by default in XAMPP)
define('DB_NAME', 'campusx_db');       // Database name

// Database Character Set
define('DB_CHARSET', 'utf8mb4');

/**
 * Create Database Connection
 * Returns mysqli connection object
 */
function getDatabaseConnection() {
    // Create connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        // Log error (in production, don't show actual error to user)
        error_log("Database Connection Failed: " . $conn->connect_error);
        die("Database connection failed. Please contact administrator.");
    }
    
    // Set character set
    $conn->set_charset(DB_CHARSET);
    
    return $conn;
}

/**
 * Close Database Connection
 */
function closeDatabaseConnection($conn) {
    if ($conn && $conn instanceof mysqli) {
        $conn->close();
    }
}

/**
 * Execute Query Safely
 * Prevents SQL injection
 */
function executeQuery($conn, $query, $params = [], $types = "") {
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        error_log("Query Preparation Failed: " . $conn->error);
        return false;
    }
    
    // Bind parameters if provided
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("Query Execution Failed: " . $stmt->error);
        return false;
    }
    
    return $stmt;
}

?>