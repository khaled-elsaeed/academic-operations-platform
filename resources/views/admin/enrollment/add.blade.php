@extends('layouts.home')

@section('title', 'Student Enrollment | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Page Header -->
  <x-ui.page-header 
    title="Student Enrollment"
    description="Search and enroll students in available courses"
    icon="bx bx-user-plus"
  >
  </x-ui.page-header>

  <!-- Student Search Section -->
  <div class="card mb-4">
    <div class="card-header d-flex align-items-center">
      <i class="bx bx-search me-2"></i>
      <h5 class="mb-0">Find Student</h5>
    </div>
    <div class="card-body">
      <form id="findStudentForm">
        <div class="row align-items-end">
          <div class="col-md-8">
            <label for="identifier" class="form-label fw-semibold">National ID or Academic ID</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bx bx-id-card"></i></span>
              <input type="text" class="form-control" id="identifier" name="identifier" required 
                     placeholder="Enter National ID or Academic ID">
            </div>
          </div>
          <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100">
              <i class="bx bx-search me-1"></i>
              Find Student
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Student Details and Enrollment Section -->
  <div id="studentDetails" style="display:none;">
    
    <!-- Student Information Card -->
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center">
        <i class="bx bx-user me-2"></i>
        <h5 class="mb-0">Student Information</h5>
      </div>
      <div class="card-body">
        <div id="studentInfo" class="row">
          <!-- Student details will be populated here -->
        </div>
      </div>
    </div>

    <!-- Term Selection -->
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center">
        <i class="bx bx-calendar me-2"></i>
        <h5 class="mb-0">Select Academic Term</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <label for="term_id" class="form-label fw-semibold">Academic Term</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bx bx-calendar-alt"></i></span>
              <select class="form-select" id="term_id" name="term_id" required>
                <option value="">Select Term</option>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="row">
      
      <!-- Enrollment History -->
      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
              <i class="bx bx-history me-2"></i>
              <h5 class="mb-0">Enrollment History</h5>
            </div>
            <span class="badge bg-label-secondary" id="historyCount">0</span>
          </div>
          <div class="card-body">
            <div id="enrollmentHistoryBox" class="enrollment-history-container">
              <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                <div class="text-center text-muted">
                  <i class="bx bx-search-alt-2 display-4 mb-3"></i>
                  <p>Find a student to view enrollment history</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Available Courses -->
      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
              <i class="bx bx-book me-2"></i>
              <h5 class="mb-0">Available Courses</h5>
            </div>
            <span class="badge bg-label-primary" id="coursesCount">0</span>
          </div>
          <div class="card-body">
            <form id="enrollForm">
              <input type="hidden" id="student_id" name="student_id">
              <div id="coursesBox" class="courses-container">
                <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                  <div class="text-center text-muted">
                    <i class="bx bx-book-bookmark display-4 mb-3"></i>
                    <p>Select a term to view available courses</p>
                  </div>
                </div>
              </div>
              <div class="mt-3">
                <button type="submit" class="btn btn-success w-100" id="enrollBtn" style="display: none;">
                  <i class="bx bx-plus me-1"></i>
                  Enroll Selected Courses
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<style>
.enrollment-history-container {
  max-height: 350px;
  overflow-y: auto;
  border: 1px solid var(--bs-border-color);
  border-radius: 0.375rem;
  padding: 1rem;
}

.courses-container {
  max-height: 350px;
  overflow-y: auto;
  border: 1px solid var(--bs-border-color);
  border-radius: 0.375rem;
  padding: 1rem;
}

.course-item {
  border: 1px solid var(--bs-border-color);
  border-radius: 0.375rem;
  padding: 1rem;
  margin-bottom: 0.75rem;
  transition: all 0.2s ease;
}

.course-item:hover {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border-color: var(--bs-primary);
}

.course-item:last-child {
  margin-bottom: 0;
}

.history-item {
  border-left: 3px solid var(--bs-primary);
  padding-left: 1rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
}

.history-item:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
}

.student-info-item {
  border-bottom: 1px solid var(--bs-border-color);
  padding-bottom: 0.5rem;
  margin-bottom: 0.75rem;
}

.student-info-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.capacity-badge {
  font-size: 0.75rem;
}

.loading-spinner {
  width: 1.5rem;
  height: 1.5rem;
}
</style>
@endsection

@push('scripts')
<script>
/**
 * Shows a loading spinner in the given container.
 * @function showLoading
 * @param {string} container - The selector for the container.
 * @param {string} message - The loading message.
 * @returns {void}
 */
function showLoading(container, message = 'Loading...') {
  $(container).html(`
    <div class="d-flex justify-content-center align-items-center" style="min-height: 100px;">
      <div class="text-center">
        <div class="spinner-border text-primary loading-spinner mb-3" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-muted mb-0">${message}</p>
      </div>
    </div>
  `);
}

/**
 * Loads the enrollment history for a student and updates the DOM.
 * @function loadEnrollmentHistory
 * @param {number|null} studentId
 * @returns {void}
 */
