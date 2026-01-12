<?php
/**
 * Submit Assignment - Student
 * CampusX - College Management System
 */

require_once '../../../config/config.php';
require_once '../../../config/constants.php';
require_once '../../../core/Session.php';
require_once '../../../core/Database.php';
require_once '../../../includes/functions.php';

Session::requireRole(ROLE_STUDENT);

$db = new Database();
$errors = [];

// Get student info
$student = $db->getOne('students', 'user_id = ?', [$_SESSION['user_id']], 'i');
if (!$student) {
    $_SESSION['error_message'] = 'Student profile not found';
    header('Location: ../dashboard.php');
    exit();
}
$studentId = $student['id'];

// Get assignment ID
$assignmentId = intval($_GET['assignment_id'] ?? 0);

if ($assignmentId === 0) {
    $_SESSION['error_message'] = 'Invalid assignment';
    header('Location: index.php');
    exit();
}

// Get assignment details
$assignment = $db->getOne(
    "SELECT a.*, c.course_code, c.course_name, t.first_name as teacher_first_name, t.last_name as teacher_last_name
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    JOIN teachers t ON c.teacher_id = t.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE a.id = ? AND e.student_id = ?",
    [$assignmentId, $studentId],
    'ii'
);

if (!$assignment) {
    $_SESSION['error_message'] = 'Assignment not found or unauthorized';
    header('Location: index.php');
    exit();
}

// Check if already submitted
$existingSubmission = $db->getOne(
    'assignment_submissions',
    'assignment_id = ? AND student_id = ?',
    [$assignmentId, $studentId],
    'ii'
);

