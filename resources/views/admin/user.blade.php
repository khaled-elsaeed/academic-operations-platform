@extends('layouts.home')

@section('title', 'User Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Users</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-users-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-users">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-users-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base bx bx-user icon-lg"></i>
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
              <span class="text-heading">Active Users</span>
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
                <i class="icon-base bx bx-user-check icon-lg"></i>
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
              <span class="text-heading">Admin Users</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-admin-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-admin">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-admin-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="icon-base bx bx-shield icon-lg"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Page Header and Actions -->
  <x-ui.page-header 
    title="Users"
    description="Manage all user accounts, assign roles, and control access permissions."
    icon="bx bx-user-circle"
  >
    <button class="btn btn-primary" onclick="openAddUserModal()">
      <i class="bx bx-plus me-1"></i> Add User
    </button>
  </x-ui.page-header>

  <!-- Users DataTable -->
  <x-ui.datatable 
    :headers="['Name', 'Email', 'Roles', 'Status', 'Created At', 'Actions']"
    :columns="[
      ['data' => 'name', 'name' => 'name'],
      ['data' => 'email', 'name' => 'email'],
      ['data' => 'roles', 'name' => 'roles'],
      ['data' => 'status', 'name' => 'status'],
      ['data' => 'created_at', 'name' => 'created_at'],
      ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
    ]"
    :ajax-url="route('admin.users.datatable')"
    :table-id="'users-table'"
    :filters="[]"
  />
</div>

<!-- Add/Edit User Modal -->
<x-ui.modal 
  id="userModal"
  title="Add/Edit User"
  size="lg"
  :scrollable="true"
  class="user-modal"
>
  <x-slot name="slot">
    <form id="userForm">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="first_name" class="form-label">First Name</label>
          <input type="text" id="first_name" name="first_name" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label for="last_name" class="form-label">Last Name</label>
          <input type="text" id="last_name" name="last_name" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" id="email" name="email" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" name="password" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label for="password_confirmation" class="form-label">Confirm Password</label>
          <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
          <label for="roles" class="form-label">Roles</label>
          <select id="roles" name="roles[]" class="form-select" multiple>
            <!-- Roles will be loaded dynamically -->
          </select>
        </div>
      </div>
    </form>
  </x-slot>
  <x-slot name="footer">
    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
      Close
    </button>
    <button type="submit" class="btn btn-primary" form="userForm">Save</button>
  </x-slot>
</x-ui.modal>

<!-- View User Modal -->
<x-ui.modal 
  id="viewUserModal"
  title="User Details"
  size="md"
  :scrollable="false"
  class="view-user-modal"
>
  <x-slot name="slot">
    <div class="row">
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Name:</label>
        <p id="view-user-name" class="mb-0"></p>
      </div>
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Email:</label>
        <p id="view-user-email" class="mb-0"></p>
      </div>
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Roles:</label>
        <p id="view-user-roles" class="mb-0"></p>
      </div>
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Status:</label>
        <p id="view-user-status" class="mb-0"></p>
      </div>
      <div class="col-12 mb-3">
        <label class="form-label fw-bold">Created At:</label>
        <p id="view-user-created" class="mb-0"></p>
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
let currentUserId = null;

// Initialize page
$(document).ready(function() {
    loadStats();
    loadRoles();
});

// Load statistics
function loadStats() {
    $.get('{{ route("admin.users.stats") }}')
        .done(function(response) {
            if (response.success) {
                $('#stat-users').text(response.data.total.total);
                $('#stat-users-updated').text(response.data.total.lastUpdateTime);
                $('#stat-active').text(response.data.active.total);
                $('#stat-active-updated').text(response.data.active.lastUpdateTime);
                $('#stat-admin').text(response.data.admin.total);
                $('#stat-admin-updated').text(response.data.admin.lastUpdateTime);
            }
        })
        .fail(function() {
            console.error('Failed to load stats');
        });
}

// Load roles for dropdown
function loadRoles() {
    $.get('{{ route("admin.users.roles") }}')
        .done(function(response) {
            if (response.success) {
                const rolesSelect = $('#roles');
                rolesSelect.empty();
                response.data.forEach(function(role) {
                    rolesSelect.append(`<option value="${role.name}">${role.name}</option>`);
                });
            }
        })
        .fail(function() {
            console.error('Failed to load roles');
        });
}

// Open add user modal
function openAddUserModal() {
    currentUserId = null;
    $('#userModalTitle').text('Add User');
    $('#userForm')[0].reset();
    $('#password').prop('required', true);
    $('#password_confirmation').prop('required', true);
    $('#userModal').modal('show');
}

// Open edit user modal
function editUser(userId) {
    currentUserId = userId;
    $('#userModalTitle').text('Edit User');
    $('#password').prop('required', false);
    $('#password_confirmation').prop('required', false);
    
    $.get(`/admin/users/${userId}`)
        .done(function(response) {
            if (response.success) {
                const user = response.data;
                $('#first_name').val(user.first_name);
                $('#last_name').val(user.last_name);
                $('#email').val(user.email);
                $('#roles').val(user.roles.map(role => role.name));
                $('#userModal').modal('show');
            }
        })
        .fail(function() {
            Swal.fire('Error', 'Failed to load user data', 'error');
        });
}

// View user details
function viewUser(userId) {
    $.get(`/admin/users/${userId}`)
        .done(function(response) {
            if (response.success) {
                const user = response.data;
                $('#view-user-name').text(user.first_name + ' ' + user.last_name);
                $('#view-user-email').text(user.email);
                $('#view-user-roles').text(user.roles.map(role => role.name).join(', ') || 'No roles assigned');
                $('#view-user-status').html(user.email_verified_at ? 
                    '<span class="badge bg-success">Active</span>' : 
                    '<span class="badge bg-warning">Pending</span>');
                $('#view-user-created').text(new Date(user.created_at).toLocaleString());
                $('#viewUserModal').modal('show');
            }
        })
        .fail(function() {
            Swal.fire('Error', 'Failed to load user data', 'error');
        });
}

// Delete user
function deleteUser(userId) {
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
                url: `/admin/users/${userId}`,
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
                Swal.fire('Error', response?.message || 'Failed to delete user', 'error');
            });
        }
    });
}

// Handle form submission
$('#userForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const url = currentUserId ? `/admin/users/${currentUserId}` : '{{ route("admin.users.store") }}';
    const method = currentUserId ? 'PUT' : 'POST';
    
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
            $('#userModal').modal('hide');
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
            Swal.fire('Error', response?.message || 'Failed to save user', 'error');
        }
    });
});

// Get DataTable instance
function getDataTable() {
    return $('#users-table').DataTable();
}
</script>
@endpush 