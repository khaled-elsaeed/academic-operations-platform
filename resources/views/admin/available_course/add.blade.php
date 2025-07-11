@extends('layouts.home')

@section('title', 'Add Available Course | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Page Header -->
  <x-ui.page-header 
    title="Add Available Course"
    description="Create a new available course for a term, program, and level"
    icon="bx bx-book-add"
  />

  <!-- Add Available Course Form -->
  <div class="card">
    <div class="card-header d-flex align-items-center">
      <i class="bx bx-book-add me-2"></i>
      <h5 class="mb-0">Available Course Details</h5>
    </div>
    <div class="card-body">
      <form id="availableCourseForm" method="POST" action="{{ route('admin.available_courses.store') }}">
        @csrf
        <div id="formErrors" class="alert alert-danger d-none"></div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="course_id" class="form-label">Course</label>
            <select class="form-control" id="course_id" name="course_id" required>
              <option value="">Select Course</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="term_id" class="form-label">Term</label>
            <select class="form-control" id="term_id" name="term_id" required>
              <option value="">Select Term</option>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-12 mb-3">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bx bx-shield-quarter me-2"></i> Eligibility (Program / Level)</span>
                <button type="button" class="btn btn-sm btn-success" id="addEligibilityRowBtn"><i class="bx bx-plus"></i> Add Row</button>
              </div>
              <div class="card-body p-2">
                <div class="table-responsive">
                  <table class="table table-bordered mb-0" id="eligibilityTable">
                    <thead>
                      <tr>
                        <th style="width:40px;">#</th>
                        <th>Program</th>
                        <th>Level</th>
                        <th style="width:40px;"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <!-- Eligibility rows will be added here -->
                    </tbody>
                  </table>
                </div>
                <div class="form-text mt-2">Add at least one eligibility (program/level pair) unless universal is checked.</div>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="min_capacity" class="form-label">Min Capacity</label>
            <input type="number" class="form-control" id="min_capacity" name="min_capacity" required>
          </div>
          <div class="col-md-6 mb-3">
            <label for="max_capacity" class="form-label">Max Capacity</label>
            <input type="number" class="form-control" id="max_capacity" name="max_capacity" required>
          </div>
        </div>
        <div class="mb-3">
          <label>
            <input type="checkbox" id="is_universal" name="is_universal" value="1">
            Available for all programs and levels
          </label>
        </div>
        <div class="mt-3">
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-plus me-1"></i>
            Add Available Course
          </button>
          <a href="{{ route('admin.available_courses.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * Fetches all programs, levels, courses, and terms for dropdowns.
 * @function fetchAllDropdownData
 * @returns {Promise}
 */
function fetchAllDropdownData() {
  return $.when(
    $.getJSON("{{ route('admin.courses.legacy.index') }}"),
    $.getJSON("{{ route('admin.terms.index') }}"),
    $.getJSON("{{ route('admin.programs.legacy.index') }}"),
    $.getJSON("{{ route('admin.levels.index') }}")
  );
}

/**
 * Renders a row for the eligibility table.
 * @function renderEligibilityRow
 * @param {number} idx
 * @param {string} selectedProgram
 * @param {string} selectedLevel
 * @param {Array} programOptions
 * @param {Array} levelOptions
 * @returns {string}
 */
function renderEligibilityRow(idx, selectedProgram = '', selectedLevel = '', programOptions = [], levelOptions = []) {
  let programSelect = `<select class='form-select program-select' name='eligibility[${idx}][program_id]' required><option value=''>Select Program</option>`;
  programOptions.forEach(opt => {
    programSelect += `<option value='${opt.id}' ${opt.id == selectedProgram ? 'selected' : ''}>${opt.name}</option>`;
  });
  programSelect += `</select>`;
  let levelSelect = `<select class='form-select level-select' name='eligibility[${idx}][level_id]' required><option value=''>Select Level</option>`;
  levelOptions.forEach(opt => {
    levelSelect += `<option value='${opt.id}' ${opt.id == selectedLevel ? 'selected' : ''}>${opt.name}</option>`;
  });
  levelSelect += `</select>`;
  return `<tr>
    <td class='align-middle text-center row-number'></td>
    <td>${programSelect}</td>
    <td>${levelSelect}</td>
    <td class='align-middle text-center'><button type='button' class='btn btn-sm btn-danger remove-eligibility-row'><i class='bx bx-trash'></i></button></td>
  </tr>`;
}

/**
 * Updates the row numbers and input names in the eligibility table.
 * @function updateRowNumbers
 * @returns {void}
 */
function updateRowNumbers() {
  $('#eligibilityTable tbody tr').each(function(idx) {
    $(this).find('.row-number').text(idx + 1);
    $(this).find('select').each(function() {
      let name = $(this).attr('name');
      if (name) {
        let newName = name.replace(/eligibility\[\d+\]/, `eligibility[${idx}]`);
        $(this).attr('name', newName);
      }
    });
  });
}

/**
 * Adds a new eligibility row to the table.
 * @function addEligibilityRow
 * @param {Array} programOptions
 * @param {Array} levelOptions
 * @param {string} selectedProgram
 * @param {string} selectedLevel
 * @returns {void}
 */
function addEligibilityRow(programOptions, levelOptions, selectedProgram = '', selectedLevel = '') {
  let idx = $('#eligibilityTable tbody tr').length;
  $('#eligibilityTable tbody').append(renderEligibilityRow(idx, selectedProgram, selectedLevel, programOptions, levelOptions));
  updateRowNumbers();
}

// Main entry point
$(document).ready(function () {
  let programOptions = [];
  let levelOptions = [];

  fetchAllDropdownData().done(function(courses, terms, programs, levels) {
    // Courses
    let courseSelect = $('#course_id');
    courseSelect.empty().append('<option value="">Select Course</option>');
    (courses[0] || []).forEach(function(item) {
      courseSelect.append($('<option>', { value: item.id, text: item.name }));
    });
    // Terms
    let termSelect = $('#term_id');
    termSelect.empty().append('<option value="">Select Term</option>');
    (terms[0] || []).forEach(function(item) {
      termSelect.append($('<option>', { value: item.id, text: item.name }));
    });
    // Programs/Levels
    programOptions = programs[0];
    levelOptions = levels[0].data || levels[0];
    // Add one eligibility row by default
    addEligibilityRow(programOptions, levelOptions);
  });

  // Add row button
  $('#addEligibilityRowBtn').on('click', function() {
    addEligibilityRow(programOptions, levelOptions);
  });

  // Remove row
  $('#eligibilityTable').on('click', '.remove-eligibility-row', function() {
    $(this).closest('tr').remove();
    updateRowNumbers();
  });

  // Disable eligibility table if universal is checked
  $('#is_universal').on('change', function() {
    if ($(this).is(':checked')) {
      $('#eligibilityTable').closest('.card').addClass('opacity-50 pointer-events-none');
      $('#eligibilityTable select, #addEligibilityRowBtn, .remove-eligibility-row').prop('disabled', true);
    } else {
      $('#eligibilityTable').closest('.card').removeClass('opacity-50 pointer-events-none');
      $('#eligibilityTable select, #addEligibilityRowBtn, .remove-eligibility-row').prop('disabled', false);
    }
  });

  // AJAX form submission
  $('#availableCourseForm').on('submit', function(e) {
    e.preventDefault();
    var form = $(this);
    var submitBtn = form.find('button[type="submit"]');
    submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');
    $('#formErrors').addClass('d-none').empty();
    // Gather form data as plain object
    var data = {
      _token: form.find('input[name="_token"]').val(),
      course_id: $('#course_id').val(),
      term_id: $('#term_id').val(),
      min_capacity: $('#min_capacity').val(),
      max_capacity: $('#max_capacity').val(),
      is_universal: $('#is_universal').is(':checked') ? 1 : 0,
      eligibility: []
    };
    if (!data.is_universal) {
      $('#eligibilityTable tbody tr').each(function() {
        var program_id = $(this).find('.program-select').val();
        var level_id = $(this).find('.level-select').val();
        if (program_id && level_id) {
          data.eligibility.push({ program_id: program_id, level_id: level_id });
        }
      });
    }
    $.ajax({
      url: form.attr('action'),
      method: 'POST',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: {
        'X-CSRF-TOKEN': form.find('input[name="_token"]').val()
      },
      success: function(res) {
        Swal.fire({
          icon: 'success',
          title: 'Success',
          text: res.message || 'Available course created successfully.',
          confirmButtonText: 'OK'
        }).then(() => {
          window.location.href = "{{ route('admin.available_courses.index') }}";
        });
      },
      error: function(xhr) {
        if (xhr.status === 422 && xhr.responseJSON) {
          let msg = xhr.responseJSON.message || 'Validation error.';
          let errors = xhr.responseJSON.errors || {};
          let html = `<strong>${msg}</strong><ul class='mb-0'>`;
          for (let key in errors) {
            errors[key].forEach(function(err) {
              html += `<li>${err}</li>`;
            });
          }
          html += '</ul>';
          $('#formErrors').removeClass('d-none').html(html);
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: xhr.responseJSON?.message || 'An error occurred. Please try again.'
          });
        }
      },
      complete: function() {
        submitBtn.prop('disabled', false).html('<i class="bx bx-plus me-1"></i> Add Available Course');
      }
    });
  });
});
</script>
@endpush 