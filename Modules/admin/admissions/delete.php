<?php
/**
 * Delete Admission - Admin
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_ADMIN);

$db = new Database();
$admissionId = $_GET['id'] ?? 0;

// Get admission details
$admission = $db->getById('students', $admissionId);

if (!$admission) {
    setErrorMessage('Admission not found');
    redirect('modules/admin/admissions/');
    exit();
}

$error = '';

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        // Check if student has any related records
        $hasAttendance = $db->count('attendance', 'student_id = ?', [$admissionId], 'i') > 0;
        $hasAssignments = $db->count('assignment_submissions', 'student_id = ?', [$admissionId], 'i') > 0;
        $hasResults = $db->count('results', 'student_id = ?', [$admissionId], 'i') > 0;
        
        if ($hasAttendance || $hasAssignments || $hasResults) {
            // Soft delete - just mark as inactive
            $deleted = $db->updateRecord('students', 
                ['status' => STATUS_INACTIVE], 
                'id = ?', 
                [$admissionId]
            );
            
            if ($deleted !== false) {
                logActivity(Session::getUserId(), 'admission_soft_delete', "Soft deleted admission ID: $admissionId");
                setSuccessMessage('Admission deactivated successfully. Student data has been archived.');
                redirect('modules/admin/admissions/?filter=rejected');
                exit();
            } else {
                $error = 'Failed to deactivate admission';
            }
        } else {
            // Hard delete - completely remove record
            $deleted = $db->deleteRecord('students', 'id = ?', [$admissionId]);
            
            if ($deleted) {
                logActivity(Session::getUserId(), 'admission_hard_delete', "Permanently deleted admission ID: $admissionId");
                setSuccessMessage('Admission deleted permanently.');
                redirect('modules/admin/admissions/');
                exit();
            } else {
                $error = 'Failed to delete admission';
            }
        }
    }
}

// Check for related records to show warning
$relatedRecords = [];
$attendanceCount = $db->count('attendance', 'student_id = ?', [$admissionId], 'i');
$assignmentCount = $db->count('assignment_submissions', 'student_id = ?', [$admissionId], 'i');
$resultsCount = $db->count('results', 'student_id = ?', [$admissionId], 'i');
$coursesCount = $db->count('student_courses', 'student_id = ?', [$admissionId], 'i');

if ($attendanceCount > 0) $relatedRecords[] = "$attendanceCount attendance record(s)";
if ($assignmentCount > 0) $relatedRecords[] = "$assignmentCount assignment submission(s)";
if ($resultsCount > 0) $relatedRecords[] = "$resultsCount result(s)";
if ($coursesCount > 0) $relatedRecords[] = "$coursesCount course enrollment(s)";

$hasRelatedData = !empty($relatedRecords);

$pageTitle = "Delete Admission";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-trash"></i> Delete Admission</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Warning Card -->
            <div class="card mb-4" style="border-left: 5px solid #e74c3c;">
                <div class="card-body">
                    <h3 style="color: #e74c3c; margin-bottom: 1rem;">
                        <i class="fas fa-exclamation-triangle"></i> Confirm Deletion
                    </h3>
                    <p style="font-size: 1.125rem;">
                        Are you sure you want to delete this admission?
                    </p>
                </div>
            </div>
            
            <!-- Student Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Student Information</h3>
                </div>
                <div class="card-body">
                    <table class="table" style="margin: 0;">
                        <tr>
                            <th style="width: 200px;">Name:</th>
                            <td><strong><?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($admission['email']); ?></td>
                        </tr>
                        <?php if ($admission['roll_number']): ?>
                            <tr>
                                <th>Roll Number:</th>
                                <td><strong><?php echo htmlspecialchars($admission['roll_number']); ?></strong></td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <?php if ($admission['status'] == STATUS_PENDING): ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php elseif ($admission['status'] == STATUS_ACTIVE): ?>
                                    <span class="badge badge-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Applied Date:</th>
                            <td><?php echo formatDate($admission['created_at']); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <?php if ($hasRelatedData): ?>
                <!-- Related Data Warning -->
                <div class="alert alert-warning mb-4">
                    <h4><i class="fas fa-database"></i> Related Data Found</h4>
                    <p>This student has the following related records in the system:</p>
                    <ul style="margin-bottom: 0;">
                        <?php foreach ($relatedRecords as $record): ?>
                            <li><?php echo $record; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <hr style="margin: 1rem 0;">
                    <p style="margin: 0;">
                        <strong>Note:</strong> The student will be marked as <strong>inactive</strong> instead of being permanently deleted. 
                        All related data will be preserved for record-keeping purposes.
                    </p>
                </div>
            <?php else: ?>
                <!-- No Related Data -->
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i>
                    <strong>No related data found.</strong> This admission will be permanently deleted from the system.
                </div>
            <?php endif; ?>
            
            <!-- Deletion Form -->
            <div class="card">
                <div class="card-header" style="background: #e74c3c; color: white;">
                    <h3 class="card-title">
                        <i class="fas fa-trash-alt"></i> 
                        <?php echo $hasRelatedData ? 'Deactivate Admission' : 'Delete Admission'; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        
                        <div class="alert alert-danger">
                            <strong>⚠️ Warning:</strong> This action cannot be undone!
                            <?php if ($hasRelatedData): ?>
                                <br>The student will be deactivated and their data will be archived.
                            <?php else: ?>
                                <br>All information about this admission will be permanently deleted.
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="confirm_delete" required>
                                I understand and want to proceed with 
                                <?php echo $hasRelatedData ? 'deactivation' : 'deletion'; ?>
                            </label>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-danger" id="delete_button" disabled>
                                <i class="fas fa-trash"></i> 
                                <?php echo $hasRelatedData ? 'Deactivate Admission' : 'Delete Permanently'; ?>
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <a href="view.php?id=<?php echo $admissionId; ?>" class="btn btn-info">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Alternative Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-question-circle"></i> Need a Different Action?</h3>
                </div>
                <div class="card-body">
                    <p>If you don't want to delete this admission, consider these alternatives:</p>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <a href="edit.php?id=<?php echo $admissionId; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Information
                        </a>
                        <?php if ($admission['status'] == STATUS_ACTIVE): ?>
                            <button onclick="alert('Feature coming soon!')" class="btn btn-warning">
                                <i class="fas fa-pause"></i> Suspend Student
                            </button>
                        <?php endif; ?>
                        <?php if ($admission['status'] == STATUS_PENDING): ?>
                            <a href="verify.php?id=<?php echo $admissionId; ?>" class="btn btn-success">
                                <i class="fas fa-times"></i> Reject Admission
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Enable delete button only when checkbox is checked
document.getElementById('confirm_delete')?.addEventListener('change', function() {
    document.getElementById('delete_button').disabled = !this.checked;
});
</script>

<?php require_once '../../../includes/footer.php'; ?>