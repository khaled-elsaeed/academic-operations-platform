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
  </div>
</div>

<!-- Group Selection Modal -->
<div class="modal fade" id="groupSelectionModal" tabindex="-1" aria-labelledby="groupSelectionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content shadow">
      <div class="modal-header bg-light">
        <h5 class="modal-title text-dark" id="groupSelectionModalLabel">
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
      <div class="modal-footer bg-light">
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
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content shadow">
      <div class="modal-header bg-light">
        <h5 class="modal-title text-dark" id="prerequisitesModalLabel">
          <i class="bx bx-link me-2"></i>
          Missing Prerequisites
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning">
          <i class="bx bx-error-circle me-2"></i>
          <span class="text-dark">The following prerequisites are required but not completed:</span>
        </div>
        <div id="missingPrerequisitesList">
          <!-- Missing prerequisites will be populated here -->
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Schedule Conflict Modal -->
<div class="modal fade" id="scheduleConflictModal" tabindex="-1" aria-labelledby="scheduleConflictModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="scheduleConflictModalLabel">
          <i class="bx bx-error-circle me-2"></i>
          Schedule Conflict Detected
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger">
          <div class="d-flex align-items-start">
            <i class="bx bx-error-circle me-2 text-danger mt-1" style="font-size: 1.5rem;"></i>
            <div>
              <strong class="text-dark">Time Conflict Warning!</strong>
              <p class="mb-0 text-dark">The selected schedule has conflicts with your current course selections. Please review the details below:</p>
            </div>
          </div>
        </div>
        
        <div id="conflictDetailsList">
          <!-- Conflict details will be populated here -->
        </div>
        
        <div class="alert alert-info mt-3">
          <div class="d-flex align-items-start">
            <i class="bx bx-info-circle me-2 text-info mt-1"></i>
            <div class="text-dark">
              <strong>What should you do?</strong>
              <ul class="mb-0 mt-2">
                <li>You can <strong>proceed anyway</strong> if this is intentional (e.g., makeup classes, special arrangements)</li>
                <li>You can <strong>select a different group</strong> for one of the conflicting courses</li>
                <li>You can <strong>unselect one of the conflicting courses</strong> entirely</li>
              </ul>
            </div>
          </div>
        </div>
        
        <!-- Conflict Resolution Table -->
        <div class="mt-4">
          <h6 class="text-dark mb-3">
            <i class="bx bx-calendar-week me-2"></i>
            Weekly Schedule Overview (Conflicting Time Slots)
          </h6>
          <div id="conflictScheduleTable" class="table-responsive">
            <!-- Conflict schedule table will be populated here -->
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bx bx-x me-1"></i>
          Select Different Group
        </button>
        <button type="button" class="btn btn-danger" id="proceedWithConflictBtn">
          <i class="bx bx-check me-1"></i>
          Proceed Anyway
        </button>
      </div>
    </div>
  </div>
</div>

<style>
/* Improved styling with better contrast and organization */
.card {
  border: 1px solid #dee2e6;
  transition: box-shadow 0.15s ease-in-out;
}

.card:hover {
  box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.15) !important;
}

.enrollment-history-container {
  max-height: 400px;
  overflow-y: auto;
  border: 2px solid #e3f2fd;
  border-radius: 0.5rem;
  padding: 1rem;
  background-color: #fafafa;
}

.courses-container {
  max-height: 500px;
  overflow-y: auto;
  border: 2px solid #e3f2fd;
  border-radius: 0.5rem;
  padding: 1rem;
  background-color: #fafafa;
}

