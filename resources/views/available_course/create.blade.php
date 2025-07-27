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
        
        <!-- Basic Course Information -->
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="course_id" class="form-label">Course</label>
            <select class="form-control select2" id="course_id" name="course_id">
              <!-- Options will be populated dynamically -->
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label for="term_id" class="form-label">Term</label>
            <select class="form-control select2" id="term_id" name="term_id">
              <!-- Options will be populated dynamically -->
            </select>
          </div>
        </div>

        <!-- Step 1: Eligibility Mode Selection -->
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
                      Universal <i class="bx bx-question-mark small text-muted" data-bs-toggle="tooltip" title="Make available to all programs and all levels."></i>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Step 2: Eligibility Sections -->
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
                  <label for="allProgramsLevelSelect" class="form-label">Level</label>
                  <select class="form-select select2" id="allProgramsLevelSelect">
                    <!-- Options will be populated dynamically -->
                  </select>
                  <div class="invalid-feedback" id="allProgramsLevelFeedback"></div>
                </div>
              </div>
            </div>

            <div id="eligibility-all-levels-section" class="d-none fade-section">
              <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><span class="fw-bold">Step 2: Choose a specific program</span></div>
                <div class="card-body">
                  <label for="allLevelsProgramSelect" class="form-label">Program</label>
                  <select class="form-select select2" id="allLevelsProgramSelect">
                    <!-- Options will be populated dynamically -->
                  </select>
                  <div class="invalid-feedback" id="allLevelsProgramFeedback"></div>
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

        <!-- Step 3: Schedule Details -->
        <div class="row">
          <div class="col-12 mb-3">
            <div class="card shadow-sm mb-4">
              <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bx bx-time me-2"></i>Step 3: Add Schedule Details</span>
                <button type="button" class="btn btn-sm btn-success" id="addScheduleDetailRowBtn"><i class="bx bx-plus"></i> Add Row</button>
              </div>
              <div class="card-body pt-2 pb-3">
                <div id="schedule-details-table-section">
                  <div class="table-responsive">
                    <table class="table table-bordered mb-0 align-middle" id="scheduleDetailsTable">
                      <thead class="table-light">
                        <tr>
                          <th style="width:40px;">#</th>
                          <th>Schedule</th>
                          <th>Day</th>
                          <th>Slot</th>
                          <th>Group</th>
                          <th>Min Capacity</th>
                          <th>Max Capacity</th>
                          <th style="width:40px;"></th>
                        </tr>
                      </thead>
                      <tbody>
                        <!-- Schedule details rows will be added here -->
                      </tbody>
                    </table>
                  </div>
                  <div class="form-text mt-2">Add one or more schedule details (schedule, day, slot, group). Group can be from 1 to 8. <b>Set min and max capacity for each group/row below.</b></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Form Actions -->
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
// CONSTANTS & CONFIGURATION
// ===========================
const ROUTES = {
  courses: { all: "{{ route('courses.all') }}" },
  terms: { all: "{{ route('terms.all.with_inactive') }}" },
  programs: { all: "{{ route('programs.all') }}" },
  levels: { all: "{{ route('levels.all') }}" },
  schedules: { all: "{{ route('schedules.all') }}" },
  scheduleDaysSlots: { get: "{{ route('schedules.days-slots', ':id') }}" },
  availableCourses: { 
    store: "{{ route('available_courses.store') }}", 
    index: "{{ route('available_courses.index') }}" 
  }
};

const ELIGIBILITY_MODES = {
  INDIVIDUAL: 'individual',
  ALL_PROGRAMS: 'all_programs',
  ALL_LEVELS: 'all_levels',
  UNIVERSAL: 'universal'
};

