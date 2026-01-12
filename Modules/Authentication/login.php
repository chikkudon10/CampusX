<?php
/**
 * Login Page
 * CampusX - College Management System
 */

// Load configuration
require_once '../../config/config.php';
require_once '../../config/constants.php';

// Load core classes
require_once '../../core/Session.php';
require_once '../../core/Auth.php';
require_once '../../core/Validator.php';

// Redirect if already logged in
if (Session::isLoggedIn()) {
    $role = Session::getUserRole();
    redirect("modules/$role/dashboard.php");
    exit();
}

$error = '';
$success = '';

// Check for timeout message
if (isset($_GET['timeout'])) {
    $error = 'Session expired. Please login again.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Validate input
        $validator = Validator::make($_POST, [
            'email' => 'required|email',
            'password' => 'required',
            'role' => 'required|in:admin,teacher,student'
        ]);
        
        if ($validator->fails()) {
            $error = implode('<br>', $validator->getMessages());
        } else {
            // Attempt login
            $auth = new Auth();
            $result = $auth->login(
                $_POST['email'],
                $_POST['password'],
                $_POST['role']
            );
            
            if ($result['success']) {
                // Redirect based on role
                redirect("modules/{$result['role']}/dashboard.php");
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pageTitle = "Login";
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
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
            display: flex;
        }
        
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-left h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .login-left p {
            font-size: 1.125rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        .feature-list {
            list-style: none;
        }
        
        .feature-list li {
            padding: 0.75rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .feature-list i {
            font-size: 1.25rem;
            color: #ffd700;
        }
        
        .login-right {
            flex: 1;
            padding: 60px 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h2 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #7f8c8d;
        }
        
        .role-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .role-tab {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }
        
        .role-tab input[type="radio"] {
            display: none;
        }
        
        .role-tab:hover {
            border-color: #667eea;
        }
        
        .role-tab input[type="radio"]:checked + label {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 6px;
        }
        
        .role-tab label {
            display: block;
            cursor: pointer;
            padding: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .role-tab i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .login-left {
                padding: 40px 30px;
            }
            
            .login-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Side -->
        <div class="login-left">
            <h1><?php echo APP_NAME; ?></h1>
            <p><?php echo INSTITUTION_NAME; ?></p>
            <p><?php echo INSTITUTION_ADDRESS; ?></p>
            
            <ul class="feature-list">
                <li><i class="fas fa-check-circle"></i> Manage Students & Teachers</li>
                <li><i class="fas fa-check-circle"></i> Track Attendance</li>
                <li><i class="fas fa-check-circle"></i> Manage Assignments</li>
                <li><i class="fas fa-check-circle"></i> View Results & Reports</li>
                <li><i class="fas fa-check-circle"></i> Apply for Leave</li>
            </ul>
        </div>
        
        <!-- Right Side -->
        <div class="login-right">
            <div class="login-header">
                <h2>Welcome Back!</h2>
                <p>Please login to your account</p>
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
            <?php endif; ?>
            
            <form method="POST" action="" onsubmit="return validateLoginForm()">
                <?php echo csrfField(); ?>
                
                <!-- Role Selection -->
                <div class="role-tabs">
                    <div class="role-tab">
                        <input type="radio" name="role" id="role_admin" value="admin" checked>
                        <label for="role_admin">
                            <i class="fas fa-user-shield"></i>
                            <div>Admin</div>
                        </label>
                    </div>
                    <div class="role-tab">
                        <input type="radio" name="role" id="role_teacher" value="teacher">
                        <label for="role_teacher">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <div>Teacher</div>
                        </label>
                    </div>
                    <div class="role-tab">
                        <input type="radio" name="role" id="role_student" value="student">
                        <label for="role_student">
                            <i class="fas fa-user-graduate"></i>
                            <div>Student</div>
                        </label>
                    </div>
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
                        placeholder="Enter your email"
                        required
                    >
                </div>
                
                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter your password"
                        required
                    >
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="d-flex justify-between align-center mb-3">
                    <label>
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="forgot-password.php">Forgot password?</a>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="text-center mt-3">
                <p style="color: #7f8c8d;">
                    Don't have an account? <a href="register.php">Register here</a>
                </p>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_PATH; ?>js/main.js"></script>
    <script src="<?php echo ASSETS_PATH; ?>js/validation.js"></script>
</body>
</html>