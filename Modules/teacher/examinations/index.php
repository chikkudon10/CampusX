<?php
/**
 * Examinations List - Teacher
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

// Get filter
$courseId = $_GET['course_id'] ?? '';

// Get teacher's courses
$courses = $db->select(
    "SELECT c.id, c.course_code, c.course_name
    FROM courses c
    WHERE c.teacher_id = ?
    ORDER BY c.course_name",
    [$teacherId],
    'i'
);

// Build query
$whereClause = "c.teacher_id = ?";
$params = [$teacherId];
$types = 'i';

if ($courseId) {
    $whereClause .= " AND ex.course_id = ?";
    $params[] = $courseId;
    $types .= 'i';
}

// Get exams with statistics
$exams = $db->select(
    "SELECT ex.*,
    c.course_code, c.course_name,
    COUNT(DISTINCT r.id) as total_results,
    COUNT(DISTINCT CASE WHEN r.grade = 'F' THEN r.id END) as failed_count,
    COUNT(DISTINCT CASE WHEN r.grade != 'F' THEN r.id END) as passed_count,
    ROUND(AVG(CASE WHEN r.grade != 'F' THEN 1 ELSE 0 END) * 100, 2) as pass_percentage,
    ROUND(AVG((r.marks_obtained / r.total_marks) * 100), 2) as avg_percentage
    FROM exams ex
    JOIN courses c ON ex.course_id = c.id
    LEFT JOIN results r ON ex.id = r.exam_id
    WHERE $whereClause
    GROUP BY ex.id
    ORDER BY ex.exam_date DESC",
    $params,
    $types
);

// Calculate statistics
$totalExams = count($exams);
$totalResults = 0;
$totalGraded = 0;

foreach ($exams as $exam) {
    $totalResults += ($exam['total_results'] ?? 0);
    $totalGraded += ($exam['total_results'] ?? 0);
}

$pageTitle = "Examinations";
$additionalCSS = ['teacher.css'];
require_once '../../../includes/header.php';
?>

<div class="teacher-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="teacher-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="teacher-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-file-alt"></i> Examinations</h1>
                <a href="grade-entry.php" class="btn btn-success">
                    <i class="fas fa-graduation-cap"></i> Enter Grades
                </a>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-card-title">Total Exams</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalExams; ?></div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-card-title">Total Results</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalResults; ?></div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="form-row">
                        <div class="form-group col-md-4">
                            <label for="course_id" class="form-label">Filter by Course</label>
                            <select id="course_id" name="course_id" class="form-control">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>" <?php echo $courseId == $course['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-2" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Exams Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Examinations List</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($exams)): ?>
                        <div class="text-center text-muted" style="padding: 3rem;">
                            <i class="fas fa-inbox" style="font-size: 4rem; opacity: 0.5;"></i>
                            <h3 style="margin-top: 1rem;">No exams found</h3>
                            <p>No examinations have been created yet</p>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table table-hover">
                                <thead style="background: #f8f9fa;">
                                    <tr>
                                        <th>Exam Name</th>
                                        <th>Course</th>
                                        <th>Exam Date</th>
                                        <th>Total Marks</th>
                                        <th>Results</th>
                                        <th>Pass %</th>
                                        <th>Avg %</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($exams as $exam): 
                                        $examDate = strtotime($exam['exam_date']);
                                        $now = time();
                                        $isPast = $examDate < $now;
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($exam['exam_name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($exam['course_code']); ?></td>
                                            <td>
                                                <span class="<?php echo $isPast ? 'text-muted' : 'text-success'; ?>">
                                                    <?php echo date('Y-m-d H:i', $examDate); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $exam['total_marks']; ?></td>
                                            <td>
                                                <span class="badge badge-info"><?php echo $exam['total_results'] ?? 0; ?></span>
                                            </td>
                                            <td>
                                                <?php if (($exam['passed_count'] ?? 0) > 0): ?>
                                                    <span class="badge badge-success"><?php echo number_format($exam['pass_percentage'], 1); ?>%</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">0%</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($exam['avg_percentage'] !== null): ?>
                                                    <strong><?php echo number_format($exam['avg_percentage'], 2); ?>%</strong>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="grade-entry.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-graduation-cap"></i> Grades
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
</style>

<?php require_once '../../../includes/footer.php'; ?>
