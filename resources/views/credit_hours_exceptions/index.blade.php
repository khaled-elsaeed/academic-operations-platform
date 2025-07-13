@extends('layouts.home')

@section('title', 'Credit Hours Exceptions Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
      <x-ui.card.stat2 
        id="exceptions"
        label="Total Exceptions"
        color="primary"
        icon="bx bx-time"
      />
    </div>
    <div class="col-sm-6 col-xl-4">
      <x-ui.card.stat2 
        id="active-exceptions"
        label="Active Exceptions"
        color="success"
        icon="bx bx-check-circle"
      />
    </div>
    <div class="col-sm-6 col-xl-4">
      <x-ui.card.stat2 
        id="inactive-exceptions"
        label="Inactive Exceptions"
        color="warning"
        icon="bx bx-x-circle"
      />
    </div>
  </div>

  <!-- Page Header and Actions -->
  <x-ui.page-header 
    title="Credit Hours Exceptions"
    description="Manage credit hours exceptions for students and add new exceptions using the options on the right."
    icon="bx bx-time"
  >
    <button class="btn btn-primary" id="addExceptionBtn" type="button" data-bs-toggle="modal" data-bs-target="#exceptionModal">
      <i class="bx bx-plus me-1"></i> Add Exception
    </button>
  </x-ui.page-header>

  <!-- Exceptions DataTable -->
  <x-ui.datatable
    :headers="['Student', 'Term', 'Additional Hours', 'Reason', 'Granted By', 'Status', 'Created', 'Action']"
    :columns="[
        ['data' => 'student_name', 'name' => 'student_name'],
        ['data' => 'term_name', 'name' => 'term_name'],
        ['data' => 'additional_hours', 'name' => 'additional_hours'],
        ['data' => 'reason', 'name' => 'reason'],
        ['data' => 'granted_by_name', 'name' => 'granted_by_name'],
        ['data' => 'status', 'name' => 'status'],
        ['data' => 'created_at', 'name' => 'created_at'],
        ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
    ]"
    :ajax-url="route('credit-hours-exceptions.datatable')"
    table-id="exceptions-table"
  />

  <!-- Add/Edit Exception Modal -->
  <x-ui.modal 
    id="exceptionModal"
    title="Add/Edit Credit Hours Exception"
    size="lg"
    :scrollable="false"
    class="exception-modal"
  >
    <x-slot name="slot">
      <form id="exceptionForm">
        <input type="hidden" id="exception_id" name="exception_id">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="student_id" class="form-label">Student</label>
            <select class="form-select select2" id="student_id" name="student_id" required>
              <option value="">Select Student</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="term_id" class="form-label">Term</label>
            <select class="form-select select2" id="term_id" name="term_id" required>
              <option value="">Select Term</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="additional_hours" class="form-label">Additional Hours</label>
            <input type="number" class="form-control" id="additional_hours" name="additional_hours" min="1" max="12" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="is_active" class="form-label">Status</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
              <label class="form-check-label" for="is_active">Active</label>
            </div>
          </div>
          <div class="col-12 mb-3">
            <label for="reason" class="form-label">Reason</label>
            <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Reason for granting additional credit hours..."></textarea>
          </div>
        </div>
      </form>
    </x-slot>
    <x-slot name="footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
        Close
      </button>
      <button type="submit" class="btn btn-primary" id="saveExceptionBtn" form="exceptionForm">Save</button>
    </x-slot>
  </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
/**
 * Credit Hours Exceptions Management System JavaScript
 * Handles CRUD operations for credit hours exceptions
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
 * Loads exception statistics and updates stat cards
 */
function loadExceptionStats() {
  // Show loading state for all stats
  toggleLoadingState('exceptions', true);
  toggleLoadingState('active-exceptions', true);
  toggleLoadingState('inactive-exceptions', true);
  
  $.ajax({
    url: '{{ route("credit-hours-exceptions.stats") }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      // Update exception statistics
      $('#exceptions-value').text(data.total.total ?? '--');
      $('#exceptions-last-updated').text(data.total.lastUpdateTime ?? '--');
      $('#active-exceptions-value').text(data.active.total ?? '--');
      $('#active-exceptions-last-updated').text(data.active.lastUpdateTime ?? '--');
      $('#inactive-exceptions-value').text(data.inactive.total ?? '--');
      $('#inactive-exceptions-last-updated').text(data.inactive.lastUpdateTime ?? '--');
      // Hide loading state
      toggleLoadingState('exceptions', false);
      toggleLoadingState('active-exceptions', false);
      toggleLoadingState('inactive-exceptions', false);
    },
    error: function() {
      // Show error state
      $('#exceptions-value, #active-exceptions-value, #inactive-exceptions-value').text('N/A');
      $('#exceptions-last-updated, #active-exceptions-last-updated, #inactive-exceptions-last-updated').text('N/A');
      toggleLoadingState('exceptions', false);
      toggleLoadingState('active-exceptions', false);
      toggleLoadingState('inactive-exceptions', false);
      showError('Failed to load exception statistics');
    }
  });
}

