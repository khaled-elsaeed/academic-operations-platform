@extends('layouts.home')

@section('title', 'Admin Enrollment | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    
    {{-- ===== STATISTICS CARDS ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 
                id="enrollments"
                label="Total Enrollments"
                color="primary"
                icon="bx bx-book-open"
            />
        </div>
    </div>

    {{-- ===== PAGE HEADER & ACTION BUTTONS ===== --}}
    <x-ui.page-header 
        title="Enrollments"
        description="Manage all student enrollments and import/export in bulk using the options on the right."
        icon="bx bx-book-open"
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
                @can('enrollment.import')
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" 
                           id="importEnrollmentsBtn"
                           data-bs-toggle="modal"
                           data-bs-target="#importEnrollmentsModal">
                            <i class="bx bx-upload me-1"></i> Import Enrollments
                        </a>
                    </li>
                @endcan
                @can('enrollment.export')
                <li>
                    <a class="dropdown-item" href="javascript:void(0);"
                       id="exportEnrollmentsBtn"
                       data-bs-toggle="modal"
                       data-bs-target="#exportEnrollmentsModal">
                        <i class="bx bx-download me-1"></i> Export Enrollments
                    </a>
                </li>
                @endcan
            </ul>
        </div>
        
        <button class="btn btn-secondary"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#enrollmentSearchCollapse"
                aria-expanded="false"
                aria-controls="enrollmentSearchCollapse">
            <i class="bx bx-filter-alt me-1"></i> Search
        </button>
    </x-ui.page-header>

    {{-- ===== ADVANCED SEARCH SECTION ===== --}}
    <x-ui.advanced-search 
        title="Advanced Search" 
        formId="advancedEnrollmentSearch" 
        collapseId="enrollmentSearchCollapse"
        :collapsed="false"
    >
        <div class="col-md-4">
            <label for="search_student" class="form-label">Student:</label>
            <input type="text" class="form-control" id="search_student" placeholder="Student Name or Academic ID">
        </div>
        
        <div class="col-md-4">
            <label for="search_course" class="form-label">Course:</label>
            <input type="text" class="form-control" id="search_course" placeholder="Course Name or Code">
        </div>
        
        <div class="col-md-3">
            <label for="search_term" class="form-label">Term:</label>
            <input type="text" class="form-control" id="search_term" placeholder="Season, Year, or Code">
        </div>
        
        <div class="col-md-3">
            <label for="search_grade" class="form-label">Grade:</label>
            <select class="form-control" id="search_grade">
                <option value="">All Grades</option>
                <option value="A+">A+</option>
                <option value="A">A</option>
                <option value="A-">A-</option>
                <option value="B+">B+</option>
                <option value="B">B</option>
                <option value="B-">B-</option>
                <option value="C+">C+</option>
                <option value="C">C</option>
                <option value="C-">C-</option>
                <option value="D+">D+</option>
                <option value="D">D</option>
                <option value="F">F</option>
                <option value="no-grade">No Grade</option>
            </select>
        </div>
        
        <button class="btn btn-outline-secondary" id="clearEnrollmentFiltersBtn" type="button">
            <i class="bx bx-x"></i> Clear Filters
        </button>
    </x-ui.advanced-search>

    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable
        :headers="['Student', 'Course', 'Term', 'Grade', 'Action']"
        :columns="[
            ['data' => 'student', 'name' => 'student'],
            ['data' => 'course', 'name' => 'course'],
            ['data' => 'term', 'name' => 'term'],
            ['data' => 'grade', 'name' => 'grade'],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
        ]"
        :ajax-url="route('enrollments.datatable')"
        table-id="enrollments-table"
        :filter-fields="['search_student','search_course','search_term','search_grade']"
    />

    {{-- ===== MODALS SECTION ===== --}}
    @can('enrollment.import')
    {{-- Import Enrollments Modal --}}
    <x-ui.modal 
        id="importEnrollmentsModal"
        title="Import Enrollments"
        size="md"
        :scrollable="false"
        class="import-enrollments-modal"
    >
        <x-slot name="slot">
            <form id="importEnrollmentsForm" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="enrollments_file" class="form-label">Upload Excel File</label>
                    <input type="file" 
                           class="form-control" 
                           id="enrollments_file" 
                           name="enrollments_file" 
                           accept=".xlsx,.xls" 
                           required>
                </div>
                
                <div class="alert alert-info d-flex align-items-center justify-content-between p-3 mb-3">
                    <div>
                        <i class="bx bx-info-circle me-2"></i>
                        <span class="small">Use the template for correct enrollment data formatting.</span>
                    </div>
                    <button type="button" 
                            class="btn btn-sm btn-outline-primary" 
                            id="downloadEnrollmentTemplateBtn">
                        <i class="bx bx-download me-1"></i>Template
                    </button>
                </div>
            </form>
        </x-slot>
        
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Close
            </button>
            <button type="submit" class="btn btn-success" id="importEnrollmentsSubmitBtn" form="importEnrollmentsForm">
                Import
            </button>
        </x-slot>
    </x-ui.modal>
    @endcan

    @can('enrollment.export')
    {{-- Export Enrollments Modal --}}
    <x-ui.modal 
        id="exportEnrollmentsModal"
        title="Export Enrollments"
        size="md"
        :scrollable="false"
        class="export-enrollments-modal"
    >
        <x-slot name="slot">
            <form id="exportEnrollmentsForm" method="GET" action="{{ route('enrollments.export') }}">
                <div class="mb-3">
                    <label for="export_term_id" class="form-label">
                        Select Term
                        <span class="text-muted">(Optional, leave blank for all terms)</span>
                    </label>
                    <select class="form-control" id="export_term_id" name="term_id">
                        <option value="">All Terms</option>
                        <!-- Options will be loaded via AJAX -->
                    </select>
                    <small class="form-text text-muted">
                        You may leave this blank to export enrollments for all terms.
                    </small>
                </div>
                <div class="mb-3">
                    <label for="export_program_id" class="form-label">
                        Select Program (Optional)
                    </label>
                    <select class="form-control" id="export_program_id" name="program_id">
                        <option value="">All Programs</option>
                        <!-- Options will be loaded via AJAX -->
                    </select>
                </div>
                <div class="mb-3">
                    <label for="export_level_id" class="form-label">
                        Select Level (Optional)
                    </label>
                    <select class="form-control" id="export_level_id" name="level_id">
                        <option value="">All Levels</option>
                        <!-- Options will be loaded via AJAX -->
                    </select>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Close
            </button>
            <button type="submit" class="btn btn-primary" id="exportEnrollmentsSubmitBtn" form="exportEnrollmentsForm">
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
  terms: {
    all: '{{ route('terms.all.with_inactive') }}'
  },
  programs: {
    all: '{{ route('programs.all') }}'
  },
  levels: {
    all: '{{ route('levels.all') }}'
  },
  enrollments: {
    stats: '{{ route('enrollments.stats') }}',
    destroy: '{{ route('enrollments.destroy', ':id') }}',
    import: '{{ route('enrollments.import') }}',
    template: '{{ route('enrollments.template') }}',
    export: '{{ route('enrollments.export') }}'
  }
};

const SELECTORS = {
  // Forms
  importForm: '#importEnrollmentsForm',
  exportForm: '#exportEnrollmentsForm',
  // Modals
  importModal: '#importEnrollmentsModal',
  exportModal: '#exportEnrollmentsModal',
  // Buttons
  importSubmitBtn: '#importEnrollmentsSubmitBtn',
  downloadTemplateBtn: '#downloadEnrollmentTemplateBtn',
  clearFiltersBtn: '#clearEnrollmentFiltersBtn',
  exportEnrollmentsBtn: '#exportEnrollmentsBtn',
  exportSubmitBtn: '#exportEnrollmentsSubmitBtn',
  // Tables
  enrollmentsTable: '#enrollments-table',
  // Search inputs
  searchStudent: '#search_student',
  searchCourse: '#search_course',
  searchTerm: '#search_term',
  searchGrade: '#search_grade'
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
  },/**
     * Hide the page loader overlay.
     */
    hidePageLoader() {
      const loader = document.getElementById('pageLoader');
      if (loader) {
        loader.classList.add('fade-out');
        // Restore scrollbars when loader is hidden
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
      }
    }
};

