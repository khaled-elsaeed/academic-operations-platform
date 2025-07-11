@extends('layouts.home')

@section('title', 'Program Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Programs</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-programs-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-programs">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-programs-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base bx bx-book-open icon-lg"></i>
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
              <span class="text-heading">Programs with Students</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-with-students-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-with-students">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-with-students-updated">--</span></small>
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
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Programs without Students</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-without-students-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-without-students">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-without-students-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="icon-base bx bx-user-x icon-lg"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Page Header and Actions -->
  <x-ui.page-header 
    title="Programs"
    description="Manage all program records and add new programs using the options on the right."
    icon="bx bx-book-open"
  >
    <button class="btn btn-primary" id="addProgramBtn" type="button" data-bs-toggle="modal" data-bs-target="#programModal">
      <i class="bx bx-plus me-1"></i> Add Program
    </button>
  </x-ui.page-header>

  <!-- Programs DataTable -->
  <x-ui.datatable
    :headers="['ID', 'Name', 'Code', 'Faculty', 'Students Count', 'Action']"
    :columns="[
        ['data' => 'id', 'name' => 'id'],
        ['data' => 'name', 'name' => 'name'],
        ['data' => 'code', 'name' => 'code'],
        ['data' => 'faculty_name', 'name' => 'faculty_name'],
        ['data' => 'students_count', 'name' => 'students_count'],
        ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
    ]"
    :ajax-url="route('admin.programs.datatable')"
    table-id="programs-table"
  />

  <!-- Add/Edit Program Modal -->
  <x-ui.modal 
    id="programModal"
    title="Add/Edit Program"
    size="lg"
    :scrollable="false"
    class="program-modal"
  >
    <x-slot name="slot">
      <form id="programForm">
        <input type="hidden" id="program_id" name="program_id">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="name" class="form-label">Program Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="code" class="form-label">Program Code</label>
            <input type="text" class="form-control" id="code" name="code" required>
          </div>
          <div class="col-md-12 mb-3">
            <label for="faculty_id" class="form-label">Faculty</label>
            <select class="form-control" id="faculty_id" name="faculty_id" required>
              <option value="">Select Faculty</option>
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
      <button type="submit" class="btn btn-primary" id="saveProgramBtn" form="programForm">Save</button>
    </x-slot>
  </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
/**
 * Program Management System JavaScript
 * Handles CRUD operations for programs
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
 * Loads all faculties into the faculty select dropdown
 * @param {number|null} selectedId - The faculty ID to preselect (optional)
 * @returns {Promise} jQuery promise
 */
function loadFaculties(selectedId = null) {
  return $.ajax({
    url: '{{ route('admin.programs.faculties') }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      const $facultySelect = $('#faculty_id');
      
      $facultySelect.empty().append('<option value="">Select Faculty</option>');
      
      data.forEach(function (faculty) {
        $facultySelect.append(
          $('<option>', { value: faculty.id, text: faculty.name })
        );
      });
      
      if (selectedId) {
        $facultySelect.val(selectedId);
      }
    },
    error: function() {
      showError('Failed to load faculties');
    }
  });
}

// ===========================
// STATISTICS MANAGEMENT
// ===========================

/**
 * Loads program statistics and updates stat cards
 */
