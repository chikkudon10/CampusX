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
    "SELECT c.id, c.course_code, c.course_name FROM enrollments e
    JOIN courses c ON e.course_id = c.id WHERE e.student_id = ? ORDER BY c.course_name",
    [$studentId], 'i'
);

$whereClause = "e.student_id = ?";
$params = [$studentId];
$types = 'i';

if ($courseId) {
    $whereClause .= " AND c.id = ?";
    $params[] = $courseId;
    $types .= 'i';
}

$exams = $db->select(
    "SELECT ex.*, c.course_code, c.course_name, t.first_name as tf, t.last_name as tl,
    r.marks_obtained, r.grade, r.id as result_id FROM exams ex
    JOIN courses c ON ex.course_id = c.id
    JOIN teachers t ON c.teacher_id = t.id
    JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN results r ON ex.id = r.exam_id AND r.student_id = ?
    WHERE $whereClause ORDER BY ex.exam_date DESC",
    array_merge([$studentId], $params), 'i' . $types
);

$pageTitle = "Examinations";
require_once '../../../includes/header.php';
?>

<div class="student-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    <div class="student-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="student-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-file-alt"></i> My Exams</h1>
                <a href="../dashboard.php" class="btn btn-secondary">Back</a>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="form-row">
                        <div class="form-group col-md-6">
                            <label>Course</label>
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
                    <?php if (empty($exams)): ?>
                        <div class="text-center text-muted" style="padding: 3rem;">
                            <p>No exams scheduled</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th>Exam</th>
                                    <th>Course</th>
                                    <th>Teacher</th>
                                    <th>Date</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($exams as $exam): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($exam['exam_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($exam['course_code']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['tf'] . ' ' . $exam['tl']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($exam['exam_date'])); ?></td>
                                        <td>
                                            <?php echo $exam['marks_obtained'] ? ($exam['marks_obtained'] . '/' . $exam['total_marks']) : '-'; ?>
                                        </td>
                                        <td>
                                            <?php if ($exam['grade']): ?>
                                                <span class="badge badge-info"><?php echo $exam['grade']; ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
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

<?php require_once '../../../includes/footer.php'; ?>
