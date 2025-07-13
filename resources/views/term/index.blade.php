@extends('layouts.home')

@section('title', 'Academic Term Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <x-ui.card.stat2 
        id="terms"
        label="Total Terms"
        color="primary"
        icon="bx bx-calendar"
      />
    </div>
    <div class="col-sm-6 col-xl-3">
      <x-ui.card.stat2 
        id="active"
        label="Active Terms"
        color="success"
        icon="bx bx-check-circle"
      />
    </div>
    <div class="col-sm-6 col-xl-3">
      <x-ui.card.stat2 
        id="inactive"
        label="Inactive Terms"
        color="warning"
        icon="bx bx-x-circle"
      />
    </div>
    <div class="col-sm-6 col-xl-3">
      <x-ui.card.stat2 
        id="current-year"
        label="Current Year Terms"
        color="info"
        icon="bx bx-calendar-star"
      />
    </div>
  </div>

  <!-- Page Header and Actions -->
  <x-ui.page-header 
    title="Academic Terms"
    description="Manage all academic term records and add new terms using the options on the right."
    icon="bx bx-calendar"
  >
    <button class="btn btn-primary" id="addTermBtn" type="button" data-bs-toggle="modal" data-bs-target="#termModal">
      <i class="bx bx-plus me-1"></i> Add Term
    </button>
  </x-ui.page-header>

  <!-- Terms DataTable -->
  <x-ui.datatable
    :headers="['ID', 'Name', 'Code', 'Status', 'Enrollments', 'Action']"
    :columns="[
        ['data' => 'id', 'name' => 'id'],
        ['data' => 'name', 'name' => 'name'],
        ['data' => 'code', 'name' => 'code'],
        ['data' => 'status', 'name' => 'status', 'orderable' => false, 'searchable' => false],
        ['data' => 'enrollments_count', 'name' => 'enrollments_count'],
        ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
    ]"
    :ajax-url="route('terms.datatable')"
    table-id="terms-table"
  />

  <!-- Add/Edit Term Modal -->
  <x-ui.modal 
    id="termModal"
    title="Add/Edit Term"
    size="md"
    :scrollable="false"
    class="term-modal"
  >
    <x-slot name="slot">
      <form id="termForm">
        <input type="hidden" id="term_id" name="term_id">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="season" class="form-label">Season <span class="text-danger">*</span></label>
            <select class="form-select" id="season" name="season" required>
              <option value="">Select Season</option>
              <option value="Fall">Fall</option>
              <option value="Spring">Spring</option>
              <option value="Summer">Summer</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="year" name="year" required>
          </div>
        </div>
        <div class="row">
          <div class="col-md-8 mb-3">
            <label for="code" class="form-label">Term Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="code" name="code" maxlength="10" required>
            <small class="form-text text-muted">e.g., 2252 for Fall 2025</small>
          </div>
          <div class="col-md-4 mb-3">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1">
              <label class="form-check-label" for="is_active">
                Active Term
              </label>
            </div>
          </div>
        </div>
      </form>
    </x-slot>
    <x-slot name="footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
        Close
      </button>
      <button type="submit" class="btn btn-primary" id="saveTermBtn" form="termForm">Save</button>
    </x-slot>
  </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
/**
 * Academic Term Management System JavaScript
 * Handles CRUD operations for academic terms
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
 * Loads term statistics and updates stat cards
 */
function loadTermStats() {
  // Show loading state for all stats
  toggleLoadingState('terms', true);
  toggleLoadingState('active', true);
  toggleLoadingState('inactive', true);
  toggleLoadingState('current-year', true);
  
  $.ajax({
    url: '{{ route('terms.stats') }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      // Update term statistics
      $('#terms-value').text(data.total.total ?? '--');
      $('#terms-last-updated').text(data.total.lastUpdateTime ?? '--');
      $('#active-value').text(data.active.total ?? '--');
      $('#active-last-updated').text(data.active.lastUpdateTime ?? '--');
      $('#inactive-value').text(data.inactive.total ?? '--');
      $('#inactive-last-updated').text(data.inactive.lastUpdateTime ?? '--');
      $('#current-year-value').text(data.currentYear.total ?? '--');
      $('#current-year-last-updated').text(data.currentYear.lastUpdateTime ?? '--');
      // Hide loading state
      toggleLoadingState('terms', false);
      toggleLoadingState('active', false);
      toggleLoadingState('inactive', false);
      toggleLoadingState('current-year', false);
    },
    error: function() {
      // Show error state
      $('#terms-value, #active-value, #inactive-value, #current-year-value').text('N/A');
      $('#terms-last-updated, #active-last-updated, #inactive-last-updated, #current-year-last-updated').text('N/A');
      toggleLoadingState('terms', false);
      toggleLoadingState('active', false);
      toggleLoadingState('inactive', false);
      toggleLoadingState('current-year', false);
      showError('Failed to load term statistics');
    }
  });
}

