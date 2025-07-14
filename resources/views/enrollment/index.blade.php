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
        description="Manage all student enrollments and import in bulk using the options on the right."
        icon="bx bx-book-open"
    >
        @can('enrollment.import')
            <button class="btn btn-success me-2" 
                    id="importEnrollmentsBtn" 
                    type="button" 
                    data-bs-toggle="modal" 
                    data-bs-target="#importEnrollmentsModal">
                <i class="bx bx-upload me-1"></i> Import Enrollments
            </button>
        @endcan
        
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
            <label for="search_score" class="form-label">Score Range:</label>
            <select class="form-control" id="search_score">
                <option value="">All Scores</option>
                <option value="90-100">90-100 (Excellent)</option>
                <option value="80-89">80-89 (Very Good)</option>
                <option value="70-79">70-79 (Good)</option>
                <option value="60-69">60-69 (Pass)</option>
                <option value="0-59">0-59 (Fail)</option>
                <option value="no-grade">No Grade</option>
            </select>
        </div>
        
        <button class="btn btn-outline-secondary" id="clearEnrollmentFiltersBtn" type="button">
            <i class="bx bx-x"></i> Clear Filters
        </button>
    </x-ui.advanced-search>

    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable
        :headers="['ID', 'Student', 'Course', 'Term', 'Score', 'Action']"
        :columns="[
            ['data' => 'id', 'name' => 'id'],
            ['data' => 'student', 'name' => 'student'],
            ['data' => 'course', 'name' => 'course'],
            ['data' => 'term', 'name' => 'term'],
            ['data' => 'score', 'name' => 'score'],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
        ]"
        :ajax-url="route('enrollments.datatable')"
        table-id="enrollments-table"
        :filter-fields="['search_student','search_course','search_term','search_score']"
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
</div>
@endsection

@push('scripts')
<script>
/**
 * Enrollment Management System JavaScript
 * Organized and structured for better maintainability
 * Handles CRUD operations and imports for enrollments
 */

// ===========================
// CONSTANTS AND CONFIGURATION
// ===========================

const ROUTES = {
  enrollments: {
    stats: '{{ route('enrollments.stats') }}',
    destroy: '{{ route('enrollments.destroy', ':id') }}',
    import: '{{ route('enrollments.import') }}',
    template: '{{ route('enrollments.template') }}'
  }
};

const SELECTORS = {
  // Forms
  importForm: '#importEnrollmentsForm',
  
  // Modals
  importModal: '#importEnrollmentsModal',
  
  // Buttons
  importSubmitBtn: '#importEnrollmentsSubmitBtn',
  downloadTemplateBtn: '#downloadEnrollmentTemplateBtn',
  clearFiltersBtn: '#clearEnrollmentFiltersBtn',
  
  // Tables
  enrollmentsTable: '#enrollments-table',
  
  // Search inputs
  searchStudent: '#search_student',
  searchCourse: '#search_course',
  searchTerm: '#search_term',
  searchScore: '#search_score'
};

// ===========================
// UTILITY FUNCTIONS
// ===========================

const Utils = {
  /**
   * Shows success notification
   * @param {string} message - Success message to display
   */
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

  /**
   * Shows error notification
   * @param {string} message - Error message to display
   */
  showError(message) {
    Swal.fire({
      title: 'Error',
      html: message,
      icon: 'error'
    });
  },

  /**
   * Shows/hides loading spinners and content for stat2 component
   * @param {string} elementId - Base element ID
   * @param {boolean} isLoading - Whether to show loading state
   */
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

  /**
   * Replaces :id placeholder in route URLs
   * @param {string} route - Route URL with :id placeholder
   * @param {number} id - ID to replace placeholder with
   * @returns {string} - Updated URL
   */
  replaceRouteId(route, id) {
    return route.replace(':id', id);
  }
};

// ===========================
// API SERVICE LAYER
// ===========================

