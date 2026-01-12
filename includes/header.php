<?php
/**
 * Common Header File
 * CampusX - College Management System
 * Include this file at the top of every page
 */

// Load configuration
if (!defined('APP_ACCESS')) {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/constants.php';
}

// Get flash messages
$flashMessages = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="<?php echo APP_FULL_NAME; ?> - <?php echo INSTITUTION_NAME; ?>">
    <meta name="author" content="K-Gang Team">
    
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo ASSETS_PATH; ?>images/logo/favicon.ico" type="image/x-icon">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/style.css">
    
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo ASSETS_PATH . 'css/' . $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php
// Display flash messages if any
if ($flashMessages['success']): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($flashMessages['success']); ?>
    </div>
<?php endif; ?>

<?php if ($flashMessages['error']): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($flashMessages['error']); ?>
    </div>
<?php endif; ?>