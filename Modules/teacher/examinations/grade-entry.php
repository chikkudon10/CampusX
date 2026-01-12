<?php
/**
 * Grade Entry - Teacher
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

// Get teacher's exams
$exams = $db->select(
    "SELECT ex.*, c.course_name, c.course_code
    FROM exams ex
    JOIN courses c ON ex.course_id = c.id
    WHERE c.teacher_id = ?
    ORDER BY ex.exam_date DESC",
    [$teacherId],
    'i'
);

// Get filter
$examId = intval($_GET['exam_id'] ?? 0);

$students = [];
$examInfo = null;

if ($examId) {
    // Verify exam belongs to teacher
    $examInfo = $db->getOne(
        "SELECT ex.*, c.course_name, c.course_code FROM exams ex
        JOIN courses c ON ex.course_id = c.id
        WHERE ex.id = ? AND c.teacher_id = ?",
        [$examId, $teacherId],
        'ii'
    );
    
    if ($examInfo) {
        // Get enrolled students for the exam's course
        $students = $db->select(
            "SELECT DISTINCT s.id, s.roll_number, s.first_name, s.last_name, s.email,
            COALESCE(r.marks_obtained, 0) as marks_obtained, 
            COALESCE(r.grade, '') as grade,
            r.id as result_id
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            WHERE e.course_id = ? AND s.deleted_at IS NULL
            LEFT JOIN results r ON s.id = r.student_id AND r.exam_id = ?
            ORDER BY s.roll_number",
            [$examInfo['course_id'], $examId],
            'ii'
        );
    }
}

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = intval($_POST['exam_id'] ?? 0);
    $grades = $_POST['grades'] ?? [];
    
    if ($examId === 0) {
        $errors[] = 'Invalid exam';
    }
    
    // Get exam details
    $exam = $db->getOne(
        "SELECT ex.* FROM exams ex
        JOIN courses c ON ex.course_id = c.id
        WHERE ex.id = ? AND c.teacher_id = ?",
        [$examId, $teacherId],
        'ii'
    );
    
    if (!$exam) {
        $errors[] = 'Exam not found or unauthorized';
    }
    
    if (empty($errors)) {
        try {
            $gradePoints = [
                'A+' => 4.0, 'A' => 4.0, 'B+' => 3.5, 'B' => 3.0,
                'C+' => 2.5, 'C' => 2.0, 'D+' => 1.5, 'D' => 1.0, 'F' => 0.0
            ];
            
            $savedCount = 0;
            
            foreach ($grades as $studentId => $marks) {
                if (!empty($marks) && $marks >= 0) {
                    $marks = floatval($marks);
                    
                    // Validate marks
                    if ($marks > $exam['total_marks']) {
                        continue;
                    }
                    
                    // Calculate grade
                    $percentage = ($marks / $exam['total_marks']) * 100;
                    $grade = 'F';
                    if ($percentage >= 80) $grade = 'A+';
                    elseif ($percentage >= 75) $grade = 'A';
                    elseif ($percentage >= 70) $grade = 'B+';
                    elseif ($percentage >= 65) $grade = 'B';
                    elseif ($percentage >= 60) $grade = 'C+';
                    elseif ($percentage >= 55) $grade = 'C';
                    elseif ($percentage >= 50) $grade = 'D+';
                    elseif ($percentage >= 45) $grade = 'D';
                    
                    // Check if result exists
                    $result = $db->getOne(
                        'results',
                        'exam_id = ? AND student_id = ?',
                        [$examId, $studentId],
                        'ii'
                    );
                    
                    $data = [
                        'marks_obtained' => $marks,
                        'grade' => $grade,
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($result) {
                        // Update existing result
                        $updated = $db->update('results', $data, 'id = ?', [$result['id']], 'i');
                        if ($updated !== false) $savedCount++;
                    } else {
                        // Insert new result
                        $data['student_id'] = intval($studentId);
                        $data['exam_id'] = $examId;
                        $data['total_marks'] = $exam['total_marks'];
                        $data['created_at'] = date('Y-m-d H:i:s');
                        $insertId = $db->insert('results', $data);
                        if ($insertId) $savedCount++;
                    }
                }
            }
            
            if ($savedCount > 0) {
                $_SESSION['success_message'] = 'Grades saved for ' . $savedCount . ' student(s)!';
            } else {
                $_SESSION['info_message'] = 'No grades were entered';
            }
            
            header("Location: grade-entry.php?exam_id=" . $examId);
            exit();
            
        } catch (Exception $e) {
            $errors[] = 'Error saving grades: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Grade Entry";
$additionalCSS = ['teacher.css'];
require_once '../../../includes/header.php';
?>

<div class="teacher-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="teacher-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="teacher-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-graduation-cap"></i> Grade Entry</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
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
            
            <!-- Select Exam -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Select Exam</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="form-row">
                        <div class="form-group col-md-8">
                            <label for="exam_id" class="form-label">Exam <span class="text-danger">*</span></label>
                            <select class="form-control" id="exam_id" name="exam_id" onchange="window.location.href='?exam_id=' + this.value;" required>
                                <option value="">-- Select Exam --</option>
                                <?php foreach ($exams as $exam): ?>
                                    <option value="<?php echo $exam['id']; ?>" <?php echo $examId == $exam['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($exam['exam_name'] . ' (' . $exam['course_code'] . ')'); ?>
                                        - <?php echo date('Y-m-d', strtotime($exam['exam_date'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($examId && $examInfo): ?>
                <!-- Exam Info -->
                <div class="alert alert-info mb-4">
                    <h5><?php echo htmlspecialchars($examInfo['exam_name']); ?></h5>
                    <p style="margin: 0.5rem 0 0 0;">
                        <strong>Course:</strong> <?php echo htmlspecialchars($examInfo['course_code'] . ' - ' . $examInfo['course_name']); ?> |
                        <strong>Total Marks:</strong> <?php echo $examInfo['total_marks']; ?> |
                        <strong>Exam Date:</strong> <?php echo date('Y-m-d H:i', strtotime($examInfo['exam_date'])); ?>
                    </p>
                </div>
                
                <?php if (!empty($students)): ?>
                    <form method="POST" class="card">
                        <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list"></i> Enter Grades
                                <span class="badge badge-primary"><?php echo count($students); ?> Students</span>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div style="overflow-x: auto;">
                                <table class="table table-hover">
                                    <thead style="background: #f8f9fa;">
                                        <tr>
                                            <th>Roll No.</th>
                                            <th>Student Name</th>
                                            <th>Email</th>
                                            <th>Marks (out of <?php echo $examInfo['total_marks']; ?>)</th>
                                            <th>%age</th>
                                            <th>Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): 
                                            $percentage = 0;
                                            if ($student['marks_obtained'] > 0) {
                                                $percentage = ($student['marks_obtained'] / $examInfo['total_marks']) * 100;
                                            }
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($student['roll_number']); ?></strong>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($student['email']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td>
                                                    <input type="number" class="form-control" name="grades[<?php echo $student['id']; ?>]" 
                                                           value="<?php echo $student['marks_obtained']; ?>" 
                                                           step="0.1" max="<?php echo $examInfo['total_marks']; ?>" min="0"
                                                           onchange="calculatePercentage(this, <?php echo $examInfo['total_marks']; ?>)">
                                                </td>
                                                <td>
                                                    <span class="percentage-display" data-student-id="<?php echo $student['id']; ?>">
                                                        <?php echo number_format($percentage, 2); ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="grade-display badge badge-info" data-student-id="<?php echo $student['id']; ?>">
                                                        <?php echo $student['grade'] ?: '-'; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div style="margin-top: 20px; display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save"></i> Save All Grades
                                </button>
                                <a href="index.php" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                <?php elseif ($examId): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No students enrolled in this exam's course.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const gradeScales = {
    'A+': { min: 80, color: 'success' },
    'A': { min: 75, color: 'success' },
    'B+': { min: 70, color: 'info' },
    'B': { min: 65, color: 'info' },
    'C+': { min: 60, color: 'warning' },
    'C': { min: 55, color: 'warning' },
    'D+': { min: 50, color: 'warning' },
    'D': { min: 45, color: 'warning' },
    'F': { min: 0, color: 'danger' }
};

function calculatePercentage(input, totalMarks) {
    const marks = parseFloat(input.value) || 0;
    const percentage = (marks / totalMarks) * 100;
    
    // Find grade
    let grade = 'F';
    for (const [g, scale] of Object.entries(gradeScales)) {
        if (percentage >= scale.min) {
            grade = g;
            break;
        }
    }
    
    // Update percentage display
    const studentId = input.name.match(/\d+/)[0];
    const percentageDisplay = document.querySelector(`[data-student-id="${studentId}"].percentage-display`);
    if (percentageDisplay) {
        percentageDisplay.textContent = percentage.toFixed(2) + '%';
    }
    
    // Update grade display
    const gradeDisplay = document.querySelector(`[data-student-id="${studentId}"].grade-display`);
    if (gradeDisplay) {
        gradeDisplay.textContent = grade;
        gradeDisplay.className = `grade-display badge badge-${gradeScales[grade].color}`;
    }
}

// Calculate on page load
document.addEventListener('DOMContentLoaded', function() {
    const totalMarks = <?php echo $examInfo['total_marks'] ?? 100; ?>;
    document.querySelectorAll('input[type="number"][name^="grades"]').forEach(input => {
        if (input.value) {
            calculatePercentage(input, totalMarks);
        }
    });
});
</script>

<?php require_once '../../../includes/footer.php'; ?>