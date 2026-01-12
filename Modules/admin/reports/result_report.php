<?php
/**
 * Result Report - Admin
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
$courseId = $_GET['course_id'] ?? '';
$minGrade = $_GET['min_grade'] ?? '';

// Get all courses for filter
$courses = $db->getAll('courses', '', [], '', '', 'course_name');

// Build query based on filters
$whereClause = '1=1';
$params = [];
$types = '';

if ($semester) {
    $whereClause .= ' AND c.semester = ?';
    $params[] = $semester;
    $types .= 'i';
}

if ($courseId) {
    $whereClause .= ' AND r.course_id = ?';
    $params[] = $courseId;
    $types .= 'i';
}

// Get results data
$resultsData = $db->select(
    "SELECT 
        s.roll_number,
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        s.semester,
        c.course_code,
        c.course_name,
        c.credits,
        r.marks_obtained,
        r.total_marks,
        r.grade,
        r.created_at
     FROM results r
     JOIN students s ON r.student_id = s.id
     JOIN courses c ON r.course_id = c.id
     WHERE $whereClause
     ORDER BY s.roll_number, c.course_code",
    $params,
    $types
);

// Filter by grade if specified
if ($minGrade && !empty($resultsData)) {
    global $GRADE_POINTS;
    $minGradePoint = $GRADE_POINTS[$minGrade] ?? 0;
    
    $resultsData = array_filter($resultsData, function($result) use ($minGradePoint, $GRADE_POINTS) {
        $gradePoint = $GRADE_POINTS[$result['grade']] ?? 0;
        return $gradePoint >= $minGradePoint;
    });
}

// Calculate statistics
$totalResults = count($resultsData);
$gradeDistribution = [];
$passCount = 0;
$totalPercentage = 0;

foreach ($resultsData as $result) {
    // Grade distribution
    $grade = $result['grade'];
    $gradeDistribution[$grade] = ($gradeDistribution[$grade] ?? 0) + 1;
    
    // Pass/Fail count
    if ($grade != 'F') $passCount++;
    
    // Total percentage
    $percentage = ($result['marks_obtained'] / $result['total_marks']) * 100;
    $totalPercentage += $percentage;
}

$passPercentage = $totalResults > 0 ? ($passCount / $totalResults) * 100 : 0;
$averagePercentage = $totalResults > 0 ? $totalPercentage / $totalResults : 0;

$pageTitle = "Result Report";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-trophy"></i> Result Report</h1>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button onclick="exportTableToCSV('resultsTable', 'results-report.csv')" class="btn btn-success">
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
                            <label for="min_grade" class="form-label">Minimum Grade</label>
                            <select id="min_grade" name="min_grade" class="form-control">
                                <option value="">All Grades</option>
                                <?php 
                                global $GRADE_POINTS;
                                foreach (array_keys($GRADE_POINTS) as $grade): 
                                ?>
                                    <option value="<?php echo $grade; ?>" <?php echo $minGrade == $grade ? 'selected' : ''; ?>>
                                        <?php echo $grade; ?> and above
                                    </option>
                                <?php endforeach; ?>
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
            
            <!-- Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-clipboard-list"></i></div>
                        <div class="stat-card-title">Total Results</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalResults; ?></div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-card-title">Pass Rate</div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($passPercentage, 1); ?>%</div>
                    <div class="stat-card-footer"><?php echo $passCount; ?> passed</div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-chart-line"></i></div>
                        <div class="stat-card-title">Average Score</div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($averagePercentage, 1); ?>%</div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-card-title">Failed</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalResults - $passCount; ?></div>
                </div>
            </div>
            
            <!-- Grade Distribution -->
            <?php if (!empty($gradeDistribution)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-bar"></i> Grade Distribution</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                            <?php 
                            foreach ($GRADE_POINTS as $grade => $point):
                                $count = $gradeDistribution[$grade] ?? 0;
                                $percentage = $totalResults > 0 ? ($count / $totalResults) * 100 : 0;
                                
                                $colorClass = 'blue';
                                if ($grade == 'A+' || $grade == 'A') $colorClass = 'green';
                                elseif ($grade == 'B+' || $grade == 'B') $colorClass = 'blue';
                                elseif ($grade == 'C+' || $grade == 'C') $colorClass = 'orange';
                                else $colorClass = 'red';
                            ?>
                                <div class="stat-card <?php echo $colorClass; ?>" style="padding: 1rem;">
                                    <div class="stat-card-title" style="font-size: 1.5rem; font-weight: bold;">
                                        <?php echo $grade; ?>
                                    </div>
                                    <div class="stat-card-value" style="font-size: 2rem;">
                                        <?php echo $count; ?>
                                    </div>
                                    <div class="stat-card-footer">
                                        <?php echo number_format($percentage, 1); ?>%
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Results Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detailed Results</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($resultsData)): ?>
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-inbox" style="font-size: 4rem; color: #ddd;"></i>
                            <h3 style="color: #7f8c8d; margin-top: 1rem;">No results found</h3>
                            <p>Try adjusting the filters</p>
                        </div>
                    <?php else: ?>
                        <table class="table" id="resultsTable">
                            <thead>
                                <tr>
                                    <th>Roll No.</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Marks</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resultsData as $result): ?>
                                    <?php 
                                    $percentage = ($result['marks_obtained'] / $result['total_marks']) * 100;
                                    $statusColor = $result['grade'] != 'F' ? 'success' : 'danger';
                                    $statusText = $result['grade'] != 'F' ? 'Pass' : 'Fail';
                                    
                                    // Grade badge color
                                    $gradeClass = 'A';
                                    if ($result['grade'] == 'A+' || $result['grade'] == 'A') $gradeClass = 'A';
                                    elseif ($result['grade'] == 'B+' || $result['grade'] == 'B') $gradeClass = 'B';
                                    elseif ($result['grade'] == 'C+' || $result['grade'] == 'C') $gradeClass = 'C';
                                    elseif ($result['grade'] == 'D+' || $result['grade'] == 'D') $gradeClass = 'D';
                                    else $gradeClass = 'F';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($result['roll_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($result['student_name']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($result['course_code']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($result['course_name']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo $result['marks_obtained']; ?> / <?php echo $result['total_marks']; ?>
                                        </td>
                                        <td><?php echo number_format($percentage, 1); ?>%</td>
                                        <td>
                                            <span class="grade-badge <?php echo $gradeClass; ?>">
                                                <?php echo $result['grade']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $statusColor; ?>">
                                                <?php echo $statusText; ?>
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