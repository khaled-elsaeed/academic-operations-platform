@extends('layouts.home')

@section('title', 'Role Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- ===== STATISTICS CARDS ===== --}}
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

    {{-- ===== PAGE HEADER & ACTION BUTTONS ===== --}}
    <x-ui.page-header 
        title="Roles"
        description="Manage user roles and assign permissions to control access levels."
        icon="bx bx-shield"
    >
        <button class="btn btn-primary mx-2" id="addRoleBtn">
            <i class="bx bx-plus me-1"></i> Add Role
        </button>
    </x-ui.page-header>

    {{-- ===== ADVANCED SEARCH SECTION ===== --}}
    <x-ui.advanced-search 
        title="Advanced Search" 
        formId="advancedRoleSearch" 
        collapseId="roleSearchCollapse"
        :collapsed="false"
    >
        <div class="col-md-6">
            <label for="search_role_name" class="form-label">Role Name:</label>
            <input type="text" class="form-control" id="search_role_name" placeholder="Role Name">
        </div>
        <div class="col-md-6">
            <label for="search_permission" class="form-label">Permission:</label>
            <input type="text" class="form-control" id="search_permission" placeholder="Permission">
        </div>
        <div class="w-100"></div>
        <button class="btn btn-outline-secondary mt-2 ms-2" id="clearRoleFiltersBtn" type="button">
            <i class="bx bx-x"></i> Clear Filters
        </button>
    </x-ui.advanced-search>

    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable 
        :headers="['Name', 'Permissions', 'Users Count', 'Created At', 'Actions']"
        :columns="[
            ['data' => 'name', 'name' => 'name'],
            ['data' => 'permissions', 'name' => 'permissions'],
            ['data' => 'users_count', 'name' => 'users_count'],
            ['data' => 'created_at', 'name' => 'created_at'],
            ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
        ]"
        :ajax-url="route('roles.datatable')"
        :table-id="'roles-table'"
        :filter-fields="['search_role_name','search_permission']"
    />

    {{-- ===== MODALS SECTION ===== --}}
    {{-- Permissions Modal --}}
    <x-ui.modal id="permissionsModal" title="Permissions" size="md" :scrollable="true" class="permissions-modal">
        <x-slot name="slot">
            <ul id="permissionsList" class="list-group"></ul>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </x-slot>
    </x-ui.modal>

    {{-- Add/Edit Role Modal --}}
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

    {{-- View Role Modal --}}
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
</div>
@endsection

@push('scripts')
<script>
// ===========================
// CONSTANTS AND CONFIGURATION
// ===========================
const ROUTES = {
  roles: {
    stats: '{{ route('roles.stats') }}',
    show: '{{ route('roles.show', ':id') }}',
    store: '{{ route('roles.store') }}',
    update: '{{ route('roles.update', ':id') }}',
    destroy: '{{ route('roles.destroy', ':id') }}',
    datatable: '{{ route('roles.datatable') }}',
    permissions: '{{ route('roles.permissions') }}'
  }
};

const SELECTORS = {
  rolesTable: '#roles-table',
  addRoleBtn: '#addRoleBtn',
  roleModal: '#roleModal',
  roleForm: '#roleForm',
  saveRoleBtn: '#saveRoleBtn',
  roleName: '#role_name',
  permissionsGrid: '#permissionsGrid',
  permissionsModal: '#permissionsModal',
  permissionsList: '#permissionsList',
  viewRoleModal: '#viewRoleModal',
  viewRoleName: '#view-role-name',
  viewRolePermissions: '#view-role-permissions',
  viewRoleUsers: '#view-role-users',
  viewRoleCreated: '#view-role-created',
};

// ===========================
// UTILITY FUNCTIONS
// ===========================
const Utils = {
  showError(message) {
    Swal.fire({ title: 'Error', html: message, icon: 'error' });
  },
  showSuccess(message) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: message, showConfirmButton: false, timer: 2500, timerProgressBar: true });
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
  fetchStats() { return this.request({ url: ROUTES.roles.stats, method: 'GET' }); },
  fetchPermissions() { return this.request({ url: ROUTES.roles.permissions, method: 'GET' }); },
  fetchRole(id) { return this.request({ url: Utils.replaceRouteId(ROUTES.roles.show, id), method: 'GET' }); },
  saveRole(data, id = null) {
    const url = id ? Utils.replaceRouteId(ROUTES.roles.update, id) : ROUTES.roles.store;
    const method = id ? 'PUT' : 'POST';
    return this.request({ url, method, data });
  },
  deleteRole(id) { return this.request({ url: Utils.replaceRouteId(ROUTES.roles.destroy, id), method: 'DELETE' }); }
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================
const StatsManager = {
  loadStats() {
    Utils.toggleLoadingState('roles', true);
    Utils.toggleLoadingState('permissions', true);
    Utils.toggleLoadingState('roles-users', true);
    ApiService.fetchStats()
      .done((response) => {
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
        Utils.toggleLoadingState('roles', false);
        Utils.toggleLoadingState('permissions', false);
        Utils.toggleLoadingState('roles-users', false);
      })
      .fail(() => {
        $('#roles-value, #permissions-value, #roles-users-value').text('N/A');
        $('#roles-last-updated, #permissions-last-updated, #roles-users-last-updated').text('N/A');
        Utils.toggleLoadingState('roles', false);
        Utils.toggleLoadingState('permissions', false);
        Utils.toggleLoadingState('roles-users', false);
        Utils.showError('Failed to load role statistics');
      });
  }
};

