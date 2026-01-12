<?php
/**
 * Courses List - Admin
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_ADMIN);

$db = new Database();

// Get all courses with teacher info
$courses = $db->select(
    "SELECT c.*, 
            CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
            COUNT(DISTINCT sc.student_id) as enrolled_students
     FROM courses c
     LEFT JOIN teachers t ON c.teacher_id = t.id
     LEFT JOIN student_courses sc ON c.id = sc.course_id
     GROUP BY c.id
     ORDER BY c.semester, c.course_code"
);

// Get statistics
$totalCourses = count($courses);
$assignedCourses = 0;
$totalEnrollments = 0;

foreach ($courses as $course) {
    if ($course['teacher_id']) $assignedCourses++;
    $totalEnrollments += $course['enrolled_students'];
}

$pageTitle = "Courses Management";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-book"></i> Courses Management</h1>
                <div class="d-flex gap-2">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Course
                    </a>
                    <a href="assign.php" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Assign Teacher
                    </a>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-book"></i></div>
                        <div class="stat-card-title">Total Courses</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalCourses; ?></div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="stat-card-title">Assigned Courses</div>
                    </div>
                    <div class="stat-card-value"><?php echo $assignedCourses; ?></div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-user-graduate"></i></div>
                        <div class="stat-card-title">Total Enrollments</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalEnrollments; ?></div>
                </div>
            </div>
            
            <!-- Courses by Semester -->
            <?php
            $coursesBySemester = [];
            foreach ($courses as $course) {
                $coursesBySemester[$course['semester']][] = $course;
            }
            ?>
            
            <?php foreach ($coursesBySemester as $semester => $semesterCourses): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-book-reader"></i> <?php echo getSemesterName($semester); ?>
                        </h3>
                        <span class="badge badge-primary"><?php echo count($semesterCourses); ?> courses</span>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Course Name</th>
                                    <th>Credits</th>
                                    <th>Teacher</th>
                                    <th>Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($semesterCourses as $course): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($course['course_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                        <td><?php echo $course['credits']; ?></td>
                                        <td>
                                            <?php if ($course['teacher_name']): ?>
                                                <span class="badge badge-success">
                                                    <?php echo htmlspecialchars($course['teacher_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo $course['enrolled_students']; ?> students
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit.php?id=<?php echo $course['id']; ?>" 
                                                   class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="assign.php?course_id=<?php echo $course['id']; ?>" 
                                                   class="btn btn-sm btn-success" title="Assign Teacher">
                                                    <i class="fas fa-user-plus"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $course['id']; ?>" 
                                                   class="btn btn-sm btn-danger btn-delete" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($courses)): ?>
                <div class="card">
                    <div class="card-body text-center" style="padding: 3rem;">
                        <i class="fas fa-book" style="font-size: 4rem; color: #ddd;"></i>
                        <h3 style="color: #7f8c8d; margin-top: 1rem;">No courses found</h3>
                        <a href="add.php" class="btn btn-primary mt-3">
                            <i class="fas fa-plus"></i> Add First Course
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>