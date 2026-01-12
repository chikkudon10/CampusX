<?php
require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_STUDENT);

$db = new Database();
$student = $db->getOne('students', 'user_id = ?', [$_SESSION['user_id']], 'i');
$studentId = $student['id'];

$courseId = $_GET['course_id'] ?? '';

$courses = $db->select(
    "SELECT DISTINCT c.id, c.course_code, c.course_name FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN results r ON c.id = r.course_id AND r.student_id = ?
    WHERE e.student_id = ? ORDER BY c.course_name",
    [$studentId, $studentId], 'ii'
);

$whereClause = "r.student_id = ?";
$params = [$studentId];
$types = 'i';

if ($courseId) {
    $whereClause .= " AND r.course_id = ?";
    $params[] = $courseId;
    $types .= 'i';
}

$results = $db->select(
    "SELECT r.*, c.course_code, c.course_name, ex.exam_name
    FROM results r
    JOIN courses c ON r.course_id = c.id
    LEFT JOIN exams ex ON r.exam_id = ex.id
    WHERE $whereClause
    ORDER BY r.created_at DESC",
    $params, $types
);

// Calculate GPA
$totalPoints = 0;
$totalCredits = 0;

foreach ($results as $result) {
    $course = $db->getOne('courses', 'id = ?', [$result['course_id']], 'i');
    if ($course) {
        $gradePoints = [
            'A+' => 4.0, 'A' => 4.0, 'B+' => 3.5, 'B' => 3.0,
            'C+' => 2.5, 'C' => 2.0, 'D+' => 1.5, 'D' => 1.0, 'F' => 0.0
        ];
        $points = $gradePoints[$result['grade']] ?? 0;
        $totalPoints += $points * $course['credits'];
        $totalCredits += $course['credits'];
    }
}

$gpa = $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : 0;

$pageTitle = "Results";
require_once '../../../includes/header.php';
?>

<div class="student-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    <div class="student-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="student-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-award"></i> My Results</h1>
                <a href="../dashboard.php" class="btn btn-secondary">Back</a>
            </div>
            
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-value"><?php echo count($results); ?></div>
                    <div class="stat-card-title">Total Results</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-card-value"><?php echo $gpa; ?></div>
                    <div class="stat-card-title">Overall GPA</div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="form-row">
                        <div class="form-group col-md-6">
                            <label>Filter by Course</label>
                            <select name="course_id" class="form-control">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $courseId == $c['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['course_code']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-2" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <?php if (empty($results)): ?>
                        <div class="text-center text-muted" style="padding: 3rem;">
                            <p>No results available yet</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th>Course</th>
                                    <th>Exam</th>
                                    <th>Marks</th>
                                    <th>Percentage</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $result): 
                                    $percentage = ($result['marks_obtained'] / $result['total_marks']) * 100;
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($result['course_code']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($result['course_name']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($result['exam_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo $result['marks_obtained']; ?> / <?php echo $result['total_marks']; ?></td>
                                        <td><?php echo number_format($percentage, 2); ?>%</td>
                                        <td>
                                            <span class="badge badge-<?php echo
                                                $result['grade'] === 'A' || $result['grade'] === 'A+' ? 'success' :
                                                ($result['grade'] === 'B' || $result['grade'] === 'B+' ? 'info' :
                                                ($result['grade'] === 'F' ? 'danger' : 'warning'));
                                            ?>">
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
    text-align: center;
}

.stat-card.blue { border-left-color: #3498db; }
.stat-card.green { border-left-color: #2ecc71; }

.stat-card-value {
    font-size: 2.5rem;
    font-weight: bold;
    color: #2c3e50;
}

.stat-card-title {
    font-size: 0.85rem;
    color: #7f8c8d;
    text-transform: uppercase;
    font-weight: 600;
    margin-top: 0.5rem;
}
</style>

<?php require_once '../../../includes/footer.php'; ?>