function loadProgramStats() {
  // Show loading state for all stats
  toggleLoadingState('stat-programs', true);
  toggleLoadingState('stat-with-students', true);
  toggleLoadingState('stat-without-students', true);
  
  $.ajax({
    url: '{{ route('admin.programs.stats') }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      
      // Update program statistics
      $('#stat-programs').text(data.total.total ?? '--');
      $('#stat-programs-updated').text(data.total.lastUpdateTime ?? '--');
      $('#stat-with-students').text(data.withStudents.total ?? '--');
      $('#stat-with-students-updated').text(data.withStudents.lastUpdateTime ?? '--');
      $('#stat-without-students').text(data.withoutStudents.total ?? '--');
      $('#stat-without-students-updated').text(data.withoutStudents.lastUpdateTime ?? '--');
      
      // Hide loading state
      toggleLoadingState('stat-programs', false);
      toggleLoadingState('stat-with-students', false);
      toggleLoadingState('stat-without-students', false);
    },
    error: function() {
      // Show error state
      $('#stat-programs, #stat-with-students, #stat-without-students').text('N/A');
      $('#stat-programs-updated, #stat-with-students-updated, #stat-without-students-updated').text('N/A');
      
      toggleLoadingState('stat-programs', false);
      toggleLoadingState('stat-with-students', false);
      toggleLoadingState('stat-without-students', false);
      
      showError('Failed to load program statistics');
    }
  });
}

// ===========================
// PROGRAM CRUD OPERATIONS
// ===========================

/**
 * Handles the Add Program button click event
 */
function handleAddProgramBtn() {
  $('#addProgramBtn').on('click', function () {
    $('#programForm')[0].reset();
    $('#program_id').val('');
    $('#programModal .modal-title').text('Add Program');
    $('#saveProgramBtn').text('Save');
    
    // Load dropdown data
    loadFaculties();
    
    $('#programModal').modal('show');
  });
}

/**
 * Handles the Add/Edit Program form submission
 */
function handleProgramFormSubmit() {
  $('#programForm').on('submit', function (e) {
    e.preventDefault();
    
    const programId = $('#program_id').val();
    const url = programId
      ? '{{ url('admin/programs') }}/' + programId
      : '{{ route('admin.programs.store') }}';
    const method = programId ? 'PUT' : 'POST';
    const formData = $(this).serialize();
    
    // Disable submit button during request
    const $submitBtn = $('#saveProgramBtn');
    const originalText = $submitBtn.text();
    $submitBtn.prop('disabled', true).text('Saving...');
    
    $.ajax({
      url: url,
      method: method,
      data: formData,
      success: function () {
        $('#programModal').modal('hide');
        $('#programs-table').DataTable().ajax.reload(null, false);
        showSuccess('Program has been saved successfully.');
        loadProgramStats(); // Refresh stats
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
 * Handles the Edit Program button click event (delegated)
 */
function handleEditProgramBtn() {
  $(document).on('click', '.editProgramBtn', function () {
    const programId = $(this).data('id');
    
    $.ajax({
      url: '{{ url('admin/programs') }}/' + programId,
      method: 'GET',
      success: function (program) {
        // Populate form fields
        $('#program_id').val(program.id);
        $('#name').val(program.name);
        $('#code').val(program.code);
        
        // Load dropdowns with preselected values
        loadFaculties(program.faculty_id);
        
        // Update modal
        $('#programModal .modal-title').text('Edit Program');
        $('#saveProgramBtn').text('Update');
        $('#programModal').modal('show');
      },
      error: function () {
        showError('Failed to fetch program data.');
      }
    });
  });
}

/**
 * Handles the Delete Program button click event (delegated)
 */
function handleDeleteProgramBtn() {
  $(document).on('click', '.deleteProgramBtn', function () {
    const programId = $(this).data('id');
    
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
          url: '{{ url('admin/programs') }}/' + programId,
          method: 'DELETE',
          success: function () {
            $('#programs-table').DataTable().ajax.reload(null, false);
            showSuccess('Program has been deleted.');
            loadProgramStats(); // Refresh stats
          },
          error: function (xhr) {
            const message = xhr.responseJSON?.message || 'Failed to delete program.';
            showError(message);
          }
        });
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
function initializeProgramManagement() {
  // Load initial data
  loadProgramStats();
  
  // Initialize CRUD handlers
  handleAddProgramBtn();
  handleProgramFormSubmit();
  handleEditProgramBtn();
  handleDeleteProgramBtn();
}

// ===========================
// DOCUMENT READY
// ===========================

$(document).ready(function () {
  initializeProgramManagement();
});
</script>
@endpush 