function loadEnrollmentHistory(studentId) {
  if (!studentId) {
    $('#enrollmentHistoryBox').html(`
      <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
        <div class="text-center text-muted">
          <i class="bx bx-search-alt-2 display-4 mb-3"></i>
          <p>Find a student to view enrollment history</p>
        </div>
      </div>
    `);
    $('#historyCount').text('0');
    return;
  }
  showLoading('#enrollmentHistoryBox', 'Loading enrollment history...');
  $.ajax({
    url: '{{ route('admin.enrollments.studentEnrollments') }}',
    method: 'POST',
    data: { student_id: studentId, _token: '{{ csrf_token() }}' },
    success: function(res) {
      let history = (res.enrollments || []);
      $('#historyCount').text(history.length);
      if (history.length === 0) {
        $('#enrollmentHistoryBox').html(`
          <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
            <div class="text-center text-muted">
              <i class="bx bx-info-circle display-4 mb-3"></i>
              <p>No enrollment history found</p>
            </div>
          </div>
        `);
      } else {
        let html = '';
        history.forEach(function(enr, index) {
          html += `
            <div class="history-item">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="mb-1">${enr.course ? enr.course.name : 'Course #' + enr.course_id}</h6>
                  <p class="text-muted mb-1">
                    <i class="bx bx-calendar me-1"></i>
                    ${enr.term ? enr.term.name : 'Term #' + enr.term_id}
                  </p>
                  <small class="text-muted">
                    <i class="bx bx-time me-1"></i>
                    Enrolled: ${enr.created_at ? new Date(enr.created_at).toLocaleDateString() : 'N/A'}
                  </small>
                </div>
                <span class="badge bg-label-success">Enrolled</span>
              </div>
            </div>
          `;
        });
        $('#enrollmentHistoryBox').html(html);
      }
    },
    error: function() {
      $('#enrollmentHistoryBox').html(`
        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
          <div class="text-center text-muted">
            <i class="bx bx-error-circle display-4 mb-3"></i>
            <p>Could not load enrollment history</p>
          </div>
        </div>
      `);
      $('#historyCount').text('0');
    }
  });
}

/**
 * Loads available courses for a student and term, and updates the DOM.
 * @function loadAvailableCourses
 * @param {number|null} studentId
 * @param {number|null} termId
 * @returns {void}
 */
function loadAvailableCourses(studentId, termId) {
  if (!studentId || !termId) {
    $('#coursesBox').html(`
      <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
        <div class="text-center text-muted">
          <i class="bx bx-book-bookmark display-4 mb-3"></i>
          <p>Select a term to view available courses</p>
        </div>
      </div>
    `);
    $('#coursesCount').text('0');
    $('#enrollBtn').hide();
    return;
  }
  showLoading('#coursesBox', 'Loading available courses...');
  $.ajax({
    url: '{{ route('admin.enrollments.availableCourses') }}',
    method: 'POST',
    data: { student_id: studentId, term_id: termId, _token: '{{ csrf_token() }}' },
    success: function(res) {
      let courses = (res.courses || []);
      $('#coursesCount').text(courses.length);
      if (courses.length === 0) {
        $('#coursesBox').html(`
          <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
            <div class="text-center text-muted">
              <i class="bx bx-info-circle display-4 mb-3"></i>
              <p>No available courses found for this term</p>
            </div>
          </div>
        `);
        $('#enrollBtn').hide();
      } else {
        let html = '';
        courses.forEach(function(course) {
          const capacityClass = course.remaining_capacity > 10 ? 'success' : 
                              course.remaining_capacity > 5 ? 'warning' : 'danger';
          html += `
            <div class="course-item">
              <div class="form-check">
                <input class="form-check-input course-checkbox" type="checkbox" 
                       name="available_course_ids[]" value="${course.available_course_id}" 
                       id="course_${course.available_course_id}">
                <label class="form-check-label w-100" for="course_${course.available_course_id}">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">${course.name}</h6>
                      <p class="text-muted mb-0 small">
                        <i class="bx bx-book me-1"></i>
                        Course Code: ${course.code || 'N/A'}
                      </p>
                    </div>
                    <span class="badge bg-label-${capacityClass} capacity-badge">
                      <i class="bx bx-group me-1"></i>
                      ${course.remaining_capacity} spots left
                    </span>
                  </div>
                </label>
              </div>
            </div>
          `;
        });
        $('#coursesBox').html(html);
        $('#enrollBtn').show();
        updateEnrollButton();
        $('.course-checkbox').on('change', updateEnrollButton);
      }
    },
    error: function() {
      $('#coursesBox').html(`
        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
          <div class="text-center text-muted">
            <i class="bx bx-error-circle display-4 mb-3"></i>
            <p>Could not load available courses</p>
          </div>
        </div>
      `);
      $('#coursesCount').text('0');
      $('#enrollBtn').hide();
    }
  });
}

/**
 * Updates the enroll button state based on selected courses.
 * @function updateEnrollButton
 * @returns {void}
 */
