@extends('layouts.home')

@section('title', 'Available Courses | AcadOps')

@push('styles')
<style>
    .group-hover-parent:hover .eligibility-badge-hover {
        background-color: #fff !important;
        color: #0dcaf0 !important;
        border: 1px solid #0dcaf0 !important;
    }
</style>
@endpush

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Page Header -->
    <x-ui.page-header 
      title="Available Courses"
      description="List of all available courses for enrollment"
      icon="bx bx-book"
    >
    <button 
      class="btn btn-success me-2" 
      id="importAvailableCoursesBtn" 
      type="button" 
      data-bs-toggle="modal" 
      data-bs-target="#importAvailableCoursesModal"
    >
      <i class="bx bx-upload me-1"></i> Import Available Courses
    </button>
  </x-ui.page-header>

  <!-- Available Courses DataTable -->
  <x-ui.datatable
    :headers="['ID', 'Course', 'Term', 'Eligibility', 'Min Capacity', 'Max Capacity', 'Action']"
    :columns="[
        ['data' => 'id', 'name' => 'id'],
        ['data' => 'course', 'name' => 'course'],
        ['data' => 'term', 'name' => 'term'],
        ['data' => 'eligibility', 'name' => 'eligibility'],
        ['data' => 'min_capacity', 'name' => 'min_capacity'],
        ['data' => 'max_capacity', 'name' => 'max_capacity'],
        ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
    ]"
    :ajax-url="route('available_courses.datatable')"
    table-id="available-courses-table"
  />

  <!-- Eligibility Modal -->
  <x-ui.modal id="eligibilityModal" title="Eligibility (Program / Level)">
    <div id="eligibilityContent"><!-- Content will be filled by JS --></div>
    @slot('footer')
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    @endslot
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
          <label for="courses_file" class="form-label">Upload Excel File</label>
          <input 
            type="file" 
            class="form-control" 
            id="courses_file" 
            name="courses_file" 
            accept=".xlsx,.xls" 
            required
          >
        </div>
        <div class="alert alert-info d-flex align-items-center justify-content-between p-3 mb-3">
          <div>
            <i class="bx bx-info-circle me-2"></i>
            <span class="small">
              The file must contain columns: course_code, term_code, program_name, level_name, min_capacity, max_capacity.
            </span>
          </div>
          <a href="{{ route('available_courses.template') }}" class="btn btn-sm btn-outline-primary">
            <i class="bx bx-download me-1"></i>Template
          </a>
        </div>
      </form>
    </x-slot>
    <x-slot name="footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      <button type="submit" class="btn btn-success" id="importAvailableCoursesSubmitBtn" form="importAvailableCoursesForm">Import</button>
    </x-slot>
  </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
// --- JS Utilities ---
function showSwal(options) {
  return Swal.fire(options);
}

function reloadAvailableCoursesTable() {
  $('#available-courses-table').DataTable().ajax.reload(null, false);
}

function showSuccess(message) {
  showSwal({ title: 'Success', text: message, icon: 'success' });
}

function showError(message) {
  showSwal({ title: 'Error', text: message, icon: 'error' });
}

/**
 * Shows import errors in a detailed modal (like enrollment import)
 * @param {Array} errors - Array of error objects
 * @param {number} importedCount - Number of successfully imported items
 * @returns {void}
 */
function showImportErrors(errors, importedCount) {
  let errorHtml = `<div class="text-start">`;
  errorHtml += `<p class="mb-3"><strong>Successfully processed: ${importedCount}</strong></p>`;
  errorHtml += `<p class="mb-3"><strong>Failed rows: ${errors.length}</strong></p>`;
  errorHtml += `<div class="table-responsive">`;
  errorHtml += `<table class="table table-sm table-bordered">`;
  errorHtml += `<thead><tr><th>Row</th><th>Errors</th><th>Data</th></tr></thead>`;
  errorHtml += `<tbody>`;

  errors.forEach(function(error) {
    // Compose error messages
    const errorMessages = Array.isArray(error.errors) ? error.errors.join(', ') :
      (error.errors.general ? error.errors.general.join(', ') :
      Object.values(error.errors).flat().join(', '));

    errorHtml += `<tr>`;
    errorHtml += `<td>${error.row}</td>`;
    errorHtml += `<td class="text-danger">${errorMessages}</td>`;
    errorHtml += `<td><small>${JSON.stringify(error.original_data)}</small></td>`;
    errorHtml += `</tr>`;
  });

  errorHtml += `</tbody></table></div></div>`;

  Swal.fire({
    title: 'Import Completed with Errors',
    html: errorHtml,
    icon: 'warning',
    width: '800px',
    confirmButtonText: 'OK'
  });
}