// ===========================
// API SERVICE
// ===========================
const ApiService = {
  async fetchCourses() {
    return $.getJSON(ROUTES.courses.all);
  },

  async fetchTerms() {
    return $.getJSON(ROUTES.terms.all);
  },

  async fetchPrograms() {
    return $.getJSON(ROUTES.programs.all);
  },

  async fetchLevels() {
    return $.getJSON(ROUTES.levels.all);
  },

  async fetchSchedules() {
    return $.getJSON(ROUTES.schedules.all);
  },

  async fetchScheduleDaysSlots(scheduleId) {
    const url = ROUTES.scheduleDaysSlots.get.replace(':id', scheduleId);
    return $.getJSON(url);
  },

  async storeAvailableCourse(data) {
    return $.ajax({
      url: ROUTES.availableCourses.store,
      method: 'POST',
      data: JSON.stringify(data),
      contentType: 'application/json',
      headers: { 
        'X-CSRF-TOKEN': $('#availableCourseForm input[name="_token"]').val() 
      }
    });
  }
};

// Note: Using Utils object from global utilities

// ===========================
// DROPDOWN MANAGER
// ===========================
const DropdownManager = {
  programOptions: [],
  levelOptions: [],
  scheduleOptions: [],

  async initialize() {
    try {
      const [courses, terms, programs, levels, schedules] = await Promise.all([
        ApiService.fetchCourses(),
        ApiService.fetchTerms(),
        ApiService.fetchPrograms(),
        ApiService.fetchLevels(),
        ApiService.fetchSchedules()
      ]);

      // Populate main dropdowns
      Utils.populateSelect($('#course_id'), courses.data, { 
        textField: 'name', placeholder: 'Select Course' 
      }, true);
      Utils.populateSelect($('#term_id'), terms.data, { 
        textField: 'name', placeholder: 'Select Term' 
      }, true);

      // Store options for dynamic use
      this.programOptions = programs.data;
      this.levelOptions = levels.data;
      this.scheduleOptions = schedules.data;

      // Populate eligibility mode dropdowns
      Utils.populateSelect($('#allProgramsLevelSelect'), levels.data, {
        textField: 'name', placeholder: 'Select Level'
      }, true);
      Utils.populateSelect($('#allLevelsProgramSelect'), programs.data, {
        textField: 'name', placeholder: 'Select Program'
      }, true);

      // Initialize Select2
      Utils.initSelect2($('#course_id'), { placeholder: 'Select Course' });
      Utils.initSelect2($('#term_id'), { placeholder: 'Select Term' });

      return true;
    } catch (error) {
      Utils.showError('Failed to load required data. Please refresh the page.');
      return false;
    }
  },

  initEligibilitySelect2($container = $(document)) {
    $container.find('.program-select').each(function() {
      Utils.initSelect2($(this), { placeholder: 'Select Program' });
    });
    
    $container.find('.level-select').each(function() {
      Utils.initSelect2($(this), { placeholder: 'Select Level' });
    });
  },

  initScheduleDetailSelect2($container = $(document)) {
    $container.find('.schedule-select').each(function() {
      Utils.initSelect2($(this), { placeholder: 'Select Schedule' });
    });
    
    $container.find('.schedule-day-select').each(function() {
      Utils.initSelect2($(this), { placeholder: 'Select Day' });
    });
    
    $container.find('.schedule-slot-select').each(function() {
      Utils.initSelect2($(this), { placeholder: 'Select Slot' });
    });
    
    $container.find('.group-select').each(function() {
      Utils.initSelect2($(this), { placeholder: 'Select Group' });
    });
    $container.find('.min-capacity-input, .max-capacity-input').each(function() {
      // No-op, but placeholder for future select2 on these fields if needed
    });
  }
};

