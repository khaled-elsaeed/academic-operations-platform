@extends('layouts.home')

@section('title', 'Credit Hours Exceptions Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- ===== STATISTICS CARDS ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 
                id="exceptions"
                label="Total Exceptions"
                color="primary"
                icon="bx bx-error"
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
                color="danger"
                icon="bx bx-x-circle"
            />
        </div>
    </div>

    {{-- ===== PAGE HEADER & ACTION BUTTONS ===== --}}
    <x-ui.page-header 
        title="Credit Hours Exceptions"
        description="Manage exceptions to credit hour limits for students."
        icon="bx bx-error"
    >
        @can('credit_hours_exception.create')
            <button class="btn btn-primary mx-2" 
                    id="addExceptionBtn" 
                    type="button" 
                    data-bs-toggle="modal" 
                    data-bs-target="#exceptionModal">
                <i class="bx bx-plus me-1"></i> Add Exception
            </button>
        @endcan
        <button class="btn btn-secondary"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#exceptionSearchCollapse"
                aria-expanded="false"
                aria-controls="exceptionSearchCollapse">
            <i class="bx bx-filter-alt me-1"></i> Search
        </button>
    </x-ui.page-header>

    {{-- ===== ADVANCED SEARCH SECTION ===== --}}
    <x-ui.advanced-search 
        title="Advanced Search" 
        formId="advancedExceptionSearch" 
        collapseId="exceptionSearchCollapse"
        :collapsed="false"
    >
        <div class="col-md-3">
            <label for="search_student_name" class="form-label">Student Name:</label>
            <input type="text" class="form-control" id="search_student_name" placeholder="Student Name">
        </div>
        <div class="col-md-3">
            <label for="search_academic_id" class="form-label">Academic ID:</label>
            <input type="text" class="form-control" id="search_academic_id" placeholder="Academic ID">
        </div>
        <div class="col-md-3">
            <label for="search_national_id" class="form-label">National ID:</label>
            <input type="text" class="form-control" id="search_national_id" placeholder="National ID">
        </div>
        <div class="col-md-3">
            <label for="search_term" class="form-label">Term:</label>
            <input type="text" class="form-control" id="search_term" placeholder="Term">
        </div>
        <div class="w-100"></div>
        <button class="btn btn-outline-secondary mt-2 ms-2" id="clearExceptionFiltersBtn" type="button">
            <i class="bx bx-x"></i> Clear Filters
        </button>
    </x-ui.advanced-search>

    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable
        :headers="['ID', 'Student', 'Academic ID', 'National ID', 'Term', 'Additional Hours', 'Status', 'Reason', 'Action']"
        :columns="[
            ['data' => 'id', 'name' => 'id'],
            ['data' => 'student', 'name' => 'student'],
            ['data' => 'academic_id', 'name' => 'academic_id'],
            ['data' => 'national_id', 'name' => 'national_id'],
            ['data' => 'term', 'name' => 'term'],
            ['data' => 'additional_hours', 'name' => 'additional_hours'],
            ['data' => 'is_active', 'name' => 'is_active'],
            ['data' => 'reason', 'name' => 'reason'],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
        ]"
        :ajax-url="route('credit-hours-exceptions.datatable')"
        table-id="credit-hours-exceptions-table"
        :filter-fields="['search_student_name','search_academic_id','search_national_id','search_term']"
    />

    {{-- ===== MODALS SECTION ===== --}}
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
const Utils = {
  showSuccess(message) {
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: message,
      showConfirmButton: false,
      timer: 2500,
      timerProgressBar: true
    });
  },
  showError(message) {
    Swal.fire('Error', message, 'error');
  },
  toggleLoadingState(elementId, isLoading) {
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
};

