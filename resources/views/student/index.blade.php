@extends('layouts.home')

@section('title', 'Admin Students | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    
    {{-- ===== STATISTICS CARDS ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 
                id="students"
                label="Total Students"
                color="primary"
                icon="bx bx-group"
            />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 
                id="male-students"
                label="Total Male Students"
                color="danger"
                icon="bx bx-user-plus"
            />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 
                id="female-students"
                label="Total Female Students"
                color="success"
                icon="bx bx-user-check"
            />
        </div>
    </div>

    {{-- ===== PAGE HEADER & ACTION BUTTONS ===== --}}
    <x-ui.page-header 
        title="Students"
        description="Manage all student records, add new students, or import/export in bulk using the options on the right."
        icon="bx bx-group"
    >
        
        @can('student.create')
            <button class="btn btn-primary mx-2" 
                    id="addStudentBtn" 
                    type="button" 
                    data-bs-toggle="modal" 
                    data-bs-target="#studentModal">
                <i class="bx bx-plus me-1"></i> Add Student
            </button>
        @endcan
        
        <button class="btn btn-secondary"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#studentSearchCollapse"
                aria-expanded="false"
                aria-controls="studentSearchCollapse">
            <i class="bx bx-filter-alt me-1"></i> Search
        </button>
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
                @can('student.import')
                    <li>
                        <a class="dropdown-item" href="javascript:void(0);" 
                           id="importStudentsBtn"
                           data-bs-toggle="modal"
                           data-bs-target="#importStudentsModal">
                            <i class="bx bx-upload me-1"></i> Import Students
                        </a>
                    </li>
                @endcan
                @can('student.export')
                <li>
                    <a class="dropdown-item" href="javascript:void(0);"
                       id="exportStudentsBtn"
                       data-bs-toggle="modal"
                       data-bs-target="#exportStudentsModal">
                        <i class="bx bx-download me-1"></i> Export Students
                    </a>
                </li>
                @endcan
            </ul>
        </div>
    </x-ui.page-header>

    {{-- ===== ADVANCED SEARCH SECTION ===== --}}
    <x-ui.advanced-search 
        title="Advanced Search" 
        formId="advancedStudentSearch" 
        collapseId="studentSearchCollapse"
        :collapsed="false"
    >
        <div class="col-md-4">
            <label for="search_name" class="form-label">Name:</label>
            <input type="text" class="form-control" id="search_name" placeholder="Student Name">
        </div>
        
        <div class="col-md-4">
            <label for="search_national_id" class="form-label">National ID:</label>
            <input type="text" class="form-control" id="search_national_id" placeholder="National ID">
        </div>
        
        <div class="col-md-4">
            <label for="search_academic_id" class="form-label">Academic ID:</label>
            <input type="text" class="form-control" id="search_academic_id" placeholder="Academic ID">
        </div>
        
        <div class="w-100"></div>
        
        <div class="col-md-4">
            <label for="search_gender" class="form-label">Gender:</label>
            <select class="form-control" id="search_gender">
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
            </select>
        </div>
        
        <div class="col-md-4">
            <label for="search_program" class="form-label">Program:</label>
            <select class="form-control" id="search_program">
                <option value="">Select Program</option>
                <!-- Options loaded via AJAX -->
            </select>
        </div>
        
        <div class="col-md-4">
            <label for="search_level" class="form-label">Level:</label>
            <select class="form-control" id="search_level">
                <option value="">Select Level</option>
                <!-- Options loaded via AJAX -->
            </select>
        </div>
        
        <button class="btn btn-outline-secondary" id="clearFiltersBtn" type="button">
            <i class="bx bx-x"></i> Clear Filters
        </button>
    </x-ui.advanced-search>

    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable
        :headers="[
            'Name (EN)', 
            'Name (AR)', 
            'Academic ID', 
            'National ID', 
            'Academic Email', 
            'Level', 
            'CGPA', 
            'Gender', 
            'Program', 
            'Action'
        ]"
        :columns="[
            ['data' => 'name_en', 'name' => 'name_en'],
            ['data' => 'name_ar', 'name' => 'name_ar'],
            ['data' => 'academic_id', 'name' => 'academic_id'],
            ['data' => 'national_id', 'name' => 'national_id'],
            ['data' => 'academic_email', 'name' => 'academic_email'],
            ['data' => 'level', 'name' => 'level'],
            ['data' => 'cgpa', 'name' => 'cgpa'],
            ['data' => 'gender', 'name' => 'gender'],
            ['data' => 'program', 'name' => 'program', 'orderable' => false, 'searchable' => false],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
        ]"
        :ajax-url="route('students.datatable')"
        table-id="students-table"
        :filter-fields="[
            'search_name',
            'search_national_id',
            'search_academic_id',
            'search_gender',
            'search_program',
            'search_level'
        ]"
    />

    {{-- ===== MODALS SECTION ===== --}}
    
    {{-- Add/Edit Student Modal --}}
    <x-ui.modal 
        id="studentModal"
        title="Add/Edit Student"
        size="lg"
        :scrollable="true"
        class="student-modal"
    >
        <x-slot name="slot">
            <form id="studentForm">
                <input type="hidden" id="student_id" name="student_id">
                
                <div class="row">
                    {{-- Name Fields --}}
                    <div class="col-md-6 mb-3">
                        <label for="name_en" class="form-label">Name (EN)</label>
                        <input type="text" class="form-control" id="name_en" name="name_en" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="name_ar" class="form-label">Name (AR)</label>
                        <input type="text" class="form-control" id="name_ar" name="name_ar" required>
                    </div>
                    
                    {{-- ID Fields --}}
                    <div class="col-md-6 mb-3">
                        <label for="academic_id" class="form-label">Academic ID</label>
                        <input type="text" class="form-control" id="academic_id" name="academic_id" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="national_id" class="form-label">National ID</label>
                        <input type="text" class="form-control" id="national_id" name="national_id" required>
                    </div>
                    
                    {{-- Email and Level --}}
                    <div class="col-md-6 mb-3">
                        <label for="academic_email" class="form-label">Academic Email</label>
                        <input type="email" class="form-control" id="academic_email" name="academic_email" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="level_id" class="form-label">Level</label>
                        <select class="form-control" id="level_id" name="level_id" required>
                            <option value="">Select Level</option>
                            <!-- Options will be loaded via AJAX -->
                        </select>
                    </div>
                    
                    {{-- CGPA and Gender --}}
                    <div class="col-md-6 mb-3">
                        <label for="cgpa" class="form-label">CGPA</label>
                        <input type="number" step="0.001" class="form-control" id="cgpa" name="cgpa" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-control" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    
                    {{-- Program --}}
                    <div class="col-md-6 mb-3">
                        <label for="program_id" class="form-label">Program</label>
                        <select class="form-control" id="program_id" name="program_id" required>
                            <option value="">Select Program</option>
                            <!-- Options will be loaded via AJAX -->
                        </select>
                    </div>
                </div>
            </form>
        </x-slot>
        
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Close
            </button>
            <button type="submit" class="btn btn-primary" id="saveStudentBtn" form="studentForm">
                Save
            </button>
        </x-slot>
    </x-ui.modal>

    {{-- Import Students Modal --}}
    @can('student.import')
    <x-ui.modal 
        id="importStudentsModal"
        title="Import Students"
        size="md"
        :scrollable="false"
        class="import-students-modal"
    >
        <x-slot name="slot">
            <form id="importStudentsForm" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="students_file" class="form-label">Upload Excel File</label>
                    <input type="file" 
                           class="form-control" 
                           id="students_file" 
                           name="students_file" 
                           accept=".xlsx,.xls" 
                           required>
                </div>
                
                <div class="alert alert-info d-flex align-items-center justify-content-between p-3 mb-3">
                    <div>
                        <i class="bx bx-info-circle me-2"></i>
                        <span class="small">Use the template for correct student data formatting.</span>
                    </div>
                    <button type="button" 
                            class="btn btn-sm btn-outline-primary" 
                            id="downloadTemplateBtn">
                        <i class="bx bx-download me-1"></i>Template
                    </button>
                </div>
            </form>
        </x-slot>
        
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Close
            </button>
            <button type="submit" class="btn btn-success" id="importStudentsSubmitBtn" form="importStudentsForm">
                Import
            </button>
        </x-slot>
    </x-ui.modal>
    @endcan

    {{-- Download Enrollment Document Modal --}}
    <x-ui.modal 
        id="downloadEnrollmentModal"
        title="Download Enrollment Document"
        size="md"
        :scrollable="false"
        class="download-enrollment-modal"
    >
        <x-slot name="slot">
            <form id="downloadEnrollmentForm">
                <input type="hidden" id="modal_student_id" name="student_id">
                <input type="hidden" id="download_type" name="download_type">
                
                <div class="mb-3">
                    <label for="term_id" class="form-label">
                        Select Term 
                        <span class="text-danger">(Required)</span>
                    </label>
                    <select class="form-control" id="term_id" name="term_id" required>
                        <!-- Options will be loaded via AJAX -->
                    </select>
                    <small class="form-text text-muted">
                        You must select a term to download the enrollment document.
                    </small>
                </div>
            </form>
        </x-slot>
        
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Close
            </button>
            <button type="button" class="btn btn-primary" id="downloadEnrollmentBtn">
                Download
            </button>
        </x-slot>
    </x-ui.modal>

    {{-- Export Students Modal --}}
    @can('student.export')
    <x-ui.modal 
        id="exportStudentsModal"
        title="Export Students"
        size="md"
        :scrollable="false"
        class="export-students-modal"
    >
        <x-slot name="slot">
            <form id="exportStudentsForm" method="GET" action="{{ route('students.export') }}">
                <div class="mb-3">
                    <label for="export_program_id" class="form-label">
                        Select Program
                        <span class="text-muted">(Optional, leave blank for all programs)</span>
                    </label>
                    <select class="form-control" id="export_program_id" name="program_id">
                        <option value="">All Programs</option>
                        <!-- Options will be loaded via AJAX -->
                    </select>
                    <small class="form-text text-muted">
                        You may leave this blank to export students for all programs.
                    </small>
                </div>
                <div class="mb-3">
                    <label for="export_level_id" class="form-label">
                        Select Level
                        <span class="text-muted">(Optional, leave blank for all levels)</span>
                    </label>
                    <select class="form-control" id="export_level_id" name="level_id">
                        <option value="">All Levels</option>
                        <!-- Options will be loaded via AJAX -->
                    </select>
                    <small class="form-text text-muted">
                        You may leave this blank to export students for all levels.
                    </small>
                </div>
                <div class="alert alert-info d-flex align-items-center justify-content-between p-3 mb-3">
                    <div>
                        <i class="bx bx-info-circle me-2"></i>
                        <span class="small">Exported file will contain all student data based on your selected filters.</span>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Close
            </button>
            <button type="submit" class="btn btn-primary" id="exportStudentsSubmitBtn" form="exportStudentsForm">
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
 * Student Management System JavaScript
 * Organized and structured for better maintainability
 * Handles CRUD operations, imports, exports, and document downloads for students
 */