// ===========================
// ELIGIBILITY TABLE MANAGER
// ===========================
const EligibilityTableManager = {
  renderRow(index, selectedProgram = '', selectedLevel = '') {
    const programOptions = DropdownManager.programOptions;
    const levelOptions = DropdownManager.levelOptions;

    let programSelect = `<select class='form-select program-select' name='eligibility[${index}][program_id]'>`;
    programSelect += `<option value="">Select Program</option>`;
    programOptions.forEach(opt => {
      const selected = opt.id == selectedProgram ? 'selected' : '';
      programSelect += `<option value='${opt.id}' ${selected}>${opt.name}</option>`;
    });
    programSelect += `</select>`;

    let levelSelect = `<select class='form-select level-select' name='eligibility[${index}][level_id]'>`;
    levelSelect += `<option value="">Select Level</option>`;
    levelOptions.forEach(opt => {
      const selected = opt.id == selectedLevel ? 'selected' : '';
      levelSelect += `<option value='${opt.id}' ${selected}>${opt.name}</option>`;
    });
    levelSelect += `</select>`;

    return `
      <tr>
        <td class='align-middle text-center row-number'></td>
        <td>${programSelect}</td>
        <td>${levelSelect}</td>
        <td class='align-middle text-center'>
          <button type='button' class='btn btn-sm btn-danger remove-eligibility-row'>
            <i class='bx bx-trash'></i>
          </button>
        </td>
      </tr>
    `;
  },

  updateRowNumbers() {
    $('#eligibilityTable tbody tr').each(function(index) {
      $(this).find('.row-number').text(index + 1);
      $(this).find('select').each(function() {
        const name = $(this).attr('name');
        if (name) {
          const newName = name.replace(/eligibility\[\d+\]/, `eligibility[${index}]`);
          $(this).attr('name', newName);
        }
      });
    });
  },

  addRow(selectedProgram = '', selectedLevel = '') {
    const currentRows = $('#eligibilityTable tbody tr').length;
    const newRow = this.renderRow(currentRows, selectedProgram, selectedLevel);
    
    $('#eligibilityTable tbody').append(newRow);
    this.updateRowNumbers();
    
    // Initialize Select2 for the new row
    const $newRow = $('#eligibilityTable tbody tr:last');
    DropdownManager.initEligibilitySelect2($newRow);
  },

  removeRow($button) {
    $button.closest('tr').remove();
    this.updateRowNumbers();
  },

  clearRows() {
    $('#eligibilityTable tbody').empty();
  }
};

