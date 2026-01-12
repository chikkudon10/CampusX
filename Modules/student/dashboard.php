<?php
/**
 * Student Dashboard
 * CampusX - College Management System
 */

require_once __DIR__.'/../../config/config.php';
require_once __DIR__.'/../../config/constants.php';
require_once __DIR__.'/../../core/Session.php';
require_once __DIR__.'/../../core/Database.php';
require_once __DIR__. '/../../includes/functions.php';

Session::requireRole(ROLE_STUDENT);

$db = new Database();

// Get student info
$student = $db->getOne('students', 'user_id = ?', [$_SESSION['user_id']], 'i');
if (!$student) {
    $_SESSION['error_message'] = 'Student profile not found';
    header('Location: ../../../login.php');
    exit();
}
$studentId = $student['id'];

// Get enrolled courses
$enrolledCourses = $db->select(
    "SELECT c.*, t.first_name as teacher_first_name, t.last_name as teacher_last_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN teachers t ON c.teacher_id = t.id
    WHERE e.student_id = ?
    ORDER BY c.course_name",
    [$studentId],
    'i'
);

// Get pending assignments
$pendingAssignments = $db->select(
    "SELECT a.*, c.course_code, c.course_name,
    CASE WHEN a.due_date < NOW() THEN 'overdue' ELSE 'pending' END as status,
    asn.id as submission_id,
    asn.status as submission_status
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN assignment_submissions asn ON a.id = asn.assignment_id AND asn.student_id = ?
    WHERE e.student_id = ? AND (asn.id IS NULL OR asn.status = 'submitted')
    ORDER BY a.due_date ASC
    LIMIT 5",
    [$studentId, $studentId],
    'ii'
);

// Get upcoming exams
$upcomingExams = $db->select(
    "SELECT ex.*, c.course_code, c.course_name, t.first_name as teacher_first_name, t.last_name as teacher_last_name
    FROM exams ex
    JOIN courses c ON ex.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    JOIN teachers t ON c.teacher_id = t.id
    WHERE e.student_id = ? AND ex.exam_date > NOW()
    ORDER BY ex.exam_date ASC
    LIMIT 5",
    [$studentId],
    'i'
);

// Get attendance records
$attendanceStats = $db->select(
    "SELECT c.id, c.course_code, c.course_name,
    COUNT(DISTINCT a.id) as total_classes,
    SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_count,
    SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
    ROUND((SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) / COUNT(DISTINCT a.id)) * 100, 2) as attendance_percentage
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN attendance a ON c.id = a.course_id AND a.student_id = ?
    WHERE e.student_id = ?
    GROUP BY c.id
    ORDER BY c.course_name",
    [$studentId, $studentId],
    'ii'
);

// Get grades
$grades = $db->select(
    "SELECT r.*, c.course_code, c.course_name,
    ROUND((r.marks_obtained / r.total_marks) * 100, 2) as percentage
    FROM results r
    JOIN courses c ON r.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = ? AND r.student_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10",
    [$studentId, $studentId],
    'ii'
);

// Calculate overall GPA and statistics
$totalCourses = count($enrolledCourses);
$totalAssignments = $db->select(
    "SELECT COUNT(a.id) as count FROM assignments a
    JOIN enrollments e ON a.course_id = e.course_id
    WHERE e.student_id = ?",
    [$studentId],
    'i'
)[0]['count'] ?? 0;

$submittedAssignments = $db->select(
    "SELECT COUNT(DISTINCT asn.id) as count FROM assignment_submissions asn
    JOIN assignments a ON asn.assignment_id = a.id
    JOIN enrollments e ON a.course_id = e.course_id
    WHERE e.student_id = ? AND asn.student_id = ?",
    [$studentId, $studentId],
    'ii'
)[0]['count'] ?? 0;

$averageGrade = 0;
if (!empty($grades)) {
    $totalPercentage = 0;
    foreach ($grades as $grade) {
        $totalPercentage += $grade['percentage'];
    }
    $averageGrade = round($totalPercentage / count($grades), 2);
}

$pageTitle = "Student Dashboard";
$additionalCSS = ['student.css'];
require_once '../../../includes/header.php';
?>

