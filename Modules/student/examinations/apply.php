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

// Get upcoming exams student can apply for
$upcomingExams = $db->select(
    "SELECT ex.*, c.course_code, c.course_name, t.first_name as tf, t.last_name as tl
    FROM exams ex
    JOIN courses c ON ex.course_id = c.id
    JOIN teachers t ON c.teacher_id = t.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = ? AND ex.exam_date > NOW()
    ORDER BY ex.exam_date ASC",
    [$studentId], 'i'
);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = intval($_POST['exam_id'] ?? 0);
    
    if ($examId === 0) {
        $errors[] = 'Please select an exam';
    } else {
        // Check if already registered
        $existing = $db->getOne('exam_registrations', 'exam_id = ? AND student_id = ?', [$examId, $studentId], 'ii');
        
        if ($existing) {
            $errors[] = 'You are already registered for this exam';
        } else {
            $data = [
                'student_id' => $studentId,
                'exam_id' => $examId,
                'registered_date' => date('Y-m-d H:i:s'),
                'status' => 'registered'
            ];
            
            if ($db->insert('exam_registrations', $data)) {
                $_SESSION['success_message'] = 'Successfully registered for exam!';
                header('Location: index.php');
                exit();
            } else {
                $errors[] = 'Failed to register. Please try again.';
            }
        }
    }
}

$pageTitle = "Apply for Exam";
require_once '../../../includes/header.php';
?>

<div class="student-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    <div class="student-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="student-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-graduation-cap"></i> Register for Exam</h1>
                <a href="index.php" class="btn btn-secondary">Back</a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mb-4">
                    <?php foreach ($errors as $error): ?>
                        <div>• <?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($upcomingExams)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No upcoming exams available for registration.
                </div>
            <?php else: ?>
                <form method="POST" class="card">
                    <div class="card-header">
                        <h3 class="card-title">Select Exam to Register</h3>
                    </div>
                    <div class="card-body">
                        <div class="exams-list">
                            <?php foreach ($upcomingExams as $exam): ?>
                                <div class="exam-option">
                                    <input type="radio" name="exam_id" value="<?php echo $exam['id']; ?>" id="exam_<?php echo $exam['id']; ?>" required>
                                    <label for="exam_<?php echo $exam['id']; ?>">
                                        <div>
                                            <h5><?php echo htmlspecialchars($exam['exam_name']); ?></h5>
                                            <p><?php echo htmlspecialchars($exam['course_code'] . ' - ' . $exam['course_name']); ?></p>
                                            <small><?php echo 'By ' . htmlspecialchars($exam['tf'] . ' ' . $exam['tl']); ?></small>
                                        </div>
                                        <div style="text-align: right;">
                                            <strong><?php echo date('M d, Y', strtotime($exam['exam_date'])); ?></strong><br>
                                            <small><?php echo $exam['total_marks']; ?> Marks</small>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="margin-top: 2rem; display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Register
                            </button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.exam-option {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 2px solid #ecf0f1;
    border-radius: 4px;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.2s;
}

.exam-option:hover {
    border-color: #3498db;
    background: #f8f9fa;
}

.exam-option input[type="radio"] {
    cursor: pointer;
    width: 20px;
    height: 20px;
}

.exam-option label {
    flex: 1;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 0;
}

.exam-option h5 {
    margin: 0 0 0.3rem 0;
}

.exam-option p, .exam-option small {
    margin: 0;
}
</style>

<?php require_once '../../../includes/footer.php'; ?>
