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
      <div class="col-lg-8 mb-4">
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
                <button type="submit" class="btn btn-secondary w-100" id="enrollBtn" style="display: none;">
                  <i class="bx bx-plus me-1"></i>
                  Enroll Selected Courses
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>

    <!-- Weekly Schedule Visualization -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="card" id="weeklyScheduleCard" style="display:none;">
          <div class="card-header">
            <div class="d-flex align-items-center">
              <i class="bx bx-calendar-week me-2"></i>
              <h5 class="mb-0">Weekly Schedule Preview</h5>
            </div>
          </div>
          <div class="card-body">
            <div id="weeklySchedule" class="schedule-grid">
              <!-- Schedule will be populated here -->
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Credit Hours Summary -->
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
                  <i class="bx bx-plus-circle text-secondary mb-1"></i>
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
                <div class="progress-bar bg-secondary" id="usageProgressBar" role="progressbar" style="width: 0%"></div>
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

<!-- Group Selection Modal -->
<div class="modal fade" id="groupSelectionModal" tabindex="-1" aria-labelledby="groupSelectionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="groupSelectionModalLabel">
          <i class="bx bx-group me-2"></i>
          Select Course Group
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="courseGroupInfo" class="mb-3">
          <!-- Course info will be populated here -->
        </div>
        <div id="groupsList">
          <!-- Groups will be populated here -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="confirmGroupSelection">
          <i class="bx bx-check me-1"></i>
          Confirm Selection
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Prerequisites Modal -->
<div class="modal fade" id="prerequisitesModal" tabindex="-1" aria-labelledby="prerequisitesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="prerequisitesModalLabel">
          <i class="bx bx-link me-2"></i>
          Missing Prerequisites
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning">
          <i class="bx bx-error-circle me-2"></i>
          The following prerequisites are required but not completed:
        </div>
        <div id="missingPrerequisitesList">
          <!-- Missing prerequisites will be populated here -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
  max-height: 500px;
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
  position: relative;
}

.course-item:hover {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border-color: var(--bs-primary);
}

.course-item:last-child {
  margin-bottom: 0;
}

.course-item.selected {
  border-color: var(--bs-secondary);
  background-color: rgba(25, 135, 84, 0.05);
}

.prerequisites-status {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid var(--bs-border-color);
}

.prerequisite-check {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  background-color: rgba(var(--bs-light-rgb), 0.5);
}

.prerequisite-check:last-child {
  margin-bottom: 0;
}

.prerequisite-check.fulfilled {
  background-color: rgba(25, 135, 84, 0.1);
  color: var(--bs-secondary);
}

.prerequisite-check.missing {
  background-color: rgba(220, 53, 69, 0.1);
  color: var(--bs-danger);
  cursor: pointer;
}

.prerequisite-check.missing:hover {
  background-color: rgba(220, 53, 69, 0.2);
}

.selected-group-info {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background-color: rgba(13, 110, 253, 0.1);
  border-radius: 0.25rem;
  border-left: 3px solid var(--bs-primary);
}

