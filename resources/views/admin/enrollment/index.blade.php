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
    <div class="d-flex gap-2">
      <button class="btn btn-success" id="importEnrollmentsBtn" type="button" data-bs-toggle="modal" data-bs-target="#importEnrollmentsModal">
        <i class="bx bx-import me-1"></i>Import Enrollments
      </button>
    </div>
  </x-ui.page-header>

  <!-- Enrollments DataTable -->
  <x-ui.datatable
    :headers="['ID', 'Student', 'Course', 'Term', 'Score', 'Action']"
    :columns="[
        ['data' => 'id', 'name' => 'id', 'orderable' => true, 'searchable' => true],
        ['data' => 'student', 'name' => 'student', 'orderable' => true, 'searchable' => true],
        ['data' => 'course', 'name' => 'course', 'orderable' => true, 'searchable' => true],
        ['data' => 'term', 'name' => 'term', 'orderable' => true, 'searchable' => true],
        ['data' => 'score', 'name' => 'score', 'orderable' => true, 'searchable' => true],
        ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
    ]"
    :ajax-url="route('admin.enrollments.datatable')"
    table-id="enrollments-table"
  />

  <!-- Import Enrollments Modal -->
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
          <input type="file" class="form-control" id="enrollments_file" name="enrollments_file" accept=".xlsx,.xls" required>
        </div>
        <div class="alert alert-info d-flex align-items-center justify-content-between p-3 mb-3">
          <div>
            <i class="bx bx-info-circle me-2"></i>
            <span class="small">Use the template for correct enrollment data formatting.</span>
          </div>
          <a href="{{ route('admin.enrollments.template') }}" class="btn btn-sm btn-outline-primary" download>
            <i class="bx bx-download me-1"></i>Template
          </a>
        </div>
      </form>
    </x-slot>
    <x-slot name="footer">
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
        Close
      </button>
      <button type="submit" class="btn btn-success" id="importEnrollmentsSubmitBtn" form="importEnrollmentsForm">Import</button>
    </x-slot>
  </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
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

/**
 * Handles the Import Enrollments form submission.
 * @function handleImportEnrollmentsForm
 * @returns {void}
 */
function handleImportEnrollmentsForm() {
  $('#importEnrollmentsForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const $submitBtn = $('#importEnrollmentsSubmitBtn');
    
    $submitBtn.prop('disabled', true).text('Importing...');
    
    $.ajax({
      url: '{{ route('admin.enrollments.import') }}',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        $('#importEnrollmentsModal').modal('hide');
        $('#enrollments-table').DataTable().ajax.reload(null, false);
        
        // Show success message
        Swal.fire({
          title: 'Import Completed',
          text: response.message,
          icon: 'success',
          confirmButtonText: 'OK'
        });
        
        // If there are errors, show them in a detailed modal
        if (response.data && response.data.errors && response.data.errors.length > 0) {
          showImportErrors(response.data.errors, response.data.imported_count);
        }
      },
      error: function(xhr) {
        $('#importEnrollmentsModal').modal('hide');
        const response = xhr.responseJSON;
        if (response && response.errors && Object.keys(response.errors).length > 0) {
          // Handle validation errors
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
          // Handle general errors
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
  });
}

/**
 * Shows import errors in a detailed modal.
 * @function showImportErrors
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

// Main entry point
$(document).ready(function () {
  handleDeleteEnrollmentBtn();
  handleImportEnrollmentsForm();
});
</script>
@endpush 