// ===========================
// DROPDOWN POPULATION
// ===========================

/**
 * Loads all students into the student select dropdown
 * @param {number|null} selectedId - The student ID to preselect (optional)
 * @returns {Promise} jQuery promise
 */
function loadStudents(selectedId = null) {
  return $.ajax({
    url: '{{ route("credit-hours-exceptions.students") }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      const $studentSelect = $('#student_id');
      
      // Clear existing options
      $studentSelect.empty().append('<option value="">Select Student</option>');
      
      // Add student options
      data.forEach(function (student) {
        $studentSelect.append(
          $('<option>', { value: student.id, text: student.text })
        );
      });
      
      // Set selected value if provided
      if (selectedId) {
        $studentSelect.val(selectedId).trigger('change');
      }
      
      // Initialize Select2 if not already initialized
      if (!$studentSelect.hasClass('select2-hidden-accessible')) {
        $studentSelect.select2({
          theme: 'bootstrap-5',
          placeholder: 'Select Student',
          allowClear: true,
          width: '100%',
          dropdownParent: $('#exceptionModal')
        });
      }
    },
    error: function() {
      showError('Failed to load students');
    }
  });
}

/**
 * Loads all terms into the term select dropdown
 * @param {number|null} selectedId - The term ID to preselect (optional)
 * @returns {Promise} jQuery promise
 */
function loadTerms(selectedId = null) {
  return $.ajax({
    url: '{{ route("credit-hours-exceptions.terms") }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      const $termSelect = $('#term_id');
      
      // Clear existing options
      $termSelect.empty().append('<option value="">Select Term</option>');
      
      // Add term options
      data.forEach(function (term) {
        $termSelect.append(
          $('<option>', { value: term.id, text: term.text })
        );
      });
      
      // Set selected value if provided
      if (selectedId) {
        $termSelect.val(selectedId).trigger('change');
      }
      
      // Initialize Select2 if not already initialized
      if (!$termSelect.hasClass('select2-hidden-accessible')) {
        $termSelect.select2({
          theme: 'bootstrap-5',
          placeholder: 'Select Term',
          allowClear: true,
          width: '100%',
          dropdownParent: $('#exceptionModal')
        });
      }
    },
    error: function() {
      showError('Failed to load terms');
    }
  });
}

// ===========================
// EXCEPTION CRUD OPERATIONS
// ===========================

/**
 * Handles the Add Exception button click event
 */
function handleAddExceptionBtn() {
  $('#addExceptionBtn').on('click', function () {
    $('#exceptionForm')[0].reset();
    $('#exception_id').val('');
    $('#exceptionModal .modal-title').text('Add Credit Hours Exception');
    $('#saveExceptionBtn').text('Save');
    $('#is_active').prop('checked', true);
    
    // Destroy existing Select2 if initialized
    if ($('#student_id').hasClass('select2-hidden-accessible')) {
      $('#student_id').select2('destroy');
    }
    if ($('#term_id').hasClass('select2-hidden-accessible')) {
      $('#term_id').select2('destroy');
    }
    
    // Load dropdown data
    loadStudents();
    loadTerms();
    
    $('#exceptionModal').modal('show');
  });
}

/**
 * Handles the Edit Exception button click event
 */
function handleEditExceptionBtn() {
  $(document).on('click', '.editExceptionBtn', function () {
    const exceptionId = $(this).data('id');
    
    $.ajax({
      url: `{{ route('credit-hours-exceptions.index') }}/${exceptionId}`,
      method: 'GET',
      success: function (response) {
        if (response.success) {
          const exception = response.data;
          
          // Populate form fields
          $('#exception_id').val(exception.id);
          $('#additional_hours').val(exception.additional_hours);
          $('#reason').val(exception.reason);
          $('#is_active').prop('checked', exception.is_active);
          
          // Destroy existing Select2 if initialized
          if ($('#student_id').hasClass('select2-hidden-accessible')) {
            $('#student_id').select2('destroy');
          }
          if ($('#term_id').hasClass('select2-hidden-accessible')) {
            $('#term_id').select2('destroy');
          }
          
          // Load dropdowns with preselected values
          loadStudents(exception.student_id);
          loadTerms(exception.term_id);
          
          $('#exceptionModal .modal-title').text('Edit Credit Hours Exception');
          $('#saveExceptionBtn').text('Update');
          $('#exceptionModal').modal('show');
        } else {
          showError(response.message || 'Failed to load exception details');
        }
      },
      error: function() {
        showError('Failed to load exception details');
      }
    });
  });
}