// ===========================
// PERMISSIONS GRID MANAGEMENT
// ===========================
const PermissionManager = {
  loadPermissions(selectedPermissions = []) {
    ApiService.fetchPermissions()
      .done((response) => {
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
          $(SELECTORS.permissionsGrid).html(html);
        }
      })
      .fail(() => {
        $(SELECTORS.permissionsGrid).html('<div class="text-danger">Failed to load permissions</div>');
      });
  }
};

// ===========================
// ROLE CRUD & MODALS
// ===========================
let currentRoleId = null;
const RoleManager = {
  handleAddRole() {
    $(document).on('click', SELECTORS.addRoleBtn, function() {
      currentRoleId = null;
      $('#roleModalTitle').text('Add Role');
      $(SELECTORS.roleForm)[0].reset();
      PermissionManager.loadPermissions([]);
      $(SELECTORS.roleModal).modal('show');
    });
  },
  handleEditRole() {
    $(document).on('click', '.editRoleBtn', function() {
      const roleId = $(this).data('id');
      currentRoleId = roleId;
      $('#roleModalTitle').text('Edit Role');
      ApiService.fetchRole(roleId)
        .done((response) => {
          if (response.success) {
            const role = response.data;
            $(SELECTORS.roleName).val(role.name);
            const selected = role.permissions.map(p => p.name);
            PermissionManager.loadPermissions(selected);
            $(SELECTORS.roleModal).modal('show');
          }
        })
        .fail(() => {
          Utils.showError('Failed to load role data');
        });
    });
  },
  handleViewRole() {
    $(document).on('click', '.viewRoleBtn', function() {
      const roleId = $(this).data('id');
      ApiService.fetchRole(roleId)
        .done((response) => {
          if (response.success) {
            const role = response.data;
            $(SELECTORS.viewRoleName).text(role.name);
            $(SELECTORS.viewRolePermissions).text(role.permissions.map(p => p.name).join(', ') || 'No permissions assigned');
            $(SELECTORS.viewRoleUsers).text(role.users_count);
            $(SELECTORS.viewRoleCreated).text(new Date(role.created_at).toLocaleString());
            $(SELECTORS.viewRoleModal).modal('show');
          }
        })
        .fail(() => {
          Utils.showError('Failed to load role data');
        });
    });
  },
  handleDeleteRole() {
    $(document).on('click', '.deleteRoleBtn', function() {
      const roleId = $(this).data('id');
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
          ApiService.deleteRole(roleId)
            .done(() => {
              $(SELECTORS.rolesTable).DataTable().ajax.reload(null, false);
              Utils.showSuccess('Role has been deleted.');
              StatsManager.loadStats();
            })
            .fail((xhr) => {
              const message = xhr.responseJSON?.message || 'Failed to delete role.';
              Utils.showError(message);
            });
        }
      });
    });
  },
  handleFormSubmit() {
    $(SELECTORS.roleForm).on('submit', function(e) {
      e.preventDefault();
      const formData = $(this).serialize();
      ApiService.saveRole(formData, currentRoleId)
        .done(() => {
          $(SELECTORS.roleModal).modal('hide');
          $(SELECTORS.rolesTable).DataTable().ajax.reload(null, false);
          Utils.showSuccess('Role has been saved successfully.');
          StatsManager.loadStats();
        })
        .fail((xhr) => {
          $(SELECTORS.roleModal).modal('hide');
          const message = xhr.responseJSON?.message || 'An error occurred. Please check your input.';
          Utils.showError(message);
        });
    });
  }
};

// ===========================
// SEARCH FUNCTIONALITY
// ===========================
const SearchManager = {
  initializeAdvancedSearch() {
    $('#search_role_name, #search_permission').on('keyup change', function() {
      $('#roles-table').DataTable().ajax.reload();
    });
    $('#clearRoleFiltersBtn').on('click', function() {
      $('#search_role_name, #search_permission').val('');
      $('#roles-table').DataTable().ajax.reload();
    });
  }
};

// ===========================
// MAIN APPLICATION
// ===========================
const RoleApp = {
  init() {
    StatsManager.loadStats();
    RoleManager.handleAddRole();
    RoleManager.handleEditRole();
    RoleManager.handleViewRole();
    RoleManager.handleDeleteRole();
    RoleManager.handleFormSubmit();
    SearchManager.initializeAdvancedSearch();
  }
};

$(document).ready(() => {
  RoleApp.init();
});
</script>
@endpush 