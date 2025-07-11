@extends('layouts.home')

@section('title', 'Admin Enrollment | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Page Header and Actions -->
  <x-ui.page-header 
    title="Enrollments"
    description="View and manage all student enrollments"
    icon="bx bx-list-check"
  >
    <button class="btn btn-primary" id="addEnrollmentBtn" type="button" data-bs-toggle="modal" data-bs-target="#enrollmentModal">Add Enrollment</button>
  </x-ui.page-header>

  <!-- Enrollments DataTable -->
  <x-ui.datatable
    :headers="['ID', 'Student', 'Course', 'Term', 'Action']"
    :columns="[
        ['data' => 'id', 'name' => 'id'],
        ['data' => 'student', 'name' => 'student'],
        ['data' => 'course', 'name' => 'course'],
        ['data' => 'term', 'name' => 'term'],
        ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
    ]"
    :ajax-url="route('admin.enrollments.datatable')"
    table-id="enrollments-table"
  />

  <!-- Add/Edit Enrollment Modal -->
  <x-ui.modal 
    id="enrollmentModal"
    title="Add/Edit Enrollment"
    size="lg"
    :scrollable="true"
    class="enrollment-modal"
  >
    <x-slot name="slot">
      <form id="enrollmentForm">
        <input type="hidden" id="enrollment_id" name="enrollment_id">
        <div class="row">
          <div class="col-md-4 mb-3">
            <label for="student_id" class="form-label">Student</label>
            <select class="form-control" id="student_id" name="student_id" required>
              <option value="">Select Student</option>
              <!-- Options loaded via AJAX -->
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label for="course_id" class="form-label">Course</label>
            <select class="form-control" id="course_id" name="course_id" required>
              <option value="">Select Course</option>
              <!-- Options loaded via AJAX -->
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label for="term_id" class="form-label">Term</label>
            <select class="form-control" id="term_id" name="term_id" required>
              <option value="">Select Term</option>
              <!-- Options loaded via AJAX -->
            </select>
          </div>
        </div>
      </form>
    </x-slot>
    <x-slot name="footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
        Close
      </button>
      <button type="submit" class="btn btn-primary" id="saveEnrollmentBtn" form="enrollmentForm">Save</button>
    </x-slot>
  </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
/**
 * Loads all students into the student select dropdown.
 * @function loadStudents
 * @param {number|null} selectedId - The student ID to preselect (optional).
 * @returns {void}
 */
function loadStudents(selectedId = null) {
  $.ajax({
    url: '{{ route('admin.students.datatable') }}',
    method: 'GET',
    success: function (data) {
      let $studentSelect = $('#student_id');
      $studentSelect.empty().append('<option value="">Select Student</option>');
      (data.data || []).forEach(function (student) {
        $studentSelect.append(
          $('<option>', { value: student.id, text: student.name_en + ' (' + student.academic_id + ')' })
        );
      });
      if (selectedId) $studentSelect.val(selectedId);
    }
  });
}

/**
 * Loads all courses into the course select dropdown.
 * @function loadCourses
 * @param {number|null} selectedId - The course ID to preselect (optional).
 * @returns {void}
 */
function loadCourses(selectedId = null) {
  $.ajax({
    url: '{{ route('admin.courses.legacy.index') }}',
    method: 'GET',
    success: function (data) {
      let $courseSelect = $('#course_id');
      $courseSelect.empty().append('<option value="">Select Course</option>');
      (data || []).forEach(function (course) {
        $courseSelect.append(
          $('<option>', { value: course.id, text: course.name })
        );
      });
      if (selectedId) $courseSelect.val(selectedId);
    }
  });
}

/**
 * Loads all terms into the term select dropdown.
 * @function loadTerms
 * @param {number|null} selectedId - The term ID to preselect (optional).
 * @returns {void}
 */
function loadTerms(selectedId = null) {
  $.ajax({
    url: '{{ route('admin.terms.index') }}',
    method: 'GET',
    success: function (data) {
      let $termSelect = $('#term_id');
      $termSelect.empty().append('<option value="">Select Term</option>');
      (data || []).forEach(function (term) {
        $termSelect.append(
          $('<option>', { value: term.id, text: term.name })
        );
      });
      if (selectedId) $termSelect.val(selectedId);
    }
  });
}

/**
 * Handles the Add Enrollment button click event.
 * @function handleAddEnrollmentBtn
 * @returns {void}
 */
function handleAddEnrollmentBtn() {
  $('#addEnrollmentBtn').on('click', function () {
    $('#enrollmentForm')[0].reset();
    $('#enrollment_id').val('');
    $('#enrollmentModal .modal-title').text('Add Enrollment');
    $('#saveEnrollmentBtn').text('Save');
    loadStudents();
    loadCourses();
    loadTerms();
    $('#enrollmentModal').modal('show');
  });
}

/**
 * Handles the Add/Edit Enrollment form submission.
 * @function handleEnrollmentFormSubmit
 * @returns {void}
 */
function handleEnrollmentFormSubmit() {
  $('#enrollmentForm').on('submit', function (e) {
    e.preventDefault();
    let enrollmentId = $('#enrollment_id').val();
    let url = enrollmentId
      ? '{{ url('admin/enrollments') }}/' + enrollmentId
      : '{{ route('admin.enrollments.store') }}';
    let method = enrollmentId ? 'PUT' : 'POST';
    let formData = $(this).serialize();
    $.ajax({
      url: url,
      method: method,
      data: formData,
      success: function (response) {
        $('#enrollmentModal').modal('hide');
        $('#enrollments-table').DataTable().ajax.reload(null, false);
        Swal.fire('Success', 'Enrollment has been saved successfully.', 'success');
      },
      error: function (xhr) {
        $('#enrollmentModal').modal('hide');
        Swal.fire('Error', 'An error occurred. Please check your input.', 'error');
      }
    });
  });
}

/**
 * Handles the Edit Enrollment button click event (delegated).
 * @function handleEditEnrollmentBtn
 * @returns {void}
 */
function handleEditEnrollmentBtn() {
  $(document).on('click', '.editEnrollmentBtn', function () {
    let enrollment = $(this).data('enrollment');
    $('#enrollment_id').val(enrollment.id);
    loadStudents(enrollment.student_id);
    loadCourses(enrollment.course_id);
    loadTerms(enrollment.term_id);
    $('#enrollmentModal .modal-title').text('Edit Enrollment');
    $('#saveEnrollmentBtn').text('Update');
    $('#enrollmentModal').modal('show');
  });
}

/**
 * Handles the Delete Enrollment button click event (delegated).
 * @function handleDeleteEnrollmentBtn
 * @returns {void}
 */
function handleDeleteEnrollmentBtn() {
  $(document).on('click', '.deleteEnrollmentBtn', function () {
    let enrollmentId = $(this).data('id');
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
          url: '{{ url('admin/enrollments') }}/' + enrollmentId,
          method: 'DELETE',
          success: function () {
            $('#enrollments-table').DataTable().ajax.reload(null, false);
            Swal.fire('Deleted!', 'Enrollment has been deleted.', 'success');
          },
          error: function () {
            Swal.fire('Error', 'Failed to delete enrollment.', 'error');
          }
        });
      }
    });
  });
}

// Main entry point
$(document).ready(function () {
  handleAddEnrollmentBtn();
  handleEnrollmentFormSubmit();
  handleEditEnrollmentBtn();
  handleDeleteEnrollmentBtn();
});
</script>
@endpush 