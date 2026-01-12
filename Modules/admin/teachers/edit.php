<?php
/**
 * Edit Teacher - Admin
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_ADMIN);

$db = new Database();
$errors = [];
$success = false;

// Get teacher ID from URL
$teacherId = intval($_GET['id'] ?? 0);

if ($teacherId === 0) {
    $_SESSION['error_message'] = 'Invalid teacher ID';
    header('Location: index.php');
    exit();
}

// Get teacher details
$teacher = $db->getOne(
    'teachers',
    'id = ?',
    [$teacherId],
    'i'
);

if (!$teacher) {
    $_SESSION['error_message'] = 'Teacher not found';
    header('Location: index.php');
    exit();
}

// Get user details
$user = $db->getOne(
    'users',
    'id = ?',
    [$teacher['user_id']],
    'i'
);

if (!$user) {
    $_SESSION['error_message'] = 'User account not found';
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $qualifications = trim($_POST['qualifications'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $experience = intval($_POST['experience'] ?? 0);
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    
    // Validation
    if (empty($firstName)) {
        $errors[] = 'First name is required';
    }
    
    if (empty($lastName)) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check if email already exists (for other users)
    if ($email !== $teacher['email']) {
        $existingUser = $db->getOne('users', 'email = ?', [$email], 's');
        if ($existingUser) {
            $errors[] = 'Email already registered';
        }
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10}$/', preg_replace('/[^0-9]/', '', $phone))) {
        $errors[] = 'Phone number must be 10 digits';
    }
    
    if (empty($department)) {
        $errors[] = 'Department is required';
    }
    
    if (empty($qualifications)) {
        $errors[] = 'Qualifications are required';
    }
    
    if ($experience < 0 || $experience > 60) {
        $errors[] = 'Experience must be between 0 and 60 years';
    }
    
    // Password validation (only if provided)
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
    }
    
    // If no errors, proceed with update
    if (empty($errors)) {
        try {
            // Update user
            $userData = [
                'email' => $email,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Add password if provided
            if (!empty($password)) {
                $userData['password'] = password_hash($password, PASSWORD_BCRYPT);
            }
            
            $userUpdated = $db->update(
                'users',
                $userData,
                'id = ?',
                [$user['id']],
                'i'
            );
            
            // Update teacher details
            $teacherData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'qualifications' => $qualifications,
                'department' => $department,
                'experience' => $experience,
                'bio' => trim($_POST['bio'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $teacherUpdated = $db->update(
                'teachers',
                $teacherData,
                'id = ?',
                [$teacherId],
                'i'
            );
            
            if ($userUpdated !== false && $teacherUpdated !== false) {
                $_SESSION['success_message'] = 'Teacher updated successfully!';
                header('Location: index.php');
                exit();
            } else {
                $errors[] = 'Failed to update teacher. Please try again.';
            }
            
        } catch (Exception $e) {
            $errors[] = 'An error occurred: ' . $e->getMessage();
        }
    }
}

// Get all departments (for dropdown)
$departments = $db->select("SELECT DISTINCT department FROM teachers ORDER BY department");

$pageTitle = "Edit Teacher";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-user-tie"></i> Edit Teacher</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Teachers
                </a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="fas fa-exclamation-circle"></i> Errors found:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Teacher Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($teacher['first_name']); ?>" required>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($teacher['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($teacher['phone']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="department" name="department" 
                                       value="<?php echo htmlspecialchars($teacher['department']); ?>" 
                                       list="departmentList" required>
                                <datalist id="departmentList">
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['department']); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="experience" class="form-label">Years of Experience <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="experience" name="experience" 
                                       value="<?php echo htmlspecialchars($teacher['experience']); ?>" 
                                       min="0" max="60" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="qualifications" class="form-label">Qualifications <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="qualifications" name="qualifications" rows="3" required><?php echo htmlspecialchars($teacher['qualifications']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio" class="form-label">Biography</label>
                            <textarea class="form-control" id="bio" name="bio" rows="3"><?php echo htmlspecialchars($teacher['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3">Account Settings</h5>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle"></i> Leave password fields empty to keep current password
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <small class="form-text text-muted">Minimum 6 characters (leave blank to keep current)</small>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <div class="form-group" style="display: flex; gap: 10px; margin-top: 30px;">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Update Teacher
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>