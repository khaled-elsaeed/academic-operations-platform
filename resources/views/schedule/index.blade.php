
@extends('layouts.home')

@section('title', 'Admin Schedules | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    

    {{-- ===== STATISTICS CARDS ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 
                id="schedules"
                label="Total Schedules"
                color="primary"
                icon="bx bx-calendar"
            />
        </div>
    </div>

    {{-- ===== PAGE HEADER & ACTION BUTTONS ===== --}}
    <x-ui.page-header 
        title="Schedules"
        description="Manage all schedules and import/export in bulk using the options on the right."
        icon="bx bx-calendar"
    >

        <div class="btn-group me-2">
            <button
                type="button"
                class="btn btn-primary btn-icon rounded-pill dropdown-toggle hide-arrow"
                data-bs-toggle="dropdown"
                aria-expanded="false"
            >
                <i class="bx bx-download"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @can('schedule.import')
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" 
                           id="importSchedulesBtn"
                           data-bs-toggle="modal"
                           data-bs-target="#importSchedulesModal">
                            <i class="bx bx-upload me-1"></i> Import Schedules
                        </a>
                    </li>
                @endcan
                @can('schedule.export')
                <li>
                    <a class="dropdown-item" href="javascript:void(0);"
                       id="exportSchedulesBtn"
                       data-bs-toggle="modal"
                       data-bs-target="#exportSchedulesModal">
                        <i class="bx bx-download me-1"></i> Export Schedules
                    </a>
                </li>
                @endcan
            </ul>
        </div>
        
        <button class="btn btn-secondary"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#scheduleSearchCollapse"
                aria-expanded="false"
                aria-controls="scheduleSearchCollapse">
            <i class="bx bx-filter-alt me-1"></i> Search
        </button>
    </x-ui.page-header>


    {{-- ===== ADVANCED SEARCH SECTION ===== --}}
    <x-ui.advanced-search 
        title="Advanced Search" 
        formId="advancedScheduleSearch" 
        collapseId="scheduleSearchCollapse"
        :collapsed="false"
    >
        <div class="col-md-4">
            <label for="search_title" class="form-label">Title:</label>
            <input type="text" class="form-control" id="search_title" placeholder="Schedule Title or Code">
        </div>
        <div class="col-md-4">
            <label for="search_type" class="form-label">Type:</label>
            <input type="text" class="form-control" id="search_type" placeholder="Schedule Type">
        </div>
        <div class="col-md-3">
            <label for="search_status" class="form-label">Status:</label>
            <select class="form-control" id="search_status">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="finalized">Finalized</option>
            </select>
        </div>
        <button class="btn btn-outline-secondary" id="clearScheduleFiltersBtn" type="button">
            <i class="bx bx-x"></i> Clear Filters
        </button>
    </x-ui.advanced-search>


    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable
        :headers="['Title', 'Type', 'Status', 'Start Date', 'End Date', 'Action']"
        :columns="[
            ['data' => 'title', 'name' => 'title'],
            ['data' => 'type', 'name' => 'type'],
            ['data' => 'status', 'name' => 'status'],
            ['data' => 'start_date', 'name' => 'start_date'],
            ['data' => 'end_date', 'name' => 'end_date'],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
        ]"
        :ajax-url="route('schedules.datatable')"
        table-id="schedules-table"
        :filter-fields="['search_title','search_type','search_status']"
    />


    {{-- ===== MODALS SECTION ===== --}}
    @can('schedule.import')
    {{-- Import Schedules Modal --}}
    <x-ui.modal 
        id="importSchedulesModal"
        title="Import Schedules"
        size="md"
        :scrollable="false"
        class="import-schedules-modal"
    >
        <x-slot name="slot">
            <form id="importSchedulesForm" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="schedules_file" class="form-label">Upload Excel File</label>
                    <input type="file" 
                           class="form-control" 
                           id="schedules_file" 
                           name="schedules_file" 
                           accept=".xlsx,.xls" 
                           required>
                </div>
                <div class="alert alert-info d-flex align-items-center justify-content-between p-3 mb-3">
                    <div>
                        <i class="bx bx-info-circle me-2"></i>
                        <span class="small">Use the template for correct schedule data formatting.</span>
                    </div>
                    <button type="button" 
                            class="btn btn-sm btn-outline-primary" 
                            id="downloadScheduleTemplateBtn">
                        <i class="bx bx-download me-1"></i>Template
                    </button>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Close
            </button>
            <button type="submit" class="btn btn-success" id="importSchedulesSubmitBtn" form="importSchedulesForm">
                Import
            </button>
        </x-slot>
    </x-ui.modal>
    @endcan


    @can('schedule.export')
    {{-- Export Schedules Modal --}}
    <x-ui.modal 
        id="exportSchedulesModal"
        title="Export Schedules"
        size="md"
        :scrollable="false"
        class="export-schedules-modal"
    >
        <x-slot name="slot">
            <form id="exportSchedulesForm" method="GET" action="{{ route('schedules.export') }}">
                <div class="mb-3">
                    <label for="export_type_id" class="form-label">
                        Select Type
                        <span class="text-muted">(Optional, leave blank for all types)</span>
                    </label>
                    <select class="form-control" id="export_type_id" name="type_id">
                        <option value="">All Types</option>
                        <!-- Options will be loaded via AJAX -->
                    </select>
                    <small class="form-text text-muted">
                        You may leave this blank to export schedules for all types.
                    </small>
                </div>
                <div class="mb-3">
                    <label for="export_status" class="form-label">
                        Select Status (Optional)
                    </label>
                    <select class="form-control" id="export_status" name="status">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="finalized">Finalized</option>
                    </select>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Close
            </button>
            <button type="submit" class="btn btn-primary" id="exportSchedulesSubmitBtn" form="exportSchedulesForm">
                Export
            </button>
        </x-slot>
    </x-ui.modal>
    @endcan
</div>
@endsection

@push('scripts')
<script>
/**
 * Enrollment Management System JavaScript
 * Organized and structured for better maintainability
 * Handles CRUD operations and imports/exports for enrollments
 */

// ===========================
// CONSTANTS AND CONFIGURATION
// ===========================


const ROUTES = {
  types: {
    all: '{{ route('schedule_types.all') }}'
  },
  schedules: {
    stats: '{{ route('schedules.stats') }}',
    destroy: '{{ route('schedules.destroy', ':id') }}',
    import: '{{ route('schedules.import') }}',
    template: '{{ route('schedules.template') }}',
    export: '{{ route('schedules.export') }}'
  }
};


const SELECTORS = {
  // Forms
  importForm: '#importSchedulesForm',
  exportForm: '#exportSchedulesForm',
  // Modals
  importModal: '#importSchedulesModal',
  exportModal: '#exportSchedulesModal',
  // Buttons
  importSubmitBtn: '#importSchedulesSubmitBtn',
  downloadTemplateBtn: '#downloadScheduleTemplateBtn',
  clearFiltersBtn: '#clearScheduleFiltersBtn',
  exportSchedulesBtn: '#exportSchedulesBtn',
  exportSubmitBtn: '#exportSchedulesSubmitBtn',
  // Tables
  schedulesTable: '#schedules-table',
  // Search inputs
  searchTitle: '#search_title',
  searchType: '#search_type',
  searchStatus: '#search_status'
};

// ===========================
// UTILITY FUNCTIONS
// ===========================

const Utils = {
  showSuccess(message) {
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: message,
      showConfirmButton: false,
      timer: 2500,
      timerProgressBar: true
    });
  },

  showError(message) {
    Swal.fire({
      title: 'Error',
      html: message,
      icon: 'error'
    });
  },

  toggleLoadingState(elementId, isLoading) {
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
  },

  replaceRouteId(route, id) {
    return route.replace(':id', id);
  }
};

