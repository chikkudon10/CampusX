<?php
/**
 * Assign Teacher to Course - Admin
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../core/Validator.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_ADMIN);

$db = new Database();
$error = '';
$success = '';

// Get course ID if provided
$courseId = $_GET['course_id'] ?? null;
$selectedCourse = null;

if ($courseId) {
    $selectedCourse = $db->getById('courses', $courseId);
    if (!$selectedCourse) {
        setErrorMessage('Course not found');
        redirect('modules/admin/courses/');
        exit();
    }
}

// Get all active teachers
$teachers = $db->select(
    "SELECT t.*, 
            COUNT(c.id) as assigned_courses
     FROM teachers t
     LEFT JOIN courses c ON t.id = c.teacher_id
     WHERE t.status = ?
     GROUP BY t.id
     ORDER BY t.first_name, t.last_name",
    [STATUS_ACTIVE],
    'i'
);

// Get all courses
$courses = $db->select(
    "SELECT c.*, 
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            t.id as teacher_id
     FROM courses c
     LEFT JOIN teachers t ON c.teacher_id = t.id
     ORDER BY c.semester, c.course_code"
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'assign_single') {
            // Assign single teacher to course
            $validator = Validator::make($_POST, [
                'course_id' => 'required|integer',
                'teacher_id' => 'required|integer'
            ]);
            
            if ($validator->fails()) {
                $error = implode('<br>', $validator->getMessages());
            } else {
                $courseIdPost = $_POST['course_id'];
                $teacherIdPost = $_POST['teacher_id'];
                
                // Check if teacher exists
                $teacher = $db->getById('teachers', $teacherIdPost);
                if (!$teacher) {
                    $error = 'Selected teacher not found';
                } else {
                    // Update course
                    $updated = $db->updateRecord('courses', 
                        ['teacher_id' => $teacherIdPost], 
                        'id = ?', 
                        [$courseIdPost]
                    );
                    
                    if ($updated !== false) {
                        $course = $db->getById('courses', $courseIdPost);
                        logActivity(
                            Session::getUserId(), 
                            'course_assign', 
                            "Assigned teacher {$teacher['first_name']} {$teacher['last_name']} to course {$course['course_code']}"
                        );
                        setSuccessMessage('Teacher assigned successfully!');
                        redirect('modules/admin/courses/');
                        exit();
                    } else {
                        $error = 'Failed to assign teacher';
                    }
                }
            }
            
        } elseif ($action === 'unassign') {
            // Unassign teacher from course
            $courseIdPost = $_POST['course_id'] ?? 0;
            
            if ($courseIdPost) {
                $course = $db->getById('courses', $courseIdPost);
                
                $updated = $db->updateRecord('courses', 
                    ['teacher_id' => null], 
                    'id = ?', 
                    [$courseIdPost]
                );
                
                if ($updated !== false) {
                    logActivity(
                        Session::getUserId(), 
                        'course_unassign', 
                        "Unassigned teacher from course {$course['course_code']}"
                    );
                    setSuccessMessage('Teacher unassigned successfully!');
                    redirect('modules/admin/courses/assign.php');
                    exit();
                } else {
                    $error = 'Failed to unassign teacher';
                }
            }
            
        } elseif ($action === 'bulk_assign') {
            // Bulk assign courses to one teacher
            $teacherId = $_POST['bulk_teacher_id'] ?? 0;
            $courseIds = $_POST['course_ids'] ?? [];
            
            if (empty($courseIds)) {
                $error = 'Please select at least one course';
            } elseif (!$teacherId) {
                $error = 'Please select a teacher';
            } else {
                $teacher = $db->getById('teachers', $teacherId);
                if (!$teacher) {
                    $error = 'Selected teacher not found';
                } else {
                    $successCount = 0;
                    foreach ($courseIds as $cId) {
                        $updated = $db->updateRecord('courses', 
                            ['teacher_id' => $teacherId], 
                            'id = ?', 
                            [$cId]
                        );
                        if ($updated !== false) $successCount++;
                    }
                    
                    logActivity(
                        Session::getUserId(), 
                        'course_bulk_assign', 
                        "Bulk assigned {$successCount} courses to teacher {$teacher['first_name']} {$teacher['last_name']}"
                    );
                    setSuccessMessage("Successfully assigned {$successCount} course(s) to teacher!");
                    redirect('modules/admin/courses/assign.php');
                    exit();
                }
            }
        }
    }
}

$pageTitle = "Assign Teachers to Courses";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-user-plus"></i> Assign Teachers to Courses</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Courses
                </a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="stat-card-title">Total Teachers</div>
                    <div class="stat-card-value"><?php echo count($teachers); ?></div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-card-icon"><i class="fas fa-book"></i></div>
                    <div class="stat-card-title">Total Courses</div>
                    <div class="stat-card-value"><?php echo count($courses); ?></div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-card-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-card-title">Assigned Courses</div>
                    <div class="stat-card-value">
                        <?php 
                        $assignedCount = 0;
                        foreach ($courses as $c) {
                            if ($c['teacher_id']) $assignedCount++;
                        }
                        echo $assignedCount;
                        ?>
                    </div>
                </div>
                
                <div class="stat-card red">
                    <div class="stat-card-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="stat-card-title">Unassigned Courses</div>
                    <div class="stat-card-value"><?php echo count($courses) - $assignedCount; ?></div>
                </div>
            </div>
            
            <?php if ($selectedCourse): ?>
                <!-- Single Course Assignment -->
                <div class="card mb-4">
                    <div class="card-header" style="background: #3498db; color: white;">
                        <h3 class="card-title">
                            <i class="fas fa-user-plus"></i> 
                            Assign Teacher to: <?php echo htmlspecialchars($selectedCourse['course_code'] . ' - ' . $selectedCourse['course_name']); ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if ($selectedCourse['teacher_id']): ?>
                            <div class="alert alert-warning">
                                <strong>Current Teacher:</strong> 
                                <?php 
                                $currentTeacher = $db->getById('teachers', $selectedCourse['teacher_id']);
                                echo htmlspecialchars($currentTeacher['first_name'] . ' ' . $currentTeacher['last_name']);
                                ?>
                                <br><small>Selecting a new teacher will replace the current assignment.</small>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="assign_single">
                            <input type="hidden" name="course_id" value="<?php echo $selectedCourse['id']; ?>">
                            
                            <div class="form-group">
                                <label for="teacher_id" class="form-label">Select Teacher *</label>
                                <select id="teacher_id" name="teacher_id" class="form-control" required>
                                    <option value="">-- Select Teacher --</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>"
                                                <?php echo ($selectedCourse['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                                            <?php 
                                            echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']);
                                            echo ' - ' . ($teacher['qualification'] ?? 'N/A');
                                            echo ' (' . $teacher['assigned_courses'] . ' courses)';
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Assign Teacher
                                </button>
                                <a href="index.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Bulk Assignment -->
            <div class="card mb-4">
                <div class="card-header" style="background: #27ae60; color: white;">
                    <h3 class="card-title"><i class="fas fa-tasks"></i> Bulk Assignment</h3>
                </div>
                <div class="card-body">
                    <p>Assign multiple courses to one teacher at once.</p>
                    
                    <form method="POST" id="bulkAssignForm">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="bulk_assign">
                        
                        <div class="form-group">
                            <label for="bulk_teacher_id" class="form-label">Select Teacher *</label>
                            <select id="bulk_teacher_id" name="bulk_teacher_id" class="form-control" required>
                                <option value="">-- Select Teacher --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php 
                                        echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']);
                                        echo ' - ' . ($teacher['qualification'] ?? 'N/A');
                                        echo ' (Currently: ' . $teacher['assigned_courses'] . ' courses)';
                                        ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Select Courses *</label>
                            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; padding: 1rem;">
                                <?php
                                $coursesBySemester = [];
                                foreach ($courses as $course) {
                                    $coursesBySemester[$course['semester']][] = $course;
                                }
                                
                                foreach ($coursesBySemester as $sem => $semCourses):
                                ?>
                                    <div class="mb-3">
                                        <strong><?php echo getSemesterName($sem); ?></strong>
                                        <?php foreach ($semCourses as $course): ?>
                                            <div style="margin-left: 1rem; margin-top: 0.5rem;">
                                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                                    <input type="checkbox" name="course_ids[]" value="<?php echo $course['id']; ?>"
                                                           <?php echo $course['teacher_id'] ? 'data-has-teacher="true"' : ''; ?>>
                                                    <span>
                                                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                                        <?php if ($course['teacher_id']): ?>
                                                            <span class="badge badge-warning">
                                                                Already assigned to <?php echo htmlspecialchars($course['teacher_name']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-secondary">Unassigned</span>
                                                        <?php endif; ?>
                                                    </span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Courses already assigned to other teachers will be reassigned to the selected teacher.
                            </small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Assign Selected Courses
                            </button>
                            <button type="button" onclick="selectUnassignedOnly()" class="btn btn-secondary">
                                Select Unassigned Only
                            </button>
                            <button type="button" onclick="clearSelection()" class="btn btn-warning">
                                Clear Selection
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Current Assignments Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Current Assignments</h3>
                    <div>
                        <input type="text" id="tableSearch" class="form-control" placeholder="Search courses...">
                    </div>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Semester</th>
                                <th>Credits</th>
                                <th>Assigned Teacher</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td><?php echo getSemesterName($course['semester']); ?></td>
                                    <td><?php echo $course['credits']; ?></td>
                                    <td>
                                        <?php if ($course['teacher_id']): ?>
                                            <span class="badge badge-success">
                                                <?php echo htmlspecialchars($course['teacher_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="assign.php?course_id=<?php echo $course['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Assign/Change">
                                                <i class="fas fa-user-plus"></i>
                                            </a>
                                            <?php if ($course['teacher_id']): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Remove teacher assignment from this course?')">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="action" value="unassign">
                                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Unassign">
                                                        <i class="fas fa-user-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectUnassignedOnly() {
    const checkboxes = document.querySelectorAll('input[name="course_ids[]"]');
    checkboxes.forEach(cb => {
        cb.checked = !cb.hasAttribute('data-has-teacher');
    });
}

function clearSelection() {
    const checkboxes = document.querySelectorAll('input[name="course_ids[]"]');
    checkboxes.forEach(cb => {
        cb.checked = false;
    });
}

// Validation before bulk assign
document.getElementById('bulkAssignForm')?.addEventListener('submit', function(e) {
    const teacherId = document.getElementById('bulk_teacher_id').value;
    const checkedBoxes = document.querySelectorAll('input[name="course_ids[]"]:checked');
    
    if (!teacherId) {
        alert('Please select a teacher');
        e.preventDefault();
        return false;
    }
    
    if (checkedBoxes.length === 0) {
        alert('Please select at least one course');
        e.preventDefault();
        return false;
    }
    
    const hasAssigned = Array.from(checkedBoxes).some(cb => cb.hasAttribute('data-has-teacher'));
    if (hasAssigned) {
        return confirm(`${checkedBoxes.length} course(s) will be assigned. Some courses are already assigned to other teachers. Do you want to continue?`);
    }
    
    return confirm(`Assign ${checkedBoxes.length} course(s) to the selected teacher?`);
});
</script>

<?php require_once '../../../includes/footer.php'; ?>