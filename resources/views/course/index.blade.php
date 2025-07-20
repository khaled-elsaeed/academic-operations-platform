@extends('layouts.home')

@section('title', 'Course Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    
    {{-- ===== STATISTICS CARDS ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 
                id="courses"
                label="Total Courses"
                color="primary"
                icon="bx bx-book"
            />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 
                id="with-prerequisites"
                label="Courses with Prerequisites"
                color="success"
                icon="bx bx-check-circle"
            />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 
                id="without-prerequisites"
                label="Courses without Prerequisites"
                color="warning"
                icon="bx bx-x-circle"
            />
        </div>
    </div>

    {{-- ===== PAGE HEADER & ACTION BUTTONS ===== --}}
    <x-ui.page-header 
        title="Courses"
        description="Manage all course records and add new courses using the options on the right."
        icon="bx bx-book"
    >
        <button class="btn btn-primary mx-2" id="addCourseBtn" type="button" data-bs-toggle="modal" data-bs-target="#courseModal">
            <i class="bx bx-plus me-1"></i> Add Course
        </button>
        <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#courseSearchCollapse" aria-expanded="false" aria-controls="courseSearchCollapse">
            <i class="bx bx-filter-alt me-1"></i> Search
        </button>
    </x-ui.page-header>

    {{-- ===== ADVANCED SEARCH SECTION ===== --}}
    <x-ui.advanced-search 
        title="Advanced Search" 
        formId="advancedCourseSearch" 
        collapseId="courseSearchCollapse"
        :collapsed="false"
    >
        <div class="col-md-4">
            <label for="search_code" class="form-label">Course Code:</label>
            <input type="text" class="form-control" id="search_code" placeholder="Course Code">
        </div>
        <div class="col-md-4">
            <label for="search_title" class="form-label">Course Title:</label>
            <input type="text" class="form-control" id="search_title" placeholder="Course Title">
        </div>
        <div class="col-md-4">
            <label for="search_faculty" class="form-label">Faculty:</label>
            <select class="form-control" id="search_faculty">
                <option value="">Select Faculty</option>
                <!-- Options loaded via AJAX -->
            </select>
        </div>
        <button class="btn btn-outline-secondary" id="clearFiltersBtn" type="button">
            <i class="bx bx-x"></i> Clear Filters
        </button>
    </x-ui.advanced-search>

    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable
        :headers="['Code', 'Title', 'Credit Hours', 'Faculty', 'Prerequisites Count', 'Prerequisites', 'Action']"
        :columns="[
            ['data' => 'code', 'name' => 'code'],
            ['data' => 'title', 'name' => 'title'],
            ['data' => 'credit_hours', 'name' => 'credit_hours'],
            ['data' => 'faculty_name', 'name' => 'faculty_name'],
            ['data' => 'prerequisites_count', 'name' => 'prerequisites_count'],
            ['data' => 'prerequisites_list', 'name' => 'prerequisites_list'],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
        ]"
        :ajax-url="route('courses.datatable')"
        table-id="courses-table"
        :filter-fields="['search_code', 'search_title', 'search_faculty']"
    />

    {{-- ===== MODALS SECTION ===== --}}
    {{-- Add/Edit Course Modal --}}
    <x-ui.modal 
        id="courseModal"
        title="Add/Edit Course"
        size="lg"
        :scrollable="false"
        class="course-modal"
    >
        <x-slot name="slot">
            <form id="courseForm">
                <input type="hidden" id="course_id" name="course_id">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="code" class="form-label">Course Code</label>
                        <input type="text" class="form-control" id="code" name="code" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="credit_hours" class="form-label">Credit Hours</label>
                        <input type="number" step="0.5" min="0" max="99" class="form-control" id="credit_hours" name="credit_hours" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="title" class="form-label">Course Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="faculty_id" class="form-label">Faculty</label>
                        <select class="form-control select2" id="faculty_id" name="faculty_id" required>
                            <option value="">Select Faculty</option>
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
            <button type="submit" class="btn btn-primary" id="saveCourseBtn" form="courseForm">
                Save
            </button>
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
  faculties: {
    all: '{{ route('faculties.all') }}'
  },
  courses: {
    stats: '{{ route('courses.stats') }}',
    store: '{{ route('courses.store') }}',
    show: '{{ route('courses.show', ':id') }}',
    destroy: '{{ route('courses.destroy', ':id') }}',
    datatable: '{{ route('courses.datatable') }}',
  }
};

const SELECTORS = {
  courseForm: '#courseForm',
  courseModal: '#courseModal',
  addCourseBtn: '#addCourseBtn',
  saveCourseBtn: '#saveCourseBtn',
  coursesTable: '#courses-table',
  clearFiltersBtn: '#clearFiltersBtn',
  searchCode: '#search_code',
  searchTitle: '#search_title',
  searchFaculty: '#search_faculty',
  facultySelect: '#faculty_id',
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
  fetchCourseStats() {
    return this.request({ url: ROUTES.courses.stats, method: 'GET' });
  },
  fetchFaculties() {
    return this.request({ url: ROUTES.faculties.all, method: 'GET' });
  },
  fetchCourse(id) {
    return this.request({ url: Utils.replaceRouteId(ROUTES.courses.show, id), method: 'GET' });
  },
  saveCourse(data, id = null) {
    const url = id ? Utils.replaceRouteId(ROUTES.courses.show, id) : ROUTES.courses.store;
    const method = id ? 'PUT' : 'POST';
    return this.request({ url, method, data });
  },
  deleteCourse(id) {
    return this.request({ url: Utils.replaceRouteId(ROUTES.courses.destroy, id), method: 'DELETE' });
  }
};

// ===========================
// DROPDOWN MANAGEMENT
// ===========================
const DropdownManager = {
  loadFaculties(selector = SELECTORS.searchFaculty, selectedId = null) {
    return ApiService.fetchFaculties()
      .done((response) => {
        const faculties = response.data;
        const $select = $(selector);
        $select.empty().append('<option value="">Select Faculty</option>');
        faculties.forEach((faculty) => {
          $select.append($('<option>', { value: faculty.id, text: faculty.name }));
        });
        if (selectedId) {
          $select.val(selectedId);
        }
        $select.trigger('change');
      })
      .fail(() => {
        Utils.showError('Failed to load faculties');
      });
  }
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================
const StatsManager = {
  loadCourseStats() {
    Utils.toggleLoadingState('courses', true);
    Utils.toggleLoadingState('with-prerequisites', true);
    Utils.toggleLoadingState('without-prerequisites', true);
    ApiService.fetchCourseStats()
      .done((response) => {
        const data = response.data;
        $('#courses-value').text(data.total.total ?? '--');
        $('#courses-last-updated').text(data.total.lastUpdateTime ?? '--');
        $('#with-prerequisites-value').text(data.withPrerequisites.total ?? '--');
        $('#with-prerequisites-last-updated').text(data.withPrerequisites.lastUpdateTime ?? '--');
        $('#without-prerequisites-value').text(data.withoutPrerequisites.total ?? '--');
        $('#without-prerequisites-last-updated').text(data.withoutPrerequisites.lastUpdateTime ?? '--');
        Utils.toggleLoadingState('courses', false);
        Utils.toggleLoadingState('with-prerequisites', false);
        Utils.toggleLoadingState('without-prerequisites', false);
      })
      .fail(() => {
        $('#courses-value, #with-prerequisites-value, #without-prerequisites-value').text('N/A');
        $('#courses-last-updated, #with-prerequisites-last-updated, #without-prerequisites-last-updated').text('N/A');
        Utils.toggleLoadingState('courses', false);
        Utils.toggleLoadingState('with-prerequisites', false);
        Utils.toggleLoadingState('without-prerequisites', false);
        Utils.showError('Failed to load course statistics');
      });
  }
};

// ===========================
// COURSE CRUD OPERATIONS
// ===========================
const CourseManager = {
  handleAddCourse() {
    $(SELECTORS.addCourseBtn).on('click', () => {
      $(SELECTORS.courseForm)[0].reset();
      $('#course_id').val('');
      $(SELECTORS.courseModal + ' .modal-title').text('Add Course');
      $(SELECTORS.saveCourseBtn).text('Save');
      DropdownManager.loadFaculties(SELECTORS.facultySelect);
      $(SELECTORS.courseModal).modal('show');
    });
  },
  handleCourseFormSubmit() {
    $(SELECTORS.courseForm).on('submit', function (e) {
      e.preventDefault();
      const courseId = $('#course_id').val();
      const formData = $(SELECTORS.courseForm).serialize();
      const $submitBtn = $(SELECTORS.saveCourseBtn);
      const originalText = $submitBtn.text();
      $submitBtn.prop('disabled', true).text('Saving...');
      ApiService.saveCourse(formData, courseId || null)
        .done(() => {
          $(SELECTORS.courseModal).modal('hide');
          $(SELECTORS.coursesTable).DataTable().ajax.reload(null, false);
          Utils.showSuccess('Course has been saved successfully.');
          StatsManager.loadCourseStats();
        })
        .fail((xhr) => {
          $(SELECTORS.courseModal).modal('hide');
          const message = xhr.responseJSON?.message || 'An error occurred. Please check your input.';
          Utils.showError(message);
        })
        .always(() => {
          $submitBtn.prop('disabled', false).text(originalText);
        });
    });
  },
  handleEditCourse() {
    $(document).on('click', '.editCourseBtn', function () {
      const courseId = $(this).data('id');
      ApiService.fetchCourse(courseId)
        .done((course) => {
          const crs = course.data ? course.data : course;
          $('#course_id').val(crs.id);
          $('#code').val(crs.code);
          $('#title').val(crs.title);
          $('#credit_hours').val(crs.credit_hours);
          DropdownManager.loadFaculties(SELECTORS.facultySelect, crs.faculty_id);
          $(SELECTORS.courseModal + ' .modal-title').text('Edit Course');
          $(SELECTORS.saveCourseBtn).text('Update');
          $(SELECTORS.courseModal).modal('show');
        })
        .fail(() => {
          Utils.showError('Failed to fetch course data.');
        });
    });
  },
  handleDeleteCourse() {
    $(document).on('click', '.deleteCourseBtn', function () {
      const courseId = $(this).data('id');
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
          ApiService.deleteCourse(courseId)
            .done(() => {
              $(SELECTORS.coursesTable).DataTable().ajax.reload(null, false);
              Utils.showSuccess('Course has been deleted.');
              StatsManager.loadCourseStats();
            })
            .fail((xhr) => {
              const message = xhr.responseJSON?.message || 'Failed to delete course.';
              Utils.showError(message);
            });
        }
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
    this.bindSearchEvents();
    DropdownManager.loadFaculties(SELECTORS.searchFaculty);
  },
  initSearchSelect2() {
    $(SELECTORS.searchFaculty).select2({
      theme: 'bootstrap-5',
      placeholder: 'Select Faculty',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#courseSearchCollapse')
    });
  },
  bindSearchEvents() {
    $(SELECTORS.clearFiltersBtn).on('click', () => {
      $(`${SELECTORS.searchCode}, ${SELECTORS.searchTitle}, ${SELECTORS.searchFaculty}`).val('').trigger('change');
      $(SELECTORS.coursesTable).DataTable().ajax.reload();
    });
    $(`${SELECTORS.searchCode}, ${SELECTORS.searchTitle}, ${SELECTORS.searchFaculty}`).on('keyup change', () => {
      $(SELECTORS.coursesTable).DataTable().ajax.reload();
    });
  }
};

// ===========================
// SELECT2 INITIALIZATION
// ===========================
const Select2Manager = {
  initCourseModalSelect2() {
    $(SELECTORS.facultySelect).select2({
      theme: 'bootstrap-5',
      placeholder: 'Select Faculty',
      allowClear: true,
      width: '100%',
      dropdownParent: $(SELECTORS.courseModal)
    });
  }
};

// ===========================
// MAIN APPLICATION
// ===========================
const CourseManagementApp = {
  init() {
    StatsManager.loadCourseStats();
    CourseManager.handleAddCourse();
    CourseManager.handleCourseFormSubmit();
    CourseManager.handleEditCourse();
    CourseManager.handleDeleteCourse();
    Select2Manager.initCourseModalSelect2();
    SearchManager.initializeAdvancedSearch();
  }
};

$(document).ready(() => {
  CourseManagementApp.init();
});
</script>
@endpush 