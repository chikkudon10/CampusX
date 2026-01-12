<?php
/**
 * Logout Page
 * CampusX - College Management System
 */

// Load configuration
require_once '../../config/config.php';
require_once '../../config/constants.php';

// Load core classes
require_once '../../core/Session.php';
require_once '../../core/Auth.php';

// Perform logout
$auth = new Auth();
$auth->logout();

// Set success message
Session::setFlash('success', 'You have been logged out successfully.');

// Redirect to login page
redirect('modules/authentication/login.php');
exit();
?>