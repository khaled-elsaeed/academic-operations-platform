@extends('layouts.home')

@section('title', 'Course Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
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

  <!-- Page Header and Actions -->
  <x-ui.page-header 
    title="Courses"
    description="Manage all course records and add new courses using the options on the right."
    icon="bx bx-book"
  >
    <button class="btn btn-primary" id="addCourseBtn" type="button" data-bs-toggle="modal" data-bs-target="#courseModal">
      <i class="bx bx-plus me-1"></i> Add Course
    </button>
  </x-ui.page-header>

  <!-- Courses DataTable -->
  <x-ui.datatable
    :headers="['ID', 'Code', 'Title', 'Credit Hours', 'Faculty', 'Prerequisites Count', 'Prerequisites', 'Action']"
    :columns="[
        ['data' => 'id', 'name' => 'id'],
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
  />

  <!-- Add/Edit Course Modal -->
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
      <button type="submit" class="btn btn-primary" id="saveCourseBtn" form="courseForm">Save</button>
    </x-slot>
  </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
/**
 * Course Management System JavaScript
 * Handles CRUD operations for courses
 */

// ===========================
// UTILITY FUNCTIONS
// ===========================

/**
 * Shows success notification
 * @param {string} message - Success message to display
 */
function showSuccess(message) {
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: message,
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true
  });
}

/**
 * Shows error notification
 * @param {string} message - Error message to display
 */
function showError(message) {
  Swal.fire('Error', message, 'error');
}

/**
 * Shows/hides loading spinners and content for stat2 component
 * @param {string} elementId - Base element ID
 * @param {boolean} isLoading - Whether to show loading state
 */
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

// ===========================
// DROPDOWN POPULATION
// ===========================

/**
 * Loads all faculties into the faculty select dropdown
 * @param {number|null} selectedId - The faculty ID to preselect (optional)
 * @returns {Promise} jQuery promise
 */
function loadFaculties(selectedId = null) {
  return $.ajax({
    url: '{{ route('courses.faculties') }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      const $facultySelect = $('#faculty_id');
      
      // Clear existing options
      $facultySelect.empty().append('<option value="">Select Faculty</option>');
      
      // Add faculty options
      data.forEach(function (faculty) {
        $facultySelect.append(
          $('<option>', { value: faculty.id, text: faculty.name })
        );
      });
      
      // Set selected value if provided
      if (selectedId) {
        $facultySelect.val(selectedId).trigger('change');
      }
      
      // Initialize Select2 if not already initialized
      if (!$facultySelect.hasClass('select2-hidden-accessible')) {
        $facultySelect.select2({
          theme: 'bootstrap-5',
          placeholder: 'Select Faculty',
          allowClear: true,
          width: '100%',
          dropdownParent: $('#courseModal')
        });
      }
    },
    error: function() {
      showError('Failed to load faculties');
    }
  });
}

// ===========================
// STATISTICS MANAGEMENT
// ===========================

/**
 * Loads course statistics and updates stat cards
 */
function loadCourseStats() {
  // Show loading state for all stats
  toggleLoadingState('courses', true);
  toggleLoadingState('with-prerequisites', true);
  toggleLoadingState('without-prerequisites', true);
  
  $.ajax({
    url: '{{ route('courses.stats') }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      // Update course statistics
      $('#courses-value').text(data.total.total ?? '--');
      $('#courses-last-updated').text(data.total.lastUpdateTime ?? '--');
      $('#with-prerequisites-value').text(data.withPrerequisites.total ?? '--');
      $('#with-prerequisites-last-updated').text(data.withPrerequisites.lastUpdateTime ?? '--');
      $('#without-prerequisites-value').text(data.withoutPrerequisites.total ?? '--');
      $('#without-prerequisites-last-updated').text(data.withoutPrerequisites.lastUpdateTime ?? '--');
      // Hide loading state
      toggleLoadingState('courses', false);
      toggleLoadingState('with-prerequisites', false);
      toggleLoadingState('without-prerequisites', false);
    },
    error: function() {
      // Show error state
      $('#courses-value, #with-prerequisites-value, #without-prerequisites-value').text('N/A');
      $('#courses-last-updated, #with-prerequisites-last-updated, #without-prerequisites-last-updated').text('N/A');
      toggleLoadingState('courses', false);
      toggleLoadingState('with-prerequisites', false);
      toggleLoadingState('without-prerequisites', false);
      showError('Failed to load course statistics');
    }
  });
}

