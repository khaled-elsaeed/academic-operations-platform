@extends('layouts.home')

@section('title', 'Course Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Courses</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-courses-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-courses">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-courses-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base bx bx-book icon-lg"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Courses with Prerequisites</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-with-prerequisites-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-with-prerequisites">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-with-prerequisites-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="icon-base bx bx-check-circle icon-lg"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Courses without Prerequisites</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-without-prerequisites-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-without-prerequisites">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-without-prerequisites-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-warning">
                <i class="icon-base bx bx-x-circle icon-lg"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
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
    :headers="['ID', 'Code', 'Title', 'Credit Hours', 'Program', 'Prerequisites Count', 'Prerequisites', 'Action']"
    :columns="[
        ['data' => 'id', 'name' => 'id'],
        ['data' => 'code', 'name' => 'code'],
        ['data' => 'title', 'name' => 'title'],
        ['data' => 'credit_hours', 'name' => 'credit_hours'],
        ['data' => 'program_name', 'name' => 'program_name'],
        ['data' => 'prerequisites_count', 'name' => 'prerequisites_count'],
        ['data' => 'prerequisites_list', 'name' => 'prerequisites_list'],
        ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
    ]"
    :ajax-url="route('admin.courses.datatable')"
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
  Swal.fire('Success', message, 'success');
}

/**
 * Shows error notification
 * @param {string} message - Error message to display
 */
function showError(message) {
  Swal.fire('Error', message, 'error');
}

/**
 * Shows/hides loading spinners and content
 * @param {string} elementId - Base element ID
 * @param {boolean} isLoading - Whether to show loading state
 */
function toggleLoadingState(elementId, isLoading) {
  const $element = $(`#${elementId}`);
  const $spinner = $(`#${elementId}-spinner`);
  const $updated = $(`#${elementId}-updated`);
  
  if (isLoading) {
    $element.hide();
    $updated.hide();
    $spinner.show();
  } else {
    $element.show();
    $updated.show();
    $spinner.hide();
  }
}

// ===========================
// DROPDOWN POPULATION
// ===========================

/**
 * Loads all programs into the program select dropdown
 * @param {number|null} selectedId - The program ID to preselect (optional)
 * @returns {Promise} jQuery promise
 */
function loadPrograms(selectedId = null) {
  return $.ajax({
    url: '{{ route('admin.courses.programs') }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      const $programSelect = $('#program_id');
      
      $programSelect.empty().append('<option value="">Select Program</option>');
      
      data.forEach(function (program) {
        $programSelect.append(
          $('<option>', { value: program.id, text: program.name })
        );
      });
      
      if (selectedId) {
        $programSelect.val(selectedId);
      }
    },
    error: function() {
      showError('Failed to load programs');
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
  toggleLoadingState('stat-courses', true);
  toggleLoadingState('stat-with-prerequisites', true);
  toggleLoadingState('stat-without-prerequisites', true);
  
  $.ajax({
    url: '{{ route('admin.courses.stats') }}',
    method: 'GET',
    success: function (response) {
      const data = response.data;
      
      // Update course statistics
      $('#stat-courses').text(data.total.total ?? '--');
      $('#stat-courses-updated').text(data.total.lastUpdateTime ?? '--');
      $('#stat-with-prerequisites').text(data.withPrerequisites.total ?? '--');
      $('#stat-with-prerequisites-updated').text(data.withPrerequisites.lastUpdateTime ?? '--');
      $('#stat-without-prerequisites').text(data.withoutPrerequisites.total ?? '--');
      $('#stat-without-prerequisites-updated').text(data.withoutPrerequisites.lastUpdateTime ?? '--');
      
      // Hide loading state
      toggleLoadingState('stat-courses', false);
      toggleLoadingState('stat-with-prerequisites', false);
      toggleLoadingState('stat-without-prerequisites', false);
    },
    error: function() {
      // Show error state
      $('#stat-courses, #stat-with-prerequisites, #stat-without-prerequisites').text('N/A');
      $('#stat-courses-updated, #stat-with-prerequisites-updated, #stat-without-prerequisites-updated').text('N/A');
      
      toggleLoadingState('stat-courses', false);
      toggleLoadingState('stat-with-prerequisites', false);
      toggleLoadingState('stat-without-prerequisites', false);
      
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
    
    // Load dropdown data
    loadPrograms();
    
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
      ? '{{ url('admin/courses') }}/' + courseId
      : '{{ route('admin.courses.store') }}';
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
      url: '{{ url('admin/courses') }}/' + courseId,
      method: 'GET',
      success: function (course) {
        // Populate form fields
        $('#course_id').val(course.id);
        $('#code').val(course.code);
        $('#title').val(course.title);
        $('#credit_hours').val(course.credit_hours);
        
        // Load dropdowns with preselected values
        loadPrograms(course.program_id);
        
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
          url: '{{ url('admin/courses') }}/' + courseId,
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
}

// ===========================
// DOCUMENT READY
// ===========================

$(document).ready(function () {
  initializeCourseManagement();
});
</script>
@endpush 