<?php
/**
 * Verify Admission - Admin
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
$admissionId = $_GET['id'] ?? 0;

// Get admission details
$admission = $db->getById('students', $admissionId);

if (!$admission || $admission['status'] != STATUS_PENDING) {
    setErrorMessage('Invalid admission or already processed');
    redirect('modules/admin/admissions/');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'approve') {
            // Generate roll number
            $year = date('Y');
            $semester = $_POST['semester'] ?? 1;
            $lastStudent = $db->selectOne(
                "SELECT roll_number FROM students WHERE roll_number LIKE ? ORDER BY roll_number DESC LIMIT 1",
                [$year . $semester . '%'],
                's'
            );
            
            $lastNumber = $lastStudent ? (int)substr($lastStudent['roll_number'], -3) : 0;
            $rollNumber = generateRollNumber($year, $semester, $lastNumber);
            
            // Update student
            $updated = $db->updateRecord('students', [
                'roll_number' => $rollNumber,
                'semester' => $semester,
                'admission_date' => date('Y-m-d'),
                'status' => STATUS_ACTIVE
            ], 'id = ?', [$admissionId]);
            
            if ($updated) {
                logActivity(Session::getUserId(), 'admission_approve', "Approved admission for student ID: $admissionId");
                setSuccessMessage('Admission approved successfully! Roll Number: ' . $rollNumber);
                redirect('modules/admin/admissions/?filter=approved');
                exit();
            } else {
                $error = 'Failed to approve admission';
            }
            
        } elseif ($action === 'reject') {
            $reason = cleanInput($_POST['rejection_reason'] ?? '');
            
            if (empty($reason)) {
                $error = 'Rejection reason is required';
            } else {
                $updated = $db->updateRecord('students', [
                    'status' => STATUS_INACTIVE
                ], 'id = ?', [$admissionId]);
                
                if ($updated) {
                    logActivity(Session::getUserId(), 'admission_reject', "Rejected admission for student ID: $admissionId. Reason: $reason");
                    setSuccessMessage('Admission rejected');
                    redirect('modules/admin/admissions/?filter=rejected');
                    exit();
                } else {
                    $error = 'Failed to reject admission';
                }
            }
        }
    }
}

$pageTitle = "Verify Admission";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-user-check"></i> Verify Admission</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Student Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Student Information</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                        <div>
                            <strong>Name:</strong><br>
                            <?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?>
                        </div>
                        <div>
                            <strong>Email:</strong><br>
                            <?php echo htmlspecialchars($admission['email']); ?>
                        </div>
                        <div>
                            <strong>Phone:</strong><br>
                            <?php echo htmlspecialchars($admission['phone'] ?? 'N/A'); ?>
                        </div>
                        <div>
                            <strong>Date of Birth:</strong><br>
                            <?php echo $admission['date_of_birth'] ? formatDate($admission['date_of_birth']) : 'N/A'; ?>
                        </div>
                        <div>
                            <strong>Gender:</strong><br>
                            <?php 
                            $genders = ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'];
                            echo $genders[$admission['gender']] ?? 'N/A';
                            ?>
                        </div>
                        <div>
                            <strong>Age:</strong><br>
                            <?php echo $admission['date_of_birth'] ? calculateAge($admission['date_of_birth']) . ' years' : 'N/A'; ?>
                        </div>
                        <div style="grid-column: 1 / -1;">
                            <strong>Address:</strong><br>
                            <?php echo htmlspecialchars($admission['address'] ?? 'N/A'); ?>
                        </div>
                        <div>
                            <strong>Applied Date:</strong><br>
                            <?php echo formatDate($admission['created_at']); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Approval Form -->
            <div class="card mb-3">
                <div class="card-header" style="background: #27ae60; color: white;">
                    <h3 class="card-title"><i class="fas fa-check-circle"></i> Approve Admission</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="approve">
                        
                        <div class="form-group">
                            <label for="semester" class="form-label">Assign Semester *</label>
                            <select id="semester" name="semester" class="form-control" required>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo getSemesterName($i); ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Roll number will be auto-generated upon approval.
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Approve Admission
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Rejection Form -->
            <div class="card">
                <div class="card-header" style="background: #e74c3c; color: white;">
                    <h3 class="card-title"><i class="fas fa-times-circle"></i> Reject Admission</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="reject">
                        
                        <div class="form-group">
                            <label for="rejection_reason" class="form-label">Rejection Reason *</label>
                            <textarea 
                                id="rejection_reason" 
                                name="rejection_reason" 
                                class="form-control" 
                                rows="3"
                                placeholder="Enter reason for rejection"
                                required
                            ></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to reject this admission?')">
                            <i class="fas fa-times"></i> Reject Admission
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>