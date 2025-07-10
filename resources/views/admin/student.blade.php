@extends('layouts.home')

@section('title', 'Admin Home | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Students</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-students-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-students">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-students-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base bx bx-group icon-lg"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Male Students</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-male-students-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-male-students">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-male-students-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-danger">
                <i class="icon-base bx bx-user-plus icon-lg"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Female Students</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-female-students-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-female-students">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-female-students-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="icon-base bx bx-user-check icon-lg"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Page Header and Actions -->
  <x-ui.page-header 
    title="Students"
    description="Manage all student records, add new students, or import in bulk using the options on the right."
    icon="bx bx-group"
  >
    <button class="btn btn-success me-2" id="importStudentsBtn" type="button" data-bs-toggle="modal" data-bs-target="#importStudentsModal">
      <i class="bx bx-upload me-1"></i> Import Students
    </button>
    <button class="btn btn-primary" id="addStudentBtn" type="button" data-bs-toggle="modal" data-bs-target="#studentModal">
      <i class="bx bx-plus me-1"></i> Add Student
    </button>
  </x-ui.page-header>

  <!-- Students DataTable -->
  <x-ui.datatable
    :headers="['ID', 'Name (EN)', 'Name (AR)', 'Academic ID', 'National ID', 'Academic Email', 'Level', 'CGPA', 'Gender', 'Program', 'Action']"
    :columns="[
        ['data' => 'id', 'name' => 'id'],
        ['data' => 'name_en', 'name' => 'name_en'],
        ['data' => 'name_ar', 'name' => 'name_ar'],
        ['data' => 'academic_id', 'name' => 'academic_id'],
        ['data' => 'national_id', 'name' => 'national_id'],
        ['data' => 'academic_email', 'name' => 'academic_email'],
        ['data' => 'level', 'name' => 'level'],
        ['data' => 'cgpa', 'name' => 'cgpa'],
        ['data' => 'gender', 'name' => 'gender'],
        ['data' => 'program', 'name' => 'program', 'orderable' => false, 'searchable' => false],
        ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
    ]"
    :ajax-url="route('admin.students.datatable')"
    table-id="students-table"
  />

  <!-- Add/Edit Student Modal -->
  <x-ui.modal 
    id="studentModal"
    title="Add/Edit Student"
    size="lg"
    :scrollable="true"
    class="student-modal"
  >
    <x-slot name="slot">
      <form id="studentForm">
        <input type="hidden" id="student_id" name="student_id">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="name_en" class="form-label">Name (EN)</label>
            <input type="text" class="form-control" id="name_en" name="name_en" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="name_ar" class="form-label">Name (AR)</label>
            <input type="text" class="form-control" id="name_ar" name="name_ar" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="academic_id" class="form-label">Academic ID</label>
            <input type="text" class="form-control" id="academic_id" name="academic_id" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="national_id" class="form-label">National ID</label>
            <input type="text" class="form-control" id="national_id" name="national_id" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="academic_email" class="form-label">Academic Email</label>
            <input type="email" class="form-control" id="academic_email" name="academic_email" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="level_id" class="form-label">Level</label>
            <select class="form-control" id="level_id" name="level_id" required>
              <option value="">Select Level</option>
              <!-- Options will be loaded via AJAX -->
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="cgpa" class="form-label">CGPA</label>
            <input type="number" step="0.01" class="form-control" id="cgpa" name="cgpa" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="gender" class="form-label">Gender</label>
            <select class="form-control" id="gender" name="gender" required>
              <option value="">Select Gender</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="program_id" class="form-label">Program</label>
            <select class="form-control" id="program_id" name="program_id" required>
              <option value="">Select Program</option>
              <!-- Options will be loaded via AJAX -->
            </select>
          </div>
        </div>
      </form>
    </x-slot>
    <x-slot name="footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
        Close
      </button>
      <button type="submit" class="btn btn-primary" id="saveStudentBtn" form="studentForm">Save</button>
    </x-slot>
  </x-ui.modal>

  <!-- Import Students Modal -->
  <x-ui.modal 
    id="importStudentsModal"
    title="Import Students"
    size="md"
    :scrollable="false"
    class="import-students-modal"
  >
    <x-slot name="slot">
      <form id="importStudentsForm" enctype="multipart/form-data">
        <div class="mb-3">
          <label for="students_file" class="form-label">Upload Excel File</label>
          <input type="file" class="form-control" id="students_file" name="students_file" accept=".xlsx,.xls" required>
        </div>
        <div class="alert alert-info d-flex align-items-center justify-content-between p-3 mb-3">
          <div>
            <i class="bx bx-info-circle me-2"></i>
            <span class="small">Use the template for correct student data formatting.</span>
          </div>
          <a href="{{ route('admin.students.template') }}" class="btn btn-sm btn-outline-primary" download>
            <i class="bx bx-download me-1"></i>Template
          </a>
        </div>
      </form>
    </x-slot>
    <x-slot name="footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
        Close
      </button>
      <button type="submit" class="btn btn-success" id="importStudentsSubmitBtn" form="importStudentsForm">Import</button>
    </x-slot>
  </x-ui.modal>

  {{-- Add the modal for downloading enrollment document by term --}}
  <x-ui.modal 
      id="downloadEnrollmentModal"
      title="Download Enrollment Document"
      size="md"
      :scrollable="false"
      class="download-enrollment-modal"
  >
      <x-slot name="slot">
          <form id="downloadEnrollmentForm">
              <input type="hidden" id="modal_student_id" name="student_id">
              <input type="hidden" id="download_type" name="download_type">
              <div class="mb-3">
                  <label for="term_id" class="form-label">Select Term <span class="text-danger">(Required)</span></label>
                  <select class="form-control" id="term_id" name="term_id" required>
                  </select>
                  <small class="form-text text-muted">You must select a term to download the enrollment document.</small>
              </div>
          </form>
      </x-slot>
      <x-slot name="footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              Close
          </button>
          <button type="button" class="btn btn-primary" id="downloadEnrollmentBtn">Download</button>
      </x-slot>
  </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
