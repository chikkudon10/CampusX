<?php
/**
 * Attendance Management - Teacher
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_TEACHER);

$db = new Database();

// Get teacher info
$teacher = $db->getOne('teachers', 'user_id = ?', [$_SESSION['user_id']], 'i');
if (!$teacher) {
    $_SESSION['error_message'] = 'Teacher profile not found';
    header('Location: ../dashboard.php');
    exit();
}
$teacherId = $teacher['id'];

// Get teacher's courses
$courses = $db->select(
    "SELECT c.id, c.course_code, c.course_name, c.semester,
    COUNT(DISTINCT e.id) as enrolled_students
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    WHERE c.teacher_id = ?
    GROUP BY c.id
    ORDER BY c.course_name",
    [$teacherId],
    'i'
);

// Get attendance statistics
$stats = $db->select(
    "SELECT 
        c.id,
        c.course_name,
        COUNT(DISTINCT a.id) as total_attendance_records,
        COUNT(DISTINCT DATE(a.attendance_date)) as total_classes_recorded,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_count
    FROM courses c
    LEFT JOIN attendance a ON c.id = a.course_id
    WHERE c.teacher_id = ?
    GROUP BY c.id
    ORDER BY c.course_name",
    [$teacherId],
    'i'
);

// Get recent attendance entries
$recentAttendance = $db->select(
    "SELECT a.*, c.course_name, c.course_code, s.roll_number, 
    s.first_name, s.last_name
    FROM attendance a
    JOIN courses c ON a.course_id = c.id
    JOIN students s ON a.student_id = s.id
    WHERE c.teacher_id = ?
    ORDER BY a.attendance_date DESC, a.created_at DESC
    LIMIT 20",
    [$teacherId],
    'i'
);

// Calculate total statistics
$totalClasses = 0;
$totalPresent = 0;
$totalAbsent = 0;
$totalLate = 0;

foreach ($stats as $stat) {
    $totalClasses += $stat['total_classes_recorded'];
    $totalPresent += $stat['present_count'];
    $totalAbsent += $stat['absent_count'];
    $totalLate += $stat['late_count'];
}

$pageTitle = "Attendance Management";
$additionalCSS = ['teacher.css'];
require_once '../../../includes/header.php';
?>

<div class="teacher-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="teacher-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="teacher-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-calendar-check"></i> Attendance Management</h1>
                <div class="d-flex gap-2">
                    <a href="take-attendance.php" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Take Attendance
                    </a>
                    <a href="view-history.php" class="btn btn-primary">
                        <i class="fas fa-history"></i> View History
                    </a>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-book"></i></div>
                        <div class="stat-card-title">Classes Recorded</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalClasses; ?></div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-card-title">Total Present</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalPresent; ?></div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-card-title">Total Late</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalLate; ?></div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-card-title">Total Absent</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalAbsent; ?></div>
                </div>
            </div>
            
            <!-- Courses Attendance Overview -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Courses Attendance Overview</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($stats)): ?>
                        <div class="text-center text-muted" style="padding: 3rem;">
                            <i class="fas fa-inbox" style="font-size: 4rem; opacity: 0.5;"></i>
                            <p style="margin-top: 1rem;">No courses assigned yet</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Enrolled Students</th>
                                        <th>Classes Recorded</th>
                                        <th>Present</th>
                                        <th>Late</th>
                                        <th>Absent</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    foreach ($stats as $stat):
                                        $enrolled = 0;
                                        foreach ($courses as $course) {
                                            if ($course['id'] == $stat['id']) {
                                                $enrolled = $course['enrolled_students'];
                                                break;
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($stat['course_name']); ?></strong>
                                            </td>
                                            <td><?php echo $enrolled; ?></td>
                                            <td><?php echo $stat['total_classes_recorded'] ?? 0; ?></td>
                                            <td>
                                                <span class="badge badge-success"><?php echo $stat['present_count'] ?? 0; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-warning"><?php echo $stat['late_count'] ?? 0; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-danger"><?php echo $stat['absent_count'] ?? 0; ?></span>
                                            </td>
                                            <td>
                                                <a href="take-attendance.php?course_id=<?php echo $stat['id']; ?>" class="btn btn-sm btn-primary" title="Take Attendance">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                                <a href="view-history.php?course_id=<?php echo $stat['id']; ?>" class="btn btn-sm btn-info" title="View History">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Attendance Records -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history"></i> Recent Attendance Records</h3>
                    <a href="view-history.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentAttendance)): ?>
                        <div class="text-center text-muted" style="padding: 3rem;">
                            <i class="fas fa-inbox" style="font-size: 4rem; opacity: 0.5;"></i>
                            <p style="margin-top: 1rem;">No attendance records yet</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto; max-height: 500px;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Course</th>
                                        <th>Roll No.</th>
                                        <th>Student Name</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentAttendance as $record): ?>
                                        <tr>
                                            <td>
                                                <small><?php echo date('Y-m-d H:i', strtotime($record['attendance_date'])); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['course_code']); ?></td>
                                            <td><strong><?php echo htmlspecialchars($record['roll_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $record['status'] === 'Present' ? 'success' : 
                                                         ($record['status'] === 'Late' ? 'warning' : 'danger');
                                                ?>">
                                                    <?php echo $record['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid;
}

.stat-card.blue {
    border-left-color: #3498db;
}

.stat-card.green {
    border-left-color: #2ecc71;
}

.stat-card.orange {
    border-left-color: #f39c12;
}

.stat-card.red {
    border-left-color: #e74c3c;
}

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-card-icon {
    font-size: 2rem;
    opacity: 0.3;
}

.stat-card-title {
    font-size: 0.85rem;
    color: #7f8c8d;
    text-transform: uppercase;
    font-weight: 600;
}

.stat-card-value {
    font-size: 2.5rem;
    font-weight: bold;
    color: #2c3e50;
}

.d-flex {
    display: flex;
}

.gap-2 {
    gap: 0.5rem;
}
</style>

<?php require_once '../../../includes/footer.php'; ?>