// ===========================
// SCHEDULE DETAILS TABLE MANAGER
// ===========================
const ScheduleDetailsTableManager = {
  scheduleDaysCache: {},

  renderRow(index, selected = {}) {
    const scheduleOptions = DropdownManager.scheduleOptions;

    let scheduleSelect = `<select class='form-select schedule-select' name='schedule_details[${index}][schedule_id]' data-row="${index}">`;
    scheduleSelect += `<option value="">Select Schedule</option>`;
    scheduleOptions.forEach(opt => {
      const selectedAttr = opt.id == selected.schedule_id ? 'selected' : '';
      scheduleSelect += `<option value='${opt.id}' ${selectedAttr}>${opt.title}</option>`;
    });
    scheduleSelect += `</select>`;

    const daySelect = `<select class='form-select schedule-day-select' name='schedule_details[${index}][schedule_day_id]' data-row="${index}" disabled></select>`;
    const slotSelect = `<select class='form-select schedule-slot-select' name='schedule_details[${index}][schedule_slot_id]' data-row="${index}" disabled></select>`;

    let groupSelect = `<select class='form-select group-select' name='schedule_details[${index}][group_number]' data-row="${index}">`;
    groupSelect += `<option value="">Select Group</option>`;
    for (let i = 1; i <= 8; i++) {
      const selectedAttr = selected.group_number == i ? 'selected' : '';
      groupSelect += `<option value="${i}" ${selectedAttr}>Group ${i}</option>`;
    }
    groupSelect += `</select>`;

    // Min/Max capacity inputs for this group/row
    const minCapacityValue = selected.min_capacity !== undefined ? selected.min_capacity : '';
    const maxCapacityValue = selected.max_capacity !== undefined ? selected.max_capacity : '';
    const minCapacityInput = `<input type="number" class="form-control min-capacity-input" name="schedule_details[${index}][min_capacity]" value="${minCapacityValue}" min="0" placeholder="Min">`;
    const maxCapacityInput = `<input type="number" class="form-control max-capacity-input" name="schedule_details[${index}][max_capacity]" value="${maxCapacityValue}" min="0" placeholder="Max">`;

    return `
      <tr>
        <td class='align-middle text-center row-number'></td>
        <td>${scheduleSelect}</td>
        <td>${daySelect}</td>
        <td>${slotSelect}</td>
        <td>${groupSelect}</td>
        <td>${minCapacityInput}</td>
        <td>${maxCapacityInput}</td>
        <td class='align-middle text-center'>
          <button type='button' class='btn btn-sm btn-danger remove-schedule-detail-row'>
            <i class='bx bx-trash'></i>
          </button>
        </td>
      </tr>
    `;
  },

  updateRowNumbers() {
    $('#scheduleDetailsTable tbody tr').each(function(index) {
      $(this).find('.row-number').text(index + 1);
      $(this).find('select, input').each(function() {
        const name = $(this).attr('name');
        if (name) {
          // Update all schedule_details[index] names
          const newName = name.replace(/schedule_details\[\d+\]/, `schedule_details[${index}]`);
          $(this).attr('name', newName);
          if ($(this).is('select')) {
            $(this).attr('data-row', index);
          }
        }
      });
    });
  },

  addRow(selected = {}) {
    const currentRows = $('#scheduleDetailsTable tbody tr').length;
    const newRow = this.renderRow(currentRows, selected);
    
    $('#scheduleDetailsTable tbody').append(newRow);
    this.updateRowNumbers();
    
    // Initialize Select2 for the new row
    const $newRow = $('#scheduleDetailsTable tbody tr:last');
    DropdownManager.initScheduleDetailSelect2($newRow);
  },

  removeRow($button) {
    $button.closest('tr').remove();
    this.updateRowNumbers();
  },

  clearRows() {
    $('#scheduleDetailsTable tbody').empty();
  },

  async loadScheduleDaysSlots(scheduleId, $daySelect, $slotSelect) {
    try {
      $daySelect.empty().prop('disabled', true);
      $slotSelect.empty().prop('disabled', true);

      if (!scheduleId) return;

      const response = await ApiService.fetchScheduleDaysSlots(scheduleId);
      
      if (response && Array.isArray(response.data) && response.data.length > 0) {
        $daySelect.empty();
        response.data.forEach((dayObj, index) => {
          const dayName = dayObj.day_of_week.charAt(0).toUpperCase() + dayObj.day_of_week.slice(1);
          $daySelect.append(`<option value="${index}">${dayName}</option>`);
        });
        
        $daySelect.prop('disabled', false);
        this.scheduleDaysCache[scheduleId] = response.data;

        Utils.initSelect2($daySelect, { placeholder: 'Select Day' });
      }
    } catch (error) {
      console.error('Failed to load schedule days and slots:', error);
    }
  },

  loadScheduleSlots(scheduleId, dayIndex, $slotSelect) {
    $slotSelect.empty().prop('disabled', true);

    if (!scheduleId || dayIndex === "") return;

    const daysData = this.scheduleDaysCache[scheduleId];
    if (daysData && daysData[dayIndex]) {
      const slots = daysData[dayIndex].slots || [];
      slots.forEach(slot => {
        const label = slot.label || `${slot.start_time} - ${slot.end_time}`;
        $slotSelect.append(`<option value="${slot.id}">Slot ${slot.slot_order}: ${label}</option>`);
      });
      $slotSelect.prop('disabled', false);
    }

    Utils.initSelect2($slotSelect, { placeholder: 'Select Slot' });
  }
};

