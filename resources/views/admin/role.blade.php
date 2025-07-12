@extends('layouts.home')

@section('title', 'Role Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 color="primary" icon="bx bx-shield" label="Total Roles" id="roles" />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 color="success" icon="bx bx-key" label="Total Permissions" id="permissions" />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 color="warning" icon="bx bx-group" label="Roles with Users" id="roles-users" />
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

    <!-- Permissions Modal -->
    <x-ui.modal id="permissionsModal" title="Permissions" size="md" :scrollable="false" class="permissions-modal">
        <x-slot name="slot">
            <ul id="permissionsList" class="list-group"></ul>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </x-slot>
    </x-ui.modal>
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
                <div class="col-md-12 mb-3">
                    <label for="role_name" class="form-label">Role Name</label>
                    <input type="text" id="role_name" name="name" class="form-control" required>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">Permissions</label>
                    <div class="d-flex justify-content-center">
                        <div id="permissionsGrid" class="row g-2 border rounded p-3 bg-light w-auto" style="min-width:320px; max-width:600px; margin:auto;">
                            <!-- Permissions grid will be rendered here -->
                        </div>
                    </div>
                    <div class="d-flex justify-content-center">
                        <small class="form-text text-muted">Select one or more actions per resource</small>
                    </div>
                </div>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
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
    // Initialize Select2 for permissions select in the role modal
    // Removed Select2 initialization as it's no longer needed
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
  toggleLoadingState('roles', true);
  toggleLoadingState('permissions', true);
  toggleLoadingState('roles-users', true);

  $.ajax({
    url: '{{ route("admin.roles.stats") }}',
    method: 'GET',
    success: function(response) {
      if (response.success) {
        $('#roles-value').text(response.data.total.total ?? '--');
        $('#roles-last-updated').text(response.data.total.lastUpdateTime ?? '--');
        $('#permissions-value').text(response.data.permissions.total ?? '--');
        $('#permissions-last-updated').text(response.data.permissions.lastUpdateTime ?? '--');
        const rolesWithUsers = response.data.rolesWithUsers.filter(role => role.users_count > 0).length;
        $('#roles-users-value').text(rolesWithUsers ?? '--');
        $('#roles-users-last-updated').text(response.data.total.lastUpdateTime ?? '--');
      } else {
        $('#roles-value, #permissions-value, #roles-users-value').text('N/A');
        $('#roles-last-updated, #permissions-last-updated, #roles-users-last-updated').text('N/A');
      }
      toggleLoadingState('roles', false);
      toggleLoadingState('permissions', false);
      toggleLoadingState('roles-users', false);
    },
    error: function() {
      $('#roles-value, #permissions-value, #roles-users-value').text('N/A');
      $('#roles-last-updated, #permissions-last-updated, #roles-users-last-updated').text('N/A');
      toggleLoadingState('roles', false);
      toggleLoadingState('permissions', false);
      toggleLoadingState('roles-users', false);
      Swal.fire('Error', 'Failed to load role statistics', 'error');
    }
  });
}

// Load permissions for dropdown
function loadPermissions(selectedPermissions = []) {
    $.get('{{ route("admin.roles.permissions") }}')
        .done(function(response) {
            if (response.success) {
                const permissions = response.data;
                // Group permissions by resource
                const grouped = {};
                permissions.forEach(function(permission) {
                    const [resource, action] = permission.name.split('.');
                    if (!grouped[resource]) grouped[resource] = [];
                    grouped[resource].push({ name: permission.name, action });
                });
                let html = '';
                Object.keys(grouped).forEach(function(resource) {
                    html += `<div class="col-12 mb-2 text-center"><strong>${resource.charAt(0).toUpperCase() + resource.slice(1)}</strong></div>`;
                    html += '<div class="col-12 mb-2 d-flex flex-wrap justify-content-center">';
                    grouped[resource].forEach(function(perm) {
                        const checked = selectedPermissions.includes(perm.name) ? 'checked' : '';
                        html += `<div class="form-check me-3">
                            <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" id="perm_${perm.name}" value="${perm.name}" ${checked}>
                            <label class="form-check-label" for="perm_${perm.name}">${perm.action.charAt(0).toUpperCase() + perm.action.slice(1)}</label>
                        </div>`;
                    });
                    html += '</div>';
                });
                $('#permissionsGrid').html(html);
            }
        })
        .fail(function() {
            $('#permissionsGrid').html('<div class="text-danger">Failed to load permissions</div>');
        });
}

// Open add role modal
function openAddRoleModal() {
    currentRoleId = null;
    $('#roleModalTitle').text('Add Role');
    $('#roleForm')[0].reset();
    loadPermissions([]);
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
                const selected = role.permissions.map(p => p.name);
                loadPermissions(selected);
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
    // Gather all checked permissions
    const permissions = [];
    $('#permissionsGrid').find('.permission-checkbox:checked').each(function() {
        permissions.push($(this).val());
    });
    const formData = new FormData(this);
    formData.delete('permissions[]'); // Remove old select
    permissions.forEach(p => formData.append('permissions[]', p));
    const isUpdate = !!currentRoleId;
    const url = isUpdate ? `/admin/roles/${currentRoleId}` : '{{ route("admin.roles.store") }}';
    const method = 'POST'; // Always POST
    if (isUpdate) {
        formData.set('_method', 'PUT'); // Spoof PUT
    }
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

$(document).on('click', '.show-permissions', function(e) {
    e.preventDefault();
    const roleId = $(this).data('role-id');
    const role = $(this).data('role');
    $('#permissionsModalLabel').text('Permissions for ' + role);
    $('#permissionsList').html('<li class="list-group-item">Loading...</li>');
    $('#permissionsModal').modal('show');
    $.get(`/admin/roles/${roleId}`)
        .done(function(response) {
            if (response.success) {
                const permissions = response.data.permissions;
                const list = permissions.length
                    ? permissions.map(p => `<li class="list-group-item">${p.name}</li>`).join('')
                    : '<li class="list-group-item">No permissions assigned</li>';
                $('#permissionsList').html(list);
            } else {
                $('#permissionsList').html('<li class="list-group-item">Failed to load permissions</li>');
            }
        })
        .fail(function() {
            $('#permissionsList').html('<li class="list-group-item">Failed to load permissions</li>');
        });
});
</script>
@endpush 