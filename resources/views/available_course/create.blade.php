@extends('layouts.home')

@section('title', 'Add Available Course | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- ===================== Page Header ===================== -->
  <x-ui.page-header 
    title="Add Available Course"
    description="Create a new available course for a term, program, and level"
    icon="bx bx-book-add"
  />

  <!-- ===================== Add Available Course Form ===================== -->
  <div class="card">
    <div class="card-header d-flex align-items-center">
      <i class="bx bx-book-add me-2"></i>
      <h5 class="mb-0">Available Course Details</h5>
    </div>
    <div class="card-body">
      <form id="availableCourseForm" method="POST" action="{{ route('available_courses.store') }}">
        @csrf
        <div id="formErrors" class="alert alert-danger d-none"></div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="course_id" class="form-label">Course</label>
            <select class="form-control select2" id="course_id" name="course_id">
              <option value="">Select Course</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="term_id" class="form-label">Term</label>
            <select class="form-control select2" id="term_id" name="term_id">
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
                    <input class="form-check-input" type="radio" name="eligibility_mode" id="mode_individual" value="individual" checked>
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
                        <!-- Eligibility rows will be added here -->
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
            <i class="bx bx-plus me-1"></i>
            Add Available Course
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
  terms: { all: "{{ route('terms.all.with_inactive') }}" },
  programs: { all: "{{ route('programs.all') }}" },
  levels: { all: "{{ route('levels.all') }}" },
  availableCourses: { store: "{{ route('available_courses.store') }}", index: "{{ route('available_courses.index') }}" }
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
      $btn.prop('disabled', false).html('<i class="bx bx-plus me-1"></i> Add Available Course');
    }
  }
};
// ===========================
// API SERVICE
// ===========================
const ApiService = {
  fetchAllDropdowns() {
    return $.when(
      $.getJSON(ROUTES.courses.all),
      $.getJSON(ROUTES.terms.all),
      $.getJSON(ROUTES.programs.all),
      $.getJSON(ROUTES.levels.all)
    );
  },
  storeAvailableCourse(data) {
    return $.ajax({
      url: ROUTES.availableCourses.store,
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
function handleEligibilityMode(programOptions, levelOptions) {
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
    console.log('Switching to mode:', mode); // Debug log
    
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
        console.warn('Unknown eligibility mode:', mode);
        $('#eligibility-individual-section').removeClass('d-none').addClass('show');
    }
    
    // Re-initialize Select2 for the visible selects after a short delay
    setTimeout(() => {
      // Only initialize Select2 for visible sections
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
    console.log('Mode changed to:', selectedMode); // Debug log
    showSection(selectedMode);
  });

  // Set initial state based on checked radio button
  const initialMode = $('input[name="eligibility_mode"]:checked').val() || 'individual';
  console.log('Initial mode:', initialMode); // Debug log
  showSection(initialMode);
}

// ===========================
// AVAILABLE COURSE MANAGER
// ===========================
const AvailableCourseManager = {
  programOptions: [],
  levelOptions: [],
  
  initDropdownsAndTable() {
    ApiService.fetchAllDropdowns().done((courses, terms, programs, levels) => {
      // Populate dropdowns
      DropdownManager.populate($(SELECTORS.course), (courses[0] && courses[0].data) || [], 'Select Course');
      DropdownManager.populate($(SELECTORS.term), (terms[0] && terms[0].data) || [], 'Select Term');
      
      // Store options for later use
      this.programOptions = (programs[0] && programs[0].data) ? programs[0].data : [];
      this.levelOptions = (levels[0] && levels[0].data) ? levels[0].data : [];
      
      // Initialize eligibility table with first row
      EligibilityTable.addRow(this.programOptions, this.levelOptions);
      
      // Initialize Select2 for main dropdowns
      DropdownManager.initSelect2();
      
      // Initialize eligibility mode handling AFTER data is loaded
      handleEligibilityMode(this.programOptions, this.levelOptions);
      
      console.log('Initialization complete'); // Debug log
    }).fail(() => {
      console.error('Failed to load dropdown data');
      Utils.showError('Failed to load required data. Please refresh the page.');
    });
  },

  bindEvents() {
    // Add row button
    $(SELECTORS.addEligibilityRowBtn).on('click', () => {
      EligibilityTable.addRow(this.programOptions, this.levelOptions);
    });
    
    // Remove row button
    $(SELECTORS.eligibilityTable).on('click', '.remove-eligibility-row', function() {
      $(this).closest('tr').remove();
      EligibilityTable.updateRowNumbers();
      DropdownManager.initEligibilitySelect2();
    });
    
    // Universal checkbox handler (if you still need it)
    $(SELECTORS.isUniversal).on('change', function() {
      if ($(this).is(':checked')) {
        $(SELECTORS.eligibilityTable).closest('.card').addClass('opacity-50 pointer-events-none');
        $(SELECTORS.eligibilityTable + ' select, ' + SELECTORS.addEligibilityRowBtn + ', .remove-eligibility-row').prop('disabled', true);
      } else {
        $(SELECTORS.eligibilityTable).closest('.card').removeClass('opacity-50 pointer-events-none');
        $(SELECTORS.eligibilityTable + ' select, ' + SELECTORS.addEligibilityRowBtn + ', .remove-eligibility-row').prop('disabled', false);
      }
    });
    
    // Form submit handler
    $(SELECTORS.form).on('submit', this.handleFormSubmit.bind(this));
  },

  handleFormSubmit(e) {
    e.preventDefault();
    const $form = $(this);
    const $submitBtn = $form.find(SELECTORS.submitBtn);
    
    Utils.setSubmitButtonState($submitBtn, true);
    $(SELECTORS.formErrors).addClass('d-none').empty();
    
    const data = this.getFormData($form);
    const mode = $('input[name="eligibility_mode"]:checked').val();
    
    
    let valid = this.validateEligibilityMode(mode, data);
    
    if (!valid) {
      Utils.setSubmitButtonState($submitBtn, false);
      return false;
    }
    
    // Show summary
    this.showEligibilitySummary(mode);
    $('#eligibility-summary-section').removeClass('d-none');
    
    // Submit form
    ApiService.storeAvailableCourse(data)
      .done(res => {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: res.message || 'Available course created successfully.',
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
  },

  validateEligibilityMode(mode, data) {
    let valid = true;
    
    if (mode === 'all_programs') {
      const levelId = $('#allProgramsLevelSelectStep').val();
      if (!levelId) {
        $('#allProgramsLevelSelectStep').addClass('is-invalid');
        $('#allProgramsLevelFeedbackStep').text('Please select a level.');
        valid = false;
      } else {
        $('#allProgramsLevelSelectStep').removeClass('is-invalid');
        $('#allProgramsLevelFeedbackStep').text('');
        data.eligibility = [{ program_id: 'all', level_id: levelId }];
        data.is_universal = false;
      }
    } else if (mode === 'all_levels') {
      const programId = $('#allLevelsProgramSelectStep').val();
      if (!programId) {
        $('#allLevelsProgramSelectStep').addClass('is-invalid');
        $('#allLevelsProgramFeedbackStep').text('Please select a program.');
        valid = false;
      } else {
        $('#allLevelsProgramSelectStep').removeClass('is-invalid');
        $('#allLevelsProgramFeedbackStep').text('');
        data.eligibility = [{ program_id: programId, level_id: 'all' }];
        data.is_universal = false;
      }
    } else if (mode === 'universal') {
      data.is_universal = true;
      data.eligibility = [];
    } else {
      // Individual mode
      data.is_universal = false;
      if (data.eligibility.length === 0) {
        Utils.showError('Please add at least one eligibility pair.');
        valid = false;
      }
    }
    
    return valid;
  },

  showEligibilitySummary(mode) {
    let summary = '';
    
    switch(mode) {
      case 'all_programs':
        const levelText = $('#allProgramsLevelSelectStep option:selected').text();
        summary = `<b>All Programs</b> for <b>Level:</b> ${levelText}`;
        break;
      case 'all_levels':
        const programText = $('#allLevelsProgramSelectStep option:selected').text();
        summary = `<b>All Levels</b> for <b>Program:</b> ${programText}`;
        break;
      case 'universal':
        summary = `<b>All Programs</b> and <b>All Levels</b> (Universal)`;
        break;
      default:
        summary = 'Custom eligibility pairs as configured.';
    }
    
    $('#eligibility-summary-content').html(`<div class='mb-2'><i class='bx bx-check-circle text-success me-2'></i>${summary}</div>`);
    $('#eligibility-summary-section').show();
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