.course-item {
  border: 2px solid #e9ecef;
  border-radius: 0.5rem;
  padding: 1rem;
  margin-bottom: 1rem;
  transition: all 0.3s ease;
  position: relative;
  background-color: white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.course-item:hover {
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  border-color: var(--bs-primary);
  transform: translateY(-2px);
}

.course-item:last-child {
  margin-bottom: 0;
}

.course-item.selected {
  border-color: var(--bs-success);
  background-color: rgba(25, 135, 84, 0.08);
  box-shadow: 0 4px 12px rgba(25, 135, 84, 0.2);
}

.course-item h6 {
  color: #2c3e50;
  font-weight: 600;
}

.course-item .text-muted {
  color: #6c757d !important;
  font-weight: 500;
}

.prerequisites-status {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 2px solid #f8f9fa;
}

.prerequisite-check {
  display: flex;
  align-items: center;
  margin-bottom: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
  background-color: #f8f9fa;
  border: 1px solid #e9ecef;
  font-weight: 500;
}

.prerequisite-check:last-child {
  margin-bottom: 0;
}

.prerequisite-check.fulfilled {
  background-color: rgba(25, 135, 84, 0.1);
  color: #198754;
  border-color: rgba(25, 135, 84, 0.3);
}

.prerequisite-check.missing {
  background-color: rgba(220, 53, 69, 0.1);
  color: #dc3545;
  border-color: rgba(220, 53, 69, 0.3);
  cursor: pointer;
}

.prerequisite-check.missing:hover {
  background-color: rgba(220, 53, 69, 0.2);
  border-color: rgba(220, 53, 69, 0.5);
}

.selected-group-info {
  margin-top: 0.75rem;
  padding: 0.75rem;
  background-color: rgba(13, 110, 253, 0.1);
  border-radius: 0.375rem;
  border-left: 4px solid var(--bs-primary);
}

.group-selection-item {
  border: 2px solid #e9ecef;
  border-radius: 0.5rem;
  padding: 1rem;
  margin-bottom: 0.75rem;
  cursor: pointer;
  transition: all 0.3s ease;
  background-color: white;
}

.group-selection-item:hover {
  border-color: var(--bs-primary);
  background-color: rgba(13, 110, 253, 0.05);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.group-selection-item.selected {
  border-color: var(--bs-success);
  background-color: rgba(25, 135, 84, 0.1);
  box-shadow: 0 4px 8px rgba(25, 135, 84, 0.2);
}

.history-item {
  border-left: 4px solid var(--bs-primary);
  padding-left: 1rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  background-color: white;
  border-radius: 0 0.375rem 0.375rem 0;
  padding-right: 1rem;
  padding-top: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.history-item:last-child {
  margin-bottom: 0;
  padding-bottom: 1rem;
}

.history-item h6 {
  color: #2c3e50;
  font-weight: 600;
}

.student-info-item {
  border-bottom: 2px solid #f8f9fa;
  padding-bottom: 0.75rem;
  margin-bottom: 1rem;
}

.student-info-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.student-info-item small {
  color: #6c757d;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.75rem;
}

.student-info-item h6 {
  color: #2c3e50;
  font-weight: 600;
  margin-top: 0.25rem;
}

.capacity-badge {
  font-size: 0.75rem;
  font-weight: 600;
}

.loading-spinner {
  width: 1.5rem;
  height: 1.5rem;
}

.credit-hours-item {
  padding: 1rem;
  border-radius: 0.5rem;
  background: rgba(255, 255, 255, 0.9);
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.credit-hours-item:hover {
  background: rgba(255, 255, 255, 1);
  transform: translateY(-3px);
  border-color: rgba(13, 110, 253, 0.2);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.credit-hours-item i {
  font-size: 2rem;
  display: block;
}

.credit-hours-label {
  font-size: 0.8rem;
  font-weight: 700;
  color: #495057;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.credit-hours-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: #2c3e50;
  line-height: 1;
}

/* Weekly Schedule Improvements */
.schedule-grid {
  display: grid;
  grid-template-columns: 120px repeat(7, 1fr);
  gap: 2px;
  background-color: #dee2e6;
  border-radius: 0.5rem;
  overflow: hidden;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.schedule-header,
.schedule-cell {
  background-color: white;
  padding: 0.75rem 0.5rem;
  text-align: center;
  min-height: 70px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  color: #2c3e50;
}

.schedule-header {
  background-color: #f8f9fa;
  font-weight: 700;
  color: #495057;
  border-bottom: 2px solid #dee2e6;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.8rem;
}

.schedule-cell.time-slot {
  background-color: #f1f3f4;
  font-weight: 600;
  color: #495057;
  border-right: 2px solid #dee2e6;
}

.schedule-cell.has-class {
  background: linear-gradient(135deg, rgba(13, 110, 253, 0.1), rgba(13, 110, 253, 0.2));
  border-left: 4px solid var(--bs-primary);
  cursor: pointer;
  transition: all 0.3s ease;
}

.schedule-cell.has-class:hover {
  background: linear-gradient(135deg, rgba(13, 110, 253, 0.2), rgba(13, 110, 253, 0.3));
  transform: scale(1.02);
  box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3);
}

.schedule-cell.schedule-conflict {
  background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), rgba(220, 53, 69, 0.3)) !important;
  border-left: 4px solid #dc3545 !important;
  animation: pulse-conflict 2s infinite;
}

.schedule-cell.schedule-conflict:hover {
  background: linear-gradient(135deg, rgba(220, 53, 69, 0.3), rgba(220, 53, 69, 0.4)) !important;
}

@keyframes pulse-conflict {
  0% { opacity: 1; }
  50% { opacity: 0.7; }
  100% { opacity: 1; }
}

.class-info {
  text-align: left;
  width: 100%;
}

.class-title {
  font-weight: 700;
  color: #0d6efd;
  margin-bottom: 0.25rem;
  font-size: 0.8rem;
  line-height: 1.2;
}

.class-details {
  color: #495057;
  font-size: 0.7rem;
  line-height: 1.3;
  font-weight: 500;
}

/* Form Improvements */
.form-control:focus,
.form-select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.btn {
  font-weight: 600;
  border-radius: 0.5rem;
  padding: 0.75rem 1.5rem;
  transition: all 0.3s ease;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.badge {
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* Table Improvements */
.table th {
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.8rem;
}

.table-primary {
  background-color: rgba(13, 110, 253, 0.1) !important;
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
  .schedule-grid {
    grid-template-columns: 80px repeat(7, 1fr);
    font-size: 0.7rem;
  }
  
  .schedule-header,
  .schedule-cell {
    padding: 0.5rem 0.25rem;
    min-height: 50px;
  }
  
  .class-title {
    font-size: 0.7rem;
  }
  
  .class-details {
    font-size: 0.6rem;
  }
  
  .credit-hours-item {
    padding: 0.75rem 0.5rem;
    margin-bottom: 1rem;
  }
  
  .credit-hours-value {
    font-size: 1.25rem;
  }
  
  /* Improve cards spacing on mobile */
  .card {
    margin-bottom: 1rem;
  }
  
  /* Better button sizing on mobile */
  .btn {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
  }
  
  /* Course item improvements */
  .course-item {
    padding: 0.75rem;
  }
  
  /* Modal improvements */
  .modal-dialog {
    margin: 0.5rem;
  }
  
  /* Input group improvements */
  .input-group-text {
    padding: 0.75rem;
  }
  
  /* Card header improvements */
  .card-header {
    padding: 0.75rem;
  }
  
  .card-header .d-flex {
    flex-direction: column;
    align-items: flex-start !important;
  }
  
  .card-header .d-flex .input-group {
    margin-top: 0.5rem;
    width: 100%;
  }
  
  /* Group selection improvements */
  .group-selection-item {
    padding: 0.75rem;
  }
}

/* Tablet responsiveness */
@media (min-width: 769px) and (max-width: 1024px) {
  .schedule-grid {
    grid-template-columns: 100px repeat(7, 1fr);
    font-size: 0.8rem;
  }
  
  .credit-hours-item {
    padding: 0.875rem;
  }
  
  .course-item {
    padding: 0.875rem;
  }
}

/* Small mobile devices */
@media (max-width: 576px) {
  .container-xxl {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }
  
  .card {
    border-radius: 0.375rem;
  }
  
  .btn {
    font-size: 0.8rem;
    padding: 0.625rem 0.875rem;
  }
  
  .credit-hours-label {
    font-size: 0.7rem;
  }
  
  .credit-hours-value {
    font-size: 1.1rem;
  }
  
  /* Stack card headers on very small screens */
  .card-header .d-flex.justify-content-between {
    flex-direction: column;
    align-items: flex-start !important;
  }
  
  .card-header .badge,
  .card-header .btn {
    margin-top: 0.5rem;
    align-self: flex-start;
  }
}

/* Accessibility Improvements */
.form-check-input:checked {
  background-color: #198754;
  border-color: #198754;
}

.form-check-input:focus {
  border-color: #86b7fe;
  outline: 0;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Dark text for better contrast */
.text-dark {
  color: #2c3e50 !important;
}


/* Enhanced alert styles */
.alert {
  border-radius: 0.5rem;
  border-width: 2px;
}

.alert-info {
  background-color: rgba(13, 202, 240, 0.1);
  border-color: rgba(13, 202, 240, 0.3);
}

.alert-warning {
  background-color: rgba(255, 193, 7, 0.1);
  border-color: rgba(255, 193, 7, 0.3);
}

.alert-danger {
  background-color: rgba(220, 53, 69, 0.1);
  border-color: rgba(220, 53, 69, 0.3);
}

/* Conflict Detection Styles */
.conflict-indicator {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 10;
  animation: pulse-warning 2s infinite;
}

.conflict-indicator i {
  font-size: 1.2rem;
  color: #dc3545;
  text-shadow: 0 0 4px rgba(220, 53, 69, 0.4);
}

@keyframes pulse-warning {
  0% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.1); opacity: 0.8; }
  100% { transform: scale(1); opacity: 1; }
}

.schedule-cell.has-conflict {
  background: linear-gradient(135deg, rgba(220, 53, 69, 0.15), rgba(220, 53, 69, 0.25)) !important;
  border: 2px solid #dc3545 !important;
  position: relative;
}

.schedule-cell.has-conflict:hover {
  background: linear-gradient(135deg, rgba(220, 53, 69, 0.25), rgba(220, 53, 69, 0.35)) !important;
  cursor: pointer;
}

.schedule-cell.has-conflict::before {
  content: "⚠️";
  position: absolute;
  top: 4px;
  right: 4px;
  font-size: 0.8rem;
  z-index: 2;
}

/* Group Selection Conflict Warning */
.group-selection-item.has-conflict {
  border-color: #dc3545 !important;
  background-color: rgba(220, 53, 69, 0.05);
}

.group-selection-item.has-conflict .card {
  border-color: #dc3545 !important;
}

.conflict-warning-badge {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  animation: pulse-conflict 2s infinite;
}

@keyframes pulse-conflict {
  0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
  70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
  100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}

/* Prerequisite Enhancement Styles */
.prerequisite-check.missing {
  background-color: rgba(220, 53, 69, 0.1);
  color: #dc3545;
  border-color: rgba(220, 53, 69, 0.3);
  cursor: pointer;
  transition: all 0.3s ease;
}

.prerequisite-check.missing:hover {
  background-color: rgba(220, 53, 69, 0.2);
  border-color: rgba(220, 53, 69, 0.5);
  transform: translateX(2px);
}

.course-item.disabled {
  opacity: 0.6;
  background-color: rgba(108, 117, 125, 0.1) !important;
  border-color: #6c757d !important;
}

.course-item.disabled .course-checkbox {
  cursor: not-allowed;
}

.course-item.disabled:hover {
  transform: none !important;
  box-shadow: none !important;
}

/* Schedule Time Display Improvements */
.schedule-time-display {
  font-family: 'Courier New', monospace;
  font-weight: 600;
  background: rgba(13, 110, 253, 0.1);
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  border-left: 3px solid #0d6efd;
}

.schedule-time-range {
  color: #0d6efd;
  font-weight: 700;
}

.schedule-location {
  font-size: 0.8rem;
  color: #6c757d;
  font-style: italic;
}

/* Conflict Details Table */
.conflict-table {
  background: white;
  border: 2px solid #dc3545;
  border-radius: 0.5rem;
  overflow: hidden;
}

.conflict-table th {
  background: linear-gradient(135deg, #dc3545, #c82333);
  color: white;
  font-weight: 700;
  text-align: center;
  padding: 0.75rem;
  border: none;
}

.conflict-table td {
  padding: 0.5rem;
  text-align: center;
  border: 1px solid #dee2e6;
  vertical-align: middle;
}

.conflict-table .conflict-slot {
  background: rgba(220, 53, 69, 0.1);
  font-weight: 600;
  color: #dc3545;
}

.conflict-table .normal-slot {
  background: rgba(13, 110, 253, 0.1);
  color: #0d6efd;
}

/* Modal Enhancements */
.modal-xl {
  max-width: 90%;
}

@media (min-width: 1200px) {
  .modal-xl {
    max-width: 1140px;
  }
}

.modal-body {
  max-height: 70vh;
  overflow-y: auto;
}

/* Improved Schedule Grid for Conflicts */
.conflict-schedule-grid {
  display: grid;
  grid-template-columns: 120px repeat(7, 1fr);
  gap: 1px;
  background-color: #dc3545;
  border-radius: 0.5rem;
  overflow: hidden;
  font-size: 0.8rem;
}

.conflict-schedule-cell {
  background-color: white;
  padding: 0.5rem 0.25rem;
  text-align: center;
  min-height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.conflict-schedule-header {
  background-color: #dc3545;
  color: white;
  font-weight: 700;
  font-size: 0.75rem;
}

.conflict-schedule-time {
  background-color: #f8f9fa;
  font-weight: 600;
  color: #495057;
  font-size: 0.7rem;
}

.conflict-schedule-occupied {
  background: linear-gradient(135deg, rgba(220, 53, 69, 0.8), rgba(220, 53, 69, 0.9));
  color: white;
  font-weight: 600;
  font-size: 0.7rem;
  line-height: 1.2;
}

.conflict-schedule-normal {
  background: linear-gradient(135deg, rgba(13, 110, 253, 0.2), rgba(13, 110, 253, 0.3));
  color: #0d6efd;
  font-weight: 500;
  font-size: 0.7rem;
  line-height: 1.2;
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
        <p class="text-dark mb-0">${message}</p>
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
          <i class="bx bx-search-alt-2 display-4 mb-3 text-primary"></i>
          <p class="text-dark">Find a student to view enrollment history</p>
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
      originalHistoryData = history;
      $('#historyCount').text(history.length);
      if (history.length === 0) {
        $('#enrollmentHistoryBox').html(`
          <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
            <div class="text-center text-muted">
              <i class="bx bx-info-circle display-4 mb-3 text-primary"></i>
              <p class="text-dark">No enrollment history found</p>
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
            <i class="bx bx-error-circle display-4 mb-3 text-danger"></i>
            <p class="text-dark">Could not load enrollment history</p>
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
          <i class="bx bx-book-bookmark display-4 mb-3 text-primary"></i>
          <p class="text-dark">Select a term to view available courses</p>
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
    success: function(res) {
      let courses = (res.courses || []);
      originalCoursesData = courses;
      $('#coursesCount').text(courses.length);
      if (courses.length === 0) {
        $('#coursesBox').html(`
          <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
            <div class="text-center text-muted">
              <i class="bx bx-info-circle display-4 mb-3 text-primary"></i>
              <p class="text-dark">No available courses found for this term</p>
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
            <i class="bx bx-error-circle display-4 mb-3 text-danger"></i>
            <p class="text-dark">Could not load available courses</p>
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
    success: function(res) {
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
    const capacityClass = course.remaining_capacity > 10 ? 'success' : 
                        course.remaining_capacity > 5 ? 'warning' : 'danger';
    
    // Get prerequisites for this course
    const coursePrereqs = prerequisites.filter(p => 
      p.required_for_course_id == course.available_course_id
    );
    
    const hasUnfulfilledPrereqs = coursePrereqs.some(p => !p.is_enrolled);
    const canEnroll = !hasUnfulfilledPrereqs;
    
    html += `
      <div class="course-item ${!canEnroll ? 'disabled' : ''}" data-course-id="${course.available_course_id}">
        <div class="form-check">
          <input class="form-check-input course-checkbox" type="checkbox" 
                 name="available_course_ids[]" value="${course.available_course_id}" 
                 data-credit-hours="${course.credit_hours}" 
                 id="course_${course.available_course_id}"
                 ${!canEnroll ? 'disabled title="Prerequisites required"' : ''}>
          <label class="form-check-label w-100" for="course_${course.available_course_id}">
            <div class="d-flex justify-content-between align-items-start">
              <div style="flex: 1;">
                <h6 class="mb-1 text-dark">${course.name}</h6>
                <p class="text-muted mb-0 small">
                  <i class="bx bx-book me-1"></i>
                  Course Code: <strong>${course.code || 'N/A'}</strong>
                  <span class="ms-2"><i class="bx bx-timer me-1"></i>Credit Hours: <strong class="text-primary">${course.credit_hours}</strong></span>
                </p>
                
                <!-- Prerequisites Status -->
                ${coursePrereqs.length > 0 ? `
                <div class="prerequisites-status">
                  <small class="text-dark fw-semibold mb-2 d-block">
                    <i class="bx bx-link me-1"></i>Prerequisites:
                  </small>
                  ${coursePrereqs.map(prereq => `
                    <div class="prerequisite-check ${prereq.is_enrolled ? 'fulfilled' : 'missing'}" 
                         ${!prereq.is_enrolled ? `onclick="showMissingPrerequisites([${JSON.stringify(prereq).replace(/"/g, '&quot;')}])"` : ''}
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
                ` : '<div class="mt-2"><small class="text-success"><i class="bx bx-check me-1"></i>No prerequisites required</small></div>'}
                
                <!-- Selected Group Info -->
                <div class="selected-group-info" id="groupInfo_${course.available_course_id}" style="display:none;">
                  <small class="text-primary fw-semibold">
                    <i class="bx bx-group me-1"></i>Selected Group: <span class="group-name"></span>
                  </small>
                  <div class="group-details mt-1"></div>
                </div>
              </div>
              <div class="text-end">
                <span class="badge bg-${capacityClass} capacity-badge mb-2">
                  <i class="bx bx-group me-1"></i>
                  ${course.remaining_capacity} spots left
                </span>
                ${!canEnroll ? '<div><span class="badge bg-danger text-white"><i class="bx bx-lock me-1"></i>Prerequisites Required</span></div>' : ''}
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
 * Checks for time conflicts between selected schedules
 */
function checkScheduleConflicts(newGroupData, currentCourseId) {
  const conflicts = [];
  
  // Get all currently selected schedules except the current course
  selectedCourseGroups.forEach((groupData, courseId) => {
    if (courseId != currentCourseId && groupData.group_activities) {
      groupData.group_activities.forEach(currentActivity => {
        // Check conflict with new group activities
        newGroupData.group_activities.forEach(newActivity => {
          if (hasTimeConflict(currentActivity, newActivity)) {
            conflicts.push({
              conflictingCourse: groupData.course.name,
              conflictingActivity: currentActivity,
              newActivity: newActivity
            });
          }
        });
      });
    }
  });
  
  return conflicts;
}

/**
 * Checks if two activities have time conflicts
 */
function hasTimeConflict(activity1, activity2) {
  // Check if same day
  if (!activity1.day_of_week || !activity2.day_of_week) {
    return false; // Skip if day not specified
  }
  
  if (activity1.day_of_week.toLowerCase() !== activity2.day_of_week.toLowerCase()) {
    return false; // Different days, no conflict
  }
  
  // Parse times for comparison
  const start1 = parseTime(activity1.start_time);
  const end1 = parseTime(activity1.end_time);
  const start2 = parseTime(activity2.start_time);
  const end2 = parseTime(activity2.end_time);
  
  // Check for overlap: (start1 < end2) && (start2 < end1)
  return (start1 < end2) && (start2 < end1);
}

/**
 * Parses time string to minutes for comparison
 */
function parseTime(timeStr) {
  if (!timeStr) return 0;
  
  const timeParts = timeStr.split(':');
  if (timeParts.length < 2) return 0;
  
  const hours = parseInt(timeParts[0]) || 0;
  const minutes = parseInt(timeParts[1]) || 0;
  
  return hours * 60 + minutes;
}

/**
 * Shows time conflict warning dialog with enhanced details
 */
function showTimeConflictWarning(conflicts, onConfirm, onCancel) {
  // Populate conflict details
  let conflictDetailsHtml = '';
  let conflictTableData = [];
  
  conflicts.forEach((conflict, index) => {
    conflictDetailsHtml += `
      <div class="alert alert-warning mb-3">
        <div class="d-flex align-items-start">
          <div class="conflict-indicator me-3">
            <i class="bx bx-error-circle"></i>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-2 text-dark">
              <i class="bx bx-book me-1"></i>
              Conflict #${index + 1}: ${conflict.conflictingCourse} vs New Selection
            </h6>
            <div class="row">
              <div class="col-md-6">
                <div class="card border-danger">
                  <div class="card-header bg-danger text-white">
                    <small><i class="bx bx-clock me-1"></i>Existing Course</small>
                  </div>
                  <div class="card-body">
                    <strong class="text-dark">${conflict.conflictingCourse}</strong><br>
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
    
    // Collect data for conflict table
    conflictTableData.push({
      day: conflict.conflictingActivity.day_of_week,
      time: `${conflict.conflictingActivity.start_time} - ${conflict.conflictingActivity.end_time}`,
      existing: conflict.conflictingCourse,
      new: 'New Selection',
      activity1: conflict.conflictingActivity.activity_type,
      activity2: conflict.newActivity.activity_type
    });
  });
  
  // Generate conflict table
  let conflictTableHtml = generateConflictScheduleTable(conflictTableData);
  
  // Populate modal content
  $('#conflictDetailsList').html(conflictDetailsHtml);
  $('#conflictScheduleTable').html(conflictTableHtml);
  
  // Set up modal event handlers
  $('#proceedWithConflictBtn').off('click').on('click', function() {
    $('#scheduleConflictModal').modal('hide');
    onConfirm();
  });
  
  $('#scheduleConflictModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
    // Only call onCancel if the proceed button wasn't clicked
    if (!$(this).data('proceeded')) {
      onCancel();
    }
  });
  
  // Track when proceed button is clicked
  $('#proceedWithConflictBtn').on('click', function() {
    $('#scheduleConflictModal').data('proceeded', true);
  });
  
  // Reset the proceeded flag when modal is shown
  $('#scheduleConflictModal').on('show.bs.modal', function() {
    $(this).data('proceeded', false);
  });
  
  // Show the modal
  $('#scheduleConflictModal').modal('show');
}

/**
 * Generates a detailed conflict schedule table
 */
function generateConflictScheduleTable(conflictData) {
  if (conflictData.length === 0) return '';
  
  const days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
  const timeSlots = [
    '9:00 – 9:50', '9:50 – 10:40', '10:40 – 11:30', '11:30 – 12:20', 
    '12:20 – 1:10', '1:10 – 2:00', '2:00 – 2:50', '2:50 – 3:40'
  ];
  
  let html = `
    <table class="table conflict-table">
      <thead>
        <tr>
          <th>Time</th>
          ${days.map(day => `<th>${day}</th>`).join('')}
        </tr>
      </thead>
      <tbody>
  `;
  
  timeSlots.forEach(timeSlot => {
    html += `<tr><td class="conflict-schedule-time">${timeSlot}</td>`;
    
    days.forEach(day => {
      // Check if there's a conflict in this time slot and day
      const conflict = conflictData.find(c => 
        c.day.toLowerCase() === day.toLowerCase() && 
        isTimeInTimeSlot(timeSlot, c.time)
      );
      
      if (conflict) {
        html += `
          <td class="conflict-slot">
            <div style="font-size: 0.7rem; line-height: 1.2;">
              <strong>⚠️ CONFLICT</strong><br>
              <span style="color: #dc3545;">${conflict.existing}</span><br>
              <span style="color: #856404;">+ New Course</span>
            </div>
          </td>
        `;
      } else {
        html += `<td class="normal-slot">-</td>`;
      }
    });
    
    html += '</tr>';
  });
  
  html += `
      </tbody>
    </table>
  `;
  
  return html;
}

/**
 * Checks if a time range falls within a time slot
 */
function isTimeInTimeSlot(timeSlot, timeRange) {
  const slotParts = timeSlot.split('–').map(t => t.trim());
  const rangeParts = timeRange.split('-').map(t => t.trim());
  
  if (slotParts.length < 2 || rangeParts.length < 2) return false;
  
  const slotStart = parseTime(slotParts[0]);
  const slotEnd = parseTime(slotParts[1]);
  const rangeStart = parseTime(rangeParts[0]);
  const rangeEnd = parseTime(rangeParts[1]);
  
  // Check for overlap
  return (rangeStart < slotEnd) && (slotStart < rangeEnd);
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
      <h6 class="mb-1 text-dark">${course.name}</h6>
      <p class="mb-0 small text-dark">
        <i class="bx bx-book me-1"></i>Course Code: <strong>${course.code || 'N/A'}</strong> | 
        <i class="bx bx-timer me-1"></i>Credit Hours: <strong>${course.credit_hours}</strong>
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
    success: function(res) {
      const groups = res.data || [];
      // Cache the groups data in the modal
      $('#groupSelectionModal').data('groups', groups);
      displayCourseGroups(groups);
    },
    error: function() {
      $('#groupsList').html(`
        <div class="alert alert-danger">
          <i class="bx bx-error-circle me-2"></i>
          <span class="text-dark">Failed to load course groups. Please try again.</span>
        </div>
      `);
    }
  });
}

/**
 * Displays course groups for selection
 */
function displayCourseGroups(groups) {
  if (groups.length === 0) {
    $('#groupsList').html(`
      <div class="alert alert-warning">
        <i class="bx bx-info-circle me-2"></i>
        <span class="text-dark">No groups available for this course.</span>
      </div>
    `);
    return;
  }
  
  let html = '';
  groups.forEach(function(group) {
    if (!group.activities || group.activities.length === 0) return;
    
    // Calculate schedule summary for this group
    const scheduleSummary = calculateGroupScheduleSummary(group.activities);
    
    // Create a group selection card
    html += `
      <div class="group-selection-item mb-3 border rounded p-3" data-group-number="${group.group}">
        <div class="form-check">
          <input class="form-check-input group-radio" type="radio" 
                 name="selected_group" value="${group.group}" 
                 id="group_${group.group}">
          <label class="form-check-label w-100" for="group_${group.group}">
            <div class="mb-2">
              <h5 class="mb-1 text-primary">
                <i class="bx bx-group me-2"></i>Group ${group.group}
              </h5>
              
              <!-- Schedule Summary -->
              <div class="schedule-summary mb-2">
                <div class="d-flex align-items-center mb-1">
                  <i class="bx bx-time me-2 text-muted"></i>
                  <span class="schedule-time-display">
                    <span class="schedule-time-range">${scheduleSummary.timeRange}</span>
                  </span>
                </div>
                <div class="d-flex align-items-center">
                  <i class="bx bx-calendar me-2 text-muted"></i>
                  <span class="small text-muted">${scheduleSummary.daysText}</span>
                </div>
              </div>
            </div>
            
            <div class="row">
    `;
    
    // Display each activity type in the group
    group.activities.forEach(function(activity) {
      const activityIcon = activity.activity_type === 'lecture' ? 'bx-book-open' : 
                          activity.activity_type === 'lab' ? 'bx-flask' : 
                          activity.activity_type === 'tutorial' ? 'bx-edit' : 'bx-chalkboard';
      
      html += `
        <div class="col-12 col-md-6 mb-2">
          <div class="card border-light">
            <div class="card-body p-2">
              <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start">
                <div class="flex-grow-1 mb-2 mb-sm-0">
                  <h6 class="mb-1 text-dark">
                    <i class="bx ${activityIcon} me-1"></i>
                    ${activity.activity_type.charAt(0).toUpperCase() + activity.activity_type.slice(1)}
                  </h6>
                  <p class="text-muted mb-1 small">
                    <i class="bx bx-time me-1"></i>
                    <strong>${formatTimeRange(activity.start_time, activity.end_time)}</strong>
                  </p>
                  <p class="text-muted mb-1 small">
                    <i class="bx bx-calendar me-1"></i>
                    <strong>${activity.day_of_week || 'Schedule TBA'}</strong>
                  </p>
                  ${activity.location ? `
                    <p class="text-muted mb-0 small schedule-location">
                      <i class="bx bx-map me-1"></i>
                      ${activity.location}
                    </p>
                  ` : ''}
                </div>
                <div class="text-end">
                  <span class="badge bg-info text-white small">
                    <i class="bx bx-users me-1"></i>
                    ${activity.enrolled_count || 0}/${activity.max_capacity}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    });
    
    html += `
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
 * Calculates schedule summary for a group
 */
function calculateGroupScheduleSummary(activities) {
  if (!activities || activities.length === 0) {
    return { timeRange: 'TBA', daysText: 'TBA' };
  }
  
  // Parse all times and find range
  let earliestTime = null;
  let latestTime = null;
  const days = new Set();
  
  activities.forEach(activity => {
    if (activity.start_time && activity.end_time) {
      const startMinutes = parseTime(activity.start_time);
      const endMinutes = parseTime(activity.end_time);
      
      if (earliestTime === null || startMinutes < earliestTime) {
        earliestTime = startMinutes;
      }
      if (latestTime === null || endMinutes > latestTime) {
        latestTime = endMinutes;
      }
    }
    
    if (activity.day_of_week) {
      days.add(activity.day_of_week);
    }
  });
  
  // Format time range
  let timeRange = 'TBA';
  if (earliestTime !== null && latestTime !== null) {
    const startTime = formatMinutesToTime(earliestTime);
    const endTime = formatMinutesToTime(latestTime);
    timeRange = `${startTime} - ${endTime}`;
  }
  
  // Format days
  const daysArray = Array.from(days).sort((a, b) => {
    const dayOrder = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    return dayOrder.indexOf(a.toLowerCase()) - dayOrder.indexOf(b.toLowerCase());
  });
  
  let daysText = daysArray.length > 0 ? daysArray.join(', ') : 'TBA';
  if (daysArray.length > 3) {
    daysText = `${daysArray.slice(0, 2).join(', ')} + ${daysArray.length - 2} more`;
  }
  
  return { timeRange, daysText };
}

/**
 * Formats a time range for better display
 */
function formatTimeRange(startTime, endTime) {
  if (!startTime || !endTime) return 'TBA';
  
  // Ensure consistent format
  const formattedStart = formatTime(startTime);
  const formattedEnd = formatTime(endTime);
  
  return `${formattedStart} - ${formattedEnd}`;
}

/**
 * Formats time string to consistent format
 */
function formatTime(timeStr) {
  if (!timeStr) return 'TBA';
  
  // Handle various time formats
  let cleanTime = timeStr.trim();
  
  // If already in HH:MM format, return as is
  if (/^\d{1,2}:\d{2}$/.test(cleanTime)) {
    return cleanTime;
  }
  
  // Handle 24-hour format with seconds
  if (/^\d{1,2}:\d{2}:\d{2}$/.test(cleanTime)) {
    return cleanTime.substring(0, 5); // Remove seconds
  }
  
  return cleanTime;
}

/**
 * Converts minutes to HH:MM format
 */
function formatMinutesToTime(minutes) {
  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;
  return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
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

/**
 * Updates the weekly schedule visualization
 */
function updateWeeklySchedule() {
  const selectedActivities = [];
  $('.course-checkbox:checked').each(function() {
    const courseId = $(this).val();
    const groupData = selectedCourseGroups.get(courseId);
    if (groupData && groupData.group_activities) {
      // Add each activity from the selected group
      groupData.group_activities.forEach(activity => {
        selectedActivities.push({
          course: groupData.course,
          activity: activity,
          group: groupData.group_number
        });
      });
    }
  });
  
  if (selectedActivities.length === 0) {
    $('#weeklyScheduleCard').hide();
    $('#scheduleConflictAlert').hide();
    return;
  }
  
  // Show the schedule card
  $('#weeklyScheduleCard').show();
  
  // Generate schedule grid
  generateScheduleGrid(selectedActivities);
  
  // Highlight any conflicts in the schedule
  highlightScheduleConflicts(selectedActivities);
}

/**
 * Generates the weekly schedule grid with improved day mapping
 */
function generateScheduleGrid(selectedActivities) {
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
    // Time column
    html += `<div class="schedule-cell time-slot">${timeSlot}</div>`;
    
    // Day columns
    for (let dayIndex = 1; dayIndex < days.length; dayIndex++) {
      const dayName = days[dayIndex].toLowerCase();
      // Find all activities that occur in this time slot and day
      const activitiesInSlot = selectedActivities.filter(item => {
        if (!item.activity || !item.activity.day_of_week) return false;
        
        const scheduleDayOfWeek = item.activity.day_of_week.toLowerCase();
        const dayMatches = scheduleDayOfWeek === dayName;
        
        return dayMatches && isTimeInRange(timeSlot, item.activity.start_time, item.activity.end_time);
      });
      
      if (activitiesInSlot.length > 0) {
        // Handle multiple activities in the same slot (conflicts)
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
                <span class="schedule-time-range">${formatTimeRange(activity.start_time, activity.end_time)}</span>
                ${activity.location ? '<br><span class="schedule-location">' + activity.location + '</span>' : ''}
              </div>
            </div>
          `;
        });
        
        const tooltipContent = activitiesInSlot.map(item => 
          `${item.course.name} - Group ${item.group} (${item.activity.activity_type}) ${formatTimeRange(item.activity.start_time, item.activity.end_time)}${item.activity.location ? ' @ ' + item.activity.location : ''}`
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
  
  // Initialize tooltips for better hover information
  $('[data-bs-toggle="tooltip"]').tooltip({
    html: true,
    container: 'body'
  });
  
  // Add click handlers for schedule cells
  $('.schedule-cell.has-class').on('click', function() {
    const tooltip = $(this).attr('title');
    if (tooltip) {
      showScheduleCellDetails(tooltip, $(this).hasClass('has-conflict'));
    }
  });
}

/**
 * Shows detailed information about a schedule cell
 */
function showScheduleCellDetails(tooltipContent, isConflict) {
  const activities = tooltipContent.split(' | ');
  let detailsHtml = '';
  
  activities.forEach((activity, index) => {
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
}

/**
 * Enhanced time slot checking with better accuracy
 */
function isTimeInRange(timeSlot, startTime, endTime) {
  if (!startTime || !endTime) return false;
  
  // Extract start and end times from slot range (e.g., "9:00 – 9:50")
  const slotParts = timeSlot.split(/[–-]/).map(t => t.trim());
  if (slotParts.length < 2) return false;
  
  const slotStart = parseTime(slotParts[0]);
  const slotEnd = parseTime(slotParts[1]);
  const activityStart = parseTime(startTime);
  const activityEnd = parseTime(endTime);
  
  // Check if activity overlaps with this time slot
  // An activity overlaps if: activity_start < slot_end AND activity_end > slot_start
  return (activityStart < slotEnd) && (activityEnd > slotStart);
}

/**
 * Highlights schedule conflicts in the weekly view
 */
function highlightScheduleConflicts(selectedActivities) {
  // Reset previous conflict styling
  $('.schedule-cell').removeClass('schedule-conflict has-conflict');
  $('#scheduleConflictAlert').hide();
  
  // Find conflicts between activities
  const conflicts = [];
  const conflictedCells = new Set();
  
  for (let i = 0; i < selectedActivities.length; i++) {
    for (let j = i + 1; j < selectedActivities.length; j++) {
      if (hasTimeConflict(selectedActivities[i].activity, selectedActivities[j].activity)) {
        conflicts.push({
          activity1: selectedActivities[i],
          activity2: selectedActivities[j]
        });
        
        // Mark both activities as conflicted
        conflictedCells.add(`${selectedActivities[i].activity.day_of_week}-${selectedActivities[i].activity.start_time}-${selectedActivities[i].activity.end_time}`);
        conflictedCells.add(`${selectedActivities[j].activity.day_of_week}-${selectedActivities[j].activity.start_time}-${selectedActivities[j].activity.end_time}`);
      }
    }
  }
  
  // If conflicts found, highlight them and show alert
  if (conflicts.length > 0) {
    $('#scheduleConflictAlert').show();
    
    // Add click handler to conflict alert to show detailed modal
    $('#scheduleConflictAlert').off('click').on('click', function() {
      showDetailedConflictModal(conflicts);
    }).css('cursor', 'pointer');
    
    // Highlight all conflicted cells
    $('.schedule-cell.has-class').each(function() {
      const cellTitle = $(this).attr('title') || '';
      const cellContent = $(this).text();
      
      // Check if this cell represents a conflicted activity
      conflicts.forEach(conflict => {
        const activities = [conflict.activity1.activity, conflict.activity2.activity];
        activities.forEach(activity => {
          if (cellTitle.includes(activity.activity_type) && 
              cellTitle.includes(activity.start_time) && 
              cellTitle.includes(activity.end_time)) {
            
            $(this).addClass('schedule-conflict has-conflict');
            
            // Add conflict indicator if not already present
            if (!$(this).find('.conflict-indicator').length) {
              $(this).append(`
                <div class="conflict-indicator" title="Click for conflict details">
                  <i class="bx bx-error-circle"></i>
                </div>
              `);
            }
            
            // Add click handler to show conflict details
            $(this).off('click.conflict').on('click.conflict', function(e) {
              e.stopPropagation();
              showDetailedConflictModal(conflicts);
            });
          }
        });
      });
    });
    
    // Update the main conflict alert text
    const conflictCount = conflicts.length;
    const cellCount = conflictedCells.size;
    $('#scheduleConflictAlert').html(`
      <div class="d-flex align-items-center">
        <i class="bx bx-error-circle me-2 text-warning"></i>
        <div class="flex-grow-1">
          <strong class="text-dark">Schedule Conflicts Detected!</strong>
          <p class="mb-0 small text-dark">
            Found ${conflictCount} conflict${conflictCount !== 1 ? 's' : ''} affecting ${cellCount} time slot${cellCount !== 1 ? 's' : ''}. 
            <strong class="text-primary">Click here or on any ⚠️ icon for details.</strong>
          </p>
        </div>
        <div class="ms-3">
          <button class="btn btn-sm btn-outline-danger" onclick="showDetailedConflictModal(getScheduleConflicts())">
            <i class="bx bx-info-circle me-1"></i>
            View Details
          </button>
        </div>
      </div>
    `);
  } else {
    // Remove all conflict indicators when no conflicts
    $('.conflict-indicator').remove();
    $('.schedule-cell').off('click.conflict');
  }
}

/**
 * Shows detailed conflict modal with comprehensive information
 */
function showDetailedConflictModal(conflicts) {
  if (!conflicts || conflicts.length === 0) return;
  
  // Transform conflicts to the format expected by showTimeConflictWarning
  const formattedConflicts = conflicts.map(conflict => ({
    conflictingCourse: conflict.activity1.course.name,
    conflictingActivity: conflict.activity1.activity,
    newActivity: conflict.activity2.activity
  }));
  
  // Show the enhanced conflict modal
  showTimeConflictWarning(formattedConflicts, 
    function() {
      // On proceed - just close modal, conflicts are accepted
      console.log('User chose to proceed with conflicts');
    },
    function() {
      // On cancel - could show guidance to resolve conflicts
      console.log('User chose to resolve conflicts');
    }
  );
}

/**
 * Gets current schedule conflicts for global access
 */
function getScheduleConflicts() {
  const selectedActivities = [];
  $('.course-checkbox:checked').each(function() {
    const courseId = $(this).val();
    const groupData = selectedCourseGroups.get(courseId);
    if (groupData && groupData.group_activities) {
      groupData.group_activities.forEach(activity => {
        selectedActivities.push({
          course: groupData.course,
          activity: activity,
          group: groupData.group_number
        });
      });
    }
  });
  
  const conflicts = [];
  for (let i = 0; i < selectedActivities.length; i++) {
    for (let j = i + 1; j < selectedActivities.length; j++) {
      if (hasTimeConflict(selectedActivities[i].activity, selectedActivities[j].activity)) {
        conflicts.push({
          activity1: selectedActivities[i],
          activity2: selectedActivities[j]
        });
      }
    }
  }
  
  return conflicts;
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
    `).prop('disabled', false).removeClass('btn-secondary').addClass('btn-success');
  } else {
    enrollBtn.html('<i class="bx bx-plus me-1"></i>Enroll Selected Courses')
           .prop('disabled', true).removeClass('btn-success').addClass('btn-secondary');
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
    success: function(res) {
      if (res.success && res.data) {
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
  
  progressBar.removeClass('bg-success bg-warning bg-danger');
  if (percentage < 70) {
    progressBar.addClass('bg-success');
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
          <i class="bx bx-search-alt display-4 mb-3 text-primary"></i>
          <p class="text-dark">No enrollments found matching your search</p>
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
              <h6 class="mb-1 text-dark">${enr.course ? enr.course.name : 'Course #' + enr.course_id}</h6>
              <p class="text-muted mb-1">
                <i class="bx bx-calendar me-1"></i>
                <strong>${enr.term ? enr.term.name : 'Term #' + enr.term_id}</strong>
              </p>
              ${enr.grade ? `<div class="mt-1"><span class="badge bg-primary text-white"><i class="bx bx-star me-1"></i>Grade: <strong>${enr.grade}</strong></span></div>` : '<div class="mt-1"><span class="badge bg-secondary text-white"><i class="bx bx-time me-1"></i>No Grade Yet</span></div>'}
            </div>
            <span class="badge bg-success text-white">Enrolled</span>
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
          <i class="bx bx-search-alt display-4 mb-3 text-primary"></i>
          <p class="text-dark">No courses found matching your search</p>
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
          <i class="bx bx-search-alt-2 display-4 mb-3 text-primary"></i>
          <p class="text-dark">Find a student to view enrollment history</p>
        </div>
      </div>
    `);
    $('#historyCount').text('0');
    $('#coursesBox').html(`
      <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
        <div class="text-center text-muted">
          <i class="bx bx-book-bookmark display-4 mb-3 text-primary"></i>
          <p class="text-dark">Select a term to view available courses</p>
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
      success: function(res) {
        let s = res.data;
        $('#student_id').val(s.id);
        
        let studentInfoHtml = `
          <div class="col-12 col-md-4 mb-3 mb-md-0">
            <div class="student-info-item">
              <small class="text-muted">Full Name (English)</small>
              <h6 class="mb-0 text-dark">${s.name_en}</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">Full Name (Arabic)</small>
              <h6 class="mb-0 text-dark">${s.name_ar}</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">Academic Email</small>
              <h6 class="mb-0 text-dark">${s.academic_email}</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">Program</small>
              <h6 class="mb-0 text-dark">${s.program ? s.program.name : 'Not Assigned'}</h6>
            </div>
          </div>
          <div class="col-12 col-md-4 mb-3 mb-md-0">
            <div class="student-info-item">
              <small class="text-muted">Academic ID</small>
              <h6 class="mb-0 text-dark">${s.academic_id}</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">National ID</small>
              <h6 class="mb-0 text-dark">${s.national_id}</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">Level</small>
              <h6 class="mb-0 text-dark">Level ${s.level.name}</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">CGPA</small>
              <h6 class="mb-0 text-dark">${s.cgpa || 'N/A'}</h6>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="student-info-item">
              <small class="text-muted">Total Units Taken</small>
              <h6 class="mb-0 text-dark">${s.taken_hours || 0} Units</h6>
            </div>
            <div class="student-info-item">
              <small class="text-muted">Remaining Hours</small>
              <h6 class="mb-0 text-dark">Unknown</h6>
            </div>
          </div>
        `