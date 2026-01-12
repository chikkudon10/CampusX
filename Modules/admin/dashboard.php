<?php
/**
 * Admin Dashboard
 * CampusX - College Management System
 */

// Load configuration
require_once '../../config/config.php';
require_once '../../config/constants.php';

// Load core classes
require_once '../../core/Session.php';
require_once '../../core/Database.php';

// Check authentication
Session::requireRole(ROLE_ADMIN);

// Load helpers
require_once '../../includes/functions.php';

// Initialize database
$db = new Database();

// Get dashboard statistics
$stats = [
    'total_students' => $db->count('students', 'status = ?', [STATUS_ACTIVE], 'i'),
    'total_teachers' => $db->count('teachers', 'status = ?', [STATUS_ACTIVE], 'i'),
    'total_courses' => $db->count('courses'),
    'pending_admissions' => $db->count('students', 'status = ?', [STATUS_PENDING], 'i')
];

// Get recent students
$recentStudents = $db->getAll(
    'students',
    'status = ?',
    [STATUS_ACTIVE],
    'i',
    'created_at DESC',
    '5'
);

// Get recent teachers
$recentTeachers = $db->getAll(
    'teachers',
    'status = ?',
    [STATUS_ACTIVE],
    'i',
    'created_at DESC',
    '5'
);

// Page settings
$pageTitle = "Admin Dashboard";
$additionalCSS = ['admin.css'];

// Header
require_once '../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <!-- Page Header -->
            <div class="page-header mb-4">
                <h1><i class="fas fa-home"></i> Dashboard</h1>
                <p>Welcome back, <?php echo Session::getUserName(); ?>!</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <!-- Total Students -->
                <div class="stat-card blue">
                    <div class="stat-card-header">
                        <div class="stat-card-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-card-title">Total Students</div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['total_students']); ?></div>
                    <div class="stat-card-footer">
                        <a href="students/" style="color: white; opacity: 0.9;">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Total Teachers -->
                <div class="stat-card green">
                    <div class="stat-card-header">
                        <div class="stat-card-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-card-title">Total Teachers</div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['total_teachers']); ?></div>
                    <div class="stat-card-footer">
                        <a href="teachers/" style="color: white; opacity: 0.9;">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Total Courses -->
                <div class="stat-card orange">
                    <div class="stat-card-header">
                        <div class="stat-card-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-card-title">Total Courses</div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['total_courses']); ?></div>
                    <div class="stat-card-footer">
                        <a href="courses/" style="color: white; opacity: 0.9;">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Pending Admissions -->
                <div class="stat-card red">
                    <div class="stat-card-header">
                        <div class="stat-card-icon">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="stat-card-title">Pending Admissions</div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($stats['pending_admissions']); ?></div>
                    <div class="stat-card-footer">
                        <a href="admissions/" style="color: white; opacity: 0.9;">
                            Review Now <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 2rem;">
                <!-- Recent Students -->
                <div class="data-section">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-graduate"></i> Recent Students</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentStudents)): ?>
                                <p class="text-center" style="color: #7f8c8d;">No students found</p>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Semester</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentStudents as $student): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td><?php echo getSemesterName($student['semester']); ?></td>
                                                <td>
                                                    <a href="students/view.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Teachers -->
                <div class="data-section">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chalkboard-teacher"></i> Recent Teachers</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentTeachers)): ?>
                                <p class="text-center" style="color: #7f8c8d;">No teachers found</p>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentTeachers as $teacher): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                                <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                                                <td>
                                                    <a href="teachers/view.php?id=<?php echo $teacher['id']; ?>" class="btn btn-sm btn-secondary">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
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
            
            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <a href="admissions/add.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> New Admission
                        </a>
                        <a href="teachers/add.php" class="btn btn-success">
                            <i class="fas fa-chalkboard-teacher"></i> Add Teacher
                        </a>
                        <a href="courses/add.php" class="btn btn-secondary">
                            <i class="fas fa-book"></i> Add Course
                        </a>
                        <a href="reports/" class="btn btn-warning">
                            <i class="fas fa-chart-bar"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>