if ($existingSubmission && $existingSubmission['status'] === 'evaluated') {
    $_SESSION['error_message'] = 'This assignment has already been evaluated and cannot be resubmitted';
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submissionText = trim($_POST['submission_text'] ?? '');
    $uploadFile = $_FILES['submission_file'] ?? null;
    
    // Validation
    if (empty($submissionText) && (!$uploadFile || $uploadFile['size'] === 0)) {
        $errors[] = 'Please provide either text submission or upload a file';
    }
    
    // Check file upload
    $filePath = null;
    if ($uploadFile && $uploadFile['size'] > 0) {
        // Validate file
        $maxSize = 10 * 1024 * 1024; // 10MB
        $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'zip', 'java', 'cpp', 'c', 'py', 'js'];
        
        if ($uploadFile['size'] > $maxSize) {
            $errors[] = 'File size cannot exceed 10MB';
        }
        
        $fileExtension = strtolower(pathinfo($uploadFile['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = 'File type not allowed. Allowed: ' . implode(', ', $allowedExtensions);
        }
        
        if (empty($errors)) {
            // Create upload directory
            $uploadDir = __DIR__ . '/../../../uploads/assignments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileName = 'assignment_' . $assignmentId . '_student_' . $studentId . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            // Move uploaded file
            if (!move_uploaded_file($uploadFile['tmp_name'], $filePath)) {
                $errors[] = 'Failed to upload file. Please try again.';
                $filePath = null;
            } else {
                // Store relative path for database
                $filePath = 'uploads/assignments/' . $fileName;
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $submissionData = [
                'assignment_id' => $assignmentId,
                'student_id' => $studentId,
                'submitted_file' => $filePath,
                'status' => 'submitted',
                'submission_date' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($existingSubmission) {
                // Update existing submission
                $updateData = [
                    'submitted_file' => $filePath ?: $existingSubmission['submitted_file'],
                    'submission_date' => date('Y-m-d H:i:s'),
                    'status' => 'submitted',
                    'score' => null,
                    'feedback' => null,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $updated = $db->update(
                    'assignment_submissions',
                    $updateData,
                    'id = ?',
                    [$existingSubmission['id']],
                    'i'
                );
                
                if ($updated !== false) {
                    $_SESSION['success_message'] = 'Assignment resubmitted successfully!';
                    header('Location: index.php');
                    exit();
                }
            } else {
                // Insert new submission
                $submissionId = $db->insert('assignment_submissions', $submissionData);
                
                if ($submissionId) {
                    $_SESSION['success_message'] = 'Assignment submitted successfully!';
                    header('Location: index.php');
                    exit();
                }
            }
            
            $errors[] = 'Failed to submit assignment. Please try again.';
            
        } catch (Exception $e) {
            $errors[] = 'Error: ' . $e->getMessage();
        }
    }
}

$pageTitle = "Submit Assignment";
$additionalCSS = ['student.css'];
require_once '../../../includes/header.php';
?>

<div class="student-wrapper">
    <?php require_once '../../../includes/sidebar.php'; ?>
    
    <div class="student-content">
        <?php require_once '../../../includes/navbar.php'; ?>
        
        <div class="student-body">
            <div class="page-header mb-4">
                <h1><i class="fas fa-upload"></i> Submit Assignment</h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="fas fa-exclamation-circle"></i> Errors:</strong>
                    <ul style="margin: 10px 0 0 20px; padding: 0;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Assignment Info Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Assignment Information</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <h4><?php echo htmlspecialchars($assignment['title']); ?></h4>
                            <p class="text-muted"><?php echo htmlspecialchars($assignment['course_code']); ?> - <?php echo htmlspecialchars($assignment['course_name']); ?></p>
                            <p style="margin-top: 1rem;">
                                <strong>Teacher:</strong> <?php echo htmlspecialchars($assignment['teacher_first_name'] . ' ' . $assignment['teacher_last_name']); ?>
                            </p>
                        </div>
                        <div style="border-left: 1px solid #ecf0f1; padding-left: 2rem;">
                            <div style="margin-bottom: 1.5rem;">
                                <small style="color: #95a5a6; text-transform: uppercase; font-weight: 600;">Due Date & Time</small>
                                <p style="font-size: 1.1em; margin-top: 0.5rem;">
                                    <?php 
                                    $dueTime = strtotime($assignment['due_date']);
                                    $now = time();
                                    if ($dueTime < $now) {
                                        echo '<span style="color: #e74c3c;"><i class="fas fa-exclamation-triangle"></i> Overdue</span>';
                                    } else {
                                        echo date('M d, Y H:i', $dueTime);
                                    }
                                    ?>
                                </p>
                            </div>
                            <div>
                                <small style="color: #95a5a6; text-transform: uppercase; font-weight: 600;">Max Score</small>
                                <p style="font-size: 1.1em; margin-top: 0.5rem;"><?php echo $assignment['max_score']; ?> Points</p>
                            </div>
                        </div>
                    </div>
                    
                    <hr style="margin: 1.5rem 0;">
                    
                    <h5>Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                    
                    <?php if ($assignment['instructions']): ?>
                        <hr>
                        <h5>Instructions</h5>
                        <p><?php echo nl2br(htmlspecialchars($assignment['instructions'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Submission Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-pen-square"></i> Submit Your Assignment</h3>
                </div>
                <div class="card-body">
                    <?php if ($existingSubmission): ?>
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Previous Submission:</strong> You already submitted this assignment on 
                            <?php echo date('M d, Y H:i', strtotime($existingSubmission['submission_date'])); ?>. 
                            You can resubmit to update your submission.
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" class="needs-validation">
                        <div class="form-group">
                            <label for="submission_text" class="form-label">Submission Text</label>
                            <textarea class="form-control" id="submission_text" name="submission_text" rows="6"
                                      placeholder="Enter your assignment answer or explanation here..."><?php echo htmlspecialchars($_POST['submission_text'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">You can type your answer directly here</small>
                        </div>
                        
                        <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 5px; margin-bottom: 1.5rem;">
                            <h5 style="margin-bottom: 1rem;"><i class="fas fa-divider"></i> OR</h5>
                        </div>
                        
                        <div class="form-group">
                            <label for="submission_file" class="form-label">Upload File</label>
                            <div class="file-upload-wrapper">
                                <input type="file" class="form-control" id="submission_file" name="submission_file"
                                       accept=".pdf,.doc,.docx,.txt,.zip,.java,.cpp,.c,.py,.js">
                                <small class="form-text text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    Allowed formats: PDF, DOC, DOCX, TXT, ZIP, JAVA, CPP, C, PY, JS<br>
                                    Maximum file size: 10MB
                                </small>
                            </div>
                            <div id="fileInfo" style="margin-top: 1rem; display: none;">
                                <small style="color: #2ecc71;"><i class="fas fa-check"></i> File selected: <span id="fileName"></span></small>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 2rem;">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-paper-plane"></i> Submit Assignment
                            </button>
                            <a href="index.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Important Information -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-lightbulb"></i> Important Information</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; gap: 1rem;">
                        <div style="padding: 1rem; background: #ecf0f1; border-radius: 4px; border-left: 3px solid #3498db;">
                            <strong><i class="fas fa-clock"></i> Deadline</strong>
                            <p style="margin: 0.5rem 0 0 0;">
                                Submit your assignment before 
                                <strong><?php echo date('M d, Y H:i', strtotime($assignment['due_date'])); ?></strong>
                                <?php if (strtotime($assignment['due_date']) < time()): ?>
                                    <span style="color: #e74c3c;"> (This assignment is now overdue)</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div style="padding: 1rem; background: #ecf0f1; border-radius: 4px; border-left: 3px solid #2ecc71;">
                            <strong><i class="fas fa-file"></i> Submission Methods</strong>
                            <p style="margin: 0.5rem 0 0 0;">
                                You can submit either by typing your answer in the text area above OR by uploading a file. 
                                You don't need to do both.
                            </p>
                        </div>
                        
                        <div style="padding: 1rem; background: #ecf0f1; border-radius: 4px; border-left: 3px solid #f39c12;">
                            <strong><i class="fas fa-refresh"></i> Resubmission</strong>
                            <p style="margin: 0.5rem 0 0 0;">
                                If you've already submitted, you can resubmit to update your submission 
                                (unless it has been evaluated by the teacher).
                            </p>
                        </div>
                        
                        <div style="padding: 1rem; background: #ecf0f1; border-radius: 4px; border-left: 3px solid #9b59b6;">
                            <strong><i class="fas fa-star"></i> Scoring</strong>
                            <p style="margin: 0.5rem 0 0 0;">
                                This assignment is worth <strong><?php echo $assignment['max_score']; ?> points</strong>. 
                                Your teacher will evaluate your submission and provide feedback.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.file-upload-wrapper {
    position: relative;
    border: 2px dashed #3498db;
    padding: 2rem;
    border-radius: 8px;
    text-align: center;
    transition: all 0.3s;
    background: #f8f9fa;
    cursor: pointer;
}

.file-upload-wrapper:hover {
    border-color: #667eea;
    background: #ecf0f1;
}

.file-upload-wrapper input[type="file"] {
    display: none;
}

.file-upload-wrapper::before {
    content: '\f093';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    font-size: 2rem;
    color: #3498db;
    display: block;
    margin-bottom: 0.5rem;
}

.file-upload-wrapper::after {
    content: 'Click to select file or drag and drop';
    display: block;
    color: #7f8c8d;
    font-weight: 600;
}

.alert {
    padding: 1rem;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.alert-danger {
    background: #fadbd8;
    border-left: 4px solid #c0392b;
    color: #c0392b;
}

.alert-info {
    background: #d6eaf8;
    border-left: 4px solid #3498db;
    color: #3498db;
}

.alert-success {
    background: #d5f4e6;
    border-left: 4px solid #27ae60;
    color: #27ae60;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 0.8rem;
    display: block;
}

.form-control {
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

textarea.form-control {
    resize: vertical;
    font-family: 'Courier New', monospace;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1rem;
}

.btn-success {
    background: #27ae60;
    color: white;
}

.btn-success:hover {
    background: #229954;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
    transform: translateY(-2px);
}

.text-muted {
    color: #7f8c8d;
}

h4, h5 {
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    [style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    
    .file-upload-wrapper {
        padding: 1rem;
    }
}
</style>

<script>
// File upload handling
document.getElementById('submission_file').addEventListener('change', function(e) {
    const fileName = e.target.files[0]?.name;
    const fileInfo = document.getElementById('fileInfo');
    const fileNameSpan = document.getElementById('fileName');
    
    if (fileName) {
        fileNameSpan.textContent = fileName;
        fileInfo.style.display = 'block';
    } else {
        fileInfo.style.display = 'none';
    }
});

// Drag and drop
const fileUpload = document.getElementById('submission_file');
const fileWrapper = fileUpload.parentElement;

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    fileWrapper.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    fileWrapper.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    fileWrapper.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    fileWrapper.style.borderColor = '#667eea';
    fileWrapper.style.background = '#f0f0ff';
}

function unhighlight(e) {
    fileWrapper.style.borderColor = '#3498db';
    fileWrapper.style.background = '#f8f9fa';
}

fileWrapper.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    fileUpload.files = files;
    
    // Trigger change event
    const event = new Event('change', { bubbles: true });
    fileUpload.dispatchEvent(event);
}

// Make file wrapper clickable
fileWrapper.addEventListener('click', function(e) {
    if (e.target !== fileUpload) {
        fileUpload.click();
    }
});
</script>

<?php require_once '../../../includes/footer.php'; ?>