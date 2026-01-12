<?php
/**
 * Assignments Management - Teacher
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
$sortBy = $_GET['sort_by'] ?? 'due_date';

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
    $whereClause .= " AND a.course_id = ?";
    $params[] = $courseId;
    $types .= 'i';
}

// Get assignments with statistics
$assignments = $db->select(
    "SELECT a.*,
    c.course_code, c.course_name,
    COUNT(DISTINCT asn.id) as total_submissions,
    SUM(CASE WHEN asn.status = 'submitted' THEN 1 ELSE 0 END) as pending_evaluations,
    SUM(CASE WHEN asn.status = 'evaluated' THEN 1 ELSE 0 END) as evaluated_count,
    AVG(CASE WHEN asn.status = 'evaluated' THEN asn.score ELSE NULL END) as avg_score
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    LEFT JOIN assignment_submissions asn ON a.id = asn.assignment_id
    WHERE $whereClause
    GROUP BY a.id
    ORDER BY " . ($sortBy === 'title' ? 'a.title ASC' : ($sortBy === 'submissions' ? 'total_submissions DESC' : 'a.due_date DESC')),
    $params,
    $types
);

// Calculate overall statistics
$totalAssignments = count($assignments);
$totalSubmissions = 0;
$totalPending = 0;
$totalEvaluated = 0;

foreach ($assignments as $assignment) {
    $totalSubmissions += ($assignment['total_submissions'] ?? 0);
    $totalPending += ($assignment['pending_evaluations'] ?? 0);
    $totalEvaluated += ($assignment['evaluated_count'] ?? 0);
}

$pageTitle = "Assignments";
$additionalCSS = ['teacher.css'];
require_once '../../../includes/header.php';
?>

<div class="teacher-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="teacher-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="teacher-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-tasks"></i> My Assignments</h1>
                <a href="create.php" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Create Assignment
                </a>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-clipboard-list"></i></div>
                        <div class="stat-card-title">Total Assignments</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalAssignments; ?></div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-file-upload"></i></div>
                        <div class="stat-card-title">Total Submissions</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalSubmissions; ?></div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-hourglass-half"></i></div>
                        <div class="stat-card-title">Pending Evaluation</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalPending; ?></div>
                </div>
                
                <div class="stat-card purple">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-check-square"></i></div>
                        <div class="stat-card-title">Evaluated</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalEvaluated; ?></div>
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
                        
                        <div class="form-group col-md-3">
                            <label for="sort_by" class="form-label">Sort By</label>
                            <select id="sort_by" name="sort_by" class="form-control">
                                <option value="due_date" <?php echo $sortBy === 'due_date' ? 'selected' : ''; ?>>Due Date (Latest)</option>
                                <option value="title" <?php echo $sortBy === 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                                <option value="submissions" <?php echo $sortBy === 'submissions' ? 'selected' : ''; ?>>Most Submissions</option>
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
            
            <!-- Assignments Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Assignments List</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($assignments)): ?>
                        <div class="text-center text-muted" style="padding: 3rem;">
                            <i class="fas fa-inbox" style="font-size: 4rem; opacity: 0.5;"></i>
                            <h3 style="margin-top: 1rem;">No assignments yet</h3>
                            <p>Create your first assignment to get started</p>
                            <a href="create.php" class="btn btn-success mt-3">
                                <i class="fas fa-plus"></i> Create Assignment
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
                            <table class="table table-hover">
                                <thead style="background: #f8f9fa;">
                                    <tr>
                                        <th>Title</th>
                                        <th>Course</th>
                                        <th>Due Date</th>
                                        <th>Max Score</th>
                                        <th>Submissions</th>
                                        <th>Pending</th>
                                        <th>Avg Score</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments as $assignment): 
                                        $dueDate = strtotime($assignment['due_date']);
                                        $now = time();
                                        $isOverdue = $dueDate < $now;
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($assignment['description'], 0, 50)) . '...'; ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($assignment['course_code']); ?></td>
                                            <td>
                                                <span class="<?php echo $isOverdue ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo date('Y-m-d', $dueDate); ?>
                                                </span>
                                                <?php if ($isOverdue): ?>
                                                    <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Overdue</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $assignment['max_score']; ?></td>
                                            <td>
                                                <span class="badge badge-info"><?php echo $assignment['total_submissions'] ?? 0; ?></span>
                                            </td>
                                            <td>
                                                <?php if (($assignment['pending_evaluations'] ?? 0) > 0): ?>
                                                    <span class="badge badge-warning"><?php echo $assignment['pending_evaluations']; ?></span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (($assignment['avg_score'] ?? 0) > 0): ?>
                                                    <strong><?php echo number_format($assignment['avg_score'], 2); ?></strong>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="evaluate.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn btn-info" title="Evaluate">
                                                        <i class="fas fa-star"></i>
                                                    </a>
                                                    <a href="edit.php?id=<?php echo $assignment['id']; ?>" class="btn btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="#" onclick="deleteAssignment(<?php echo $assignment['id']; ?>)" class="btn btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
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
</style>

<script>
function deleteAssignment(id) {
    if (confirm('Are you sure you want to delete this assignment?')) {
        // Implement delete functionality
        console.log('Delete assignment:', id);
    }
}
</script>

<?php require_once '../../../includes/footer.php'; ?>
