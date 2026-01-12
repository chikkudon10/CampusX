<?php
/**
 * Teachers List - Admin
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_ADMIN);

$db = new Database();

// Get all active teachers
$teachers = $db->getAll('teachers', 'status = ?', [STATUS_ACTIVE], 'i', 'created_at DESC');

// Get statistics
$totalTeachers = count($teachers);
$teachersWithCourses = $db->query("SELECT COUNT(DISTINCT teacher_id) as count FROM courses WHERE teacher_id IS NOT NULL")->fetch_assoc()['count'] ?? 0;

$pageTitle = "Teachers Management";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-chalkboard-teacher"></i> Teachers Management</h1>
                <div class="d-flex gap-2">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Teacher
                    </a>
                    <button onclick="exportTableToCSV('teachersTable', 'teachers.csv')" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Export
                    </button>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-card-title">Total Teachers</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalTeachers; ?></div>
                </div>
                
                <div class="stat-card green">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-book"></i></div>
                        <div class="stat-card-title">Assigned Teachers</div>
                    </div>
                    <div class="stat-card-value"><?php echo $teachersWithCourses; ?></div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-user-clock"></i></div>
                        <div class="stat-card-title">Unassigned</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalTeachers - $teachersWithCourses; ?></div>
                </div>
            </div>
            
            <!-- Teachers Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Teachers</h3>
                    <div>
                        <input type="text" id="tableSearch" class="form-control" placeholder="Search teachers...">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($teachers)): ?>
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-chalkboard-teacher" style="font-size: 4rem; color: #ddd;"></i>
                            <h3 style="color: #7f8c8d; margin-top: 1rem;">No teachers found</h3>
                            <a href="add.php" class="btn btn-primary mt-3">
                                <i class="fas fa-plus"></i> Add First Teacher
                            </a>
                        </div>
                    <?php else: ?>
                        <table class="table" id="teachersTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Qualification</th>
                                    <th>Experience</th>
                                    <th>Courses</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teachers as $teacher): ?>
                                    <?php
                                    // Get assigned courses count
                                    $courseCount = $db->count('courses', 'teacher_id = ?', [$teacher['id']], 'i');
                                    ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                            </strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($teacher['qualification'] ?? 'N/A'); ?></td>
                                        <td><?php echo ($teacher['experience'] ?? 0) . ' years'; ?></td>
                                        <td>
                                            <?php if ($courseCount > 0): ?>
                                                <span class="badge badge-success"><?php echo $courseCount; ?> course(s)</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="view.php?id=<?php echo $teacher['id']; ?>" 
                                                   class="btn btn-sm btn-secondary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $teacher['id']; ?>" 
                                                   class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $teacher['id']; ?>" 
                                                   class="btn btn-sm btn-danger btn-delete" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
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