.group-selection-item {
  border: 1px solid var(--bs-border-color);
  border-radius: 0.375rem;
  padding: 1rem;
  margin-bottom: 0.75rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.group-selection-item:hover {
  border-color: var(--bs-primary);
  background-color: rgba(13, 110, 253, 0.05);
}

.group-selection-item.selected {
  border-color: var(--bs-secondary);
  background-color: rgba(25, 135, 84, 0.1);
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

/* Weekly Schedule Styles */
.schedule-grid {
  display: grid;
  grid-template-columns: 100px repeat(6, 1fr);
  gap: 1px;
  background-color: var(--bs-border-color);
  border-radius: 0.375rem;
  overflow: hidden;
}

.schedule-header,
.schedule-cell {
  background-color: white;
  padding: 0.75rem 0.5rem;
  text-align: center;
  min-height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
}

.schedule-header {
  background-color: var(--bs-light);
  font-weight: 600;
  color: var(--bs-dark);
}

.schedule-cell.time-slot {
  background-color: var(--bs-light);
  font-weight: 500;
  color: var(--bs-secondary);
}

.schedule-cell.has-class {
  background-color: rgba(13, 110, 253, 0.1);
  border-left: 3px solid var(--bs-primary);
  cursor: pointer;
}

.schedule-cell.has-class:hover {
  background-color: rgba(13, 110, 253, 0.2);
}

.class-info {
  text-align: left;
  width: 100%;
}

.class-title {
  font-weight: 600;
  color: var(--bs-primary);
  margin-bottom: 0.25rem;
  font-size: 0.8rem;
}

.class-details {
  color: var(--bs-secondary);
  font-size: 0.7rem;
  line-height: 1.2;
}

@media (max-width: 768px) {
  .schedule-grid {
    font-size: 0.75rem;
  }
  
  .schedule-header,
  .schedule-cell {
    padding: 0.5rem 0.25rem;
    min-height: 50px;
  }
}
</style>
@endsection

@push('scripts')
<script src="{{ asset('js/utils.js') }}?v={{ config('app.version') }}"></script>

<script>
// Global variables
let currentStudentId = null;
let currentTermId = null;
let originalHistoryData = [];
let originalCoursesData = [];
let selectedCourseGroups = new Map(); // Store selected groups for each course

/**
 * Shows a loading spinner in the given container.
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
    originalHistoryData = [];
    return;
  }
  showLoading('#enrollmentHistoryBox', 'Loading enrollment history...');
  $.ajax({
    url: '{{ route('enrollments.studentEnrollments') }}',
    method: 'POST',
    data: { student_id: studentId, _token: '{{ csrf_token() }}' },
    secondary: function(res) {
      let history = (res.data || []);
      originalHistoryData = history;
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

/**
 * Loads available courses for a student and term, and updates the DOM.
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
    $('#weeklyScheduleCard').hide();
    originalCoursesData = [];
    selectedCourseGroups.clear();
    return;
  }
  showLoading('#coursesBox', 'Loading available courses...');
  $.ajax({
    url: '{{ route('available_courses.all') }}',
    method: 'GET',
    data: { student_id: studentId, term_id: termId },
    secondary: function(res) {
      let courses = (res.courses || []);
      originalCoursesData = courses;
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
        $('#weeklyScheduleCard').hide();
      } else {
        // Load prerequisites for all courses first
        loadCoursesWithPrerequisites(courses);
        $('#enrollBtn').show();
        updateEnrollButton();
        updateCreditHoursSummary();
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
      $('#weeklyScheduleCard').hide();
      originalCoursesData = [];
    }
  });
}

/**
 * Loads courses with their prerequisites status
 */
function loadCoursesWithPrerequisites(courses) {
  const courseIds = courses.map(course => course.available_course_id);
  
  $.ajax({
    url: '{{ route('courses.prerequisites') }}',
    method: 'POST',
    data: { 
      student_id: currentStudentId, 
      course_ids: courseIds,
      _token: '{{ csrf_token() }}' 
    },
    secondary: function(res) {
      const prerequisites = res.data || [];
      displayCoursesWithPrerequisites(courses, prerequisites);
    },
    error: function() {
      displayCoursesWithPrerequisites(courses, []);
    }
  });
}

/**
 * Displays courses with prerequisites validation
 */
function displayCoursesWithPrerequisites(courses, prerequisites) {
  let html = '';
  
  courses.forEach(function(course) {
    const capacityClass = course.remaining_capacity > 10 ? 'secondary' : 
                        course.remaining_capacity > 5 ? 'warning' : 'danger';
    
    // Get prerequisites for this course
    const coursePrereqs = prerequisites.filter(p => 
      p.required_for_course_id == course.available_course_id
    );
    
    const hasUnfulfilledPrereqs = coursePrereqs.some(p => !p.is_enrolled);
    const canEnroll = !hasUnfulfilledPrereqs;
    
    html += `
      <div class="course-item" data-course-id="${course.available_course_id}">
        <div class="form-check">
          <input class="form-check-input course-checkbox" type="checkbox" 
                 name="available_course_ids[]" value="${course.available_course_id}" 
                 data-credit-hours="${course.credit_hours}" 
                 id="course_${course.available_course_id}"
                 ${!canEnroll ? 'disabled' : ''}>
          <label class="form-check-label w-100" for="course_${course.available_course_id}">
            <div class="d-flex justify-content-between align-items-start">
              <div style="flex: 1;">
                <h6 class="mb-1">${course.name}</h6>
                <p class="text-muted mb-0 small">
                  <i class="bx bx-book me-1"></i>
                  Course Code: ${course.code || 'N/A'}
                  <span class="ms-2"><i class="bx bx-timer me-1"></i>Credit Hours: <strong>${course.credit_hours}</strong></span>
                </p>
                
                <!-- Prerequisites Status -->
                ${coursePrereqs.length > 0 ? `
                <div class="prerequisites-status">
                  <small class="text-muted fw-semibold mb-2 d-block">
                    <i class="bx bx-link me-1"></i>Prerequisites:
                  </small>
                  ${coursePrereqs.map(prereq => `
                    <div class="prerequisite-check ${prereq.is_enrolled ? 'fulfilled' : 'missing'}" 
                         ${!prereq.is_enrolled ? `onclick="showMissingPrerequisites([${JSON.stringify(prereq).replace(/"/g, '&quot;')}])"` : ''}>
                      <i class="bx ${prereq.is_enrolled ? 'bx-check-circle' : 'bx-x-circle'} me-2"></i>
                      <span class="small">${prereq.course_name} (${prereq.course_code})</span>
                    </div>
                  `).join('')}
                </div>
                ` : '<div class="mt-2"><small class="text-secondary"><i class="bx bx-check me-1"></i>No prerequisites required</small></div>'}
                
                <!-- Selected Group Info -->
                <div class="selected-group-info" id="groupInfo_${course.available_course_id}" style="display:none;">
                  <small class="text-primary fw-semibold">
                    <i class="bx bx-group me-1"></i>Selected Group: <span class="group-name"></span>
                  </small>
                  <div class="group-details mt-1"></div>
                </div>
              </div>
              <div class="text-end">
                <span class="badge bg-label-${capacityClass} capacity-badge mb-2">
                  <i class="bx bx-group me-1"></i>
                  ${course.remaining_capacity} spots left
                </span>
                ${!canEnroll ? '<div><span class="badge bg-label-danger"><i class="bx bx-lock me-1"></i>Prerequisites Required</span></div>' : ''}
              </div>
            </div>
          </label>
        </div>
      </div>
    `;
  });
  
  $('#coursesBox').html(html);
  
  // Attach event handlers
  $('.course-checkbox').on('change', function() {
    const courseId = $(this).val();
    const isChecked = $(this).is(':checked');
    
    if (isChecked) {
      // Show group selection modal
      showGroupSelectionModal(courseId);
    } else {
      // Remove from selected groups
      selectedCourseGroups.delete(courseId);
      $(`#groupInfo_${courseId}`).hide();
      $(this).closest('.course-item').removeClass('selected');
      updateEnrollButton();
      updateCreditHoursSummary();
      updateWeeklySchedule();
    }
  });
}

