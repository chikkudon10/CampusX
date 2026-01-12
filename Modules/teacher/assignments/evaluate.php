<?php
/**
 * Evaluate Submissions - Teacher
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_TEACHER);

$db = new Database();
$errors = [];

// Get teacher info
$teacher = $db->getOne('teachers', 'user_id = ?', [$_SESSION['user_id']], 'i');
if (!$teacher) {
    $_SESSION['error_message'] = 'Teacher profile not found';
    header('Location: ../dashboard.php');
    exit();
}
$teacherId = $teacher['id'];

// Get filter
$assignmentId = $_GET['assignment_id'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';

// Get teacher's assignments
$assignments = $db->select(
    "SELECT a.*, c.course_name, c.course_code
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    WHERE c.teacher_id = ?
    ORDER BY a.title",
    [$teacherId],
    'i'
);

$submissions = [];
$assignmentInfo = null;

if ($assignmentId) {
    // Verify assignment belongs to teacher
    $assignmentInfo = $db->getOne(
        "SELECT a.*, c.course_name FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE a.id = ? AND c.teacher_id = ?",
        [$assignmentId, $teacherId],
        'ii'
    );
    
    if ($assignmentInfo) {
        // Get submissions
        $whereClause = "a.id = ?";
        $params = [$assignmentId];
        $types = 'i';
        
        if ($filterStatus) {
            $whereClause .= " AND asn.status = ?";
            $params[] = $filterStatus;
            $types .= 's';
        }
        
        $submissions = $db->select(
            "SELECT asn.id, asn.student_id, asn.submitted_file, asn.submission_date, 
            asn.score, asn.feedback, asn.status,
            s.roll_number, s.first_name, s.last_name, s.email,
            a.max_score
            FROM assignment_submissions asn
            JOIN students s ON asn.student_id = s.id
            JOIN assignments a ON asn.assignment_id = a.id
            WHERE $whereClause
            ORDER BY asn.status ASC, asn.submission_date DESC",
            $params,
            $types
        );
    }
}

// Handle score submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submissionId = intval($_POST['submission_id'] ?? 0);
    $score = floatval($_POST['score'] ?? 0);
    $feedback = trim($_POST['feedback'] ?? '');
    
    if ($submissionId === 0) {
        $errors[] = 'Invalid submission';
    }
    
    if ($score < 0) {
        $errors[] = 'Score cannot be negative';
    }
    
    if (empty($errors)) {
        try {
            // Get submission to verify it belongs to teacher's course
            $submission = $db->getOne(
                "SELECT asn.* FROM assignment_submissions asn
                JOIN assignments a ON asn.assignment_id = a.id
                JOIN courses c ON a.course_id = c.id
                WHERE asn.id = ? AND c.teacher_id = ?",
                [$submissionId, $teacherId],
                'ii'
            );
            
            if (!$submission) {
                $errors[] = 'Submission not found or unauthorized';
            } else {
                $data = [
                    'score' => $score,
                    'feedback' => $feedback,
                    'status' => 'evaluated',
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $updated = $db->update(
                    'assignment_submissions',
                    $data,
                    'id = ?',
                    [$submissionId],
                    'i'
                );
                
                if ($updated !== false) {
                    $_SESSION['success_message'] = 'Submission evaluated successfully!';
                    header("Location: evaluate.php?assignment_id=" . $assignmentId . "&filter_status=" . $filterStatus);
                    exit();
                } else {
                    $errors[] = 'Failed to save evaluation';
                }
            }
            
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Evaluate Submissions";
$additionalCSS = ['teacher.css'];
require_once '../../../includes/header.php';
?>

<div class="teacher-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="teacher-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="teacher-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-star"></i> Evaluate Submissions</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Assignments
                </a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="fas fa-exclamation-circle"></i> Errors:</strong>
                    <ul style="margin: 10px 0 0 20px; padding: 0;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Select Assignment -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Select Assignment</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-row">
                        <div class="form-group col-md-6">
                            <label for="assignment_id">Assignment <span class="text-danger">*</span></label>
                            <select class="form-control" id="assignment_id" name="assignment_id" onchange="window.location.href='?assignment_id=' + this.value;" required>
                                <option value="">-- Select Assignment --</option>
                                <?php foreach ($assignments as $assign): ?>
                                    <option value="<?php echo $assign['id']; ?>" <?php echo $assignmentId == $assign['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($assign['title'] . ' (' . $assign['course_code'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <?php if ($assignmentId): ?>
                            <div class="form-group col-md-4">
                                <label for="filter_status">Filter by Status</label>
                                <select class="form-control" id="filter_status" name="filter_status" onchange="this.form.submit();">
                                    <option value="">All Submissions</option>
                                    <option value="submitted" <?php echo $filterStatus === 'submitted' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="evaluated" <?php echo $filterStatus === 'evaluated' ? 'selected' : ''; ?>>Evaluated</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <?php if ($assignmentId && $assignmentInfo): ?>
                <!-- Assignment Info -->
                <div class="alert alert-info mb-4">
                    <h5><?php echo htmlspecialchars($assignmentInfo['title']); ?></h5>
                    <p style="margin: 0.5rem 0 0 0;">
                        <strong>Course:</strong> <?php echo htmlspecialchars($assignmentInfo['course_name']); ?> |
                        <strong>Max Score:</strong> <?php echo $assignmentInfo['max_score']; ?> |
                        <strong>Due Date:</strong> <?php echo date('Y-m-d H:i', strtotime($assignmentInfo['due_date'])); ?>
                    </p>
                </div>
                
                <?php if (!empty($submissions)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list"></i> Submissions 
                                <span class="badge badge-primary"><?php echo count($submissions); ?></span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($submissions as $submission): ?>
                                <div class="submission-card" style="padding: 1.5rem; border: 1px solid #ecf0f1; border-radius: 4px; margin-bottom: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                        <div>
                                            <h5 style="margin: 0;">
                                                <?php echo htmlspecialchars($submission['first_name'] . ' ' . $submission['last_name']); ?>
                                                <small class="text-muted">(<?php echo htmlspecialchars($submission['roll_number']); ?>)</small>
                                            </h5>
                                            <small class="text-muted">
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($submission['email']); ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar"></i> Submitted: <?php echo date('Y-m-d H:i', strtotime($submission['submission_date'])); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php if ($submission['status'] === 'evaluated'): ?>
                                                <span class="badge badge-success" style="font-size: 1rem;">Evaluated</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning" style="font-size: 1rem;">Pending</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($submission['submitted_file']): ?>
                                        <div style="margin-bottom: 1rem;">
                                            <a href="<?php echo htmlspecialchars($submission['submitted_file']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-download"></i> Download Submission
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="margin-top: 1rem;">
                                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                        
                                        <div class="form-row">
                                            <div class="form-group col-md-3">
                                                <label>Score (out of <?php echo $submission['max_score']; ?>)</label>
                                                <input type="number" class="form-control" name="score" step="0.1" 
                                                       value="<?php echo $submission['score'] ?? 0; ?>" 
                                                       max="<?php echo $submission['max_score']; ?>" min="0" required>
                                            </div>
                                            
                                            <?php if ($submission['status'] === 'evaluated'): ?>
                                                <div class="form-group col-md-9">
                                                    <label>Percentage</label>
                                                    <input type="text" class="form-control" disabled value="<?php echo number_format(($submission['score'] / $submission['max_score']) * 100, 2); ?>%">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Feedback for Student</label>
                                            <textarea class="form-control" name="feedback" rows="3" placeholder="Provide constructive feedback to the student..."><?php echo htmlspecialchars($submission['feedback'] ?? ''); ?></textarea>
                                            <small class="form-text text-muted">This feedback will be visible to the student</small>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Save Evaluation
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif ($assignmentId): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No submissions for this assignment yet.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>