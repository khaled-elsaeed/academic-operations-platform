@extends('layouts.home')

@section('title', 'Advisor Access Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 color="primary" icon="bx bx-user-check" label="Total Access Rules" id="total" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 color="success" icon="bx bx-check-circle" label="Active Rules" id="active" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 color="warning" icon="bx bx-x-circle" label="Inactive Rules" id="inactive" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 color="info" icon="bx bx-group" label="Unique Advisors" id="advisors" />
        </div>
    </div>

    <!-- Page Header and Actions -->
    <x-ui.page-header 
        title="Advisor Access"
        description="Manage advisor access permissions to students based on level and program."
        icon="bx bx-user-check"
    >
        <button class="btn btn-primary" onclick="openAddAccessModal()">
            <i class="bx bx-plus me-1"></i> Add Access Rule
        </button>
    </x-ui.page-header>

    <!-- Advisor Access DataTable -->
    <x-ui.datatable 
        :headers="['Advisor', 'Level', 'Program', 'Status', 'Created At', 'Actions']"
        :columns="[
            ['data' => 'advisor_name', 'name' => 'advisor_name'],
            ['data' => 'level_name', 'name' => 'level_name'],
            ['data' => 'program_name', 'name' => 'program_name'],
            ['data' => 'status', 'name' => 'status'],
            ['data' => 'created_at', 'name' => 'created_at'],
            ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
        ]"
        :ajax-url="route('academic_advisor_access.datatable')"
        :table-id="'academic-advisor-access-table'"
        :filters="[]"
    />
</div>

<!-- Add/Edit Access Modal -->
<x-ui.modal 
    id="accessModal"
    title="Add/Edit Access Rule"
    size="lg"
    :scrollable="false"
    class="access-modal"
>
    <x-slot name="slot">
        <form id="accessForm">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="advisor_id" class="form-label">Advisor <span class="text-danger">*</span></label>
                    <select id="advisor_id" name="advisor_id" class="form-select" required>
                        <option value="">Select Advisor</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card p-2 h-100">
                        <label for="level_id" class="form-label mb-2">Level <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-2">
                            <select id="level_id" name="level_id" class="form-select flex-grow-1" required>
                                <option value="">Select Level</option>
                            </select>
                            <div class="form-check ms-2">
                                <input type="checkbox" id="all_levels" name="all_levels" class="form-check-input">
                                <label for="all_levels" class="form-check-label">All Levels</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card p-2 h-100">
                        <label for="program_id" class="form-label mb-2">Program <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-2">
                            <select id="program_id" name="program_id" class="form-select flex-grow-1" required>
                                <option value="">Select Program</option>
                            </select>
                            <div class="form-check ms-2">
                                <input type="checkbox" id="all_programs" name="all_programs" class="form-check-input">
                                <label for="all_programs" class="form-check-label">All Programs</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="is_active" class="form-label">Status</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" form="accessForm">Save</button>
    </x-slot>
</x-ui.modal>

<!-- View Access Modal -->
<x-ui.modal 
    id="viewAccessModal"
    title="Access Rule Details"
    size="md"
    :scrollable="false"
    class="view-access-modal"
>
    <x-slot name="slot">
        <div class="row">
            <div class="col-12 mb-3">
                <label class="form-label fw-bold">Advisor:</label>
                <p id="view-access-advisor" class="mb-0"></p>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label fw-bold">Level:</label>
                <p id="view-access-level" class="mb-0"></p>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label fw-bold">Program:</label>
                <p id="view-access-program" class="mb-0"></p>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label fw-bold">Status:</label>
                <p id="view-access-status" class="mb-0"></p>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label fw-bold">Created At:</label>
                <p id="view-access-created" class="mb-0"></p>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
    </x-slot>
</x-ui.modal>
@endsection

@push('scripts')
<script>
let currentAccessId = null;

