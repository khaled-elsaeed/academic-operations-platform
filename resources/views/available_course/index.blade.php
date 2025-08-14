@extends('layouts.home')

@section('title', 'Available Courses | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- ===== STATISTICS CARDS ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 
                id="available-courses"
                label="Total Available Courses"
                color="primary"
                icon="bx bx-book"
            />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 
                id="universal-courses"
                label="Universal Courses"
                color="info"
                icon="bx bx-globe"
            />
        </div>
    </div>
    {{-- ===== PAGE HEADER & ACTION BUTTONS ===== --}}
    <x-ui.page-header 
      title="Available Courses"
      description="List of all available courses for enrollment"
      icon="bx bx-book"
    >
        @can('available_course.import')
        <button class="btn btn-success me-2" 
                id="importAvailableCoursesBtn" 
                type="button" 
                data-bs-toggle="modal" 
                data-bs-target="#importAvailableCoursesModal">
            <i class="bx bx-upload me-1"></i> Import Available Courses
        </button>
        @endcan
        <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#availableCourseSearchCollapse" aria-expanded="false" aria-controls="availableCourseSearchCollapse">
            <i class="bx bx-filter-alt me-1"></i> Search
        </button>
    </x-ui.page-header>

    {{-- ===== ADVANCED SEARCH SECTION ===== --}}
    <x-ui.advanced-search 
        title="Advanced Search" 
        formId="advancedAvailableCourseSearch" 
        collapseId="availableCourseSearchCollapse"
        :collapsed="false"
    >
        <div class="col-md-4">
            <label for="search_course" class="form-label">Course:</label>
            <input type="text" class="form-control" id="search_course" placeholder="Course Name or Code">
        </div>
        <div class="col-md-4">
            <label for="search_term" class="form-label">Term:</label>
            <select class="form-control" id="search_term" style="width:100%">
                <option value="">Select Term</option>
            </select>
        </div>
        <button class="btn btn-outline-secondary mt-3 ms-2" id="clearAvailableCourseFiltersBtn" type="button">
            <i class="bx bx-x"></i> Clear Filters
        </button>
    </x-ui.advanced-search>

    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable
      :headers="['Course', 'Term', 'Eligibilities', 'Schedules', 'Action']"
      :columns="[
          ['data' => 'course', 'name' => 'course'],
          ['data' => 'term', 'name' => 'term'],
          ['data' => 'eligibility', 'name' => 'eligibility'],
          ['data' => 'schedules', 'name' => 'schedules'],
          ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
      ]"
      :ajax-url="route('available_courses.datatable')"
      table-id="available-courses-table"
      :filter-fields="['search_course','search_term']"
    />

    {{-- ===== MODALS SECTION ===== --}}
    {{-- Eligibility Modal --}}
    <x-ui.modal id="eligibilityModal" title="Eligibility (Program / Level)" size="md" :scrollable="false" class="eligibility-modal">
      <x-slot name="slot">
        <div id="eligibilityContent"><!-- Content will be filled by JS --></div>
      </x-slot>
      <x-slot name="footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </x-slot>
    </x-ui.modal>

    {{-- Schedules Modal --}}
    <x-ui.modal id="schedulesModal" title="Course Schedules" size="xl" :scrollable="true" class="schedules-modal">
      <x-slot name="slot">
        <div id="schedulesContent"><!-- Content will be filled by JS --></div>
      </x-slot>
      <x-slot name="footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </x-slot>
    </x-ui.modal>

    @can('available_course.import')
    {{-- Import Available Courses Modal --}}
    <x-ui.modal 
        id="importAvailableCoursesModal"
        title="Import Available Courses"
        size="md"
        :scrollable="false"
        class="import-available-courses-modal"
    >
        <x-slot name="slot">
            <form id="importAvailableCoursesForm" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="courses_file" class="form-label">Upload Excel File</label>
                    <input type="file" 
                           class="form-control" 
                           id="courses_file" 
                           name="courses_file" 
                           accept=".xlsx,.xls" 
                           required>
                </div>
                <div class="alert alert-info d-flex align-items-center justify-content-between p-3 mb-3">
                    <div>
                        <i class="bx bx-info-circle me-2"></i>
                        <span class="small">
                            Use the template for correct available course data formatting.<br>
                            The file must contain columns: course_code, term_code, program_name, level_name, min_capacity, max_capacity.
                        </span>
                    </div>
                    <button type="button" 
                            class="btn btn-sm btn-outline-primary" 
                            id="downloadAvailableCourseTemplateBtn">
                        <i class="bx bx-download me-1"></i>Template
                    </button>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Close
            </button>
            <button type="submit" class="btn btn-success" id="importAvailableCoursesSubmitBtn" form="importAvailableCoursesForm">
                Import
            </button>
        </x-slot>
    </x-ui.modal>
    @endcan
