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
            <select class="form-control" id="course_id" name="course_id">
              <option value="">Select Course</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="term_id" class="form-label">Term</label>
            <select class="form-control" id="term_id" name="term_id">
              <option value="">Select Term</option>
            </select>
          </div>
        </div>
        <div class="row">
          <div class="col-12 mb-3">
            <div class="card shadow-sm mb-4">
              <div class="card-header bg-white border-bottom-0 pb-2">
                <h6 class="mb-0 fw-bold">Step 1: Select Eligibility Mode
                  <i class="bx bx-info-circle text-primary ms-1" data-bs-toggle="tooltip" title="Choose how you want to assign this course: to specific pairs, all programs, all levels, or universally."></i>
                </h6>
              </div>
              <div class="card-body pt-2 pb-3">
                <div class="d-flex flex-wrap gap-3 align-items-center">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="eligibility_mode" id="mode_individual" value="individual">
                    <label class="form-check-label" for="mode_individual">
                      Individual <i class="bx bx-question-mark small text-muted" data-bs-toggle="tooltip" title="Add specific program & level pairs."></i>
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="eligibility_mode" id="mode_all_programs" value="all_programs">
                    <label class="form-check-label" for="mode_all_programs">
                      All Programs <i class="bx bx-question-mark small text-muted" data-bs-toggle="tooltip" title="Make available to all programs for a specific level."></i>
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="eligibility_mode" id="mode_all_levels" value="all_levels">
                    <label class="form-check-label" for="mode_all_levels">
                      All Levels <i class="bx bx-question-mark small text-muted" data-bs-toggle="tooltip" title="Make available to all levels for a specific program."></i>
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="eligibility_mode" id="mode_universal" value="universal">
                    <label class="form-check-label" for="mode_universal">
                      Universal
                      <i class="bx bx-question-mark small text-muted" data-bs-toggle="tooltip" title="Make available to all programs and all levels."></i>
                    </label>
                  </div>
                </div>
              </div>
            </div>
            <!-- Step 2 sections -->
            <div id="eligibility-individual-section" class="fade-section">
              <div class="card shadow-sm mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                  <span class="fw-bold"><i class="bx bx-shield-quarter me-2"></i>Step 2: Add Eligibility Pairs</span>
                  <button type="button" class="btn btn-sm btn-success" id="addEligibilityRowBtn"><i class="bx bx-plus"></i> Add Row</button>
                </div>
                <div class="card-body p-2">
                  <div class="table-responsive">
                    <table class="table table-bordered mb-0 align-middle" id="eligibilityTable">
                      <thead class="table-light">
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
                  <div class="form-text mt-2">Add at least one eligibility (program/level pair).</div>
                </div>
              </div>
            </div>
            <div id="eligibility-all-programs-section" class="d-none fade-section">
              <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><span class="fw-bold">Step 2: Choose a specific level</span></div>
                <div class="card-body">
                  <label for="allProgramsLevelSelectStep" class="form-label">Level</label>
                  <select class="form-select select2" id="allProgramsLevelSelectStep"></select>
                  <div class="invalid-feedback" id="allProgramsLevelFeedbackStep"></div>
                </div>
              </div>
            </div>
            <div id="eligibility-all-levels-section" class="d-none fade-section">
              <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><span class="fw-bold">Step 2: Choose a specific program</span></div>
                <div class="card-body">
                  <label for="allLevelsProgramSelectStep" class="form-label">Program</label>
                  <select class="form-select select2" id="allLevelsProgramSelectStep"></select>
                  <div class="invalid-feedback" id="allLevelsProgramFeedbackStep"></div>
                </div>
              </div>
            </div>
            <div id="eligibility-universal-section" class="d-none fade-section">
              <div class="card shadow-sm mb-3 border-primary">
                <div class="card-body text-center">
                  <i class="bx bx-globe bx-lg text-primary mb-2"></i>
                  <div class="alert alert-info mb-0"><b>This course will be available for all programs and all levels.</b></div>
                </div>
              </div>
            </div>
            <div id="eligibility-summary-section" class="d-none fade-section">
              <div class="card shadow-sm mb-3 border-success">
                <div class="card-body">
                  <div id="eligibility-summary-content"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="min_capacity" class="form-label">Min Capacity</label>
            <input type="number" class="form-control" id="min_capacity" name="min_capacity">
          </div>
          <div class="col-md-6 mb-3">
            <label for="max_capacity" class="form-label">Max Capacity</label>
            <input type="number" class="form-control" id="max_capacity" name="max_capacity">
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
// ===========================
// ROUTES & SELECTORS
// ===========================
const ROUTES = {
  courses: { all: "{{ route('courses.all') }}" },
  terms: { all: "{{ route('terms.all') }}" },
  programs: { all: "{{ route('programs.all') }}" },
  levels: { all: "{{ route('levels.all') }}" },
  availableCourses: {
    update: "{{ route('available_courses.update', $availableCourse->id) }}",
    show: "{{ route('available_courses.show', $availableCourse->id) }}",
    index: "{{ route('available_courses.index') }}"
  }
};
const SELECTORS = {
  form: '#availableCourseForm',
  course: '#course_id',
  term: '#term_id',
  minCapacity: '#min_capacity',
  maxCapacity: '#max_capacity',
  isUniversal: '#is_universal',
  eligibilityTable: '#eligibilityTable',
  addEligibilityRowBtn: '#addEligibilityRowBtn',
  formErrors: '#formErrors',
  submitBtn: 'button[type="submit"]'
};
// ===========================
// UTILS
// ===========================
const Utils = {
  showSuccess(message) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: message, showConfirmButton: false, timer: 1800, timerProgressBar: true });
  },
  showError(message) {
    Swal.fire({ icon: 'error', title: 'Error', text: message });
  },
  setSubmitButtonState($btn, isLoading) {
    if (isLoading) {
      $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');
    } else {
      $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Update Available Course');
    }
  }
};
// ===========================
// API SERVICE
// ===========================
const ApiService = {
  fetchAllDropdownsAndCourse() {
    return $.when(
      $.getJSON(ROUTES.courses.all),
      $.getJSON(ROUTES.terms.all),
      $.getJSON(ROUTES.programs.all),
      $.getJSON(ROUTES.levels.all),
      $.getJSON(ROUTES.availableCourses.show)
    );
  },
  updateAvailableCourse(data) {
    return $.ajax({
      url: ROUTES.availableCourses.update,
      method: 'POST',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 'X-CSRF-TOKEN': $(SELECTORS.form + ' input[name="_token"]').val() }
    });
  }
};
// ===========================
// DROPDOWN MANAGER
// ===========================
const DropdownManager = {
  populate($select, items, placeholder) {
    $select.empty().append(`<option value="">${placeholder}</option>`);
    (items || []).forEach(item => {
      $select.append($('<option>', { value: item.id, text: item.name }));
    });
  },
  initSelect2() {
    $('#course_id, #term_id').each(function() {
      if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
      $(this).select2({ theme: 'bootstrap-5', placeholder: $(this).attr('id') === 'course_id' ? 'Select Course' : 'Select Term', allowClear: true, width: '100%', dropdownParent: $(SELECTORS.form) });
    });
  },
  initEligibilitySelect2() {
    $('.program-select, .level-select').each(function() {
      if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
      $(this).select2({ theme: 'bootstrap-5', placeholder: $(this).hasClass('program-select') ? 'Select Program' : 'Select Level', allowClear: true, width: '100%', dropdownParent: $(SELECTORS.form) });
    });
  }
};
// ===========================
// ELIGIBILITY TABLE
// ===========================
const EligibilityTable = {
  renderRow(idx, selectedProgram = '', selectedLevel = '', programOptions = [], levelOptions = []) {
    let programSelect = `<select class='form-select program-select' name='eligibility[${idx}][program_id]'>`;
    programOptions.forEach(opt => { programSelect += `<option value='${opt.id}' ${opt.id == selectedProgram ? 'selected' : ''}>${opt.name}</option>`; });
    programSelect += `</select>`;
    let levelSelect = `<select class='form-select level-select' name='eligibility[${idx}][level_id]'>`;
    levelOptions.forEach(opt => { levelSelect += `<option value='${opt.id}' ${opt.id == selectedLevel ? 'selected' : ''}>${opt.name}</option>`; });
    levelSelect += `</select>`;
    return `<tr><td class='align-middle text-center row-number'></td><td>${programSelect}</td><td>${levelSelect}</td><td class='align-middle text-center'><button type='button' class='btn btn-sm btn-danger remove-eligibility-row'><i class='bx bx-trash'></i></button></td></tr>`;
  },
  updateRowNumbers() {
    $(SELECTORS.eligibilityTable + ' tbody tr').each(function(idx) {
      $(this).find('.row-number').text(idx + 1);
      $(this).find('select').each(function() {
        let name = $(this).attr('name');
        if (name) {
          let newName = name.replace(/eligibility\[\d+\]/, `eligibility[${idx}]`);
          $(this).attr('name', newName);
        }
      });
    });
  },
  addRow(programOptions, levelOptions, selectedProgram = '', selectedLevel = '') {
    let idx = $(SELECTORS.eligibilityTable + ' tbody tr').length;
    $(SELECTORS.eligibilityTable + ' tbody').append(this.renderRow(idx, selectedProgram, selectedLevel, programOptions, levelOptions));
    this.updateRowNumbers();
    DropdownManager.initEligibilitySelect2();
  }
};
// ===========================
// FIXED ELIGIBILITY MODE HANDLING
// ===========================
function handleEligibilityMode(programOptions, levelOptions, initialMode) {
  // Populate step 2 selects
  const $allProgramsLevelSelectStep = $('#allProgramsLevelSelectStep');
  const $allLevelsProgramSelectStep = $('#allLevelsProgramSelectStep');

  // Clear and populate level select
  $allProgramsLevelSelectStep.empty().append('<option value="">Select Level</option>');
  levelOptions.forEach(opt => $allProgramsLevelSelectStep.append($('<option>', { value: opt.id, text: opt.name })));

  // Clear and populate program select
  $allLevelsProgramSelectStep.empty().append('<option value="">Select Program</option>');
  programOptions.forEach(opt => $allLevelsProgramSelectStep.append($('<option>', { value: opt.id, text: opt.name })));

  // Initialize tooltips
  $('[data-bs-toggle="tooltip"]').tooltip();

  function showSection(mode) {
    // Hide all step 2 sections first using Bootstrap classes
    $('#eligibility-individual-section').addClass('d-none').removeClass('show');
    $('#eligibility-all-programs-section').addClass('d-none').removeClass('show');
    $('#eligibility-all-levels-section').addClass('d-none').removeClass('show');
    $('#eligibility-universal-section').addClass('d-none').removeClass('show');
    $('#eligibility-summary-section').addClass('d-none').removeClass('show');

    // Reset validation states
    $allProgramsLevelSelectStep.removeClass('is-invalid').val('').trigger('change');
    $('#allProgramsLevelFeedbackStep').text('');
    $allLevelsProgramSelectStep.removeClass('is-invalid').val('').trigger('change');
    $('#allLevelsProgramFeedbackStep').text('');

    // Show the appropriate section based on mode
    switch(mode) {
      case 'individual':
        $('#eligibility-individual-section').removeClass('d-none').addClass('show');
        break;
      case 'all_programs':
        $('#eligibility-all-programs-section').removeClass('d-none').addClass('show');
        break;
      case 'all_levels':
        $('#eligibility-all-levels-section').removeClass('d-none').addClass('show');
        break;
      case 'universal':
        $('#eligibility-universal-section').removeClass('d-none').addClass('show');
        break;
      default:
        $('#eligibility-individual-section').removeClass('d-none').addClass('show');
    }

    // Re-initialize Select2 for the visible selects after a short delay
    setTimeout(() => {
      if (mode === 'all_programs' && !$('#eligibility-all-programs-section').hasClass('d-none')) {
        if ($allProgramsLevelSelectStep.hasClass('select2-hidden-accessible')) {
          $allProgramsLevelSelectStep.select2('destroy');
        }
        $allProgramsLevelSelectStep.select2({ 
          theme: 'bootstrap-5', 
          placeholder: 'Select Level', 
          allowClear: true, 
          width: '100%'
        });
      }
      if (mode === 'all_levels' && !$('#eligibility-all-levels-section').hasClass('d-none')) {
        if ($allLevelsProgramSelectStep.hasClass('select2-hidden-accessible')) {
          $allLevelsProgramSelectStep.select2('destroy');
        }
        $allLevelsProgramSelectStep.select2({ 
          theme: 'bootstrap-5', 
          placeholder: 'Select Program', 
          allowClear: true, 
          width: '100%'
        });
      }
    }, 200);
  }

  // Remove any previous event handlers to avoid duplicates
  $('input[name="eligibility_mode"]').off('change.eligibilityMode');

  // Add new event handler
  $('input[name="eligibility_mode"]').on('change.eligibilityMode', function() {
    const selectedMode = $(this).val();
    showSection(selectedMode);
  });

  // Set initial state based on checked radio button or provided initialMode
  const modeToSet = initialMode || $('input[name="eligibility_mode"]:checked').val() || 'individual';
  $(`input[name="eligibility_mode"][value="${modeToSet}"]`).prop('checked', true);
  showSection(modeToSet);
}
// ===========================
// AVAILABLE COURSE MANAGER (extend)
// ===========================
const AvailableCourseManager = {
  programOptions: [],
  levelOptions: [],
  initDropdownsAndTable() {
    ApiService.fetchAllDropdownsAndCourse().done((courses, terms, programs, levels, ac) => {
      DropdownManager.populate($(SELECTORS.course), (courses[0] && courses[0].data) || [], 'Select Course');
      DropdownManager.populate($(SELECTORS.term), (terms[0] && terms[0].data) || [], 'Select Term');
      this.programOptions = (programs[0] && programs[0].data) ? programs[0].data : [];
      this.levelOptions = (levels[0] && levels[0].data) ? levels[0].data : [];
      // Fill form values
      let availableCourse = ac[0].data;
      $(SELECTORS.course).val(availableCourse.course_id);
      $(SELECTORS.term).val(availableCourse.term_id);
      $(SELECTORS.minCapacity).val(availableCourse.min_capacity);
      $(SELECTORS.maxCapacity).val(availableCourse.max_capacity);
      $(SELECTORS.isUniversal).prop('checked', availableCourse.is_universal);
      // Set eligibility mode radio and show correct section
      let eligibilityMode = availableCourse.eligibility_mode || 'individual';
      $(`input[name="eligibility_mode"][value="${eligibilityMode}"]`).prop('checked', true);
      handleEligibilityMode(this.programOptions, this.levelOptions, eligibilityMode);
      // Fill eligibility table (for individual mode)
      $(SELECTORS.eligibilityTable + ' tbody').empty();
      if (availableCourse.eligibilities && availableCourse.eligibilities.length && eligibilityMode === 'individual') {
        availableCourse.eligibilities.forEach(function(e) {
          EligibilityTable.addRow(AvailableCourseManager.programOptions, AvailableCourseManager.levelOptions, e.program_id, e.level_id);
        });
      } else if (eligibilityMode === 'all_programs') {
        // Set the level in the all programs select
        $('#allProgramsLevelSelectStep').val(availableCourse.eligibilities && availableCourse.eligibilities[0] ? availableCourse.eligibilities[0].level_id : '').trigger('change');
      } else if (eligibilityMode === 'all_levels') {
        // Set the program in the all levels select
        $('#allLevelsProgramSelectStep').val(availableCourse.eligibilities && availableCourse.eligibilities[0] ? availableCourse.eligibilities[0].program_id : '').trigger('change');
      }
      DropdownManager.initEligibilitySelect2();
      DropdownManager.initSelect2();
    });
  },
  bindEvents() {
    // Add row
    $(SELECTORS.addEligibilityRowBtn).on('click', () => {
      EligibilityTable.addRow(this.programOptions, this.levelOptions);
    });
    // Remove row
    $(SELECTORS.eligibilityTable).on('click', '.remove-eligibility-row', function() {
      $(this).closest('tr').remove();
      EligibilityTable.updateRowNumbers();
      DropdownManager.initEligibilitySelect2();
    });
    // Universal checkbox disables eligibility table
    $(SELECTORS.isUniversal).on('change', function() {
      if ($(this).is(':checked')) {
        $(SELECTORS.eligibilityTable).closest('.card').addClass('opacity-50 pointer-events-none');
        $(SELECTORS.eligibilityTable + ' select, ' + SELECTORS.addEligibilityRowBtn + ', .remove-eligibility-row').prop('disabled', true);
      } else {
        $(SELECTORS.eligibilityTable).closest('.card').removeClass('opacity-50 pointer-events-none');
        $(SELECTORS.eligibilityTable + ' select, ' + SELECTORS.addEligibilityRowBtn + ', .remove-eligibility-row').prop('disabled', false);
      }
    });
    // Form submit
    $(SELECTORS.form).on('submit', function(e) {
      e.preventDefault();
      const $form = $(this);
      const $submitBtn = $form.find(SELECTORS.submitBtn);
      Utils.setSubmitButtonState($submitBtn, true);
      $(SELECTORS.formErrors).addClass('d-none').empty();
      const data = AvailableCourseManager.getFormData($form);
      data._method = 'PUT';
      ApiService.updateAvailableCourse(data)
        .done(res => {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: res.message || 'Available course updated successfully.',
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true
          });
          setTimeout(() => { window.location.href = ROUTES.availableCourses.index; }, 1850);
        })
        .fail(xhr => {
          let msg = xhr.responseJSON?.message || 'An error occurred. Please try again.';
          let errors = xhr.responseJSON?.errors || {};
          let html = `<strong>${msg}</strong>`;
          if (Object.keys(errors).length > 0) {
            html += '<ul class="mb-0">';
            for (let key in errors) {
              errors[key].forEach(err => { html += `<li>${err}</li>`; });
            }
            html += '</ul>';
          }
          Swal.fire({
            icon: 'error',
            title: 'Error',
            html: html
          });
        })
        .always(() => { Utils.setSubmitButtonState($submitBtn, false); });
    });
  },
  getFormData($form) {
    const data = {
      _token: $form.find('input[name="_token"]').val(),
      course_id: $(SELECTORS.course).val(),
      term_id: $(SELECTORS.term).val(),
      min_capacity: $(SELECTORS.minCapacity).val(),
      max_capacity: $(SELECTORS.maxCapacity).val(),
      is_universal: $(SELECTORS.isUniversal).is(':checked') ? 1 : 0,
      eligibility: [],
      eligibility_mode: $('input[name="eligibility_mode"]:checked').val() // Ensure eligibility_mode is sent
    };
    if (!data.is_universal) {
      $(SELECTORS.eligibilityTable + ' tbody tr').each(function() {
        const program_id = $(this).find('.program-select').val();
        const level_id = $(this).find('.level-select').val();
        if (program_id && level_id) {
          data.eligibility.push({ program_id, level_id });
        }
      });
    }
    return data;
  }
};
// ===========================
// MAIN APP
// ===========================
const AvailableCourseApp = {
  init() {
    AvailableCourseManager.initDropdownsAndTable();
    AvailableCourseManager.bindEvents();
  }
};
// ===========================
// DOCUMENT READY
// ===========================
$(document).ready(() => { AvailableCourseApp.init(); });
</script>
@endpush 