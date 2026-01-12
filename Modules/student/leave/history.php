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

$filterStatus = $_GET['status'] ?? '';

$whereClause = "student_id = ?";
$params = [$studentId];
$types = 'i';

if ($filterStatus) {
    $whereClause .= " AND status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}

$leaves = $db->select(
    "SELECT * FROM leave_applications WHERE $whereClause ORDER BY applied_date DESC",
    $params, $types
);

$pageTitle = "Leave History";
require_once '../../../includes/header.php';
?>

<div class="student-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    <div class="student-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="student-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-history"></i> Leave Applications</h1>
                <a href="apply.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Apply for Leave
                </a>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="form-row">
                        <div class="form-group col-md-4">
                            <label>Filter by Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Applications</option>
                                <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $filterStatus === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $filterStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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
                    <?php if (empty($leaves)): ?>
                        <div class="text-center text-muted" style="padding: 3rem;">
                            <p>No leave applications found</p>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th>Leave Type</th>
                                    <th>From - To</th>
                                    <th>Days</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Applied On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($leaves as $leave): 
                                    $days = (strtotime($leave['to_date']) - strtotime($leave['from_date'])) / (60 * 60 * 24) + 1;
                                ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($leave['leave_type']); ?></strong></td>
                                        <td><?php echo date('Y-m-d', strtotime($leave['from_date'])); ?> - <?php echo date('Y-m-d', strtotime($leave['to_date'])); ?></td>
                                        <td><?php echo $days; ?> day(s)</td>
                                        <td><?php echo htmlspecialchars(substr($leave['reason'], 0, 30)) . '...'; ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo
                                                $leave['status'] === 'pending' ? 'warning' :
                                                ($leave['status'] === 'approved' ? 'success' : 'danger');
                                            ?>">
                                                <?php echo ucfirst($leave['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($leave['applied_date'])); ?></td>
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