</div>
@endsection

@push('scripts')
<script>
// ===========================
// CONSTANTS AND CONFIGURATION
// ===========================
const ROUTES = {
  availableCourses: {
    stats: '{{ route('available_courses.stats') }}',
    datatable: '{{ route('available_courses.datatable') }}',
    import: '{{ route('available_courses.import') }}',
    template: '{{ route('available_courses.template') }}',
    destroy: '{{ route('available_courses.destroy', ':id') }}',
    terms: "{{ route('terms.all') }}",
    schedules: "{{ route('available_courses.schedules', ':id') }}",
    eligibilities: "{{ route('available_courses.eligibilities', ':id') }}"
  }
};
const SELECTORS = {
  datatable: '#available-courses-table',
  importForm: '#importAvailableCoursesForm',
  importModal: '#importAvailableCoursesModal',
  importSubmitBtn: '#importAvailableCoursesSubmitBtn',
  downloadTemplateBtn: '#downloadAvailableCourseTemplateBtn',
  clearFiltersBtn: '#clearAvailableCourseFiltersBtn',
  searchCourse: '#search_course',
  searchTerm: '#search_term',
};
// ===========================
// UTILITY FUNCTIONS
// ===========================
const Utils = {
  showSuccess(message, useToast = true) {
    if (useToast) {
      Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: message, showConfirmButton: false, timer: 2500, timerProgressBar: true });
    } else {
      Swal.fire({ title: 'Success', text: message, icon: 'success' });
    }
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
  request(options) { return $.ajax(options); },
  fetchStats() { return this.request({ url: ROUTES.availableCourses.stats, method: 'GET' }); },
  deleteAvailableCourse(id) { return this.request({ url: Utils.replaceRouteId(ROUTES.availableCourses.destroy, id), method: 'DELETE' }); },
  importAvailableCourses(formData) { return this.request({ url: ROUTES.availableCourses.import, method: 'POST', data: formData, processData: false, contentType: false }); },
  fetchTerms() { return this.request({ url: ROUTES.availableCourses.terms, method: 'GET' }); },
  downloadTemplate() { return this.request({ url: ROUTES.availableCourses.template, method: 'GET', xhrFields: { responseType: 'blob' } }); },
  fetchSchedules(availableCourseId) { return this.request({ url: Utils.replaceRouteId(ROUTES.availableCourses.schedules, availableCourseId), method: 'GET' }); },
  fetchEligibilities(availableCourseId) { return this.request({ url: Utils.replaceRouteId(ROUTES.availableCourses.eligibilities, availableCourseId), method: 'GET' }); }
};
// ===========================
// DROPDOWN MANAGEMENT
// ===========================
const DropdownManager = {
  loadTerms(selector = SELECTORS.searchTerm, selectedId = null) {
    return ApiService.fetchTerms().done(function(response) {
      const terms = response.data || [];
      const $select = $(selector);
      $select.empty().append('<option value="">Select Term</option>');
      terms.forEach(function(term) {
        $select.append($('<option>', { value: term.id, text: term.name }));
      });
      if (selectedId) { $select.val(selectedId); }
      $select.trigger('change');
    }).fail(function() { Utils.showError('Failed to load terms'); });
  }
};
// ===========================
// STATS MANAGER
// ===========================
const StatsManager = {
  loadStats() {
    Utils.toggleLoadingState('available-courses', true);
    Utils.toggleLoadingState('universal-courses', true);
    ApiService.fetchStats()
      .done(function(response) {
        const data = response.data;
        $('#available-courses-value').text(data.available_courses.total ?? '--');
        $('#available-courses-last-updated').text(data.available_courses.lastUpdateTime ?? '--');
        $('#universal-courses-value').text(data.universal_courses.total ?? '--');
        $('#universal-courses-last-updated').text(data.universal_courses.lastUpdateTime ?? '--');
        Utils.toggleLoadingState('available-courses', false);
        Utils.toggleLoadingState('universal-courses', false);
      })
      .fail(function() {
        $('#available-courses-value, #universal-courses-value').text('N/A');
        $('#available-courses-last-updated, #universal-courses-last-updated').text('N/A');
        Utils.toggleLoadingState('available-courses', false);
        Utils.toggleLoadingState('universal-courses', false);
        Utils.showError('Failed to load available course statistics');
      });
  }
};
// ===========================
// TEMPLATE DOWNLOAD FUNCTIONALITY
// ===========================
const TemplateDownloadManager = {
  handleTemplateDownload() {
    $(SELECTORS.downloadTemplateBtn).on('click', function () {
      const $btn = $(SELECTORS.downloadTemplateBtn);
      const originalText = $btn.html();
      $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Downloading...');
      ApiService.downloadTemplate()
        .done(function(response) {
          const blob = new Blob([response], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = 'available_courses_template.xlsx';
          document.body.appendChild(a);
          a.click();
          window.URL.revokeObjectURL(url);
          document.body.removeChild(a);
          Utils.showSuccess('Template downloaded successfully!');
        })
        .fail(function(xhr) {
          const message = xhr.responseJSON?.message || 'Failed to download template.';
          Utils.showError(message);
        })
        .always(function() {
          $btn.prop('disabled', false).html(originalText);
        });
    });
  }
};
// ===========================
// IMPORT FUNCTIONALITY
// ===========================
const ImportManager = {
  handleImportForm() {
    $(SELECTORS.importForm).on('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      const $submitBtn = $(SELECTORS.importSubmitBtn);
      $submitBtn.prop('disabled', true).text('Importing...');
      ApiService.importAvailableCourses(formData)
        .done(function(response) {
          $(SELECTORS.importModal).modal('hide');
          $(SELECTORS.datatable).DataTable().ajax.reload(null, false);
          Utils.showSuccess(response.message);
          if (response.data && response.data.errors && response.data.errors.length > 0) {
            ImportManager.showImportErrors(response.data.errors, response.data.imported_count);
          }
          StatsManager.loadStats();
        })
        .fail(function(xhr) {
          $(SELECTORS.importModal).modal('hide');
          const response = xhr.responseJSON;
          if (response && response.errors && Object.keys(response.errors).length > 0) {
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
        .always(function() {
          $submitBtn.prop('disabled', false).text('Import');
        });
    });
  },
  showImportErrors(errors, importedCount) {
    let errorHtml = '<div class="text-start">';
    errorHtml += `<p class="mb-3"><strong>Successfully processed: ${importedCount} available courses</strong></p>`;
    errorHtml += '<p class="mb-3"><strong>Failed rows:</strong></p>';
    errorHtml += '<div class="table-responsive" style="max-height:400px; overflow-y:auto;">';
    errorHtml += '<table class="table table-sm table-bordered table-striped mb-0">';
    errorHtml += '<thead><tr><th style="width: 80px;">Row #</th><th style="width: 200px;">Error</th><th>Original Data</th></tr></thead><tbody>';
    errors.forEach(function(error) {
      let errorMessages = '';
      
      // Handle different error message formats
      if (error.error) {
        // Single error message (current format)
        errorMessages = String(error.error);
      } else if (Array.isArray(error.errors)) {
        // Array of error messages
        errorMessages = error.errors.join('<br>');
      } else if (typeof error.errors === 'object' && error.errors !== null) {
        // Object of field errors
        Object.keys(error.errors).forEach(function(field) {
          const fieldErrors = error.errors[field];
          if (Array.isArray(fieldErrors)) {
            errorMessages += fieldErrors.join('<br>') + '<br>';
          } else if (typeof fieldErrors === 'string') {
            errorMessages += fieldErrors + '<br>';
          } else {
            errorMessages += String(fieldErrors) + '<br>';
          }
        });
      } else if (typeof error.errors === 'string') {
        // String error message
        errorMessages = error.errors;
      } else {
        // Fallback
        errorMessages = 'Unknown error';
      }
      
      let originalDataHtml = '';
      const dataSource = error.data || error.original_data; // Handle both formats
      if (dataSource) {
        originalDataHtml = '<div class="small">';
        Object.keys(dataSource).forEach(function(key) {
          const value = dataSource[key];
          const displayValue = value === null || value === undefined ? '<em class="text-muted">null</em>' : value;
          originalDataHtml += `<strong>${key}:</strong> ${displayValue}<br>`;
        });
        originalDataHtml += '</div>';
      }
      
      const rowNumber = error.row_number || error.row || 'N/A';
      errorHtml += '<tr>';
      errorHtml += `<td class="text-center fw-bold">${rowNumber}</td>`;
      errorHtml += `<td class="text-danger small">${errorMessages}</td>`;
      errorHtml += `<td class="small">${originalDataHtml}</td>`;
      errorHtml += '</tr>';
    });
    errorHtml += '</tbody></table></div>';
    Swal.fire({
      title: 'Import Completed with Errors',
      html: errorHtml,
      icon: 'warning',
      confirmButtonText: 'OK',
      width: '800px',
      customClass: { popup: 'swal-wide' }
    });
  }
};
// ===========================
// DELETE MANAGER
// ===========================
const DeleteManager = {
  handleDeleteBtn() {
    $(document).on('click', '.deleteAvailableCourseBtn', function () {
      const availableCourseId = $(this).data('id');
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then(result => {
        if (result.isConfirmed) {
          ApiService.deleteAvailableCourse(availableCourseId)
            .done(() => {
              $(SELECTORS.datatable).DataTable().ajax.reload(null, false);
              Utils.showSuccess('Available course has been deleted.');
              StatsManager.loadStats();
            })
            .fail(xhr => {
              const msg = xhr.responseJSON?.message || 'Failed to delete available course.';
              Utils.showError(msg);
            });
        }
      });
    });
  }
};
// ===========================
// SEARCH MANAGER
// ===========================
const SearchManager = {
  initializeAdvancedSearch() {
    DropdownManager.loadTerms();
    this.initSearchSelect2();
    this.bindSearchEvents();
  },
  initSearchSelect2() {
    $(SELECTORS.searchTerm).select2({
      theme: 'bootstrap-5',
      placeholder: 'Select Term',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#availableCourseSearchCollapse')
    });
  },
  bindSearchEvents() {
    $(SELECTORS.clearFiltersBtn).on('click', function() {
      $(SELECTORS.searchCourse + ',' + SELECTORS.searchTerm).val('').trigger('change');
      $(SELECTORS.datatable).DataTable().ajax.reload();
    });
    $(SELECTORS.searchCourse + ',' + SELECTORS.searchTerm)
      .on('keyup change', function() {
        $(SELECTORS.datatable).DataTable().ajax.reload();
      });
  }
};
// ===========================
// ELIGIBILITY MODAL MANAGER
// ===========================
const EligibilityModalManager = {
  handleShowEligibilityModal() {
    $(document).on('click', '.show-eligibility-modal', function () {
      const availableCourseId = $(this).data('id');
      EligibilityModalManager.renderEligibilityLoading();
      const modal = new bootstrap.Modal(document.getElementById('eligibilityModal'));
      modal.show();

      ApiService.fetchEligibilities(availableCourseId)
        .done(function(response) {
          EligibilityModalManager.renderEligibilityContent(response.data);
        })
        .fail(function(xhr) {
          let message = 'Failed to load eligibilities.';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
          }
          EligibilityModalManager.renderEligibilityError(message);
        });
    });
  },
  renderEligibilityLoading() {
    const $content = $('#eligibilityContent');
    $content.html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
  },
  renderEligibilityError(message) {
    const $content = $('#eligibilityContent');
    $content.html(`<div class="alert alert-danger">${message}</div>`);
  },
  renderEligibilityContent(eligibilities) {
    const $content = $('#eligibilityContent');
    $content.empty();
    if (Array.isArray(eligibilities) && eligibilities.length > 0) {
      if (eligibilities.length === 1) {
        $content.append('<div class="mb-2"><strong>Program / Level:</strong></div>');
        $content.append(`<div class="alert alert-info p-3">
          <div class="d-flex align-items-center">
            <i class="bx bx-check-circle text-success me-2 fs-5"></i>
            <div>
              <strong>${eligibilities[0].program_name} / ${eligibilities[0].level_name}</strong>
            </div>
          </div>
        </div>`);
      } else {
        $content.append('<div class="mb-3"><strong>Eligible Programs & Levels:</strong></div>');
        let table = `<div class="table-responsive">
          <table class="table table-bordered table-striped table-sm mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;">#</th>
                <th style="width: 45%;">Program</th>
                <th style="width: 45%;">Level</th>
              </tr>
            </thead>
            <tbody>`;
        eligibilities.forEach((eligibility, idx) => {
          table += `<tr>
            <td class="text-center fw-bold">${idx + 1}</td>
            <td>${eligibility.program_name}</td>
            <td>${eligibility.level_name}</td>
          </tr>`;
        });
        table += '</tbody></table></div>';
        $content.append(table);
      }
    } else {
      $content.append('<div class="alert alert-warning d-flex align-items-center"><i class="bx bx-info-circle me-2 fs-5"></i>No eligibility requirements found.</div>');
    }
  }
};
// ===========================
// SCHEDULES MODAL MANAGER
// ===========================
const SchedulesModalManager = {
  handleShowSchedulesModal() {
    $(document).on('click', '.show-schedules-modal', function () {
      const availableCourseId = $(this).data('id');
      SchedulesModalManager.renderSchedulesLoading();
      const modal = new bootstrap.Modal(document.getElementById('schedulesModal'));
      modal.show();

      ApiService.fetchSchedules(availableCourseId)
        .done(function(response) {
          SchedulesModalManager.renderSchedulesContent(response.data);
        })
        .fail(function(xhr) {
          let message = 'Failed to load schedules.';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
          }
          SchedulesModalManager.renderSchedulesError(message);
        });
    });
  },
  renderSchedulesLoading() {
    const $content = $('#schedulesContent');
    $content.html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
  },
  renderSchedulesError(message) {
    const $content = $('#schedulesContent');
    $content.html(`<div class="alert alert-danger">${message}</div>`);
  },
  renderSchedulesContent(groups) {
    const $content = $('#schedulesContent');
    $content.empty();
    
    if (Array.isArray(groups) && groups.length > 0) {
      $content.append('<div class="mb-3"><strong>Course Schedules by Groups:</strong></div>');
      
      groups.forEach((group, groupIdx) => {
        const groupNumber = group.group ?? 'N/A';
        const activities = group.activities ?? [];
        
        // Create group container
        $content.append(`
          <div class="card mb-4 shadow-sm group-card">
            <div class="card-header py-2">
              <h6 class="mb-0 d-flex align-items-center">
                <i class="bx bx-group me-2"></i>
                Group ${groupNumber}
                <span class="badge bg-light text-dark ms-2">${activities.length} Activities</span>
              </h6>
            </div>
            <div class="card-body p-0" id="group-${groupIdx}-activities">
            </div>
          </div>
        `);
        
        const $activitiesContainer = $(`#group-${groupIdx}-activities`);
        
        if (activities.length > 0) {
          activities.forEach((activity, activityIdx) => {
            const activityType = activity.activity_type ?? 'N/A';
            const location = activity.location ?? 'TBA';
            const minCapacity = activity.min_capacity ?? '0';
            const maxCapacity = activity.max_capacity ?? '0';
            const enrolledCount = activity.enrolled_count ?? '0';
            const dayOfWeek = activity.day_of_week ?? 'TBA';
            const startTime = activity.start_time ?? 'TBA';
            const endTime = activity.end_time ?? 'TBA';
            
            // Calculate enrollment percentage
            const enrollmentPercentage = maxCapacity > 0 ? Math.round((enrolledCount / maxCapacity) * 100) : 0;
            const progressBarClass = enrollmentPercentage >= 90 ? 'bg-danger' : (enrollmentPercentage >= 70 ? 'bg-warning' : 'bg-success');
            
            $activitiesContainer.append(`
              <div class="border-bottom ${activityIdx === activities.length - 1 ? 'border-0' : ''} p-3">
                <div class="row align-items-center">
                  <div class="col-md-8">
                    <div class="d-flex align-items-center mb-2">
                      <span class="badge bg-info me-2">${activityType}</span>
                      <i class="bx bx-map-pin text-muted me-1"></i>
                      <small class="text-muted">${location}</small>
                    </div>
                    <div class="row text-sm">
                      <div class="col-sm-6">
                        <i class="bx bx-calendar text-primary me-1"></i>
                        <strong>Day:</strong> ${dayOfWeek.charAt(0).toUpperCase() + dayOfWeek.slice(1)}
                      </div>
                      <div class="col-sm-6">
                        <i class="bx bx-time text-primary me-1"></i>
                        <strong>Time:</strong> ${startTime} - ${endTime}
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="text-center">
                      <div class="small text-muted mb-1">Enrollment</div>
                      <div class="fw-bold mb-1">${enrolledCount} / ${maxCapacity}</div>
                      <div class="progress" style="height: 6px;">
                        <div class="progress-bar ${progressBarClass}" role="progressbar" 
                             style="width: ${enrollmentPercentage}%" 
                             aria-valuenow="${enrollmentPercentage}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                      </div>
                      <small class="text-muted">${enrollmentPercentage}% Full</small>
                    </div>
                  </div>
                </div>
              </div>
            `);
          });
        } else {
          $activitiesContainer.append(`
            <div class="p-3 text-center text-muted">
              <i class="bx bx-info-circle me-1"></i>
              No activities scheduled for this group
            </div>
          `);
        }
      });
    } else {
      $content.append(`
        <div class="alert alert-warning d-flex align-items-center">
          <i class="bx bx-info-circle me-2 fs-5"></i>
          No schedules found for this course.
        </div>
      `);
    }
  }
};
// ===========================
// MAIN APPLICATION
// ===========================
const AvailableCourseManagementApp = {
  init() {
    StatsManager.loadStats();
    SearchManager.initializeAdvancedSearch();
    ImportManager.handleImportForm();
    DeleteManager.handleDeleteBtn();
    EligibilityModalManager.handleShowEligibilityModal();
    SchedulesModalManager.handleShowSchedulesModal();
    TemplateDownloadManager.handleTemplateDownload();
    Utils.hidePageLoader();

  }
};
// ===========================
// DOCUMENT READY
// ===========================
$(document).ready(function () {
  AvailableCourseManagementApp.init();
});
</script>
@endpush 