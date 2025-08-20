@extends('layouts.home')

@section('title', 'Student Enrollment | AcadOps')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/enrollment.css') }}">
@endpush

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
  <div class="card mb-4 shadow-sm">
    <div class="card-header bg-light d-flex align-items-center">
      <i class="bx bx-search me-2 text-primary"></i>
      <h5 class="mb-0 text-dark">Find Student</h5>
    </div>
    <div class="card-body">
      <form id="findStudentForm">
        <div class="row align-items-end">
          <div class="col-12 col-md-8 mb-3 mb-md-0">
            <label for="identifier" class="form-label fw-semibold text-dark">National ID or Academic ID</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-primary">
                <i class="bx bx-id-card text-primary"></i>
              </span>
              <input type="text" class="form-control border-primary" id="identifier" name="identifier" required 
                     placeholder="Enter National ID or Academic ID">
            </div>
          </div>
          <div class="col-12 col-md-4">
            <button type="submit" class="btn btn-primary w-100 shadow-sm">
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
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-light d-flex align-items-center">
        <i class="bx bx-user me-2 text-primary"></i>
        <h5 class="mb-0 text-dark">Student Information</h5>
      </div>
      <div class="card-body">
        <div id="studentInfo" class="row">
          <!-- Student details will be populated here -->
        </div>
      </div>
    </div>

    <!-- Term Selection -->
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-light d-flex align-items-center">
        <i class="bx bx-calendar me-2 text-primary"></i>
        <h5 class="mb-0 text-dark">Academic Term</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-12 col-md-8">
            <label for="term_id" class="form-label fw-semibold text-dark">
              <i class="bx bx-calendar-alt me-1"></i> Academic Term <span class="text-danger">*</span>
            </label>
            <small class="form-text text-muted mb-2 d-block">Please select the academic term for enrollment.</small>
            <select class="form-select select2-term border-primary" id="term_id" name="term_id" required aria-label="Academic Term">
              <option value="">Please select an academic term</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Credit Hour Info Box -->
    <div id="chInfoBox" class="alert alert-info shadow-sm mt-3" style="display:none;">
      <strong class="text-dark">Maximum Allowed Credit Hours (CH):</strong>
      <table class="table table-bordered table-sm mb-0 mt-2" style="background:white;">
        <thead class="table-light">
          <tr>
            <th class="text-dark">Season</th>
            <th class="text-dark">CGPA</th>
            <th class="text-dark">Max CH</th>
            <th class="text-dark">For Graduation</th>
          </tr>
        </thead>
        <tbody>
          <tr class="ch-row" data-season="Fall" data-cgpa="lt2">
            <td class="text-dark">Fall</td>
            <td class="text-dark">&lt;2</td>
            <td class="text-dark fw-bold">14</td>
            <td class="text-dark">+3</td>
          </tr>
          <tr class="ch-row" data-season="Fall" data-cgpa="2to3">
            <td class="text-dark">Fall</td>
            <td class="text-dark">&ge;2 and &lt;3</td>
            <td class="text-dark fw-bold">18</td>
            <td class="text-dark">+3</td>
          </tr>
          <tr class="ch-row" data-season="Fall" data-cgpa="gte3">
            <td class="text-dark">Fall</td>
            <td class="text-dark">&ge;3</td>
            <td class="text-dark fw-bold">21</td>
            <td class="text-dark">+3</td>
          </tr>
          <tr class="ch-row" data-season="Spring" data-cgpa="lt2">
            <td class="text-dark">Spring</td>
            <td class="text-dark">&lt;2</td>
            <td class="text-dark fw-bold">14</td>
            <td class="text-dark">+3</td>
          </tr>
          <tr class="ch-row" data-season="Spring" data-cgpa="2to3">
            <td class="text-dark">Spring</td>
            <td class="text-dark">&ge;2 and &lt;3</td>
            <td class="text-dark fw-bold">18</td>
            <td class="text-dark">+3</td>
          </tr>
          <tr class="ch-row" data-season="Spring" data-cgpa="gte3">
            <td class="text-dark">Spring</td>
            <td class="text-dark">&ge;3</td>
            <td class="text-dark fw-bold">21</td>
            <td class="text-dark">+3</td>
          </tr>
          <tr class="ch-row" data-season="Summer" data-cgpa="any">
            <td class="text-dark">Summer</td>
            <td class="text-dark">Any</td>
            <td class="text-dark fw-bold">9</td>
            <td class="text-dark">+3</td>
          </tr>
        </tbody>
      </table>
      <div class="mt-2 small text-dark">
        <strong>Note:</strong> To exceed the maximum credit hours for graduation, an administrator must grant permission.
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="row">
      
      <!-- Enrollment History -->
      <div class="col-12 col-lg-4 mb-4">
        <div class="card h-100 shadow-sm">
          <div class="card-header bg-light">
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between mb-2">
              <div class="d-flex align-items-center mb-2 mb-sm-0">
                <i class="bx bx-history me-2 text-primary"></i>
                <h5 class="mb-0 text-dark">Enrollment History</h5>
                <span class="badge bg-primary text-white ms-2" id="historyCount">0</span>
              </div>
            </div>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light border-primary">
                <i class="bx bx-search text-primary"></i>
              </span>
              <input type="text" class="form-control border-primary" id="historySearch" placeholder="Search courses, terms, or grades...">
            </div>
          </div>
          <div class="card-body">
            <div id="enrollmentHistoryBox" class="enrollment-history-container">
              <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                <div class="text-center text-muted">
                  <i class="bx bx-search-alt-2 display-4 mb-3 text-primary"></i>
                  <p class="text-dark">Find a student to view enrollment history</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Available Courses -->
      <div class="col-12 col-lg-8 mb-4">
        <div class="card h-100 shadow-sm">
          <div class="card-header bg-light">
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between mb-2">
              <div class="d-flex align-items-center mb-2 mb-sm-0">
                <i class="bx bx-book me-2 text-primary"></i>
                <h5 class="mb-0 text-dark">Available Courses</h5>
                <span class="badge bg-primary text-white ms-2" id="coursesCount">0</span>
              </div>
            </div>
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-light border-primary">
                <i class="bx bx-search text-primary"></i>
              </span>
              <input type="text" class="form-control border-primary" id="coursesSearch" placeholder="Search course names or codes...">
            </div>
          </div>
          <div class="card-body">
            <form id="enrollForm">
              <input type="hidden" id="student_id" name="student_id">
              <div id="coursesBox" class="courses-container">
                <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                  <div class="text-center text-muted">
                    <i class="bx bx-book-bookmark display-4 mb-3 text-primary"></i>
                    <p class="text-dark">Select a term to view available courses</p>
                  </div>
                </div>
              </div>
              <div class="mt-3">
                <button type="submit" class="btn btn-success w-100 shadow-sm" id="enrollBtn" style="display: none;">
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
        <div class="card shadow-sm" id="weeklyScheduleCard" style="display:none;">
          <div class="card-header bg-light">
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between">
              <div class="d-flex align-items-center mb-2 mb-sm-0">
                <i class="bx bx-calendar-week me-2 text-primary"></i>
                <h5 class="mb-0 text-dark">Weekly Schedule Preview</h5>
              </div>
            </div>
          </div>
          <div class="card-body">
            <!-- Conflict Warning Alert -->
            <div id="scheduleConflictAlert" class="alert alert-warning" style="display:none;">
              <div class="d-flex align-items-center">
                <i class="bx bx-error-circle me-2 text-warning"></i>
                <div>
                  <strong class="text-dark">Schedule Conflicts Detected!</strong>
                  <p class="mb-0 small text-dark">Red highlighted time slots indicate overlapping classes. Please review your course selections.</p>
                </div>
              </div>
            </div>
            
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
          <div class="alert alert-info mb-0 p-3 shadow-sm">
            <div class="row text-center">
              <div class="col-6 col-md-3 mb-3 mb-md-0">
                <div class="credit-hours-item">
                  <i class="bx bx-book-open text-primary mb-1"></i>
                  <div class="credit-hours-label">Current Enrollment</div>
                  <div class="credit-hours-value" id="currentEnrollmentHours">0</div>
                </div>
              </div>
              <div class="col-6 col-md-3 mb-3 mb-md-0">
                <div class="credit-hours-item">
                  <i class="bx bx-plus-circle text-success mb-1"></i>
                  <div class="credit-hours-label">Selected Additional</div>
                  <div class="credit-hours-value" id="selectedCH">0</div>
                </div>
              </div>
              <div class="col-6 col-md-3 mb-3 mb-md-0">
                <div class="credit-hours-item">
                  <i class="bx bx-target-lock text-warning mb-1"></i>
                  <div class="credit-hours-label">Max Allowed</div>
                  <div class="credit-hours-value" id="maxCH">0</div>
                </div>
              </div>
              <div class="col-6 col-md-3">
                <div class="credit-hours-item">
                  <i class="bx bx-time text-info mb-1"></i>
                  <div class="credit-hours-label">Remaining</div>
                  <div class="credit-hours-value" id="remainingCH">0</div>
                </div>
              </div>
            </div>
            <div class="mt-3">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-dark fw-semibold">Credit Hours Usage</small>
                <small class="text-dark fw-semibold" id="usagePercentage">0%</small>
              </div>
              <div class="progress" style="height: 10px;">
                <div class="progress-bar bg-success" id="usageProgressBar" role="progressbar" style="width: 0%"></div>
              </div>
            </div>
          </div>
          <!-- Exception alert container -->
          <div id="exceptionAlert" style="display:none;"></div>
        </div>
      </div>
    </div>

  <!-- Activity Type Selection Modal -->
  <x-ui.modal id="activitySelectionModal" scrollable="true" size="xl" title="Select Activity Schedule">
    <div id="courseActivityInfo" class="mb-3">
      <!-- Course info will be populated here -->
    </div>
    <div class="alert alert-info">
      <i class="bx bx-info-circle me-2"></i>
      <strong>Important:</strong> You can only select one activity from each activity type. Please choose the schedule that works best for you.
    </div>
    <div id="activitiesList">
      <!-- Activity types will be populated here -->
    </div>
    <x-slot name="footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-primary" id="confirmActivitySelection">
        <i class="bx bx-check me-1"></i>
        Confirm Selection
      </button>
    </x-slot>
  </x-ui.modal>

  <!-- Prerequisites Modal -->
  <x-ui.modal id="prerequisitesModal" scrollable="true" size="lg" title="Missing Prerequisites">
    <div class="alert alert-warning">
      <i class="bx bx-error-circle me-2"></i>
      <span class="text-dark">The following prerequisites are required but not completed:</span>
    </div>
    <div id="missingPrerequisitesList">
      <!-- Missing prerequisites will be populated here -->
    </div>
    <x-slot name="footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </x-slot>
  </x-ui.modal>



  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/utils.js') }}?v={{ config('app.version') }}"></script>