// ===========================
// API SERVICE LAYER
// ===========================

const ApiService = {
  request(options) {
    return $.ajax(options);
  },

  fetchScheduleStats() {
    return this.request({
      url: ROUTES.schedules.stats,
      method: 'GET'
    });
  },

  deleteSchedule(id) {
    return this.request({
      url: Utils.replaceRouteId(ROUTES.schedules.destroy, id),
      method: 'DELETE'
    });
  },

  importSchedules(formData) {
    return this.request({
      url: ROUTES.schedules.import,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false
    });
  },

  exportSchedules(queryParams) {
    return this.request({
      url: `${ROUTES.schedules.export}?${queryParams}`,
      method: 'GET',
      xhrFields: {
        responseType: 'blob'
      }
    });
  },

  fetchTypes() {
    return this.request({
      url: ROUTES.types.all,
      method: 'GET'
    });
  },

  downloadTemplate() {
    return this.request({
      url: ROUTES.schedules.template,
      method: 'GET',
      xhrFields: {
        responseType: 'blob'
      }
    });
  }
};

// ===========================
// DROPDOWN MANAGEMENT
// ===========================

const DropdownManager = {
  loadTypes(selector = '#export_type_id', selectedId = null) {
    return ApiService.fetchTypes()
      .done((response) => {
        const types = response.data || [];
        const $select = $(selector);
        $select.empty().append('<option value="">All Types</option>');
        types.forEach((type) => {
          $select.append($('<option>', { value: type.id, text: type.name }));
        });
        if (selectedId) {
          $select.val(selectedId);
        }
        $select.trigger('change');
      })
      .fail(() => {
        Utils.showError('Failed to load schedule types');
      });
  }
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================

const StatsManager = {
  loadScheduleStats() {
    Utils.toggleLoadingState('schedules', true);
    ApiService.fetchScheduleStats()
      .done((response) => {
        const data = response.data;
        $('#schedules-value').text(data.schedules?.total ?? '--');
        $('#schedules-last-updated').text(data.schedules?.lastUpdateTime ?? '--');
        Utils.toggleLoadingState('schedules', false);
      })
      .fail(() => {
        $('#schedules-value').text('N/A');
        $('#schedules-last-updated').text('N/A');
        Utils.toggleLoadingState('schedules', false);
        Utils.showError('Failed to load schedule statistics');
      });
  }
};

// ===========================
// ENROLLMENT CRUD OPERATIONS
// ===========================

const ScheduleManager = {
  handleDeleteSchedule() {
    $(document).on('click', '.deleteScheduleBtn', function () {
      const scheduleId = $(this).data('id');
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
          ApiService.deleteSchedule(scheduleId)
            .done(() => {
              $(SELECTORS.schedulesTable).DataTable().ajax.reload(null, false);
              Utils.showSuccess('Schedule has been deleted.');
              StatsManager.loadScheduleStats();
            })
            .fail(() => {
              Utils.showError('Failed to delete schedule.');
            });
        }
      });
    });
  }
};

