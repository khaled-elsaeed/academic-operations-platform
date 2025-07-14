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
        <h5 class="mb-0">Academic Term</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-8">
            <label for="term_id" class="form-label fw-semibold">
              <i class="bx bx-calendar-alt me-1"></i> Academic Term <span class="text-danger">*</span>
            </label>
            <small class="form-text text-muted mb-2 d-block">Please select the academic term for enrollment.</small>
            <select class="form-select select2-term" id="term_id" name="term_id" required aria-label="Academic Term">
              <option value="">Please select an academic term</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Credit Hour Info Box -->
    <div id="chInfoBox" class="alert alert-info mt-3" style="display:none;">
      <strong>Maximum Allowed Credit Hours (CH):</strong>
      <table class="table table-bordered table-sm mb-0" style="background:white;">
        <thead>
          <tr>
            <th>Season</th>
            <th>CGPA</th>
            <th>Max CH</th>
            <th>For Graduation</th>
          </tr>
        </thead>
        <tbody>
          <tr class="ch-row" data-season="Fall" data-cgpa="lt2">
            <td>Fall</td>
            <td>&lt;2</td>
            <td>14</td>
            <td>+3</td>
          </tr>
          <tr class="ch-row" data-season="Fall" data-cgpa="2to3">
            <td>Fall</td>
            <td>&ge;2 and &lt;3</td>
            <td>18</td>
            <td>+3</td>
          </tr>
          <tr class="ch-row" data-season="Fall" data-cgpa="gte3">
            <td>Fall</td>
            <td>&ge;3</td>
            <td>21</td>
            <td>+3</td>
          </tr>
          <tr class="ch-row" data-season="Spring" data-cgpa="lt2">
            <td>Spring</td>
            <td>&lt;2</td>
            <td>14</td>
            <td>+3</td>
          </tr>
          <tr class="ch-row" data-season="Spring" data-cgpa="2to3">
            <td>Spring</td>
            <td>&ge;2 and &lt;3</td>
            <td>18</td>
            <td>+3</td>
          </tr>
          <tr class="ch-row" data-season="Spring" data-cgpa="gte3">
            <td>Spring</td>
            <td>&ge;3</td>
            <td>21</td>
            <td>+3</td>
          </tr>
          <tr class="ch-row" data-season="Summer" data-cgpa="any">
            <td>Summer</td>
            <td>Any</td>
            <td>9</td>
            <td>+3</td>
          </tr>
        </tbody>
      </table>
      <div class="mt-2 small text-muted">
        <strong>Note:</strong> To exceed the maximum credit hours for graduation, an administrator must grant permission.
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="row">
      
      <!-- Enrollment History -->
      <div class="col-lg-4 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="d-flex align-items-center">
                <i class="bx bx-history me-2"></i>
                <h5 class="mb-0">Enrollment History</h5>
              </div>
              <span class="badge bg-label-secondary" id="historyCount">0</span>
            </div>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bx bx-search"></i></span>
              <input type="text" class="form-control" id="historySearch" placeholder="Search courses, terms, or grades...">
            </div>
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
      <div class="col-lg-4 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="d-flex align-items-center">
                <i class="bx bx-book me-2"></i>
                <h5 class="mb-0">Available Courses</h5>
              </div>
              <span class="badge bg-label-primary" id="coursesCount">0</span>
            </div>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bx bx-search"></i></span>
              <input type="text" class="form-control" id="coursesSearch" placeholder="Search course names or codes...">
            </div>
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

      <!-- Course Prerequisites -->
      <div class="col-lg-4 mb-4">
        <div class="card h-100">
          <div class="card-header">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="d-flex align-items-center">
                <i class="bx bx-link me-2"></i>
                <h5 class="mb-0">Course Prerequisites</h5>
              </div>
              <span class="badge bg-label-info" id="prerequisitesCount">0</span>
            </div>
            <div class="input-group input-group-sm">
              <span class="input-group-text"><i class="bx bx-search"></i></span>
              <input type="text" class="form-control" id="prerequisitesSearch" placeholder="Search prerequisite courses...">
            </div>
          </div>
          <div class="card-body">
            <div id="prerequisitesBox" class="prerequisites-container">
              <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                <div class="text-center text-muted">
                  <i class="bx bx-link-alt display-4 mb-3"></i>
                  <p>Select courses to view prerequisites</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Credit Hours Summary (Outside the main row) -->
    <div class="row mt-4">
      <div class="col-12">
        <div id="creditHoursSummary" style="display:none;">
          <div class="alert alert-info mb-0 p-3">
            <div class="row text-center">
              <div class="col-md-3">
                <div class="credit-hours-item">
                  <i class="bx bx-book-open text-primary mb-1"></i>
                  <div class="credit-hours-label">Current Enrollment</div>
                  <div class="credit-hours-value" id="currentEnrollmentHours">0</div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="credit-hours-item">
                  <i class="bx bx-plus-circle text-success mb-1"></i>
                  <div class="credit-hours-label">Selected Additional</div>
                  <div class="credit-hours-value" id="selectedCH">0</div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="credit-hours-item">
                  <i class="bx bx-target-lock text-warning mb-1"></i>
                  <div class="credit-hours-label">Max Allowed</div>
                  <div class="credit-hours-value" id="maxCH">0</div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="credit-hours-item">
                  <i class="bx bx-time text-info mb-1"></i>
                  <div class="credit-hours-label">Remaining</div>
                  <div class="credit-hours-value" id="remainingCH">0</div>
                </div>
              </div>
            </div>
            <div class="mt-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-muted">Credit Hours Usage</small>
                <small class="text-muted" id="usagePercentage">0%</small>
              </div>
              <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-success" id="usageProgressBar" role="progressbar" style="width: 0%"></div>
              </div>
            </div>
          </div>
          <!-- Exception alert container -->
          <div id="exceptionAlert" style="display:none;"></div>
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

