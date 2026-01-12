<?php
/**
 * Attendance API - AJAX Endpoint
 * CampusX - College Management System
 */

header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../config/constants.php';
require_once '../core/Session.php';
require_once '../core/Database.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = new Database();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ==================== GET ATTENDANCE STATS ====================
if ($action === 'get_stats') {
    $courseId = intval($_GET['course_id'] ?? 0);
    
    if ($courseId === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Course ID required']);
        exit();
    }
    
    // Verify user has access to this course
    $course = $db->getOne('courses', 'id = ?', [$courseId], 'i');
    if (!$course) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Course not found']);
        exit();
    }
    
    // Get attendance statistics
    $stats = $db->select(
        "SELECT 
            COUNT(DISTINCT DATE(attendance_date)) as total_classes,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count
        FROM attendance
        WHERE course_id = ?",
        [$courseId], 'i'
    );
    
    $data = $stats[0];
    $percentage = $data['total_classes'] > 0 ? 
        round(($data['present_count'] / $data['total_classes']) * 100, 2) : 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_classes' => $data['total_classes'],
            'present_count' => $data['present_count'],
            'late_count' => $data['late_count'],
            'absent_count' => $data['absent_count'],
            'attendance_percentage' => $percentage
        ]
    ]);
    exit();
}

// ==================== GET STUDENT ATTENDANCE ====================
if ($action === 'get_student_attendance') {
    $studentId = intval($_GET['student_id'] ?? 0);
    $courseId = intval($_GET['course_id'] ?? 0);
    
    if ($studentId === 0 || $courseId === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Student ID and Course ID required']);
        exit();
    }
    
    $attendance = $db->select(
        "SELECT a.*, DATE_FORMAT(a.attendance_date, '%Y-%m-%d %H:%i') as formatted_date
        FROM attendance a
        WHERE a.student_id = ? AND a.course_id = ?
        ORDER BY a.attendance_date DESC",
        [$studentId, $courseId], 'ii'
    );
    
    echo json_encode([
        'success' => true,
        'data' => $attendance,
        'count' => count($attendance)
    ]);
    exit();
}

// ==================== BULK ATTENDANCE REPORT ====================
if ($action === 'bulk_report') {
    $courseId = intval($_POST['course_id'] ?? 0);
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    
    if ($courseId === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Course ID required']);
        exit();
    }
    
    $whereClause = "course_id = ?";
    $params = [$courseId];
    $types = 'i';
    
    if (!empty($startDate)) {
        $whereClause .= " AND attendance_date >= ?";
        $params[] = $startDate . ' 00:00:00';
        $types .= 's';
    }
    
    if (!empty($endDate)) {
        $whereClause .= " AND attendance_date <= ?";
        $params[] = $endDate . ' 23:59:59';
        $types .= 's';
    }
    
    $attendance = $db->select(
        "SELECT s.roll_number, s.first_name, s.last_name, 
        COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present,
        COUNT(CASE WHEN a.status = 'Late' THEN 1 END) as late,
        COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent,
        COUNT(*) as total
        FROM students s
        LEFT JOIN attendance a ON s.id = a.student_id AND $whereClause
        GROUP BY s.id
        ORDER BY s.roll_number",
        $params, $types
    );
    
    echo json_encode([
        'success' => true,
        'data' => $attendance,
        'count' => count($attendance)
    ]);
    exit();
}

// ==================== EXPORT ATTENDANCE ====================
if ($action === 'export_csv') {
    $courseId = intval($_GET['course_id'] ?? 0);
    $month = $_GET['month'] ?? date('Y-m');
    
    if ($courseId === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Course ID required']);
        exit();
    }
    
    $attendance = $db->select(
        "SELECT s.roll_number, s.first_name, s.last_name, a.attendance_date, a.status, c.course_code
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        JOIN courses c ON a.course_id = c.id
        WHERE a.course_id = ? AND DATE_FORMAT(a.attendance_date, '%Y-%m') = ?
        ORDER BY a.attendance_date DESC",
        [$courseId, $month], 'is'
    );
    
    // Create CSV content
    $csv = "Roll Number,First Name,Last Name,Date,Status,Course\n";
    foreach ($attendance as $record) {
        $csv .= "\"{$record['roll_number']}\",\"{$record['first_name']}\",\"{$record['last_name']}\",";
        $csv .= "\"{$record['attendance_date']}\",\"{$record['status']}\",\"{$record['course_code']}\"\n";
    }
    
    echo json_encode([
        'success' => true,
        'csv' => $csv,
        'filename' => 'attendance_' . date('Y-m-d_His') . '.csv'
    ]);
    exit();
}

// Invalid action
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit();

?>

<?php