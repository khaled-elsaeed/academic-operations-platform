@extends('layouts.home')

@section('title', 'Program Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
      <x-ui.card.stat2 
        id="programs"
        label="Total Programs"
        color="primary"
        icon="bx bx-book-open"
      />
    </div>
    <div class="col-sm-6 col-xl-4">
      <x-ui.card.stat2 
        id="with-students"
        label="Programs with Students"
        color="success"
        icon="bx bx-user-check"
      />
    </div>
    <div class="col-sm-6 col-xl-4">
      <x-ui.card.stat2 
        id="without-students"
        label="Programs without Students"
        color="warning"
        icon="bx bx-user-x"
      />
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
    :ajax-url="route('programs.datatable')"
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
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: message,
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true
  });
}

/**
 * Shows error notification
 * @param {string} message - Error message to display
 */
function showError(message) {
  Swal.fire('Error', message, 'error');
}

/**
 * Shows/hides loading spinners and content for stat2 component
 * @param {string} elementId - Base element ID
 * @param {boolean} isLoading - Whether to show loading state
 */
function toggleLoadingState(elementId, isLoading) {
  const $value = $(`#${elementId}-value`);
  const $loader = $(`#${elementId}-loader`);
  const $updated = $(`#${elementId}-last-updated`);
  const $updatedLoader = $(`#${elementId}-last-updated-loader`);

  if (isLoading) {
    $value.addClass('d-none');
    $loader.removeClass('d-none');
    $updated.addClass('d-none');
    $updatedLoader.removeClass('d-none');
  } else {
    $value.removeClass('d-none');
    $loader.addClass('d-none');
    $updated.removeClass('d-none');
    $updatedLoader.addClass('d-none');
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
    url: '{{ route('programs.faculties') }}',
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
  toggleLoadingState('programs', true);
  toggleLoadingState('with-students', true);
  toggleLoadingState('without-students', true);
  
  $.ajax({
    url: '{{ route('programs.stats') }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      // Update program statistics
      $('#programs-value').text(data.total.total ?? '--');
      $('#programs-last-updated').text(data.total.lastUpdateTime ?? '--');
      $('#with-students-value').text(data.withStudents.total ?? '--');
      $('#with-students-last-updated').text(data.withStudents.lastUpdateTime ?? '--');
      $('#without-students-value').text(data.withoutStudents.total ?? '--');
      $('#without-students-last-updated').text(data.withoutStudents.lastUpdateTime ?? '--');
      // Hide loading state
      toggleLoadingState('programs', false);
      toggleLoadingState('with-students', false);
      toggleLoadingState('without-students', false);
    },
    error: function() {
      // Show error state
      $('#programs-value, #with-students-value, #without-students-value').text('N/A');
      $('#programs-last-updated, #with-students-last-updated, #without-students-last-updated').text('N/A');
      toggleLoadingState('programs', false);
      toggleLoadingState('with-students', false);
      toggleLoadingState('without-students', false);
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
              ? '{{ route('programs.show', ':id') }}'.replace(':id', programId)
      : '{{ route('programs.store') }}';
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
      url: '{{ route('programs.show', ':id') }}'.replace(':id', programId),
      method: 'GET',
      success: function (program) {
        // Prefer program.data if exists, fallback to program
        const prog = program.data ? program.data : program;
        // Populate form fields
        $('#program_id').val(prog.id);
        $('#name').val(prog.name);
        $('#code').val(prog.code);
        
        // Load dropdowns with preselected values
        loadFaculties(prog.faculty_id);
        
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
          url: '{{ route('programs.destroy', ':id') }}'.replace(':id', programId),
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

  // Initialize Select2 for faculty select in the program modal
  $('#faculty_id').select2({
    theme: 'bootstrap-5',
    placeholder: 'Select Faculty',
    allowClear: true,
    width: '100%',
    dropdownParent: $('#programModal')
  });
}

// ===========================
// DOCUMENT READY
// ===========================

$(document).ready(function () {
  initializeProgramManagement();
});
</script>
@endpush 