<script>
// ========================================
// GLOBAL STATE MANAGER
// ========================================
const EnrollmentState = {
  currentStudentId: null,
  currentTermId: null,
  originalHistoryData: [],
  originalCoursesData: [],
  selectedCourses: new Set(),
  selectedCourseGroups: new Map(),
  selectedActivities: [],

  reset() {
    this.currentStudentId = null;
    this.currentTermId = null;
    this.originalHistoryData = [];
    this.originalCoursesData = [];
    this.selectedCourses.clear();
    this.selectedCourseGroups.clear();
    this.selectedActivities = [];
  },

  resetCourseSelections() {
    this.selectedCourses.clear();
    this.selectedCourseGroups.clear();
    this.selectedActivities = this.selectedActivities.filter(activity => activity.source !== 'selection');
  }
};



// ========================================
// STUDENT MODULE
// ========================================
const StudentModule = {
  findStudent(identifier) {
    return $.ajax({
      url: window.routes.findStudent,
      method: 'POST',
      data: { identifier: identifier}
    });
  },

  displayStudentInfo(student) {
    const studentInfoHtml = `
      <div class="col-12 col-md-4 mb-3 mb-md-0">
        <div class="student-info-item">
          <small class="text-muted">Full Name (English)</small>
          <h6 class="mb-0 text-dark">${student.name_en}</h6>
        </div>
        <div class="student-info-item">
          <small class="text-muted">Full Name (Arabic)</small>
          <h6 class="mb-0 text-dark">${student.name_ar}</h6>
        </div>
        <div class="student-info-item">
          <small class="text-muted">Academic Email</small>
          <h6 class="mb-0 text-dark">${student.academic_email}</h6>
        </div>
        <div class="student-info-item">
          <small class="text-muted">Program</small>
          <h6 class="mb-0 text-dark">${student.program ? student.program.name : 'Not Assigned'}</h6>
        </div>
      </div>
      <div class="col-12 col-md-4 mb-3 mb-md-0">
        <div class="student-info-item">
          <small class="text-muted">Academic ID</small>
          <h6 class="mb-0 text-dark">${student.academic_id}</h6>
        </div>
        <div class="student-info-item">
          <small class="text-muted">National ID</small>
          <h6 class="mb-0 text-dark">${student.national_id}</h6>
        </div>
        <div class="student-info-item">
          <small class="text-muted">Level</small>
          <h6 class="mb-0 text-dark">Level ${student.level.name}</h6>
        </div>
        <div class="student-info-item">
          <small class="text-muted">CGPA</small>
          <h6 class="mb-0 text-dark">${student.cgpa || 'N/A'}</h6>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="student-info-item">
          <small class="text-muted">Total Units Taken</small>
          <h6 class="mb-0 text-dark">${student.taken_hours || 0} Units</h6>
        </div>
        <div class="student-info-item">
          <small class="text-muted">Remaining Hours</small>
          <h6 class="mb-0 text-dark">Unknown</h6>
        </div>
      </div>
    `;
    
    $('#studentInfo').html(studentInfoHtml);
    $('#studentDetails').show();
  }
};

// ========================================
// ENROLLMENT HISTORY MODULE
// ========================================
const EnrollmentHistoryModule = {
  load(studentId) {
    if (!studentId) {
      Utils.showEmptyState('#enrollmentHistoryBox', 'bx-search-alt-2', 'Find a student to view enrollment history');
      $('#historyCount').text('0');
      EnrollmentState.originalHistoryData = [];
      return;
    }

    Utils.showLoading('#enrollmentHistoryBox', 'Loading enrollment history...');
    
    $.ajax({
      url: window.routes.studentEnrollments,
      method: 'POST',
      data: { student_id: studentId },
      success: (res) => {
        const history = res.data || [];
        EnrollmentState.originalHistoryData = history;
        $('#historyCount').text(history.length);
        
        if (history.length === 0) {
          Utils.showEmptyState('#enrollmentHistoryBox', 'bx-info-circle', 'No enrollment history found');
        } else {
          this.display(history);
        }
      },
      error: () => {
        Utils.showErrorState('#enrollmentHistoryBox', 'Could not load enrollment history');
        $('#historyCount').text('0');
        EnrollmentState.originalHistoryData = [];
      }
    });
  },

  display(enrollments) {
    if (enrollments.length === 0) {
      Utils.showEmptyState('#enrollmentHistoryBox', 'bx-search-alt', 'No enrollments found matching your search');
      return;
    }

    let html = '';
    enrollments.forEach((enrollment) => {
      html += `
        <div class="history-item">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h6 class="mb-1 text-dark">${enrollment.course ? enrollment.course.name : 'Course #' + enrollment.course_id}</h6>
              <p class="text-muted mb-1">
                <i class="bx bx-calendar me-1"></i>
                <strong>${enrollment.term ? enrollment.term.name : 'Term #' + enrollment.term_id}</strong>
              </p>
              ${enrollment.grade ? 
                `<div class="mt-1"><span class="badge bg-primary text-white"><i class="bx bx-star me-1"></i>Grade: <strong>${enrollment.grade}</strong></span></div>` : 
                '<div class="mt-1"><span class="badge bg-secondary text-white"><i class="bx bx-time me-1"></i>No Grade Yet</span></div>'
              }
            </div>
            <span class="badge bg-success text-white">Enrolled</span>
          </div>
        </div>
      `;
    });
    $('#enrollmentHistoryBox').html(html);
  },

  filter(searchTerm) {
    if (!EnrollmentState.originalHistoryData.length) return;
    
    const filteredData = EnrollmentState.originalHistoryData.filter(enrollment => {
      const courseName = (enrollment.course?.name || '').toLowerCase();
      const courseCode = (enrollment.course?.code || '').toLowerCase();
      const termName = (enrollment.term?.name || '').toLowerCase();
      const grade = enrollment.grade ? enrollment.grade.toString() : '';
      
      return courseName.includes(searchTerm) || 
             courseCode.includes(searchTerm) || 
             termName.includes(searchTerm) || 
             grade.includes(searchTerm);
    });

    this.display(filteredData);
  }
};

