<?php
/**
 * Constants Configuration File
 * CampusX - College Management System
 * 
 * Contains system-wide constants for user roles, status codes, etc.
 */

// Prevent direct access
if (!defined('APP_ACCESS')) {
    die('Direct access not permitted');
}

// ==================== USER ROLES ====================

define('ROLE_ADMIN', 'admin');
define('ROLE_TEACHER', 'teacher');
define('ROLE_STUDENT', 'student');

// Role IDs (for database)
define('ROLE_ID_ADMIN', 1);
define('ROLE_ID_TEACHER', 2);
define('ROLE_ID_STUDENT', 3);

// ==================== USER STATUS ====================

define('STATUS_ACTIVE', 1);
define('STATUS_INACTIVE', 0);
define('STATUS_SUSPENDED', 2);
define('STATUS_PENDING', 3);

// ==================== ATTENDANCE STATUS ====================

define('ATTENDANCE_PRESENT', 'P');
define('ATTENDANCE_ABSENT', 'A');
define('ATTENDANCE_LATE', 'L');
define('ATTENDANCE_EXCUSED', 'E');

// Attendance display names
$ATTENDANCE_STATUS = [
    'P' => 'Present',
    'A' => 'Absent',
    'L' => 'Late',
    'E' => 'Excused'
];

// ==================== LEAVE STATUS ====================

define('LEAVE_PENDING', 'pending');
define('LEAVE_APPROVED', 'approved');
define('LEAVE_REJECTED', 'rejected');

// Leave types
define('LEAVE_TYPE_SICK', 'sick');
define('LEAVE_TYPE_PERSONAL', 'personal');
define('LEAVE_TYPE_EMERGENCY', 'emergency');
define('LEAVE_TYPE_OTHER', 'other');

// ==================== ASSIGNMENT STATUS ====================

define('ASSIGNMENT_NOT_SUBMITTED', 'not_submitted');
define('ASSIGNMENT_SUBMITTED', 'submitted');
define('ASSIGNMENT_LATE_SUBMISSION', 'late');
define('ASSIGNMENT_GRADED', 'graded');

// ==================== EXAMINATION STATUS ====================

define('EXAM_UPCOMING', 'upcoming');
define('EXAM_ONGOING', 'ongoing');
define('EXAM_COMPLETED', 'completed');
define('EXAM_CANCELLED', 'cancelled');

// ==================== ADMISSION STATUS ====================

define('ADMISSION_PENDING', 'pending');
define('ADMISSION_VERIFIED', 'verified');
define('ADMISSION_APPROVED', 'approved');
define('ADMISSION_REJECTED', 'rejected');

// ==================== GRADE SYSTEM ====================

// Grade points
$GRADE_POINTS = [
    'A+' => 4.0,
    'A'  => 3.7,
    'B+' => 3.3,
    'B'  => 3.0,
    'C+' => 2.7,
    'C'  => 2.3,
    'D+' => 2.0,
    'D'  => 1.7,
    'F'  => 0.0
];

// Grade ranges (percentage)
$GRADE_RANGES = [
    'A+' => ['min' => 90, 'max' => 100],
    'A'  => ['min' => 80, 'max' => 89],
    'B+' => ['min' => 70, 'max' => 79],
    'B'  => ['min' => 60, 'max' => 69],
    'C+' => ['min' => 50, 'max' => 59],
    'C'  => ['min' => 40, 'max' => 49],
    'D+' => ['min' => 35, 'max' => 39],
    'D'  => ['min' => 30, 'max' => 34],
    'F'  => ['min' => 0,  'max' => 29]
];

// ==================== SEMESTER/YEAR CONSTANTS ====================

$SEMESTERS = [
    1 => 'First Semester',
    2 => 'Second Semester',
    3 => 'Third Semester',
    4 => 'Fourth Semester',
    5 => 'Fifth Semester',
    6 => 'Sixth Semester',
    7 => 'Seventh Semester',
    8 => 'Eighth Semester'
];

$ACADEMIC_YEARS = [];
$currentYear = date('Y');
for ($i = -2; $i <= 2; $i++) {
    $year = $currentYear + $i;
    $ACADEMIC_YEARS[$year] = $year . '-' . ($year + 1);
}

// ==================== GENDER ====================

$GENDERS = [
    'M' => 'Male',
    'F' => 'Female',
    'O' => 'Other'
];

// ==================== BLOOD GROUPS ====================

$BLOOD_GROUPS = [
    'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'
];

// ==================== NOTIFICATION TYPES ====================

define('NOTIFICATION_INFO', 'info');
define('NOTIFICATION_SUCCESS', 'success');
define('NOTIFICATION_WARNING', 'warning');
define('NOTIFICATION_ERROR', 'error');

// ==================== DAYS OF WEEK ====================

$DAYS_OF_WEEK = [
    'Sunday',
    'Monday',
    'Tuesday',
    'Wednesday',
    'Thursday',
    'Friday',
    'Saturday'
];

// ==================== TIME SLOTS ====================

$TIME_SLOTS = [
    '07:00-08:00' => '7:00 AM - 8:00 AM',
    '08:00-09:00' => '8:00 AM - 9:00 AM',
    '09:00-10:00' => '9:00 AM - 10:00 AM',
    '10:00-11:00' => '10:00 AM - 11:00 AM',
    '11:00-12:00' => '11:00 AM - 12:00 PM',
    '12:00-13:00' => '12:00 PM - 1:00 PM',
    '13:00-14:00' => '1:00 PM - 2:00 PM',
    '14:00-15:00' => '2:00 PM - 3:00 PM',
    '15:00-16:00' => '3:00 PM - 4:00 PM',
    '16:00-17:00' => '4:00 PM - 5:00 PM'
];

// ==================== HELPER FUNCTION ====================

/**
 * Get grade from percentage
 */
function getGradeFromPercentage($percentage) {
    global $GRADE_RANGES;
    
    foreach ($GRADE_RANGES as $grade => $range) {
        if ($percentage >= $range['min'] && $percentage <= $range['max']) {
            return $grade;
        }
    }
    return 'F';
}

/**
 * Get attendance status name
 */
function getAttendanceStatusName($code) {
    global $ATTENDANCE_STATUS;
    return isset($ATTENDANCE_STATUS[$code]) ? $ATTENDANCE_STATUS[$code] : 'Unknown';
}

/**
 * Get semester name
 */
function getSemesterName($semester) {
    global $SEMESTERS;
    return isset($SEMESTERS[$semester]) ? $SEMESTERS[$semester] : 'Unknown Semester';
}

?>