// ===========================
// COURSE CRUD OPERATIONS
// ===========================

/**
 * Handles the Add Course button click event
 */
function handleAddCourseBtn() {
  $('#addCourseBtn').on('click', function () {
    $('#courseForm')[0].reset();
    $('#course_id').val('');
    $('#courseModal .modal-title').text('Add Course');
    $('#saveCourseBtn').text('Save');
    
    // Destroy existing Select2 if initialized
    if ($('#faculty_id').hasClass('select2-hidden-accessible')) {
      $('#faculty_id').select2('destroy');
    }
    
    // Load dropdown data
    loadFaculties();
    
    $('#courseModal').modal('show');
  });
}

/**
 * Handles the Add/Edit Course form submission
 */
function handleCourseFormSubmit() {
  $('#courseForm').on('submit', function (e) {
    e.preventDefault();
    
    const courseId = $('#course_id').val();
    const url = courseId
              ? '{{ route('courses.show', ':id') }}'.replace(':id', courseId)
      : '{{ route('courses.store') }}';
    const method = courseId ? 'PUT' : 'POST';
    const formData = $(this).serialize();
    
    // Disable submit button during request
    const $submitBtn = $('#saveCourseBtn');
    const originalText = $submitBtn.text();
    $submitBtn.prop('disabled', true).text('Saving...');
    
    $.ajax({
      url: url,
      method: method,
      data: formData,
      success: function () {
        $('#courseModal').modal('hide');
        $('#courses-table').DataTable().ajax.reload(null, false);
        showSuccess('Course has been saved successfully.');
        loadCourseStats(); // Refresh stats
      },
      error: function (xhr) {
        const message = xhr.responseJSON?.message || 'An error occurred. Please check your input.';
        showError(message);
      },
      complete: function() {
        $submitBtn.prop('disabled', false).text(originalText);
      }
    });
  });
}

/**
 * Handles the Edit Course button click event (delegated)
 */
function handleEditCourseBtn() {
  $(document).on('click', '.editCourseBtn', function () {
    const courseId = $(this).data('id');
    
    $.ajax({
      url: '{{ route('courses.show', ':id') }}'.replace(':id', courseId),
      method: 'GET',
      success: function (course) {
        // Prefer course.data if exists, fallback to course
        const crs = course.data ? course.data : course;
        
        // Populate form fields
        $('#course_id').val(crs.id);
        $('#code').val(crs.code);
        $('#title').val(crs.title);
        $('#credit_hours').val(crs.credit_hours);
        
        // Destroy existing Select2 if initialized
        if ($('#faculty_id').hasClass('select2-hidden-accessible')) {
          $('#faculty_id').select2('destroy');
        }
        
        // Load dropdowns with preselected values
        loadFaculties(crs.faculty_id);
        
        // Update modal
        $('#courseModal .modal-title').text('Edit Course');
        $('#saveCourseBtn').text('Update');
        $('#courseModal').modal('show');
      },
      error: function () {
        showError('Failed to fetch course data.');
      }
    });
  });
}

/**
 * Handles the Delete Course button click event (delegated)
 */
function handleDeleteCourseBtn() {
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
        $.ajax({
          url: '{{ route('courses.destroy', ':id') }}'.replace(':id', courseId),
          method: 'DELETE',
          success: function () {
            $('#courses-table').DataTable().ajax.reload(null, false);
            showSuccess('Course has been deleted.');
            loadCourseStats(); // Refresh stats
          },
          error: function (xhr) {
            const message = xhr.responseJSON?.message || 'Failed to delete course.';
            showError(message);
          }
        });
      }
    });
  });
}

// ===========================
// INITIALIZATION
// ===========================

/**
 * Initialize all event handlers and load initial data
 */
function initializeCourseManagement() {
  // Load initial data
  loadCourseStats();
  
  // Initialize CRUD handlers
  handleAddCourseBtn();
  handleCourseFormSubmit();
  handleEditCourseBtn();
  handleDeleteCourseBtn();

  // Handle modal cleanup when closed
  $('#courseModal').on('hidden.bs.modal', function () {
    // Destroy Select2 when modal is closed to prevent conflicts
    if ($('#faculty_id').hasClass('select2-hidden-accessible')) {
      $('#faculty_id').select2('destroy');
    }
  });
}

// ===========================
// DOCUMENT READY
// ===========================

$(document).ready(function () {
  initializeCourseManagement();
});
</script>
@endpush 