const ApiService = {
  /**
   * Generic AJAX request wrapper
   * @param {Object} options - jQuery AJAX options
   * @returns {Promise} - jQuery promise
   */
  request(options) {
    return $.ajax(options);
  },

  /**
   * Fetches enrollment statistics
   * @returns {Promise}
   */
  fetchEnrollmentStats() {
    return this.request({
      url: ROUTES.enrollments.stats,
      method: 'GET'
    });
  },

  /**
   * Deletes an enrollment
   * @param {number} id - Enrollment ID
   * @returns {Promise}
   */
  deleteEnrollment(id) {
    return this.request({
      url: Utils.replaceRouteId(ROUTES.enrollments.destroy, id),
      method: 'DELETE'
    });
  },

  /**
   * Imports enrollments from file
   * @param {FormData} formData - Form data containing file
   * @returns {Promise}
   */
  importEnrollments(formData) {
    return this.request({
      url: ROUTES.enrollments.import,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false
    });
  },

  /**
   * Downloads template file
   * @returns {Promise}
   */
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
// STATISTICS MANAGEMENT
// ===========================

const StatsManager = {
  /**
   * Loads and displays enrollment statistics
   */
  loadEnrollmentStats() {
    // Show loading state for stats
    Utils.toggleLoadingState('enrollments', true);
    
    ApiService.fetchEnrollmentStats()
      .done((response) => {
        const data = response.data;
        
        // Update enrollment statistics
        $('#enrollments-value').text(data.enrollments?.total ?? '--');
        $('#enrollments-last-updated').text(data.enrollments?.lastUpdateTime ?? '--');
        
        // Hide loading state
        Utils.toggleLoadingState('enrollments', false);
      })
      .fail(() => {
        // Show error state
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
  /**
   * Handles Delete Enrollment button click (delegated)
   */
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
              StatsManager.loadEnrollmentStats(); // Refresh stats
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
  /**
   * Handles import form submission
   */
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
          
          // If there are errors, show them in a detailed modal
          if (response.data?.errors?.length > 0) {
            this.showImportErrors(response.data.errors, response.data.imported_count);
          }
          
          StatsManager.loadEnrollmentStats(); // Refresh stats
        })
        .fail((xhr) => {
          $(SELECTORS.importModal).modal('hide');
          const response = xhr.responseJSON;
          
          if (response?.errors && Object.keys(response.errors).length > 0) {
            // Handle validation errors
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

  /**
   * Shows import errors in a detailed modal
   * @param {Array} errors - Array of error objects
   * @param {number} importedCount - Number of successfully imported records
   */
  showImportErrors(errors, importedCount) {
    let errorHtml = '<div class="text-start">';
    errorHtml += `<p class="mb-3"><strong>Successfully processed: ${importedCount} enrollments</strong></p>`;
    errorHtml += '<p class="mb-3"><strong>Failed rows:</strong></p>';
    
    // Make the table scrollable with a fixed max height
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
      // Get error messages as a single string
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
      
      // Format original data as JSON-like display
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
// TEMPLATE DOWNLOAD FUNCTIONALITY
// ===========================

const TemplateDownloadManager = {
  /**
   * Handles template download button click
   */
  handleTemplateDownload() {
    $(SELECTORS.downloadTemplateBtn).on('click', () => {
      const $btn = $(SELECTORS.downloadTemplateBtn);
      const originalText = $btn.html();
      
      // Show loading state
      $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Downloading...');
      
      ApiService.downloadTemplate()
        .done((response) => {
          // Create blob and download
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
          // Restore button state
          $btn.prop('disabled', false).html(originalText);
        });
    });
  }
};

// ===========================
// SEARCH FUNCTIONALITY
// ===========================

const SearchManager = {
  /**
   * Initializes advanced search functionality
   */
  initializeAdvancedSearch() {
    // Bind search events
    this.bindSearchEvents();
  },

  /**
   * Binds search events
   */
  bindSearchEvents() {
    // Clear filters button
    $(SELECTORS.clearFiltersBtn).on('click', () => {
      $(`${SELECTORS.searchStudent}, ${SELECTORS.searchCourse}, ${SELECTORS.searchTerm}`).val('');
      $(SELECTORS.enrollmentsTable).DataTable().ajax.reload();
    });

    // Search input events
    $(`${SELECTORS.searchStudent}, ${SELECTORS.searchCourse}, ${SELECTORS.searchTerm}`).on('keyup change', () => {
      $(SELECTORS.enrollmentsTable).DataTable().ajax.reload();
    });
  }
};

// ===========================
// MAIN APPLICATION
// ===========================

const EnrollmentManagementApp = {
  /**
   * Initializes the entire application
   */
  init() {
    // Load initial data
    StatsManager.loadEnrollmentStats();
    
    // Initialize CRUD operations
    EnrollmentManager.handleDeleteEnrollment();
    
    // Initialize import functionality
    ImportManager.handleImportEnrollments();
    
    // Initialize template download functionality
    TemplateDownloadManager.handleTemplateDownload();
    
    // Initialize search functionality
    SearchManager.initializeAdvancedSearch();
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