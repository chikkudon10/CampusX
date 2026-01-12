<?php
/**
 * Sidebar Navigation (For Admin Dashboard)
 * CampusX - College Management System
 */

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('modules/authentication/login.php');
    exit();
}

$userRole = $_SESSION['user_role'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="admin-sidebar" id="adminSidebar">
    <!-- Logo Section -->
    <div class="logo">
        <h2><?php echo APP_NAME; ?></h2>
        <p><?php echo ucfirst($userRole); ?> Panel</p>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <ul>
            <?php if ($userRole === ROLE_ADMIN): ?>
                <!-- Admin Menu Items -->
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/admin/dashboard.php" 
                       class="<?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/admin/admissions/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admissions') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i>
                        <span>Admissions</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/admin/students/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'students') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-user-graduate"></i>
                        <span>Students</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/admin/teachers/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'teachers') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Teachers</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/admin/courses/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'courses') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i>
                        <span>Courses</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/admin/reports/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'reports') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                </li>

            <?php elseif ($userRole === ROLE_TEACHER): ?>
                <!-- Teacher Menu Items -->
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/teacher/dashboard.php" 
                       class="<?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/teacher/courses/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'courses') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i>
                        <span>My Courses</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/teacher/attendance/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'attendance') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Attendance</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/teacher/assignments/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'assignments') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i>
                        <span>Assignments</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/teacher/examinations/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'examinations') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>Examinations</span>
                    </a>
                </li>

            <?php elseif ($userRole === ROLE_STUDENT): ?>
                <!-- Student Menu Items -->
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/student/dashboard.php" 
                       class="<?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/student/attendance/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'attendance') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-check"></i>
                        <span>My Attendance</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/student/assignments/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'assignments') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-tasks"></i>
                        <span>Assignments</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/student/examinations/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'examinations') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>Examinations</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/student/results/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'results') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-trophy"></i>
                        <span>Results</span>
                    </a>
                </li>
                
                <li>
                    <a href="<?php echo BASE_URL; ?>modules/student/leave/" 
                       class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'leave') !== false) ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-times"></i>
                        <span>Leave Application</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- Profile (Common for all) -->
            <li>
                <a href="<?php echo BASE_URL; ?>modules/<?php echo $userRole; ?>/profile/view.php" 
                   class="<?php echo (strpos($_SERVER['REQUEST_URI'], 'profile') !== false) ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
            </li>

            <!-- Logout -->
            <li>
                <a href="<?php echo BASE_URL; ?>modules/authentication/logout.php" class="text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>

<!-- Mobile Sidebar Toggle Button -->
<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>

<script>
// Sidebar toggle for mobile
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('adminSidebar').classList.toggle('active');
});
</script>