/**
 * Student Management System JavaScript
 * Handles CRUD operations, imports, and document downloads for students
 */

// ===========================
// UTILITY FUNCTIONS
// ===========================

/**
 * Shows success notification
 * @param {string} message - Success message to display
 */
function showSuccess(message) {
  Swal.fire('Success', message, 'success');
}

/**
 * Shows error notification
 * @param {string} message - Error message to display
 */
function showError(message) {
  Swal.fire('Error', message, 'error');
}

/**
 * Shows/hides loading spinners and content
 * @param {string} elementId - Base element ID
 * @param {boolean} isLoading - Whether to show loading state
 */
function toggleLoadingState(elementId, isLoading) {
  const $element = $(`#${elementId}`);
  const $spinner = $(`#${elementId}-spinner`);
  const $updated = $(`#${elementId}-updated`);
  
  if (isLoading) {
    $element.hide();
    $updated.hide();
    $spinner.show();
  } else {
    $element.show();
    $updated.show();
    $spinner.hide();
  }
}

// ===========================
// DROPDOWN POPULATION
// ===========================

/**
 * Loads all programs into the program select dropdown
 * @param {number|null} selectedId - The program ID to preselect (optional)
 * @returns {Promise} jQuery promise
 */
function loadPrograms(selectedId = null) {
  return $.ajax({
    url: '{{ route('admin.programs.index') }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      const $programSelect = $('#program_id');
      
      $programSelect.empty().append('<option value="">Select Program</option>');
      
      data.forEach(function (program) {
        $programSelect.append(
          $('<option>', { value: program.id, text: program.name })
        );
      });
      
      if (selectedId) {
        $programSelect.val(selectedId);
      }
    },
    error: function() {
      showError('Failed to load programs');
    }
  });
}

/**
 * Fetches all levels from the server
 * @returns {Promise} jQuery promise
 */
function fetchLevels() {
  return $.getJSON("{{ route('admin.levels.index') }}");
}

/**
 * Populates the level dropdown with available levels
 * @param {number|null} selectedLevelId - The level ID to preselect (optional)
 */
function populateLevelDropdown(selectedLevelId = null) {
  fetchLevels()
    .done(function(levels) {
      const $levelSelect = $('#level_id');
      $levelSelect.empty().append('<option value="">Select Level</option>');
      
      (levels.data || levels).forEach(function(item) {
        $levelSelect.append($('<option>', { value: item.id, text: item.name }));
      });
      
      if (selectedLevelId) {
        $levelSelect.val(selectedLevelId);
      }
    })
    .fail(function() {
      showError('Failed to load levels');
    });
}

/**
 * Loads terms into the term select dropdown
 * @param {number|null} selectedTermId - The term ID to preselect (optional)
 * @returns {Promise} jQuery promise
 */
function loadTerms(selectedTermId = null) {
  return $.getJSON("{{ route('admin.terms.index') }}")
    .done(function(response) {
      const $termSelect = $('#term_id');
      $termSelect.empty().append('<option value="">All Terms</option>');
      
      (response.data || []).forEach(function(term) {
        $termSelect.append('<option value="' + term.id + '">' + term.name + '</option>');
      });
      
      if (selectedTermId) {
        $termSelect.val(selectedTermId);
      }
    })
    .fail(function() {
      showError('Failed to load terms');
    });
}

// ===========================
// STATISTICS MANAGEMENT
// ===========================

/**
 * Loads student statistics and updates stat cards
 */
