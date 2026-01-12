<?php
/**
 * Add Admission (Manual Entry) - Admin
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
$error = '';
$success = '';

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
            'blood_group' => 'in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'admission_status' => 'required|in:pending,approved'
        ]);
        
        if ($validator->fails()) {
            $error = implode('<br>', $validator->getMessages());
        } else {
            // Check if email exists
            if ($db->exists('students', 'email = ?', [$_POST['email']], 's')) {
                $error = 'Email already exists in the system';
            } else {
                // Generate temporary password
                $tempPassword = generateRandomPassword(8);
                
                $data = [
                    'first_name' => cleanInput($_POST['first_name']),
                    'last_name' => cleanInput($_POST['last_name']),
                    'email' => cleanInput($_POST['email']),
                    'password' => hashPassword($tempPassword),
                    'phone' => cleanInput($_POST['phone']),
                    'date_of_birth' => $_POST['date_of_birth'],
                    'gender' => $_POST['gender'],
                    'address' => cleanInput($_POST['address']),
                    'blood_group' => $_POST['blood_group'] ?? null
                ];
                
                // If approved, generate roll number and add semester
                if ($_POST['admission_status'] === 'approved') {
                    $semester = $_POST['semester'] ?? 1;
                    $year = date('Y');
                    
                    $lastStudent = $db->selectOne(
                        "SELECT roll_number FROM students WHERE roll_number LIKE ? ORDER BY roll_number DESC LIMIT 1",
                        [$year . $semester . '%'],
                        's'
                    );
                    
                    $lastNumber = $lastStudent ? (int)substr($lastStudent['roll_number'], -3) : 0;
                    $rollNumber = generateRollNumber($year, $semester, $lastNumber);
                    
                    $data['roll_number'] = $rollNumber;
                    $data['semester'] = $semester;
                    $data['admission_date'] = date('Y-m-d');
                    $data['status'] = STATUS_ACTIVE;
                    
                    $successMsg = "Admission added and approved! Roll Number: $rollNumber | Password: $tempPassword";
                } else {
                    // Pending status
                    $data['status'] = STATUS_PENDING;
                    $successMsg = "Admission added as pending. Password: $tempPassword";
                }
                
                $studentId = $db->insertRecord('students', $data);
                
                if ($studentId) {
                    logActivity(Session::getUserId(), 'admission_add', "Added admission for: " . $data['email']);
                    setSuccessMessage($successMsg);
                    redirect('modules/admin/admissions/');
                    exit();
                } else {
                    $error = 'Failed to add admission';
                }
            }
        }
    }
}

$pageTitle = "Add New Admission";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-user-plus"></i> Add New Admission</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Admissions
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
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
                                       value="<?php echo $_POST['first_name'] ?? ''; ?>" 
                                       placeholder="Enter first name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo $_POST['last_name'] ?? ''; ?>" 
                                       placeholder="Enter last name" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo $_POST['email'] ?? ''; ?>" 
                                       placeholder="student@example.com" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="text" id="phone" name="phone" class="form-control" 
                                       value="<?php echo $_POST['phone'] ?? ''; ?>" 
                                       placeholder="98XXXXXXXX" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth" class="form-label">Date of Birth *</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" 
                                       value="<?php echo $_POST['date_of_birth'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="gender" class="form-label">Gender *</label>
                                <select id="gender" name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="M" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'M') ? 'selected' : ''; ?>>Male</option>
                                    <option value="F" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'F') ? 'selected' : ''; ?>>Female</option>
                                    <option value="O" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'O') ? 'selected' : ''; ?>>Other</option>
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
                                    <option value="<?php echo $bg; ?>" <?php echo (isset($_POST['blood_group']) && $_POST['blood_group'] == $bg) ? 'selected' : ''; ?>>
                                        <?php echo $bg; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="address" class="form-label">Full Address *</label>
                            <textarea id="address" name="address" class="form-control" rows="3" 
                                      placeholder="Enter complete address" required><?php echo $_POST['address'] ?? ''; ?></textarea>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Admission Status -->
                        <h4 class="mb-3"><i class="fas fa-clipboard-check"></i> Admission Status</h4>
                        
                        <div class="form-group">
                            <label for="admission_status" class="form-label">Status *</label>
                            <select id="admission_status" name="admission_status" class="form-control" 
                                    onchange="toggleSemesterField()" required>
                                <option value="pending">Pending (Requires approval later)</option>
                                <option value="approved">Approved (Admit directly)</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="semester_field" style="display: none;">
                            <label for="semester" class="form-label">Assign Semester *</label>
                            <select id="semester" name="semester" class="form-control">
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo getSemesterName($i); ?></option>
                                <?php endfor; ?>
                            </select>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Roll number will be auto-generated
                            </small>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-key"></i>
                            <strong>Note:</strong> A temporary password will be generated automatically. 
                            Make sure to note it down and share with the student.
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Admission
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

<script>
function toggleSemesterField() {
    const status = document.getElementById('admission_status').value;
    const semesterField = document.getElementById('semester_field');
    const semesterSelect = document.getElementById('semester');
    
    if (status === 'approved') {
        semesterField.style.display = 'block';
        semesterSelect.required = true;
    } else {
        semesterField.style.display = 'none';
        semesterSelect.required = false;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleSemesterField();
});
</script>

<?php require_once '../../../includes/footer.php'; ?>