// ===========================
// STATS MANAGER
// ===========================
const StatsManager = {
  loadExceptionStats() {
    Utils.toggleLoadingState('exceptions', true);
    Utils.toggleLoadingState('active-exceptions', true);
    Utils.toggleLoadingState('inactive-exceptions', true);
    $.ajax({
      url: '{{ route("credit-hours-exceptions.stats") }}',
      method: 'GET',
      success: function (response) {
        const data = response.data;
        $('#exceptions-value').text(data.total.total ?? '--');
        $('#exceptions-last-updated').text(data.total.lastUpdateTime ?? '--');
        $('#active-exceptions-value').text(data.active.total ?? '--');
        $('#active-exceptions-last-updated').text(data.active.lastUpdateTime ?? '--');
        $('#inactive-exceptions-value').text(data.inactive.total ?? '--');
        $('#inactive-exceptions-last-updated').text(data.inactive.lastUpdateTime ?? '--');
        Utils.toggleLoadingState('exceptions', false);
        Utils.toggleLoadingState('active-exceptions', false);
        Utils.toggleLoadingState('inactive-exceptions', false);
      },
      error: function() {
        $('#exceptions-value, #active-exceptions-value, #inactive-exceptions-value').text('N/A');
        $('#exceptions-last-updated, #active-exceptions-last-updated, #inactive-exceptions-last-updated').text('N/A');
        Utils.toggleLoadingState('exceptions', false);
        Utils.toggleLoadingState('active-exceptions', false);
        Utils.toggleLoadingState('inactive-exceptions', false);
        Utils.showError('Failed to load exception statistics');
      }
    });
  }
};

// ===========================
// DROPDOWN MANAGER
// ===========================
const DropdownManager = {
  loadStudents(selectedId = null) {
    return $.ajax({
      url: '{{ route("credit-hours-exceptions.students") }}',
      method: 'GET',
      success: function (response) {
        const data = response.data;
        const $studentSelect = $('#student_id');
        $studentSelect.empty().append('<option value="">Select Student</option>');
        data.forEach(function (student) {
          $studentSelect.append(
            $('<option>', { value: student.id, text: student.text })
          );
        });
        if (selectedId) {
          $studentSelect.val(selectedId).trigger('change');
        }
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
        Utils.showError('Failed to load students');
      }
    });
  },
  loadTerms(selectedId = null) {
    return $.ajax({
      url: '{{ route("credit-hours-exceptions.terms") }}',
      method: 'GET',
      success: function (response) {
        const data = response.data;
        const $termSelect = $('#term_id');
        $termSelect.empty().append('<option value="">Select Term</option>');
        data.forEach(function (term) {
          $termSelect.append(
            $('<option>', { value: term.id, text: term.text })
          );
        });
        if (selectedId) {
          $termSelect.val(selectedId).trigger('change');
        }
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
        Utils.showError('Failed to load terms');
      }
    });
  }
};