function updateEnrollButton() {
  const selectedCount = $('.course-checkbox:checked').length;
  const enrollBtn = $('#enrollBtn');
  if (selectedCount > 0) {
    enrollBtn.html(`
      <i class="bx bx-plus me-1"></i>Enroll (${selectedCount})
    `).prop('disabled', false);
  } else {
    enrollBtn.html('<i class="bx bx-plus me-1"></i>Enroll').prop('disabled', true);
  }
}

// Main entry point
$(document).ready(function () {
  let currentStudentId = null;
  let currentTermId = null;

  $('#findStudentForm').on('submit', function(e) {
    e.preventDefault();
    let identifier = $('#identifier').val();
    
    $.ajax({
      url: '{{ route('admin.enrollments.findStudent') }}',
      method: 'POST',
      data: { identifier: identifier, _token: '{{ csrf_token() }}' },
      success: function(res) {
        let s = res.student;
        $('#student_id').val(s.id);
        
        // Build student info HTML
        let studentInfoHtml = `
          <div class="col-md-6">
            <div class="student-info-item">
              <small class="text-muted">Full Name (English)</small>
              <h6 class="mb-0">${s.name_en}</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">Full Name (Arabic)</small>
              <h6 class="mb-0">${s.name_ar}</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">Academic Email</small>
              <h6 class="mb-0">${s.academic_email}</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">Program</small>
              <h6 class="mb-0">${s.program ? s.program.name : 'Not Assigned'}</h6>
            </div>
          </div>
          <div class="col-md-6">
            <div class="student-info-item">
              <small class="text-muted">Academic ID</small>
              <h6 class="mb-0">${s.academic_id}</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">National ID</small>
              <h6 class="mb-0">${s.national_id}</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">Level</small>
              <h6 class="mb-0">Level ${s.level}</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">CGPA</small>
              <h6 class="mb-0">${s.cgpa || 'N/A'}</h6>
            </div>
          </div>
        `;
        
        $('#studentInfo').html(studentInfoHtml);
        $('#studentDetails').show();
        currentStudentId = s.id;
        
        // Load enrollment history
        loadEnrollmentHistory(currentStudentId);
        
        // Handle term selection
        $('#term_id').off('change').on('change', function() {
          currentTermId = $(this).val();
          loadAvailableCourses(currentStudentId, currentTermId);
        });
        
        // Trigger change if term is already selected
        if ($('#term_id').val()) {
          $('#term_id').trigger('change');
        }
      },
      error: function(xhr) {
        $('#studentDetails').hide();
        Swal.fire({
          icon: 'error',
          title: 'Student Not Found',
          text: xhr.responseJSON?.message || 'Could not find student with the provided identifier',
          confirmButtonText: 'Try Again'
        });
      }
    });
  });

  // Load terms for the dropdown
  function loadTerms() {
    $.ajax({
      url: '{{ route('admin.terms.index') }}',
      method: 'GET',
      success: function (data) {
        let $termSelect = $('#term_id');
        $termSelect.empty().append('<option value="">Select Term</option>');
        (data || []).forEach(function (term) {
          $termSelect.append(`<option value="${term.id}">${term.name}</option>`);
        });
      },
      error: function() {
        console.error('Failed to load terms');
      }
    });
  }
  
  loadTerms();

  $('#enrollForm').on('submit', function(e) {
    e.preventDefault();
    
    const selectedCourses = $('.course-checkbox:checked').length;
    if (selectedCourses === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'No Courses Selected',
        text: 'Please select at least one course to enroll the student.',
        confirmButtonText: 'OK'
      });
      return;
    }
    
    let formData = new FormData(this);
    formData.append('student_id', $('#student_id').val());
    formData.append('term_id', $('#term_id').val());
    formData.append('_token', '{{ csrf_token() }}');

    // Show loading state
    const enrollBtn = $('#enrollBtn');
    const originalText = enrollBtn.html();
    enrollBtn.html('<i class="bx bx-loader-alt bx-spin me-1"></i>Processing...').prop('disabled', true);

    $.ajax({
      url: '{{ route('admin.enrollments.store') }}',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(res) {
        Swal.fire({
          icon: 'success',
          title: 'Enrollment Successful!',
          text: `Student has been enrolled in ${selectedCourses} course(s).`,
          confirmButtonText: 'Great!'
        }).then(() => {
          // Reset form and reload data
          $('.course-checkbox').prop('checked', false);
          loadEnrollmentHistory(currentStudentId);
          loadAvailableCourses(currentStudentId, currentTermId);
        });
      },
      error: function(xhr) {
        Swal.fire({
          icon: 'error',
          title: 'Enrollment Failed',
          text: xhr.responseJSON?.message || 'An error occurred during enrollment. Please try again.',
          confirmButtonText: 'Try Again'
        });
      },
      complete: function() {
        // Restore button state
        enrollBtn.html(originalText).prop('disabled', false);
        updateEnrollButton();
      }
    });
  });
});
</script>
@endpush