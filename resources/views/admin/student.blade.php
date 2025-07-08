@extends('layouts.home')

@section('title', 'Admin Dashboard | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
              <span class="text-heading">Total Students</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-students-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-students">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-students-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-primary">
                <i class="icon-base bx bx-group icon-lg"></i>
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
              <span class="text-heading">Total Male Students</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-male-students-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-male-students">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-male-students-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-danger">
                <i class="icon-base bx bx-user-plus icon-lg"></i>
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
              <span class="text-heading">Total Female Students</span>
              <div class="d-flex align-items-center my-1">
                <div id="stat-female-students-spinner" class="spinner-border spinner-border-sm me-2" role="status" style="display: none;">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="mb-0 me-2" id="stat-female-students">--</h4>
              </div>
              <small class="mb-0">Last update: <span id="stat-female-students-updated">--</span></small>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-success">
                <i class="icon-base bx bx-user-check icon-lg"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Page Header and Actions -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold">Students</h4>
    <div>
      <button class="btn btn-success me-2" id="importStudentsBtn" type="button" data-bs-toggle="modal" data-bs-target="#importStudentsModal">
        Import Students
      </button>
      <button class="btn btn-primary" id="addStudentBtn" type="button" data-bs-toggle="modal" data-bs-target="#studentModal">Add Student</button>
    </div>
  </div>

  <!-- Students DataTable -->
  <x-ui.datatable
    :headers="['ID', 'Name (EN)', 'Name (AR)', 'Academic ID', 'National ID', 'Academic Email', 'Level', 'CGPA', 'Gender', 'Program', 'Action']"
    :columns="[
        ['data' => 'id', 'name' => 'id'],
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
    :ajax-url="route('admin.students.datatable')"
    table-id="students-table"
  />

  <!-- Add/Edit Student Modal -->
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
          <div class="col-md-6 mb-3">
            <label for="name_en" class="form-label">Name (EN)</label>
            <input type="text" class="form-control" id="name_en" name="name_en" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="name_ar" class="form-label">Name (AR)</label>
            <input type="text" class="form-control" id="name_ar" name="name_ar" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="academic_id" class="form-label">Academic ID</label>
            <input type="text" class="form-control" id="academic_id" name="academic_id" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="national_id" class="form-label">National ID</label>
            <input type="text" class="form-control" id="national_id" name="national_id" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="academic_email" class="form-label">Academic Email</label>
            <input type="email" class="form-control" id="academic_email" name="academic_email" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="level" class="form-label">Level</label>
            <input type="text" class="form-control" id="level" name="level" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="cgpa" class="form-label">CGPA</label>
            <input type="number" step="0.01" class="form-control" id="cgpa" name="cgpa" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="gender" class="form-label">Gender</label>
            <select class="form-control" id="gender" name="gender" required>
              <option value="">Select Gender</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>
          </div>
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
      <button type="submit" class="btn btn-primary" id="saveStudentBtn" form="studentForm">Save</button>
    </x-slot>
  </x-ui.modal>

  <!-- Import Students Modal -->
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
          <input type="file" class="form-control" id="students_file" name="students_file" accept=".xlsx,.xls" required>
        </div>
        <div class="alert alert-info d-flex align-items-center justify-content-between p-3 mb-3">
          <div>
            <i class="bx bx-info-circle me-2"></i>
            <span class="small">Use the template for correct student data formatting.</span>
          </div>
          <a href="{{ route('admin.students.template') }}" class="btn btn-sm btn-outline-primary" download>
            <i class="bx bx-download me-1"></i>Template
          </a>
        </div>
      </form>
    </x-slot>
    <x-slot name="footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
        Close
      </button>
      <button type="submit" class="btn btn-success" id="importStudentsSubmitBtn" form="importStudentsForm">Import</button>
    </x-slot>
  </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Load programs into select
    function loadPrograms(selectedId = null) {
      $.ajax({
        url: '{{ route('admin.programs.index') }}',
        method: 'GET',
        success: function (data) {
          let $programSelect = $('#program_id');
          $programSelect.empty().append('<option value="">Select Program</option>');
          data.forEach(function (program) {
            $programSelect.append(
              $('<option>', { value: program.id, text: program.name })
            );
          });
          if (selectedId) $programSelect.val(selectedId);
        }
      });
    }

    // Add Student Button
    $('#addStudentBtn').on('click', function () {
      $('#studentForm')[0].reset();
      $('#student_id').val('');
      $('#studentModal .modal-title').text('Add Student');
      $('#saveStudentBtn').text('Save');
      loadPrograms();
      $('#studentModal').modal('show');
    });

    // Handle Add/Edit Student Form Submit
    $('#studentForm').on('submit', function (e) {
      e.preventDefault();
      let studentId = $('#student_id').val();
      let url = studentId
        ? '{{ url('admin/students') }}/' + studentId
        : '{{ route('admin.students.store') }}';
      let method = studentId ? 'PUT' : 'POST';
      let formData = $(this).serialize();
      $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function (response) {
          $('#studentModal').modal('hide');
          $('#students-table').DataTable().ajax.reload(null, false);
          Swal.fire('Success', 'Student has been saved successfully.', 'success');
        },
        error: function (xhr) {
          $('#studentModal').modal('hide');
          Swal.fire('Error', 'An error occurred. Please check your input.', 'error');
        }
      });
    });

    // Edit Student Button (delegated)
    $(document).on('click', '.editStudentBtn', function () {
      let student = $(this).data('student');
      $('#student_id').val(student.id);
      $('#name_en').val(student.name_en);
      $('#name_ar').val(student.name_ar);
      $('#academic_id').val(student.academic_id);
      $('#national_id').val(student.national_id);
      $('#academic_email').val(student.academic_email);
      $('#level').val(student.level);
      $('#cgpa').val(student.cgpa);
      $('#gender').val(student.gender);
      loadPrograms(student.program_id);
      $('#studentModal .modal-title').text('Edit Student');
      $('#saveStudentBtn').text('Update');
      $('#studentModal').modal('show');
    });

    // Delete Student Button (delegated)
    $(document).on('click', '.deleteStudentBtn', function () {
      let studentId = $(this).data('id');
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
            url: '{{ url('admin/students') }}/' + studentId,
            method: 'DELETE',
            success: function () {
              $('#students-table').DataTable().ajax.reload(null, false);
              Swal.fire('Deleted!', 'Student has been deleted.', 'success');
            },
            error: function () {
              Swal.fire('Error', 'Failed to delete student.', 'error');
            }
          });
        }
      });
    });

    // Show spinners and hide stat values before AJAX
    $('#stat-students, #stat-male-students, #stat-female-students, #stat-students-updated, #stat-male-students-updated, #stat-female-students-updated').hide();
    $('#stat-students-spinner, #stat-male-students-spinner, #stat-female-students-spinner').show();

    // Load student statistics
    $.ajax({
      url: '{{ route('admin.students.stats') }}',
      method: 'GET',
      success: function(data) {
        $('#stat-students').text(data.students.total ?? '--');
        $('#stat-students-updated').text(data.students.lastUpdateTime ?? '--');
        $('#stat-male-students').text(data.maleStudents.total ?? '--');
        $('#stat-male-students-updated').text(data.maleStudents.lastUpdateTime ?? '--');
        $('#stat-female-students').text(data.femaleStudents.total ?? '--');
        $('#stat-female-students-updated').text(data.femaleStudents.lastUpdateTime ?? '--');
        // Hide spinners and show stat values
        $('#stat-students, #stat-male-students, #stat-female-students, #stat-students-updated, #stat-male-students-updated, #stat-female-students-updated').show();
        $('#stat-students-spinner, #stat-male-students-spinner, #stat-female-students-spinner').hide();
      },
      error: function() {
        $('#stat-students, #stat-male-students, #stat-female-students, #stat-students-updated, #stat-male-students-updated, #stat-female-students-updated').text('N/A');
        // Hide spinners and show stat values
        $('#stat-students, #stat-male-students, #stat-female-students, #stat-students-updated, #stat-male-students-updated, #stat-female-students-updated').show();
        $('#stat-students-spinner, #stat-male-students-spinner, #stat-female-students-spinner').hide();
      }
    });

    // Import Students AJAX
    $('#importStudentsForm').on('submit', function(e) {
      e.preventDefault();
      let formData = new FormData(this);
      $('#importStudentsSubmitBtn').prop('disabled', true).text('Importing...');
      $.ajax({
        url: '{{ route('admin.students.import') }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
          $('#importStudentsModal').modal('hide');
          $('#students-table').DataTable().ajax.reload(null, false);
          Swal.fire('Success', response.message, 'success');
        },
        error: function(xhr) {
          $('#importStudentsModal').modal('hide');
          let msg = xhr.responseJSON?.message || 'Import failed. Please check your file.';
          Swal.fire('Error', msg, 'error');
        },
        complete: function() {
          $('#importStudentsSubmitBtn').prop('disabled', false).text('Import');
        }
      });
    });
  });
</script>
@endpush