/**
 * Handles the form submission for creating/updating exceptions
 */
function handleExceptionFormSubmit() {
  $('#exceptionForm').on('submit', function (e) {
    e.preventDefault();
    
    const exceptionId = $('#exception_id').val();
    const isEdit = exceptionId !== '';
    const url = isEdit 
      ? `{{ route('credit-hours-exceptions.index') }}/${exceptionId}`
      : '{{ route("credit-hours-exceptions.store") }}';
    const method = isEdit ? 'PUT' : 'POST';
    
    $.ajax({
      url: url,
      method: method,
      data: $(this).serialize(),
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function (response) {
        if (response.success) {
          $('#exceptionModal').modal('hide');
          showSuccess(response.message);
          // Reload datatable and stats
          $('#exceptions-table').DataTable().ajax.reload();
          loadExceptionStats();
        } else {
          showError(response.message || 'Operation failed');
        }
      },
      error: function (xhr) {
        const message = xhr.responseJSON?.message || 'An error occurred';
        showError(message);
      }
    });
  });
}

/**
 * Handles the Deactivate Exception button click event
 */
function handleDeactivateExceptionBtn() {
  $(document).on('click', '.deactivateExceptionBtn', function () {
    const exceptionId = $(this).data('id');
    
    Swal.fire({
      title: 'Deactivate Exception?',
      text: 'Are you sure you want to deactivate this exception?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, deactivate it!'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: `{{ route('credit-hours-exceptions.index') }}/${exceptionId}/deactivate`,
          method: 'PATCH',
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.success) {
              showSuccess(response.message);
              $('#exceptions-table').DataTable().ajax.reload();
              loadExceptionStats();
            } else {
              showError(response.message || 'Failed to deactivate exception');
            }
          },
          error: function() {
            showError('Failed to deactivate exception');
          }
        });
      }
    });
  });
}

/**
 * Handles the Activate Exception button click event
 */
function handleActivateExceptionBtn() {
  $(document).on('click', '.activateExceptionBtn', function () {
    const exceptionId = $(this).data('id');
    
    Swal.fire({
      title: 'Activate Exception?',
      text: 'Are you sure you want to activate this exception?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, activate it!'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: `{{ route('credit-hours-exceptions.index') }}/${exceptionId}/activate`,
          method: 'PATCH',
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.success) {
              showSuccess(response.message);
              $('#exceptions-table').DataTable().ajax.reload();
              loadExceptionStats();
            } else {
              showError(response.message || 'Failed to activate exception');
            }
          },
          error: function (xhr) {
            const message = xhr.responseJSON?.message || 'Failed to activate exception';
            showError(message);
          }
        });
      }
    });
  });
}

/**
 * Handles the Delete Exception button click event
 */
function handleDeleteExceptionBtn() {
  $(document).on('click', '.deleteExceptionBtn', function () {
    const exceptionId = $(this).data('id');
    
    Swal.fire({
      title: 'Delete Exception?',
      text: 'Are you sure you want to delete this exception? This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: `{{ route('credit-hours-exceptions.index') }}/${exceptionId}`,
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (response) {
            if (response.success) {
              showSuccess(response.message);
              $('#exceptions-table').DataTable().ajax.reload();
              loadExceptionStats();
            } else {
              showError(response.message || 'Failed to delete exception');
            }
          },
          error: function() {
            showError('Failed to delete exception');
          }
        });
      }
    });
  });
}

// ===========================
// INITIALIZATION
// ===========================

$(document).ready(function() {
  // Load initial data
  loadExceptionStats();
  
  // Initialize event handlers
  handleAddExceptionBtn();
  handleEditExceptionBtn();
  handleExceptionFormSubmit();
  handleDeactivateExceptionBtn();
  handleActivateExceptionBtn();
  handleDeleteExceptionBtn();
  
  // Handle modal cleanup when closed
  $('#exceptionModal').on('hidden.bs.modal', function () {
    // Destroy Select2 when modal is closed to prevent conflicts
    if ($('#student_id').hasClass('select2-hidden-accessible')) {
      $('#student_id').select2('destroy');
    }
    if ($('#term_id').hasClass('select2-hidden-accessible')) {
      $('#term_id').select2('destroy');
    }
  });
});
</script>
@endpush 