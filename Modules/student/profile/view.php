<?php
require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_STUDENT);

$db = new Database();
$student = $db->getOne('students', 'user_id = ?', [$_SESSION['user_id']], 'i');
$user = $db->getOne('users', 'id = ?', [$_SESSION['user_id']], 'i');

$pageTitle = "My Profile";
require_once '../../../includes/header.php';
?>

<div class="student-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    <div class="student-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="student-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                <a href="edit.php" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
            </div>
            
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                        <p><?php echo htmlspecialchars($student['roll_number']); ?></p>
                    </div>
                </div>
                
                <div class="profile-details">
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($student['email']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Phone</span>
                        <span class="detail-value"><?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Semester</span>
                        <span class="detail-value">Semester <?php echo $student['semester']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Account Status</span>
                        <span class="detail-value">
                            <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
    color: white;
}

.profile-info h2 {
    margin: 0;
    font-size: 1.8em;
}

.profile-info p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
}

.profile-details {
    padding: 2rem;
}

.detail-row {
    display: flex;
    padding: 1rem 0;
    border-bottom: 1px solid #ecf0f1;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    flex: 0 0 150px;
    font-weight: 600;
    color: #7f8c8d;
}

.detail-value {
    flex: 1;
    color: #2c3e50;
}
</style>

<?php require_once '../../../includes/footer.php'; ?>