function loadStudentStats() {
  // Show loading state for all stats
  toggleLoadingState('stat-students', true);
  toggleLoadingState('stat-male-students', true);
  toggleLoadingState('stat-female-students', true);
  
  $.ajax({
    url: '{{ route('admin.students.stats') }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      
      // Update student statistics
      $('#stat-students').text(data.students.total ?? '--');
      $('#stat-students-updated').text(data.students.lastUpdateTime ?? '--');
      $('#stat-male-students').text(data.maleStudents.total ?? '--');
      $('#stat-male-students-updated').text(data.maleStudents.lastUpdateTime ?? '--');
      $('#stat-female-students').text(data.femaleStudents.total ?? '--');
      $('#stat-female-students-updated').text(data.femaleStudents.lastUpdateTime ?? '--');
      
      // Hide loading state
      toggleLoadingState('stat-students', false);
      toggleLoadingState('stat-male-students', false);
      toggleLoadingState('stat-female-students', false);
    },
    error: function() {
      // Show error state
      $('#stat-students, #stat-male-students, #stat-female-students').text('N/A');
      $('#stat-students-updated, #stat-male-students-updated, #stat-female-students-updated').text('N/A');
      
      toggleLoadingState('stat-students', false);
      toggleLoadingState('stat-male-students', false);
      toggleLoadingState('stat-female-students', false);
      
      showError('Failed to load student statistics');
    }
  });
}

// ===========================
// STUDENT CRUD OPERATIONS
// ===========================

/**
 * Handles the Add Student button click event
 */
function handleAddStudentBtn() {
  $('#addStudentBtn').on('click', function () {
    $('#studentForm')[0].reset();
    $('#student_id').val('');
    $('#studentModal .modal-title').text('Add Student');
    $('#saveStudentBtn').text('Save');
    
    // Load dropdown data
    loadPrograms();
    populateLevelDropdown();
    
    $('#studentModal').modal('show');
  });
}

/**
 * Handles the Add/Edit Student form submission
 */
function handleStudentFormSubmit() {
  $('#studentForm').on('submit', function (e) {
    e.preventDefault();
    
    const studentId = $('#student_id').val();
    const url = studentId
      ? '{{ url('admin/students') }}/' + studentId
      : '{{ route('admin.students.store') }}';
    const method = studentId ? 'PUT' : 'POST';
    const formData = $(this).serialize();
    
    // Disable submit button during request
    const $submitBtn = $('#saveStudentBtn');
    const originalText = $submitBtn.text();
    $submitBtn.prop('disabled', true).text('Saving...');
    
    $.ajax({
      url: url,
      method: method,
      data: formData,
      success: function () {
        $('#studentModal').modal('hide');
        $('#students-table').DataTable().ajax.reload(null, false);
        showSuccess('Student has been saved successfully.');
        loadStudentStats(); // Refresh stats
      },
      error: function (xhr) {
        const message = xhr.responseJSON?.message || 'An error occurred. Please check your input.';
        showError(message);
      },
      complete: function() {
        $submitBtn.prop('disabled', false).text(originalText);
      }
    });
  });
}

/**
 * Handles the Edit Student button click event (delegated)
 */
function handleEditStudentBtn() {
  $(document).on('click', '.editStudentBtn', function () {
    const studentId = $(this).data('id');
    
    $.ajax({
      url: '{{ url('admin/students') }}/' + studentId,
      method: 'GET',
      success: function (student) {
        // Populate form fields
        $('#student_id').val(student.id);
        $('#name_en').val(student.name_en);
        $('#name_ar').val(student.name_ar);
        $('#academic_id').val(student.academic_id);
        $('#national_id').val(student.national_id);
        $('#academic_email').val(student.academic_email);
        $('#cgpa').val(student.cgpa);
        $('#gender').val(student.gender);
        
        // Load dropdowns with preselected values
        loadPrograms(student.program_id);
        populateLevelDropdown(student.level_id);
        
        // Update modal
        $('#studentModal .modal-title').text('Edit Student');
        $('#saveStudentBtn').text('Update');
        $('#studentModal').modal('show');
      },
      error: function () {
        showError('Failed to fetch student data.');
      }
    });
  });
}

/**
 * Handles the Delete Student button click event (delegated)
 */
function handleDeleteStudentBtn() {
  $(document).on('click', '.deleteStudentBtn', function () {
    const studentId = $(this).data('id');
    
    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '{{ url('admin/students') }}/' + studentId,
          method: 'DELETE',
          success: function () {
            $('#students-table').DataTable().ajax.reload(null, false);
            showSuccess('Student has been deleted.');
            loadStudentStats(); // Refresh stats
          },
          error: function () {
            showError('Failed to delete student.');
          }
        });
      }
    });
  });
}

