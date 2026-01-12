<?php
// ============================================================
// FILE: modules/teacher/courses/my-courses.php
// ============================================================
?>
<?php
/**
 * My Courses - Teacher
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

// Get courses with statistics
$courses = $db->select(
    "SELECT c.*,
    COUNT(DISTINCT e.id) as enrolled_students,
    COUNT(DISTINCT a.id) as total_assignments,
    COUNT(DISTINCT ex.id) as total_exams,
    COUNT(DISTINCT att.id) as total_attendance_records,
    ROUND(AVG(CASE WHEN r.grade != 'F' THEN 1 ELSE 0 END) * 100, 2) as pass_rate
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN assignments a ON c.id = a.course_id
    LEFT JOIN exams ex ON c.id = ex.course_id
    LEFT JOIN attendance att ON c.id = att.course_id
    LEFT JOIN results r ON c.id = r.course_id
    WHERE c.teacher_id = ?
    GROUP BY c.id
    ORDER BY c.course_name",
    [$teacherId],
    'i'
);

// Get semester filter
$filterSemester = $_GET['semester'] ?? '';

// Get unique semesters
$semesters = $db->select(
    "SELECT DISTINCT semester FROM courses WHERE teacher_id = ? ORDER BY semester",
    [$teacherId],
    'i'
);

// Filter courses by semester if selected
$filteredCourses = $courses;
if ($filterSemester) {
    $filteredCourses = array_filter($courses, function($course) use ($filterSemester) {
        return $course['semester'] == $filterSemester;
    });
}

// Calculate overall statistics
$totalCourses = count($courses);
$totalEnrolledStudents = 0;
$totalAssignments = 0;
$totalExams = 0;

foreach ($courses as $course) {
    $totalEnrolledStudents += $course['enrolled_students'];
    $totalAssignments += $course['total_assignments'];
    $totalExams += $course['total_exams'];
}

$pageTitle = "My Courses";
$additionalCSS = ['teacher.css'];
require_once '../../../includes/header.php';
?>

<div class="teacher-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="teacher-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="teacher-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-book"></i> My Courses</h1>
            </div>
            
            <!-- Overall Statistics -->
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
                        <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-card-title">Total Students</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalEnrolledStudents; ?></div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-tasks"></i></div>
                        <div class="stat-card-title">Total Assignments</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalAssignments; ?></div>
                </div>
                
                <div class="stat-card purple">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-card-title">Total Exams</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalExams; ?></div>
                </div>
            </div>
            
            <!-- Semester Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="form-row">
                        <div class="form-group col-md-4">
                            <label for="semester" class="form-label">Filter by Semester</label>
                            <select id="semester" name="semester" class="form-control">
                                <option value="">All Semesters</option>
                                <?php foreach ($semesters as $sem): ?>
                                    <option value="<?php echo $sem['semester']; ?>" <?php echo $filterSemester == $sem['semester'] ? 'selected' : ''; ?>>
                                        Semester <?php echo $sem['semester']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-2" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Courses Grid -->
            <?php if (empty($filteredCourses)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No courses found for the selected criteria.
                </div>
            <?php else: ?>
                <div class="courses-grid">
                    <?php foreach ($filteredCourses as $course): ?>
                        <div class="course-card">
                            <div class="course-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <h5 style="margin: 0 0 0.5rem 0; color: white;">
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </h5>
                                <span style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($course['course_code']); ?>
                                </span>
                            </div>
                            
                            <div class="course-body">
                                <p style="color: #7f8c8d; margin-bottom: 1rem; line-height: 1.4;">
                                    <?php echo htmlspecialchars(substr($course['description'], 0, 80)) . '...'; ?>
                                </p>
                                
                                <div class="course-info" style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                        <div>
                                            <small style="color: #7f8c8d; text-transform: uppercase; font-weight: 600;">
                                                <i class="fas fa-users"></i> Students
                                            </small>
                                            <div style="font-size: 1.5rem; font-weight: bold; color: #3498db;">
                                                <?php echo $course['enrolled_students']; ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <small style="color: #7f8c8d; text-transform: uppercase; font-weight: 600;">
                                                <i class="fas fa-star"></i> Credits
                                            </small>
                                            <div style="font-size: 1.5rem; font-weight: bold; color: #2ecc71;">
                                                <?php echo $course['credits']; ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <small style="color: #7f8c8d; text-transform: uppercase; font-weight: 600;">
                                                <i class="fas fa-tasks"></i> Assignments
                                            </small>
                                            <div style="font-size: 1.5rem; font-weight: bold; color: #f39c12;">
                                                <?php echo $course['total_assignments']; ?>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <small style="color: #7f8c8d; text-transform: uppercase; font-weight: 600;">
                                                <i class="fas fa-file-alt"></i> Exams
                                            </small>
                                            <div style="font-size: 1.5rem; font-weight: bold; color: #e74c3c;">
                                                <?php echo $course['total_exams']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($course['pass_rate'] !== null): ?>
                                    <div style="background: #ecf0f1; padding: 0.5rem 1rem; border-radius: 4px; margin-bottom: 1rem;">
                                        <small style="color: #7f8c8d;">Pass Rate</small>
                                        <div class="progress" style="height: 20px; margin-top: 0.5rem;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo $course['pass_rate']; ?>%; background: #2ecc71;"
                                                 aria-valuenow="<?php echo $course['pass_rate']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <small style="color: white; font-weight: bold;">
                                                    <?php echo number_format($course['pass_rate'], 1); ?>%
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="course-footer" style="padding: 1rem; border-top: 1px solid #ecf0f1; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <a href="../attendance/take-attendance.php?course_id=<?php echo $course['id']; ?>" 
                                   class="btn btn-sm btn-primary" title="Take Attendance">
                                    <i class="fas fa-check-circle"></i> Attendance
                                </a>
                                <a href="../assignments/index.php?course_id=<?php echo $course['id']; ?>" 
                                   class="btn btn-sm btn-info" title="Assignments">
                                    <i class="fas fa-tasks"></i> Assignments
                                </a>
                                <a href="../examinations/index.php?course_id=<?php echo $course['id']; ?>" 
                                   class="btn btn-sm btn-warning" title="Exams">
                                    <i class="fas fa-file-alt"></i> Exams
                                </a>
                                <a href="view.php?course_id=<?php echo $course['id']; ?>" 
                                   class="btn btn-sm btn-secondary" title="View Details">
                                    <i class="fas fa-eye"></i> Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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

.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.course-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
}

.course-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
}

.course-header {
    padding: 1.5rem;
    color: white;
}

.course-body {
    padding: 1.5rem;
    flex: 1;
    overflow-y: auto;
}

.course-footer {
    margin-top: auto;
}
</style>

<?php require_once '../../../includes/footer.php'; ?>
