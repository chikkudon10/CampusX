?>
<?php
/**
 * View Attendance - Student
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_STUDENT);

$db = new Database();

$student = $db->getOne('students', 'user_id = ?', [$_SESSION['user_id']], 'i');
if (!$student) {
    header('Location: ../dashboard.php');
    exit();
}
$studentId = $student['id'];

// Get filters
$courseId = $_GET['course_id'] ?? '';
$month = $_GET['month'] ?? date('Y-m');

// Get courses
$courses = $db->select(
    "SELECT c.id, c.course_code, c.course_name FROM enrollments e
    JOIN courses c ON e.course_id = c.id WHERE e.student_id = ? ORDER BY c.course_name",
    [$studentId], 'i'
);

// Build query
$whereClause = "a.student_id = ? AND DATE_FORMAT(a.attendance_date, '%Y-%m') = ?";
$params = [$studentId, $month];
$types = 'is';

if ($courseId) {
    $whereClause .= " AND c.id = ?";
    $params[] = $courseId;
    $types .= 'i';
}

// Get attendance
$attendance = $db->select(
    "SELECT a.*, c.course_code, c.course_name FROM attendance a
    JOIN courses c ON a.course_id = c.id WHERE $whereClause
    ORDER BY a.attendance_date DESC",
    $params, $types
);

// Calculate stats
$totalClasses = count($attendance);
$presentCount = 0;
$lateCount = 0;
$absentCount = 0;

foreach ($attendance as $record) {
    if ($record['status'] === 'Present') $presentCount++;
    elseif ($record['status'] === 'Late') $lateCount++;
    elseif ($record['status'] === 'Absent') $absentCount++;
}

$attendancePercentage = $totalClasses > 0 ? round(($presentCount / $totalClasses) * 100, 2) : 0;

$pageTitle = "Attendance";
require_once '../../../includes/header.php';
?>

<div class="student-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    <div class="student-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="student-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-calendar-check"></i> My Attendance</h1>
                <a href="../dashboard.php" class="btn btn-secondary">Back</a>
            </div>
            
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-value"><?php echo $totalClasses; ?></div>
                    <div class="stat-card-title">Total Classes</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-card-value"><?php echo $presentCount; ?></div>
                    <div class="stat-card-title">Present</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-card-value"><?php echo $lateCount; ?></div>
                    <div class="stat-card-title">Late</div>
                </div>
                <div class="stat-card red">
                    <div class="stat-card-value"><?php echo $absentCount; ?></div>
                    <div class="stat-card-title">Absent</div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="form-row">
                        <div class="form-group col-md-4">
                            <label>Course</label>
                            <select name="course_id" class="form-control">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $courseId == $c['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['course_code']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Month</label>
                            <input type="month" name="month" class="form-control" value="<?php echo $month; ?>">
                        </div>
                        <div class="form-group col-md-2" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <?php if (empty($attendance)): ?>
                        <div class="text-center text-muted" style="padding: 3rem;">
                            <i class="fas fa-inbox" style="font-size: 3rem; opacity: 0.5;"></i>
                            <p style="margin-top: 1rem;">No attendance records found</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th>Date</th>
                                    <th>Course</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance as $record): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i', strtotime($record['attendance_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($record['course_code']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo 
                                                $record['status'] === 'Present' ? 'success' : 
                                                ($record['status'] === 'Late' ? 'warning' : 'danger');
                                            ?>">
                                                <?php echo $record['status']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
