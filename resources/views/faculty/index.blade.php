@extends('layouts.home')

@section('title', 'Faculty Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
      <x-ui.card.stat2 
        id="faculties"
        label="Total Faculties"
        color="primary"
        icon="bx bx-building"
      />
    </div>
    <div class="col-sm-6 col-xl-4">
      <x-ui.card.stat2 
        id="with-programs"
        label="Faculties with Programs"
        color="success"
        icon="bx bx-check-circle"
      />
    </div>
    <div class="col-sm-6 col-xl-4">
      <x-ui.card.stat2 
        id="without-programs"
        label="Faculties without Programs"
        color="warning"
        icon="bx bx-x-circle"
      />
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
    :ajax-url="route('faculties.datatable')"
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
// STATISTICS MANAGEMENT
// ===========================

/**
 * Loads faculty statistics and updates stat cards
 */
function loadFacultyStats() {
  // Show loading state for all stats
  toggleLoadingState('faculties', true);
  toggleLoadingState('with-programs', true);
  toggleLoadingState('without-programs', true);
  
  $.ajax({
    url: '{{ route('faculties.stats') }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      // Update faculty statistics
      $('#faculties-value').text(data.total.total ?? '--');
      $('#faculties-last-updated').text(data.total.lastUpdateTime ?? '--');
      $('#with-programs-value').text(data.withPrograms.total ?? '--');
      $('#with-programs-last-updated').text(data.withPrograms.lastUpdateTime ?? '--');
      $('#without-programs-value').text(data.withoutPrograms.total ?? '--');
      $('#without-programs-last-updated').text(data.withoutPrograms.lastUpdateTime ?? '--');
      // Hide loading state
      toggleLoadingState('faculties', false);
      toggleLoadingState('with-programs', false);
      toggleLoadingState('without-programs', false);
    },
    error: function() {
      // Show error state
      $('#faculties-value, #with-programs-value, #without-programs-value').text('N/A');
      $('#faculties-last-updated, #with-programs-last-updated, #without-programs-last-updated').text('N/A');
      toggleLoadingState('faculties', false);
      toggleLoadingState('with-programs', false);
      toggleLoadingState('without-programs', false);
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
              ? '{{ route('faculties.show', ':id') }}'.replace(':id', facultyId)
      : '{{ route('faculties.store') }}';
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
      url: '{{ route('faculties.show', ':id') }}'.replace(':id', facultyId),
      method: 'GET',
      success: function (response) {

        fac = response.data ;
        $('#faculty_id').val(fac.id);
        $('#name').val(fac.name);
        
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
 * Handles the Delete Faculty button click event
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
          url: '{{ route('faculties.destroy', ':id') }}'.replace(':id', facultyId),
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