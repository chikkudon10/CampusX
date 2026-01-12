<?php
/**
 * Student Registration Page
 * CampusX - College Management System
 */
include('../../includes/functions.php');
// Load configuration
require_once '../../config/config.php';
require_once '../../config/constants.php';

// Load core classes
require_once '../../core/Session.php';
require_once '../../core/Auth.php';
require_once '../../core/Validator.php';
require_once '../../core/Database.php';

// Redirect if already logged in
if (Session::isLoggedIn()) {
    $role = Session::getUserRole();
    redirect("modules/$role/dashboard.php");
    exit();
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        // Validate input
        $validator = Validator::make($_POST, [
            'first_name' => 'required|alpha|min:2|max:50',
            'last_name' => 'required|alpha|min:2|max:50',
            'email' => 'required|email',
            'phone' => 'required|phone',
            'password' => 'required|min:6',
            'confirm_password' => 'required|same:password',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:M,F,O',
            'address' => 'required|min:5'
        ]);
        
        if ($validator->fails()) {
            $error = implode('<br>', $validator->getMessages());
        } else {
            // Prepare data for registration
            $data = [
                'first_name' => cleanInput($_POST['first_name']),
                'last_name' => cleanInput($_POST['last_name']),
                'email' => cleanInput($_POST['email']),
                'phone' => cleanInput($_POST['phone']),
                'password' => $_POST['password'],
                'date_of_birth' => $_POST['date_of_birth'],
                'gender' => $_POST['gender'],
                'address' => cleanInput($_POST['address']),
                'semester' => 1, // Default first semester
                'status' => STATUS_PENDING // Pending approval
            ];
            
            // Attempt registration
            $auth = new Auth();
            $result = $auth->register($data, ROLE_STUDENT);
            
            if ($result['success']) {
                $success = 'Registration successful! Your account is pending approval. You will receive an email once approved.';
                // Clear form data
                $_POST = [];
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pageTitle = "Student Registration";
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
            padding: 40px 20px;
        }
        
        .register-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .register-header p {
            color: #7f8c8d;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .register-container {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1><i class="fas fa-user-graduate"></i> Student Registration</h1>
            <p>Create your account to access <?php echo APP_NAME; ?></p>
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
                <a href="login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="" onsubmit="return validateStudentForm()">
                <?php echo csrfField(); ?>
                
                <div class="form-row">
                    <!-- First Name -->
                    <div class="form-group">
                        <label for="first_name" class="form-label">
                            <i class="fas fa-user"></i> First Name *
                        </label>
                        <input 
                            type="text" 
                            id="first_name" 
                            name="first_name" 
                            class="form-control" 
                            placeholder="Enter first name"
                            value="<?php echo $_POST['first_name'] ?? ''; ?>"
                            required
                        >
                    </div>
                    
                    <!-- Last Name -->
                    <div class="form-group">
                        <label for="last_name" class="form-label">
                            <i class="fas fa-user"></i> Last Name *
                        </label>
                        <input 
                            type="text" 
                            id="last_name" 
                            name="last_name" 
                            class="form-control" 
                            placeholder="Enter last name"
                            value="<?php echo $_POST['last_name'] ?? ''; ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="form-row">
                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address *
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-control" 
                            placeholder="your.email@example.com"
                            value="<?php echo $_POST['email'] ?? ''; ?>"
                            required
                        >
                    </div>
                    
                    <!-- Phone -->
                    <div class="form-group">
                        <label for="phone" class="form-label">
                            <i class="fas fa-phone"></i> Phone Number *
                        </label>
                        <input 
                            type="text" 
                            id="phone" 
                            name="phone" 
                            class="form-control" 
                            placeholder="98XXXXXXXX"
                            value="<?php echo $_POST['phone'] ?? ''; ?>"
                            required
                        >
                    </div>
                </div>
                
                <div class="form-row">
                    <!-- Date of Birth -->
                    <div class="form-group">
                        <label for="date_of_birth" class="form-label">
                            <i class="fas fa-calendar"></i> Date of Birth *
                        </label>
                        <input 
                            type="date" 
                            id="date_of_birth" 
                            name="date_of_birth" 
                            class="form-control"
                            value="<?php echo $_POST['date_of_birth'] ?? ''; ?>"
                            required
                        >
                    </div>
                    
                    <!-- Gender -->
                    <div class="form-group">
                        <label for="gender" class="form-label">
                            <i class="fas fa-venus-mars"></i> Gender *
                        </label>
                        <select id="gender" name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="M" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'M') ? 'selected' : ''; ?>>Male</option>
                            <option value="F" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'F') ? 'selected' : ''; ?>>Female</option>
                            <option value="O" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'O') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <!-- Address -->
                <div class="form-group">
                    <label for="address" class="form-label">
                        <i class="fas fa-map-marker-alt"></i> Address *
                    </label>
                    <textarea 
                        id="address" 
                        name="address" 
                        class="form-control" 
                        rows="2"
                        placeholder="Enter your full address"
                        required
                    ><?php echo $_POST['address'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <!-- Password -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password *
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Minimum 6 characters"
                            required
                        >
                    </div>
                    
                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock"></i> Confirm Password *
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-control" 
                            placeholder="Re-enter password"
                            required
                        >
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>
            
            <div class="text-center mt-3">
                <p style="color: #7f8c8d;">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="<?php echo ASSETS_PATH; ?>js/main.js"></script>
    <script src="<?php echo ASSETS_PATH; ?>js/validation.js"></script>
</body>
</html>