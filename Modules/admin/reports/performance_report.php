<?php
/**
 * Performance Report - Admin
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
$semester = $_GET['semester'] ?? '';
$studentId = $_GET['student_id'] ?? '';
$sortBy = $_GET['sort_by'] ?? 'avg_marks';

// Build performance query
$whereClause = 's.deleted_at IS NULL';
$params = [];
$types = '';

if ($semester) {
    $whereClause .= ' AND s.semester = ?';
    $params[] = $semester;
    $types .= 'i';
}

if ($studentId) {
    $whereClause .= ' AND s.id = ?';
    $params[] = $studentId;
    $types .= 'i';
}

// Get performance data
$performanceData = $db->select(
    "SELECT 
        s.id,
        s.roll_number,
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        s.email,
        s.semester,
        COUNT(DISTINCT a.id) as total_classes,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_classes,
        ROUND((SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) / COUNT(DISTINCT a.id)) * 100, 2) as attendance_percentage,
        COUNT(DISTINCT r.id) as total_exams,
        ROUND(AVG((r.marks_obtained / r.total_marks) * 100), 2) as avg_percentage,
        MAX((r.marks_obtained / r.total_marks) * 100) as best_score,
        MIN((r.marks_obtained / r.total_marks) * 100) as worst_score,
        COUNT(DISTINCT asn.id) as assignments_submitted,
        ROUND(AVG(asn.score), 2) as avg_assignment_score,
        CASE 
            WHEN ROUND(AVG((r.marks_obtained / r.total_marks) * 100), 2) >= 80 THEN 'Excellent'
            WHEN ROUND(AVG((r.marks_obtained / r.total_marks) * 100), 2) >= 70 THEN 'Good'
            WHEN ROUND(AVG((r.marks_obtained / r.total_marks) * 100), 2) >= 60 THEN 'Average'
            WHEN ROUND(AVG((r.marks_obtained / r.total_marks) * 100), 2) >= 50 THEN 'Below Average'
            ELSE 'Poor'
        END as performance_status
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id
    LEFT JOIN results r ON s.id = r.student_id
    LEFT JOIN assignment_submissions asn ON s.id = asn.student_id
    WHERE $whereClause
    GROUP BY s.id, s.roll_number, s.first_name, s.last_name, s.email, s.semester",
    $params,
    $types
);

// Sort the data
if (!empty($performanceData)) {
    usort($performanceData, function($a, $b) use ($sortBy) {
        switch ($sortBy) {
            case 'attendance':
                return ($b['attendance_percentage'] ?? 0) <=> ($a['attendance_percentage'] ?? 0);
            case 'name':
                return strcmp($a['student_name'], $b['student_name']);
            case 'performance':
                $statusOrder = ['Excellent' => 0, 'Good' => 1, 'Average' => 2, 'Below Average' => 3, 'Poor' => 4];
                $statusA = $statusOrder[$a['performance_status']] ?? 5;
                $statusB = $statusOrder[$b['performance_status']] ?? 5;
                return $statusA <=> $statusB;
            default: // avg_marks
                return ($b['avg_percentage'] ?? 0) <=> ($a['avg_percentage'] ?? 0);
        }
    });
}

// Calculate overall statistics
$totalStudents = count($performanceData);
$avgAttendance = 0;
$avgMarks = 0;
$excellentCount = 0;
$goodCount = 0;
$averageCount = 0;
$belowCount = 0;
$poorCount = 0;

foreach ($performanceData as $perf) {
    $avgAttendance += ($perf['attendance_percentage'] ?? 0);
    $avgMarks += ($perf['avg_percentage'] ?? 0);
    
    switch ($perf['performance_status']) {
        case 'Excellent':
            $excellentCount++;
            break;
        case 'Good':
            $goodCount++;
            break;
        case 'Average':
            $averageCount++;
            break;
        case 'Below Average':
            $belowCount++;
            break;
        case 'Poor':
            $poorCount++;
            break;
    }
}

if ($totalStudents > 0) {
    $avgAttendance = round($avgAttendance / $totalStudents, 2);
    $avgMarks = round($avgMarks / $totalStudents, 2);
}

// Get all students for filter
$allStudents = $db->getAll('students', 'deleted_at IS NULL', [], '', '', 'first_name, last_name');

$pageTitle = "Performance Report";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-chart-line"></i> Performance Report</h1>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button onclick="exportTableToCSV('performanceTable', 'performance-report.csv')" class="btn btn-success">
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
                    <h3 class="card-title">Filters & Options</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-row">
                        <div class="form-group">
                            <label for="semester" class="form-label">Semester</label>
                            <select id="semester" name="semester" class="form-control">
                                <option value="">All Semesters</option>
                                <?php for ($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $semester == $i ? 'selected' : ''; ?>>
                                        <?php echo getSemesterName($i); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="student_id" class="form-label">Student</label>
                            <select id="student_id" name="student_id" class="form-control">
                                <option value="">All Students</option>
                                <?php foreach ($allStudents as $student): ?>
                                    <option value="<?php echo $student['id']; ?>" 
                                            <?php echo $studentId == $student['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['roll_number'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="sort_by" class="form-label">Sort By</label>
                            <select id="sort_by" name="sort_by" class="form-control">
                                <option value="avg_marks" <?php echo $sortBy == 'avg_marks' ? 'selected' : ''; ?>>Average Marks</option>
                                <option value="attendance" <?php echo $sortBy == 'attendance' ? 'selected' : ''; ?>>Attendance</option>
                                <option value="performance" <?php echo $sortBy == 'performance' ? 'selected' : ''; ?>>Performance Status</option>
                                <option value="name" <?php echo $sortBy == 'name' ? 'selected' : ''; ?>>Student Name</option>
                            </select>
                        </div>
                        
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Overall Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-card-title">Total Students</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalStudents; ?></div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-card-title">Avg Attendance</div>
                    </div>
                    <div class="stat-card-value"><?php echo $avgAttendance; ?>%</div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-star"></i></div>
                        <div class="stat-card-title">Avg Marks</div>
                    </div>
                    <div class="stat-card-value"><?php echo $avgMarks; ?>%</div>
                </div>
                
                <div class="stat-card purple">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-trophy"></i></div>
                        <div class="stat-card-title">Excellent</div>
                    </div>
                    <div class="stat-card-value"><?php echo $excellentCount; ?></div>
                </div>
            </div>
            
            <!-- Performance Distribution -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-pie"></i> Performance Distribution</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                        <div class="stat-card green" style="padding: 1rem;">
                            <div class="stat-card-title" style="font-size: 1.2rem;">Excellent</div>
                            <div class="stat-card-value" style="font-size: 2rem;"><?php echo $excellentCount; ?></div>
                            <div class="stat-card-footer"><?php echo $totalStudents > 0 ? number_format(($excellentCount / $totalStudents) * 100, 1) : 0; ?>%</div>
                        </div>
                        
                        <div class="stat-card blue" style="padding: 1rem;">
                            <div class="stat-card-title" style="font-size: 1.2rem;">Good</div>
                            <div class="stat-card-value" style="font-size: 2rem;"><?php echo $goodCount; ?></div>
                            <div class="stat-card-footer"><?php echo $totalStudents > 0 ? number_format(($goodCount / $totalStudents) * 100, 1) : 0; ?>%</div>
                        </div>
                        
                        <div class="stat-card orange" style="padding: 1rem;">
                            <div class="stat-card-title" style="font-size: 1.2rem;">Average</div>
                            <div class="stat-card-value" style="font-size: 2rem;"><?php echo $averageCount; ?></div>
                            <div class="stat-card-footer"><?php echo $totalStudents > 0 ? number_format(($averageCount / $totalStudents) * 100, 1) : 0; ?>%</div>
                        </div>
                        
                        <div class="stat-card red" style="padding: 1rem;">
                            <div class="stat-card-title" style="font-size: 1.2rem;">Below Average</div>
                            <div class="stat-card-value" style="font-size: 2rem;"><?php echo $belowCount; ?></div>
                            <div class="stat-card-footer"><?php echo $totalStudents > 0 ? number_format(($belowCount / $totalStudents) * 100, 1) : 0; ?>%</div>
                        </div>
                        
                        <div class="stat-card red" style="padding: 1rem; opacity: 0.8;">
                            <div class="stat-card-title" style="font-size: 1.2rem;">Poor</div>
                            <div class="stat-card-value" style="font-size: 2rem;"><?php echo $poorCount; ?></div>
                            <div class="stat-card-footer"><?php echo $totalStudents > 0 ? number_format(($poorCount / $totalStudents) * 100, 1) : 0; ?>%</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Performance Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detailed Performance</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($performanceData)): ?>
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-inbox" style="font-size: 4rem; color: #ddd;"></i>
                            <h3 style="color: #7f8c8d; margin-top: 1rem;">No performance data available</h3>
                            <p>Try adjusting the filters</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table" id="performanceTable">
                                <thead>
                                    <tr>
                                        <th>Roll No.</th>
                                        <th>Student Name</th>
                                        <th>Attendance</th>
                                        <th>Avg Marks</th>
                                        <th>Best Score</th>
                                        <th>Worst Score</th>
                                        <th>Assignments</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($performanceData as $perf): ?>
                                        <?php
                                        $statusColor = 'info';
                                        $statusClass = 'status-info';
                                        
                                        if ($perf['performance_status'] == 'Excellent') {
                                            $statusColor = 'success';
                                            $statusClass = 'status-excellent';
                                        } elseif ($perf['performance_status'] == 'Good') {
                                            $statusColor = 'primary';
                                            $statusClass = 'status-good';
                                        } elseif ($perf['performance_status'] == 'Average') {
                                            $statusColor = 'warning';
                                            $statusClass = 'status-average';
                                        } elseif ($perf['performance_status'] == 'Below Average' || $perf['performance_status'] == 'Poor') {
                                            $statusColor = 'danger';
                                            $statusClass = 'status-poor';
                                        }
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($perf['roll_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($perf['student_name']); ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px; background: #ecf0f1; border-radius: 3px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $perf['attendance_percentage'] ?? 0; ?>%; background: #3498db;"
                                                         aria-valuenow="<?php echo $perf['attendance_percentage'] ?? 0; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <span style="color: white; font-size: 11px; font-weight: bold;">
                                                            <?php echo ($perf['attendance_percentage'] ?? 0) . '%'; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong><?php echo $perf['avg_percentage'] ?? '-'; ?>%</strong>
                                            </td>
                                            <td><?php echo $perf['best_score'] ?? '-'; ?>%</td>
                                            <td><?php echo $perf['worst_score'] ?? '-'; ?>%</td>
                                            <td><?php echo $perf['assignments_submitted'] ?? 0; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $statusColor; ?>">
                                                    <?php echo $perf['performance_status']; ?>
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