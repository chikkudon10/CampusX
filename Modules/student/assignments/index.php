<?php
/**
 * Assignments - Student
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_STUDENT);

$db = new Database();

// Get student info
$student = $db->getOne('students', 'user_id = ?', [$_SESSION['user_id']], 'i');
if (!$student) {
    $_SESSION['error_message'] = 'Student profile not found';
    header('Location: ../dashboard.php');
    exit();
}
$studentId = $student['id'];

// Get filter parameters
$courseId = $_GET['course_id'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';

// Get student's courses
$courses = $db->select(
    "SELECT c.id, c.course_code, c.course_name
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id = ?
    ORDER BY c.course_name",
    [$studentId],
    'i'
);

// Build query
$whereClause = "e.student_id = ?";
$params = [$studentId];
$types = 'i';

if ($courseId) {
    $whereClause .= " AND a.course_id = ?";
    $params[] = $courseId;
    $types .= 'i';
}

// Get assignments with submission status
$assignments = $db->select(
    "SELECT a.*, c.course_code, c.course_name, t.first_name as teacher_first_name, t.last_name as teacher_last_name,
    asn.id as submission_id, asn.status as submission_status, asn.submission_date, asn.score, asn.feedback,
    CASE WHEN a.due_date < NOW() THEN 'overdue' ELSE 'pending' END as deadline_status
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    JOIN teachers t ON c.teacher_id = t.id
    JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN assignment_submissions asn ON a.id = asn.assignment_id AND asn.student_id = ?
    WHERE $whereClause
    ORDER BY a.due_date DESC",
    array_merge([$studentId], $params),
    'i' . $types
);

// Apply status filter
$filteredAssignments = $assignments;
if ($filterStatus) {
    $filteredAssignments = array_filter($assignments, function($a) use ($filterStatus) {
        if ($filterStatus === 'submitted') {
            return $a['submission_status'] === 'submitted' || $a['submission_status'] === 'evaluated';
        } elseif ($filterStatus === 'pending') {
            return $a['submission_status'] === null;
        } elseif ($filterStatus === 'evaluated') {
            return $a['submission_status'] === 'evaluated';
        }
        return true;
    });
}

// Calculate statistics
$totalAssignments = count($assignments);
$submittedCount = 0;
$evaluatedCount = 0;
$pendingCount = 0;
$overdueCount = 0;

foreach ($assignments as $assignment) {
    if ($assignment['submission_status'] === 'submitted' || $assignment['submission_status'] === 'evaluated') {
        $submittedCount++;
    } else {
        $pendingCount++;
    }
    
    if ($assignment['submission_status'] === 'evaluated') {
        $evaluatedCount++;
    }
    
    if ($assignment['deadline_status'] === 'overdue' && $assignment['submission_status'] === null) {
        $overdueCount++;
    }
}

$pageTitle = "Assignments";
$additionalCSS = ['student.css'];
require_once '../../../includes/header.php';
?>

<div class="student-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="student-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="student-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-tasks"></i> My Assignments</h1>
                <a href="../dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
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
                        <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-card-title">Submitted</div>
                    </div>
                    <div class="stat-card-value"><?php echo $submittedCount; ?></div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-hourglass-half"></i></div>
                        <div class="stat-card-title">Pending</div>
                    </div>
                    <div class="stat-card-value"><?php echo $pendingCount; ?></div>
                </div>
                
                <div class="stat-card purple">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-star"></i></div>
                        <div class="stat-card-title">Evaluated</div>
                    </div>
                    <div class="stat-card-value"><?php echo $evaluatedCount; ?></div>
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
                            <label for="filter_status" class="form-label">Filter by Status</label>
                            <select id="filter_status" name="filter_status" class="form-control">
                                <option value="">All Assignments</option>
                                <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="submitted" <?php echo $filterStatus === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                <option value="evaluated" <?php echo $filterStatus === 'evaluated' ? 'selected' : ''; ?>>Evaluated</option>
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
            
            <!-- Assignments List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Assignments</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($filteredAssignments)): ?>
                        <div class="text-center text-muted" style="padding: 3rem;">
                            <i class="fas fa-inbox" style="font-size: 4rem; opacity: 0.5;"></i>
                            <h3 style="margin-top: 1rem;">No assignments found</h3>
                            <p>Try adjusting your filters</p>
                        </div>
                    <?php else: ?>
                        <div class="assignments-list">
                            <?php foreach ($filteredAssignments as $assignment): 
                                $daysLeft = ceil((strtotime($assignment['due_date']) - time()) / (60 * 60 * 24));
                                $isOverdue = $daysLeft < 0;
                                $isSubmitted = $assignment['submission_status'] !== null;
                                $isEvaluated = $assignment['submission_status'] === 'evaluated';
                            ?>
                                <div class="assignment-card">
                                    <div class="assignment-header">
                                        <div>
                                            <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
                                            <p class="course-info">
                                                <span class="badge badge-info"><?php echo htmlspecialchars($assignment['course_code']); ?></span>
                                                <span style="color: #7f8c8d; font-size: 0.9em;">
                                                    By <?php echo htmlspecialchars($assignment['teacher_first_name'] . ' ' . $assignment['teacher_last_name']); ?>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="status-badges">
                                            <?php if ($isEvaluated): ?>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Evaluated
                                                </span>
                                            <?php elseif ($isSubmitted): ?>
                                                <span class="badge badge-info">
                                                    <i class="fas fa-paper-plane"></i> Submitted
                                                </span>
                                            <?php elseif ($isOverdue): ?>
                                                <span class="badge badge-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> Overdue
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-clock"></i> Pending
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="assignment-body">
                                        <p><?php echo htmlspecialchars(substr($assignment['description'], 0, 150)) . '...'; ?></p>
                                        
                                        <div class="assignment-meta">
                                            <div class="meta-item">
                                                <i class="fas fa-calendar"></i>
                                                <div>
                                                    <span class="meta-label">Due Date</span>
                                                    <span class="meta-value <?php echo $isOverdue ? 'text-danger' : 'text-success'; ?>">
                                                        <?php echo date('M d, Y H:i', strtotime($assignment['due_date'])); ?>
                                                        <?php if ($isOverdue): ?>
                                                            <br><small>(Overdue by <?php echo abs($daysLeft); ?> days)</small>
                                                        <?php elseif ($daysLeft === 0): ?>
                                                            <br><small style="color: #f39c12;">(Due today)</small>
                                                        <?php else: ?>
                                                            <br><small>(<?php echo $daysLeft; ?> days remaining)</small>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="meta-item">
                                                <i class="fas fa-star"></i>
                                                <div>
                                                    <span class="meta-label">Max Score</span>
                                                    <span class="meta-value"><?php echo $assignment['max_score']; ?> Points</span>
                                                </div>
                                            </div>
                                            
                                            <?php if ($isEvaluated): ?>
                                                <div class="meta-item">
                                                    <i class="fas fa-check-double"></i>
                                                    <div>
                                                        <span class="meta-label">Your Score</span>
                                                        <span class="meta-value" style="color: #2ecc71; font-weight: bold;">
                                                            <?php echo $assignment['score']; ?>/<?php echo $assignment['max_score']; ?> 
                                                            (<?php echo number_format(($assignment['score'] / $assignment['max_score']) * 100, 1); ?>%)
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($isEvaluated && $assignment['feedback']): ?>
                                        <div class="assignment-feedback">
                                            <h5><i class="fas fa-comment-dots"></i> Teacher Feedback</h5>
                                            <p><?php echo htmlspecialchars($assignment['feedback']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="assignment-footer">
                                        <?php if (!$isSubmitted): ?>
                                            <a href="submit.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn btn-success">
                                                <i class="fas fa-upload"></i> Submit Assignment
                                            </a>
                                        <?php else: ?>
                                            <div style="color: #27ae60; padding: 10px;">
                                                <i class="fas fa-check-circle"></i>
                                                Submitted on <?php echo date('M d, Y H:i', strtotime($assignment['submission_date'])); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <a href="view.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn btn-info">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
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

.stat-card.blue { border-left-color: #3498db; }
.stat-card.green { border-left-color: #2ecc71; }
.stat-card.orange { border-left-color: #f39c12; }
.stat-card.purple { border-left-color: #9b59b6; }

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

.assignments-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.assignment-card {
    background: white;
    border: 1px solid #ecf0f1;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
}

.assignment-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-3px);
}

.assignment-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-bottom: 2px solid #ecf0f1;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.assignment-header h4 {
    margin: 0 0 0.5rem 0;
    color: #2c3e50;
    font-size: 1.1em;
}

.course-info {
    margin: 0;
}

.status-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.assignment-body {
    padding: 1.5rem;
    flex: 1;
}

.assignment-body p {
    color: #7f8c8d;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.assignment-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.meta-item {
    padding: 0.8rem;
    background: #f8f9fa;
    border-radius: 4px;
    display: flex;
    gap: 0.8rem;
}

.meta-item i {
    color: #3498db;
    margin-top: 0.2rem;
}

.meta-label {
    display: block;
    font-size: 0.75rem;
    color: #95a5a6;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 0.3rem;
}

.meta-value {
    display: block;
    color: #2c3e50;
    font-weight: 600;
}

.assignment-feedback {
    padding: 1rem 1.5rem;
    background: #fffacd;
    border-left: 3px solid #f39c12;
    margin: 0 1.5rem;
}

.assignment-feedback h5 {
    margin: 0 0 0.5rem 0;
    color: #d68910;
}

.assignment-feedback p {
    margin: 0;
    color: #7f6b00;
}

.assignment-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #ecf0f1;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.assignment-footer .btn {
    flex: 1;
    min-width: 120px;
}

@media (max-width: 768px) {
    .assignments-list {
        grid-template-columns: 1fr;
    }
    
    .assignment-meta {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once '../../../includes/footer.php'; ?>
