@extends('layouts.home')

@section('title', 'Available Courses | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Page Header -->
  <x-ui.page-header 
    title="Available Courses"
    description="List of all available courses for enrollment"
    icon="bx bx-book"
  />

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
    :ajax-url="route('admin.available_courses.datatable')"
    table-id="available-courses-table"
  />

  <!-- Eligibility Modal -->
  <x-ui.modal id="eligibilityModal" title="Eligibility (Program / Level)">
    <div id="eligibilityContent">
        <!-- Content will be filled by JS -->
    </div>
    @slot('footer')
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    @endslot
  </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
/**
 * Handles the delete available course button click event (delegated).
 * @function handleDeleteAvailableCourseBtn
 * @returns {void}
 */
function handleDeleteAvailableCourseBtn() {
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
        $.ajax({
          url: `/admin/available-courses/${availableCourseId}`,
          method: 'DELETE',
          success: () => {
            $('#available-courses-table').DataTable().ajax.reload(null, false);
            Swal.fire('Deleted!', 'Available course has been deleted.', 'success');
          },
          error: xhr => {
            const msg = xhr.responseJSON?.message || 'Failed to delete available course.';
            Swal.fire('Error', msg, 'error');
          }
        });
      }
    });
  });
}

/**
 * Handles the import available courses form submission via AJAX.
 * @function handleImportAvailableCoursesForm
 * @returns {void}
 */
function handleImportAvailableCoursesForm() {
  $('#importAvailableCoursesForm').on('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    $('#importAvailableCoursesSubmitBtn').prop('disabled', true).text('Importing...');
    $.ajax({
      url: '{{ route('admin.available_courses.import') }}',
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: response => {
        $('#importAvailableCoursesModal').modal('hide');
        $('#available-courses-table').DataTable().ajax.reload(null, false);
        Swal.fire('Success', response.message, 'success');
      },
      error: xhr => {
        $('#importAvailableCoursesModal').modal('hide');
        const msg = xhr.responseJSON?.message || 'Import failed. Please check your file.';
        Swal.fire('Error', msg, 'error');
      },
      complete: () => {
        $('#importAvailableCoursesSubmitBtn').prop('disabled', false).text('Import');
      }
    });
  });
}

/**
 * Handles the eligibility modal display logic.
 * @function handleShowEligibilityModal
 * @returns {void}
 */
function handleShowEligibilityModal() {
  $(document).on('click', '.show-eligibility-modal', function () {
    const pairs = $(this).data('eligibility-pairs');
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
    const modal = new bootstrap.Modal(document.getElementById('eligibilityModal'));
    modal.show();
  });
}

// Main entry point
$(document).ready(function () {
  handleDeleteAvailableCourseBtn();
  handleImportAvailableCoursesForm();
  handleShowEligibilityModal();
});
</script>
@endpush 