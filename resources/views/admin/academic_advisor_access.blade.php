@extends('layouts.home')

@section('title', 'Advisor Access Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Access Rules</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-total-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-total">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-total-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base bx bx-user-check icon-lg"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Active Rules</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-active-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-active">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-active-updated">--</span></small>
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
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Inactive Rules</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-inactive-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-inactive">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-inactive-updated">--</span></small>
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
    <div class="col-sm-6 col-xl-3">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Unique Advisors</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-advisors-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-advisors">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-advisors-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-info">
                <i class="icon-base bx bx-group icon-lg"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
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
    :ajax-url="route('admin.academic_advisor_access.datatable')"
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
        <div class="col-md-6 mb-3">
          <label for="advisor_id" class="form-label">Advisor</label>
          <select id="advisor_id" name="advisor_id" class="form-select" required>
            <option value="">Select Advisor</option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="level_id" class="form-label">Level</label>
          <select id="level_id" name="level_id" class="form-select" required>
            <option value="">Select Level</option>
          </select>
        </div>
        <div class="col-md-6 mb-3">
          <label for="program_id" class="form-label">Program</label>
          <select id="program_id" name="program_id" class="form-select" required>
            <option value="">Select Program</option>
          </select>
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
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
      Close
    </button>
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
    loadAdvisors();
    loadLevels();
    loadPrograms();
});

// Load statistics
function loadStats() {
    $.get('{{ route("admin.academic_advisor_access.stats") }}')
        .done(function(response) {
            if (response.success) {
                $('#stat-total').text(response.data.total.total);
                $('#stat-total-updated').text(response.data.total.lastUpdateTime);
                $('#stat-active').text(response.data.active.total);
                $('#stat-active-updated').text(response.data.active.lastUpdateTime);
                $('#stat-inactive').text(response.data.inactive.total);
                $('#stat-inactive-updated').text(response.data.inactive.lastUpdateTime);
                $('#stat-advisors').text(response.data.uniqueAdvisors.total);
                $('#stat-advisors-updated').text(response.data.uniqueAdvisors.lastUpdateTime);
            }
        })
        .fail(function() {
            console.error('Failed to load statistics');
        });
}

// Load advisors for dropdown
function loadAdvisors() {
    $.get('{{ route("admin.academic_advisor_access.advisors") }}')
        .done(function(advisors) {
            const select = $('#advisor_id');
            select.empty().append('<option value="">Select Advisor</option>');
            advisors.forEach(function(advisor) {
                select.append(`<option value="${advisor.id}">${advisor.name}</option>`);
            });
        })
        .fail(function() {
            console.error('Failed to load advisors');
        });
}

// Load levels for dropdown
function loadLevels() {
    $.getJSON('{{ route("admin.levels.index") }}')
        .done(function(levels) {
            const select = $('#level_id');
            select.empty().append('<option value="">Select Level</option>');
            levels.forEach(function(level) {
                select.append(`<option value="${level.id}">${level.name}</option>`);
            });
        })
        .fail(function() {
            console.error('Failed to load levels');
        });
}

// Load programs for dropdown
function loadPrograms() {
    $.getJSON('{{ route("admin.programs.legacy.index") }}')
        .done(function(programs) {
            const select = $('#program_id');
            select.empty().append('<option value="">Select Program</option>');
            programs.forEach(function(program) {
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
    $('#accessModal').modal('show');
}

// Open edit access modal
function editAccess(id) {
    currentAccessId = id;
    $('#accessModal .modal-title').text('Edit Access Rule');
    
    $.get(`/admin/academic-advisor-access/${id}`)
        .done(function(response) {
            if (response.success) {
                const access = response.data;
                $('#advisor_id').val(access.advisor_id);
                $('#level_id').val(access.level_id);
                $('#program_id').val(access.program_id);
                $('#is_active').prop('checked', access.is_active);
                $('#accessModal').modal('show');
            }
        })
        .fail(function() {
            Swal.fire('Error', 'Failed to load access rule details', 'error');
        });
}

// View access details
function viewAccess(id) {
    $.get(`/admin/academic-advisor-access/${id}`)
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
                url: `/admin/academic-advisor-access/${id}`,
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
    
    const url = currentAccessId ? `/admin/academic-advisor-access/${currentAccessId}` : '{{ route("admin.academic_advisor_access.store") }}';
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
            Swal.fire('Success', response.message, 'success');
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