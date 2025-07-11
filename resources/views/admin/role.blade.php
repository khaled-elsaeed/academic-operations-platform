@extends('layouts.home')

@section('title', 'Role Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
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
              <span class="avatar-initial rounded bg-label-primary">
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
              <span class="avatar-initial rounded bg-label-success">
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
              <span class="text-heading">Roles with Users</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-roles-users-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-roles-users">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-roles-users-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
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
    title="Roles"
    description="Manage user roles and assign permissions to control access levels."
    icon="bx bx-shield"
  >
    <button class="btn btn-primary" onclick="openAddRoleModal()">
      <i class="bx bx-plus me-1"></i> Add Role
    </button>
  </x-ui.page-header>

  <!-- Roles DataTable -->
  <x-ui.datatable 
    :headers="['Name', 'Permissions', 'Users Count', 'Created At', 'Actions']"
    :columns="[
      ['data' => 'name', 'name' => 'name'],
      ['data' => 'permissions', 'name' => 'permissions'],
      ['data' => 'users_count', 'name' => 'users_count'],
      ['data' => 'created_at', 'name' => 'created_at'],
      ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
    ]"
    :ajax-url="route('admin.roles.datatable')"
    :table-id="'roles-table'"
    :filters="[]"
  />
</div>

<!-- Add/Edit Role Modal -->
<x-ui.modal 
  id="roleModal"
  title="Add/Edit Role"
  size="lg"
  :scrollable="true"
  class="role-modal"
>
  <x-slot name="slot">
    <form id="roleForm">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="role_name" class="form-label">Role Name</label>
          <input type="text" id="role_name" name="name" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label for="role_permissions" class="form-label">Permissions</label>
          <select id="role_permissions" name="permissions[]" class="form-select" multiple>
            <!-- Permissions will be loaded dynamically -->
          </select>
          <small class="form-text text-muted">Hold Ctrl (or Cmd on Mac) to select multiple permissions</small>
        </div>
      </div>
    </form>
  </x-slot>
  <x-slot name="footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
      Close
    </button>
    <button type="submit" class="btn btn-primary" form="roleForm">Save</button>
  </x-slot>
</x-ui.modal>

<!-- View Role Modal -->
<x-ui.modal 
  id="viewRoleModal"
  title="Role Details"
  size="md"
  :scrollable="false"
  class="view-role-modal"
>
  <x-slot name="slot">
    <div class="row">
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Name:</label>
        <p id="view-role-name" class="mb-0"></p>
      </div>
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Permissions:</label>
        <p id="view-role-permissions" class="mb-0"></p>
      </div>
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Users Count:</label>
        <p id="view-role-users" class="mb-0"></p>
      </div>
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Created At:</label>
        <p id="view-role-created" class="mb-0"></p>
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
let currentRoleId = null;

// Initialize page
$(document).ready(function() {
    loadStats();
    loadPermissions();
});

// Load statistics
function loadStats() {
    $.get('{{ route("admin.roles.stats") }}')
        .done(function(response) {
            if (response.success) {
                $('#stat-roles').text(response.data.total.total);
                $('#stat-roles-updated').text(response.data.total.lastUpdateTime);
                $('#stat-permissions').text(response.data.permissions.total);
                $('#stat-permissions-updated').text(response.data.permissions.lastUpdateTime);
                
                // Count roles with users
                const rolesWithUsers = response.data.rolesWithUsers.filter(role => role.users_count > 0).length;
                $('#stat-roles-users').text(rolesWithUsers);
                $('#stat-roles-users-updated').text(response.data.total.lastUpdateTime);
            }
        })
        .fail(function() {
            console.error('Failed to load stats');
        });
}

// Load permissions for dropdown
function loadPermissions() {
    $.get('{{ route("admin.roles.permissions") }}')
        .done(function(response) {
            if (response.success) {
                const permissionsSelect = $('#role_permissions');
                permissionsSelect.empty();
                response.data.forEach(function(permission) {
                    permissionsSelect.append(`<option value="${permission.name}">${permission.name}</option>`);
                });
            }
        })
        .fail(function() {
            console.error('Failed to load permissions');
        });
}

// Open add role modal
function openAddRoleModal() {
    currentRoleId = null;
    $('#roleModalTitle').text('Add Role');
    $('#roleForm')[0].reset();
    $('#roleModal').modal('show');
}

// Open edit role modal
function editRole(roleId) {
    currentRoleId = roleId;
    $('#roleModalTitle').text('Edit Role');
    
    $.get(`/admin/roles/${roleId}`)
        .done(function(response) {
            if (response.success) {
                const role = response.data;
                $('#role_name').val(role.name);
                $('#role_permissions').val(role.permissions.map(permission => permission.name));
                $('#roleModal').modal('show');
            }
        })
        .fail(function() {
            Swal.fire('Error', 'Failed to load role data', 'error');
        });
}

// View role details
function viewRole(roleId) {
    $.get(`/admin/roles/${roleId}`)
        .done(function(response) {
            if (response.success) {
                const role = response.data;
                $('#view-role-name').text(role.name);
                $('#view-role-permissions').text(role.permissions.map(permission => permission.name).join(', ') || 'No permissions assigned');
                $('#view-role-users').text(role.users.length);
                $('#view-role-created').text(new Date(role.created_at).toLocaleString());
                $('#viewRoleModal').modal('show');
            }
        })
        .fail(function() {
            Swal.fire('Error', 'Failed to load role data', 'error');
        });
}

// Delete role
function deleteRole(roleId) {
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
                url: `/admin/roles/${roleId}`,
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
                Swal.fire('Error', response?.message || 'Failed to delete role', 'error');
            });
        }
    });
}

// Handle form submission
$('#roleForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const url = currentRoleId ? `/admin/roles/${currentRoleId}` : '{{ route("admin.roles.store") }}';
    const method = currentRoleId ? 'PUT' : 'POST';
    
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
            $('#roleModal').modal('hide');
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
            Swal.fire('Error', response?.message || 'Failed to save role', 'error');
        }
    });
});

// Get DataTable instance
function getDataTable() {
    return $('#roles-table').DataTable();
}
</script>
@endpush 