// ========================================
// TIME CONFLICT MODULE
// ========================================
const TimeConflictModule = {
  hasConflict(activity1, activity2) {
    if (!activity1 || !activity2) return false;
    if (!activity1.day_of_week || !activity2.day_of_week) return false;
    if (activity1.day_of_week.toLowerCase() !== activity2.day_of_week.toLowerCase()) return false;
    const start1 = Utils.parseTime(activity1.start_time);
    const end1 = Utils.parseTime(activity1.end_time);
    const start2 = Utils.parseTime(activity2.start_time);
    const end2 = Utils.parseTime(activity2.end_time);
    return (start1 < end2) && (start2 < end1);
  },

  checkScheduleConflicts(newCourseData, currentCourseId) {
    const conflicts = [];
    
    // Check conflicts with other selected courses
    EnrollmentState.selectedCourseGroups.forEach((groupData, courseId) => {
      if (courseId != currentCourseId && groupData.group_activities) {
        groupData.group_activities.forEach(currentActivity => {
          newCourseData.selected_activities.forEach(newActivity => {
            if (this.hasConflict(currentActivity, newActivity)) {
              conflicts.push({
                conflictingCourse: groupData.course.name,
                conflictingActivity: currentActivity,
                newActivity: newActivity,
                conflictType: 'selected_course'
              });
            }
          });
        });
      }
    });

    // Check conflicts with existing enrolled courses (from initialization)
    const existingEnrolledActivities = EnrollmentState.selectedActivities.filter(
      activity => activity.source === 'old_schedule'
    );

    existingEnrolledActivities.forEach(enrolledItem => {
      newCourseData.selected_activities.forEach(newActivity => {
        if (this.hasConflict(enrolledItem.activity, newActivity)) {
          conflicts.push({
            conflictingCourse: enrolledItem.course.name,
            conflictingActivity: enrolledItem.activity,
            newActivity: newActivity,
            conflictType: 'enrolled_course',
            enrolledGroup: enrolledItem.group
          });
        }
      });
    });
    
    return conflicts;
  },

  showConflictWarning(conflicts, onConfirm, onCancel) {
    let conflictDetailsHtml = '';
    
    conflicts.forEach((conflict, index) => {
      // Different styling based on conflict type
      const isEnrolledConflict = conflict.conflictType === 'enrolled_course';
      const cardHeaderClass = isEnrolledConflict ? 'bg-warning text-dark' : 'bg-danger text-white';
      const badgeClass = isEnrolledConflict ? 'bg-warning text-dark' : 'bg-danger text-white';
      
      conflictDetailsHtml += `
        <div class="alert  mb-3">
          <div class="d-flex align-items-start">
            <div class="conflict-indicator me-3">
              <i class="bx bx-error-circle"></i>
            </div>
            <div class="flex-grow-1">
              <h6 class="mb-2 text-dark">
                <i class="bx bx-book me-1"></i>
                Conflict #${index + 1}: ${conflict.conflictingCourse} vs New Selection
                ${isEnrolledConflict ? '<span class="badge bg-warning text-dark ms-2">Already Enrolled</span>' : '<span class="badge bg-info text-white ms-2">Selected Course</span>'}
              </h6>
              <div class="row">
                <div class="col-md-6">
                  <div class="card border-danger">
                    <div class="card-header ${cardHeaderClass}">
                      <small>
                        <i class="bx bx-clock me-1"></i>
                        ${isEnrolledConflict ? 'Enrolled Course' : 'Selected Course'}
                      </small>
                    </div>
                    <div class="card-body">
                      <strong class="text-dark">${conflict.conflictingCourse}</strong><br>
                      ${isEnrolledConflict && conflict.enrolledGroup ? `<small class="text-muted">Group ${conflict.enrolledGroup}</small><br>` : ''}
                      <div class="schedule-time-display mt-2">
                        <i class="bx bx-time me-1"></i>
                        <span class="schedule-time-range">${conflict.conflictingActivity.start_time} - ${conflict.conflictingActivity.end_time}</span><br>
                        <i class="bx bx-calendar me-1"></i>
                        ${conflict.conflictingActivity.day_of_week}<br>
                        <i class="bx bx-chalkboard me-1"></i>
                        ${conflict.conflictingActivity.activity_type.charAt(0).toUpperCase() + conflict.conflictingActivity.activity_type.slice(1)}
                        ${conflict.conflictingActivity.location ? '<br><i class="bx bx-map me-1"></i>' + conflict.conflictingActivity.location : ''}
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                      <small><i class="bx bx-plus me-1"></i>New Selection</small>
                    </div>
                    <div class="card-body">
                      <strong class="text-dark">New Course Selection</strong><br>
                      <div class="schedule-time-display mt-2">
                        <i class="bx bx-time me-1"></i>
                        <span class="schedule-time-range">${conflict.newActivity.start_time} - ${conflict.newActivity.end_time}</span><br>
                        <i class="bx bx-calendar me-1"></i>
                        ${conflict.newActivity.day_of_week}<br>
                        <i class="bx bx-chalkboard me-1"></i>
                        ${conflict.newActivity.activity_type.charAt(0).toUpperCase() + conflict.newActivity.activity_type.slice(1)}
                        ${conflict.newActivity.location ? '<br><i class="bx bx-map me-1"></i>' + conflict.newActivity.location : ''}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    });

    // Add warning message for enrolled course conflicts
    const enrolledConflicts = conflicts.filter(c => c.conflictType === 'enrolled_course');
    if (enrolledConflicts.length > 0) {
      conflictDetailsHtml = `
        <div class="alert alert-danger mb-3">
          <div class="d-flex align-items-center">
            <i class="bx bx-error-circle me-2"></i>
            <strong class="text-dark">Warning: Conflicts with Already Enrolled Courses!</strong>
          </div>
          <p class="mb-0 text-dark small mt-1">
            You have ${enrolledConflicts.length} conflict(s) with courses you're already enrolled in. 
            Proceeding may cause schedule conflicts in your timetable.
          </p>
        </div>
      ` + conflictDetailsHtml;
    }
    
    $('#conflictDetailsList').html(conflictDetailsHtml);
    
    // Set up modal event handlers
    $('#proceedWithConflictBtn').off('click').on('click', function() {
      $('#scheduleConflictModal').modal('hide');
      onConfirm();
    });
    
    $('#scheduleConflictModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
      if (!$(this).data('proceeded')) {
        onCancel();
      }
    });
    
    $('#proceedWithConflictBtn').on('click', function() {
      $('#scheduleConflictModal').data('proceeded', true);
    });
    
    $('#scheduleConflictModal').on('show.bs.modal', function() {
      $(this).data('proceeded', false);
    });
    
    $('#scheduleConflictModal').modal('show');
  }
};

// ========================================
// COURSE MODULE
// ========================================
const CourseModule = {
  load(studentId, termId) {
    if (!studentId || !termId) {
      Utils.showEmptyState('#coursesBox', 'bx-book-bookmark', 'Select a term to view available courses');
      $('#coursesCount').text('0');
      this.hideRelatedElements();
      EnrollmentState.originalCoursesData = [];
      EnrollmentState.selectedCourseGroups.clear();
      return;
    }

    Utils.showLoading('#coursesBox', 'Loading available courses...');
    
    $.ajax({
      url: window.routes.availableCourses,
      method: 'GET',
      data: { student_id: studentId, term_id: termId },
      success: (res) => {
        const courses = res.courses || [];
        EnrollmentState.originalCoursesData = courses;
        $('#coursesCount').text(courses.length);
        
        if (courses.length === 0) {
          Utils.showEmptyState('#coursesBox', 'bx-info-circle', 'No available courses found for this term');
          this.hideRelatedElements();
        } else {
          this.loadWithPrerequisites(courses);
          this.showRelatedElements();
          CreditHoursModule.loadRemaining(studentId, termId);
        }
      },
      error: () => {
        Utils.showErrorState('#coursesBox', 'Could not load available courses');
        $('#coursesCount').text('0');
        this.hideRelatedElements();
        EnrollmentState.originalCoursesData = [];
      }
    });
  },

  hideRelatedElements() {
    $('#enrollBtn').hide();
    $('#creditHoursSummary').hide();
    $('#exceptionAlert').hide();
    $('#weeklyScheduleCard').hide();
  },

  showRelatedElements() {
    $('#enrollBtn').show();
    EnrollmentUIModule.updateEnrollButton();
    CreditHoursModule.updateSummary();
  },

  loadWithPrerequisites(courses) {
    const courseIds = courses.map(course => course.available_course_id);
    
    $.ajax({
      url: window.routes.prerequisites,
      method: 'POST',
      data: { 
        student_id: EnrollmentState.currentStudentId, 
        course_ids: courseIds,
      },
      success: (res) => {
        const prerequisites = res.data || [];
        this.display(courses, prerequisites);
      },
      error: () => {
        this.display(courses, []);
      }
    });
  },

  display(courses, prerequisites) {
    let html = '';
    
    courses.forEach((course) => {
      const capacityClass = course.remaining_capacity > 10 ? 'success' : 
                          course.remaining_capacity > 5 ? 'warning' : 'danger';
      
      const coursePrereqs = prerequisites.filter(p => 
        p.required_for_course_id == course.available_course_id
      );
      
      const hasUnfulfilledPrereqs = coursePrereqs.some(p => !p.is_enrolled);
      
      // Check for schedule conflicts with this course
      const hasScheduleConflicts = this.checkCourseConflicts(course.available_course_id);
      
      const canEnroll = !hasUnfulfilledPrereqs && !hasScheduleConflicts;
      const disabledReason = hasUnfulfilledPrereqs ? 'Prerequisites required' : 
                           hasScheduleConflicts ? 'Schedule conflicts with enrolled courses' : '';
      
      html += `
        <div class="course-item ${!canEnroll ? 'disabled' : ''}" data-course-id="${course.available_course_id}">
          <div class="form-check">
            <input class="form-check-input course-checkbox" type="checkbox" 
                   name="available_course_ids[]" value="${course.available_course_id}" 
                   data-credit-hours="${course.credit_hours}" 
                   id="course_${course.available_course_id}"
                   ${!canEnroll ? `disabled title="${disabledReason}"` : ''}>
            <label class="form-check-label w-100" for="course_${course.available_course_id}">
              <div class="d-flex justify-content-between align-items-start">
                <div style="flex: 1;">
                  <h6 class="mb-1 text-dark">${course.name}</h6>
                  <p class="text-muted mb-0 small">
                    <i class="bx bx-book me-1"></i>
                    Course Code: <strong>${course.code || 'N/A'}</strong>
                    <span class="ms-2"><i class="bx bx-timer me-1"></i>Credit Hours: <strong class="text-primary">${course.credit_hours}</strong></span>
                  </p>
                  
                  ${this.renderPrerequisites(coursePrereqs, hasUnfulfilledPrereqs)}
                  
                  ${hasScheduleConflicts ? this.renderScheduleConflictWarning() : ''}
                  
                  <div class="selected-group-info" id="groupInfo_${course.available_course_id}" style="display:none;">
                    <small class="text-primary fw-semibold">
                      <i class="bx bx-chalkboard me-1"></i>Selected Schedules: <span class="group-name"></span>
                    </small>
                    <div class="group-details mt-1"></div>
                  </div>
                </div>
                <div class="text-end">
                  <span class="badge bg-${capacityClass} capacity-badge mb-2">
                    <i class="bx bx-group me-1"></i>
                    ${course.remaining_capacity} spots left
                  </span>
                  ${hasUnfulfilledPrereqs ? '<div><span class="badge bg-danger text-white"><i class="bx bx-lock me-1"></i>Prerequisites Required</span></div>' : ''}
                  ${hasScheduleConflicts ? '<div><span class="badge bg-warning text-dark"><i class="bx bx-error-circle me-1"></i>Schedule Conflict</span></div>' : ''}
                </div>
              </div>
            </label>
          </div>
        </div>
      `;
    });
    
    $('#coursesBox').html(html);
    this.attachEventHandlers();
  },

  checkCourseConflicts(courseId) {
    // This is a placeholder - we would need to load course schedules to check conflicts
    // For now, we'll implement basic conflict checking based on enrolled courses
    // In a real implementation, you would make an AJAX call to check conflicts
    
    // Check if any of the current enrolled activities would conflict
    // This is a simplified implementation - ideally you'd check all possible schedules
    const existingEnrolledActivities = EnrollmentState.selectedActivities.filter(
      item => item.source === 'old_schedule'
    );
    
    // For this simplified implementation, we'll return false
    // In practice, you'd need to load the course schedules and check conflicts
    return false;
  },

  renderScheduleConflictWarning() {
    return `
      <div class="alert alert-warning mt-2 mb-0 py-2">
        <div class="d-flex align-items-center">
          <i class="bx bx-error-circle me-2 text-warning"></i>
          <small class="text-dark">
            <strong>Schedule Conflict:</strong> This course has schedules that conflict with your current enrollments.
          </small>
        </div>
      </div>
    `;
  },

  renderPrerequisites(coursePrereqs, hasUnfulfilledPrereqs) {
    if (coursePrereqs.length === 0) {
      return '<div class="mt-2"><small class="text-success"><i class="bx bx-check me-1"></i>No prerequisites required</small></div>';
    }

    let html = `
      <div class="prerequisites-status">
        <small class="text-dark fw-semibold mb-2 d-block">
          <i class="bx bx-link me-1"></i>Prerequisites:
        </small>
        ${coursePrereqs.map(prereq => `
          <div class="prerequisite-check ${prereq.is_enrolled ? 'fulfilled' : 'missing'}" 
               ${!prereq.is_enrolled ? `onclick="PrerequisiteModule.showMissing([${JSON.stringify(prereq).replace(/"/g, '&quot;')}])"` : ''}
               title="${prereq.is_enrolled ? 'Prerequisite completed' : 'Click to view details - Prerequisite not completed'}">
            <i class="bx ${prereq.is_enrolled ? 'bx-check-circle' : 'bx-x-circle'} me-2"></i>
            <span class="small">
              <strong>${prereq.course_name}</strong> 
              <span class="text-muted">(${prereq.course_code})</span>
              ${prereq.is_enrolled ? 
                '<span class="badge bg-success ms-2">✓ Completed</span>' : 
                '<span class="badge bg-danger ms-2">✗ Missing</span>'
              }
            </span>
          </div>
        `).join('')}
        
        ${hasUnfulfilledPrereqs ? `
          <div class="alert alert-warning mt-2 mb-0 py-2">
            <div class="d-flex align-items-center">
              <i class="bx bx-error-circle me-2 text-warning"></i>
              <small class="text-dark">
                <strong>Enrollment Blocked:</strong> Complete all prerequisites before enrolling in this course.
              </small>
            </div>
          </div>
        ` : `
          <div class="alert alert-success mt-2 mb-0 py-2">
            <div class="d-flex align-items-center">
              <i class="bx bx-check-circle me-2 text-success"></i>
              <small class="text-dark">
                <strong>All Prerequisites Met:</strong> You can enroll in this course.
              </small>
            </div>
          </div>
        `}
      </div>
    `;

    return html;
  },

  attachEventHandlers() {
    $('.course-checkbox').on('change', function() {
      const courseId = $(this).val();
      const isChecked = $(this).is(':checked');
      
      if (isChecked) {
        ActivitySelectionModule.show(courseId);
      } else {
        EnrollmentState.selectedCourseGroups.delete(courseId);
        $(`#groupInfo_${courseId}`).hide();
        $(this).closest('.course-item').removeClass('selected');
        EnrollmentUIModule.updateEnrollButton();
        CreditHoursModule.updateSummary();
        ScheduleModule.update();
      }
    });
  },

  filter(searchTerm) {
    if (!EnrollmentState.originalCoursesData.length) return;
    
    const filteredData = EnrollmentState.originalCoursesData.filter(course => {
      const courseName = (course.name || '').toLowerCase();
      const courseCode = (course.code || '').toLowerCase();
      
      return courseName.includes(searchTerm) || courseCode.includes(searchTerm);
    });

    if (filteredData.length > 0) {
      this.loadWithPrerequisites(filteredData);
    } else {
      Utils.showEmptyState('#coursesBox', 'bx-search-alt', 'No courses found matching your search');
    }
  }
};