// ===========================
// IMPORT FUNCTIONALITY
// ===========================

/**
 * Handles the Import Students form submission via AJAX
 */
function handleImportStudentsForm() {
  $('#importStudentsForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const $submitBtn = $('#importStudentsSubmitBtn');
    
    $submitBtn.prop('disabled', true).text('Importing...');
    
    $.ajax({
      url: '{{ route('admin.students.import') }}',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        $('#importStudentsModal').modal('hide');
        $('#students-table').DataTable().ajax.reload(null, false);
        showSuccess(response.message);
        loadStudentStats(); // Refresh stats
      },
      error: function(xhr) {
        const message = xhr.responseJSON?.message || 'Import failed. Please check your file.';
        showError(message);
      },
      complete: function() {
        $submitBtn.prop('disabled', false).text('Import');
      }
    });
  });
}

// ===========================
// DOCUMENT DOWNLOAD FUNCTIONALITY
// ===========================

/**
 * Handles download enrollment button click (legacy)
 */
function handleDownloadEnrollmentBtn() {
  $(document).on('click', '.downloadEnrollmentBtn', function() {
    const studentId = $(this).data('id');
    setupDownloadModal(studentId, 'legacy', 'Download Enrollment Document');
  });
}

/**
 * Handles PDF download button click
 */
function handleDownloadPdfBtn() {
  $(document).on('click', '.downloadPdfBtn', function(e) {
    e.preventDefault();
    const studentId = $(this).data('id');
    setupDownloadModal(studentId, 'pdf', 'Download Enrollment as PDF');
  });
}

/**
 * Handles Word download button click
 */
function handleDownloadWordBtn() {
  $(document).on('click', '.downloadWordBtn', function(e) {
    e.preventDefault();
    const studentId = $(this).data('id');
    setupDownloadModal(studentId, 'word', 'Download Enrollment as Word');
  });
}

/**
 * Sets up the download modal with student ID and type
 * @param {number} studentId - The student ID
 * @param {string} downloadType - Type of download (legacy, pdf, word)
 * @param {string} modalTitle - Title for the modal
 */
function setupDownloadModal(studentId, downloadType, modalTitle) {
  $('#modal_student_id').val(studentId);
  $('#download_type').val(downloadType);
  
  loadTerms().done(function() {
    $('#downloadEnrollmentModal .modal-title').text(modalTitle);
    $('#downloadEnrollmentModal').modal('show');
  });
}

/**
 * Handles the actual download process
 */
function handleDownloadProcess() {
  $('#downloadEnrollmentBtn').on('click', function() {
    const studentId = $('#modal_student_id').val();
    const termId = $('#term_id').val();
    const downloadType = $('#download_type').val();

    // Validation
    if (!downloadType) {
      showError('Please select a download type.');
      return;
    }

    if (!termId) {
      showError('Please select a term.');
      return;
    }

    // Build URL based on download type
    let url;
    switch (downloadType) {
      case 'pdf':
        url = '{{ url('admin/students') }}/' + studentId + '/download/pdf';
        break;
      case 'word':
        url = '{{ url('admin/students') }}/' + studentId + '/download/word';
        break;
      default:
        url = '/enrollment/download/' + studentId;
    }

    // Add term parameter
    if (termId) {
      url += '?term_id=' + termId;
    }

    // Show loading
    Swal.fire({
      title: 'Generating Document...',
      text: 'Please wait while we prepare your document.',
      allowOutsideClick: false,
      showConfirmButton: false,
      willOpen: () => {
        Swal.showLoading();
      }
    });

    $.ajax({
      url: url,
      method: 'GET',
      dataType: 'json',
      success: function(response) {
        if (response.url) {
          window.open(response.url, '_blank');
          Swal.close();
          $('#downloadEnrollmentModal').modal('hide');
        } else {
          showError('Invalid response from server.');
        }
      },
      error: function(xhr) {
        Swal.close();
        const message = xhr.responseJSON?.message || 'Failed to generate document.';
        showError(message);
      }
    });
  });
}

// ===========================
// INITIALIZATION
// ===========================

/**
 * Initialize all event handlers and load initial data
 */
function initializeStudentManagement() {
  // Load initial data
  loadStudentStats();
  
  // Initialize CRUD handlers
  handleAddStudentBtn();
  handleStudentFormSubmit();
  handleEditStudentBtn();
  handleDeleteStudentBtn();
  
  // Initialize import functionality
  handleImportStudentsForm();
  
  // Initialize download functionality
  handleDownloadEnrollmentBtn();
  handleDownloadPdfBtn();
  handleDownloadWordBtn();
  handleDownloadProcess();
}

// ===========================
// DOCUMENT READY
// ===========================

$(document).ready(function () {
  initializeStudentManagement();
});
</script>
@endpush