// ===========================
// ELIGIBILITY MODE MANAGER
// ===========================
const EligibilityModeManager = {
  initialize() {
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Set up mode change handler
    $('input[name="eligibility_mode"]').on('change', (e) => {
      this.showSection(e.target.value);
    });

    // Initialize Select2 for mode-specific dropdowns
    Utils.initSelect2($('#allProgramsLevelSelect'), { placeholder: 'Select Level' });
    Utils.initSelect2($('#allLevelsProgramSelect'), { placeholder: 'Select Program' });

    // Show initial section
    const initialMode = $('input[name="eligibility_mode"]:checked').val() || ELIGIBILITY_MODES.INDIVIDUAL;
    this.showSection(initialMode);
  },

  showSection(mode) {
    // Hide all sections
    $('#eligibility-individual-section, #eligibility-all-programs-section, #eligibility-all-levels-section, #eligibility-universal-section, #eligibility-summary-section')
      .addClass('d-none').removeClass('show');
    
    // Clear validation states
    $('#allProgramsLevelSelect').removeClass('is-invalid').val('').trigger('change');
    $('#allProgramsLevelFeedback').text('');
    $('#allLevelsProgramSelect').removeClass('is-invalid').val('').trigger('change');
    $('#allLevelsProgramFeedback').text('');
    
    // Show appropriate section
    switch(mode) {
      case ELIGIBILITY_MODES.INDIVIDUAL:
        $('#eligibility-individual-section').removeClass('d-none').addClass('show');
        break;
      case ELIGIBILITY_MODES.ALL_PROGRAMS:
        $('#eligibility-all-programs-section').removeClass('d-none').addClass('show');
        break;
      case ELIGIBILITY_MODES.ALL_LEVELS:
        $('#eligibility-all-levels-section').removeClass('d-none').addClass('show');
        break;
      case ELIGIBILITY_MODES.UNIVERSAL:
        $('#eligibility-universal-section').removeClass('d-none').addClass('show');
        break;
    }
  },

  validateMode(mode, data) {
    let isValid = true;

    switch (mode) {
      case ELIGIBILITY_MODES.ALL_PROGRAMS: {
        const levelId = $('#allProgramsLevelSelect').val();
        if (!levelId) {
          Utils.validateField($('#allProgramsLevelSelect'), 'Please select a level.', false);
          isValid = false;
        } else {
          Utils.validateField($('#allProgramsLevelSelect'), '', true);
          data.is_all_programs = true;
          data.is_all_levels = false;
          data.is_universal = false;
          data.eligibility = [{ level_id: levelId }];
        }
        break;
      }

      case ELIGIBILITY_MODES.ALL_LEVELS: {
        const programId = $('#allLevelsProgramSelect').val();
        if (!programId) {
          Utils.validateField($('#allLevelsProgramSelect'), 'Please select a program.', false);
          isValid = false;
        } else {
          Utils.validateField($('#allLevelsProgramSelect'), '', true);
          data.is_all_programs = false;
          data.is_all_levels = true;
          data.is_universal = false;
          data.eligibility = [{ program_id: programId }];
        }
        break;
      }

      case ELIGIBILITY_MODES.UNIVERSAL: {
        data.is_universal = true;
        data.is_all_programs = true;
        data.is_all_levels = true;
        data.eligibility = [];
        break;
      }

      case ELIGIBILITY_MODES.INDIVIDUAL:
      default: {
        if (!Array.isArray(data.eligibility) || data.eligibility.length === 0) {
          Utils.showError('Please add at least one eligibility pair.');
          isValid = false;
        } else {
          data.eligibility = data.eligibility.map(item => ({
            ...item,
          }));
          data.is_all_levels = false;
          data.is_all_programs = false;
          data.is_universal = false;
        }
        break;
      }
    }

    return isValid;
  },

  showSummary(mode) {
    let summary = '';
    
    switch(mode) {
      case ELIGIBILITY_MODES.ALL_PROGRAMS:
        const levelText = $('#allProgramsLevelSelect option:selected').text();
        summary = `<b>All Programs</b> for <b>Level:</b> ${levelText}`;
        break;
      case ELIGIBILITY_MODES.ALL_LEVELS:
        const programText = $('#allLevelsProgramSelect option:selected').text();
        summary = `<b>All Levels</b> for <b>Program:</b> ${programText}`;
        break;
      case ELIGIBILITY_MODES.UNIVERSAL:
        summary = `<b>All Programs</b> and <b>All Levels</b> (Universal)`;
        break;
      default:
        summary = 'Custom eligibility pairs as configured.';
    }
    
    $('#eligibility-summary-content').html(`<div class='mb-2'><i class='bx bx-check-circle text-success me-2'></i>${summary}</div>`);
    $('#eligibility-summary-section').removeClass('d-none').show();
  }
};

