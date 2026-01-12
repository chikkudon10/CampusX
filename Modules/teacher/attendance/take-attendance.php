<?php
/**
 * Take Attendance - Teacher
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
$success = false;s

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

// Get students for selected course
$students = [];
$selectedCourse = $_GET['course_id'] ?? $_POST['course_id'] ?? '';
$courseInfo = null;

if ($selectedCourse) {
    // Verify course belongs to teacher
    $courseInfo = $db->getOne(
        'courses',
        'id = ? AND teacher_id = ?',
        [$selectedCourse, $teacherId],
        'ii'
    );
    
    if ($courseInfo) {
        $students = $db->select(
            "SELECT s.id, s.roll_number, s.first_name, s.last_name, s.email
            FROM enrollments e
            JOIN students s ON e.student_id = s.id
            WHERE e.course_id = ? AND s.deleted_at IS NULL
            ORDER BY s.roll_number",
            [$selectedCourse],
            'i'
        );
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = intval($_POST['course_id'] ?? 0);
    $attendanceDate = $_POST['attendance_date'] ?? date('Y-m-d');
    $attendanceTime = $_POST['attendance_time'] ?? date('H:i:s');
    $attendanceData = $_POST['attendance'] ?? [];
    
    // Validations
    if ($courseId === 0) {
        $errors[] = 'Please select a course';
    } else {
        // Verify course belongs to teacher
        $verify = $db->getOne('courses', 'id = ? AND teacher_id = ?', [$courseId, $teacherId], 'ii');
        if (!$verify) {
            $errors[] = 'Invalid course selection';
        }
    }
    
    if (empty($attendanceDate)) {
        $errors[] = 'Please select an attendance date';
    }
    
    if (empty($attendanceData)) {
        $errors[] = 'Please mark attendance for at least one student';
    }
    
    // Count if all students have attendance marked
    $markedCount = 0;
    foreach ($attendanceData as $studentId => $status) {
        if (!empty($status)) {
            $markedCount++;
        }
    }
    
    if ($markedCount === 0) {
        $errors[] = 'Please mark attendance for at least one student';
    }
    
    if (empty($errors)) {
        try {
            $attendanceDateTime = $attendanceDate . ' ' . $attendanceTime;
            
            foreach ($attendanceData as $studentId => $status) {
                if (!empty($status)) {
                    // Check if attendance already exists for this date
                    $existingAttendance = $db->getOne(
                        'attendance',
                        'student_id = ? AND course_id = ? AND DATE(attendance_date) = ?',
                        [$studentId, $courseId, $attendanceDate],
                        'iis'
                    );
                    
                    $data = [
                        'student_id' => intval($studentId),
                        'course_id' => intval($courseId),
                        'attendance_date' => $attendanceDateTime,
                        'status' => htmlspecialchars($status),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if ($existingAttendance) {
                        // Update existing attendance
                        $db->update(
                            'attendance',
                            $data,
                            'id = ?',
                            [$existingAttendance['id']],
                            'i'
                        );
                    } else {
                        // Insert new attendance
                        $data['created_at'] = date('Y-m-d H:i:s');
                        $db->insert('attendance', $data);
                    }
                }
            }
            
            $_SESSION['success_message'] = 'Attendance recorded successfully for ' . $markedCount . ' student(s)!';
            header('Location: index.php');
            exit();
            
        } catch (Exception $e) {
            $errors[] = 'Failed to record attendance: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Take Attendance";
$additionalCSS = ['teacher.css'];
require_once '../../../includes/header.php';
?>

<div class="teacher-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="teacher-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="teacher-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-clipboard-list"></i> Take Attendance</h1>
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
            
            <form method="POST">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">Select Course & Date</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
                                <select id="course_id" name="course_id" class="form-control" required onchange="location.href='?course_id=' + this.value;">
                                    <option value="">-- Select Course --</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" <?php echo $selectedCourse == $course['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Select the course to take attendance</small>
                            </div>
                            
                            <div class="form-group col-md-3">
                                <label for="attendance_date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" id="attendance_date" name="attendance_date" class="form-control" 
                                       value="<?php echo $_POST['attendance_date'] ?? date('Y-m-d'); ?>" required>
                                <small class="form-text text-muted">Date of class</small>
                            </div>
                            
                            <div class="form-group col-md-3">
                                <label for="attendance_time" class="form-label">Time <span class="text-danger">*</span></label>
                                <input type="time" id="attendance_time" name="attendance_time" class="form-control" 
                                       value="<?php echo $_POST['attendance_time'] ?? date('H:i'); ?>" required>
                                <small class="form-text text-muted">Time of class</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($selectedCourse && !empty($students)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Mark Attendance - <?php echo htmlspecialchars($courseInfo['course_name'] ?? ''); ?></h3>
                            <small class="text-muted">Total Students: <?php echo count($students); ?></small>
                        </div>
                        <div class="card-body">
                            <div class="attendance-controls mb-3">
                                <button type="button" class="btn btn-sm btn-success" onclick="markAllPresent()">
                                    <i class="fas fa-check"></i> Mark All Present
                                </button>
                                <button type="button" class="btn btn-sm btn-warning" onclick="clearAll()">
                                    <i class="fas fa-eraser"></i> Clear All
                                </button>
                            </div>
                            
                            <div style="overflow-x: auto;">
                                <table class="table table-bordered">
                                    <thead style="background: #f8f9fa;">
                                        <tr>
                                            <th style="width: 15%;">Roll No.</th>
                                            <th style="width: 40%;">Student Name</th>
                                            <th style="width: 45%;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($student['roll_number']); ?></strong>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($student['email']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <input type="radio" class="btn-check" 
                                                               name="attendance[<?php echo $student['id']; ?>]" 
                                                               id="present_<?php echo $student['id']; ?>" 
                                                               value="Present">
                                                        <label class="btn btn-outline-success" for="present_<?php echo $student['id']; ?>">
                                                            <i class="fas fa-check"></i> Present
                                                        </label>
                                                        
                                                        <input type="radio" class="btn-check" 
                                                               name="attendance[<?php echo $student['id']; ?>]" 
                                                               id="late_<?php echo $student['id']; ?>" 
                                                               value="Late">
                                                        <label class="btn btn-outline-warning" for="late_<?php echo $student['id']; ?>">
                                                            <i class="fas fa-clock"></i> Late
                                                        </label>
                                                        
                                                        <input type="radio" class="btn-check" 
                                                               name="attendance[<?php echo $student['id']; ?>]" 
                                                               id="absent_<?php echo $student['id']; ?>" 
                                                               value="Absent">
                                                        <label class="btn btn-outline-danger" for="absent_<?php echo $student['id']; ?>">
                                                            <i class="fas fa-times"></i> Absent
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div style="margin-top: 20px; display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save"></i> Save Attendance
                                </button>
                                <a href="index.php" class="btn btn-secondary btn-lg">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                <?php elseif ($selectedCourse && empty($students)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No students enrolled in this course yet.
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
function markAllPresent() {
    const radioButtons = document.querySelectorAll('input[type="radio"][value="Present"]');
    radioButtons.forEach(radio => radio.checked = true);
}

function clearAll() {
    const radioButtons = document.querySelectorAll('input[type="radio"]');
    radioButtons.forEach(radio => radio.checked = false);
}
</script>

<?php require_once '../../../includes/footer.php'; ?>