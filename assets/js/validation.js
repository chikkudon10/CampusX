/**
 * Form Validation JavaScript
 * CampusX - College Management System
 */

/**
 * Validate Login Form
 */
function validateLoginForm() {
    const email = document.getElementById('email');
    const password = document.getElementById('password');
    let isValid = true;
    
    // Clear previous errors
    clearErrors();
    
    // Email validation
    if (!email.value.trim()) {
        showError(email, 'Email is required');
        isValid = false;
    } else if (!validateEmail(email.value)) {
        showError(email, 'Please enter a valid email');
        isValid = false;
    }
    
    // Password validation
    if (!password.value.trim()) {
        showError(password, 'Password is required');
        isValid = false;
    } else if (password.value.length < 6) {
        showError(password, 'Password must be at least 6 characters');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Validate Student Registration Form
 */
function validateStudentForm() {
    const firstName = document.getElementById('first_name');
    const lastName = document.getElementById('last_name');
    const email = document.getElementById('email');
    const phone = document.getElementById('phone');
    const rollNumber = document.getElementById('roll_number');
    
    let isValid = true;
    clearErrors();
    
    // First Name
    if (!firstName.value.trim()) {
        showError(firstName, 'First name is required');
        isValid = false;
    }
    
    // Last Name
    if (!lastName.value.trim()) {
        showError(lastName, 'Last name is required');
        isValid = false;
    }
    
    // Email
    if (!email.value.trim()) {
        showError(email, 'Email is required');
        isValid = false;
    } else if (!validateEmail(email.value)) {
        showError(email, 'Please enter a valid email');
        isValid = false;
    }
    
    // Phone
    if (!phone.value.trim()) {
        showError(phone, 'Phone number is required');
        isValid = false;
    } else if (!validatePhone(phone.value)) {
        showError(phone, 'Please enter a valid Nepal phone number (98XXXXXXXX or 97XXXXXXXX)');
        isValid = false;
    }
    
    // Roll Number
    if (!rollNumber.value.trim()) {
        showError(rollNumber, 'Roll number is required');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Validate Assignment Form
 */
function validateAssignmentForm() {
    const title = document.getElementById('title');
    const description = document.getElementById('description');
    const dueDate = document.getElementById('due_date');
    const course = document.getElementById('course_id');
    
    let isValid = true;
    clearErrors();
    
    // Title
    if (!title.value.trim()) {
        showError(title, 'Assignment title is required');
        isValid = false;
    }
    
    // Description
    if (!description.value.trim()) {
        showError(description, 'Description is required');
        isValid = false;
    }
    
    // Due Date
    if (!dueDate.value) {
        showError(dueDate, 'Due date is required');
        isValid = false;
    } else {
        const selectedDate = new Date(dueDate.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            showError(dueDate, 'Due date cannot be in the past');
            isValid = false;
        }
    }
    
    // Course
    if (!course.value) {
        showError(course, 'Please select a course');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Validate Leave Application Form
 */
function validateLeaveForm() {
    const leaveType = document.getElementById('leave_type');
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const reason = document.getElementById('reason');
    
    let isValid = true;
    clearErrors();
    
    // Leave Type
    if (!leaveType.value) {
        showError(leaveType, 'Please select leave type');
        isValid = false;
    }
    
    // Start Date
    if (!startDate.value) {
        showError(startDate, 'Start date is required');
        isValid = false;
    }
    
    // End Date
    if (!endDate.value) {
        showError(endDate, 'End date is required');
        isValid = false;
    } else if (startDate.value && endDate.value) {
        const start = new Date(startDate.value);
        const end = new Date(endDate.value);
        
        if (end < start) {
            showError(endDate, 'End date must be after start date');
            isValid = false;
        }
    }
    
    // Reason
    if (!reason.value.trim()) {
        showError(reason, 'Reason is required');
        isValid = false;
    } else if (reason.value.trim().length < 10) {
        showError(reason, 'Reason must be at least 10 characters');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Validate Course Form
 */
function validateCourseForm() {
    const courseName = document.getElementById('course_name');
    const courseCode = document.getElementById('course_code');
    const credits = document.getElementById('credits');
    const semester = document.getElementById('semester');
    
    let isValid = true;
    clearErrors();
    
    // Course Name
    if (!courseName.value.trim()) {
        showError(courseName, 'Course name is required');
        isValid = false;
    }
    
    // Course Code
    if (!courseCode.value.trim()) {
        showError(courseCode, 'Course code is required');
        isValid = false;
    }
    
    // Credits
    if (!credits.value) {
        showError(credits, 'Credits are required');
        isValid = false;
    } else if (credits.value < 1 || credits.value > 6) {
        showError(credits, 'Credits must be between 1 and 6');
        isValid = false;
    }
    
    // Semester
    if (!semester.value) {
        showError(semester, 'Please select semester');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Validate Password Change Form
 */
function validatePasswordForm() {
    const currentPassword = document.getElementById('current_password');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    let isValid = true;
    clearErrors();
    
    // Current Password
    if (!currentPassword.value) {
        showError(currentPassword, 'Current password is required');
        isValid = false;
    }
    
    // New Password
    if (!newPassword.value) {
        showError(newPassword, 'New password is required');
        isValid = false;
    } else if (newPassword.value.length < 8) {
        showError(newPassword, 'Password must be at least 8 characters');
        isValid = false;
    } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(newPassword.value)) {
        showError(newPassword, 'Password must contain uppercase, lowercase, and number');
        isValid = false;
    }
    
    // Confirm Password
    if (!confirmPassword.value) {
        showError(confirmPassword, 'Please confirm your password');
        isValid = false;
    } else if (newPassword.value !== confirmPassword.value) {
        showError(confirmPassword, 'Passwords do not match');
        isValid = false;
    }
    
    return isValid;
}

/**
 * Show error message for input field
 */
function showError(input, message) {
    input.classList.add('error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'form-error';
    errorDiv.textContent = message;
    
    input.parentElement.appendChild(errorDiv);
}

/**
 * Clear all error messages
 */
function clearErrors() {
    // Remove error class from inputs
    const errorInputs = document.querySelectorAll('.form-control.error');
    errorInputs.forEach(input => {
        input.classList.remove('error');
    });
    
    // Remove error messages
    const errorMessages = document.querySelectorAll('.form-error');
    errorMessages.forEach(message => {
        message.remove();
    });
}

/**
 * Real-time validation on input
 */
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.form-control');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            // Clear existing errors for this field
            const existingError = this.parentElement.querySelector('.form-error');
            if (existingError) {
                existingError.remove();
            }
            this.classList.remove('error');
            
            // Validate based on input type
            if (this.type === 'email' && this.value) {
                if (!validateEmail(this.value)) {
                    showError(this, 'Please enter a valid email');
                }
            }
            
            if (this.id === 'phone' && this.value) {
                if (!validatePhone(this.value)) {
                    showError(this, 'Please enter a valid phone number');
                }
            }
            
            if (this.hasAttribute('required') && !this.value.trim()) {
                showError(this, 'This field is required');
            }
        });
    });
});

// Make validation functions globally available
window.validateLoginForm = validateLoginForm;
window.validateStudentForm = validateStudentForm;
window.validateAssignmentForm = validateAssignmentForm;
window.validateLeaveForm = validateLeaveForm;
window.validateCourseForm = validateCourseForm;
window.validatePasswordForm = validatePasswordForm;