// ===========================
// FORM MANAGER
// ===========================
const FormManager = {
  initialize() {
    this.bindEvents();
    this.initializeTables();
  },

  bindEvents() {
    // Eligibility table events
    $('#addEligibilityRowBtn').on('click', () => {
      EligibilityTableManager.addRow();
    });

    $('#eligibilityTable').on('click', '.remove-eligibility-row', function() {
      EligibilityTableManager.removeRow($(this));
    });

    // Schedule details table events
    $('#addScheduleDetailRowBtn').on('click', () => {
      ScheduleDetailsTableManager.addRow();
    });

    $('#scheduleDetailsTable').on('click', '.remove-schedule-detail-row', function() {
      ScheduleDetailsTableManager.removeRow($(this));
    });

    // Schedule change events
    $(document).on('change', '.schedule-select', function() {
      const $row = $(this).closest('tr');
      const scheduleId = $(this).val();
      const $daySelect = $row.find('.schedule-day-select');
      const $slotSelect = $row.find('.schedule-slot-select');

      ScheduleDetailsTableManager.loadScheduleDaysSlots(scheduleId, $daySelect, $slotSelect);
    });

    $(document).on('change', '.schedule-day-select', function() {
      const $row = $(this).closest('tr');
      const scheduleId = $row.find('.schedule-select').val();
      const dayIndex = $(this).val();
      const $slotSelect = $row.find('.schedule-slot-select');

      ScheduleDetailsTableManager.loadScheduleSlots(scheduleId, dayIndex, $slotSelect);
    });

    // Form submission
    $('#availableCourseForm').on('submit', (e) => {
      this.handleFormSubmit(e);
    });
  },

  initializeTables() {
    EligibilityTableManager.addRow();
    ScheduleDetailsTableManager.addRow();
  },

  getFormData() {
    const data = {
      _token: $('#availableCourseForm input[name="_token"]').val(),
      course_id: $('#course_id').val(),
      term_id: $('#term_id').val(),
      eligibility_mode: $('input[name="eligibility_mode"]:checked').val(),
      eligibility: [],
      schedule_details: [],
      is_universal: false
    };

    // Collect eligibility data (only for individual mode)
    if (data.eligibility_mode === ELIGIBILITY_MODES.INDIVIDUAL) {
      $('#eligibilityTable tbody tr').each(function() {
        const program_id = $(this).find('.program-select').val();
        const level_id = $(this).find('.level-select').val();
        if (program_id && level_id) {
          data.eligibility.push({ program_id, level_id });
        }
      });
    }

    // Collect schedule details data (now with min/max capacity per row)
    $('#scheduleDetailsTable tbody tr').each(function() {
      const schedule_id = $(this).find('.schedule-select').val();
      const schedule_day_id = $(this).find('.schedule-day-select').val();
      const schedule_slot_id = $(this).find('.schedule-slot-select').val();
      const group_number = $(this).find('.group-select').val();
      const min_capacity = $(this).find('.min-capacity-input').val();
      const max_capacity = $(this).find('.max-capacity-input').val();
      
      if (schedule_id && schedule_day_id !== "" && schedule_slot_id && group_number) {
        data.schedule_details.push({
          schedule_id,
          schedule_day_id,
          schedule_slot_id,
          group_number,
          min_capacity,
          max_capacity
        });
      }
    });

    return data;
  },

  validateFormData(data) {
    let isValid = true;

    // Validate basic fields
    if (!data.course_id) {
      Utils.showError('Please select a course.');
      return false;
    }

    if (!data.term_id) {
      Utils.showError('Please select a term.');
      return false;
    }

    // Validate eligibility mode
    if (!EligibilityModeManager.validateMode(data.eligibility_mode, data)) {
      isValid = false;
    }

    // Validate schedule details
    if (!data.schedule_details || data.schedule_details.length === 0) {
      Utils.showError('Please add at least one schedule detail (schedule, day, slot, group).');
      return false;
    }

    // Validate each schedule detail row
    for (let i = 0; i < data.schedule_details.length; i++) {
      const detail = data.schedule_details[i];
      const rowNum = i + 1;

      if (!detail.schedule_id) {
        Utils.showError(`Please select a schedule in row ${rowNum}.`);
        return false;
      }
      if (detail.schedule_day_id === "" || detail.schedule_day_id === undefined) {
        Utils.showError(`Please select a day in row ${rowNum}.`);
        return false;
      }
      if (!detail.schedule_slot_id) {
        Utils.showError(`Please select a slot in row ${rowNum}.`);
        return false;
      }
      if (!detail.group_number) {
        Utils.showError(`Please select a group in row ${rowNum}.`);
        return false;
      }
      // Validate min/max capacity per group
      if (
        detail.min_capacity === undefined ||
        detail.min_capacity === "" ||
        isNaN(detail.min_capacity) ||
        parseInt(detail.min_capacity) < 0
      ) {
        Utils.showError(`Please enter a valid min capacity in row ${rowNum}.`);
        return false;
      }
      if (
        detail.max_capacity === undefined ||
        detail.max_capacity === "" ||
        isNaN(detail.max_capacity) ||
        parseInt(detail.max_capacity) < 0
      ) {
        Utils.showError(`Please enter a valid max capacity in row ${rowNum}.`);
        return false;
      }
      if (parseInt(detail.max_capacity) < parseInt(detail.min_capacity)) {
        Utils.showError(`Max capacity must be greater than or equal to min capacity in row ${rowNum}.`);
        return false;
      }
    }

    return isValid;
  },

  async handleFormSubmit(e) {
    e.preventDefault();
    
    const $submitBtn = $('button[type="submit"]');
    const loadingConfig = {
      loadingText: 'Adding...',
      loadingIcon: 'bx bx-loader-alt bx-spin me-1',
      normalText: 'Add Available Course',
      normalIcon: 'bx bx-plus me-1'
    };
    
    Utils.setLoadingState($submitBtn, true, loadingConfig);
    
    // Hide form errors using Utils method to work with SweetAlert
    $('#formErrors').addClass('d-none').empty();
    
    try {
      const data = this.getFormData();
      
      if (!this.validateFormData(data)) {
        Utils.setLoadingState($submitBtn, false, loadingConfig);
        return;
      }
      
      // Show eligibility summary
      EligibilityModeManager.showSummary(data.eligibility_mode);
      
      // Submit form
      const response = await ApiService.storeAvailableCourse(data);
      
      // Show success message using Utils
      Utils.showSuccess(response.message || 'Available course created successfully.');
      
      // Optionally redirect after delay
      // Utils.redirectAfterDelay(ROUTES.availableCourses.index, 2000);
      
    } catch (error) {
      this.handleSubmissionError(error);
    } finally {
      Utils.setLoadingState($submitBtn, false, loadingConfig);
    }
  },

  handleSubmissionError(xhr) {
    // Use Utils.handleAjaxError for consistent error handling
    Utils.handleAjaxError(xhr, 'An error occurred. Please try again.');
  }
};

// ===========================
// MAIN APPLICATION
// ===========================
const AvailableCourseApp = {
  async initialize() {
    try {
      // Initialize dropdowns and load data
      const dataLoaded = await DropdownManager.initialize();
      if (!dataLoaded) {
        return;
      }

      // Initialize eligibility mode handling
      EligibilityModeManager.initialize();

      // Initialize form management
      FormManager.initialize();

      Utils.hidePageLoader();

      console.log('Available Course App initialized successfully');
    } catch (error) {
      console.error('Failed to initialize Available Course App:', error);
      Utils.showError('Application initialization failed. Please refresh the page.');
    }
  }
};

// ===========================
// DOCUMENT READY
// ===========================
$(document).ready(() => {
  AvailableCourseApp.initialize();
});
</script>
@endpush