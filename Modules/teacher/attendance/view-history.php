<?php
/**
 * Attendance History - Teacher
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

// Get filter parameters
$courseId = $_GET['course_id'] ?? '';
$studentId = $_GET['student_id'] ?? '';
$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';

// Get teacher's courses
$courses = $db->select(
    "SELECT c.id, c.course_code, c.course_name
    FROM courses c
    WHERE c.teacher_id = ?
    ORDER BY c.course_name",
    [$teacherId],
    'i'
);

// Get students from teacher's courses
$students = $db->select(
    "SELECT DISTINCT s.id, s.roll_number, s.first_name, s.last_name
    FROM students s
    JOIN enrollments e ON s.id = e.student_id
    JOIN courses c ON e.course_id = c.id
    WHERE c.teacher_id = ? AND s.deleted_at IS NULL
    ORDER BY s.roll_number",
    [$teacherId],
    'i'
);

// Build attendance query
$whereClause = "c.teacher_id = ?";
$params = [$teacherId];
$types = 'i';

if (!empty($courseId)) {
    $whereClause .= " AND a.course_id = ?";
    $params[] = $courseId;
    $types .= 'i';
}

if (!empty($studentId)) {
    $whereClause .= " AND a.student_id = ?";
    $params[] = $studentId;
    $types .= 'i';
}

if (!empty($fromDate)) {
    $whereClause .= " AND DATE(a.attendance_date) >= ?";
    $params[] = $fromDate;
    $types .= 's';
}

if (!empty($toDate)) {
    $whereClause .= " AND DATE(a.attendance_date) <= ?";
    $params[] = $toDate;
    $types .= 's';
}

if (!empty($status)) {
    $whereClause .= " AND a.status = ?";
    $params[] = $status;
    $types .= 's';
}

// Get attendance records
$attendance = $db->select(
    "SELECT a.id, a.attendance_date, a.status, 
    c.course_name, c.course_code,
    s.roll_number, s.first_name, s.last_name
    FROM attendance a
    JOIN courses c ON a.course_id = c.id
    JOIN students s ON a.student_id = s.id
    WHERE $whereClause
    ORDER BY a.attendance_date DESC, s.roll_number",
    $params,
    $types
);

// Calculate statistics
$totalRecords = count($attendance);
$presentCount = 0;
$absentCount = 0;
$lateCount = 0;

foreach ($attendance as $record) {
    if ($record['status'] === 'Present') $presentCount++;
    elseif ($record['status'] === 'Absent') $absentCount++;
    elseif ($record['status'] === 'Late') $lateCount++;
}

$pageTitle = "Attendance History";
$additionalCSS = ['teacher.css'];
require_once '../../../includes/header.php';
?>

<div class="teacher-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="teacher-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="teacher-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-history"></i> Attendance History</h1>
                <div class="d-flex gap-2">
                    <a href="take-attendance.php" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Take Attendance
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter"></i> Filters</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="needs-validation">
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label for="course_id" class="form-label">Course</label>
                                <select id="course_id" name="course_id" class="form-control">
                                    <option value="">All Courses</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" <?php echo $courseId == $course['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group col-md-3">
                                <label for="student_id" class="form-label">Student</label>
                                <select id="student_id" name="student_id" class="form-control">
                                    <option value="">All Students</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>" <?php echo $studentId == $student['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($student['roll_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group col-md-2">
                                <label for="from_date" class="form-label">From Date</label>
                                <input type="date" id="from_date" name="from_date" class="form-control" value="<?php echo $fromDate; ?>">
                            </div>
                            
                            <div class="form-group col-md-2">
                                <label for="to_date" class="form-label">To Date</label>
                                <input type="date" id="to_date" name="to_date" class="form-control" value="<?php echo $toDate; ?>">
                            </div>
                            
                            <div class="form-group col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="Present" <?php echo $status === 'Present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="Late" <?php echo $status === 'Late' ? 'selected' : ''; ?>>Late</option>
                                    <option value="Absent" <?php echo $status === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <a href="view-history.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                            <button type="button" onclick="window.print()" class="btn btn-info">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button type="button" onclick="exportToCSV()" class="btn btn-success">
                                <i class="fas fa-download"></i> Export CSV
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-list"></i></div>
                        <div class="stat-card-title">Total Records</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalRecords; ?></div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-card-title">Present</div>
                    </div>
                    <div class="stat-card-value"><?php echo $presentCount; ?></div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-card-title">Late</div>
                    </div>
                    <div class="stat-card-value"><?php echo $lateCount; ?></div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-card-title">Absent</div>
                    </div>
                    <div class="stat-card-value"><?php echo $absentCount; ?></div>
                </div>
            </div>
            
            <!-- Attendance Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-table"></i> Attendance Records</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($attendance)): ?>
                        <div class="text-center text-muted" style="padding: 3rem;">
                            <i class="fas fa-inbox" style="font-size: 4rem; opacity: 0.5;"></i>
                            <h3 style="margin-top: 1rem;">No attendance records found</h3>
                            <p>Try adjusting your filters</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table table-striped table-hover" id="attendanceTable">
                                <thead style="background: #f8f9fa;">
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Course</th>
                                        <th>Roll No.</th>
                                        <th>Student Name</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance as $record): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('Y-m-d', strtotime($record['attendance_date'])); ?></strong>
                                            </td>
                                            <td>
                                                <small><?php echo date('H:i:s', strtotime($record['attendance_date'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-info"><?php echo htmlspecialchars($record['course_code']); ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($record['roll_number']); ?></strong>
                                            </td>
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

@media print {
    .page-header,
    .card:first-child,
    .btn {
        display: none !important;
    }
}
</style>

<script>
function exportToCSV() {
    const table = document.getElementById('attendanceTable');
    let csv = [];
    
    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent.trim());
    });
    csv.push(headers.join(','));
    
    // Get rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push('"' + td.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(row.join(','));
    });
    
    // Download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'attendance-history-' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
}
</script>

<?php require_once '../../../includes/footer.php'; ?>