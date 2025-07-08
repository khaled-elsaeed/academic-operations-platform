@extends('layouts.home')

@section('title', 'Admin Dashboard | Available Courses | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Page Header and Actions -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold">Available Courses</h4>
    <div>
      <button class="btn btn-success me-2" id="importAvailableCoursesBtn" type="button" data-bs-toggle="modal" data-bs-target="#importAvailableCoursesModal">
        Import Available Courses
      </button>
      <button class="btn btn-primary" id="addAvailableCourseBtn" type="button" data-bs-toggle="modal" data-bs-target="#availableCourseModal">Add Available Course</button>
    </div>
  </div>

  <!-- Available Courses DataTable -->
  <x-ui.datatable
    :headers="['ID', 'Course', 'Term', 'Program', 'Level', 'Capacity', 'Action']"
    :columns="[
        ['data' => 'id', 'name' => 'id'],
        ['data' => 'course', 'name' => 'course'],
        ['data' => 'term', 'name' => 'term'],
        ['data' => 'program', 'name' => 'program'],
        ['data' => 'level', 'name' => 'level'],
        ['data' => 'capacity', 'name' => 'capacity'],
        ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
    ]"
    :ajax-url="route('admin.available_courses.datatable')"  {{-- Placeholder route --}}
    table-id="available-courses-table"
  />

  <!-- Add/Edit Available Course Modal -->
  <x-ui.modal 
    id="availableCourseModal"
    title="Add/Edit Available Course"
    size="lg"
    :scrollable="true"
    class="available-course-modal"
  >
    <x-slot name="slot">
      <form id="availableCourseForm">
        <input type="hidden" id="available_course_id" name="available_course_id">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="course_id" class="form-label">Course</label>
            <select class="form-control" id="course_id" name="course_id" required>
              <option value="">Select Course</option>
              <!-- Options loaded via AJAX -->
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="term_id" class="form-label">Term</label>
            <select class="form-control" id="term_id" name="term_id" required>
              <option value="">Select Term</option>
              <!-- Options loaded via AJAX -->
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="program_id" class="form-label">Program</label>
            <select class="form-control" id="program_id" name="program_id" required>
              <option value="">Select Program</option>
              <!-- Options loaded via AJAX -->
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="level" class="form-label">Level</label>
            <input type="text" class="form-control" id="level" name="level" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="capacity" class="form-label">Capacity</label>
            <input type="number" class="form-control" id="capacity" name="capacity" required>
          </div>
        </div>
      </form>
    </x-slot>
    <x-slot name="footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
        Close
      </button>
      <button type="submit" class="btn btn-primary" id="saveAvailableCourseBtn" form="availableCourseForm">Save</button>
    </x-slot>
  </x-ui.modal>

  <!-- Import Available Courses Modal -->
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
          <label for="available_courses_file" class="form-label">Upload Excel File</label>
          <input type="file" class="form-control" id="available_courses_file" name="available_courses_file" accept=".xlsx,.xls" required>
        </div>
        <div class="alert alert-info d-flex align-items-center justify-content-between p-3 mb-3">
          <div>
            <i class="bx bx-info-circle me-2"></i>
            <span class="small">Use the template for correct available course data formatting.</span>
          </div>
          <a href="{{ route('admin.available_courses.template') }}" class="btn btn-sm btn-outline-primary" download>
            <i class="bx bx-download me-1"></i>Template
          </a>
        </div>
      </form>
    </x-slot>
    <x-slot name="footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
        Close
      </button>
      <button type="submit" class="btn btn-success" id="importAvailableCoursesSubmitBtn" form="importAvailableCoursesForm">Import</button>
    </x-slot>
  </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Load select options for Course, Term, Program
  function loadCourses(selectedId = null) {
    $.ajax({
      url: '{{ route('admin.courses.index') }}', // Placeholder route
      method: 'GET',
      success: function (data) {
        let $select = $('#course_id');
        $select.empty().append('<option value="">Select Course</option>');
        data.forEach(function (item) {
          $select.append($('<option>', { value: item.id, text: item.name }));
        });
        if (selectedId) $select.val(selectedId);
      }
    });
  }
  function loadTerms(selectedId = null) {
    $.ajax({
      url: '{{ route('admin.terms.index') }}', // Placeholder route
      method: 'GET',
      success: function (data) {
        let $select = $('#term_id');
        $select.empty().append('<option value="">Select Term</option>');
        data.forEach(function (item) {
          $select.append($('<option>', { value: item.id, text: item.name }));
        });
        if (selectedId) $select.val(selectedId);
      }
    });
  }
  function loadPrograms(selectedId = null) {
    $.ajax({
      url: '{{ route('admin.programs.index') }}',
      method: 'GET',
      success: function (data) {
        let $select = $('#program_id');
        $select.empty().append('<option value="">Select Program</option>');
        data.forEach(function (item) {
          $select.append($('<option>', { value: item.id, text: item.name }));
        });
        if (selectedId) $select.val(selectedId);
      }
    });
  }

  // Add Available Course Button
  $('#addAvailableCourseBtn').on('click', function () {
    $('#availableCourseForm')[0].reset();
    $('#available_course_id').val('');
    $('#availableCourseModal .modal-title').text('Add Available Course');
    $('#saveAvailableCourseBtn').text('Save');
    loadCourses();
    loadTerms();
    loadPrograms();
    $('#availableCourseModal').modal('show');
  });

  // Handle Add/Edit Available Course Form Submit
  $('#availableCourseForm').on('submit', function (e) {
    e.preventDefault();
    let availableCourseId = $('#available_course_id').val();
    let url = availableCourseId
      ? '/admin/available-courses/' + availableCourseId // Placeholder URL
      : '/admin/available-courses'; // Placeholder URL
    let method = availableCourseId ? 'PUT' : 'POST';
    let formData = $(this).serialize();
    $.ajax({
      url: url,
      method: method,
      data: formData,
      success: function (response) {
        $('#availableCourseModal').modal('hide');
        $('#available-courses-table').DataTable().ajax.reload(null, false);
        Swal.fire('Success', 'Available course has been saved successfully.', 'success');
      },
      error: function (xhr) {
        $('#availableCourseModal').modal('hide');
        let msg = xhr.responseJSON?.message || 'An error occurred. Please check your input.';
        Swal.fire('Error', msg, 'error');
      }
    });
  });

  // Edit Available Course Button (delegated)
  $(document).on('click', '.editAvailableCourseBtn', function () {
    let availableCourse = $(this).data('available-course');
    $('#available_course_id').val(availableCourse.id);
    loadCourses(availableCourse.course_id);
    loadTerms(availableCourse.term_id);
    loadPrograms(availableCourse.program_id);
    $('#level').val(availableCourse.level);
    $('#capacity').val(availableCourse.capacity);
    $('#availableCourseModal .modal-title').text('Edit Available Course');
    $('#saveAvailableCourseBtn').text('Update');
    $('#availableCourseModal').modal('show');
  });

  // Delete Available Course Button (delegated)
  $(document).on('click', '.deleteAvailableCourseBtn', function () {
    let availableCourseId = $(this).data('id');
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
          url: '/admin/available-courses/' + availableCourseId, // Placeholder URL
          method: 'DELETE',
          success: function () {
            $('#available-courses-table').DataTable().ajax.reload(null, false);
            Swal.fire('Deleted!', 'Available course has been deleted.', 'success');
          },
          error: function (xhr) {
            let msg = xhr.responseJSON?.message || 'Failed to delete available course.';
            Swal.fire('Error', msg, 'error');
          }
        });
      }
    });
  });

  // Import Available Courses AJAX
  $('#importAvailableCoursesForm').on('submit', function(e) {
    e.preventDefault();
    let formData = new FormData(this);
    $('#importAvailableCoursesSubmitBtn').prop('disabled', true).text('Importing...');
    $.ajax({
      url: '{{ route('admin.available_courses.import') }}',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        $('#importAvailableCoursesModal').modal('hide');
        $('#available-courses-table').DataTable().ajax.reload(null, false);
        Swal.fire('Success', response.message, 'success');
      },
      error: function(xhr) {
        $('#importAvailableCoursesModal').modal('hide');
        let msg = xhr.responseJSON?.message || 'Import failed. Please check your file.';
        Swal.fire('Error', msg, 'error');
      },
      complete: function() {
        $('#importAvailableCoursesSubmitBtn').prop('disabled', false).text('Import');
      }
    });
  });
});
</script>
@endpush 