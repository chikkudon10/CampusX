<?php
/**
 * View Admission Details - Admin
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

// Get enrolled courses (if approved)
$enrolledCourses = [];
if ($admission['status'] == STATUS_ACTIVE) {
    $enrolledCourses = $db->select(
        "SELECT c.* FROM courses c
         JOIN student_courses sc ON c.id = sc.course_id
         WHERE sc.student_id = ?",
        [$admissionId],
        'i'
    );
}

$pageTitle = "View Admission Details";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-eye"></i> Admission Details</h1>
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a href="edit.php?id=<?php echo $admissionId; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <?php if ($admission['status'] == STATUS_PENDING): ?>
                        <a href="verify.php?id=<?php echo $admissionId; ?>" class="btn btn-success">
                            <i class="fas fa-check"></i> Verify
                        </a>
                    <?php endif; ?>
                    <button onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            
            <!-- Status Badge -->
            <div class="card mb-4" style="border-left: 5px solid 
                <?php 
                echo $admission['status'] == STATUS_PENDING ? '#f39c12' : 
                    ($admission['status'] == STATUS_ACTIVE ? '#27ae60' : '#e74c3c'); 
                ?>;">
                <div class="card-body">
                    <div class="d-flex justify-between align-center">
                        <div>
                            <h3 style="margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?>
                            </h3>
                            <p style="color: #7f8c8d; margin: 0;">
                                <?php echo htmlspecialchars($admission['email']); ?>
                            </p>
                        </div>
                        <div>
                            <?php if ($admission['status'] == STATUS_PENDING): ?>
                                <span class="badge badge-warning" style="font-size: 1.25rem; padding: 0.5rem 1rem;">
                                    <i class="fas fa-clock"></i> Pending Approval
                                </span>
                            <?php elseif ($admission['status'] == STATUS_ACTIVE): ?>
                                <span class="badge badge-success" style="font-size: 1.25rem; padding: 0.5rem 1rem;">
                                    <i class="fas fa-check-circle"></i> Approved
                                </span>
                            <?php else: ?>
                                <span class="badge badge-danger" style="font-size: 1.25rem; padding: 0.5rem 1rem;">
                                    <i class="fas fa-times-circle"></i> Rejected
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
                <!-- Main Information -->
                <div>
                    <!-- Personal Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user"></i> Personal Information</h3>
                        </div>
                        <div class="card-body">
                            <table class="table" style="margin: 0;">
                                <tr>
                                    <th style="width: 200px;">Full Name:</th>
                                    <td><?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo htmlspecialchars($admission['email']); ?></td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td><?php echo htmlspecialchars($admission['phone'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Date of Birth:</th>
                                    <td>
                                        <?php 
                                        if ($admission['date_of_birth']) {
                                            echo formatDate($admission['date_of_birth']);
                                            echo ' (' . calculateAge($admission['date_of_birth']) . ' years)';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Gender:</th>
                                    <td>
                                        <?php 
                                        $genders = ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'];
                                        echo $genders[$admission['gender']] ?? 'N/A';
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Blood Group:</th>
                                    <td><?php echo htmlspecialchars($admission['blood_group'] ?? 'Not specified'); ?></td>
                                </tr>
                                <tr>
                                    <th>Address:</th>
                                    <td><?php echo nl2br(htmlspecialchars($admission['address'] ?? 'N/A')); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <?php if ($admission['status'] == STATUS_ACTIVE): ?>
                        <!-- Academic Information -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                            </div>
                            <div class="card-body">
                                <table class="table" style="margin: 0;">
                                    <tr>
                                        <th style="width: 200px;">Roll Number:</th>
                                        <td><strong style="font-size: 1.25rem; color: #2c3e50;">
                                            <?php echo htmlspecialchars($admission['roll_number'] ?? 'Not assigned'); ?>
                                        </strong></td>
                                    </tr>
                                    <tr>
                                        <th>Current Semester:</th>
                                        <td><?php echo getSemesterName($admission['semester']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Admission Date:</th>
                                        <td><?php echo $admission['admission_date'] ? formatDate($admission['admission_date']) : 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Academic Year:</th>
                                        <td><?php echo getAcademicYear($admission['admission_date']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Enrolled Courses -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-book"></i> Enrolled Courses</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($enrolledCourses)): ?>
                                    <p class="text-center" style="color: #7f8c8d; padding: 2rem;">
                                        <i class="fas fa-info-circle"></i> No courses enrolled yet
                                    </p>
                                <?php else: ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Course Code</th>
                                                <th>Course Name</th>
                                                <th>Credits</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($enrolledCourses as $course): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                                    <td><?php echo $course['credits']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Sidebar -->
                <div>
                    <!-- Application Timeline -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-clock"></i> Timeline</h3>
                        </div>
                        <div class="card-body">
                            <div style="position: relative; padding-left: 2rem;">
                                <!-- Applied Date -->
                                <div style="margin-bottom: 1.5rem; position: relative;">
                                    <div style="position: absolute; left: -2rem; top: 0; width: 10px; height: 10px; background: #3498db; border-radius: 50%;"></div>
                                    <small style="color: #7f8c8d;">Applied Date</small>
                                    <p style="margin: 0; font-weight: 600;">
                                        <?php echo formatDate($admission['created_at'], 'd M Y'); ?>
                                    </p>
                                </div>
                                
                                <?php if ($admission['status'] == STATUS_ACTIVE && $admission['admission_date']): ?>
                                    <!-- Approval Date -->
                                    <div style="margin-bottom: 1.5rem; position: relative;">
                                        <div style="position: absolute; left: -2rem; top: 0; width: 10px; height: 10px; background: #27ae60; border-radius: 50%;"></div>
                                        <small style="color: #7f8c8d;">Approved Date</small>
                                        <p style="margin: 0; font-weight: 600;">
                                            <?php echo formatDate($admission['admission_date'], 'd M Y'); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Last Updated -->
                                <div style="position: relative;">
                                    <div style="position: absolute; left: -2rem; top: 0; width: 10px; height: 10px; background: #95a5a6; border-radius: 50%;"></div>
                                    <small style="color: #7f8c8d;">Last Updated</small>
                                    <p style="margin: 0; font-weight: 600;">
                                        <?php echo formatDate($admission['updated_at'], 'd M Y'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <a href="edit.php?id=<?php echo $admissionId; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit Information
                                </a>
                                
                                <?php if ($admission['status'] == STATUS_PENDING): ?>
                                    <a href="verify.php?id=<?php echo $admissionId; ?>" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Verify Admission
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($admission['status'] == STATUS_ACTIVE): ?>
                                    <button onclick="alert('Email sent to student!')" class="btn btn-info btn-sm">
                                        <i class="fas fa-envelope"></i> Send Email
                                    </button>
                                    
                                    <a href="../students/view.php?id=<?php echo $admissionId; ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-user-graduate"></i> View as Student
                                    </a>
                                <?php endif; ?>
                                
                                <a href="delete.php?id=<?php echo $admissionId; ?>" class="btn btn-danger btn-sm btn-delete">
                                    <i class="fas fa-trash"></i> Delete Admission
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .admin-sidebar,
    .navbar,
    .page-header .d-flex,
    .btn,
    .card:last-child {
        display: none !important;
    }
    
    .admin-content {
        margin-left: 0 !important;
        width: 100% !important;
    }
}
</style>

<?php require_once '../../../includes/footer.php'; ?>