// ========================================
// PREREQUISITE MODULE
// ========================================
const PrerequisiteModule = {
  showMissing(missingPrereqs) {
    let html = '';
    missingPrereqs.forEach((prereq) => {
      html += `
        <div class="alert alert-danger">
          <div class="d-flex align-items-center">
            <i class="bx bx-x-circle me-3 text-danger" style="font-size: 1.5rem;"></i>
            <div>
              <h6 class="mb-1 text-dark">${prereq.course_name}</h6>
              <p class="mb-0 small text-dark">
                <i class="bx bx-book me-1"></i>Course Code: <strong>${prereq.course_code || 'N/A'}</strong> | 
                <i class="bx bx-timer me-1"></i>Credit Hours: <strong>${prereq.credit_hours || 'N/A'}</strong>
              </p>
            </div>
          </div>
        </div>
      `;
    });
    
    $('#missingPrerequisitesList').html(html);
    $('#prerequisitesModal').modal('show');
  }
};

// ========================================
// ACTIVITY SELECTION MODULE
// ========================================
const ActivitySelectionModule = {
  show(courseId) {
    const course = EnrollmentState.originalCoursesData.find(c => c.available_course_id == courseId);
    if (!course) return;
    
    $('#activitySelectionModalLabel').html(`
      <i class="bx bx-chalkboard me-2"></i>
      Select Activity Schedule for ${course.name}
    `);
    
    $('#courseActivityInfo').html(`
      <div class="alert alert-info">
        <h6 class="mb-1 text-dark">${course.name}</h6>
        <p class="mb-0 small text-dark">
          <i class="bx bx-book me-1"></i>Course Code: <strong>${course.code || 'N/A'}</strong> | 
          <i class="bx bx-timer me-1"></i>Credit Hours: <strong>${course.credit_hours}</strong>
        </p>
      </div>
    `);
    
    this.loadActivities(courseId);
    $('#activitySelectionModal').data('course-id', courseId);
    $('#activitySelectionModal').modal('show');
  },

  loadActivities(courseId) {
    Utils.showLoading('#activitiesList', 'Loading course schedules...');
    
    const url = window.routes.courseSchedules.replace(':id', courseId);
    $.ajax({
      url: url,
      method: 'GET',
      success: (res) => {
        const activityTypes = res.data || [];
        $('#activitySelectionModal').data('activityTypes', activityTypes);
        this.displayActivities(activityTypes);
      },
      error: () => {
        $('#activitiesList').html(`
          <div class="alert alert-danger">
            <i class="bx bx-error-circle me-2"></i>
            <span class="text-dark">Failed to load course schedules. Please try again.</span>
          </div>
        `);
      }
    });
  },

  displayActivities(activityTypes) {
    if (activityTypes.length === 0) {
      $('#activitiesList').html(`
        <div class="alert alert-warning">
          <i class="bx bx-info-circle me-2"></i>
          <span class="text-dark">No schedules available for this course.</span>
        </div>
      `);
      return;
    }
    
    let html = '';
    
    activityTypes.forEach((activityTypeData) => {
      const activityType = activityTypeData.activity_type;
      const schedules = activityTypeData.schedules || [];
      
      if (schedules.length === 0) return;
      
      const activityIcon = activityType === 'lecture' ? 'bx-book-open' : 
                          activityType === 'lab' ? 'bx-flask' : 
                          activityType === 'tutorial' ? 'bx-edit' : 'bx-chalkboard';
      
      html += `
        <div class="activity-type-section mb-4" data-activity-type="${activityType}">
          <div class="activity-type-header bg-light p-3 rounded-top border">
            <h5 class="mb-0 text-dark">
              <i class="bx ${activityIcon} me-2"></i>
              ${activityType.charAt(0).toUpperCase() + activityType.slice(1)} 
              <span class="badge bg-primary ms-2">${schedules.length} option${schedules.length !== 1 ? 's' : ''}</span>
              <span class="badge bg-danger ms-1 text-white" style="font-size: 0.75em;">* Required</span>
            </h5>
            <small class="text-muted">Select one schedule for this activity type</small>
          </div>
          <div class="activity-options border border-top-0 rounded-bottom p-3">
      `;
      
      schedules.forEach((schedule, index) => {
        const isConflicting = this.checkActivityConflict(schedule);
        const conflictClass = isConflicting ? 'border-danger' : 'border-light';
        const disabledAttr = isConflicting ? 'disabled' : '';
        const disabledClass = isConflicting ? 'activity-disabled' : '';
        
        html += `
          <div class="activity-option mb-2 ${disabledClass}" data-activity-id="${schedule.id}">
            <div class="card ${conflictClass}">
              <div class="card-body p-3">
                <div class="form-check">
                  <input class="form-check-input activity-radio" type="radio" 
                         name="activity_${activityType}" value="${schedule.id}" 
                         data-activity-type="${activityType}"
                         data-group-number="${schedule.group_number}"
                         id="activity_${schedule.id}" ${disabledAttr}>
                  <label class="form-check-label w-100" for="activity_${schedule.id}">
                    <div class="d-flex justify-content-between align-items-start">
                      <div class="flex-grow-1">
                        <h6 class="mb-1 text-dark">
                          Group ${schedule.group_number} - ${activityType.charAt(0).toUpperCase() + activityType.slice(1)}
                          ${isConflicting ? '<span class="badge bg-danger ms-2">CONFLICT</span>' : ''}
                        </h6>
                        <div class="schedule-details">
                          <p class="text-muted mb-1 small">
                            <i class="bx bx-time me-1"></i>
                            <strong>${Utils.formatTimeRange(schedule.start_time, schedule.end_time)}</strong>
                          </p>
                          <p class="text-muted mb-1 small">
                            <i class="bx bx-calendar me-1"></i>
                            <strong>${schedule.day_of_week || 'Schedule TBA'}</strong>
                          </p>
                          ${schedule.location ? `
                            <p class="text-muted mb-0 small">
                              <i class="bx bx-map me-1"></i>
                              ${schedule.location}
                            </p>
                          ` : ''}
                          ${isConflicting ? `
                            <p class="text-danger mb-0 small">
                              <i class="bx bx-error-circle me-1"></i>
                              Conflicts with your current schedule
                            </p>
                          ` : ''}
                        </div>
                      </div>
                      <div class="text-end">
                        <span class="badge ${isConflicting ? 'bg-secondary' : 'bg-info'} text-white small">
                          <i class="bx bx-users me-1"></i>
                          ${schedule.enrolled_count || 0}/${schedule.max_capacity}
                        </span>
                      </div>
                    </div>
                  </label>
                </div>
              </div>
            </div>
          </div>
        `;
      });
      
      html += `
          </div>
        </div>
      `;
    });
    
    $('#activitiesList').html(html);
    
    // Handle activity selection
    $('.activity-radio').on('change', this.handleActivitySelection.bind(this));
  },

  checkActivityConflict(activity) {
    // Check conflicts with existing enrolled courses
    const existingEnrolledActivities = EnrollmentState.selectedActivities.filter(
      item => item.source === 'old_schedule'
    );

    for (let enrolledItem of existingEnrolledActivities) {
      if (TimeConflictModule.hasConflict(enrolledItem.activity, activity)) {
        return true;
      }
    }

    // Check conflicts with other selected activities from current course selections
    for (let [courseId, groupData] of EnrollmentState.selectedCourseGroups) {
      if (groupData.group_activities) {
        for (let selectedActivity of groupData.group_activities) {
          if (TimeConflictModule.hasConflict(selectedActivity, activity)) {
            return true;
          }
        }
      }
    }

    return false;
  },

  handleActivitySelection() {
    this.updateConfirmButton();
  },

  updateConfirmButton() {
    const allActivityTypes = {};
    
    // Collect all activity types and their selection status
    $('.activity-type-section').each(function() {
      const activityType = $(this).data('activity-type');
      const hasSelection = $(this).find('.activity-radio:checked').length > 0;
      allActivityTypes[activityType] = hasSelection;
    });

    // Check if all activity types have at least one selection
    const allTypesSelected = Object.values(allActivityTypes).every(hasSelection => hasSelection);
    
    $('#confirmActivitySelection').prop('disabled', !allTypesSelected);
    
    // Update UI feedback
    $('.activity-type-header').each(function() {
      const section = $(this).closest('.activity-type-section');
      const activityType = section.data('activity-type');
      const hasSelection = allActivityTypes[activityType];
      
      if (hasSelection) {
        $(this).find('.badge.bg-danger').hide();
        $(this).find('.badge.bg-success').remove();
        $(this).find('h5').append('<span class="badge bg-success ms-2">✓ Selected</span>');
      } else {
        $(this).find('.badge.bg-danger').show();
        $(this).find('.badge.bg-success').remove();
      }
    });
  },

    confirmSelection() {
    const courseId = $('#activitySelectionModal').data('course-id');
    const selectedActivities = $('.activity-radio:checked');
    
    if (selectedActivities.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'No Activities Selected',
        text: 'Please select one activity for each activity type.',
        confirmButtonText: 'OK'
      });
      return;
    }

    // Check if all activity types have exactly one selection
    const allActivityTypes = {};
    $('.activity-type-section').each(function() {
      const activityType = $(this).data('activity-type');
      const hasSelection = $(this).find('.activity-radio:checked').length > 0;
      allActivityTypes[activityType] = hasSelection;
    });

    const unselectedTypes = Object.keys(allActivityTypes).filter(type => !allActivityTypes[type]);
    
    if (unselectedTypes.length > 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Missing Required Selections',
        html: `Please select one activity for the following activity types:<br><strong>${unselectedTypes.join(', ')}</strong>`,
        confirmButtonText: 'OK'
      });
      return;
    }
    
    const cachedActivityTypes = $('#activitySelectionModal').data('activityTypes') || [];
    const selectedActivityData = [];
    
    selectedActivities.each(function() {
      const activityId = $(this).val();
      const activityType = $(this).data('activity-type');
      const groupNumber = $(this).data('group-number');
      
      // Find the activity data in the new structure
      for (let activityTypeData of cachedActivityTypes) {
        const schedule = activityTypeData.schedules.find(s => s.id == activityId);
        if (schedule) {
          selectedActivityData.push(schedule);
          break;
        }
      }
    });

    const courseData = {
      course_id: courseId,
      selected_activities: selectedActivityData,
      course: EnrollmentState.originalCoursesData.find(c => c.available_course_id == courseId)
    };
    
    // NEW: Check for conflicts, including with old enrollments
    const conflicts = TimeConflictModule.checkScheduleConflicts(courseData, courseId);
    
    // BONUS: Also check for intra-course conflicts (new activities conflicting among themselves)
    for (let i = 0; i < selectedActivityData.length; i++) {
      for (let j = i + 1; j < selectedActivityData.length; j++) {
        if (TimeConflictModule.hasConflict(selectedActivityData[i], selectedActivityData[j])) {
          conflicts.push({
            conflictingCourse: courseData.course.name,
            conflictingActivity: selectedActivityData[i],
            newActivity: selectedActivityData[j],
            conflictType: 'intra_course'
          });
        }
      }
    }

    if (conflicts.length > 0) {
      TimeConflictModule.showConflictWarning(conflicts, () => {
        // Proceed despite conflicts
        this.finalizeSelection(courseId, courseData);
      }, () => {
        // Cancel: Stay in modal, do nothing
      });
    } else {
      // No conflicts: Proceed normally
      this.finalizeSelection(courseId, courseData);
    }
  },

  finalizeSelection(courseId, courseData) {
    // Store the selected activities in the expected format
    const groupData = {
      course_id: courseId,
      group_activities: courseData.selected_activities,
      course: courseData.course,
      selected_activities_by_type: this.organizeActivitiesByType(courseData.selected_activities)
    };
    
    EnrollmentState.selectedCourseGroups.set(courseId, groupData);
    
    $(`#course_${courseId}`).prop('checked', true);
    
    const groupInfo = $(`#groupInfo_${courseId}`);
    
    // Create summary of selected activities
    const activitiesByType = this.organizeActivitiesByType(courseData.selected_activities);
    let activitiesSummary = Object.keys(activitiesByType).map(activityType => {
      const activity = activitiesByType[activityType][0]; // Only one activity per type now
      return `<span class="badge bg-success me-1">${activityType.charAt(0).toUpperCase() + activityType.slice(1)} - Group ${activity.group_number}</span>`;
    }).join('');
    
    groupInfo.find('.group-name').text(`${Object.keys(activitiesByType).length} Activity Type${Object.keys(activitiesByType).length !== 1 ? 's' : ''} Selected`);
    groupInfo.find('.group-details').html(`
      <div class="mb-1">${activitiesSummary}</div>
      <small class="text-muted">
        <i class="bx bx-info-circle me-1"></i>Selected ${courseData.selected_activities.length} activity${courseData.selected_activities.length !== 1 ? ' schedules' : ' schedule'} across ${Object.keys(activitiesByType).length} activity type${Object.keys(activitiesByType).length !== 1 ? 's' : ''}
      </small>
    `);
    groupInfo.show();
    
    $(`.course-item[data-course-id="${courseId}"]`).addClass('selected');
    
    $('#activitySelectionModal').modal('hide');
    
    EnrollmentUIModule.updateEnrollButton();
    CreditHoursModule.updateSummary();
    ScheduleModule.update();
  },

  organizeActivitiesByType(activities) {
    const activitiesByType = {};
    activities.forEach(activity => {
      if (!activitiesByType[activity.activity_type]) {
        activitiesByType[activity.activity_type] = [];
      }
      activitiesByType[activity.activity_type].push(activity);
    });
    return activitiesByType;
  }
};

