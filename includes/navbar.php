<?php
/**
 * Navigation Bar
 * CampusX - College Management System
 */

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('modules/authentication/login.php');
    exit();
}

$userRole = $_SESSION['user_role'] ?? '';
$userName = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
?>

<nav class="navbar">
    <div class="navbar-container">
        <!-- Logo and Brand -->
        <div class="navbar-brand">
            <img src="<?php echo ASSETS_PATH; ?>images/logo/logo.png" alt="Logo" class="navbar-logo">
            <div class="brand-text">
                <h3><?php echo APP_NAME; ?></h3>
                <p><?php echo INSTITUTION_NAME; ?></p>
            </div>
        </div>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggle" id="navbarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <!-- Navigation Links -->
        <div class="navbar-menu" id="navbarMenu">
            <ul class="navbar-nav">
                <?php if ($userRole === ROLE_ADMIN): ?>
                    <li><a href="<?php echo BASE_URL; ?>modules/admin/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>modules/admin/students/"><i class="fas fa-user-graduate"></i> Students</a></li>
                    <li><a href="<?php echo BASE_URL; ?>modules/admin/teachers/"><i class="fas fa-chalkboard-teacher"></i> Teachers</a></li>
                    <li><a href="<?php echo BASE_URL; ?>modules/admin/courses/"><i class="fas fa-book"></i> Courses</a></li>
                    <li><a href="<?php echo BASE_URL; ?>modules/admin/reports/"><i class="fas fa-chart-bar"></i> Reports</a></li>
                
                <?php elseif ($userRole === ROLE_TEACHER): ?>
                    <li><a href="<?php echo BASE_URL; ?>modules/teacher/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>modules/teacher/courses/"><i class="fas fa-book"></i> My Courses</a></li>
                    <li><a href="<?php echo BASE_URL; ?>modules/teacher/attendance/"><i class="fas fa-clipboard-check"></i> Attendance</a></li>
                    <li><a href="<?php echo BASE_URL; ?>modules/teacher/assignments/"><i class="fas fa-tasks"></i> Assignments</a></li>
                    <li><a href="<?php echo BASE_URL; ?>modules/teacher/examinations/"><i class="fas fa-file-alt"></i> Examinations</a></li>
                
                <?php elseif ($userRole === ROLE_STUDENT): ?>
                    <li><a href="<?php echo BASE_URL; ?>modules/student/dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>modules/student/attendance/"><i class="fas fa-clipboard-check"></i> Attendance</a></li>
                    <li><a href="<?php echo BASE_URL; ?>modules/student/assignments/"><i class="fas fa-tasks"></i> Assignments</a></li>
                    <li><a href="<?php echo BASE_URL; ?>modules/student/results/"><i class="fas fa-trophy"></i> Results</a></li>
                    <li><a href="<?php echo BASE_URL; ?>modules/student/leave/"><i class="fas fa-calendar-times"></i> Leave</a></li>
                <?php endif; ?>
            </ul>

            <!-- User Profile Dropdown -->
            <div class="navbar-user">
                <div class="user-dropdown">
                    <button class="user-dropdown-toggle">
                        <img src="<?php echo ASSETS_PATH; ?>images/profile/default-avatar.png" alt="Profile" class="user-avatar">
                        <span><?php echo htmlspecialchars($userName); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown-menu">
                        <div class="dropdown-header">
                            <strong><?php echo htmlspecialchars($userName); ?></strong>
                            <small><?php echo htmlspecialchars($userEmail); ?></small>
                            <span class="badge badge-<?php echo $userRole; ?>"><?php echo ucfirst($userRole); ?></span>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo BASE_URL; ?>modules/<?php echo $userRole; ?>/profile/view.php">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="<?php echo BASE_URL; ?>modules/<?php echo $userRole; ?>/profile/edit.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?php echo BASE_URL; ?>modules/authentication/logout.php" class="text-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
.navbar {
    background-color: #2c3e50;
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    min-height: 70px;
}

.navbar-brand {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.navbar-logo {
    width: 50px;
    height: 50px;
    object-fit: contain;
}

.brand-text h3 {
    color: #ffffff;
    margin: 0;
    font-size: 1.25rem;
}

.brand-text p {
    color: #95a5a6;
    margin: 0;
    font-size: 0.75rem;
}

.navbar-toggle {
    display: none;
    background: none;
    border: none;
    color: #ffffff;
    font-size: 1.5rem;
    cursor: pointer;
}

.navbar-menu {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.navbar-nav {
    display: flex;
    list-style: none;
    gap: 0.5rem;
    margin: 0;
    padding: 0;
}

.navbar-nav li a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    color: #ecf0f1;
    text-decoration: none;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.navbar-nav li a:hover,
.navbar-nav li a.active {
    background-color: #34495e;
    color: #3498db;
}

/* User Dropdown */
.user-dropdown {
    position: relative;
}

.user-dropdown-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: none;
    border: none;
    color: #ffffff;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: background-color 0.3s ease;
}

.user-dropdown-toggle:hover {
    background-color: #34495e;
}

.user-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    object-fit: cover;
}

.user-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    min-width: 250px;
    display: none;
    z-index: 1000;
}

.user-dropdown:hover .user-dropdown-menu {
    display: block;
}

.dropdown-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.dropdown-header strong {
    display: block;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.dropdown-header small {
    display: block;
    color: #6c757d;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.dropdown-divider {
    height: 1px;
    background-color: #e9ecef;
    margin: 0.5rem 0;
}

.user-dropdown-menu a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: #2c3e50;
    text-decoration: none;
    transition: background-color 0.3s ease;
}

.user-dropdown-menu a:hover {
    background-color: #f8f9fa;
}

.user-dropdown-menu a.text-danger {
    color: #e74c3c;
}

.badge-admin { background-color: #e74c3c; }
.badge-teacher { background-color: #3498db; }
.badge-student { background-color: #27ae60; }

/* Responsive */
@media (max-width: 968px) {
    .navbar-toggle {
        display: block;
    }
    
    .navbar-menu {
        position: fixed;
        top: 70px;
        left: -100%;
        width: 250px;
        height: calc(100vh - 70px);
        background-color: #2c3e50;
        flex-direction: column;
        align-items: flex-start;
        padding: 1rem;
        transition: left 0.3s ease;
        overflow-y: auto;
    }
    
    .navbar-menu.active {
        left: 0;
    }
    
    .navbar-nav {
        flex-direction: column;
        width: 100%;
    }
    
    .navbar-nav li {
        width: 100%;
    }
    
    .navbar-nav li a {
        width: 100%;
    }
}
</style>

<script>
// Mobile menu toggle
document.getElementById('navbarToggle')?.addEventListener('click', function() {
    document.getElementById('navbarMenu').classList.toggle('active');
});

// Close mobile menu when clicking outside
document.addEventListener('click', function(e) {
    const navbar = document.querySelector('.navbar-menu');
    const toggle = document.getElementById('navbarToggle');
    
    if (navbar && toggle && !navbar.contains(e.target) && !toggle.contains(e.target)) {
        navbar.classList.remove('active');
    }
});
</script>