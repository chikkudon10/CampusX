<?php
/**
 * Forgot Password Page
 * CampusX - College Management System
 */

// Load configuration
require_once '../../config/config.php';
require_once '../../config/constants.php';

// Load core classes
require_once '../../core/Session.php';
require_once '../../core/Auth.php';
require_once '../../core/Validator.php';

$error = '';
$success = '';

// Handle forgot password form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Validate input
        $validator = Validator::make($_POST, [
            'email' => 'required|email',
            'role' => 'required|in:admin,teacher,student'
        ]);
        
        if ($validator->fails()) {
            $error = implode('<br>', $validator->getMessages());
        } else {
            // Attempt password reset
            $auth = new Auth();
            $result = $auth->resetPassword($_POST['email'], $_POST['role']);
            
            if ($result['success']) {
                $success = $result['message'];
                // In development, show temp password
                if (DEBUG_MODE && isset($result['temp_password'])) {
                    $success .= '<br><strong>Temporary Password:</strong> ' . $result['temp_password'];
                }
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pageTitle = "Forgot Password";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .forgot-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        
        .forgot-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .forgot-header i {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        .forgot-header h2 {
            color: #2c3e50;
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        
        .forgot-header p {
            color: #7f8c8d;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <i class="fas fa-key"></i>
            <h2>Forgot Password?</h2>
            <p>Enter your email address and we'll send you a new password</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Back to Login
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                
                <!-- Role Selection -->
                <div class="form-group">
                    <label for="role" class="form-label">
                        <i class="fas fa-user-tag"></i> Select Role
                    </label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="">Choose your role</option>
                        <option value="admin">Admin</option>
                        <option value="teacher">Teacher</option>
                        <option value="student">Student</option>
                    </select>
                </div>
                
                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="Enter your registered email"
                        required
                    >
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-paper-plane"></i> Send New Password
                </button>
            </form>
            
            <div class="text-center mt-3">
                <a href="login.php">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="<?php echo ASSETS_PATH; ?>js/main.js"></script>
</body>
</html>