// ========================================
// CREDIT HOURS MODULE
// ========================================
const CreditHoursModule = {
  loadRemaining(studentId, termId) {
    if (!studentId || !termId) return;
    
    $.ajax({
      url: window.routes.remainingCreditHours,
      method: 'POST',
      data: { 
        student_id: studentId, 
        term_id: termId, 
      },
      success: (res) => {
        if (res.success && res.data) {
          const data = res.data;
          
          $('#currentEnrollmentHours').text(data.current_enrollment_hours);
          $('#selectedCH').text('0');
          $('#maxCH').text(data.max_allowed_hours);
          $('#remainingCH').text(data.remaining_hours);
          
          this.updateProgress(data.current_enrollment_hours, data.max_allowed_hours);
          
          const exceptionAlert = $('#exceptionAlert');
          if (data.exception_hours > 0) {
            exceptionAlert.html(`
              <div class="alert alert-warning alert-sm mt-2 mb-0">
                <div class="d-flex align-items-center">
                  <i class="bx bx-shield-check me-2 text-warning"></i>
                  <div class="text-dark">
                    <strong>Admin Exception:</strong> +${data.exception_hours} additional credit hours granted
                  </div>
                </div>
              </div>
            `).show();
          } else {
            exceptionAlert.hide();
          }
          
          $('#creditHoursSummary').show();
          this.updateInfoBox(data.student_cgpa, data.term_season);
        }
      },
      error: (xhr) => {
        console.error('Failed to load remaining credit hours:', xhr);
        this.updateSummary();
      }
    });
  },

  updateSummary() {
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
    
    this.updateProgress(newTotal, maxCH);
  },

  updateProgress(current, max) {
    if (max <= 0) return;
    
    const percentage = Math.min((current / max) * 100, 100);
    const progressBar = $('#usageProgressBar');
    const percentageText = $('#usagePercentage');
    
    progressBar.css('width', percentage + '%');
    percentageText.text(Math.round(percentage) + '%');
    
    progressBar.removeClass('bg-success bg-warning bg-danger');
    if (percentage < 70) {
      progressBar.addClass('bg-success');
    } else if (percentage < 90) {
      progressBar.addClass('bg-warning');
    } else {
      progressBar.addClass('bg-danger');
    }
  },

  updateInfoBox(cgpa, season) {
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
  },

  updateInfoBoxByTermName(termName) {
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
};

// ========================================
// SCHEDULE MODULE
// ========================================
const ScheduleModule = {
  initialize(studentId, termId) {
    if (!studentId || !termId) {
      $('#weeklyScheduleCard').hide();
      $('#scheduleConflictAlert').hide();
      return;
    }

    $.ajax({
      url: window.routes.getSchedules,
      method: 'GET',
      data: { student_id: studentId, term_id: termId },
      success: (res) => {
        if (res.success && Array.isArray(res.data) && res.data.length > 0) {
          EnrollmentState.selectedActivities = res.data.map(item => ({
            course: item.course,
            activity: item.activity,
            group: item.group,
            source: 'old_schedule'
          }));

          $('#weeklyScheduleCard').show();
          this.generateGrid(EnrollmentState.selectedActivities);
          this.highlightConflicts(EnrollmentState.selectedActivities);
        } else {
          $('#weeklyScheduleCard').hide();
          $('#scheduleConflictAlert').hide();
        }
      },
      error: (xhr) => {
        $('#weeklyScheduleCard').hide();
        $('#scheduleConflictAlert').hide();
        console.error('Failed to load old schedule:', xhr);
      }
    });
  },

  update() {
    EnrollmentState.selectedActivities = EnrollmentState.selectedActivities.filter(activity => activity.source !== 'selection');

    $('.course-checkbox:checked').each(function() {
      const courseId = $(this).val();
      const groupData = EnrollmentState.selectedCourseGroups.get(courseId);
      if (groupData && groupData.group_activities) {
        groupData.group_activities.forEach(activity => {
          EnrollmentState.selectedActivities.push({
            course: groupData.course,
            activity: activity,
            group: activity.group_number, // Use the group_number from the activity itself
            source: 'selection',
          });
        });
      }
    });
    
    if (EnrollmentState.selectedActivities.length === 0) {
      $('#weeklyScheduleCard').hide();
      $('#scheduleConflictAlert').hide();
      return;
    }
    
    $('#weeklyScheduleCard').show();
    this.generateGrid(EnrollmentState.selectedActivities);
    this.highlightConflicts(EnrollmentState.selectedActivities);
  },

  generateGrid(selectedActivities) {
    const days = ['Time', 'Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    const timeSlots = [
      '8:00 – 8:50', '9:00 – 9:50', '9:50 – 10:40', '10:40 – 11:30', '11:30 – 12:20', 
      '12:20 – 1:10', '1:10 – 2:00', '2:00 – 2:50', '2:50 – 3:40', '3:40 – 4:30', '4:30 – 5:20'
    ];
    
    let html = '';
    
    // Create headers
    days.forEach(day => {
      html += `<div class="schedule-header">${day}</div>`;
    });
    
    // Create time slots
    timeSlots.forEach(timeSlot => {
      html += `<div class="schedule-cell time-slot">${timeSlot}</div>`;
      
      for (let dayIndex = 1; dayIndex < days.length; dayIndex++) {
        const dayName = days[dayIndex].toLowerCase();
        
        const activitiesInSlot = selectedActivities.filter(item => {
          if (!item.activity || !item.activity.day_of_week) return false;
          
          const scheduleDayOfWeek = item.activity.day_of_week.toLowerCase();
          const dayMatches = scheduleDayOfWeek === dayName;
          
          return dayMatches && this.isTimeInRange(timeSlot, item.activity.start_time, item.activity.end_time);
        });
        
        if (activitiesInSlot.length > 0) {
          const isConflict = activitiesInSlot.length > 1;
          const cellClasses = `schedule-cell has-class ${isConflict ? 'has-conflict' : ''}`;
          
          let cellContent = '';
          activitiesInSlot.forEach((classItem, index) => {
            const activity = classItem.activity;
            const course = classItem.course;
            
            if (index > 0) cellContent += '<hr class="my-1" style="margin: 2px 0; border-color: rgba(255,255,255,0.3);">';
            
            cellContent += `
              <div class="class-info ${index > 0 ? 'mt-1' : ''}">
                <div class="class-title">${course.name}</div>
                <div class="class-details">
                  Group ${classItem.group} | ${activity.activity_type.charAt(0).toUpperCase() + activity.activity_type.slice(1)}<br>
                  <span class="schedule-time-range">${Utils.formatTimeRange(activity.start_time, activity.end_time)}</span>
                  ${activity.location ? '<br><span class="schedule-location">' + activity.location + '</span>' : ''}
                </div>
              </div>
            `;
          });
          
          const tooltipContent = activitiesInSlot.map(item => 
            `${item.course.name} - Group ${item.group} (${item.activity.activity_type}) ${Utils.formatTimeRange(item.activity.start_time, item.activity.end_time)}${item.activity.location ? ' @ ' + item.activity.location : ''}`
          ).join(' | ');
          
          html += `
            <div class="${cellClasses}" title="${tooltipContent}" data-bs-toggle="tooltip" data-bs-placement="top">
              ${cellContent}
              ${isConflict ? '<div class="conflict-indicator position-absolute top-0 end-0 mt-1 me-1"><i class="bx bx-error-circle text-warning"></i></div>' : ''}
            </div>
          `;
        } else {
          html += `<div class="schedule-cell"></div>`;
        }
      }
    });
    
    $('#weeklySchedule').html(html);
    
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip({
      html: true,
      container: 'body'
    });
    
    // Add click handlers for schedule cells - Fixed: Single click handler
    $('.schedule-cell.has-class').off('click').on('click', function() {
      const tooltip = $(this).attr('title');
      if (tooltip) {
        ScheduleModule.showCellDetails(tooltip, $(this).hasClass('has-conflict'));
      }
    });
  },

  isTimeInRange(timeSlot, startTime, endTime) {
    if (!startTime || !endTime) return false;
    
    const slotParts = timeSlot.split(/[–-]/).map(t => t.trim());
    if (slotParts.length < 2) return false;
    
    const slotStart = Utils.parseTime(slotParts[0]);
    const slotEnd = Utils.parseTime(slotParts[1]);
    const activityStart = Utils.parseTime(startTime);
    const activityEnd = Utils.parseTime(endTime);
    
    return (activityStart < slotEnd) && (activityEnd > slotStart);
  },

  highlightConflicts(selectedActivities) {
    $('.schedule-cell').removeClass('schedule-conflict has-conflict');
    $('#scheduleConflictAlert').hide();
    
    const conflicts = [];
    
    for (let i = 0; i < selectedActivities.length; i++) {
      for (let j = i + 1; j < selectedActivities.length; j++) {
        if (TimeConflictModule.hasConflict(selectedActivities[i].activity, selectedActivities[j].activity)) {
          conflicts.push({
            activity1: selectedActivities[i],
            activity2: selectedActivities[j]
          });
        }
      }
    }
    
    if (conflicts.length > 0) {
      $('#scheduleConflictAlert').show();
      
      // Fixed: Single click handler for conflict alert
      $('#scheduleConflictAlert').off('click').on('click', function() {
        ScheduleModule.showDetailedConflicts(conflicts);
      }).css('cursor', 'pointer');
      
      $('.schedule-cell.has-class').each(function() {
        const cellTitle = $(this).attr('title') || '';
        
        conflicts.forEach(conflict => {
          const activities = [conflict.activity1.activity, conflict.activity2.activity];
          activities.forEach(activity => {
            if (cellTitle.includes(activity.activity_type) && 
                cellTitle.includes(activity.start_time) && 
                cellTitle.includes(activity.end_time)) {
              
              $(this).addClass('schedule-conflict has-conflict');
              
              if (!$(this).find('.conflict-indicator').length) {
                $(this).append(`
                  <div class="conflict-indicator" title="Click for conflict details">
                    <i class="bx bx-error-circle"></i>
                  </div>
                `);
              }
            }
          });
        });
      });
      
      const conflictCount = conflicts.length;
      $('#scheduleConflictAlert').html(`
        <div class="d-flex align-items-center">
          <i class="bx bx-error-circle me-2 text-warning"></i>
          <div class="flex-grow-1">
            <strong class="text-dark">Schedule Conflicts Detected!</strong>
            <p class="mb-0 small text-dark">
              Found ${conflictCount} conflict${conflictCount !== 1 ? 's' : ''}. 
              <strong class="text-primary">Click here or on any ⚠️ icon for details.</strong>
            </p>
          </div>
          <div class="ms-3">
            <button class="btn btn-sm btn-outline-danger" onclick="ScheduleModule.showDetailedConflicts(ScheduleModule.getCurrentConflicts())">
              <i class="bx bx-info-circle me-1"></i>
              View Details
            </button>
          </div>
        </div>
      `);
    } else {
      $('.conflict-indicator').remove();
    }
  },

  showCellDetails(tooltipContent, isConflict) {
    const activities = tooltipContent.split(' | ');
    let detailsHtml = '';
    
    activities.forEach((activity) => {
      const alertClass = isConflict ? 'alert-warning' : 'alert-info';
      detailsHtml += `
        <div class="alert ${alertClass} mb-2">
          <div class="d-flex align-items-center">
            <i class="bx ${isConflict ? 'bx-error-circle text-warning' : 'bx-info-circle text-info'} me-2"></i>
            <strong>${activity}</strong>
          </div>
        </div>
      `;
    });
    
    if (isConflict) {
      detailsHtml = `
        <div class="alert alert-danger mb-3">
          <div class="d-flex align-items-center">
            <i class="bx bx-error-circle me-2"></i>
            <strong>Time Conflict Detected!</strong>
          </div>
        </div>
      ` + detailsHtml;
    }
    
    Swal.fire({
      title: isConflict ? 'Schedule Conflict' : 'Schedule Details',
      html: detailsHtml,
      icon: isConflict ? 'warning' : 'info',
      confirmButtonText: 'OK',
      width: '500px'
    });
  },

  showDetailedConflicts(conflicts) {
    if (!conflicts || conflicts.length === 0) return;
    
    const formattedConflicts = conflicts.map(conflict => ({
      conflictingCourse: conflict.activity1.course.name,
      conflictingActivity: conflict.activity1.activity,
      newActivity: conflict.activity2.activity
    }));
    
    TimeConflictModule.showConflictWarning(formattedConflicts, 
      () => console.log('User chose to proceed with conflicts'),
      () => console.log('User chose to resolve conflicts')
    );
  },

  getCurrentConflicts() {
    const conflicts = [];
    for (let i = 0; i < EnrollmentState.selectedActivities.length; i++) {
      for (let j = i + 1; j < EnrollmentState.selectedActivities.length; j++) {
        if (TimeConflictModule.hasConflict(EnrollmentState.selectedActivities[i].activity, EnrollmentState.selectedActivities[j].activity)) {
          conflicts.push({
            activity1: EnrollmentState.selectedActivities[i],
            activity2: EnrollmentState.selectedActivities[j]
          });
        }
      }
    }
    return conflicts;
  }
};

// ========================================
// ENROLLMENT UI MODULE
// ========================================
const EnrollmentUIModule = {
  updateEnrollButton() {
    const selectedCount = $('.course-checkbox:checked').length;
    const enrollBtn = $('#enrollBtn');
    
    if (selectedCount > 0) {
      enrollBtn.html(`
        <i class="bx bx-plus me-1"></i>Enroll Selected Courses (${selectedCount})
      `).prop('disabled', false).removeClass('btn-secondary').addClass('btn-success');
    } else {
      enrollBtn.html('<i class="bx bx-plus me-1"></i>Enroll Selected Courses')
             .prop('disabled', true).removeClass('btn-success').addClass('btn-secondary');
    }
  },

  resetForm() {
    $('.course-checkbox').prop('checked', false);
    EnrollmentState.selectedCourseGroups.clear();
    $('.course-item').removeClass('selected');
    $('.selected-group-info').hide();
    $('#weeklyScheduleCard').hide();
    EnrollmentHistoryModule.load(EnrollmentState.currentStudentId);
    CourseModule.load(EnrollmentState.currentStudentId, EnrollmentState.currentTermId);
  }
};

// ========================================
// ENROLLMENT SUBMISSION MODULE
// ========================================
const EnrollmentSubmissionModule = {
  submit() {
    // Validate basic form data
    if (!this.validateBasicForm()) {
      return;
    }
    
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
      if (!EnrollmentState.selectedCourseGroups.has(courseId)) {
        const courseName = EnrollmentState.originalCoursesData.find(c => c.available_course_id == courseId)?.name || 'Unknown Course';
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
    
    this.processEnrollment();
  },

  validateBasicForm() {
    const studentId = $('#student_id').val();
    const termId = $('#term_id').val();
    
    if (!studentId) {
      Swal.fire({
        icon: 'error',
        title: 'Student Not Selected',
        text: 'Please search and select a student first.',
        confirmButtonText: 'OK'
      });
      return false;
    }
    
    if (!termId) {
      Swal.fire({
        icon: 'error',
        title: 'Term Not Selected',
        text: 'Please select an academic term.',
        confirmButtonText: 'OK'
      });
      return false;
    }
    
    
    return true;
  },

  processEnrollment() {
    const formData = new FormData();
    
    // Add basic required data
    formData.append('student_id', $('#student_id').val());
    formData.append('term_id', $('#term_id').val());

    // Collect selected courses and their schedule data
    const selectedCourseIds = [];
    const scheduleIds = [];
    const courseScheduleMapping = {};

    $('.course-checkbox:checked').each(function() {
      const courseId = $(this).val();
      const groupData = EnrollmentState.selectedCourseGroups.get(courseId);
      
      if (groupData && groupData.group_activities) {
        selectedCourseIds.push(courseId);
        
        // Collect schedule IDs for this course
        const courseScheduleIds = groupData.group_activities.map(activity => activity.id);
        scheduleIds.push(...courseScheduleIds);
        
        // Create mapping as expected by backend
        courseScheduleMapping[courseId] = JSON.stringify(courseScheduleIds);
      }
    });

    // Add course data to form
    selectedCourseIds.forEach(courseId => {
      formData.append('available_course_ids[]', courseId);
    });

    // Add schedule IDs
    scheduleIds.forEach(scheduleId => {
      formData.append('available_course_schedule_ids[]', scheduleId);
    });

    // Add course schedule mapping
    Object.keys(courseScheduleMapping).forEach(courseId => {
      formData.append(`course_schedule_mapping[${courseId}]`, courseScheduleMapping[courseId]);
    });

    const enrollBtn = $('#enrollBtn');
    const originalText = enrollBtn.html();
    
    // Disable form and show loading state
    enrollBtn.html('<i class="bx bx-loader-alt bx-spin me-1"></i>Processing Enrollment...').prop('disabled', true);
    $('.course-checkbox').prop('disabled', true);
    $('#term_id').prop('disabled', true);
    
    console.log('Submitting enrollment data:', {
      studentId: $('#student_id').val(),
      termId: $('#term_id').val(),
      selectedCourses: selectedCourseIds.length,
      totalSchedules: scheduleIds.length
    });

    // Store request data for potential retry
    this.lastRequestData = {
      formData: formData,
      selectedCourseIds: selectedCourseIds,
      scheduleIds: scheduleIds
    };

    $.ajax({
      url: window.routes.storeEnrollment,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      timeout: 30000, // 30 second timeout
      success: (res) => {
        console.log('Enrollment successful:', res);
        this.lastRequestData = null; // Clear stored data on success
        this.handleSuccess(res);
      },
      error: (xhr) => {
        console.error('Enrollment failed:', xhr);
        this.handleError(xhr);
      },
      complete: () => {
        // Re-enable form
        enrollBtn.html(originalText).prop('disabled', false);
        $('.course-checkbox').prop('disabled', false);
        $('#term_id').prop('disabled', false);
        EnrollmentUIModule.updateEnrollButton();
      }
    });
  },

  handleSuccess(response) {
    const enrolledCourses = $('.course-checkbox:checked').length;
    
    Swal.fire({
      icon: 'success',
      title: 'Enrollment Successful!',
      html: `
        <div class="text-center">
          <p class="mb-3">Successfully enrolled in <strong>${enrolledCourses}</strong> course(s).</p>
          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-primary btn-sm" id="generatePdfBtn">
              <i class="bx bx-download me-1"></i>
              Download Schedule PDF
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="continueBtn">
              <i class="bx bx-check me-1"></i>
              Continue
            </button>
          </div>
        </div>
      `,
      showConfirmButton: false,
      allowOutsideClick: false,
      didOpen: () => {
        this.setupPdfDownload();
        this.setupContinueButton();
      }
    });
  },

  setupPdfDownload() {
    document.getElementById('generatePdfBtn').addEventListener('click', function() {
      const pdfBtn = this;
      pdfBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Generating...';
      pdfBtn.disabled = true;
      
      const url = window.routes.downloadPdf.replace(':id', EnrollmentState.currentStudentId) + `?term_id=${EnrollmentState.currentTermId}`;
      
      $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
          if (response && (response.url || (response.data && response.data.url))) {
            const pdfUrl = response.url || response.data.url;
            window.open(pdfUrl, '_blank');
            
            Swal.fire({
              icon: 'success',
              title: 'PDF Generated',
              text: 'Schedule PDF has been opened in a new tab.',
              timer: 2000,
              showConfirmButton: false
            }).then(() => {
              Swal.close();
              EnrollmentUIModule.resetForm();
            });
          } else {
            pdfBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Downloading...';
            EnrollmentSubmissionModule.downloadPdfDirect(url, pdfBtn);
          }
        },
        error: function(xhr) {
          if (xhr.status !== 404) {
            pdfBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Trying direct download...';
            EnrollmentSubmissionModule.downloadPdfDirect(url, pdfBtn);
          } else {
            let errorMessage = 'PDF generation route not found or not working.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            }
            Swal.fire('Error', errorMessage, 'error');
            pdfBtn.innerHTML = '<i class="bx bx-download me-1"></i>Download Schedule PDF';
            pdfBtn.disabled = false;
          }
        }
      });
    });
  },

  downloadPdfDirect(url, pdfBtn) {
    $.ajax({
      url: url,
      method: 'GET',
      xhrFields: {
        responseType: 'blob'
      },
      success: function(blob, status, xhr) {
        const contentType = xhr.getResponseHeader('content-type');
        if (contentType && contentType.includes('application/pdf')) {
          const blobUrl = window.URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = blobUrl;
          link.download = `student_schedule_${EnrollmentState.currentStudentId}_${EnrollmentState.currentTermId}.pdf`;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          window.URL.revokeObjectURL(blobUrl);
          
          Swal.fire({
            icon: 'success',
            title: 'PDF Downloaded',
            text: 'Schedule PDF has been downloaded successfully.',
            timer: 2000,
            showConfirmButton: false
          }).then(() => {
            Swal.close();
            EnrollmentUIModule.resetForm();
          });
        } else {
          Swal.fire('Error', 'Response is not a PDF document.', 'error');
        }
      },
      error: function(xhr) {
        let errorMessage = 'Failed to generate PDF document.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }
        Swal.fire('Error', errorMessage, 'error');
      },
      complete: function() {
        pdfBtn.innerHTML = '<i class="bx bx-download me-1"></i>Download Schedule PDF';
        pdfBtn.disabled = false;
      }
    });
  },

  setupContinueButton() {
    document.getElementById('continueBtn').addEventListener('click', function() {
      Swal.close();
      EnrollmentUIModule.resetForm();
    });
  },

  handleError(xhr) {
    let errorMessage = 'An error occurred during enrollment. Please try again.';
    let errorDetails = '';
    let showRetryButton = false;
    
    console.error('Enrollment submission error:', xhr);
    
    if (xhr.responseJSON) {
      errorMessage = xhr.responseJSON.message || errorMessage;
      
      if (xhr.status === 422) {
        // Validation errors
        const errors = xhr.responseJSON.errors;
        if (errors) {
          errorDetails = '<div class="alert alert-danger text-start mt-3"><ul class="mb-0">';
          Object.keys(errors).forEach(field => {
            const fieldName = this.formatFieldName(field);
            errors[field].forEach(error => {
              errorDetails += `<li><strong>${fieldName}:</strong> ${error}</li>`;
            });
          });
          errorDetails += '</ul></div>';
        }
      } else if (xhr.status === 400) {
        // Business validation errors
        errorDetails = `<div class="alert alert-warning text-start mt-3"><small>${errorMessage}</small></div>`;
      } else if (xhr.status === 500) {
        // Server errors
        errorMessage = 'Internal server error occurred. Please contact administrator.';
        errorDetails = '<div class="alert alert-danger text-start mt-3"><small>Please try again later or contact support if the problem persists.</small></div>';
        showRetryButton = true;
      }
    } else if (xhr.status === 0 || xhr.statusText === 'timeout') {
      // Network or timeout errors
      errorMessage = 'Connection failed or request timed out. Please check your internet connection.';
      errorDetails = '<div class="alert alert-warning text-start mt-3"><small>This might be a temporary network issue.</small></div>';
      showRetryButton = true;
    } else {
      // Other errors
      errorMessage = 'An unexpected error occurred. Please try again.';
      errorDetails = '<div class="alert alert-danger text-start mt-3"><small>Please try again later.</small></div>';
      showRetryButton = true;
    }
    
    const buttonConfig = showRetryButton && this.lastRequestData ? {
      showDenyButton: true,
      confirmButtonText: 'Retry',
      denyButtonText: 'Cancel',
      confirmButtonColor: '#007bff',
      denyButtonColor: '#dc3545'
    } : {
      confirmButtonText: 'Understand',
      confirmButtonColor: '#dc3545'
    };
    
    Swal.fire({
      icon: 'error',
      title: 'Enrollment Failed',
      html: `
        <div class="text-center">
          <p class="mb-2">${errorMessage}</p>
          ${errorDetails}
          <div class="mt-3">
            <small class="text-muted">Please review your selections and try again.</small>
          </div>
        </div>
      `,
      width: '600px',
      ...buttonConfig
    }).then((result) => {
      if (result.isConfirmed && showRetryButton && this.lastRequestData) {
        // Retry the request
        console.log('Retrying enrollment submission...');
        setTimeout(() => {
          this.retrySubmission();
        }, 1000);
      }
    });
  },

  retrySubmission() {
    if (!this.lastRequestData) {
      console.error('No data available for retry');
      return;
    }

    const enrollBtn = $('#enrollBtn');
    const originalText = enrollBtn.html();
    
    enrollBtn.html('<i class="bx bx-loader-alt bx-spin me-1"></i>Retrying...').prop('disabled', true);

    $.ajax({
      url: window.routes.storeEnrollment,
      method: 'POST',
      data: this.lastRequestData.formData,
      processData: false,
      contentType: false,
      timeout: 30000,
      success: (res) => {
        console.log('Retry successful:', res);
        this.lastRequestData = null;
        this.handleSuccess(res);
      },
      error: (xhr) => {
        console.error('Retry failed:', xhr);
        this.handleError(xhr);
      },
      complete: () => {
        enrollBtn.html(originalText).prop('disabled', false);
        $('.course-checkbox').prop('disabled', false);
        $('#term_id').prop('disabled', false);
        EnrollmentUIModule.updateEnrollButton();
      }
    });
  },

  formatFieldName(field) {
    const fieldMappings = {
      'student_id': 'Student',
      'term_id': 'Academic Term',
      'available_course_ids': 'Selected Courses',
      'available_course_schedule_ids': 'Course Schedules',
      'course_schedule_mapping': 'Schedule Mapping'
    };
    return fieldMappings[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
  }
};

