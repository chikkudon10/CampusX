<?php
require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_STUDENT);

$db = new Database();
$student = $db->getOne('students', 'user_id = ?', [$_SESSION['user_id']], 'i');
$studentId = $student['id'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fromDate = $_POST['from_date'] ?? '';
    $toDate = $_POST['to_date'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    $leaveType = $_POST['leave_type'] ?? '';
    
    if (empty($fromDate)) $errors[] = 'From date is required';
    if (empty($toDate)) $errors[] = 'To date is required';
    if (empty($reason)) $errors[] = 'Reason is required';
    if (empty($leaveType)) $errors[] = 'Leave type is required';
    
    if (strtotime($fromDate) > strtotime($toDate)) {
        $errors[] = 'From date must be before to date';
    }
    
    if (empty($errors)) {
        $data = [
            'student_id' => $studentId,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'reason' => $reason,
            'leave_type' => $leaveType,
            'status' => 'pending',
            'applied_date' => date('Y-m-d H:i:s')
        ];
        
        if ($db->insert('leave_applications', $data)) {
            $_SESSION['success_message'] = 'Leave application submitted successfully!';
            header('Location: history.php');
            exit();
        } else {
            $errors[] = 'Failed to submit application. Please try again.';
        }
    }
}

$pageTitle = "Apply Leave";
require_once '../../../includes/header.php';
?>

<div class="student-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    <div class="student-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="student-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-file-medical"></i> Apply for Leave</h1>
                <a href="history.php" class="btn btn-secondary">View History</a>
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
                    <h3 class="card-title">Leave Application Form</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type" class="form-control" required>
                            <option value="">Select Leave Type</option>
                            <option value="Sick">Sick Leave</option>
                            <option value="Casual">Casual Leave</option>
                            <option value="Emergency">Emergency Leave</option>
                            <option value="Medical">Medical Leave</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="form-label">From Date <span class="text-danger">*</span></label>
                            <input type="date" name="from_date" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="form-label">To Date <span class="text-danger">*</span></label>
                            <input type="date" name="to_date" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4" required></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 2rem;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Submit
                        </button>
                        <a href="history.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
