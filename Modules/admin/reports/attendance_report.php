<?php
/**
 * Attendance Report - Admin
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_ADMIN);

$db = new Database();

// Get filters
$courseId = $_GET['course_id'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Get all courses for filter
$courses = $db->getAll('courses', '', [], '', '', 'course_name');

// Build query based on filters
$whereClause = 'a.attendance_date BETWEEN ? AND ?';
$params = [$startDate, $endDate];
$types = 'ss';

if ($courseId) {
    $whereClause .= ' AND a.course_id = ?';
    $params[] = $courseId;
    $types .= 'i';
}

// Get attendance data
$attendanceData = $db->select(
    "SELECT 
        s.roll_number,
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        c.course_code,
        c.course_name,
        COUNT(*) as total_classes,
        SUM(CASE WHEN a.status = 'P' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN a.status = 'A' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN a.status = 'L' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN a.status = 'E' THEN 1 ELSE 0 END) as excused
     FROM attendance a
     JOIN students s ON a.student_id = s.id
     JOIN courses c ON a.course_id = c.id
     WHERE $whereClause
     GROUP BY s.id, c.id
     ORDER BY c.course_code, s.roll_number",
    $params,
    $types
);

$pageTitle = "Attendance Report";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-clipboard-check"></i> Attendance Report</h1>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button onclick="exportTableToCSV('attendanceTable', 'attendance-report.csv')" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Export CSV
                    </button>
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Filters</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-row">
                        <div class="form-group">
                            <label for="course_id" class="form-label">Course</label>
                            <select id="course_id" name="course_id" class="form-control">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>" 
                                            <?php echo $courseId == $course['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" 
                                   value="<?php echo $startDate; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" 
                                   value="<?php echo $endDate; ?>">
                        </div>
                        
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Report -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        Attendance Report 
                        (<?php echo formatDate($startDate); ?> to <?php echo formatDate($endDate); ?>)
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (empty($attendanceData)): ?>
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-inbox" style="font-size: 4rem; color: #ddd;"></i>
                            <h3 style="color: #7f8c8d; margin-top: 1rem;">No attendance data found</h3>
                            <p>Try adjusting the filters</p>
                        </div>
                    <?php else: ?>
                        <table class="table" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th>Roll No.</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Total Classes</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Excused</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendanceData as $data): ?>
                                    <?php 
                                    $percentage = calculateAttendancePercentage(
                                        $data['present'], 
                                        $data['total_classes']
                                    );
                                    $statusColor = $percentage >= 75 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger');
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($data['roll_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($data['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($data['course_code']); ?></td>
                                        <td><?php echo $data['total_classes']; ?></td>
                                        <td><span class="badge badge-success"><?php echo $data['present']; ?></span></td>
                                        <td><span class="badge badge-danger"><?php echo $data['absent']; ?></span></td>
                                        <td><span class="badge badge-warning"><?php echo $data['late']; ?></span></td>
                                        <td><span class="badge badge-info"><?php echo $data['excused']; ?></span></td>
                                        <td>
                                            <span class="badge badge-<?php echo $statusColor; ?>">
                                                <?php echo number_format($percentage, 1); ?>%
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

<style>
@media print {
    .admin-sidebar,
    .navbar,
    .page-header .d-flex,
    .card:first-child,
    .btn {
        display: none !important;
    }
    
    .admin-content {
        margin-left: 0 !important;
        width: 100% !important;
    }
}
</style>

<?php require_once '../../../includes/footer.php'; ?>