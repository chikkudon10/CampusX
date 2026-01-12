
<?php
/**
 * Common Utility Functions
 * CampusX - College Management System
 */



/**
 * Generate unique filename for uploads
 */
function generateUniqueFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    return $filename;
}

/**
 * Upload file with validation
 */
function uploadFile($file, $uploadDir, $allowedTypes = []) {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum limit'];
    }
    
    // Check file type
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowedTypes) && !in_array($extension, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    // Generate unique filename
    $filename = generateUniqueFilename($file['name']);
    $destination = $uploadDir . $filename;
    
    // Create directory if not exists
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename, 'path' => $destination];
    }
    
    return ['success' => false, 'message' => 'Failed to save file'];
}

/**
 * Delete file
 */
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Calculate age from date of birth
 */
function calculateAge($dob) {
    $birthDate = new DateTime($dob);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age;
}

/**
 * Calculate attendance percentage
 */
function calculateAttendancePercentage($present, $total) {
    if ($total == 0) return 0;
    return round(($present / $total) * 100, 2);
}

/**
 * Calculate GPA from grades
 */
function calculateGPA($grades) {
    global $GRADE_POINTS;
    
    if (empty($grades)) return 0;
    
    $totalPoints = 0;
    $totalSubjects = count($grades);
    
    foreach ($grades as $grade) {
        if (isset($GRADE_POINTS[$grade])) {
            $totalPoints += $GRADE_POINTS[$grade];
        }
    }
    
    return round($totalPoints / $totalSubjects, 2);
}

/**
 * Get academic year from date
 */
function getAcademicYear($date = null) {
    if ($date === null) {
        $date = date('Y-m-d');
    }
    
    $year = date('Y', strtotime($date));
    $month = date('m', strtotime($date));
    
    // Academic year starts in August (month 8)
    if ($month >= 8) {
        return $year . '-' . ($year + 1);
    } else {
        return ($year - 1) . '-' . $year;
    }
}

/**
 * Format phone number
 */
function formatPhoneNumber($phone) {
    // Remove any non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Format as: 98XXXXXXXX
    if (strlen($phone) == 10) {
        return substr($phone, 0, 2) . '-' . substr($phone, 2, 4) . '-' . substr($phone, 6);
    }
    
    return $phone;
}

/**
 * Generate random password
 */
function generateRandomPassword($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $password;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ALGO, ['cost' => PASSWORD_COST]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate roll number
 */
function generateRollNumber($year, $semester, $lastNumber = 0) {
    $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
    return $year . $semester . $newNumber;
}

/**
 * Check if date is holiday
 */
function isHoliday($date) {
    $dayOfWeek = date('l', strtotime($date));
    
    // Saturday is holiday in Nepal
    if ($dayOfWeek === 'Saturday') {
        return true;
    }
    
    // You can add more holiday checks here (public holidays, etc.)
    
    return false;
}

/**
 * Get days between two dates
 */
function getDaysBetween($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $diff = $start->diff($end);
    return $diff->days + 1; // +1 to include both start and end date
}

/**
 * Send email notification
 */
function sendEmail($to, $subject, $message) {
    // Basic email headers
    $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    // Send email
    return mail($to, $subject, $message, $headers);
}

/**
 * Log activity
 */
function logActivity($userId, $action, $details = '') {
    $conn = getDatabaseConnection();
    
    $query = "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) 
              VALUES (?, ?, ?, ?, NOW())";
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isss', $userId, $action, $details, $ipAddress);
    $result = $stmt->execute();
    
    $stmt->close();
    closeDatabaseConnection($conn);
    
    return $result;
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        
        if ($elapsed > SESSION_TIMEOUT) {
            // Session expired
            session_unset();
            session_destroy();
            redirect('modules/authentication/login.php?timeout=1');
            exit();
        }
    }
    
    $_SESSION['last_activity'] = time();
}

/**
 * Paginate results
 */
function paginate($totalRecords, $currentPage = 1, $recordsPerPage = RECORDS_PER_PAGE) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $recordsPerPage;
    
    return [
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'records_per_page' => $recordsPerPage,
        'offset' => $offset
    ];
}

/**
 * Display pagination links
 */
function displayPagination($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) return;
    
    echo '<ul class="pagination">';
    
    // Previous button
    if ($pagination['current_page'] > 1) {
        echo '<li><a href="' . $baseUrl . '?page=' . ($pagination['current_page'] - 1) . '">Previous</a></li>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $pagination['total_pages']; $i++) {
        $active = ($i == $pagination['current_page']) ? 'class="active"' : '';
        echo '<li ' . $active . '><a href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($pagination['current_page'] < $pagination['total_pages']) {
        echo '<li><a href="' . $baseUrl . '?page=' . ($pagination['current_page'] + 1) . '">Next</a></li>';
    }
    
    echo '</ul>';
}

/**
 * Clean input data
 */
function cleanInput($data) {
    if (is_array($data)) {
        return array_map('cleanInput', $data);
    }
    return sanitize($data);
}

/**
 * Debug function (only in development)
 */
function debug($data, $die = false) {
    if (DEBUG_MODE) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        
        if ($die) die();
    }
}

/**
 * Get user IP address
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * JSON response helper
 */
function jsonResponse($success, $message = '', $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}
?>