.prerequisites-container {
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

.prerequisite-item {
  border: 1px solid var(--bs-border-color);
  border-radius: 0.375rem;
  padding: 1rem;
  margin-bottom: 0.75rem;
  transition: all 0.2s ease;
}

.prerequisite-item:hover {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border-color: var(--bs-info);
}

.prerequisite-item:last-child {
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

.credit-hours-item {
  padding: 0.5rem;
  border-radius: 0.375rem;
  background: rgba(255, 255, 255, 0.7);
  transition: all 0.2s ease;
}

.credit-hours-item:hover {
  background: rgba(255, 255, 255, 0.9);
  transform: translateY(-2px);
}

.credit-hours-item i {
  font-size: 1.5rem;
  display: block;
}

.credit-hours-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--bs-gray-600);
  margin-bottom: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.credit-hours-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--bs-dark);
  line-height: 1;
}

.credit-hours-item .bx {
  font-size: 1.75rem;
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
                url: '{{ route('enrollments.studentEnrollments') }}',
    method: 'POST',
    data: { student_id: studentId, _token: '{{ csrf_token() }}' },
    success: function(res) {
      let history = (res.data || []);
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
                  ${enr.score ? `<div class="mt-1"><span class="badge bg-label-primary"><i class="bx bx-star me-1"></i>Grade: <strong>${parseFloat(enr.score).toFixed(2)}</strong></span></div>` : '<div class="mt-1"><span class="badge bg-label-secondary"><i class="bx bx-time me-1"></i>No Grade Yet</span></div>'}
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
    $('#creditHoursSummary').hide();
    $('#exceptionAlert').hide();
    $('#prerequisitesBox').html(`
      <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
        <div class="text-center text-muted">
          <i class="bx bx-link-alt display-4 mb-3"></i>
          <p>Select courses to view prerequisites</p>
        </div>
      </div>
    `);
    $('#prerequisitesCount').text('0');
    return;
  }
  showLoading('#coursesBox', 'Loading available courses...');
  $.ajax({
    url: '{{ route('available_courses.all') }}',
    method: 'GET',
    data: { student_id: studentId, term_id: termId },
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
        $('#creditHoursSummary').hide();
        $('#exceptionAlert').hide();
        $('#prerequisitesBox').html(`
          <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
            <div class="text-center text-muted">
              <i class="bx bx-link-alt display-4 mb-3"></i>
              <p>Select courses to view prerequisites</p>
            </div>
          </div>
        `);
        $('#prerequisitesCount').text('0');
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
                       data-credit-hours="${course.credit_hours}" 
                       id="course_${course.available_course_id}">
                <label class="form-check-label w-100" for="course_${course.available_course_id}">
                  <div class="d-flex justify-content-between align-items-start">
                    <div>
                      <h6 class="mb-1">${course.name}</h6>
                      <p class="text-muted mb-0 small">
                        <i class="bx bx-book me-1"></i>
                        Course Code: ${course.code || 'N/A'}
                        <span class="ms-2"><i class="bx bx-timer me-1"></i>Credit Hours: <strong>${course.credit_hours}</strong></span>
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
        updateCreditHoursSummary();
        $('.course-checkbox').on('change', function() {
          updateEnrollButton();
          updateCreditHoursSummary();
          
          // Load prerequisites for selected courses
          const selectedCourseIds = [];
          $('.course-checkbox:checked').each(function() {
            selectedCourseIds.push($(this).val());
          });
          loadPrerequisites(currentStudentId, selectedCourseIds);
        });
        
        // Load remaining credit hours for the student in this term
        loadRemainingCreditHours(studentId, termId);
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
      $('#creditHoursSummary').hide();
      $('#exceptionAlert').hide();
      $('#prerequisitesBox').html(`
        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
          <div class="text-center text-muted">
            <i class="bx bx-link-alt display-4 mb-3"></i>
            <p>Select courses to view prerequisites</p>
          </div>
        </div>
      `);
      $('#prerequisitesCount').text('0');
    }
  });
}

