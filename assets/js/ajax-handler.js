/**
 * AJAX Handler JavaScript
 * CampusX - College Management System
 * Handles all AJAX requests
 */

/**
 * Generic AJAX request function
 */
function ajaxRequest(url, method = 'GET', data = null, callback = null) {
    const xhr = new XMLHttpRequest();
    
    xhr.open(method, url, true);
    
    if (method === 'POST') {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }
    
    xhr.onload = function() {
        if (xhr.status >= 200 && xhr.status < 300) {
            const response = JSON.parse(xhr.responseText);
            if (callback) callback(response);
        } else {
            console.error('Request failed:', xhr.status);
            showToast('Request failed. Please try again.', 'error');
        }
    };
    
    xhr.onerror = function() {
        console.error('Network error');
        showToast('Network error. Please check your connection.', 'error');
    };
    
    xhr.send(data);
}

/**
 * Mark attendance via AJAX
 */
function markAttendance(studentId, status, date, courseId) {
    showLoading('Marking attendance...');
    
    const data = `student_id=${studentId}&status=${status}&date=${date}&course_id=${courseId}`;
    
    ajaxRequest('../api/attendance.php', 'POST', data, function(response) {
        hideLoading();
        
        if (response.success) {
            showToast('Attendance marked successfully!', 'success');
            
            // Update UI
            const studentRow = document.querySelector(`[data-student-id="${studentId}"]`);
            if (studentRow) {
                const buttons = studentRow.querySelectorAll('.status-btn');
                buttons.forEach(btn => btn.classList.remove('active'));
                
                const activeBtn = studentRow.querySelector(`.status-btn.${status}`);
                if (activeBtn) activeBtn.classList.add('active');
            }
        } else {
            showToast(response.message || 'Failed to mark attendance', 'error');
        }
    });
}

/**
 * Submit assignment via AJAX
 */
