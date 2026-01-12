<?php
/**
 * Add Student - Admin
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
            'semester' => 'required|integer|min_value:1|max_value:8',
            'blood_group' => 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'
        ]);
        
        if ($validator->fails()) {
            $error = implode('<br>', $validator->getMessages());
        } else {
            // Check if email exists
            if ($db->exists('students', 'email = ?', [$_POST['email']], 's')) {
                $error = 'Email already exists';
            } else {
                // Generate roll number
                $year = date('Y');
                $semester = $_POST['semester'];
                $lastStudent = $db->selectOne(
                    "SELECT roll_number FROM students WHERE roll_number LIKE ? ORDER BY roll_number DESC LIMIT 1",
                    [$year . $semester . '%'],
                    's'
                );
                
                $lastNumber = $lastStudent ? (int)substr($lastStudent['roll_number'], -3) : 0;
                $rollNumber = generateRollNumber($year, $semester, $lastNumber);
                
                // Generate temporary password
                $tempPassword = generateRandomPassword(8);
                
                $data = [
                    'roll_number' => $rollNumber,
                    'first_name' => cleanInput($_POST['first_name']),
                    'last_name' => cleanInput($_POST['last_name']),
                    'email' => cleanInput($_POST['email']),
                    'password' => hashPassword($tempPassword),
                    'phone' => cleanInput($_POST['phone']),
                    'date_of_birth' => $_POST['date_of_birth'],
                    'gender' => $_POST['gender'],
                    'address' => cleanInput($_POST['address']),
                    'blood_group' => $_POST['blood_group'] ?? null,
                    'semester' => $semester,
                    'admission_date' => date('Y-m-d'),
                    'status' => STATUS_ACTIVE
                ];
                
                $studentId = $db->insertRecord('students', $data);
                
                if ($studentId) {
                    logActivity(Session::getUserId(), 'student_add', "Added student: $rollNumber");
                    setSuccessMessage("Student added successfully! Roll Number: $rollNumber | Temporary Password: $tempPassword");
                    redirect('modules/admin/students/');
                    exit();
                } else {
                    $error = 'Failed to add student';
                }
            }
        }
    }
}

$pageTitle = "Add Student";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-user-plus"></i> Add New Student</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Student Information</h3>
                </div>
                <div class="card-body">
                    <form method="POST" onsubmit="return validateStudentForm()">
                        <?php echo csrfField(); ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       value="<?php echo $_POST['first_name'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       value="<?php echo $_POST['last_name'] ?? ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo $_POST['email'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="form-label">Phone *</label>
                                <input type="text" id="phone" name="phone" class="form-control" 
                                       placeholder="98XXXXXXXX" value="<?php echo $_POST['phone'] ?? ''; ?>" required>
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
                                    <option value="M">Male</option>
                                    <option value="F">Female</option>
                                    <option value="O">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="blood_group" class="form-label">Blood Group</label>
                                <select id="blood_group" name="blood_group" class="form-control">
                                    <option value="">Select Blood Group</option>
                                    <?php 
                                    global $BLOOD_GROUPS;
                                    foreach ($BLOOD_GROUPS as $bg): 
                                    ?>
                                        <option value="<?php echo $bg; ?>"><?php echo $bg; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="semester" class="form-label">Semester *</label>
                                <select id="semester" name="semester" class="form-control" required>
                                    <option value="">Select Semester</option>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo getSemesterName($i); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address" class="form-label">Address *</label>
                            <textarea id="address" name="address" class="form-control" rows="3" required><?php echo $_POST['address'] ?? ''; ?></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Roll number and temporary password will be generated automatically.
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Student
                            </button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>