// --- Delete Available Course ---
function handleDeleteAvailableCourseBtn() {
  $(document).on('click', '.deleteAvailableCourseBtn', function () {
    const availableCourseId = $(this).data('id');
    showSwal({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it!'
    }).then(result => {
      if (result.isConfirmed) {
        deleteAvailableCourse(availableCourseId);
      }
    });
  });
}

function deleteAvailableCourse(id) {
  $.ajax({
    url: "{{ route('available_courses.destroy', ':id') }}".replace(':id', id),
    method: 'DELETE',
    success: () => {
      reloadAvailableCoursesTable();
      showSuccess('Available course has been deleted.');
    },
    error: xhr => {
      const msg = xhr.responseJSON?.message || 'Failed to delete available course.';
      showError(msg);
    }
  });
}

// --- Import Available Courses ---
function handleImportAvailableCoursesForm() {
  $('#importAvailableCoursesForm').on('submit', function(e) {
    e.preventDefault();
    importAvailableCourses(new FormData(this));
  });
}

function importAvailableCourses(formData) {
  const $submitBtn = $('#importAvailableCoursesSubmitBtn');
  $submitBtn.prop('disabled', true).text('Importing...');
  $.ajax({
    url: "{{ route('available_courses.import') }}",
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    success: function(response) {
      $('#importAvailableCoursesModal').modal('hide');
      $('#available-courses-table').DataTable().ajax.reload(null, false);
      Swal.fire({
        title: 'Import Completed',
        text: response.message,
        icon: 'success',
        confirmButtonText: 'OK'
      });
      if (response.data && response.data.errors && response.data.errors.length > 0) {
        showImportErrors(response.data.errors, response.data.imported_count);
      }
    },
    error: function(xhr) {
      $('#importAvailableCoursesModal').modal('hide');
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
        Swal.fire({
          title: 'Import Failed',
          html: errorMessages.join('<br>'),
          icon: 'error',
          confirmButtonText: 'OK'
        });
      } else {
        const message = response?.message || 'Import failed. Please check your file.';
        Swal.fire({
          title: 'Import Failed',
          text: message,
          icon: 'error',
          confirmButtonText: 'OK'
        });
      }
    },
    complete: function() {
      $submitBtn.prop('disabled', false).text('Import');
    }
  });
}

// --- Eligibility Modal ---
function handleShowEligibilityModal() {
  $(document).on('click', '.show-eligibility-modal', function () {
    const pairs = $(this).data('eligibility-pairs');
    renderEligibilityContent(pairs);
    const modal = new bootstrap.Modal(document.getElementById('eligibilityModal'));
    modal.show();
  });
}

function renderEligibilityContent(pairs) {
  const $content = $('#eligibilityContent');
  $content.empty();
  if (Array.isArray(pairs) && pairs.length > 0) {
    if (pairs.length === 1) {
      $content.append('<div class="mb-2"><strong>Program / Level:</strong></div>');
      $content.append(`<div class="alert alert-info">${pairs[0]}</div>`);
    } else {
      $content.append('<div class="mb-2"><strong>Programs & Levels:</strong></div>');
      let table = `<table class="table table-bordered table-sm"><thead><tr><th>#</th><th>Program / Level</th></tr></thead><tbody>`;
      pairs.forEach((pair, idx) => {
        table += `<tr><td>${idx + 1}</td><td>${pair}</td></tr>`;
      });
      table += '</tbody></table>';
      $content.append(table);
    }
  } else {
    $content.append('<div class="alert alert-warning">No eligibility pairs found.</div>');
  }
}

// --- Main Entry ---
$(document).ready(function () {
  handleDeleteAvailableCourseBtn();
  handleImportAvailableCoursesForm();
  handleShowEligibilityModal();
});
</script>
@endpush 