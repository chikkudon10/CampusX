<?php
/**
 * Edit Student - Admin
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../core/Validator.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_ADMIN);

$db = new Database();
$studentId = $_GET['id'] ?? 0;

// Get student details
$student = $db->getById('students', $studentId);

if (!$student || $student['status'] == STATUS_INACTIVE) {
    setErrorMessage('Student not found or inactive');
    redirect('modules/admin/students/');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $validator = Validator::make($_POST, [
            'first_name' => 'required|alpha|min:2|max:50',
            'last_name' => 'required|alpha|min:2|max:50',
            'email' => 'required|email',
            'phone' => 'required|phone',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:M,F,O',
            'address' => 'required|min:5',
            'semester' => 'required|integer|min_value:1|max_value:8',
            'blood_group' => 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'
        ]);
        
        if ($validator->fails()) {
            $error = implode('<br>', $validator->getMessages());
        } else {
            // Check if email exists for other students
            $emailExists = $db->selectOne(
                "SELECT id FROM students WHERE email = ? AND id != ?",
                [$_POST['email'], $studentId],
                'si'
            );
            
            if ($emailExists) {
                $error = 'Email already exists for another student';
            } else {
                $data = [
                    'first_name' => cleanInput($_POST['first_name']),
                    'last_name' => cleanInput($_POST['last_name']),
                    'email' => cleanInput($_POST['email']),
                    'phone' => cleanInput($_POST['phone']),
                    'date_of_birth' => $_POST['date_of_birth'],
                    'gender' => $_POST['gender'],
                    'address' => cleanInput($_POST['address']),
                    'blood_group' => $_POST['blood_group'] ?? null,
                    'semester' => $_POST['semester']
                ];
                
                $updated = $db->updateRecord('students', $data, 'id = ?', [$studentId]);
                
                if ($updated !== false) {
                    logActivity(Session::getUserId(), 'student_edit', "Edited student ID: $studentId - {$data['first_name']} {$data['last_name']}");
                    setSuccessMessage('Student updated successfully!');
                    redirect('modules/admin/students/');
                    exit();
                } else {
                    $error = 'Failed to update student';
                }
            }
        }
    }
}

$pageTitle = "Edit Student";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-edit"></i> Edit Student</h1>
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a href="view.php?id=<?php echo $studentId; ?>" class="btn btn-info">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Student Info Card -->
            <div class="alert alert-info mb-4">
                <div class="d-flex justify-between align-center">
                    <div>
                        <strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_number']); ?>
                        <span class="mx-3">|</span>
                        <strong>Status:</strong> <span class="badge badge-success">Active</span>
                        <span class="mx-3">|</span>
                        <strong>Registered:</strong> <?php echo formatDate($student['created_at'], 'd M Y'); ?>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Student Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return validateStudentForm()">
                        <?php echo csrfField(); ?>
                        
                        <!-- Personal Information -->
                        <h4 class="mb-3"><i class="fas fa-user"></i> Personal Information</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="text" id="phone" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth" class="form-label">Date of Birth *</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                                       value="<?php echo $student['date_of_birth']; ?>" required>
                                <?php if ($student['date_of_birth']): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        Current Age: <?php echo calculateAge($student['date_of_birth']); ?> years
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="gender" class="form-label">Gender *</label>
                                <select id="gender" name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="M" <?php echo ($student['gender'] == 'M') ? 'selected' : ''; ?>>Male</option>
                                    <option value="F" <?php echo ($student['gender'] == 'F') ? 'selected' : ''; ?>>Female</option>
                                    <option value="O" <?php echo ($student['gender'] == 'O') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="blood_group" class="form-label">Blood Group</label>
                            <select id="blood_group" name="blood_group" class="form-control">
                                <option value="">Select Blood Group (Optional)</option>
                                <?php 
                                global $BLOOD_GROUPS;
                                foreach ($BLOOD_GROUPS as $bg): 
                                ?>
                                    <option value="<?php echo $bg; ?>" <?php echo ($student['blood_group'] == $bg) ? 'selected' : ''; ?>>
                                        <?php echo $bg; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="address" class="form-label">Full Address *</label>
                            <textarea id="address" name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Academic Information -->
                        <h4 class="mb-3"><i class="fas fa-graduation-cap"></i> Academic Information</h4>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="roll_number" class="form-label">Roll Number</label>
                                <input type="text" id="roll_number" class="form-control" 
                                       value="<?php echo htmlspecialchars($student['roll_number']); ?>" 
                                       readonly style="background-color: #e9ecef; cursor: not-allowed;">
                                <small class="text-muted">
                                    <i class="fas fa-lock"></i> Roll number cannot be changed
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label for="semester" class="form-label">Current Semester *</label>
                                <select id="semester" name="semester" class="form-control" required>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($student['semester'] == $i) ? 'selected' : ''; ?>>
                                            <?php echo getSemesterName($i); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> Update when promoting to next semester
                                </small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="admission_date" class="form-label">Admission Date</label>
                                <input type="text" id="admission_date" class="form-control" 
                                       value="<?php echo $student['admission_date'] ? formatDate($student['admission_date']) : 'N/A'; ?>" 
                                       readonly style="background-color: #e9ecef;">
                            </div>
                            
                            <div class="form-group">
                                <label for="created_at" class="form-label">Registration Date</label>
                                <input type="text" id="created_at" class="form-control" 
                                       value="<?php echo formatDate($student['created_at']); ?>" 
                                       readonly style="background-color: #e9ecef;">
                            </div>
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Roll number and dates cannot be modified. 
                            Only personal information and current semester can be updated.
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Student
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <a href="view.php?id=<?php echo $studentId; ?>" class="btn btn-info">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Password Reset Section -->
            <div class="card mt-4">
                <div class="card-header" style="background: #e74c3c; color: white;">
                    <h3 class="card-title"><i class="fas fa-key"></i> Password Management</h3>
                </div>
                <div class="card-body">
                    <p>Reset the student's password if they have forgotten it.</p>
                    <button onclick="resetPassword(<?php echo $studentId; ?>)" class="btn btn-danger">
                        <i class="fas fa-redo"></i> Reset Password
                    </button>
                    <div id="password-result" class="mt-3"></div>
                </div>
            </div>
            
            <!-- Account Status Section -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-cog"></i> Account Status</h3>
                </div>
                <div class="card-body">
                    <p>Manage student account status (suspend, deactivate, etc.)</p>
                    <div style="display: flex; gap: 1rem;">
                        <button onclick="alert('Feature coming soon!')" class="btn btn-warning">
                            <i class="fas fa-pause"></i> Suspend Account
                        </button>
                        <a href="delete.php?id=<?php echo $studentId; ?>" class="btn btn-danger btn-delete">
                            <i class="fas fa-trash"></i> Delete Student
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function resetPassword(studentId) {
    if (!confirm('Are you sure you want to reset this student\'s password?')) {
        return;
    }
    
    showLoading('Resetting password...');
    
    // Generate new password
    const newPassword = generateRandomPassword(8);
    
    // In production, make AJAX call to reset-password.php
    setTimeout(() => {
        hideLoading();
        
        const resultDiv = document.getElementById('password-result');
        resultDiv.innerHTML = `
            <div class="alert alert-success">
                <strong><i class="fas fa-check-circle"></i> Password Reset Successfully!</strong><br>
                New Password: <strong style="font-size: 1.25rem; font-family: monospace;">${newPassword}</strong><br>
                <hr style="margin: 0.5rem 0;">
                <small>
                    <i class="fas fa-info-circle"></i>
                    Please share this password with the student via secure means. 
                    Ask them to change it immediately after login.
                </small>
            </div>
        `;
    }, 1000);
}

function generateRandomPassword(length) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let password = '';
    for (let i = 0; i < length; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return password;
}
</script>

<?php require_once '../../../includes/footer.php'; ?>