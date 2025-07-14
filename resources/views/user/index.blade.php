@extends('layouts.home')

@section('title', 'User Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- ===== STATISTICS CARDS ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 
                id="users"
                label="Total Users"
                color="primary"
                icon="bx bx-user"
            />
        </div>
    </div>

    {{-- ===== PAGE HEADER & ACTION BUTTONS ===== --}}
    <x-ui.page-header 
        title="Users"
        description="Manage all user accounts, assign roles, and control access permissions."
        icon="bx bx-user-circle"
    >
        @can('user.create')
            <button class="btn btn-primary" id="addUserBtn">
                <i class="bx bx-plus me-1"></i> Add User
            </button>
        @endcan
        <button class="btn btn-secondary ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#userSearchCollapse" aria-expanded="false" aria-controls="userSearchCollapse">
            <i class="bx bx-filter-alt me-1"></i> Search
        </button>
    </x-ui.page-header>

    {{-- ===== ADVANCED SEARCH SECTION ===== --}}
    <x-ui.advanced-search 
        title="Advanced Search" 
        formId="advancedUserSearch" 
        collapseId="userSearchCollapse"
        :collapsed="false"
    >
        <div class="col-md-4">
            <label for="search_name" class="form-label">Name:</label>
            <input type="text" class="form-control" id="search_name" placeholder="User Name">
        </div>
        <div class="col-md-4">
            <label for="search_email" class="form-label">Email:</label>
            <input type="text" class="form-control" id="search_email" placeholder="Email">
        </div>
        <div class="col-md-4">
            <label for="search_role" class="form-label">Role:</label>
            <select class="form-control" id="search_role">
                <option value="">Select Role</option>
                <!-- Options loaded via AJAX -->
            </select>
        </div>
        <div class="w-100"></div>
        <button class="btn btn-outline-secondary mt-2 ms-2" id="clearUserFiltersBtn" type="button">
            <i class="bx bx-x"></i> Clear Filters
        </button>
    </x-ui.advanced-search>

    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable 
        :headers="['Name', 'Email', 'Roles', 'Created At', 'Actions']"
        :columns="[
            ['data' => 'name', 'name' => 'name'],
            ['data' => 'email', 'name' => 'email'],
            ['data' => 'roles', 'name' => 'roles'],
            ['data' => 'created_at', 'name' => 'created_at'],
            ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
        ]"
        :ajax-url="route('users.datatable')"
        :table-id="'users-table'"
        :filter-fields="['search_name','search_email','search_role']"
        :filters="[]"
    />

    {{-- ===== MODALS SECTION ===== --}}
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
                        <div class="input-group">
                            <input type="password" id="password" name="password" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bx bx-hide" id="passwordIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirmation">
                                <i class="bx bx-hide" id="passwordConfirmationIcon"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="roles" class="form-label">Role</label>
                        <select id="roles" name="roles[]" class="form-select select2">
                            <option value="">Select Role</option>
                            <!-- Roles will be loaded dynamically -->
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select id="gender" name="gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Close
            </button>
            <button type="submit" class="btn btn-primary" id="saveUserBtn" form="userForm">
                <span class="spinner-border spinner-border-sm d-none" id="saveUserSpinner" role="status" aria-hidden="true"></span>
                <span id="saveUserBtnText">Save</span>
            </button>
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
                    <label class="form-label fw-bold">Created At:</label>
                    <p id="view-user-created" class="mb-0"></p>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
        </x-slot>
    </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
// ===========================
// CONSTANTS AND CONFIGURATION
// ===========================
const ROUTES = {
  users: {
    stats: '{{ route('users.stats') }}',
    store: '{{ route('users.store') }}',
    show: '{{ route('users.show', ':id') }}',
    update: '{{ route('users.update', ':id') }}',
    destroy: '{{ route('users.destroy', ':id') }}',
    roles: '{{ route('users.roles') }}',
    datatable: '{{ route('users.datatable') }}',
  }
};
const SELECTORS = {
  userForm: '#userForm',
  userModal: '#userModal',
  viewUserModal: '#viewUserModal',
  addUserBtn: '#addUserBtn',
  saveUserBtn: '#saveUserBtn',
  usersTable: '#users-table',
  searchName: '#search_name',
  searchEmail: '#search_email',
  searchRole: '#search_role',
  clearFiltersBtn: '#clearUserFiltersBtn',
  rolesSelect: '#roles',
  togglePassword: '#togglePassword',
  togglePasswordConfirmation: '#togglePasswordConfirmation',
  password: '#password',
  passwordConfirmation: '#password_confirmation',
  passwordIcon: '#passwordIcon',
  passwordConfirmationIcon: '#passwordConfirmationIcon',
};