// ===========================
// EXCEPTION MANAGER
// ===========================
const ExceptionManager = {
  handleAddExceptionBtn() {
    $('#addExceptionBtn').on('click', function () {
      $('#exceptionForm')[0].reset();
      $('#exception_id').val('');
      $('#exceptionModal .modal-title').text('Add Credit Hours Exception');
      $('#saveExceptionBtn').text('Save');
      $('#is_active').prop('checked', true);
      if ($('#student_id').hasClass('select2-hidden-accessible')) {
        $('#student_id').select2('destroy');
      }
      if ($('#term_id').hasClass('select2-hidden-accessible')) {
        $('#term_id').select2('destroy');
      }
      DropdownManager.loadStudents();
      DropdownManager.loadTerms();
      $('#exceptionModal').modal('show');
    });
  },
  handleEditExceptionBtn() {
    $(document).on('click', '.editExceptionBtn', function () {
      const exceptionId = $(this).data('id');
      $.ajax({
        url: `{{ route('credit-hours-exceptions.index') }}/${exceptionId}`,
        method: 'GET',
        success: function (response) {
          if (response.success) {
            const exception = response.data;
            $('#exception_id').val(exception.id);
            $('#additional_hours').val(exception.additional_hours);
            $('#reason').val(exception.reason);
            $('#is_active').prop('checked', exception.is_active);
            if ($('#student_id').hasClass('select2-hidden-accessible')) {
              $('#student_id').select2('destroy');
            }
            if ($('#term_id').hasClass('select2-hidden-accessible')) {
              $('#term_id').select2('destroy');
            }
            DropdownManager.loadStudents(exception.student_id);
            DropdownManager.loadTerms(exception.term_id);
            $('#exceptionModal .modal-title').text('Edit Credit Hours Exception');
            $('#saveExceptionBtn').text('Update');
            $('#exceptionModal').modal('show');
          } else {
            Utils.showError(response.message || 'Failed to load exception details');
          }
        },
        error: function() {
          Utils.showError('Failed to load exception details');
        }
      });
    });
  },
  handleExceptionFormSubmit() {
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
            Utils.showSuccess(response.message);
            $('#credit-hours-exceptions-table').DataTable().ajax.reload();
            StatsManager.loadExceptionStats();
          } else {
            Utils.showError(response.message || 'Operation failed');
          }
        },
        error: function (xhr) {
          $('#exceptionModal').modal('hide');
          const message = xhr.responseJSON?.message || 'An error occurred';
          Utils.showError(message);
        }
      });
    });
  },
  handleDeactivateExceptionBtn() {
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
                Utils.showSuccess(response.message);
                $('#credit-hours-exceptions-table').DataTable().ajax.reload();
                StatsManager.loadExceptionStats();
              } else {
                Utils.showError(response.message || 'Failed to deactivate exception');
              }
            },
            error: function() {
              Utils.showError('Failed to deactivate exception');
            }
          });
        }
      });
    });
  },
  handleActivateExceptionBtn() {
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
                Utils.showSuccess(response.message);
                $('#credit-hours-exceptions-table').DataTable().ajax.reload();
                StatsManager.loadExceptionStats();
              } else {
                Utils.showError(response.message || 'Failed to activate exception');
              }
            },
            error: function (xhr) {
              const message = xhr.responseJSON?.message || 'Failed to activate exception';
              Utils.showError(message);
            }
          });
        }
      });
    });
  },
  handleDeleteExceptionBtn() {
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
                Utils.showSuccess(response.message);
                $('#credit-hours-exceptions-table').DataTable().ajax.reload();
                StatsManager.loadExceptionStats();
              } else {
                Utils.showError(response.message || 'Failed to delete exception');
              }
            },
            error: function() {
              Utils.showError('Failed to delete exception');
            }
          });
        }
      });
    });
  }
};

// ===========================
// SEARCH MANAGER
// ===========================
const SearchManager = {
  initializeAdvancedSearch() {
    $('#search_student_name, #search_academic_id, #search_national_id, #search_term').on('keyup change', function() {
      $('#credit-hours-exceptions-table').DataTable().ajax.reload();
    });
    $('#clearExceptionFiltersBtn').on('click', function() {
      $('#search_student_name, #search_academic_id, #search_national_id, #search_term').val('');
      $('#credit-hours-exceptions-table').DataTable().ajax.reload();
    });
  }
};

// ===========================
// MAIN APPLICATION
// ===========================
const CreditHoursExceptionApp = {
  init() {
    StatsManager.loadExceptionStats();
    ExceptionManager.handleAddExceptionBtn();
    ExceptionManager.handleEditExceptionBtn();
    ExceptionManager.handleExceptionFormSubmit();
    ExceptionManager.handleDeactivateExceptionBtn();
    ExceptionManager.handleActivateExceptionBtn();
    ExceptionManager.handleDeleteExceptionBtn();
    SearchManager.initializeAdvancedSearch();
    $('#exceptionModal').on('hidden.bs.modal', function () {
      if ($('#student_id').hasClass('select2-hidden-accessible')) {
        $('#student_id').select2('destroy');
      }
      if ($('#term_id').hasClass('select2-hidden-accessible')) {
        $('#term_id').select2('destroy');
      }
    });
  }
};

$(document).ready(function() {
  CreditHoursExceptionApp.init();
});
</script>
@endpush 