// ===========================
// TERM CRUD OPERATIONS
// ===========================

/**
 * Handles the Add Term button click event
 */
function handleAddTermBtn() {
  $('#addTermBtn').on('click', function () {
    $('#termForm')[0].reset();
    $('#term_id').val('');
    $('#termModal .modal-title').text('Add Term');
    $('#saveTermBtn').text('Save');
    $('#termModal').modal('show');
  });
}

/**
 * Handles the Add/Edit Term form submission
 */
function handleTermFormSubmit() {
  $('#termForm').on('submit', function (e) {
    e.preventDefault();
    
    const termId = $('#term_id').val();
    const url = termId 
              ? '{{ route('terms.show', ':id') }}'.replace(':id', termId)
      : '{{ route('terms.store') }}';
    const method = termId ? 'PUT' : 'POST';
    
    // Prepare form data
    const formData = new FormData(this);
    if (!formData.get('is_active')) {
      formData.set('is_active', '0');
    }
    
    $.ajax({
      url: url,
      method: method,
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        $('#termModal').modal('hide');
        $('#terms-table').DataTable().ajax.reload(null, false);
        loadTermStats();
        showSuccess(response.message || 'Term saved successfully.');
      },
      error: function (xhr) {
        const response = xhr.responseJSON;
        if (response && response.errors && Object.keys(response.errors).length > 0) {
          // Handle validation errors
          const errorMessages = [];
          Object.keys(response.errors).forEach(field => {
            if (Array.isArray(response.errors[field])) {
              errorMessages.push(...response.errors[field]);
            } else {
              errorMessages.push(response.errors[field]);
            }
          });
          showError(errorMessages.join('<br>'));
        } else {
          // Handle general errors
          const message = response?.message || 'An error occurred. Please check your input.';
          showError(message);
        }
      }
    });
  });
}

/**
 * Handles the Edit Term button click event (delegated)
 */
function handleEditTermBtn() {
  $(document).on('click', '.editTermBtn', function () {
    const termId = $(this).data('id');
    
    $.ajax({
      url: '{{ route('terms.show', ':id') }}'.replace(':id', termId),
      method: 'GET',
      success: function (response) {
        const term = response.data;
        $('#term_id').val(term.id);
        $('#season').val(term.season);
        $('#year').val(term.year);
        $('#code').val(term.code);
        $('#is_active').prop('checked', term.is_active);
        
        $('#termModal .modal-title').text('Edit Term');
        $('#saveTermBtn').text('Update');
        $('#termModal').modal('show');
      },
      error: function () {
        showError('Failed to load term details.');
      }
    });
  });
}

/**
 * Handles the Delete Term button click event (delegated)
 */
function handleDeleteTermBtn() {
  $(document).on('click', '.deleteTermBtn', function () {
    const termId = $(this).data('id');
    
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
          url: '{{ route('terms.destroy', ':id') }}'.replace(':id', termId),
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            $('#terms-table').DataTable().ajax.reload(null, false);
            loadTermStats();
            Swal.fire('Deleted!', response.message || 'Term has been deleted.', 'success');
          },
          error: function (xhr) {
            const response = xhr.responseJSON;
            const message = response?.message || 'Failed to delete term.';
            Swal.fire('Error', message, 'error');
          }
        });
      }
    });
  });
}

// ===========================
// FORM VALIDATION & HELPERS
// ===========================

/**
 * Auto-generate term code based on season and year
 */
function handleSeasonYearChange() {
  $('#season, #year').on('change', function() {
    const season = $('#season').val();
    const year = $('#year').val();
    
    if (season && year) {
      let seasonCode = '';
      switch(season) {
        case 'Fall':
          seasonCode = '1';
          break;
        case 'Spring':
          seasonCode = '2';
          break;
        case 'Summer':
          seasonCode = '3';
          break;
      }
      
      if (seasonCode) {
        const shortYear = year.toString().slice(-2);
        const generatedCode = shortYear + seasonCode;
        $('#code').val(generatedCode);
      }
    }
  });
}

// ===========================
// INITIALIZATION
// ===========================

// Main entry point
$(document).ready(function () {
  // Load initial data
  loadTermStats();
  
  // Initialize event handlers
  handleAddTermBtn();
  handleTermFormSubmit();
  handleEditTermBtn();
  handleDeleteTermBtn();
  handleSeasonYearChange();
  
  // Set current year as default
});
</script>
@endpush 