// ===========================
// IMPORT FUNCTIONALITY
// ===========================

const ImportManager = {
  handleImportSchedules() {
    $(SELECTORS.importForm).on('submit', (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const $submitBtn = $(SELECTORS.importSubmitBtn);
      $submitBtn.prop('disabled', true).text('Importing...');
      ApiService.importSchedules(formData)
        .done((response) => {
          $(SELECTORS.importModal).modal('hide');
          $(SELECTORS.schedulesTable).DataTable().ajax.reload(null, false);
          Utils.showSuccess(response.message);
          if (response.data?.errors?.length > 0) {
            this.showImportErrors(response.data.errors, response.data.imported_count);
          }
          StatsManager.loadScheduleStats();
        })
        .fail((xhr) => {
          $(SELECTORS.importModal).modal('hide');
          const response = xhr.responseJSON;
          if (response?.errors && Object.keys(response.errors).length > 0) {
            const errorMessages = [];
            Object.keys(response.errors).forEach(field => {
              if (Array.isArray(response.errors[field])) {
                errorMessages.push(...response.errors[field]);
              } else {
                errorMessages.push(response.errors[field]);
              }
            });
            Utils.showError(errorMessages.join('<br>'));
          } else {
            const message = response?.message || 'Import failed. Please check your file.';
            Utils.showError(message);
          }
        })
        .always(() => {
          $submitBtn.prop('disabled', false).text('Import');
        });
    });
  },

  showImportErrors(errors, importedCount) {
    let errorHtml = '<div class="text-start">';
    errorHtml += `<p class="mb-3"><strong>Successfully processed: ${importedCount} schedules</strong></p>`;
    errorHtml += '<p class="mb-3"><strong>Failed rows:</strong></p>';
    errorHtml += '<div class="table-responsive" style="max-height:400px; overflow-y:auto;">';
    errorHtml += '<table class="table table-sm table-bordered table-striped mb-0">';
    errorHtml += '<thead>';
    errorHtml += '<tr>';
    errorHtml += '<th style="width: 80px;">Row #</th>';
    errorHtml += '<th style="width: 200px;">Error</th>';
    errorHtml += '<th>Original Data</th>';
    errorHtml += '</tr>';
    errorHtml += '</thead>';
    errorHtml += '<tbody>';
    errors.forEach((error) => {
      let errorMessages = '';
      if (Array.isArray(error.errors)) {
        errorMessages = error.errors.join('<br>');
      } else if (typeof error.errors === 'object') {
        Object.keys(error.errors).forEach((field) => {
          const fieldErrors = error.errors[field];
          if (Array.isArray(fieldErrors)) {
            errorMessages += fieldErrors.join('<br>') + '<br>';
          } else if (typeof fieldErrors === 'string') {
            errorMessages += fieldErrors + '<br>';
          } else {
            errorMessages += String(fieldErrors) + '<br>';
          }
        });
      } else {
        errorMessages = String(error.errors);
      }
      let originalDataHtml = '';
      if (error.original_data) {
        originalDataHtml = '<div class="small">';
        Object.keys(error.original_data).forEach((key) => {
          const value = error.original_data[key];
          const displayValue = value === null || value === undefined ? '<em class="text-muted">null</em>' : value;
          originalDataHtml += `<strong>${key}:</strong> ${displayValue}<br>`;
        });
        originalDataHtml += '</div>';
      }
      errorHtml += '<tr>';
      errorHtml += `<td class="text-center fw-bold">${error.row}</td>`;
      errorHtml += `<td class="text-danger small">${errorMessages}</td>`;
      errorHtml += `<td class="small">${originalDataHtml}</td>`;
      errorHtml += '</tr>';
    });
    errorHtml += '</tbody>';
    errorHtml += '</table>';
    errorHtml += '</div>';
    Swal.fire({
      title: 'Import Completed with Errors',
      html: errorHtml,
      icon: 'warning',
      confirmButtonText: 'OK',
      width: '800px',
      customClass: {
        popup: 'swal-wide'
      }
    });
  }
};