/**
 * Shows the group selection modal for a course
 */
function showGroupSelectionModal(courseId) {
  const course = originalCoursesData.find(c => c.available_course_id == courseId);
  if (!course) return;
  
  // Update modal title and course info
  $('#groupSelectionModalLabel').html(`
    <i class="bx bx-group me-2"></i>
    Select Group for ${course.name}
  `);
  
  $('#courseGroupInfo').html(`
    <div class="alert alert-info">
      <h6 class="mb-1">${course.name}</h6>
      <p class="mb-0 small">
        <i class="bx bx-book me-1"></i>Course Code: ${course.code || 'N/A'} | 
        <i class="bx bx-timer me-1"></i>Credit Hours: ${course.credit_hours}
      </p>
    </div>
  `);
  
  // Load groups for this course
  loadCourseGroups(courseId);
  
  // Store current course ID for modal
  $('#groupSelectionModal').data('course-id', courseId);
  
  // Show modal
  $('#groupSelectionModal').modal('show');
}

/**
 * Loads groups for a specific course
 */
function loadCourseGroups(courseId) {
  showLoading('#groupsList', 'Loading course groups...');
  
  $.ajax({
    url: '{{ route('available_courses.schedules', ':id') }}'.replace(':id', courseId),
    method: 'GET',
    secondary: function(res) {
      const schedules = res.data || [];
      displayCourseGroups(schedules);
    },
    error: function() {
      $('#groupsList').html(`
        <div class="alert alert-danger">
          <i class="bx bx-error-circle me-2"></i>
          Failed to load course groups. Please try again.
        </div>
      `);
    }
  });
}