<div class="student-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="student-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="student-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-tachometer-alt"></i> Welcome, <?php echo htmlspecialchars($student['first_name']); ?></h1>
                <p class="text-muted">Semester <?php echo $student['semester']; ?> | Roll No: <?php echo htmlspecialchars($student['roll_number']); ?></p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-book"></i></div>
                        <div class="stat-card-title">Enrolled Courses</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalCourses; ?></div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-card-title">Assignments</div>
                    </div>
                    <div class="stat-card-value"><?php echo $submittedAssignments . '/' . $totalAssignments; ?></div>
                    <small class="stat-card-footer">Submitted/Total</small>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-star"></i></div>
                        <div class="stat-card-title">Average Grade</div>
                    </div>
                    <div class="stat-card-value"><?php echo $averageGrade . '%'; ?></div>
                </div>
                
                <div class="stat-card purple">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-card-title">Grades Received</div>
                    </div>
                    <div class="stat-card-value"><?php echo count($grades); ?></div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-lightning-bolt"></i> Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions-grid">
                        <a href="courses/my-courses.php" class="quick-action-btn">
                            <i class="fas fa-book"></i>
                            <div>My Courses</div>
                        </a>
                        <a href="assignments/index.php" class="quick-action-btn">
                            <i class="fas fa-tasks"></i>
                            <div>Assignments</div>
                        </a>
                        <a href="results/view.php" class="quick-action-btn">
                            <i class="fas fa-chart-line"></i>
                            <div>Results</div>
                        </a>
                        <a href="attendance/view.php" class="quick-action-btn">
                            <i class="fas fa-calendar-check"></i>
                            <div>Attendance</div>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Pending Assignments -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-hourglass-half"></i> Pending Assignments</h3>
                            <a href="assignments/index.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pendingAssignments)): ?>
                                <div class="text-center text-muted" style="padding: 2rem;">
                                    <i class="fas fa-check-circle" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <p style="margin-top: 1rem;">No pending assignments!</p>
                                </div>
                            <?php else: ?>
                                <div style="max-height: 400px; overflow-y: auto;">
                                    <?php foreach ($pendingAssignments as $assignment): 
                                        $daysLeft = ceil((strtotime($assignment['due_date']) - time()) / (60 * 60 * 24));
                                        $statusClass = $assignment['status'] === 'overdue' ? 'danger' : 'warning';
                                    ?>
                                        <div class="assignment-item" style="padding: 1rem; border-bottom: 1px solid #ecf0f1;">
                                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($assignment['course_code']); ?></small>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar"></i>
                                                        Due: <?php echo date('Y-m-d H:i', strtotime($assignment['due_date'])); ?>
                                                        <?php if ($daysLeft < 0): ?>
                                                            <span style="color: #e74c3c;">(Overdue by <?php echo abs($daysLeft); ?> days)</span>
                                                        <?php elseif ($daysLeft === 0): ?>
                                                            <span style="color: #f39c12;">(Due today)</span>
                                                        <?php else: ?>
                                                            <span style="color: #2ecc71;">(<?php echo $daysLeft; ?> days left)</span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <?php if ($assignment['submission_status'] === 'submitted'): ?>
                                                    <span class="badge badge-success">Submitted</span>
                                                <?php else: ?>
                                                    <span class="badge badge-<?php echo $statusClass; ?>">Pending</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!$assignment['submission_id']): ?>
                                                <a href="assignments/submit.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-primary" style="margin-top: 0.5rem;">
                                                    <i class="fas fa-upload"></i> Submit Now
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Exams -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-file-alt"></i> Upcoming Exams</h3>
                            <a href="examinations/index.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($upcomingExams)): ?>
                                <div class="text-center text-muted" style="padding: 2rem;">
                                    <i class="fas fa-calendar-check" style="font-size: 2rem; opacity: 0.5;"></i>
                                    <p style="margin-top: 1rem;">No upcoming exams</p>
                                </div>
                            <?php else: ?>
                                <div style="max-height: 400px; overflow-y: auto;">
                                    <?php foreach ($upcomingExams as $exam): 
                                        $daysLeft = ceil((strtotime($exam['exam_date']) - time()) / (60 * 60 * 24));
                                    ?>
                                        <div class="exam-item" style="padding: 1rem; border-bottom: 1px solid #ecf0f1;">
                                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($exam['exam_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($exam['course_code']); ?></small>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($exam['teacher_first_name'] . ' ' . $exam['teacher_last_name']); ?>
                                                    </small>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar"></i>
                                                        <?php echo date('Y-m-d H:i', strtotime($exam['exam_date'])); ?>
                                                        <span style="color: #2ecc71;">(<?php echo $daysLeft; ?> days)</span>
                                                    </small>
                                                </div>
                                                <span class="badge badge-info"><?php echo $exam['total_marks']; ?> marks</span>
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
                    <a href="attendance/view.php" class="btn btn-sm btn-primary">View Details</a>
                </div>
                <div class="card-body">
                    <?php if (empty($attendanceStats)): ?>
                        <div class="text-center text-muted" style="padding: 2rem;">
                            <p>No attendance records yet</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table table-sm">
                                <thead style="background: #f8f9fa;">
                                    <tr>
                                        <th>Course</th>
                                        <th>Classes</th>
                                        <th>Present</th>
                                        <th>Late</th>
                                        <th>Absent</th>
                                        <th>Attendance %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendanceStats as $stat): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($stat['course_code']); ?></strong></td>
                                            <td><?php echo $stat['total_classes'] ?? 0; ?></td>
                                            <td><span class="badge badge-success"><?php echo $stat['present_count'] ?? 0; ?></span></td>
                                            <td><span class="badge badge-warning"><?php echo $stat['late_count'] ?? 0; ?></span></td>
                                            <td><span class="badge badge-danger"><?php echo $stat['absent_count'] ?? 0; ?></span></td>
                                            <td>
                                                <div class="progress" style="height: 20px; min-width: 100px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $stat['attendance_percentage'] ?? 0; ?>%; background: <?php echo ($stat['attendance_percentage'] ?? 0) >= 75 ? '#2ecc71' : '#f39c12'; ?>;"
                                                         aria-valuenow="<?php echo $stat['attendance_percentage'] ?? 0; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <small style="color: white; font-weight: bold;">
                                                            <?php echo ($stat['attendance_percentage'] ?? 0) . '%'; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Grades -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-award"></i> Recent Grades</h3>
                    <a href="results/view.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($grades)): ?>
                        <div class="text-center text-muted" style="padding: 2rem;">
                            <i class="fas fa-inbox" style="font-size: 2rem; opacity: 0.5;"></i>
                            <p style="margin-top: 1rem;">No grades received yet</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table table-sm">
                                <thead style="background: #f8f9fa;">
                                    <tr>
                                        <th>Course</th>
                                        <th>Marks</th>
                                        <th>Percentage</th>
                                        <th>Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grades as $grade): 
                                        $gradeColor = 'info';
                                        if ($grade['grade'] === 'A' || $grade['grade'] === 'A+') $gradeColor = 'success';
                                        elseif ($grade['grade'] === 'B' || $grade['grade'] === 'B+') $gradeColor = 'info';
                                        elseif ($grade['grade'] === 'C' || $grade['grade'] === 'C+') $gradeColor = 'warning';
                                        elseif ($grade['grade'] === 'F') $gradeColor = 'danger';
                                    ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($grade['course_code']); ?></strong></td>
                                            <td><?php echo $grade['marks_obtained']; ?> / <?php echo $grade['total_marks']; ?></td>
                                            <td><?php echo $grade['percentage']; ?>%</td>
                                            <td><span class="badge badge-<?php echo $gradeColor; ?>"><?php echo $grade['grade']; ?></span></td>
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

.stat-card.purple {
    border-left-color: #9b59b6;
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

.stat-card-footer {
    font-size: 0.75rem;
    color: #95a5a6;
    margin-top: 0.5rem;
}

.assignment-item:hover,
.exam-item:hover {
    background: #f8f9fa;
}

.row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.col-md-6 {
    min-width: 0;
}

@media (max-width: 768px) {
    .row {
        grid-template-columns: 1fr;
    }
    
    .quick-actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php require_once '../../../includes/footer.php'; ?>