/**
 * Loads prerequisites for selected courses and checks enrollment status.
 * @function loadPrerequisites
 * @param {number} studentId
 * @param {Array} selectedCourseIds
 * @returns {void}
 */
function loadPrerequisites(studentId, selectedCourseIds) {
  if (!studentId || !selectedCourseIds || selectedCourseIds.length === 0) {
    $('#prerequisitesBox').html(`
      <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
        <div class="text-center text-muted">
          <i class="bx bx-link-alt display-4 mb-3"></i>
          <p>Select courses to view prerequisites</p>
        </div>
      </div>
    `);
    $('#prerequisitesCount').text('0');
    return;
  }

  showLoading('#prerequisitesBox', 'Loading prerequisites...');
  
  $.ajax({
    url: '{{ route('courses.prerequisites') }}',
    method: 'POST',
    data: { 
      student_id: studentId, 
      course_ids: selectedCourseIds,
      _token: '{{ csrf_token() }}' 
    },
    success: function(res) {
      let prerequisites = (res.data || []);
      $('#prerequisitesCount').text(prerequisites.length);
      
      if (prerequisites.length === 0) {
        $('#prerequisitesBox').html(`
          <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
            <div class="text-center text-muted">
              <i class="bx bx-check-circle display-4 mb-3"></i>
              <p>No prerequisites found for selected courses</p>
            </div>
          </div>
        `);
      } else {
        let html = '';
        prerequisites.forEach(function(prereq) {
          const statusClass = prereq.is_enrolled ? 'success' : 'danger';
          const statusIcon = prereq.is_enrolled ? 'bx-check-circle' : 'bx-x-circle';
          const statusText = prereq.is_enrolled ? 'Enrolled' : 'Not Enrolled';
          
          html += `
            <div class="prerequisite-item">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="mb-1">${prereq.course_name}</h6>
                  <p class="text-muted mb-0 small">
                    <i class="bx bx-book me-1"></i>
                    Course Code: ${prereq.course_code || 'N/A'}
                    <span class="ms-2"><i class="bx bx-timer me-1"></i>Credit Hours: <strong>${prereq.credit_hours}</strong></span>
                  </p>
                  <small class="text-muted">
                    <i class="bx bx-link me-1"></i>
                    Prerequisite for: ${prereq.required_for}
                  </small>
                </div>
                <span class="badge bg-label-${statusClass}">
                  <i class="bx ${statusIcon} me-1"></i>
                  ${statusText}
                </span>
              </div>
            </div>
          `;
        });
        $('#prerequisitesBox').html(html);
      }
    },
    error: function(xhr) {
      console.error('Failed to load prerequisites:', xhr);
      $('#prerequisitesBox').html(`
        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
          <div class="text-center text-muted">
            <i class="bx bx-error-circle display-4 mb-3"></i>
            <p>Could not load prerequisites</p>
          </div>
        </div>
      `);
      $('#prerequisitesCount').text('0');
    }
  });
}

