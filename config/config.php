<?php
/**
 * Main Configuration File
 * CampusX - College Management System
 * 
 * Contains all general application settings
 */

// Security: Prevent direct access
define('APP_ACCESS', true);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== APPLICATION SETTINGS ====================

// Application Name
define('APP_NAME', 'CampusX');
define('APP_FULL_NAME', 'College Management System');

// Version
define('APP_VERSION', '1.0.0');

// Institution Details
define('INSTITUTION_NAME', 'Prithvi Narayan Campus');
define('INSTITUTION_ADDRESS', 'Bagar-1, Pokhara');
define('INSTITUTION_EMAIL', 'info@pncampus.edu.np');
define('INSTITUTION_PHONE', '+977-61-123456');

// ==================== PATH SETTINGS ====================

// Base URL (Update this according to your local setup)
define('BASE_URL', 'http://localhost/campusx/');

// Directory paths
define('ROOT_PATH', dirname(dirname(__FILE__)) . '/');
define('ASSETS_PATH', BASE_URL . 'assets/');
define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
define('UPLOADS_URL', BASE_URL . 'uploads/');

// ==================== DATE & TIME SETTINGS ====================

// Timezone
date_default_timezone_set('Asia/Kathmandu');

// Date format
define('DATE_FORMAT', 'd-m-Y');
define('DATETIME_FORMAT', 'd-m-Y H:i:s');

// ==================== SESSION SETTINGS ====================

// Session timeout (in seconds) - 30 minutes
define('SESSION_TIMEOUT', 1800);

// ==================== SECURITY SETTINGS ====================

// Password hashing
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_COST', 12);

// CSRF Token (for form security)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==================== FILE UPLOAD SETTINGS ====================

// Maximum file upload size (in bytes) - 5MB
define('MAX_FILE_SIZE', 5242880);

// Allowed file extensions
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'txt']);

// ==================== PAGINATION SETTINGS ====================

define('RECORDS_PER_PAGE', 10);

// ==================== EMAIL SETTINGS (Optional) ====================

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_FROM_EMAIL', 'noreply@campusx.edu.np');
define('SMTP_FROM_NAME', APP_NAME);

// ==================== ERROR REPORTING ====================

// Development mode (set to false in production)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . 'logs/error.log');
}

// ==================== INCLUDE DATABASE CONFIG ====================

require_once 'database.php';

// ==================== HELPER FUNCTIONS ====================

/**
 * Redirect to another page
 */
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check user role
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate CSRF token input field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format date
 */
function formatDate($date, $format = DATE_FORMAT) {
    return date($format, strtotime($date));
}

/**
 * Display success message
 */
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Display error message
 */
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Get and clear flash messages
 */
function getFlashMessage() {
    $success = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
    $error = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;
    
    unset($_SESSION['success_message']);
    unset($_SESSION['error_message']);
    
    return ['success' => $success, 'error' => $error];
}

?>