// ===========================
// EXPORT FUNCTIONALITY
// ===========================

const ExportManager = {
  handleExportSchedules() {
    $(SELECTORS.exportSchedulesBtn).on('click', () => {
      this.setupExportModal();
    });
    $(SELECTORS.exportForm).on('submit', function (e) {
      e.preventDefault();
      const $form = $(this);
      const typeId = $form.find('#export_type_id').val();
      const status = $form.find('#export_status').val();
      const $submitBtn = $(SELECTORS.exportSubmitBtn);
      $submitBtn.prop('disabled', true).text('Exporting...');
      const queryParams = new URLSearchParams({
        type_id: typeId,
        ...(status && { status: status })
      }).toString();
      ApiService.exportSchedules(queryParams)
        .done((response) => {
          const blob = new Blob([response], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
          });
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = 'schedules_export.xlsx';
          document.body.appendChild(a);
          a.click();
          window.URL.revokeObjectURL(url);
          document.body.removeChild(a);
          $(SELECTORS.exportModal).modal('hide');
          Utils.showSuccess('Schedules exported successfully!');
        })
        .fail((xhr) => {
          $(SELECTORS.exportModal).modal('hide');
          const response = xhr.responseJSON || {};
          const message = response.message || 'Export failed. Please check your input.';
          Utils.showError(message);
        })
        .always(() => {
          $submitBtn.prop('disabled', false).text('Export');
        });
    });
  },

  setupExportModal() {
    DropdownManager.loadTypes('#export_type_id').done(() => {
      $(SELECTORS.exportModal).modal('show');
    });
  }
};

// ===========================
// TEMPLATE DOWNLOAD FUNCTIONALITY
// ===========================

const TemplateDownloadManager = {
  handleTemplateDownload() {
    $(SELECTORS.downloadTemplateBtn).on('click', () => {
      const $btn = $(SELECTORS.downloadTemplateBtn);
      const originalText = $btn.html();
      $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Downloading...');
      ApiService.downloadTemplate()
        .done((response) => {
          const blob = new Blob([response], { 
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
          });
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = 'schedules_template.xlsx';
          document.body.appendChild(a);
          a.click();
          window.URL.revokeObjectURL(url);
          document.body.removeChild(a);
          Utils.showSuccess('Template downloaded successfully!');
        })
        .fail((xhr) => {
          const message = xhr.responseJSON?.message || 'Failed to download template.';
          Utils.showError(message);
        })
        .always(() => {
          $btn.prop('disabled', false).html(originalText);
        });
    });
  }
};

// ===========================
// SEARCH FUNCTIONALITY
// ===========================

const SearchManager = {
  initializeAdvancedSearch() {
    this.bindSearchEvents();
  },
  bindSearchEvents() {
    $(SELECTORS.clearFiltersBtn).on('click', () => {
      $(`${SELECTORS.searchTitle}, ${SELECTORS.searchType}`).val('');
      $(SELECTORS.searchStatus).val('');
      $(SELECTORS.schedulesTable).DataTable().ajax.reload();
    });
    $(`${SELECTORS.searchTitle}, ${SELECTORS.searchType}, ${SELECTORS.searchStatus}`).on('keyup change', () => {
      $(SELECTORS.schedulesTable).DataTable().ajax.reload();
    });
  }
};

// ===========================
// SELECT2 INITIALIZATION
// ===========================

const Select2Manager = {
  initExportModalSelect2() {
    $(`${SELECTORS.exportModal} select`).select2({
      theme: 'bootstrap-5',
      placeholder: function() {
        const id = $(this).attr('id');
        if (id === 'export_type_id') return 'Select Type';
        if (id === 'export_status') return 'Select Status';
        return '';
      },
      allowClear: true,
      width: '100%',
      dropdownParent: $(SELECTORS.exportModal)
    });
  }
};

// ===========================
// MAIN APPLICATION
// ===========================

const ScheduleManagementApp = {
  init() {
    StatsManager.loadScheduleStats();
    ScheduleManager.handleDeleteSchedule();
    ImportManager.handleImportSchedules();
    ExportManager.handleExportSchedules();
    TemplateDownloadManager.handleTemplateDownload();
    SearchManager.initializeAdvancedSearch();
    Select2Manager.initExportModalSelect2();
  }
};

// ===========================
// DOCUMENT READY
// ===========================

$(document).ready(() => {
  ScheduleManagementApp.init();
});
</script>
@endpush