/**
 * Loads remaining credit hours for a student in a specific term.
 * @function loadRemainingCreditHours
 * @param {number} studentId
 * @param {number} termId
 * @returns {void}
 */
function loadRemainingCreditHours(studentId, termId) {
  if (!studentId || !termId) {
    return;
  }
  
  $.ajax({
    url: '{{ route('available_courses.remaining-credit-hours') }}',
    method: 'POST',
    data: { 
      student_id: studentId, 
      term_id: termId, 
      _token: '{{ csrf_token() }}' 
    },
    success: function(res) {
      if (res.success && res.data) {
        const data = res.data;
        
        // Update the credit hours summary with real data
        $('#currentEnrollmentHours').text(data.current_enrollment_hours);
        $('#selectedCH').text('0'); // Reset selected courses
        $('#maxCH').text(data.max_allowed_hours);
        $('#remainingCH').text(data.remaining_hours);
        
        // Update progress bar
        updateCreditHoursProgress(data.current_enrollment_hours, data.max_allowed_hours);
        
        // Show additional information if there's an exception
        const exceptionAlert = $('#exceptionAlert');
        if (data.exception_hours > 0) {
          exceptionAlert.html(`
            <div class="alert alert-warning alert-sm mt-2 mb-0">
              <div class="d-flex align-items-center">
                <i class="bx bx-shield-check me-2 text-warning"></i>
                <div>
                  <strong>Admin Exception:</strong> +${data.exception_hours} additional credit hours granted
                </div>
              </div>
            </div>
          `).show();
        } else {
          exceptionAlert.hide();
        }
        
        // Show the credit hours summary
        $('#creditHoursSummary').show();
        
        // Update the credit hours info box with student's actual CGPA
        updateChInfoBoxWithStudentData(data.student_cgpa, data.term_season);
      }
    },
    error: function(xhr) {
      console.error('Failed to load remaining credit hours:', xhr);
      // Fall back to the default credit hours calculation
      updateCreditHoursSummary();
    }
  });
}

/**
 * Updates the credit hours info box with student's actual data.
 * @function updateChInfoBoxWithStudentData
 * @param {number} cgpa
 * @param {string} season
 * @returns {void}
 */