// ===========================
// CONSTANTS AND CONFIGURATION
// ===========================

const ROUTES = {
  programs: {
    all: '{{ route('programs.all') }}'
  },
  levels: {
    all: '{{ route('levels.all') }}'
  },
  terms: {
    all: '{{ route('terms.all') }}'
  },
  students: {
    stats: '{{ route('students.stats') }}',
    store: '{{ route('students.store') }}',
    show: '{{ route('students.show', ':id') }}',
    destroy: '{{ route('students.destroy', ':id') }}',
    import: '{{ route('students.import') }}',
    export: '{{ route('students.export') }}',
    template: '{{ route('students.template') }}',
    downloadPdf: '{{ route('students.download.pdf', ':id') }}',
    downloadWord: '{{ route('students.download.word', ':id') }}'
  }
};

const SELECTORS = {
  // Forms
  studentForm: '#studentForm',
  importForm: '#importStudentsForm',
  exportForm: '#exportStudentsForm',
  // Modals
  studentModal: '#studentModal',
  importModal: '#importStudentsModal',
  downloadModal: '#downloadEnrollmentModal',
  exportModal: '#exportStudentsModal',
  // Buttons
  addStudentBtn: '#addStudentBtn',
  saveStudentBtn: '#saveStudentBtn',
  importSubmitBtn: '#importStudentsSubmitBtn',
  exportSubmitBtn: '#exportStudentsSubmitBtn',
  downloadBtn: '#downloadEnrollmentBtn',
  downloadTemplateBtn: '#downloadTemplateBtn',
  clearFiltersBtn: '#clearFiltersBtn',
  // Tables
  studentsTable: '#students-table',
  // Search inputs
  searchName: '#search_name',
  searchNationalId: '#search_national_id',
  searchAcademicId: '#search_academic_id',
  searchGender: '#search_gender',
  searchProgram: '#search_program',
  searchLevel: '#search_level'
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
  },
  /**
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

  fetchTerms() {
    return this.request({
      url: ROUTES.terms.all,
      method: 'GET'
    });
  },

  fetchStudentStats() {
    return this.request({
      url: ROUTES.students.stats,
      method: 'GET'
    });
  },

  fetchStudent(id) {
    return this.request({
      url: Utils.replaceRouteId(ROUTES.students.show, id),
      method: 'GET'
    });
  },

  saveStudent(data, id = null) {
    const url = id ? Utils.replaceRouteId(ROUTES.students.show, id) : ROUTES.students.store;
    const method = id ? 'PUT' : 'POST';
    
    return this.request({
      url: url,
      method: method,
      data: data
    });
  },

  deleteStudent(id) {
    return this.request({
      url: Utils.replaceRouteId(ROUTES.students.destroy, id),
      method: 'DELETE'
    });
  },

  importStudents(formData) {
    return this.request({
      url: ROUTES.students.import,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false
    });
  },

  exportStudents(queryParams) {
    return this.request({
      url: `${ROUTES.students.export}?${queryParams}`,
      method: 'GET',
      xhrFields: {
        responseType: 'blob'
      }
    });
  },

  downloadDocument(studentId, type, termId) {
    let url;
    switch (type) {
      case 'pdf':
        url = Utils.replaceRouteId(ROUTES.students.downloadPdf, studentId);
        break;
      case 'word':
        url = Utils.replaceRouteId(ROUTES.students.downloadWord, studentId);
        break;
      default:
        url = '/enrollment/download/' + studentId;
    }

    if (termId) {
      url += '?term_id=' + termId;
    }

    return this.request({
      url: url,
      method: 'GET',
      dataType: 'json'
    });
  },

  downloadTemplate() {
    return this.request({
      url: ROUTES.students.template,
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
  loadPrograms(selector = '#program_id', selectedId = null) {
    return ApiService.fetchPrograms()
      .done((response) => {
        const programs = response.data || response || [];
        const $select = $(selector);
        $select.empty().append('<option value="">Select Program</option>');
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

  loadLevels(selector = '#level_id', selectedId = null) {
    return ApiService.fetchLevels()
      .done((response) => {
        const levels = response.data || response || [];
        const $select = $(selector);
        $select.empty().append('<option value="">Select Level</option>');
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
  },

  loadTerms(selector = '#term_id', selectedId = null) {
    return ApiService.fetchTerms()
      .done((response) => {
        const terms = response.data || response || [];
        const $select = $(selector);
        $select.empty().append('<option value="">Select Term</option>');
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
  }
};

// ===========================
// EXPORT FUNCTIONALITY
// ===========================

const ExportManager = {
  handleExportStudents() {
    $(SELECTORS.exportModal).on('show.bs.modal', () => {
      DropdownManager.loadPrograms('#export_program_id');
      DropdownManager.loadLevels('#export_level_id');
    });

    $(SELECTORS.exportForm).on('submit', function (e) {
      e.preventDefault();

      const $form = $(this);
      const programId = $form.find('#export_program_id').val();
      const levelId = $form.find('#export_level_id').val();
      const $submitBtn = $(SELECTORS.exportSubmitBtn);

      $submitBtn.prop('disabled', true).text('Exporting...');

      const queryParams = new URLSearchParams();
      if (programId) queryParams.append('program_id', programId);
      if (levelId) queryParams.append('level_id', levelId);

      ApiService.exportStudents(queryParams.toString())
        .done((response) => {
          const blob = new Blob([response], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
          });
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = 'students_' + new Date().toISOString().replace(/[-:T]/g, '').slice(0, 15) + '.xlsx';
          document.body.appendChild(a);
          a.click();
          window.URL.revokeObjectURL(url);
          document.body.removeChild(a);

          $(SELECTORS.exportModal).modal('hide');
          Utils.showSuccess('Students exported successfully!');
        })
        .fail((xhr) => {
          const response = xhr.responseJSON || {};
          let message = response.message || 'Export failed. Please check your input.';
          if (response.errors) {
            const errorMessages = [];
            Object.keys(response.errors).forEach(field => {
              if (Array.isArray(response.errors[field])) {
                errorMessages.push(...response.errors[field]);
              } else {
                errorMessages.push(response.errors[field]);
              }
            });
            message = errorMessages.join('<br>');
          }
          Utils.showError(message);
        })
        .always(() => {
          $submitBtn.prop('disabled', false).text('Export');
        });
    });
  }
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================

const StatsManager = {
  loadStudentStats() {
    Utils.toggleLoadingState('students', true);
    Utils.toggleLoadingState('male-students', true);
    Utils.toggleLoadingState('female-students', true);
    
    ApiService.fetchStudentStats()
      .done((response) => {
        const data = response.data;
        
        $('#students-value').text(data.students?.total ?? '--');
        $('#students-last-updated').text(data.students?.lastUpdateTime ?? '--');
        $('#male-students-value').text(data.maleStudents?.total ?? '--');
        $('#male-students-last-updated').text(data.maleStudents?.lastUpdateTime ?? '--');
        $('#female-students-value').text(data.femaleStudents?.total ?? '--');
        $('#female-students-last-updated').text(data.femaleStudents?.lastUpdateTime ?? '--');
        
        Utils.toggleLoadingState('students', false);
        Utils.toggleLoadingState('male-students', false);
        Utils.toggleLoadingState('female-students', false);
      })
      .fail(() => {
        $('#students-value, #male-students-value, #female-students-value').text('N/A');
        $('#students-last-updated, #male-students-last-updated, #female-students-last-updated').text('N/A');
        
        Utils.toggleLoadingState('students', false);
        Utils.toggleLoadingState('male-students', false);
        Utils.toggleLoadingState('female-students', false);
        
        Utils.showError('Failed to load student statistics');
      });
  }
};

// ===========================
// STUDENT CRUD OPERATIONS
// ===========================

const StudentManager = {
  handleAddStudent() {
    $(SELECTORS.addStudentBtn).on('click', () => {
      $(SELECTORS.studentForm)[0].reset();
      $('#student_id').val('');
      
      $(SELECTORS.studentModal + ' .modal-title').text('Add Student');
      $(SELECTORS.saveStudentBtn).text('Save');
      
      DropdownManager.loadPrograms();
      DropdownManager.loadLevels();
      
      $(SELECTORS.studentModal).modal('show');
    });
  },

  handleStudentFormSubmit() {
    $(SELECTORS.studentForm).on('submit', (e) => {
      e.preventDefault();
      
      const studentId = $('#student_id').val();
      const formData = $(SELECTORS.studentForm).serialize();
      
      const $submitBtn = $(SELECTORS.saveStudentBtn);
      const originalText = $submitBtn.text();
      $submitBtn.prop('disabled', true).text('Saving...');
      
      ApiService.saveStudent(formData, studentId || null)
        .done(() => {
          $(SELECTORS.studentModal).modal('hide');
          $(SELECTORS.studentsTable).DataTable().ajax.reload(null, false);
          Utils.showSuccess('Student has been saved successfully.');
          StatsManager.loadStudentStats();
        })
        .fail((xhr) => {
          const message = xhr.responseJSON?.message || 'An error occurred. Please check your input.';
          Utils.showError(message);
        })
        .always(() => {
          $submitBtn.prop('disabled', false).text(originalText);
        });
    });
  },

  handleEditStudent() {
    $(document).on('click', '.editStudentBtn', function () {
      const studentId = $(this).data('id');
      
      ApiService.fetchStudent(studentId)
        .done((student) => {
          $('#student_id').val(student.id);
          $('#name_en').val(student.name_en);
          $('#name_ar').val(student.name_ar);
          $('#academic_id').val(student.academic_id);
          $('#national_id').val(student.national_id);
          $('#academic_email').val(student.academic_email);
          $('#cgpa').val(student.cgpa);
          $('#gender').val(student.gender).trigger('change');
          
          DropdownManager.loadPrograms('#program_id', student.program_id);
          DropdownManager.loadLevels('#level_id', student.level_id);
          
          $(SELECTORS.studentModal + ' .modal-title').text('Edit Student');
          $(SELECTORS.saveStudentBtn).text('Update');
          $(SELECTORS.studentModal).modal('show');
        })
        .fail(() => {
          Utils.showError('Failed to fetch student data.');
        });
    });
  },

  handleDeleteStudent() {
    $(document).on('click', '.deleteStudentBtn', function () {
      const studentId = $(this).data('id');
      
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
          ApiService.deleteStudent(studentId)
            .done(() => {
              $(SELECTORS.studentsTable).DataTable().ajax.reload(null, false);
              Utils.showSuccess('Student has been deleted.');
              StatsManager.loadStudentStats();
            })
            .fail(() => {
              Utils.showError('Failed to delete student.');
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
  handleImportStudents() {
    $(SELECTORS.importForm).on('submit', (e) => {
      e.preventDefault();
      
      const formData = new FormData(e.target);
      const $submitBtn = $(SELECTORS.importSubmitBtn);
      
      $submitBtn.prop('disabled', true).text('Importing...');
      
      ApiService.importStudents(formData)
        .done((response) => {
          $(SELECTORS.importModal).modal('hide');
          $(SELECTORS.studentsTable).DataTable().ajax.reload(null, false);
          
          Utils.showSuccess(response.message);
          
          if (response.data?.errors?.length > 0) {
            this.showImportErrors(response.data.errors, response.data.imported_count);
          }
          
          StatsManager.loadStudentStats();
        })
        .fail((xhr) => {
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
    errorHtml += `<p class="mb-3"><strong>Successfully imported: ${importedCount} students</strong></p>`;
    errorHtml += '<p class="mb-3"><strong>Errors found:</strong></p>';
    
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
          a.download = 'students_template.xlsx';
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
// DOWNLOAD FUNCTIONALITY
// ===========================

const DownloadManager = {
  handleDownloadEnrollment() {
    $(document).on('click', '.downloadEnrollmentBtn', function() {
      const studentId = $(this).data('id');
      DownloadManager.setupDownloadModal(studentId, 'legacy', 'Download Enrollment Document');
    });
  },

  handleDownloadPdf() {
    $(document).on('click', '.downloadPdfBtn', function(e) {
      e.preventDefault();
      const studentId = $(this).data('id');
      DownloadManager.setupDownloadModal(studentId, 'pdf', 'Download Enrollment as PDF');
    });
  },

  handleDownloadWord() {
    $(document).on('click', '.downloadWordBtn', function(e) {
      e.preventDefault();
      const studentId = $(this).data('id');
      DownloadManager.setupDownloadModal(studentId, 'word', 'Download Enrollment as Word');
    });
  },

  setupDownloadModal(studentId, downloadType, modalTitle) {
    $('#modal_student_id').val(studentId);
    $('#download_type').val(downloadType);
    
    DropdownManager.loadTerms('#term_id').done(() => {
      $(SELECTORS.downloadModal + ' .modal-title').text(modalTitle);
      $(SELECTORS.downloadModal).modal('show');
    });
  },

  handleDownloadProcess() {
    $(SELECTORS.downloadBtn).on('click', () => {
      const studentId = $('#modal_student_id').val();
      const termId = $('#term_id').val();
      const downloadType = $('#download_type').val();

      if (!downloadType) {
        Utils.showError('Please select a download type.');
        return;
      }

      if (!termId) {
        Utils.showError('Please select a term.');
        return;
      }

      $(SELECTORS.downloadModal).modal('hide');

      Swal.fire({
        title: 'Generating Document...',
        text: 'Please wait while we prepare your document.',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
          Swal.showLoading();
        }
      });

      ApiService.downloadDocument(studentId, downloadType, termId)
        .done((response) => {
          if (response.url) {
            window.open(response.url, '_blank');
          Swal.close();
          } else {
            Utils.showError('Invalid response from server.');
          }
        })
        .fail((xhr) => {
          Swal.close();
          const message = xhr.responseJSON?.message || 'Failed to generate document.';
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
    this.initSearchSelect2();
    
    DropdownManager.loadPrograms(SELECTORS.searchProgram);
    DropdownManager.loadLevels(SELECTORS.searchLevel);
    
    this.bindSearchEvents();
  },

  initSearchSelect2() {
    $(`${SELECTORS.searchGender}, ${SELECTORS.searchProgram}, ${SELECTORS.searchLevel}`).select2({
      theme: 'bootstrap-5',
      placeholder: function() {
        const id = $(this).attr('id');
        if (id === 'search_gender') return 'Select Gender';
        if (id === 'search_program') return 'Select Program';
        if (id === 'search_level') return 'Select Level';
        return '';
      },
      allowClear: true,
      width: '100%',
      dropdownParent: $('#studentSearchCollapse')
    });
  },

  bindSearchEvents() {
    $(SELECTORS.clearFiltersBtn).on('click', () => {
      $(`${SELECTORS.searchName}, ${SELECTORS.searchNationalId}, ${SELECTORS.searchAcademicId}`).val('');
      $(`${SELECTORS.searchGender}, ${SELECTORS.searchProgram}, ${SELECTORS.searchLevel}`).val('').trigger('change');
      $(SELECTORS.studentsTable).DataTable().ajax.reload();
    });

    $(`${SELECTORS.searchName}, ${SELECTORS.searchNationalId}, ${SELECTORS.searchAcademicId}, ${SELECTORS.searchGender}, ${SELECTORS.searchProgram}, ${SELECTORS.searchLevel}`).on('keyup change', () => {
      $(SELECTORS.studentsTable).DataTable().ajax.reload();
    });
  }
};

// ===========================
// SELECT2 INITIALIZATION
// ===========================

const Select2Manager = {
  initStudentModalSelect2() {
    $(`${SELECTORS.studentModal} select`).select2({
      theme: 'bootstrap-5',
      placeholder: function() {
        const id = $(this).attr('id');
        if (id === 'level_id') return 'Select Level';
        if (id === 'gender') return 'Select Gender';
        if (id === 'program_id') return 'Select Program';
        return '';
      },
      allowClear: true,
      width: '100%',
      dropdownParent: $(SELECTORS.studentModal)
    });
  },

  initDownloadModalSelect2() {
    $(`${SELECTORS.downloadModal} #term_id`).select2({
      theme: 'bootstrap-5',
      placeholder: 'Select Term',
      allowClear: true,
      width: '100%',
      dropdownParent: $(SELECTORS.downloadModal)
    });
  },

  initExportModalSelect2() {
    $(`${SELECTORS.exportModal} select`).select2({
      theme: 'bootstrap-5',
      placeholder: function() {
        const id = $(this).attr('id');
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

const StudentManagementApp = {
  init() {
    StatsManager.loadStudentStats();
    
    StudentManager.handleAddStudent();
    StudentManager.handleStudentFormSubmit();
    StudentManager.handleEditStudent();
    StudentManager.handleDeleteStudent();
    
    ImportManager.handleImportStudents();
    
    ExportManager.handleExportStudents();
    
    DownloadManager.handleDownloadEnrollment();
    DownloadManager.handleDownloadPdf();
    DownloadManager.handleDownloadWord();
    DownloadManager.handleDownloadProcess();
    
    TemplateDownloadManager.handleTemplateDownload();
    
    Select2Manager.initStudentModalSelect2();
    Select2Manager.initDownloadModalSelect2();
    Select2Manager.initExportModalSelect2();
    
    SearchManager.initializeAdvancedSearch();
    Utils.hidePageLoader();

  }
};

// ===========================
// DOCUMENT READY
// ===========================

$(document).ready(() => {
  StudentManagementApp.init();
});
</script>
@endpush