// Initialize page
$(document).ready(function() {
    loadStats();
    // Initialize Select2 for all selects in the access modal
    $('#advisor_id, #level_id, #program_id').select2({
      theme: 'bootstrap-5',
      placeholder: function(){
        return $(this).attr('id') === 'advisor_id' ? 'Select Advisor' :
               $(this).attr('id') === 'level_id' ? 'Select Level' :
               'Select Program';
      },
      allowClear: true,
      width: '100%',
      dropdownParent: $('#accessModal')
    });
    // Add this script to handle enabling/disabling selects based on checkboxes and sending flags
    $('#all_levels').on('change', function() {
      $('#level_id').prop('disabled', this.checked);
      if (this.checked) {
        $('#level_id').val('').trigger('change');
      }
    });
    $('#all_programs').on('change', function() {
      $('#program_id').prop('disabled', this.checked);
      if (this.checked) {
        $('#program_id').val('').trigger('change');
      }
    });
});

// Utility: Shows/hides loading spinners and content for stat2 component
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

// Load statistics (robust, like student page)
function loadStats() {
  toggleLoadingState('total', true);
  toggleLoadingState('active', true);
  toggleLoadingState('inactive', true);
  toggleLoadingState('advisors', true);

  $.ajax({
    url: '{{ route("academic_advisor_access.stats") }}',
    method: 'GET',
    success: function(response) {
      if (response.success) {
        $('#total-value').text(response.data.total.total ?? '--');
        $('#total-last-updated').text(response.data.total.lastUpdateTime ?? '--');
        $('#active-value').text(response.data.active.total ?? '--');
        $('#active-last-updated').text(response.data.active.lastUpdateTime ?? '--');
        $('#inactive-value').text(response.data.inactive.total ?? '--');
        $('#inactive-last-updated').text(response.data.inactive.lastUpdateTime ?? '--');
        $('#advisors-value').text(response.data.uniqueAdvisors.total ?? '--');
        $('#advisors-last-updated').text(response.data.uniqueAdvisors.lastUpdateTime ?? '--');
      } else {
        $('#total-value, #active-value, #inactive-value, #advisors-value').text('N/A');
        $('#total-last-updated, #active-last-updated, #inactive-last-updated, #advisors-last-updated').text('N/A');
      }
      toggleLoadingState('total', false);
      toggleLoadingState('active', false);
      toggleLoadingState('inactive', false);
      toggleLoadingState('advisors', false);
    },
    error: function() {
      $('#total-value, #active-value, #inactive-value, #advisors-value').text('N/A');
      $('#total-last-updated, #active-last-updated, #inactive-last-updated, #advisors-last-updated').text('N/A');
      toggleLoadingState('total', false);
      toggleLoadingState('active', false);
      toggleLoadingState('inactive', false);
      toggleLoadingState('advisors', false);
      Swal.fire('Error', 'Failed to load access statistics', 'error');
    }
  });
}

// Load advisors for dropdown
function loadAdvisors() {
    return $.getJSON('{{ route("academic_advisor_access.all") }}')
        .done(function(response) {
            const select = $('#advisor_id');
            select.empty().append('<option value="">Select Advisor</option>');
            response.data.forEach(function(advisor) {
                select.append(`<option value="${advisor.id}">${advisor.name}</option>`);
            });
        })
        .fail(function() {
            console.error('Failed to load advisors');
        });
}

// Load levels for dropdown
function loadLevels() {
    return $.getJSON('{{ route("levels.all") }}')
        .done(function(response) {
            const select = $('#level_id');
            select.empty().append('<option value="">Select Level</option>');
            response.data.forEach(function(level) {
                select.append(`<option value="${level.id}">${level.name}</option>`);
            });
        })
        .fail(function() {
            console.error('Failed to load levels');
        });
}

// Load programs for dropdown
function loadPrograms() {
    return $.getJSON('{{ route("programs.all") }}')
        .done(function(response) {
            const select = $('#program_id');
            select.empty().append('<option value="">Select Program</option>');
            response.data.forEach(function(program) {
                select.append(`<option value="${program.id}">${program.name}</option>`);
            });
        })
        .fail(function() {
            console.error('Failed to load programs');
        });
}

// Open add access modal
function openAddAccessModal() {
    currentAccessId = null;
    $('#accessModal .modal-title').text('Add Access Rule');
    $('#accessForm')[0].reset();
    // Load dropdowns only when modal is opened
    $.when(loadAdvisors(), loadLevels(), loadPrograms()).done(function() {
        $('#advisor_id').val('').trigger('change');
        $('#level_id').val('').trigger('change');
        $('#program_id').val('').trigger('change');
        $('#is_active').prop('checked', true);
        $('#accessModal').modal('show');
    });
}

