@extends('layouts.home')

@section('title', 'Permission Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 color="primary" icon="bx bx-key" label="Total Permissions" id="permissions" />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 color="success" icon="bx bx-shield" label="Total Roles" id="roles" />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 color="warning" icon="bx bx-link" label="Permissions with Roles" id="permissions-roles" />
        </div>
    </div>

    <!-- Page Header and Actions -->
    <x-ui.page-header 
        title="Permissions"
        description="Manage system permissions and control access to different features."
        icon="bx bx-key"
    />

    {{-- ===== ADVANCED SEARCH SECTION ===== --}}
    <x-ui.advanced-search 
        title="Advanced Search" 
        formId="advancedPermissionSearch" 
        collapseId="permissionSearchCollapse"
        :collapsed="false"
    >
        <div class="col-md-4">
            <label for="search_permission_name" class="form-label">Permission Name:</label>
            <input type="text" class="form-control" id="search_permission_name" placeholder="Permission Name">
        </div>
        <div class="col-md-4">
            <label for="search_guard_name" class="form-label">Guard Name:</label>
            <input type="text" class="form-control" id="search_guard_name" placeholder="Guard Name">
        </div>
        <div class="col-md-4">
            <label for="search_role" class="form-label">Role:</label>
            <input type="text" class="form-control" id="search_role" placeholder="Role">
        </div>
        <div class="w-100"></div>
        <button class="btn btn-outline-secondary mt-2 ms-2" id="clearPermissionFiltersBtn" type="button">
            <i class="bx bx-x"></i> Clear Filters
        </button>
    </x-ui.advanced-search>

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
        :ajax-url="route('permissions.datatable')"
        :table-id="'permissions-table'"
        :filter-fields="['search_permission_name','search_guard_name','search_role']"
        :filters="[]"
    />
</div>

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
// ===========================
// CONSTANTS AND CONFIGURATION
// ===========================
const ROUTES = {
  permissions: {
    stats: '{{ route('permissions.stats') }}',
    show: '{{ route('permissions.show', ':id') }}',
    datatable: '{{ route('permissions.datatable') }}'
  }
};

const SELECTORS = {
  permissionsTable: '#permissions-table',
  viewPermissionModal: '#viewPermissionModal',
  viewPermissionName: '#view-permission-name',
  viewPermissionGuard: '#view-permission-guard',
  viewPermissionRoles: '#view-permission-roles',
  viewPermissionRolesCount: '#view-permission-roles-count',
  viewPermissionCreated: '#view-permission-created',
};

// ===========================
// UTILITY FUNCTIONS
// ===========================
const Utils = {
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
  fetchStats() { return this.request({ url: ROUTES.permissions.stats, method: 'GET' }); },
  fetchPermission(id) { return this.request({ url: Utils.replaceRouteId(ROUTES.permissions.show, id), method: 'GET' }); }
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================
const StatsManager = {
  loadStats() {
    Utils.toggleLoadingState('permissions', true);
    Utils.toggleLoadingState('roles', true);
    Utils.toggleLoadingState('permissions-roles', true);
    ApiService.fetchStats()
      .done((response) => {
        if (response.success) {
          $('#permissions-value').text(response.data.total.total ?? '--');
          $('#permissions-last-updated').text(response.data.total.lastUpdateTime ?? '--');
          $('#roles-value').text(response.data.roles.total ?? '--');
          $('#roles-last-updated').text(response.data.roles.lastUpdateTime ?? '--');
          const permissionsWithRoles = response.data.permissionsWithRoles.filter(permission => permission.roles_count > 0).length;
          $('#permissions-roles-value').text(permissionsWithRoles ?? '--');
          $('#permissions-roles-last-updated').text(response.data.total.lastUpdateTime ?? '--');
        } else {
          $('#permissions-value, #roles-value, #permissions-roles-value').text('N/A');
          $('#permissions-last-updated, #roles-last-updated, #permissions-roles-last-updated').text('N/A');
        }
        Utils.toggleLoadingState('permissions', false);
        Utils.toggleLoadingState('roles', false);
        Utils.toggleLoadingState('permissions-roles', false);
      })
      .fail(() => {
        $('#permissions-value, #roles-value, #permissions-roles-value').text('N/A');
        $('#permissions-last-updated, #roles-last-updated, #permissions-roles-last-updated').text('N/A');
        Utils.toggleLoadingState('permissions', false);
        Utils.toggleLoadingState('roles', false);
        Utils.toggleLoadingState('permissions-roles', false);
        Utils.showError('Failed to load permission statistics');
      });
  }
};

// ===========================
// PERMISSION VIEW MODAL
// ===========================
const PermissionManager = {
  handleViewPermission() {
    $(document).on('click', '.viewPermissionBtn', function() {
      const permissionId = $(this).data('id');
      ApiService.fetchPermission(permissionId)
        .done((response) => {
          if (response.success) {
            const permission = response.data;
            $(SELECTORS.viewPermissionName).text(permission.name);
            $(SELECTORS.viewPermissionGuard).text(permission.guard_name);
            $(SELECTORS.viewPermissionRoles).text(permission.roles.map(role => role.name).join(', ') || 'No roles assigned');
            $(SELECTORS.viewPermissionRolesCount).text(permission.roles.length);
            $(SELECTORS.viewPermissionCreated).text(new Date(permission.created_at).toLocaleString());
            $(SELECTORS.viewPermissionModal).modal('show');
          }
        })
        .fail(() => {
          Utils.showError('Failed to load permission data');
        });
    });
  }
};

// ===========================
// SEARCH FUNCTIONALITY
// ===========================
const SearchManager = {
  initializeAdvancedSearch() {
    $('#search_permission_name, #search_guard_name, #search_role').on('keyup change', function() {
      $('#permissions-table').DataTable().ajax.reload();
    });
    $('#clearPermissionFiltersBtn').on('click', function() {
      $('#search_permission_name, #search_guard_name, #search_role').val('');
      $('#permissions-table').DataTable().ajax.reload();
    });
  }
};

// ===========================
// MAIN APPLICATION
// ===========================
const PermissionApp = {
  init() {
    StatsManager.loadStats();
    PermissionManager.handleViewPermission();
    SearchManager.initializeAdvancedSearch();
  }
};

$(document).ready(() => {
  PermissionApp.init();
});
</script>
@endpush 