function updateChInfoBoxWithStudentData(cgpa, season) {
  if (!season) return;
  
  const seasonCapitalized = season.charAt(0).toUpperCase() + season.slice(1);
  
  // Show the info box
  $('#chInfoBox').show();
  
  // Hide all rows first
  $('.ch-row').hide();
  
  // Show the appropriate row based on season and CGPA
  if (season.toLowerCase() === 'summer') {
    // Summer has fixed 9 hours regardless of CGPA
    $(`.ch-row[data-season='${seasonCapitalized}']`).show();
  } else {
    // For Fall and Spring, show the appropriate CGPA-based row
    let cgpaRange = '';
    if (cgpa < 2.0) {
      cgpaRange = 'lt2';
    } else if (cgpa >= 2.0 && cgpa < 3.0) {
      cgpaRange = '2to3';
    } else if (cgpa >= 3.0) {
      cgpaRange = 'gte3';
    }
    
    // Highlight the current student's CGPA range
    $(`.ch-row[data-season='${seasonCapitalized}'][data-cgpa='${cgpaRange}']`).addClass('table-primary');
    $(`.ch-row[data-season='${seasonCapitalized}']`).show();
  }
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

// Credit Hour Info Box Logic
function updateChInfoBox(termName) {
  if (!termName) {
    $('#chInfoBox').hide();
    return;
  }
  let season = '';
  termName = termName.toLowerCase();
  if (termName.includes('fall')) season = 'Fall';
  else if (termName.includes('spring')) season = 'Spring';
  else if (termName.includes('summer')) season = 'Summer';

  if (season) {
    $('#chInfoBox').show();
    $('.ch-row').hide();
    $(`.ch-row[data-season='${season}']`).show();
  } else {
    $('#chInfoBox').hide();
  }
}

// When the term is changed, update the info box
$('#term_id').on('change', function() {
  let selectedText = $('#term_id option:selected').text();
  updateChInfoBox(selectedText);
});

// On page load, in case a term is already selected
$(document).ready(function() {
  let selectedText = $('#term_id option:selected').text();
  updateChInfoBox(selectedText);
});

function updateCreditHoursSummary() {
  let selectedTotal = 0;
  $('.course-checkbox:checked').each(function() {
    selectedTotal += parseInt($(this).data('credit-hours')) || 0;
  });
  
  // Get current enrollment hours from the display (set by AJAX)
  let currentEnrollmentHours = parseInt($('#currentEnrollmentHours').text()) || 0;
  
  // Calculate new total if courses are selected
  let newTotal = currentEnrollmentHours + selectedTotal;
  
  // Get max allowed from the info box (visible row)
  let maxCH = parseInt($('#maxCH').text()) || 0;
  let remaining = maxCH - newTotal;
  
  // Update the display to show what would be the new total
  $('#selectedCH').text(newTotal);
  $('#remainingCH').text(Math.max(0, remaining));
  
  if ($('.course-checkbox').length > 0) {
    $('#creditHoursSummary').show();
  } else {
    $('#creditHoursSummary').hide();
  }
  
  // Update progress bar with new total
  updateCreditHoursProgress(newTotal, maxCH);
}

/**
 * Updates the credit hours progress bar.
 * @function updateCreditHoursProgress
 * @param {number} current - Current total credit hours
 * @param {number} max - Maximum allowed credit hours
 * @returns {void}
 */
function updateCreditHoursProgress(current, max) {
  if (max <= 0) return;
  
  const percentage = Math.min((current / max) * 100, 100);
  const progressBar = $('#usageProgressBar');
  const percentageText = $('#usagePercentage');
  
  // Update progress bar
  progressBar.css('width', percentage + '%');
  
  // Update percentage text
  percentageText.text(Math.round(percentage) + '%');
  
  // Update progress bar color based on usage
  progressBar.removeClass('bg-success bg-warning bg-danger');
  if (percentage < 70) {
    progressBar.addClass('bg-success');
  } else if (percentage < 90) {
    progressBar.addClass('bg-warning');
  } else {
    progressBar.addClass('bg-danger');
  }
}

// Global variables
let currentStudentId = null;
let currentTermId = null;

// Main entry point
$(document).ready(function () {

  $('#findStudentForm').on('submit', function(e) {
    e.preventDefault();
    let identifier = $('#identifier').val();
    
    // Reset term selection, history, and available courses before searching
    $('#term_id').val('').trigger('change');
    $('#enrollmentHistoryBox').html(`
      <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
        <div class="text-center text-muted">
          <i class="bx bx-search-alt-2 display-4 mb-3"></i>
          <p>Find a student to view enrollment history</p>
        </div>
      </div>
    `);
    $('#historyCount').text('0');
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
    $('#creditHoursSummary').hide();
    $('#exceptionAlert').hide();
    $('#prerequisitesBox').html(`
      <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
        <div class="text-center text-muted">
          <i class="bx bx-link-alt display-4 mb-3"></i>
          <p>Select courses to view prerequisites</p>
        </div>
      </div>
    `);
    $('#prerequisitesCount').text('0');
    
    $.ajax({
                  url: '{{ route('enrollments.findStudent') }}',
      method: 'POST',
      data: { identifier: identifier, _token: '{{ csrf_token() }}' },
      success: function(res) {
        let s = res.data;
        $('#student_id').val(s.id);
        
        // Build student info HTML
        let studentInfoHtml = `
          <div class="col-md-4">
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
          <div class="col-md-4">
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
              <h6 class="mb-0">Level ${s.level.name}</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">CGPA</small>
              <h6 class="mb-0">${s.cgpa || 'N/A'}</h6>
            </div>
          </div>
          <div class="col-md-4">
            <div class="student-info-item">
              <small class="text-muted">Total Units Taken</small>
              <h6 class="mb-0">${s.taken_hours || 0} Units</h6>
            </div>
           
            <div class="student-info-item">
              <small class="text-muted">Remaining Hours</small>
              <h6 class="mb-0">Unknown</h6>
            </div>
          </div>
        `;
        
        $('#studentInfo').html(studentInfoHtml);
        $('#studentDetails').show();
        currentStudentId = s.id;
        currentTermId = null; // Reset current term ID
        
        // Load enrollment history
        loadEnrollmentHistory(currentStudentId);
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

  // Initialize Select2 ONCE after DOM is ready
  $('#term_id').select2({
    theme: 'bootstrap-5',
    placeholder: 'Please select an academic term',
    allowClear: true,
    width: '100%'
  });

  // Load terms for the dropdown
  function loadTerms() {
    $.ajax({
      url: '{{ route('terms.all') }}',
      method: 'GET',
      success: function (response) {
        let $termSelect = $('#term_id');
        $termSelect.empty().append('<option value="">Please select an academic term</option>');
        response.data.forEach(function (term) {
          $termSelect.append(`<option value="${term.id}">${term.name}</option>`);
        });
      },
      error: function() {
        console.error('Failed to load terms');
      }
    });
  }
  
  loadTerms();

  $('#term_id').on('change', function() {
    currentTermId = $(this).val();
    loadAvailableCourses(currentStudentId, currentTermId);
  });

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
                  url: '{{ route('enrollments.store') }}',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(res) {
        // Show loading modal and send AJAX for pdf only
        Swal.fire({
          title: 'Generating pdf Document...',
          html: '<div class="text-center">Please wait while your pdf document is being generated.</div>',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => {
            Swal.showLoading();
            let url = `{{ route('students.download.pdf', ':id') }}?term_id=${currentTermId}`.replace(':id', currentStudentId); // or use academic_id/national_id
            $.ajax({
              url: url,
              method: 'GET',
              success: function(response) {
                let fileUrl = response.url || (response.data && response.data.url);
                if (fileUrl) {
                  window.open(fileUrl, '_blank');
                  Swal.fire({
                    icon: 'success',
                    title: 'Pdf Ready',
                    html: `<div>Your pdf document is ready for download.</div>`,
                    showConfirmButton: false,
                    timer: 5000
                  });
                } else {
                  Swal.fire('Error', 'No file URL returned from server.', 'error');
                }
              },
              error: function() {
                Swal.fire('Error', 'Failed to generate pdf document.', 'error');
              }
            });
          }
        });
        // Reset form and reload data
        $('.course-checkbox').prop('checked', false);
        loadEnrollmentHistory(currentStudentId);
        loadAvailableCourses(currentStudentId, currentTermId);
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

  // ===========================
  // SEARCH FUNCTIONALITY
  // ===========================

  // Global variables to store original data
  let originalHistoryData = [];
  let originalCoursesData = [];
  let originalPrerequisitesData = [];

  // Enrollment History Search
  $('#historySearch').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    filterEnrollmentHistory(searchTerm);
  });

  function filterEnrollmentHistory(searchTerm) {
    if (!originalHistoryData.length) return;
    
    const filteredData = originalHistoryData.filter(enrollment => {
      const courseName = (enrollment.course?.name || '').toLowerCase();
      const courseCode = (enrollment.course?.code || '').toLowerCase();
      const termName = (enrollment.term?.name || '').toLowerCase();
      const score = enrollment.score ? enrollment.score.toString() : '';
      
      return courseName.includes(searchTerm) || 
             courseCode.includes(searchTerm) || 
             termName.includes(searchTerm) || 
             score.includes(searchTerm);
    });

    displayFilteredHistory(filteredData);
  }

  function displayFilteredHistory(enrollments) {
    if (enrollments.length === 0) {
      $('#enrollmentHistoryBox').html(`
        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
          <div class="text-center text-muted">
            <i class="bx bx-search-alt display-4 mb-3"></i>
            <p>No enrollments found matching your search</p>
          </div>
        </div>
      `);
    } else {
      let html = '';
      enrollments.forEach(function(enr) {
        html += `
          <div class="history-item">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h6 class="mb-1">${enr.course ? enr.course.name : 'Course #' + enr.course_id}</h6>
                <p class="text-muted mb-1">
                  <i class="bx bx-calendar me-1"></i>
                  ${enr.term ? enr.term.name : 'Term #' + enr.term_id}
                </p>
                ${enr.score ? `<div class="mt-1"><span class="badge bg-label-primary"><i class="bx bx-star me-1"></i>Grade: <strong>${parseFloat(enr.score).toFixed(2)}</strong></span></div>` : '<div class="mt-1"><span class="badge bg-label-secondary"><i class="bx bx-time me-1"></i>No Grade Yet</span></div>'}
              </div>
              <span class="badge bg-label-success">Enrolled</span>
            </div>
          </div>
        `;
      });
      $('#enrollmentHistoryBox').html(html);
    }
  }

  // Available Courses Search
  $('#coursesSearch').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    filterAvailableCourses(searchTerm);
  });

  function filterAvailableCourses(searchTerm) {
    if (!originalCoursesData.length) return;
    
    const filteredData = originalCoursesData.filter(course => {
      const courseName = (course.name || '').toLowerCase();
      const courseCode = (course.code || '').toLowerCase();
      
      return courseName.includes(searchTerm) || courseCode.includes(searchTerm);
    });

    displayFilteredCourses(filteredData);
  }

  function displayFilteredCourses(courses) {
    if (courses.length === 0) {
      $('#coursesBox').html(`
        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
          <div class="text-center text-muted">
            <i class="bx bx-search-alt display-4 mb-3"></i>
            <p>No courses found matching your search</p>
          </div>
        </div>
      `);
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
                     data-credit-hours="${course.credit_hours}" 
                     id="course_${course.available_course_id}">
              <label class="form-check-label w-100" for="course_${course.available_course_id}">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <h6 class="mb-1">${course.name}</h6>
                    <p class="text-muted mb-0 small">
                      <i class="bx bx-book me-1"></i>
                      Course Code: ${course.code || 'N/A'}
                      <span class="ms-2"><i class="bx bx-timer me-1"></i>Credit Hours: <strong>${course.credit_hours}</strong></span>
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
      
      // Reattach event handlers
      $('.course-checkbox').on('change', function() {
        updateEnrollButton();
        updateCreditHoursSummary();
        
        // Load prerequisites for selected courses
        const selectedCourseIds = [];
        $('.course-checkbox:checked').each(function() {
          selectedCourseIds.push($(this).val());
        });
        loadPrerequisites(currentStudentId, selectedCourseIds);
      });
    }
  }

  // Prerequisites Search
  $('#prerequisitesSearch').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    filterPrerequisites(searchTerm);
  });

  function filterPrerequisites(searchTerm) {
    if (!originalPrerequisitesData.length) return;
    
    const filteredData = originalPrerequisitesData.filter(prereq => {
      const courseName = (prereq.course_name || '').toLowerCase();
      const courseCode = (prereq.course_code || '').toLowerCase();
      const requiredFor = (prereq.required_for || '').toLowerCase();
      
      return courseName.includes(searchTerm) || 
             courseCode.includes(searchTerm) || 
             requiredFor.includes(searchTerm);
    });

    displayFilteredPrerequisites(filteredData);
  }

  function displayFilteredPrerequisites(prerequisites) {
    if (prerequisites.length === 0) {
      $('#prerequisitesBox').html(`
        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
          <div class="text-center text-muted">
            <i class="bx bx-search-alt display-4 mb-3"></i>
            <p>No prerequisites found matching your search</p>
          </div>
        </div>
      `);
    } else {
      let html = '';
      prerequisites.forEach(function(prereq) {
        const statusClass = prereq.is_enrolled ? 'success' : 'danger';
        const statusText = prereq.is_enrolled ? 'Enrolled' : 'Not Enrolled';
        const statusIcon = prereq.is_enrolled ? 'bx-check-circle' : 'bx-x-circle';
        
        html += `
          <div class="prerequisite-item">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h6 class="mb-1">${prereq.course_name || 'Course Name N/A'}</h6>
                <p class="text-muted mb-0 small">
                  <i class="bx bx-book me-1"></i>
                  Course Code: ${prereq.course_code || 'N/A'}
                  <span class="ms-2"><i class="bx bx-timer me-1"></i>Credit Hours: <strong>${prereq.credit_hours || 'N/A'}</strong></span>
                </p>
                <p class="text-muted mb-0 small">
                  <i class="bx bx-link me-1"></i>
                  Required for: <strong>${prereq.required_for || 'N/A'}</strong>
                </p>
              </div>
              <span class="badge bg-label-${statusClass}">
                <i class="bx ${statusIcon} me-1"></i>${statusText}
              </span>
            </div>
          </div>
        `;
      });
      $('#prerequisitesBox').html(html);
    }
  }

  // Update the existing functions to store original data
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
      originalHistoryData = [];
      return;
    }
    showLoading('#enrollmentHistoryBox', 'Loading enrollment history...');
    $.ajax({
      url: '{{ route('enrollments.studentEnrollments') }}',
      method: 'POST',
      data: { student_id: studentId, _token: '{{ csrf_token() }}' },
      success: function(res) {
        let history = (res.data || []);
        originalHistoryData = history; // Store original data
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
          displayFilteredHistory(history);
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
        originalHistoryData = [];
      }
    });
  }

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
      $('#creditHoursSummary').hide();
      $('#exceptionAlert').hide();
      $('#prerequisitesBox').html(`
        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
          <div class="text-center text-muted">
            <i class="bx bx-link-alt display-4 mb-3"></i>
            <p>Select courses to view prerequisites</p>
          </div>
        </div>
      `);
      $('#prerequisitesCount').text('0');
      originalCoursesData = [];
      return;
    }
    showLoading('#coursesBox', 'Loading available courses...');
    $.ajax({
      url: '{{ route('available_courses.all') }}',
      method: 'GET',
      data: { student_id: studentId, term_id: termId },
      success: function(res) {
        let courses = (res.courses || []);
        originalCoursesData = courses; // Store original data
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
          $('#creditHoursSummary').hide();
          $('#exceptionAlert').hide();
          $('#prerequisitesBox').html(`
            <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
              <div class="text-center text-muted">
                <i class="bx bx-link-alt display-4 mb-3"></i>
                <p>Select courses to view prerequisites</p>
              </div>
            </div>
          `);
          $('#prerequisitesCount').text('0');
        } else {
          displayFilteredCourses(courses);
          $('#enrollBtn').show();
          updateEnrollButton();
          updateCreditHoursSummary();
          
          // Load remaining credit hours for the student in this term
          loadRemainingCreditHours(studentId, termId);
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
        $('#creditHoursSummary').hide();
        $('#exceptionAlert').hide();
        $('#prerequisitesBox').html(`
          <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
            <div class="text-center text-muted">
              <i class="bx bx-link-alt display-4 mb-3"></i>
              <p>Select courses to view prerequisites</p>
            </div>
          </div>
        `);
        $('#prerequisitesCount').text('0');
        originalCoursesData = [];
      }
    });
  }

  function loadPrerequisites(studentId, selectedCourseIds) {
    if (!studentId || !selectedCourseIds || selectedCourseIds.length === 0) {
      $('#prerequisitesBox').html(`
        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
          <div class="text-center text-muted">
            <i class="bx bx-link-alt display-4 mb-3"></i>
            <p>Select courses to view prerequisites</p>
          </div>
        </div>
      `);
      $('#prerequisitesCount').text('0');
      originalPrerequisitesData = [];
      return;
    }
    showLoading('#prerequisitesBox', 'Loading prerequisites...');
    $.ajax({
      url: '{{ route('courses.prerequisites') }}',
      method: 'POST',
      data: { 
        student_id: studentId, 
        course_ids: selectedCourseIds, 
        _token: '{{ csrf_token() }}' 
      },
      success: function(reponse) {
        let prerequisites = (reponse.data || []);
        originalPrerequisitesData = prerequisites; // Store original data
        $('#prerequisitesCount').text(prerequisites.length);
        if (prerequisites.length === 0) {
          $('#prerequisitesBox').html(`
            <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
              <div class="text-center text-muted">
                <i class="bx bx-info-circle display-4 mb-3"></i>
                <p>No prerequisites found for selected courses</p>
              </div>
            </div>
          `);
        } else {
          displayFilteredPrerequisites(prerequisites);
        }
      },
      error: function() {
        $('#prerequisitesBox').html(`
          <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
            <div class="text-center text-muted">
              <i class="bx bx-error-circle display-4 mb-3"></i>
              <p>Could not load prerequisites</p>
            </div>
          </div>
        `);
        $('#prerequisitesCount').text('0');
        originalPrerequisitesData = [];
      }
    });
  }
});
</script>
@endpush