// Open edit access modal
function editAccess(id) {
    currentAccessId = id;
    $('#accessModal .modal-title').text('Edit Access Rule');
    // Fetch dropdowns first, then fetch and set the access data
    $.when(loadAdvisors(), loadLevels(), loadPrograms()).done(function() {
        $.get(`{{ route('academic_advisor_access.show', ':id') }}`.replace(':id', id))
            .done(function(response) {
                if (response.success) {
                    const access = response.data;
                    // Set advisor select to advisor.id if available, else fallback to advisor_id
                    if (access.advisor && access.advisor.id) {
                        $('#advisor_id').val(access.advisor.id).trigger('change');
                    } else {
                        $('#advisor_id').val(access.advisor_id).trigger('change');
                    }
                    // Set level select to level.id if available, else fallback to level_id
                    if (access.level && access.level.id) {
                        $('#level_id').val(access.level.id).trigger('change');
                    } else {
                        $('#level_id').val(access.level_id).trigger('change');
                    }
                    // Set program select to program.id if available, else fallback to program_id
                    if (access.program && access.program.id) {
                        $('#program_id').val(access.program.id).trigger('change');
                    } else {
                        $('#program_id').val(access.program_id).trigger('change');
                    }
                    $('#is_active').prop('checked', !!access.is_active);
                    $('#accessModal').modal('show');
                }
            })
            .fail(function() {
                Swal.fire('Error', 'Failed to load access rule details', 'error');
            });
    });
}

// View access details
function viewAccess(id) {
    $.get(`{{ route('academic_advisor_access.show', ':id') }}`.replace(':id', id))
        .done(function(response) {
            if (response.success) {
                const access = response.data;
                $('#view-access-advisor').text(access.advisor ? access.advisor.name : 'N/A');
                $('#view-access-level').text(access.level ? access.level.name : 'N/A');
                $('#view-access-program').text(access.program ? access.program.name : 'N/A');
                $('#view-access-status').html(access.is_active 
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-danger">Inactive</span>');
                $('#view-access-created').text(new Date(access.created_at).toLocaleString());
                $('#viewAccessModal').modal('show');
            }
        })
        .fail(function() {
            Swal.fire('Error', 'Failed to load access rule details', 'error');
        });
}

// Delete access
function deleteAccess(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ route('academic_advisor_access.destroy', ':id') }}`.replace(':id', id),
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .done(function(response) {
                if (response.success) {
                    Swal.fire('Deleted!', response.message, 'success');
                    $('#academic-advisor-access-table').DataTable().ajax.reload();
                    loadStats();
                }
            })
            .fail(function() {
                Swal.fire('Error', 'Failed to delete access rule', 'error');
            });
        }
    });
}

// Handle form submission
$('#accessForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('is_active', $('#is_active').is(':checked') ? 1 : 0);
    // Add all_programs/all_levels flags
    formData.set('all_programs', $('#all_programs').is(':checked') ? 1 : 0);
    formData.set('all_levels', $('#all_levels').is(':checked') ? 1 : 0);
    
    const url = currentAccessId ? `{{ route('academic_advisor_access.update', ':id') }}`.replace(':id', currentAccessId) : '{{ route("academic_advisor_access.store") }}';
    const method = currentAccessId ? 'PUT' : 'POST';
    
    $.ajax({
        url: url,
        type: method,
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            Swal.fire({
              toast: true,
              position: 'top-end',
              icon: 'success',
              title: response.message,
              showConfirmButton: false,
              timer: 2500,
              timerProgressBar: true
            });
            $('#accessModal').modal('hide');
            $('#academic-advisor-access-table').DataTable().ajax.reload();
            loadStats();
        }
    })
    .fail(function(xhr) {
        if (xhr.responseJSON && xhr.responseJSON.message) {
            Swal.fire('Error', xhr.responseJSON.message, 'error');
        } else {
            Swal.fire('Error', 'Failed to save access rule', 'error');
        }
    });
});
</script>
@endpush 