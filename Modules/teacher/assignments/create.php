<?php
/**
 * Create Assignment - Teacher
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

// Get teacher's courses
$courses = $db->select(
    "SELECT c.id, c.course_code, c.course_name, c.semester
    FROM courses c
    WHERE c.teacher_id = ?
    ORDER BY c.course_name",
    [$teacherId],
    'i'
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $courseId = intval($_POST['course_id'] ?? 0);
    $dueDate = $_POST['due_date'] ?? '';
    $dueTime = $_POST['due_time'] ?? '23:59:59';
    $maxScore = intval($_POST['max_score'] ?? 100);
    $instructions = trim($_POST['instructions'] ?? '');
    
    // Validations
    if (empty($title)) {
        $errors[] = 'Assignment title is required';
    } elseif (strlen($title) < 3) {
        $errors[] = 'Assignment title must be at least 3 characters';
    } elseif (strlen($title) > 255) {
        $errors[] = 'Assignment title cannot exceed 255 characters';
    }
    
    if (empty($description)) {
        $errors[] = 'Assignment description is required';
    }
    
    if ($courseId === 0) {
        $errors[] = 'Please select a course';
    } else {
        // Verify course belongs to teacher
        $courseVerify = $db->getOne('courses', 'id = ? AND teacher_id = ?', [$courseId, $teacherId], 'ii');
        if (!$courseVerify) {
            $errors[] = 'Invalid course selection';
        }
    }
    
    if (empty($dueDate)) {
        $errors[] = 'Due date is required';
    } else {
        $dueDatetime = strtotime($dueDate . ' ' . $dueTime);
        $now = time();
        if ($dueDatetime <= $now) {
            $errors[] = 'Due date and time must be in the future';
        }
    }
    
    if ($maxScore <= 0 || $maxScore > 1000) {
        $errors[] = 'Max score must be between 1 and 1000';
    }
    
    if (empty($errors)) {
        try {
            $data = [
                'course_id' => $courseId,
                'title' => $title,
                'description' => $description,
                'instructions' => $instructions,
                'due_date' => $dueDate . ' ' . $dueTime,
                'max_score' => $maxScore,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $assignmentId = $db->insert('assignments', $data);
            
            if ($assignmentId) {
                $_SESSION['success_message'] = 'Assignment created successfully!';
                header('Location: index.php');
                exit();
            } else {
                $errors[] = 'Failed to create assignment. Please try again.';
            }
            
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Create Assignment";
$additionalCSS = ['teacher.css'];
require_once '../../../includes/header.php';
?>

<div class="teacher-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="teacher-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="teacher-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-plus-circle"></i> Create Assignment</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="fas fa-exclamation-circle"></i> Errors found:</strong>
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
            
            <form method="POST" class="needs-validation">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Assignment Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="title" class="form-label">Assignment Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                   placeholder="e.g., Chapter 5 Exercise" required>
                            <small class="form-text text-muted">Enter a descriptive title for the assignment</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="5" 
                                      placeholder="Provide a detailed description of what students need to do..."
                                      required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Be clear about what is expected from students</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="instructions" class="form-label">Additional Instructions</label>
                            <textarea class="form-control" id="instructions" name="instructions" rows="3"
                                      placeholder="Optional: Add any additional instructions or guidelines"><?php echo htmlspecialchars($_POST['instructions'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Optional: Provide format requirements, submission guidelines, etc.</small>
                        </div>
                        
                        <hr>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
                                <select class="form-control" id="course_id" name="course_id" required>
                                    <option value="">-- Select Course --</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" 
                                                <?php echo ($_POST['course_id'] ?? '') == $course['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Select the course this assignment is for</small>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="max_score" class="form-label">Max Score <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="max_score" name="max_score" 
                                       value="<?php echo htmlspecialchars($_POST['max_score'] ?? 100); ?>" 
                                       min="1" max="1000" required>
                                <small class="form-text text-muted">Maximum marks for this assignment</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       value="<?php echo htmlspecialchars($_POST['due_date'] ?? date('Y-m-d', strtotime('+7 days'))); ?>" required>
                                <small class="form-text text-muted">Date when assignment is due</small>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="due_time" class="form-label">Due Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="due_time" name="due_time" 
                                       value="<?php echo htmlspecialchars($_POST['due_time'] ?? '23:59'); ?>" required>
                                <small class="form-text text-muted">Time when assignment is due</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check"></i> Create Assignment
                            </button>
                            <a href="index.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
