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
let currentPermissionId = null;

// Initialize page
$(document).ready(function() {
    loadStats();
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
  toggleLoadingState('permissions', true);
  toggleLoadingState('roles', true);
  toggleLoadingState('permissions-roles', true);

  $.ajax({
    url: '{{ route("permissions.stats") }}',
    method: 'GET',
    success: function(response) {
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
      toggleLoadingState('permissions', false);
      toggleLoadingState('roles', false);
      toggleLoadingState('permissions-roles', false);
    },
    error: function() {
      $('#permissions-value, #roles-value, #permissions-roles-value').text('N/A');
      $('#permissions-last-updated, #roles-last-updated, #permissions-roles-last-updated').text('N/A');
      toggleLoadingState('permissions', false);
      toggleLoadingState('roles', false);
      toggleLoadingState('permissions-roles', false);
      Swal.fire('Error', 'Failed to load permission statistics', 'error');
    }
  });
}

// View permission details
function viewPermission(permissionId) {
    $.get(`{{ route('permissions.show', ':id') }}`.replace(':id', permissionId))
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

// Get DataTable instance
function getDataTable() {
    return $('#permissions-table').DataTable();
}
</script>
@endpush 