// ========================================
// TERMS MODULE
// ========================================
const TermsModule = {
  load() {
    const url = window.user && window.user.hasRole && window.user.hasRole('admin') 
      ? window.routes.termsWithInactive 
      : window.routes.terms;
      
    $.ajax({
      url: url,
      method: 'GET',
      success: (response) => {
        let $termSelect = $('#term_id');
        $termSelect.empty().append('<option value="">Please select an academic term</option>');
        response.data.forEach((term) => {
          $termSelect.append(`<option value="${term.id}">${term.name}</option>`);
        });
      },
      error: () => {
        console.error('Failed to load terms');
      }
    });
  }
};

// ========================================
// MAIN APPLICATION MODULE
// ========================================
const EnrollmentApp = {
  init() {
    this.setupGlobalVariables();
    this.initializeComponents();
    this.attachEventHandlers();
    this.loadInitialData();
  },

  setupGlobalVariables() {
      window.routes = window.routes || {
        findStudent: '{{ route("enrollments.findStudent") }}',
        studentEnrollments: '{{ route("enrollments.studentEnrollments") }}',
        availableCourses: '{{ route("available_courses.all") }}',
        prerequisites: '{{ route("courses.prerequisites") }}',
        courseSchedules: '{{ route("available_courses.schedules", ":id") }}',
        remainingCreditHours: '{{ route("enrollments.remainingCreditHours") }}',
        getSchedules: '{{ route("enrollments.getSchedules") }}',
        storeEnrollment: '{{ route("enrollments.store") }}',
        downloadPdf: '{{ route("students.download.pdf", ":id") }}',
        terms: '{{ route("terms.all") }}',
        termsWithInactive: '{{ route("terms.all.with_inactive") }}'
      };
  },

  initializeComponents() {
    // Initialize Select2
    $('#term_id').select2({
      theme: 'bootstrap-5',
      placeholder: 'Please select an academic term',
      allowClear: true,
      width: '100%'
    });

    // Hide page loader
    Utils.hidePageLoader();
  },

  attachEventHandlers() {
    // Student search form
    $('#findStudentForm').on('submit', this.handleStudentSearch.bind(this));

    // Term selection
    $('#term_id').on('change', this.handleTermChange.bind(this));

    // Activity selection modal
    $('#confirmActivitySelection').on('click', () => {
      ActivitySelectionModule.confirmSelection();
    });

    // Handle modal close without selection
    $('#activitySelectionModal').on('hidden.bs.modal', this.handleActivityModalClose.bind(this));

    // Enrollment form submission
    $('#enrollForm').on('submit', this.handleEnrollmentSubmission.bind(this));

    // Search functionality
    $('#historySearch').on('input', (e) => {
      const searchTerm = $(e.target).val().toLowerCase();
      EnrollmentHistoryModule.filter(searchTerm);
    });

    $('#coursesSearch').on('input', (e) => {
      const searchTerm = $(e.target).val().toLowerCase();
      CourseModule.filter(searchTerm);
    });
  },

  loadInitialData() {
    TermsModule.load();
  },

  handleStudentSearch(e) {
    e.preventDefault();
    const identifier = $('#identifier').val();
    
    // Reset everything
    this.resetAllComponents();
    
    StudentModule.findStudent(identifier)
      .done((res) => {
        const student = res.data;
        $('#student_id').val(student.id);
        StudentModule.displayStudentInfo(student);
        
        EnrollmentState.currentStudentId = student.id;
        EnrollmentState.currentTermId = null;
        
        EnrollmentHistoryModule.load(EnrollmentState.currentStudentId);
      })
      .fail((xhr) => {
        $('#studentDetails').hide();
        Swal.fire({
          icon: 'error',
          title: 'Student Not Found',
          text: xhr.responseJSON?.message || 'Could not find student with the provided identifier',
          confirmButtonText: 'Try Again'
        });
      });
  },

  handleTermChange() {
    EnrollmentState.currentTermId = $('#term_id').val();
    EnrollmentState.resetCourseSelections();
    $('#weeklyScheduleCard').hide();
    $('#scheduleConflictAlert').hide();
    
    if (EnrollmentState.currentStudentId) {
      EnrollmentHistoryModule.load(EnrollmentState.currentStudentId);
      ScheduleModule.initialize(EnrollmentState.currentStudentId, EnrollmentState.currentTermId);
    }
    
    CourseModule.load(EnrollmentState.currentStudentId, EnrollmentState.currentTermId);
    
    const selectedText = $('#term_id option:selected').text();
    CreditHoursModule.updateInfoBoxByTermName(selectedText);
  },

  handleActivityModalClose() {
    const courseId = $('#activitySelectionModal').data('course-id');
    const checkbox = $(`#course_${courseId}`);
    
    if (!EnrollmentState.selectedCourseGroups.has(courseId)) {
      checkbox.prop('checked', false);
      $(`.course-item[data-course-id="${courseId}"]`).removeClass('selected');
      $(`#groupInfo_${courseId}`).hide();
      EnrollmentUIModule.updateEnrollButton();
      CreditHoursModule.updateSummary();
      ScheduleModule.update();
    }
  },

  handleEnrollmentSubmission(e) {
    e.preventDefault();
    EnrollmentSubmissionModule.submit();
  },

  resetAllComponents() {
    $('#term_id').val('').trigger('change');
    Utils.showEmptyState('#enrollmentHistoryBox', 'bx-search-alt-2', 'Find a student to view enrollment history');
    $('#historyCount').text('0');
    Utils.showEmptyState('#coursesBox', 'bx-book-bookmark', 'Select a term to view available courses');
    $('#coursesCount').text('0');
    $('#enrollBtn').hide();
    $('#creditHoursSummary').hide();
    $('#exceptionAlert').hide();
    $('#weeklyScheduleCard').hide();
    EnrollmentState.reset();
  }
};

// ========================================
// GLOBAL FUNCTIONS (for onclick handlers)
// ========================================
function showMissingPrerequisites(missingPrereqs) {
  PrerequisiteModule.showMissing(missingPrereqs);
}

// ========================================
// DOCUMENT READY
// ========================================
$(document).ready(function() {
  EnrollmentApp.init();
});
</script>
@endpush