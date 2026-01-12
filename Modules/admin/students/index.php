<?php
/**
 * Students List - Admin
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_ADMIN);

$db = new Database();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = RECORDS_PER_PAGE;

// Get total count
$totalStudents = $db->count('students', 'status = ?', [STATUS_ACTIVE], 'i');
$pagination = paginate($totalStudents, $page, $recordsPerPage);

// Get students
$students = $db->select(
    "SELECT * FROM students 
     WHERE status = ? 
     ORDER BY created_at DESC 
     LIMIT ? OFFSET ?",
    [STATUS_ACTIVE, $recordsPerPage, $pagination['offset']],
    'iii'
);

$pageTitle = "Students Management";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-user-graduate"></i> Students Management</h1>
                <div class="d-flex gap-2">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Student
                    </a>
                    <button onclick="printElement('studentsTable')" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button onclick="exportTableToCSV('studentsTable', 'students.csv')" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Export
                    </button>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid mb-4">
                <div class="stat-card blue">
                    <div class="stat-card-header">
                        <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-card-title">Total Students</div>
                    </div>
                    <div class="stat-card-value"><?php echo $totalStudents; ?></div>
                </div>
                
                <?php for ($sem = 1; $sem <= 3; $sem++): ?>
                    <?php $semCount = $db->count('students', 'semester = ? AND status = ?', [$sem, STATUS_ACTIVE], 'ii'); ?>
                    <div class="stat-card <?php echo ['green', 'orange', 'red'][$sem-1]; ?>">
                        <div class="stat-card-header">
                            <div class="stat-card-icon"><i class="fas fa-book-reader"></i></div>
                            <div class="stat-card-title"><?php echo getSemesterName($sem); ?></div>
                        </div>
                        <div class="stat-card-value"><?php echo $semCount; ?></div>
                    </div>
                <?php endfor; ?>
            </div>
            
            <!-- Students Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">All Students</h3>
                    <div>
                        <input type="text" id="tableSearch" class="form-control" placeholder="Search students...">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($students)): ?>
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-user-graduate" style="font-size: 4rem; color: #ddd;"></i>
                            <h3 style="color: #7f8c8d; margin-top: 1rem;">No students found</h3>
                            <a href="add.php" class="btn btn-primary mt-3">
                                <i class="fas fa-plus"></i> Add First Student
                            </a>
                        </div>
                    <?php else: ?>
                        <table class="table" id="studentsTable">
                            <thead>
                                <tr>
                                    <th>Roll No.</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Semester</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($student['roll_number']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo getSemesterName($student['semester']); ?></td>
                                        <td>
                                            <span class="badge badge-success">Active</span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="view.php?id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-sm btn-secondary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete.php?id=<?php echo $student['id']; ?>" 
                                                   class="btn btn-sm btn-danger btn-delete" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <?php displayPagination($pagination, 'index.php'); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>