/**
 * Displays course groups for selection
 */
function displayCourseGroups(schedules) {
  if (schedules.length === 0) {
    $('#groupsList').html(`
      <div class="alert alert-warning">
        <i class="bx bx-info-circle me-2"></i>
        No groups available for this course.
      </div>
    `);
    return;
  }
  
  let html = '';
  schedules.forEach(function(schedule) {
    html += `
      <div class="group-selection-item" data-group-id="${schedule.id}">
        <div class="form-check">
          <input class="form-check-input group-radio" type="radio" 
                 name="selected_group" value="${schedule.id}" 
                 id="group_${schedule.id}">
          <label class="form-check-label w-100" for="group_${schedule.id}">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <h6 class="mb-1">Group ${schedule.group} - ${schedule.activity_type}</h6>
                <p class="text-muted mb-1 small">
                  <i class="bx bx-time me-1"></i>
                  ${schedule.start_time} - ${schedule.end_time}
                </p>
                <p class="text-muted mb-0 small">
                  <i class="bx bx-calendar me-1"></i>
                  ${schedule.day_of_week || 'Schedule TBA'}
                </p>
              </div>
              <div class="text-end">
                <span class="badge bg-label-info">
                  <i class="bx bx-users me-1"></i>
                  ${schedule.enrolled_count || 0}/${schedule.max_capacity}
                </span>
              </div>
            </div>
          </label>
        </div>
      </div>
    `;
  });
  
  $('#groupsList').html(html);
  
  // Handle group selection
  $('.group-selection-item').on('click', function() {
    $('.group-selection-item').removeClass('selected');
    $(this).addClass('selected');
    $(this).find('.group-radio').prop('checked', true);
  });
}

/**
 * Shows missing prerequisites modal
 */
function showMissingPrerequisites(missingPrereqs) {
  let html = '';
  missingPrereqs.forEach(function(prereq) {
    html += `
      <div class="alert alert-danger">
        <div class="d-flex align-items-center">
          <i class="bx bx-x-circle me-3 text-danger" style="font-size: 1.5rem;"></i>
          <div>
            <h6 class="mb-1">${prereq.course_name}</h6>
            <p class="mb-0 small">
              <i class="bx bx-book me-1"></i>Course Code: ${prereq.course_code || 'N/A'} | 
              <i class="bx bx-timer me-1"></i>Credit Hours: ${prereq.credit_hours || 'N/A'}
            </p>
          </div>
        </div>
      </div>
    `;
  });
  
  $('#missingPrerequisitesList').html(html);
  $('#prerequisitesModal').modal('show');
}

/**
 * Updates the weekly schedule visualization
 */
function updateWeeklySchedule() {
  const selectedCourses = [];
  $('.course-checkbox:checked').each(function() {
    const courseId = $(this).val();
    const groupData = selectedCourseGroups.get(courseId);
    if (groupData) {
      selectedCourses.push(groupData);
    }
  });
  
  if (selectedCourses.length === 0) {
    $('#weeklyScheduleCard').hide();
    return;
  }
  
  // Show the schedule card
  $('#weeklyScheduleCard').show();
  
  // Generate schedule grid
  generateScheduleGrid(selectedCourses);
}

