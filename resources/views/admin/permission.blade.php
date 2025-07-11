@extends('layouts.home')

@section('title', 'Permission Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Permissions</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-permissions-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-permissions">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-permissions-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base bx bx-key icon-lg"></i>
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
              <span class="text-heading">Total Roles</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-roles-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-roles">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-roles-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="icon-base bx bx-shield icon-lg"></i>
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
              <span class="text-heading">Permissions with Roles</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-permissions-roles-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-permissions-roles">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-permissions-roles-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="icon-base bx bx-link icon-lg"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Page Header and Actions -->
  <x-ui.page-header 
    title="Permissions"
    description="Manage system permissions and control access to different features."
    icon="bx bx-key"
  >
    <button class="btn btn-outline-primary me-2" onclick="openBulkCreateModal()">
      <i class="bx bx-plus me-1"></i> Bulk Create
    </button>
    <button class="btn btn-primary" onclick="openAddPermissionModal()">
      <i class="bx bx-plus me-1"></i> Add Permission
    </button>
  </x-ui.page-header>

  <!-- Permissions DataTable -->
  <x-ui.datatable 
    :headers="['Name', 'Guard Name', 'Roles', 'Roles Count', 'Created At', 'Actions']"
    :columns="[
      ['data' => 'name', 'name' => 'name'],
      ['data' => 'guard_name', 'name' => 'guard_name'],
      ['data' => 'roles', 'name' => 'roles'],
      ['data' => 'roles_count', 'name' => 'roles_count'],
      ['data' => 'created_at', 'name' => 'created_at'],
      ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
    ]"
    :ajax-url="route('admin.permissions.datatable')"
    :table-id="'permissions-table'"
    :filters="[]"
  />
</div>

<!-- Add/Edit Permission Modal -->
<x-ui.modal 
  id="permissionModal"
  title="Add/Edit Permission"
  size="md"
  :scrollable="false"
  class="permission-modal"
>
  <x-slot name="slot">
    <form id="permissionForm">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="permission_name" class="form-label">Permission Name</label>
          <input type="text" id="permission_name" name="name" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label for="guard_name" class="form-label">Guard Name</label>
          <input type="text" id="guard_name" name="guard_name" class="form-control" value="web" required>
          <small class="form-text text-muted">Usually "web" for web applications</small>
        </div>
      </div>
    </form>
  </x-slot>
  <x-slot name="footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
      Close
    </button>
    <button type="submit" class="btn btn-primary" form="permissionForm">Save</button>
  </x-slot>
</x-ui.modal>

<!-- Bulk Create Permissions Modal -->
<x-ui.modal 
  id="bulkCreateModal"
  title="Bulk Create Permissions"
  size="lg"
  :scrollable="true"
  class="bulk-create-modal"
>
  <x-slot name="slot">
    <form id="bulkCreateForm">
      <div class="row">
        <div class="col-12 mb-3">
          <label for="bulk_permissions" class="form-label">Permission Names (one per line)</label>
          <textarea id="bulk_permissions" name="permissions" class="form-control" rows="10" placeholder="user.create&#10;user.edit&#10;user.delete&#10;user.view"></textarea>
          <small class="form-text text-muted">Enter permission names, one per line. Existing permissions will be skipped.</small>
        </div>
      </div>
    </form>
  </x-slot>
  <x-slot name="footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
    <button type="submit" class="btn btn-primary" form="bulkCreateForm">Create Permissions</button>
  </x-slot>
</x-ui.modal>

<!-- View Permission Modal -->
<x-ui.modal 
  id="viewPermissionModal"
  title="Permission Details"
  size="md"
  :scrollable="false"
  class="view-permission-modal"
>
  <x-slot name="slot">
    <div class="row">
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Name:</label>
        <p id="view-permission-name" class="mb-0"></p>
      </div>
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Guard Name:</label>
        <p id="view-permission-guard" class="mb-0"></p>
      </div>
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Roles:</label>
        <p id="view-permission-roles" class="mb-0"></p>
      </div>
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Roles Count:</label>
        <p id="view-permission-roles-count" class="mb-0"></p>
      </div>
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Created At:</label>
        <p id="view-permission-created" class="mb-0"></p>
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
let currentPermissionId = null;

// Initialize page
$(document).ready(function() {
    loadStats();
});

// Load statistics
function loadStats() {
    $.get('{{ route("admin.permissions.stats") }}')
        .done(function(response) {
            if (response.success) {
                $('#stat-permissions').text(response.data.total.total);
                $('#stat-permissions-updated').text(response.data.total.lastUpdateTime);
                $('#stat-roles').text(response.data.roles.total);
                $('#stat-roles-updated').text(response.data.roles.lastUpdateTime);
                
                // Count permissions with roles
                const permissionsWithRoles = response.data.permissionsWithRoles.filter(permission => permission.roles_count > 0).length;
                $('#stat-permissions-roles').text(permissionsWithRoles);
                $('#stat-permissions-roles-updated').text(response.data.total.lastUpdateTime);
            }
        })
        .fail(function() {
            console.error('Failed to load stats');
        });
}

