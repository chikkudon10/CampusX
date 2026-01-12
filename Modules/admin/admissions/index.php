<?php
/**
 * Admissions List - Admin
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_ADMIN);

$db = new Database();

// Get filter
$filter = $_GET['filter'] ?? 'pending';

// Build query based on filter
$whereClause = '';
$params = [];
$types = '';

switch ($filter) {
    case 'approved':
        $whereClause = 'status = ?';
        $params = [STATUS_ACTIVE];
        $types = 'i';
        break;
    case 'rejected':
        $whereClause = 'status = ?';
        $params = [STATUS_INACTIVE];
        $types = 'i';
        break;
    default: // pending
        $whereClause = 'status = ?';
        $params = [STATUS_PENDING];
        $types = 'i';
        break;
}

// Get admissions
$admissions = $db->getAll('students', $whereClause, $params, $types, 'created_at DESC');

// Get counts for tabs
$pendingCount = $db->count('students', 'status = ?', [STATUS_PENDING], 'i');
$approvedCount = $db->count('students', 'status = ?', [STATUS_ACTIVE], 'i');
$rejectedCount = $db->count('students', 'status = ?', [STATUS_INACTIVE], 'i');

$pageTitle = "Admissions Management";
$additionalCSS = ['admin.css'];
require_once '../../../includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="admin-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-user-plus"></i> Admissions Management</h1>
                <div class="d-flex gap-2">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Admission
                    </a>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="card mb-4">
                <div class="card-body">
                    <div style="display: flex; gap: 1rem; border-bottom: 2px solid #e9ecef; padding-bottom: 1rem;">
                        <a href="?filter=pending" class="btn <?php echo $filter == 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">
                            <i class="fas fa-clock"></i> Pending (<?php echo $pendingCount; ?>)
                        </a>
                        <a href="?filter=approved" class="btn <?php echo $filter == 'approved' ? 'btn-success' : 'btn-secondary'; ?>">
                            <i class="fas fa-check"></i> Approved (<?php echo $approvedCount; ?>)
                        </a>
                        <a href="?filter=rejected" class="btn <?php echo $filter == 'rejected' ? 'btn-danger' : 'btn-secondary'; ?>">
                            <i class="fas fa-times"></i> Rejected (<?php echo $rejectedCount; ?>)
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Admissions Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <?php 
                        echo ucfirst($filter) . ' Admissions';
                        ?>
                    </h3>
                    <div>
                        <input type="text" id="tableSearch" class="form-control" placeholder="Search...">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($admissions)): ?>
                        <div class="text-center" style="padding: 3rem;">
                            <i class="fas fa-inbox" style="font-size: 4rem; color: #ddd;"></i>
                            <h3 style="color: #7f8c8d; margin-top: 1rem;">No admissions found</h3>
                        </div>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>DOB</th>
                                    <th>Gender</th>
                                    <th>Applied Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admissions as $admission): ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <?php echo htmlspecialchars($admission['first_name'] . ' ' . $admission['last_name']); ?>
                                            </strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($admission['email']); ?></td>
                                        <td><?php echo htmlspecialchars($admission['phone'] ?? 'N/A'); ?></td>
                                        <td><?php echo $admission['date_of_birth'] ? formatDate($admission['date_of_birth']) : 'N/A'; ?></td>
                                        <td>
                                            <?php 
                                            $genders = ['M' => 'Male', 'F' => 'Female', 'O' => 'Other'];
                                            echo $genders[$admission['gender']] ?? 'N/A';
                                            ?>
                                        </td>
                                        <td><?php echo formatDate($admission['created_at']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="view.php?id=<?php echo $admission['id']; ?>" 
                                                   class="btn btn-sm btn-secondary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($filter == 'pending'): ?>
                                                    <a href="verify.php?id=<?php echo $admission['id']; ?>" 
                                                       class="btn btn-sm btn-success" title="Verify">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="delete.php?id=<?php echo $admission['id']; ?>" 
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