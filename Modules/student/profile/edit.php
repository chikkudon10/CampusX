<?php
require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_STUDENT);

$db = new Database();
$student = $db->getOne('students', 'user_id = ?', [$_SESSION['user_id']], 'i');
$user = $db->getOne('users', 'id = ?', [$_SESSION['user_id']], 'i');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    if (empty($firstName)) $errors[] = 'First name is required';
    if (empty($lastName)) $errors[] = 'Last name is required';
    if (empty($email)) $errors[] = 'Email is required';
    
    if ($email !== $student['email']) {
        $exists = $db->getOne('users', 'email = ?', [$email], 's');
        if ($exists) $errors[] = 'Email already in use';
    }
    
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
    }
    
    if (empty($errors)) {
        $studentData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $userData = [
            'email' => $email,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($password)) {
            $userData['password'] = password_hash($password, PASSWORD_BCRYPT);
        }
        
        if ($db->update('students', $studentData, 'id = ?', [$student['id']], 'i') &&
            $db->update('users', $userData, 'id = ?', [$user['id']], 'i')) {
            
            $_SESSION['email'] = $email;
            $_SESSION['success_message'] = 'Profile updated successfully!';
            header('Location: view.php');
            exit();
        } else {
            $errors[] = 'Failed to update profile';
        }
    }
}

$pageTitle = "Edit Profile";
require_once '../../../includes/header.php';
?>

<div class="student-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    <div class="student-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="student-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-user-edit"></i> Edit Profile</h1>
                <a href="view.php" class="btn btn-secondary">Back</a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mb-4">
                    <?php foreach ($errors as $error): ?>
                        <div>• <?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="card">
                <div class="card-header">
                    <h3 class="card-title">Personal Information</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5>Change Password (Optional)</h5>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" 
                                   placeholder="Leave blank to keep current password">
                            <small class="form-text text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 2rem;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <a href="view.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