// ===========================
// UTILITY FUNCTIONS
// ===========================
const Utils = {
  showSuccess(message) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: message, showConfirmButton: false, timer: 2500, timerProgressBar: true });
  },
  showError(message) {
    Swal.fire({ title: 'Error', html: message, icon: 'error' });
  },
  toggleLoadingState(elementId, isLoading) {
    const $value = $(`#${elementId}-value`);
    const $loader = $(`#${elementId}-loader`);
    const $updated = $(`#${elementId}-last-updated`);
    const $updatedLoader = $(`#${elementId}-last-updated-loader`);
    if (isLoading) {
      $value.addClass('d-none'); $loader.removeClass('d-none'); $updated.addClass('d-none'); $updatedLoader.removeClass('d-none');
    } else {
      $value.removeClass('d-none'); $loader.addClass('d-none'); $updated.removeClass('d-none'); $updatedLoader.addClass('d-none');
    }
  },
  replaceRouteId(route, id) {
    return route.replace(':id', id);
  }
};

// ===========================
// API SERVICE LAYER
// ===========================
const ApiService = {
  request(options) { return $.ajax(options); },
  fetchRoles() { return this.request({ url: ROUTES.users.roles, method: 'GET' }); },
  fetchStats() { return this.request({ url: ROUTES.users.stats, method: 'GET' }); },
  fetchUser(id) { return this.request({ url: Utils.replaceRouteId(ROUTES.users.show, id), method: 'GET' }); },
  saveUser(formData, id = null) {
    const url = id ? Utils.replaceRouteId(ROUTES.users.update, id) : ROUTES.users.store;
    const method = 'POST';
    if (id) formData.set('_method', 'PUT');
    return this.request({ url, type: method, data: formData, processData: false, contentType: false, headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
  },
  deleteUser(id) {
    return this.request({ url: Utils.replaceRouteId(ROUTES.users.destroy, id), type: 'DELETE', headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
  }
};

// ===========================
// DROPDOWN MANAGEMENT
// ===========================
const DropdownManager = {
  loadRoles(selector = SELECTORS.rolesSelect, selected = null, parent = $(SELECTORS.userModal)) {
    return ApiService.fetchRoles().done(function(response) {
      if (response.success) {
        const $select = $(selector);
        $select.empty().append('<option value="">Select Role</option>');
        response.data.forEach(function(role) {
          $select.append($('<option>', { value: role.name, text: role.name }));
        });
        if (selected) $select.val(selected);
        $select.trigger('change');
        if (!$select.hasClass('select2-hidden-accessible')) {
          $select.select2({ theme: 'bootstrap-5', placeholder: 'Select Role', allowClear: true, width: '100%', dropdownParent: parent });
        }
      }
    });
  },
  loadRolesForFilter() {
    return ApiService.fetchRoles().done(function(response) {
      if (response.success) {
        const $roleSelect = $(SELECTORS.searchRole);
        $roleSelect.empty().append('<option value="">Select Role</option>');
        response.data.forEach(function(role) {
          $roleSelect.append($('<option>', { value: role.name, text: role.name }));
        });
        $roleSelect.select2({ theme: 'bootstrap-5', placeholder: 'Select Role', allowClear: true, width: '100%', dropdownParent: $('#userSearchCollapse') });
      }
    });
  }
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================
const StatsManager = {
  loadUserStats() {
    Utils.toggleLoadingState('users', true);
    Utils.toggleLoadingState('admin', true);
    Utils.toggleLoadingState('active-users', true);
    ApiService.fetchStats().done(function(response) {
      if (response.success) {
        $('#users-value').text(response.data.total.total ?? '--');
        $('#users-last-updated').text(response.data.total.lastUpdateTime ?? '--');
      } else {
        $('#users-value').text('N/A');
        $('#users-last-updated').text('N/A');
      }
      Utils.toggleLoadingState('users', false);
    }).fail(function() {
      $('#users-value').text('N/A');
      $('#users-last-updated').text('N/A');
      Utils.toggleLoadingState('users', false);
      Utils.showError('Failed to load user statistics');
    });
  }
};

// ===========================
// USER CRUD & MODALS
// ===========================
const UserManager = {
  currentUserId: null,
  handleAddUser() {
    $(document).on('click', SELECTORS.addUserBtn, function() {
      UserManager.currentUserId = null;
      $('#userModalTitle').text('Add User');
      $(SELECTORS.userForm)[0].reset();
      $('#password').prop('required', true);
      $('#password_confirmation').prop('required', true);
      $('#gender').val('');
      if ($(SELECTORS.rolesSelect).hasClass('select2-hidden-accessible')) {
        $(SELECTORS.rolesSelect).select2('destroy');
      }
      DropdownManager.loadRoles();
      PasswordToggleManager.resetPasswordFields();
      $(SELECTORS.userModal).modal('show');
    });
  },
  handleEditUser() {
    $(document).on('click', '.editUserBtn', function() {
      const userId = $(this).data('id');
      UserManager.currentUserId = userId;
      $('#userModalTitle').text('Edit User');
      $('#password').prop('required', false);
      $('#password_confirmation').prop('required', false);
      ApiService.fetchUser(userId).done(function(response) {
        if (response.success) {
          const user = response.data;
          $('#first_name').val(user.first_name);
          $('#last_name').val(user.last_name);
          $('#email').val(user.email);
          $('#gender').val(user.gender);
          if ($(SELECTORS.rolesSelect).hasClass('select2-hidden-accessible')) {
            $(SELECTORS.rolesSelect).select2('destroy');
          }
          DropdownManager.loadRoles(SELECTORS.rolesSelect, user.roles.length > 0 ? user.roles[0].name : '', $(SELECTORS.userModal));
          setTimeout(function() {
            const selectedRole = user.roles.length > 0 ? user.roles[0].name : '';
            $(SELECTORS.rolesSelect).val(selectedRole).trigger('change');
          }, 100);
          PasswordToggleManager.resetPasswordFields();
          $(SELECTORS.userModal).modal('show');
        }
      }).fail(function() {
        Utils.showError('Failed to load user data');
      });
    });
  },
  handleViewUser() {
    $(document).on('click', '.viewUserBtn', function() {
      const userId = $(this).data('id');
      ApiService.fetchUser(userId).done(function(response) {
        if (response.success) {
          const user = response.data;
          $('#view-user-name').text(user.first_name + ' ' + user.last_name);
          $('#view-user-email').text(user.email);
          $('#view-user-roles').text(user.roles.length > 0 ? user.roles[0].name : 'No role assigned');
          $('#view-user-created').text(new Date(user.created_at).toLocaleString());
          $(SELECTORS.viewUserModal).modal('show');
        }
      }).fail(function() {
        Utils.showError('Failed to load user data');
      });
    });
  },
  handleDeleteUser() {
    $(document).on('click', '.deleteUserBtn', function() {
      const userId = $(this).data('id');
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
          ApiService.deleteUser(userId).done(function(response) {
            if (response.success) {
              Swal.fire('Deleted!', response.message, 'success');
              $(SELECTORS.usersTable).DataTable().ajax.reload();
              StatsManager.loadUserStats();
            } else {
              Utils.showError(response.message);
            }
          }).fail(function(xhr) {
            const response = xhr.responseJSON;
            Utils.showError(response?.message || 'Failed to delete user');
          });
        }
      });
    });
  },
  handleFormSubmit() {
    $(SELECTORS.userForm).on('submit', function(e) {
      e.preventDefault();
      // Show loading state
      $('#saveUserBtn').prop('disabled', true);
      $('#saveUserSpinner').removeClass('d-none');
      $('#saveUserBtnText').text('Saving...');
      const formData = new FormData(this);
      formData.set('gender', $('#gender').val());
      const isUpdate = !!UserManager.currentUserId;
      ApiService.saveUser(formData, isUpdate ? UserManager.currentUserId : null)
        .done(function(response) {
          if (response.success) {
            Utils.showSuccess(response.message);
            $(SELECTORS.userModal).modal('hide');
            $(SELECTORS.usersTable).DataTable().ajax.reload();
            StatsManager.loadUserStats();
          } else {
            Utils.showError(response.message);
          }
        })
        .fail(function(xhr) {
          $(SELECTORS.userModal).modal('hide');
          const response = xhr.responseJSON;
          if (response && response.errors) {
            let errorMessage = '';
            Object.values(response.errors).forEach(function(errors) {
              errorMessage += errors.join('\n') + '\n';
            });
            Utils.showError(errorMessage);
          } else {
            Utils.showError(response?.message || 'Failed to save user');
          }
        })
        .always(function() {
          // Hide loading state
          $('#saveUserBtn').prop('disabled', false);
          $('#saveUserSpinner').addClass('d-none');
          $('#saveUserBtnText').text('Save');
        });
    });
  }
};

// ===========================
// PASSWORD TOGGLE FUNCTIONALITY
// ===========================
const PasswordToggleManager = {
  initializePasswordToggles() {
    // Toggle password visibility using event delegation
    $(document).on('click', SELECTORS.togglePassword, function() {
      const $password = $(SELECTORS.password);
      const $icon = $(SELECTORS.passwordIcon);
      
      if ($password.attr('type') === 'password') {
        $password.attr('type', 'text');
        $icon.removeClass('bx-hide').addClass('bx-show');
      } else {
        $password.attr('type', 'password');
        $icon.removeClass('bx-show').addClass('bx-hide');
      }
    });

    // Toggle password confirmation visibility using event delegation
    $(document).on('click', SELECTORS.togglePasswordConfirmation, function() {
      const $passwordConfirmation = $(SELECTORS.passwordConfirmation);
      const $icon = $(SELECTORS.passwordConfirmationIcon);
      
      if ($passwordConfirmation.attr('type') === 'password') {
        $passwordConfirmation.attr('type', 'text');
        $icon.removeClass('bx-hide').addClass('bx-show');
      } else {
        $passwordConfirmation.attr('type', 'password');
        $icon.removeClass('bx-show').addClass('bx-hide');
      }
    });
  },
  
  resetPasswordFields() {
    // Reset password fields to hidden state when modal is shown
    $(SELECTORS.password).attr('type', 'password');
    $(SELECTORS.passwordConfirmation).attr('type', 'password');
    $(SELECTORS.passwordIcon).removeClass('bx-show').addClass('bx-hide');
    $(SELECTORS.passwordConfirmationIcon).removeClass('bx-show').addClass('bx-hide');
  }
};

// ===========================
// SEARCH FUNCTIONALITY
// ===========================
const SearchManager = {
  initializeAdvancedSearch() {
    DropdownManager.loadRolesForFilter();
    $(SELECTORS.searchName + ', ' + SELECTORS.searchEmail + ', ' + SELECTORS.searchRole).on('keyup change', function() {
      $(SELECTORS.usersTable).DataTable().ajax.reload();
    });
    $(SELECTORS.clearFiltersBtn).on('click', function() {
      $(SELECTORS.searchName + ', ' + SELECTORS.searchEmail).val('');
      $(SELECTORS.searchRole).val('').trigger('change');
      $(SELECTORS.usersTable).DataTable().ajax.reload();
    });
  }
};

// ===========================
// MAIN APPLICATION
// ===========================
const UserManagementApp = {
  init() {
    StatsManager.loadUserStats();
    UserManager.handleAddUser();
    UserManager.handleEditUser();
    UserManager.handleViewUser();
    UserManager.handleDeleteUser();
    UserManager.handleFormSubmit();
    SearchManager.initializeAdvancedSearch();
    PasswordToggleManager.initializePasswordToggles();
    // Modal cleanup
    $(SELECTORS.userModal).on('hidden.bs.modal', function () {
      if ($(SELECTORS.rolesSelect).hasClass('select2-hidden-accessible')) {
        $(SELECTORS.rolesSelect).select2('destroy');
      }
    });
  }
};

// ===========================
// DOCUMENT READY
// ===========================
$(document).ready(function() {
  UserManagementApp.init();
});
</script>
@endpush 