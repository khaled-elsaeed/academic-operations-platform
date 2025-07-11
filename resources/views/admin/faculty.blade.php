@extends('layouts.home')

@section('title', 'Faculty Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Faculties</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-faculties-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-faculties">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-faculties-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base bx bx-building icon-lg"></i>
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
              <span class="text-heading">Faculties with Programs</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-with-programs-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-with-programs">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-with-programs-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="icon-base bx bx-check-circle icon-lg"></i>
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
              <span class="text-heading">Faculties without Programs</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-without-programs-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-without-programs">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-without-programs-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="icon-base bx bx-x-circle icon-lg"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Page Header and Actions -->
  <x-ui.page-header 
    title="Faculties"
    description="Manage all faculty records and add new faculties using the options on the right."
    icon="bx bx-building"
  >
    <button class="btn btn-primary" id="addFacultyBtn" type="button" data-bs-toggle="modal" data-bs-target="#facultyModal">
      <i class="bx bx-plus me-1"></i> Add Faculty
    </button>
  </x-ui.page-header>

  <!-- Faculties DataTable -->
  <x-ui.datatable
    :headers="['ID', 'Name', 'Programs Count', 'Action']"
    :columns="[
        ['data' => 'id', 'name' => 'id'],
        ['data' => 'name', 'name' => 'name'],
        ['data' => 'programs_count', 'name' => 'programs_count'],
        ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
    ]"
    :ajax-url="route('admin.faculties.datatable')"
    table-id="faculties-table"
  />

  <!-- Add/Edit Faculty Modal -->
  <x-ui.modal 
    id="facultyModal"
    title="Add/Edit Faculty"
    size="md"
    :scrollable="false"
    class="faculty-modal"
  >
    <x-slot name="slot">
      <form id="facultyForm">
        <input type="hidden" id="faculty_id" name="faculty_id">
        <div class="mb-3">
          <label for="name" class="form-label">Faculty Name</label>
          <input type="text" class="form-control" id="name" name="name" required>
        </div>
      </form>
    </x-slot>
    <x-slot name="footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
        Close
      </button>
      <button type="submit" class="btn btn-primary" id="saveFacultyBtn" form="facultyForm">Save</button>
    </x-slot>
  </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
/**
 * Faculty Management System JavaScript
 * Handles CRUD operations for faculties
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
// STATISTICS MANAGEMENT
// ===========================

/**
 * Loads faculty statistics and updates stat cards
 */
function loadFacultyStats() {
  // Show loading state for all stats
  toggleLoadingState('stat-faculties', true);
  toggleLoadingState('stat-with-programs', true);
  toggleLoadingState('stat-without-programs', true);
  
  $.ajax({
    url: '{{ route('admin.faculties.stats') }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      
      // Update faculty statistics
      $('#stat-faculties').text(data.total.total ?? '--');
      $('#stat-faculties-updated').text(data.total.lastUpdateTime ?? '--');
      $('#stat-with-programs').text(data.withPrograms.total ?? '--');
      $('#stat-with-programs-updated').text(data.withPrograms.lastUpdateTime ?? '--');
      $('#stat-without-programs').text(data.withoutPrograms.total ?? '--');
      $('#stat-without-programs-updated').text(data.withoutPrograms.lastUpdateTime ?? '--');
      
      // Hide loading state
      toggleLoadingState('stat-faculties', false);
      toggleLoadingState('stat-with-programs', false);
      toggleLoadingState('stat-without-programs', false);
    },
    error: function() {
      // Show error state
      $('#stat-faculties, #stat-with-programs, #stat-without-programs').text('N/A');
      $('#stat-faculties-updated, #stat-with-programs-updated, #stat-without-programs-updated').text('N/A');
      
      toggleLoadingState('stat-faculties', false);
      toggleLoadingState('stat-with-programs', false);
      toggleLoadingState('stat-without-programs', false);
      
      showError('Failed to load faculty statistics');
    }
  });
}

// ===========================
// FACULTY CRUD OPERATIONS
// ===========================

/**
 * Handles the Add Faculty button click event
 */
function handleAddFacultyBtn() {
  $('#addFacultyBtn').on('click', function () {
    $('#facultyForm')[0].reset();
    $('#faculty_id').val('');
    $('#facultyModal .modal-title').text('Add Faculty');
    $('#saveFacultyBtn').text('Save');
    $('#facultyModal').modal('show');
  });
}

/**
 * Handles the Add/Edit Faculty form submission
 */
function handleFacultyFormSubmit() {
  $('#facultyForm').on('submit', function (e) {
    e.preventDefault();
    
    const facultyId = $('#faculty_id').val();
    const url = facultyId
      ? '{{ url('admin/faculties') }}/' + facultyId
      : '{{ route('admin.faculties.store') }}';
    const method = facultyId ? 'PUT' : 'POST';
    const formData = $(this).serialize();
    
    // Disable submit button during request
    const $submitBtn = $('#saveFacultyBtn');
    const originalText = $submitBtn.text();
    $submitBtn.prop('disabled', true).text('Saving...');
    
    $.ajax({
      url: url,
      method: method,
      data: formData,
      success: function () {
        $('#facultyModal').modal('hide');
        $('#faculties-table').DataTable().ajax.reload(null, false);
        showSuccess('Faculty has been saved successfully.');
        loadFacultyStats(); // Refresh stats
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
 * Handles the Edit Faculty button click event (delegated)
 */
function handleEditFacultyBtn() {
  $(document).on('click', '.editFacultyBtn', function () {
    const facultyId = $(this).data('id');
    
    $.ajax({
      url: '{{ url('admin/faculties') }}/' + facultyId,
      method: 'GET',
      success: function (faculty) {
        // Populate form fields
        $('#faculty_id').val(faculty.id);
        $('#name').val(faculty.name);
        
        // Update modal
        $('#facultyModal .modal-title').text('Edit Faculty');
        $('#saveFacultyBtn').text('Update');
        $('#facultyModal').modal('show');
      },
      error: function () {
        showError('Failed to fetch faculty data.');
      }
    });
  });
}

/**
 * Handles the Delete Faculty button click event (delegated)
 */
function handleDeleteFacultyBtn() {
  $(document).on('click', '.deleteFacultyBtn', function () {
    const facultyId = $(this).data('id');
    
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
          url: '{{ url('admin/faculties') }}/' + facultyId,
          method: 'DELETE',
          success: function () {
            $('#faculties-table').DataTable().ajax.reload(null, false);
            showSuccess('Faculty has been deleted.');
            loadFacultyStats(); // Refresh stats
          },
          error: function (xhr) {
            const message = xhr.responseJSON?.message || 'Failed to delete faculty.';
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
function initializeFacultyManagement() {
  // Load initial data
  loadFacultyStats();
  
  // Initialize CRUD handlers
  handleAddFacultyBtn();
  handleFacultyFormSubmit();
  handleEditFacultyBtn();
  handleDeleteFacultyBtn();
}

// ===========================
// DOCUMENT READY
// ===========================

$(document).ready(function () {
  initializeFacultyManagement();
});
</script>
@endpush 