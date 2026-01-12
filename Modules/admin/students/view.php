<?php
/**
 * View Student Details - Admin
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_ADMIN);

$db = new Database();
$studentId = $_GET['id'] ?? 0;

// Get student details
$student = $db->getById('students', $studentId);

if (!$student) {
    setErrorMessage('Student not found');
    redirect('modules/admin/students/');
    exit();
}

// Get enrolled courses
$enrolledCourses = $db->select(
    "SELECT c.*, 
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name
     FROM courses c
     JOIN student_courses sc ON c.id = sc.course_id
     LEFT JOIN teachers t ON c.teacher_id = t.id
     WHERE sc.student_id = ?
     ORDER BY c.semester, c.course_code",
    [$studentId],
    'i'
);

// Get attendance statistics
$attendanceStats = $db->selectOne(
    "SELECT 
        COUNT(*) as total_classes,
        SUM(CASE WHEN status = 'P' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'A' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'L' THEN 1 ELSE 0 END) as late
     FROM attendance
     WHERE student_id = ?",
    [$studentId],
    'i'
);

$attendancePercentage = calculateAttendancePercentage(
    $attendanceStats['present'] ?? 0,
    $attendanceStats['total_classes'] ?? 0
);

// Get assignment submissions
$assignments = $db->select(
    "SELECT a.title, a.due_date, 
            asub.submission_date, asub.marks, asub.status,
            c.course_code
     FROM assignments a
     JOIN courses c ON a.course_id = c.id
     LEFT JOIN assignment_submissions asub ON a.id = asub.assignment_id AND asub.student_id = ?
     WHERE c.id IN (SELECT course_id FROM student_courses WHERE student_id = ?)
     ORDER BY a.due_date DESC
     LIMIT 10",
    [$studentId, $studentId],
    'ii'
);

// Get results/grades
$results = $db->select(
    "SELECT r.*, c.course_code, c.course_name, c.credits
     FROM results r
     JOIN courses c ON r.course_id = c.id
     WHERE r.student_id = ?
     ORDER BY r.created_at DESC",
    [$studentId],
    'i'
);

// Calculate GPA
$grades = [];
foreach ($results as $result) {
    if ($result['grade']) $grades[] = $result['grade'];
}
$currentGPA = calculateGPA($grades);

// Get leave applications
$leaveApplications = $db->select(
    "SELECT * FROM leave_applications
     WHERE student_id = ?
     ORDER BY created_at DESC
     LIMIT 5",
    [$studentId],
    'i'
);

$pageTitle = "Student Details";
$additionalCSS = ['admin.css', 'student.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-user-graduate"></i> Student Details</h1>
                <div class="d-flex gap-2">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    <a href="edit.php?id=<?php echo $studentId; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <button onclick="window.print()" class="btn btn-info">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            
            <!-- Student Profile Header -->
            <div class="student-profile-header mb-4">
                <img src="<?php echo ASSETS_PATH; ?>images/profile/default-avatar.png" 
                     alt="Profile" class="student-profile-avatar">
                <div class="student-profile-info">
                    <h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                    <div class="student-profile-meta">
                        <div class="student-profile-meta-item">
                            <span class="meta-label">Roll Number</span>
                            <span class="meta-value"><?php echo htmlspecialchars($student['roll_number']); ?></span>
                        </div>
                        <div class="student-profile-meta-item">
                            <span class="meta-label">Semester</span>
                            <span class="meta-value"><?php echo getSemesterName($student['semester']); ?></span>
                        </div>
                        <div class="student-profile-meta-item">
                            <span class="meta-label">Current GPA</span>
                            <span class="meta-value"><?php echo number_format($currentGPA, 2); ?></span>
                        </div>
                        <div class="student-profile-meta-item">
                            <span class="meta-label">Status</span>
                            <span class="meta-value">
                                <?php if ($student['status'] == STATUS_ACTIVE): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="student-cards-grid mb-4">
                <div class="student-info-card attendance">
                    <div class="info-card-header">
                        <span class="info-card-title">Attendance</span>
                        <i class="info-card-icon fas fa-clipboard-check" style="color: #27ae60;"></i>
                    </div>
                    <div class="info-card-value"><?php echo number_format($attendancePercentage, 1); ?>%</div>
                    <div class="info-card-description">
                        <?php echo $attendanceStats['present'] ?? 0; ?> / <?php echo $attendanceStats['total_classes'] ?? 0; ?> classes
                    </div>
                </div>
                
                <div class="student-info-card">
                    <div class="info-card-header">
                        <span class="info-card-title">Enrolled Courses</span>
                        <i class="info-card-icon fas fa-book"></i>
                    </div>
                    <div class="info-card-value"><?php echo count($enrolledCourses); ?></div>
                    <div class="info-card-description">Active courses</div>
                </div>
                
                <div class="student-info-card assignments">
                    <div class="info-card-header">
                        <span class="info-card-title">Assignments</span>
                        <i class="info-card-icon fas fa-tasks" style="color: #f39c12;"></i>
                    </div>
                    <div class="info-card-value"><?php echo count($assignments); ?></div>
                    <div class="info-card-description">Total assignments</div>
                </div>
                
                <div class="student-info-card results">
                    <div class="info-card-header">
                        <span class="info-card-title">Results</span>
                        <i class="info-card-icon fas fa-trophy" style="color: #e74c3c;"></i>
                    </div>
                    <div class="info-card-value"><?php echo count($results); ?></div>
                    <div class="info-card-description">Graded courses</div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
                <!-- Main Content -->
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
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                </tr>
                                <tr>
                                    <th>Phone:</th>
                                    <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Date of Birth:</th>
                                    <td>
                                        <?php 
                                        if ($student['date_of_birth']) {
                                            echo formatDate($student['date_of_birth']);
                                            echo ' (' . calculateAge($student['date_of_birth']) . ' years)';
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
                                        echo $genders[$student['gender']] ?? 'N/A';
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Blood Group:</th>
                                    <td><?php echo htmlspecialchars($student['blood_group'] ?? 'Not specified'); ?></td>
                                </tr>
                                <tr>
                                    <th>Address:</th>
                                    <td><?php echo nl2br(htmlspecialchars($student['address'] ?? 'N/A')); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Enrolled Courses -->
                    <div class="card mb-4">
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
                                            <th>Code</th>
                                            <th>Course Name</th>
                                            <th>Credits</th>
                                            <th>Teacher</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($enrolledCourses as $course): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                                <td><?php echo $course['credits']; ?></td>
                                                <td>
                                                    <?php if ($course['teacher_name']): ?>
                                                        <span class="badge badge-success">
                                                            <?php echo htmlspecialchars($course['teacher_name']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Not assigned</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Academic Performance -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-line"></i> Academic Performance</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($results)): ?>
                                <p class="text-center" style="color: #7f8c8d; padding: 2rem;">
                                    <i class="fas fa-info-circle"></i> No results available yet
                                </p>
                            <?php else: ?>
                                <div class="mb-3" style="text-align: right;">
                                    <div class="gpa-display">
                                        <div class="gpa-label">Overall GPA</div>
                                        <div class="gpa-value"><?php echo number_format($currentGPA, 2); ?></div>
                                    </div>
                                </div>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Marks</th>
                                            <th>Total</th>
                                            <th>Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $result): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($result['course_code']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($result['course_name']); ?></small>
                                                </td>
                                                <td><?php echo $result['marks_obtained']; ?></td>
                                                <td><?php echo $result['total_marks']; ?></td>
                                                <td>
                                                    <span class="grade-badge <?php echo substr($result['grade'], 0, 1); ?>">
                                                        <?php echo $result['grade']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Assignments -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-tasks"></i> Recent Assignments</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($assignments)): ?>
                                <p class="text-center" style="color: #7f8c8d; padding: 2rem;">
                                    <i class="fas fa-info-circle"></i> No assignments yet
                                </p>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Assignment</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assignments as $assignment): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($assignment['course_code']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                                <td><?php echo formatDate($assignment['due_date']); ?></td>
                                                <td>
                                                    <?php if ($assignment['status'] == 'graded'): ?>
                                                        <span class="badge badge-success">Graded (<?php echo $assignment['marks']; ?>)</span>
                                                    <?php elseif ($assignment['status'] == 'submitted'): ?>
                                                        <span class="badge badge-info">Submitted</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div>
                    <!-- Academic Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-graduation-cap"></i> Academic Info</h3>
                        </div>
                        <div class="card-body">
                            <table style="width: 100%;">
                                <tr>
                                    <td style="padding: 0.5rem 0;"><strong>Roll Number:</strong></td>
                                    <td style="text-align: right;"><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 0;"><strong>Semester:</strong></td>
                                    <td style="text-align: right;"><?php echo getSemesterName($student['semester']); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 0;"><strong>Admission Date:</strong></td>
                                    <td style="text-align: right;">
                                        <?php echo $student['admission_date'] ? formatDate($student['admission_date'], 'd M Y') : 'N/A'; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 0.5rem 0;"><strong>Academic Year:</strong></td>
                                    <td style="text-align: right;">
                                        <?php echo getAcademicYear($student['admission_date']); ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Attendance Summary -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-clipboard-check"></i> Attendance</h3>
                        </div>
                        <div class="card-body">
                            <div class="attendance-progress mb-3">
                                <div class="progress-label">
                                    <span>Overall Attendance</span>
                                    <strong><?php echo number_format($attendancePercentage, 1); ?>%</strong>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar" style="width: <?php echo $attendancePercentage; ?>%;">
                                        <?php echo number_format($attendancePercentage, 0); ?>%
                                    </div>
                                </div>
                            </div>
                            
                            <div class="attendance-details" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <div class="attendance-detail-item">
                                    <div class="detail-value present"><?php echo $attendanceStats['present'] ?? 0; ?></div>
                                    <div class="detail-label">Present</div>
                                </div>
                                <div class="attendance-detail-item">
                                    <div class="detail-value absent"><?php echo $attendanceStats['absent'] ?? 0; ?></div>
                                    <div class="detail-label">Absent</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Leave Applications -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-calendar-times"></i> Leave History</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($leaveApplications)): ?>
                                <p class="text-center" style="color: #7f8c8d;">No leave applications</p>
                            <?php else: ?>
                                <?php foreach ($leaveApplications as $leave): ?>
                                    <div class="leave-item">
                                        <div>
                                            <strong><?php echo ucfirst($leave['leave_type']); ?></strong>
                                            <div class="leave-dates">
                                                <?php echo formatDate($leave['start_date'], 'd M'); ?> - 
                                                <?php echo formatDate($leave['end_date'], 'd M Y'); ?>
                                            </div>
                                        </div>
                                        <span class="status-badge <?php echo $leave['status']; ?>">
                                            <?php echo ucfirst($leave['status']); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                <a href="edit.php?id=<?php echo $studentId; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Edit Information
                                </a>
                                <button onclick="alert('Feature coming soon!')" class="btn btn-info btn-sm">
                                    <i class="fas fa-envelope"></i> Send Email
                                </button>
                                <button onclick="alert('Feature coming soon!')" class="btn btn-success btn-sm">
                                    <i class="fas fa-user-plus"></i> Enroll in Course
                                </button>
                                <a href="delete.php?id=<?php echo $studentId; ?>" class="btn btn-danger btn-sm btn-delete">
                                    <i class="fas fa-trash"></i> Delete Student
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