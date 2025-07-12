@extends('layouts.home')

@section('title', 'Edit Available Course | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Page Header -->
  <x-ui.page-header 
    title="Edit Available Course"
    description="Update the available course for a term, program, and level"
    icon="bx bx-edit"
  />

  <!-- Edit Available Course Form -->
  <div class="card">
    <div class="card-header d-flex align-items-center">
      <i class="bx bx-edit me-2"></i>
      <h5 class="mb-0">Available Course Details</h5>
    </div>
    <div class="card-body">
      <form id="availableCourseForm" method="POST" action="{{ route('available_courses.update', $availableCourse->id) }}">
        @csrf
        @method('PUT')
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
                      <!-- Eligibility rows will be added here by JS -->
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
            <i class="bx bx-save me-1"></i>
            Update Available Course
          </button>
          <a href="{{ route('available_courses.index') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
// ===================== Dropdown Data Fetch =====================
function fetchAllDropdownData(availableCourseId) {
  return $.when(
    $.getJSON("{{ route('admin.courses.legacy.index') }}"),
    $.getJSON("{{ route('admin.terms.legacy.index') }}"),
    $.getJSON("{{ route('admin.programs.legacy.index') }}"),
    $.getJSON("{{ route('admin.levels.legacy.index') }}"),
    $.getJSON("{{ route('available_courses.show', ['available_course_id']) }}".replace('available_course_id', availableCourseId))
  );
}

// ===================== Eligibility Table Row Renderer =====================
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

// ===================== Row Number & Name Updater =====================
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

// ===================== Select2 Initializer for Eligibility =====================
function initEligibilitySelect2() {
  $('.program-select, .level-select').each(function() {
    if ($(this).hasClass('select2-hidden-accessible')) {
      $(this).select2('destroy');
    }
    $(this).select2({
      theme: 'bootstrap-5',
      placeholder: $(this).hasClass('program-select') ? 'Select Program' : 'Select Level',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#availableCourseForm')
    });
  });
}

// ===================== Add Eligibility Row =====================
function addEligibilityRow(programOptions, levelOptions, selectedProgram = '', selectedLevel = '') {
  let idx = $('#eligibilityTable tbody tr').length;
  $('#eligibilityTable tbody').append(renderEligibilityRow(idx, selectedProgram, selectedLevel, programOptions, levelOptions));
  updateRowNumbers();
  initEligibilitySelect2();
}

// ===================== Populate Select Options =====================
function populateSelectOptions($select, items, placeholder) {
  $select.empty().append(`<option value="">${placeholder}</option>`);
  (items || []).forEach(function(item) {
    $select.append($('<option>', { value: item.id, text: item.name }));
  });
}

// ===================== Initialize Course and Term Select2 =====================
function initializeCourseAndTermSelect2() {
  $('#course_id, #term_id').each(function() {
    if ($(this).hasClass('select2-hidden-accessible')) {
      $(this).select2('destroy');
    }
    $(this).select2({
      theme: 'bootstrap-5',
      placeholder: $(this).attr('id') === 'course_id' ? 'Select Course' : 'Select Term',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#availableCourseForm')
    });
  });
}

// ===================== Get Form Data =====================
function getFormData(form) {
  var data = {
    _token: form.find('input[name="_token"]').val(),
    _method: form.find('input[name="_method"]').val() || 'PUT',
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
  return data;
}

// ===================== Handle Success =====================
function handleSuccess(res) {
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: res.message || 'Available course updated successfully.',
    showConfirmButton: false,
    timer: 1800,
    timerProgressBar: true
  });
  setTimeout(function() {
    window.location.href = "{{ route('available_courses.index') }}";
  }, 1850);
}

// ===================== Handle Error =====================
function handleError(xhr) {
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
}

// ===================== Set Submit Button State =====================
function setSubmitButtonState(submitBtn, isLoading) {
  if (isLoading) {
    submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');
  } else {
    submitBtn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Update Available Course');
  }
}

// ===================== Main Document Ready =====================
$(document).ready(function () {
  let programOptions = [];
  let levelOptions = [];
  let availableCourseId = {{ $availableCourse->id }};

  fetchAllDropdownData(availableCourseId).done(function(courses, terms, programs, levels, ac) {
    // Courses
    let courseSelect = $('#course_id');
    let termSelect = $('#term_id');
    populateSelectOptions(courseSelect, (courses[0] && courses[0].data) || [], 'Select Course');
    populateSelectOptions(termSelect, (terms[0] && terms[0].data) || [], 'Select Term');
    // Programs/Levels
    programOptions = (programs[0] && programs[0].data) ? programs[0].data : [];
    levelOptions = (levels[0] && levels[0].data) ? levels[0].data : [];
    // Fill form values
    let availableCourse = ac[0].data;
    courseSelect.val(availableCourse.course_id);
    termSelect.val(availableCourse.term_id);
    $('#min_capacity').val(availableCourse.min_capacity);
    $('#max_capacity').val(availableCourse.max_capacity);
    $('#is_universal').prop('checked', availableCourse.is_universal);
    // Fill eligibility table
    $('#eligibilityTable tbody').empty();
    if (availableCourse.eligibilities && availableCourse.eligibilities.length) {
      availableCourse.eligibilities.forEach(function(e) {
        addEligibilityRow(programOptions, levelOptions, e.program_id, e.level_id);
      });
    } else {
      addEligibilityRow(programOptions, levelOptions);
    }
    initEligibilitySelect2();
    initializeCourseAndTermSelect2();
  });

  // Add row button
  $('#addEligibilityRowBtn').on('click', function() {
    addEligibilityRow(programOptions, levelOptions);
  });

  // Remove row
  $('#eligibilityTable').on('click', '.remove-eligibility-row', function() {
    $(this).closest('tr').remove();
    updateRowNumbers();
    initEligibilitySelect2();
  });

  // Universal checkbox disables eligibility table
  $('#is_universal').on('change', function() {
    if ($(this).is(':checked')) {
      $('#eligibilityTable').closest('.card').addClass('opacity-50 pointer-events-none');
      $('#eligibilityTable select, #addEligibilityRowBtn, .remove-eligibility-row').prop('disabled', true);
    } else {
      $('#eligibilityTable').closest('.card').removeClass('opacity-50 pointer-events-none');
      $('#eligibilityTable select, #addEligibilityRowBtn, .remove-eligibility-row').prop('disabled', false);
    }
  });

  // ===================== AJAX Form Submission =====================
  $('#availableCourseForm').on('submit', function(e) {
    e.preventDefault();
    var form = $(this);
    var submitBtn = form.find('button[type="submit"]');
    setSubmitButtonState(submitBtn, true);
    $('#formErrors').addClass('d-none').empty();
    var data = getFormData(form);
    $.ajax({
      url: form.attr('action'),
      method: 'POST',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: {
        'X-CSRF-TOKEN': form.find('input[name="_token"]').val()
      },
      success: handleSuccess,
      error: handleError,
      complete: function() {
        setSubmitButtonState(submitBtn, false);
      }
    });
  });
});
</script>
@endpush 