// Open add permission modal
function openAddPermissionModal() {
    currentPermissionId = null;
    $('#permissionModalTitle').text('Add Permission');
    $('#permissionForm')[0].reset();
    $('#guard_name').val('web');
    $('#permissionModal').modal('show');
}

// Open bulk create modal
function openBulkCreateModal() {
    $('#bulkCreateForm')[0].reset();
    $('#bulkCreateModal').modal('show');
}

// Open edit permission modal
function editPermission(permissionId) {
    currentPermissionId = permissionId;
    $('#permissionModalTitle').text('Edit Permission');
    
    $.get(`/admin/permissions/${permissionId}`)
        .done(function(response) {
            if (response.success) {
                const permission = response.data;
                $('#permission_name').val(permission.name);
                $('#guard_name').val(permission.guard_name);
                $('#permissionModal').modal('show');
            }
        })
        .fail(function() {
            Swal.fire('Error', 'Failed to load permission data', 'error');
        });
}

// View permission details
function viewPermission(permissionId) {
    $.get(`/admin/permissions/${permissionId}`)
        .done(function(response) {
            if (response.success) {
                const permission = response.data;
                $('#view-permission-name').text(permission.name);
                $('#view-permission-guard').text(permission.guard_name);
                $('#view-permission-roles').text(permission.roles.map(role => role.name).join(', ') || 'No roles assigned');
                $('#view-permission-roles-count').text(permission.roles.length);
                $('#view-permission-created').text(new Date(permission.created_at).toLocaleString());
                $('#viewPermissionModal').modal('show');
            }
        })
        .fail(function() {
            Swal.fire('Error', 'Failed to load permission data', 'error');
        });
}

// Delete permission
function deletePermission(permissionId) {
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
                url: `/admin/permissions/${permissionId}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            })
            .done(function(response) {
                if (response.success) {
                    Swal.fire('Deleted!', response.message, 'success');
                    getDataTable().ajax.reload();
                    loadStats();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            })
            .fail(function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire('Error', response?.message || 'Failed to delete permission', 'error');
            });
        }
    });
}

// Handle permission form submission
$('#permissionForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const url = currentPermissionId ? `/admin/permissions/${currentPermissionId}` : '{{ route("admin.permissions.store") }}';
    const method = currentPermissionId ? 'PUT' : 'POST';
    
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
            $('#permissionModal').modal('hide');
            getDataTable().ajax.reload();
            loadStats();
        } else {
            Swal.fire('Error', response.message, 'error');
        }
    })
    .fail(function(xhr) {
        const response = xhr.responseJSON;
        if (response.errors) {
            let errorMessage = '';
            Object.values(response.errors).forEach(function(errors) {
                errorMessage += errors.join('\n') + '\n';
            });
            Swal.fire('Validation Error', errorMessage, 'error');
        } else {
            Swal.fire('Error', response?.message || 'Failed to save permission', 'error');
        }
    });
});

// Handle bulk create form submission
$('#bulkCreateForm').on('submit', function(e) {
    e.preventDefault();
    
    const permissionsText = $('#bulk_permissions').val();
    const permissions = permissionsText.split('\n').filter(permission => permission.trim() !== '');
    
    if (permissions.length === 0) {
        Swal.fire('Error', 'Please enter at least one permission name', 'error');
        return;
    }
    
    $.ajax({
        url: '{{ route("admin.permissions.bulk-create") }}',
        type: 'POST',
        data: {
            permissions: permissions,
            _token: $('meta[name="csrf-token"]').attr('content')
        }
    })
    .done(function(response) {
        if (response.success) {
            Swal.fire('Success', response.message, 'success');
            $('#bulkCreateModal').modal('hide');
            getDataTable().ajax.reload();
            loadStats();
        } else {
            Swal.fire('Error', response.message, 'error');
        }
    })
    .fail(function(xhr) {
        const response = xhr.responseJSON;
        if (response.errors) {
            let errorMessage = '';
            Object.values(response.errors).forEach(function(errors) {
                errorMessage += errors.join('\n') + '\n';
            });
            Swal.fire('Validation Error', errorMessage, 'error');
        } else {
            Swal.fire('Error', response?.message || 'Failed to create permissions', 'error');
        }
    });
});

// Get DataTable instance
function getDataTable() {
    return $('#permissions-table').DataTable();
}
</script>
@endpush 