// ===========================
// API SERVICE LAYER
// ===========================

const ApiService = {
  request(options) {
    return $.ajax(options);
  },

  fetchEnrollmentStats() {
    return this.request({
      url: ROUTES.enrollments.stats,
      method: 'GET'
    });
  },

  deleteEnrollment(id) {
    return this.request({
      url: Utils.replaceRouteId(ROUTES.enrollments.destroy, id),
      method: 'DELETE'
    });
  },

  importEnrollments(formData) {
    return this.request({
      url: ROUTES.enrollments.import,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false
    });
  },

  exportEnrollments(queryParams) {
    return this.request({
      url: `${ROUTES.enrollments.export}?${queryParams}`,
      method: 'GET',
      xhrFields: {
        responseType: 'blob'
      }
    });
  },

  fetchTerms() {
    return this.request({
      url: ROUTES.terms.all,
      method: 'GET'
    });
  },

  fetchPrograms() {
    return this.request({
      url: ROUTES.programs.all,
      method: 'GET'
    });
  },

  fetchLevels() {
    return this.request({
      url: ROUTES.levels.all,
      method: 'GET'
    });
  },

  downloadTemplate() {
    return this.request({
      url: ROUTES.enrollments.template,
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
  loadTerms(selector = '#export_term_id', selectedId = null) {
    return ApiService.fetchTerms()
      .done((response) => {
        const terms = response.data || [];
        const $select = $(selector);
        
        $select.empty().append('<option value="">All Terms</option>');
        terms.forEach((term) => {
          $select.append($('<option>', { value: term.id, text: term.name }));
        });
        
        if (selectedId) {
          $select.val(selectedId);
        }
        $select.trigger('change');
      })
      .fail(() => {
        Utils.showError('Failed to load terms');
      });
  },

  loadPrograms(selector = '#export_program_id', selectedId = null) {
    return ApiService.fetchPrograms()
      .done((response) => {
        const programs = response.data || [];
        const $select = $(selector);
        
        $select.empty().append('<option value="">All Programs</option>');
        programs.forEach((program) => {
          $select.append($('<option>', { value: program.id, text: program.name }));
        });
        
        if (selectedId) {
          $select.val(selectedId);
        }
        $select.trigger('change');
      })
      .fail(() => {
        Utils.showError('Failed to load programs');
      });
  },

  loadLevels(selector = '#export_level_id', selectedId = null) {
    return ApiService.fetchLevels()
      .done((response) => {
        const levels = response.data || [];
        const $select = $(selector);
        
        $select.empty().append('<option value="">All Levels</option>');
        levels.forEach((level) => {
          $select.append($('<option>', { value: level.id, text: level.name }));
        });
        
        if (selectedId) {
          $select.val(selectedId);
        }
        $select.trigger('change');
      })
      .fail(() => {
        Utils.showError('Failed to load levels');
      });
  }
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================

const StatsManager = {
  loadEnrollmentStats() {
    Utils.toggleLoadingState('enrollments', true);
    
    ApiService.fetchEnrollmentStats()
      .done((response) => {
        const data = response.data;
        $('#enrollments-value').text(data.enrollments?.total ?? '--');
        $('#enrollments-last-updated').text(data.enrollments?.lastUpdateTime ?? '--');
        Utils.toggleLoadingState('enrollments', false);
      })
      .fail(() => {
        $('#enrollments-value').text('N/A');
        $('#enrollments-last-updated').text('N/A');
        Utils.toggleLoadingState('enrollments', false);
        Utils.showError('Failed to load enrollment statistics');
      });
  }
};

// ===========================
// ENROLLMENT CRUD OPERATIONS
// ===========================

const EnrollmentManager = {
  handleDeleteEnrollment() {
    $(document).on('click', '.deleteEnrollmentBtn', function () {
      const enrollmentId = $(this).data('id');
      
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
          ApiService.deleteEnrollment(enrollmentId)
            .done(() => {
              $(SELECTORS.enrollmentsTable).DataTable().ajax.reload(null, false);
              Utils.showSuccess('Enrollment has been deleted.');
              StatsManager.loadEnrollmentStats();
            })
            .fail(() => {
              Utils.showError('Failed to delete enrollment.');
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
  handleImportEnrollments() {
    $(SELECTORS.importForm).on('submit', (e) => {
      e.preventDefault();
      
      const formData = new FormData(e.target);
      const $submitBtn = $(SELECTORS.importSubmitBtn);
      
      $submitBtn.prop('disabled', true).text('Importing...');
      
      ApiService.importEnrollments(formData)
        .done((response) => {
          $(SELECTORS.importModal).modal('hide');
          $(SELECTORS.enrollmentsTable).DataTable().ajax.reload(null, false);
          
          Utils.showSuccess(response.message);
          
          if (response.data?.errors?.length > 0) {
            this.showImportErrors(response.data.errors, response.data.imported_count);
          }
          
          StatsManager.loadEnrollmentStats();
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
    errorHtml += `<p class="mb-3"><strong>Successfully processed: ${importedCount} enrollments</strong></p>`;
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
  handleExportEnrollments() {
    $(SELECTORS.exportEnrollmentsBtn).on('click', () => {
      this.setupExportModal();
    });

    $(SELECTORS.exportForm).on('submit', function (e) {
      e.preventDefault();

      const $form = $(this);
      const termId = $form.find('#export_term_id').val();
      const programId = $form.find('#export_program_id').val();
      const levelId = $form.find('#export_level_id').val();
      const $submitBtn = $(SELECTORS.exportSubmitBtn);

      $submitBtn.prop('disabled', true).text('Exporting...');

      const queryParams = new URLSearchParams({
        term_id: termId,
        ...(programId && { program_id: programId }),
        ...(levelId && { level_id: levelId })
      }).toString();

      ApiService.exportEnrollments(queryParams)
        .done((response) => {
          const blob = new Blob([response], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
          });
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = 'enrollments_export.xlsx';
          document.body.appendChild(a);
          a.click();
          window.URL.revokeObjectURL(url);
          document.body.removeChild(a);

          $(SELECTORS.exportModal).modal('hide');
          Utils.showSuccess('Enrollments exported successfully!');
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
    DropdownManager.loadTerms('#export_term_id');
    DropdownManager.loadPrograms('#export_program_id');
    DropdownManager.loadLevels('#export_level_id').done(() => {
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
          a.download = 'enrollments_template.xlsx';
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
      $(`${SELECTORS.searchStudent}, ${SELECTORS.searchCourse}, ${SELECTORS.searchTerm}`).val('');
      $(SELECTORS.searchGrade).val('');
      $(SELECTORS.enrollmentsTable).DataTable().ajax.reload();
    });

    $(`${SELECTORS.searchStudent}, ${SELECTORS.searchCourse}, ${SELECTORS.searchTerm}, ${SELECTORS.searchGrade}`).on('keyup change', () => {
      $(SELECTORS.enrollmentsTable).DataTable().ajax.reload();
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
        if (id === 'export_term_id') return 'Select Term';
        if (id === 'export_program_id') return 'Select Program';
        if (id === 'export_level_id') return 'Select Level';
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

const EnrollmentManagementApp = {
  init() {
    StatsManager.loadEnrollmentStats();
    EnrollmentManager.handleDeleteEnrollment();
    ImportManager.handleImportEnrollments();
    ExportManager.handleExportEnrollments();
    TemplateDownloadManager.handleTemplateDownload();
    SearchManager.initializeAdvancedSearch();
    Select2Manager.initExportModalSelect2();
    Utils.hidePageLoader();

  }
};

// ===========================
// DOCUMENT READY
// ===========================

$(document).ready(() => {
  EnrollmentManagementApp.init();
});
</script>
@endpush