function submitAssignment(assignmentId, formData) {
    showLoading('Submitting assignment...');
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../api/assignments.php', true);
    
    xhr.onload = function() {
        hideLoading();
        
        if (xhr.status >= 200 && xhr.status < 300) {
            const response = JSON.parse(xhr.responseText);
            
            if (response.success) {
                showToast('Assignment submitted successfully!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showToast(response.message || 'Failed to submit assignment', 'error');
            }
        } else {
            showToast('Request failed. Please try again.', 'error');
        }
    };
    
    xhr.onerror = function() {
        hideLoading();
        showToast('Network error. Please check your connection.', 'error');
    };
    
    xhr.send(formData);
}

/**
 * Approve/Reject leave application
 */
function updateLeaveStatus(leaveId, status) {
    if (!confirm(`Are you sure you want to ${status} this leave application?`)) {
        return;
    }
    
    showLoading('Updating leave status...');
    
    const data = `leave_id=${leaveId}&status=${status}`;
    
    ajaxRequest('../api/leave.php', 'POST', data, function(response) {
        hideLoading();
        
        if (response.success) {
            showToast(`Leave ${status} successfully!`, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(response.message || 'Failed to update leave status', 'error');
        }
    });
}

/**
 * Load student details via AJAX
 */
function loadStudentDetails(studentId) {
    showLoading('Loading student details...');
    
    ajaxRequest(`../api/students.php?id=${studentId}`, 'GET', null, function(response) {
        hideLoading();
        
        if (response.success) {
            displayStudentModal(response.data);
        } else {
            showToast('Failed to load student details', 'error');
        }
    });
}

/**
 * Display student details in modal
 */
function displayStudentModal(student) {
    const modalHTML = `
        <div class="modal-overlay" onclick="closeModal()">
            <div class="modal-content" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h2>Student Details</h2>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="student-detail-grid">
                        <div class="detail-item">
                            <strong>Name:</strong> ${student.first_name} ${student.last_name}
                        </div>
                        <div class="detail-item">
                            <strong>Roll Number:</strong> ${student.roll_number}
                        </div>
                        <div class="detail-item">
                            <strong>Email:</strong> ${student.email}
                        </div>
                        <div class="detail-item">
                            <strong>Phone:</strong> ${student.phone}
                        </div>
                        <div class="detail-item">
                            <strong>Semester:</strong> ${student.semester}
                        </div>
                        <div class="detail-item">
                            <strong>Status:</strong> ${student.status}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

/**
 * Close modal
 */
function closeModal() {
    const modal = document.querySelector('.modal-overlay');
    if (modal) {
        modal.remove();
    }
}

/**
 * Load attendance statistics
 */
function loadAttendanceStats(studentId, courseId) {
    ajaxRequest(`../api/attendance.php?student_id=${studentId}&course_id=${courseId}&action=stats`, 'GET', null, function(response) {
        if (response.success) {
            updateAttendanceUI(response.data);
        }
    });
}

/**
 * Update attendance UI with statistics
 */
function updateAttendanceUI(stats) {
    document.getElementById('total-classes').textContent = stats.total;
    document.getElementById('present-count').textContent = stats.present;
    document.getElementById('absent-count').textContent = stats.absent;
    document.getElementById('attendance-percentage').textContent = stats.percentage + '%';
    
    // Update progress bar
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        progressBar.style.width = stats.percentage + '%';
    }
}

/**
 * Search students dynamically
 */
function searchStudents(query) {
    if (query.length < 2) return;
    
    ajaxRequest(`../api/students.php?search=${encodeURIComponent(query)}`, 'GET', null, function(response) {
        if (response.success) {
            displaySearchResults(response.data);
        }
    });
}

/**
 * Display search results
 */
function displaySearchResults(students) {
    const resultsContainer = document.getElementById('search-results');
    
    if (!resultsContainer) return;
    
    if (students.length === 0) {
        resultsContainer.innerHTML = '<p>No students found</p>';
        return;
    }
    
    let html = '<ul class="search-results-list">';
    
    students.forEach(student => {
        html += `
            <li onclick="selectStudent(${student.id})">
                <strong>${student.first_name} ${student.last_name}</strong>
                <span>${student.roll_number}</span>
            </li>
        `;
    });
    
    html += '</ul>';
    resultsContainer.innerHTML = html;
}

/**
 * Load notifications
 */
function loadNotifications() {
    ajaxRequest('../api/notifications.php', 'GET', null, function(response) {
        if (response.success) {
            updateNotificationBadge(response.unread_count);
            displayNotifications(response.data);
        }
    });
}

/**
 * Update notification badge
 */
function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline-block' : 'none';
    }
}

/**
 * Mark notification as read
 */
function markNotificationRead(notificationId) {
    const data = `notification_id=${notificationId}&action=mark_read`;
    
    ajaxRequest('../api/notifications.php', 'POST', data, function(response) {
        if (response.success) {
            loadNotifications();
        }
    });
}

/**
 * Delete record via AJAX
 */
function deleteRecord(id, type, redirectUrl = null) {
    if (!confirm('Are you sure you want to delete this record?')) {
        return;
    }
    
    showLoading('Deleting...');
    
    const data = `id=${id}&type=${type}&action=delete`;
    
    ajaxRequest('../api/delete.php', 'POST', data, function(response) {
        hideLoading();
        
        if (response.success) {
            showToast('Record deleted successfully!', 'success');
            
            if (redirectUrl) {
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1500);
            } else {
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }
        } else {
            showToast(response.message || 'Failed to delete record', 'error');
        }
    });
}

/**
 * Auto-save form data
 */
let autoSaveTimer;
function autoSaveForm(formId) {
    clearTimeout(autoSaveTimer);
    
    autoSaveTimer = setTimeout(() => {
        const form = document.getElementById(formId);
        if (!form) return;
        
        const formData = new FormData(form);
        const data = new URLSearchParams(formData).toString();
        
        ajaxRequest('../api/autosave.php', 'POST', data, function(response) {
            if (response.success) {
                console.log('Form auto-saved');
            }
        });
    }, 2000);
}

// Make functions globally available
window.ajaxRequest = ajaxRequest;
window.markAttendance = markAttendance;
window.submitAssignment = submitAssignment;
window.updateLeaveStatus = updateLeaveStatus;
window.loadStudentDetails = loadStudentDetails;
window.closeModal = closeModal;
window.loadAttendanceStats = loadAttendanceStats;
window.searchStudents = searchStudents;
window.loadNotifications = loadNotifications;
window.markNotificationRead = markNotificationRead;
window.deleteRecord = deleteRecord;
window.autoSaveForm = autoSaveForm;