<?php
/**
 * Teacher Dashboard
 * CampusX - College Management System
 */

require_once (__DIR__ .'/../../config/config.php');
require_once __DIR__ .'/../../config/constants.php';
require_once __DIR__ .'/../../core/Session.php';
require_once __DIR__ .'/../../core/Database.php';
require_once __DIR__ .'/../../includes/functions.php';

Session::requireRole(ROLE_TEACHER);

$db = new Database();
$teacherId = $_SESSION['teacher_id'] ?? 0;

// Get teacher info
$teacher = $db->getOne('teachers', 'user_id = ?', [$_SESSION['user_id']], 'i');
$teacherId = $teacher['id'];

// Get assigned courses
$courses = $db->select(
    "SELECT c.*, COUNT(DISTINCT e.id) as student_count
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    WHERE c.teacher_id = ?
    GROUP BY c.id
    ORDER BY c.course_name",
    [$teacherId],
    'i'
);

// Get pending assignments to evaluate
$pendingAssignments = $db->select(
    "SELECT asn.*, c.course_name, s.first_name, s.last_name, s.roll_number
    FROM assignment_submissions asn
    JOIN assignments a ON asn.assignment_id = a.id
    JOIN courses c ON a.course_id = c.id
    JOIN students s ON asn.student_id = s.id
    WHERE c.teacher_id = ? AND asn.status = 'submitted'
    ORDER BY asn.created_at DESC
    LIMIT 5",
    [$teacherId],
    'i'
);

// Get attendance statistics
$attendanceStats = $db->select(
    "SELECT c.course_name, COUNT(a.id) as total_classes, 
    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_count
    FROM courses c
    LEFT JOIN attendance a ON c.id = a.course_id
    WHERE c.teacher_id = ?
    GROUP BY c.id
    ORDER BY c.course_name",
    [$teacherId],
    'i'
);

// Get total statistics
$totalCourses = count($courses);
$totalStudents = 0;
foreach ($courses as $course) {
    $totalStudents += $course['student_count'];
}

$totalAssignments = $db->select(
    "SELECT COUNT(a.id) as count FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE c.teacher_id = ?",
    [$teacherId],
    'i'
)[0]['count'] ?? 0;

$pageTitle = "Teacher Dashboard";
$additionalCSS = ['teacher.css'];
require_once '../../../includes/header.php';
?>

<div class="teacher-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="teacher-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="teacher-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-tachometer-alt"></i> Welcome, <?php echo htmlspecialchars($teacher['first_name']); ?></h1>
                <p class="text-muted">Here's your teaching dashboard and recent activity</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-book"></i></div>
                        <div class="stat-card-title">Courses Teaching</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalCourses; ?></div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-card-title">Total Students</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalStudents; ?></div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-tasks"></i></div>
                        <div class="stat-card-title">Assignments</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalAssignments; ?></div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-card-title">Pending Evaluations</div>
                    </div>
                    <div class="stat-card-value"><?php echo count($pendingAssignments); ?></div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-lightning-bolt"></i> Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="attendance/take-attendance.php" class="quick-action-btn">
                            <i class="fas fa-check-circle"></i>
                            <div>Take Attendance</div>
                        </a>
                        <a href="assignments/create.php" class="quick-action-btn">
                            <i class="fas fa-plus-circle"></i>
                            <div>Create Assignment</div>
                        </a>
                        <a href="assignments/evaluate.php" class="quick-action-btn">
                            <i class="fas fa-star"></i>
                            <div>Evaluate Submissions</div>
                        </a>
                        <a href="examinations/grade-entry.php" class="quick-action-btn">
                            <i class="fas fa-graduation-cap"></i>
                            <div>Enter Grades</div>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Pending Assignments -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-inbox"></i> Pending Evaluations</h3>
                            <a href="assignments/evaluate.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pendingAssignments)): ?>
                                <div class="text-center text-muted" style="padding: 2rem;">
                                    <i class="fas fa-check-circle" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <p style="margin-top: 1rem;">All assignments evaluated!</p>
                                </div>
                            <?php else: ?>
                                <div style="max-height: 400px; overflow-y: auto;">
                                    <?php foreach ($pendingAssignments as $assignment): ?>
                                        <div class="list-item" style="padding: 1rem; border-bottom: 1px solid #ecf0f1;">
                                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($assignment['course_name']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></small>
                                                    <br><small class="text-muted"><?php echo $assignment['roll_number']; ?></small>
                                                </div>
                                                <span class="badge badge-warning">Pending</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Courses Overview -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-book"></i> Your Courses</h3>
                            <a href="courses/my-courses.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($courses)): ?>
                                <div class="text-center text-muted" style="padding: 2rem;">
                                    <i class="fas fa-book" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <p style="margin-top: 1rem;">No courses assigned yet</p>
                                </div>
                            <?php else: ?>
                                <div style="max-height: 400px; overflow-y: auto;">
                                    <?php foreach ($courses as $course): ?>
                                        <div class="list-item" style="padding: 1rem; border-bottom: 1px solid #ecf0f1;">
                                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($course['course_name']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($course['course_code']); ?></small>
                                                    <br><small class="text-muted"><?php echo $course['student_count']; ?> students</small>
                                                </div>
                                                <span class="badge badge-info"><?php echo $course['credits']; ?> Credits</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Attendance Overview -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-check"></i> Attendance Overview</h3>
                </div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Total Classes</th>
                                    <th>Classes Recorded</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($attendanceStats)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No attendance records yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($attendanceStats as $stat): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($stat['course_name']); ?></strong></td>
                                            <td><?php echo intval($stat['total_classes'] ?? 0); ?></td>
                                            <td><?php echo intval($stat['present_count'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    font-weight: 600;
}

.quick-action-btn:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    color: white;
    text-decoration: none;
}

.quick-action-btn i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

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

.list-item {
    transition: background 0.2s;
}

.list-item:hover {
    background: #f8f9fa;
}
</style>

<?php require_once '../../../includes/footer.php'; ?>