/**
 * Generates the weekly schedule grid
 */
function generateScheduleGrid(selectedCourses) {
  const days = ['Time', 'Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
  const timeSlots = [
    '08:00', '09:00', '10:00', '11:00', '12:00', 
    '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'
  ];
  
  let html = '';
  
  // Create headers
  days.forEach(day => {
    html += `<div class="schedule-header">${day}</div>`;
  });
  
  // Create time slots
  timeSlots.forEach(timeSlot => {
    // Time column
    html += `<div class="schedule-cell time-slot">${timeSlot}</div>`;
    
    // Day columns
    for (let dayIndex = 1; dayIndex < days.length; dayIndex++) {
      const dayName = days[dayIndex].toLowerCase();
      // Check if any selected course has a class at this time and day
      const classAtThisTime = selectedCourses.find(course => {
        return course.schedule &&
               course.schedule.day_of_week &&
               course.schedule.day_of_week.toLowerCase().includes(dayName) &&
               isTimeInRange(timeSlot, course.schedule.start_time, course.schedule.end_time);
      });
      if (classAtThisTime) {
        html += `
          <div class="schedule-cell has-class" title="${classAtThisTime.course.name} - Group ${classAtThisTime.schedule.group}">
            <div class="class-info">
              <div class="class-title">${classAtThisTime.course.name}</div>
              <div class="class-details">
                Group ${classAtThisTime.schedule.group}<br>
                ${classAtThisTime.schedule.activity_type}<br>
                ${classAtThisTime.schedule.start_time}-${classAtThisTime.schedule.end_time}
              </div>
            </div>
          </div>
        `;
      } else {
        html += `<div class="schedule-cell"></div>`;
      }
    }
  });
  
  $('#weeklySchedule').html(html);
}

/**
 * Checks if a time slot falls within a class time range
 */
function isTimeInRange(timeSlot, startTime, endTime) {
  const slotTime = parseTime(timeSlot);
  const start = parseTime(startTime);
  const end = parseTime(endTime);
  
  return slotTime >= start && slotTime < end;
}

/**
 * Parses time string to minutes for comparison
 */
function parseTime(timeStr) {
  const [hours, minutes] = timeStr.split(':').map(Number);
  return hours * 60 + (minutes || 0);
}

/**
 * Updates the enroll button state based on selected courses.
 */
function updateEnrollButton() {
  const selectedCount = $('.course-checkbox:checked').length;
  const enrollBtn = $('#enrollBtn');
  if (selectedCount > 0) {
    enrollBtn.html(`
      <i class="bx bx-plus me-1"></i>Enroll Selected Courses (${selectedCount})
    `).prop('disabled', false);
  } else {
    enrollBtn.html('<i class="bx bx-plus me-1"></i>Enroll Selected Courses').prop('disabled', true);
  }
}

/**
 * Loads remaining credit hours for the student in the selected term
 */
function loadRemainingCreditHours(studentId, termId) {
  if (!studentId || !termId) {
    return;
  }
  
  $.ajax({
    url: '{{ route('enrollments.remainingCreditHours') }}',
    method: 'POST',
    data: { 
      student_id: studentId, 
      term_id: termId, 
      _token: '{{ csrf_token() }}' 
    },
    secondary: function(res) {
      if (res.secondary && res.data) {
        const data = res.data;
        
        $('#currentEnrollmentHours').text(data.current_enrollment_hours);
        $('#selectedCH').text('0');
        $('#maxCH').text(data.max_allowed_hours);
        $('#remainingCH').text(data.remaining_hours);
        
        updateCreditHoursProgress(data.current_enrollment_hours, data.max_allowed_hours);
        
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
        
        $('#creditHoursSummary').show();
        updateChInfoBoxWithStudentData(data.student_cgpa, data.term_season);
      }
    },
    error: function(xhr) {
      console.error('Failed to load remaining credit hours:', xhr);
      updateCreditHoursSummary();
    }
  });
}

/**
 * Updates the credit hours info box with student's actual data.
 */
function updateChInfoBoxWithStudentData(cgpa, season) {
  if (!season) return;
  
  const seasonCapitalized = season.charAt(0).toUpperCase() + season.slice(1);
  $('#chInfoBox').show();
  $('.ch-row').hide();
  
  if (season.toLowerCase() === 'summer') {
    $(`.ch-row[data-season='${seasonCapitalized}']`).show();
  } else {
    let cgpaRange = '';
    if (cgpa < 2.0) {
      cgpaRange = 'lt2';
    } else if (cgpa >= 2.0 && cgpa < 3.0) {
      cgpaRange = '2to3';
    } else if (cgpa >= 3.0) {
      cgpaRange = 'gte3';
    }
    
    $(`.ch-row[data-season='${seasonCapitalized}'][data-cgpa='${cgpaRange}']`).addClass('table-primary');
    $(`.ch-row[data-season='${seasonCapitalized}']`).show();
  }
}

function updateCreditHoursSummary() {
  let selectedTotal = 0;
  $('.course-checkbox:checked').each(function() {
    selectedTotal += parseInt($(this).data('credit-hours')) || 0;
  });
  
  let currentEnrollmentHours = parseInt($('#currentEnrollmentHours').text()) || 0;
  let newTotal = currentEnrollmentHours + selectedTotal;
  let maxCH = parseInt($('#maxCH').text()) || 0;
  let remaining = maxCH - newTotal;
  
  $('#selectedCH').text(newTotal);
  $('#remainingCH').text(Math.max(0, remaining));
  
  if ($('.course-checkbox').length > 0) {
    $('#creditHoursSummary').show();
  } else {
    $('#creditHoursSummary').hide();
  }
  
  updateCreditHoursProgress(newTotal, maxCH);
}

/**
 * Updates the credit hours progress bar.
 */
function updateCreditHoursProgress(current, max) {
  if (max <= 0) return;
  
  const percentage = Math.min((current / max) * 100, 100);
  const progressBar = $('#usageProgressBar');
  const percentageText = $('#usagePercentage');
  
  progressBar.css('width', percentage + '%');
  percentageText.text(Math.round(percentage) + '%');
  
  progressBar.removeClass('bg-secondary bg-warning bg-danger');
  if (percentage < 70) {
    progressBar.addClass('bg-secondary');
  } else if (percentage < 90) {
    progressBar.addClass('bg-warning');
  } else {
    progressBar.addClass('bg-danger');
  }
}

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

// Search functionality
function filterEnrollmentHistory(searchTerm) {
  if (!originalHistoryData.length) return;
  
  const filteredData = originalHistoryData.filter(enrollment => {
    const courseName = (enrollment.course?.name || '').toLowerCase();
    const courseCode = (enrollment.course?.code || '').toLowerCase();
    const termName = (enrollment.term?.name || '').toLowerCase();
    const grade = enrollment.grade ? enrollment.grade.toString() : '';
    
    return courseName.includes(searchTerm) || 
           courseCode.includes(searchTerm) || 
           termName.includes(searchTerm) || 
           grade.includes(searchTerm);
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
              ${enr.grade ? `<div class="mt-1"><span class="badge bg-label-primary"><i class="bx bx-star me-1"></i>Grade: <strong>${enr.grade}</strong></span></div>` : '<div class="mt-1"><span class="badge bg-label-secondary"><i class="bx bx-time me-1"></i>No Grade Yet</span></div>'}
            </div>
            <span class="badge bg-label-secondary">Enrolled</span>
          </div>
        </div>
      `;
    });
    $('#enrollmentHistoryBox').html(html);
  }
}

function filterAvailableCourses(searchTerm) {
  if (!originalCoursesData.length) return;
  
  const filteredData = originalCoursesData.filter(course => {
    const courseName = (course.name || '').toLowerCase();
    const courseCode = (course.code || '').toLowerCase();
    
    return courseName.includes(searchTerm) || courseCode.includes(searchTerm);
  });

  // Reload prerequisites for filtered courses
  if (filteredData.length > 0) {
    loadCoursesWithPrerequisites(filteredData);
  } else {
    $('#coursesBox').html(`
      <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
        <div class="text-center text-muted">
          <i class="bx bx-search-alt display-4 mb-3"></i>
          <p>No courses found matching your search</p>
        </div>
      </div>
    `);
  }
}

// Main jQuery document ready
$(document).ready(function () {

  $('#findStudentForm').on('submit', function(e) {
    e.preventDefault();
    let identifier = $('#identifier').val();
    
    // Reset everything
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
    $('#weeklyScheduleCard').hide();
    selectedCourseGroups.clear();
    
    $.ajax({
      url: '{{ route('enrollments.findStudent') }}',
      method: 'POST',
      data: { identifier: identifier, _token: '{{ csrf_token() }}' },
      secondary: function(res) {
        let s = res.data;
        $('#student_id').val(s.id);
        
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
        currentTermId = null;
        
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

  // Initialize Select2
  $('#term_id').select2({
    theme: 'bootstrap-5',
    placeholder: 'Please select an academic term',
    allowClear: true,
    width: '100%'
  });

  // Load terms
  function loadTerms() {
    $.ajax({
      url: @json(auth()->user() && auth()->user()->hasRole('admin') ? route('terms.all.with_inactive') : route('terms.all')),
      method: 'GET',
      secondary: function (response) {
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
    selectedCourseGroups.clear();
    $('#weeklyScheduleCard').hide();
    loadAvailableCourses(currentStudentId, currentTermId);
    
    let selectedText = $('#term_id option:selected').text();
    updateChInfoBox(selectedText);
  });

  // Group selection modal handlers
  $('#confirmGroupSelection').on('click', function() {
    const courseId = $('#groupSelectionModal').data('course-id');
    const selectedGroupId = $('input[name="selected_group"]:checked').val();
    
    if (!selectedGroupId) {
      Swal.fire({
        icon: 'warning',
        title: 'No Group Selected',
        text: 'Please select a group for this course.',
        confirmButtonText: 'OK'
      });
      return;
    }
    
    // Get selected group data robustly
    const selectedGroup = $('.group-selection-item.selected');
    const h6Text = selectedGroup.find('h6').text().trim();
    const [groupLabel, activityType] = h6Text.split(' - ');
    const groupNum = groupLabel.replace('Group ', '').trim();
    const timeText = selectedGroup.find('.text-muted').eq(0).text().trim();
    const [startTime, endTime] = timeText.split(' - ').map(s => s.trim());
    const dayOfWeek = selectedGroup.find('.text-muted').eq(1).text().replace(/.*\s/, '').trim();

    const groupData = {
      course_id: courseId,
      group_id: selectedGroupId,
      course: originalCoursesData.find(c => c.available_course_id == courseId),
      schedule: {
        id: selectedGroupId,
        group: groupNum,
        activity_type: activityType,
        start_time: startTime,
        end_time: endTime,
        day_of_week: dayOfWeek
      }
    };
    
    // Store the selection
    selectedCourseGroups.set(courseId, groupData);
    
    // Update course item to show selected group
    const groupInfo = $(`#groupInfo_${courseId}`);
    groupInfo.find('.group-name').text(`Group ${groupData.schedule.group}`);
    groupInfo.find('.group-details').html(`
      <small class="text-muted">
        <i class="bx bx-time me-1"></i>${groupData.schedule.start_time} - ${groupData.schedule.end_time} | 
        <i class="bx bx-calendar me-1"></i>${groupData.schedule.day_of_week}
      </small>
    `);
    groupInfo.show();
    
    // Mark course as selected
    $(`.course-item[data-course-id="${courseId}"]`).addClass('selected');
    
    // Close modal
    $('#groupSelectionModal').modal('hide');
    
    // Update schedule and summary
    updateEnrollButton();
    updateCreditHoursSummary();
    updateWeeklySchedule();
  });

  // Handle modal close without selection
  $('#groupSelectionModal').on('hidden.bs.modal', function() {
    const courseId = $(this).data('course-id');
    const checkbox = $(`#course_${courseId}`);
    
    // If no group was selected, uncheck the course
    if (!selectedCourseGroups.has(courseId)) {
      checkbox.prop('checked', false);
      updateEnrollButton();
      updateCreditHoursSummary();
      updateWeeklySchedule();
    }
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
    
    // Check if all selected courses have groups selected
    let missingGroups = [];
    $('.course-checkbox:checked').each(function() {
      const courseId = $(this).val();
      if (!selectedCourseGroups.has(courseId)) {
        const courseName = originalCoursesData.find(c => c.available_course_id == courseId)?.name || 'Unknown Course';
        missingGroups.push(courseName);
      }
    });
    
    if (missingGroups.length > 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Groups Not Selected',
        text: `Please select groups for: ${missingGroups.join(', ')}`,
        confirmButtonText: 'OK'
      });
      return;
    }
    
    let formData = new FormData();
    formData.append('student_id', $('#student_id').val());
    formData.append('term_id', $('#term_id').val());
    formData.append('_token', '{{ csrf_token() }}');

    // Add selected courses and their groups
    $('.course-checkbox:checked').each(function() {
      const courseId = $(this).val();
      const groupData = selectedCourseGroups.get(courseId);
      if (groupData) {
        formData.append('available_course_ids[]', courseId);
        formData.append(`schedule_ids[${courseId}]`, groupData.group_id);
      }
    });

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
      secondary: function(res) {
        // Show loading modal and send AJAX for pdf
        Swal.fire({
          title: 'Generating PDF Document...',
          html: '<div class="text-center">Please wait while your PDF document is being generated.</div>',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => {
            Swal.showLoading();
            let url = `{{ route('students.download.pdf', ':id') }}?term_id=${currentTermId}`.replace(':id', currentStudentId);
            $.ajax({
              url: url,
              method: 'GET',
              secondary: function(response) {
                let fileUrl = response.url || (response.data && response.data.url);
                if (fileUrl) {
                  window.open(fileUrl, '_blank');
                  Swal.fire({
                    icon: 'secondary',
                    title: 'PDF Ready',
                    html: `<div>Your PDF document is ready for download.</div>`,
                    showConfirmButton: false,
                    timer: 5000
                  });
                } else {
                  Swal.fire('Error', 'No file URL returned from server.', 'error');
                }
              },
              error: function() {
                Swal.fire('Error', 'Failed to generate PDF document.', 'error');
              }
            });
          }
        });
        
        // Reset form and reload data
        $('.course-checkbox').prop('checked', false);
        selectedCourseGroups.clear();
        $('.course-item').removeClass('selected');
        $('.selected-group-info').hide();
        $('#weeklyScheduleCard').hide();
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
        enrollBtn.html(originalText).prop('disabled', false);
        updateEnrollButton();
      }
    });
  });

  // Search functionality
  $('#historySearch').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    filterEnrollmentHistory(searchTerm);
  });

  $('#coursesSearch').on('input', function() {
    const searchTerm = $(this).val().toLowerCase();
    filterAvailableCourses(searchTerm);
  });

  Utils.hidePageLoader();
});

// Global function to show missing prerequisites (called from onclick)
function showMissingPrerequisites(missingPrereqs) {
  let html = '';
  missingPrereqs.forEach(function(prereq) {
    html += `
      <div class="alert alert-danger">
        <div class="d-flex align-items-center">
          <i class="bx bx-x-circle me-3 text-danger" style="font-size: 1.5rem;"></i>
          <div>
            <h6 class="mb-1">${prereq.course_name}</h6>
            <p class="mb-0 small">
              <i class="bx bx-book me-1"></i>Course Code: ${prereq.course_code || 'N/A'} | 
              <i class="bx bx-timer me-1"></i>Credit Hours: ${prereq.credit_hours || 'N/A'}
            </p>
          </div>
        </div>
      </div>
    `;
  });
  
  $('#missingPrerequisitesList').html(html);
  $('#prerequisitesModal').modal('show');
}
</script>
@endpush