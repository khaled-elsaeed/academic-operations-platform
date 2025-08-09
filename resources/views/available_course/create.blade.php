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
                    <input class="form-check-input" type="radio" name="mode" id="mode_individual" value="individual" checked>
                    <label class="form-check-label" for="mode_individual">
                      Individual <i class="bx bx-question-mark small text-muted" data-bs-toggle="tooltip" title="Add specific program & level pairs."></i>
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="mode" id="mode_all_programs" value="all_programs">
                    <label class="form-check-label" for="mode_all_programs">
                      All Programs <i class="bx bx-question-mark small text-muted" data-bs-toggle="tooltip" title="Make available to all programs for a specific level."></i>
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="mode" id="mode_all_levels" value="all_levels">
                    <label class="form-check-label" for="mode_all_levels">
                      All Levels <i class="bx bx-question-mark small text-muted" data-bs-toggle="tooltip" title="Make available to all levels for a specific program."></i>
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="mode" id="mode_universal" value="universal">
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

        <!-- Step 3: Schedule Details - New Card-Based Layout -->
        <div class="row">
          <div class="col-12 mb-3">
            <div class="card shadow-sm mb-4">
              <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bx bx-time me-2"></i>Step 3: Add Schedule Details</span>
                <button type="button" class="btn btn-sm btn-success" id="addScheduleDetailRowBtn"><i class="bx bx-plus"></i> Add Schedule</button>
              </div>
              <div class="card-body pt-2 pb-3">
                <div id="schedule-details-container">
                  <!-- Schedule detail cards will be added here -->
                </div>
                <div class="form-text mt-2">Add one or more schedule details. Each schedule detail represents a specific time slot for the course.</div>
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

<!-- Schedule Detail Card Template -->
<template id="schedule-detail-template">
  <div class="schedule-detail-card border rounded mb-3 p-3 bg-light position-relative">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <h6 class="mb-0 text-primary">
        <i class="bx bx-calendar me-1"></i>
        Schedule Detail <span class="schedule-number"></span>
      </h6>
      <button type="button" class="btn btn-sm btn-outline-danger remove-schedule-detail-btn">
        <i class="bx bx-trash"></i>
      </button>
    </div>
    
    <div class="row g-3">
      <!-- Schedule Selection -->
      <div class="col-md-4">
        <label class="form-label fw-semibold">Schedule</label>
        <select class="form-select schedule-select">
          <option value="">Select Schedule</option>
        </select>
      </div>
      
      <!-- Activity Type -->
      <div class="col-md-4">
        <label class="form-label fw-semibold">Activity Type</label>
        <select class="form-select activity-type-select">
          <option value="">Select Type</option>
          <option value="lecture">Lecture</option>
          <option value="tutorial">Tutorial</option>
          <option value="lab">Lab</option>
        </select>
      </div>
      
      <!-- Group Number -->
      <div class="col-md-4">
        <label class="form-label fw-semibold">Group Number</label>
        <select class="form-select group-select">
          <option value="">Select Group</option>
          <option value="1">Group 1</option>
          <option value="2">Group 2</option>
          <option value="3">Group 3</option>
          <option value="4">Group 4</option>
          <option value="5">Group 5</option>
          <option value="6">Group 6</option>
          <option value="7">Group 7</option>
          <option value="8">Group 8</option>
        </select>
      </div>
      
      <!-- Location -->
      <div class="col-md-4">
        <label class="form-label fw-semibold">Location</label>
        <input type="text" class="form-control location-input" placeholder="Enter Location">
      </div>
      
      <!-- Day Selection -->
      <div class="col-md-4">
        <label class="form-label fw-semibold">Day</label>
        <select class="form-select schedule-day-select" disabled>
          <option value="">Select Day</option>
        </select>
      </div>
      
      <!-- Slot Selection -->
      <div class="col-md-4">
        <label class="form-label fw-semibold">Slots</label>
        <select class="form-select schedule-slot-select" multiple disabled style="min-height: 120px;">
          <!-- Options will be populated dynamically -->
        </select>
        <div class="form-text small">Hold Ctrl/Cmd to select multiple consecutive slots</div>
        <div class="selected-slots-summary mt-2 d-none">
          <small class="text-muted">Selected: <span class="slot-summary-text"></span></small>
        </div>
      </div>
      
      <!-- Capacity Section -->
      <div class="col-md-4">
        <label class="form-label fw-semibold">Capacity</label>
        <div class="input-group">
          <span class="input-group-text">Min</span>
          <input type="number" class="form-control min-capacity-input" placeholder="Min" min="1">
          <span class="input-group-text">Max</span>
          <input type="number" class="form-control max-capacity-input" placeholder="Max" min="1">
        </div>
      </div>
    </div>
  </div>
</template>

@endsection

@push('styles')
<style>
.schedule-detail-card {
  transition: all 0.3s ease;
  border: 1px solid #e3e6f0 !important;
}

.schedule-detail-card:hover {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  border-color: #5a6acf !important;
}

.fade-section {
  transition: opacity 0.3s ease;
}

.fade-section.show {
  opacity: 1;
}

.schedule-number {
  background: #5a6acf;
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.remove-schedule-detail-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  border: none;
  padding: 4px 8px;
}

.input-group-text {
  font-size: 0.875rem;
  font-weight: 500;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .schedule-detail-card .row > div {
    margin-bottom: 1rem;
  }
  
  .d-flex.gap-3 {
    flex-direction: column;
    gap: 1rem !important;
  }
}

/* Multiple select styling */
.schedule-slot-select[multiple] {
  min-height: 120px !important;
  max-height: 200px;
  overflow-y: auto;
}

.schedule-slot-select[multiple] option {
  padding: 8px 12px;
  margin: 2px 0;
  border-radius: 4px;
}

.schedule-slot-select[multiple] option:checked {
  background: #5a6acf !important;
  color: white !important;
}

.slot-feedback {
  font-size: 0.875rem;
  margin-top: 0.25rem;
  color: #dc3545;
}

.selected-slots-summary {
  background: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 4px;
  padding: 6px 10px;
}

.selected-slots-summary .slot-summary-text {
  font-weight: 500;
  color: #5a6acf;
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/utils.js') }}?v={{ config('app.version') }}"></script>

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

const ModeS = {
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
    
    $container.find('.activity-type-select').each(function() {
      Utils.initSelect2($(this), { placeholder: 'Select Activity Type' });
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
// SCHEDULE DETAILS CARD MANAGER
// ===========================
const ScheduleDetailsCardManager = {
  scheduleDaysCache: {},
  cardCounter: 0,

  addCard(selected = {}) {
    const cardIndex = this.cardCounter++;
    const template = document.getElementById('schedule-detail-template');
    const cardHtml = template.innerHTML;
    
    const $container = $('#schedule-details-container');
    $container.append(cardHtml);
    
    const $newCard = $container.children().last();
    
    // Update card number and form names
    this.updateCardNames($newCard, cardIndex);
    this.populateCardSelects($newCard, selected);
    this.updateCardNumbers();
    
    // Initialize Select2 for the new card
    DropdownManager.initScheduleDetailSelect2($newCard);
    
    return $newCard;
  },

  updateCardNames($card, index) {
    $card.find('.schedule-select').attr('name', `schedule_details[${index}][schedule_id]`).attr('data-index', index);
    $card.find('.activity-type-select').attr('name', `schedule_details[${index}][activity_type]`).attr('data-index', index);
    $card.find('.schedule-day-select').attr('name', `schedule_details[${index}][schedule_day_id]`).attr('data-index', index);
    $card.find('.schedule-slot-select').attr('name', `schedule_details[${index}][schedule_slot_ids][]`).attr('data-index', index);
    $card.find('.group-select').attr('name', `schedule_details[${index}][group_number]`).attr('data-index', index);
    $card.find('.location-input').attr('name', `schedule_details[${index}][location]`).attr('data-index', index);
    $card.find('.min-capacity-input').attr('name', `schedule_details[${index}][min_capacity]`).attr('data-index', index);
    $card.find('.max-capacity-input').attr('name', `schedule_details[${index}][max_capacity]`).attr('data-index', index);
  },

  populateCardSelects($card, selected = {}) {
    // Populate schedule select
    const $scheduleSelect = $card.find('.schedule-select');
    $scheduleSelect.empty().append('<option value="">Select Schedule</option>');
    DropdownManager.scheduleOptions.forEach(opt => {
      const selectedAttr = opt.id == selected.schedule_id ? 'selected' : '';
      $scheduleSelect.append(`<option value="${opt.id}" ${selectedAttr}>${opt.title}</option>`);
    });

    // Set other selected values
    if (selected.activity_type) {
      $card.find('.activity-type-select').val(selected.activity_type);
    }
    if (selected.group_number) {
      $card.find('.group-select').val(selected.group_number);
    }
    if (selected.location) {
      $card.find('.location-input').val(selected.location);
    }
    if (selected.min_capacity) {
      $card.find('.min-capacity-input').val(selected.min_capacity);
    }
    if (selected.max_capacity) {
      $card.find('.max-capacity-input').val(selected.max_capacity);
    }
    if (selected.schedule_slot_ids && Array.isArray(selected.schedule_slot_ids)) {
      $card.find('.schedule-slot-select').val(selected.schedule_slot_ids);
    }
  },

  updateCardNumbers() {
    $('#schedule-details-container .schedule-detail-card').each(function(index) {
      $(this).find('.schedule-number').text(index + 1);
      
      // Update all form names to use current index
      $(this).find('select, input').each(function() {
        const name = $(this).attr('name');
        if (name) {
          const newName = name.replace(/schedule_details\[\d+\]/, `schedule_details[${index}]`);
          $(this).attr('name', newName);
          $(this).attr('data-index', index);
        }
      });
    });
  },

  removeCard($button) {
    $button.closest('.schedule-detail-card').remove();
    this.updateCardNumbers();
  },

  clearCards() {
    $('#schedule-details-container').empty();
    this.cardCounter = 0;
  },

  async loadScheduleDaysSlots(scheduleId, $daySelect, $slotSelect) {
    try {
      $daySelect.empty().prop('disabled', true);
      $slotSelect.empty().prop('disabled', true);

      if (!scheduleId) return;

      const response = await ApiService.fetchScheduleDaysSlots(scheduleId);
      
      if (response && Array.isArray(response.data) && response.data.length > 0) {
        $daySelect.empty().append('<option value="">Select Day</option>');
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
        $slotSelect.append(`<option value="${slot.id}" data-order="${slot.slot_order}">Slot ${slot.slot_order}: ${label}</option>`);
      });
      $slotSelect.prop('disabled', false);
      
      // Add event listener for consecutive slot validation
      $slotSelect.off('change.consecutiveValidation').on('change.consecutiveValidation', function() {
        ScheduleDetailsCardManager.validateConsecutiveSlots($(this));
      });
    }

    Utils.initSelect2($slotSelect, { 
      placeholder: 'Select Slots',
      closeOnSelect: false
    });
  },

  validateConsecutiveSlots($slotSelect) {
    const selectedValues = $slotSelect.val() || [];
    const $card = $slotSelect.closest('.schedule-detail-card');
    const $feedback = $card.find('.slot-feedback');
    const $summary = $card.find('.selected-slots-summary');
    const $summaryText = $card.find('.slot-summary-text');
    
    if (selectedValues.length === 0) {
      $feedback.removeClass('d-block').addClass('d-none');
      $summary.addClass('d-none');
      $slotSelect.removeClass('is-invalid');
      return true;
    }

    // Get slot orders and labels for validation and display
    const selectedSlots = selectedValues.map(val => {
      const option = $slotSelect.find(`option[value="${val}"]`);
      return {
        id: val,
        order: parseInt(option.attr('data-order')),
        label: option.text()
      };
    }).sort((a, b) => a.order - b.order);

    // Update summary
    if (selectedSlots.length > 0) {
      const summaryText = selectedSlots.length === 1 
        ? selectedSlots[0].label
        : `${selectedSlots[0].label} - ${selectedSlots[selectedSlots.length - 1].label} (${selectedSlots.length} slots)`;
      $summaryText.text(summaryText);
      $summary.removeClass('d-none');
    }

    if (selectedSlots.length <= 1) {
      $feedback.removeClass('d-block').addClass('d-none');
      $slotSelect.removeClass('is-invalid');
      return true;
    }

    // Check if slots are consecutive
    let isConsecutive = true;
    for (let i = 1; i < selectedSlots.length; i++) {
      if (selectedSlots[i].order !== selectedSlots[i-1].order + 1) {
        isConsecutive = false;
        break;
      }
    }

    if (!isConsecutive) {
      if ($feedback.length === 0) {
        $slotSelect.after('<div class="invalid-feedback slot-feedback d-block">Selected slots must be consecutive.</div>');
      } else {
        $feedback.text('Selected slots must be consecutive.').addClass('d-block');
      }
      $slotSelect.addClass('is-invalid');
      return false;
    } else {
      $feedback.removeClass('d-block').addClass('d-none');
      $slotSelect.removeClass('is-invalid');
      return true;
    }
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
    $('input[name="mode"]').on('change', (e) => {
      this.showSection(e.target.value);
    });

    // Initialize Select2 for mode-specific dropdowns
    Utils.initSelect2($('#allProgramsLevelSelect'), { placeholder: 'Select Level' });
    Utils.initSelect2($('#allLevelsProgramSelect'), { placeholder: 'Select Program' });

    // Show initial section
    const initialMode = $('input[name="mode"]:checked').val() || ModeS.INDIVIDUAL;
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
      case ModeS.INDIVIDUAL:
        $('#eligibility-individual-section').removeClass('d-none').addClass('show');
        break;
      case ModeS.ALL_PROGRAMS:
        $('#eligibility-all-programs-section').removeClass('d-none').addClass('show');
        break;
      case ModeS.ALL_LEVELS:
        $('#eligibility-all-levels-section').removeClass('d-none').addClass('show');
        break;
      case ModeS.UNIVERSAL:
        $('#eligibility-universal-section').removeClass('d-none').addClass('show');
        break;
    }
  },

  validateMode(mode, data) {
    let isValid = true;

    switch (mode) {
      case ModeS.ALL_PROGRAMS: {
        const levelId = $('#allProgramsLevelSelect').val();
        if (!levelId) {
          Utils.validateField($('#allProgramsLevelSelect'), 'Please select a level.', false);
          isValid = false;
        } else {
          Utils.validateField($('#allProgramsLevelSelect'), '', true);
          data.mode = 'all_programs';
          data.level_id = levelId;
          data.eligibility = [];
        }
        break;
      }

      case ModeS.ALL_LEVELS: {
        const programId = $('#allLevelsProgramSelect').val();
        if (!programId) {
          Utils.validateField($('#allLevelsProgramSelect'), 'Please select a program.', false);
          isValid = false;
        } else {
          Utils.validateField($('#allLevelsProgramSelect'), '', true);
          data.mode = 'all_levels';
          data.eligibility = [];
          data.program_id = programId;
        }
        break;
      }

      case ModeS.UNIVERSAL: {
        data.mode = 'universal';
        data.eligibility = [];
        break;
      }

      case ModeS.INDIVIDUAL:
      default: {
        if (!Array.isArray(data.eligibility) || data.eligibility.length === 0) {
          Utils.showError('Please add at least one eligibility pair.');
          isValid = false;
        } else {
          data.eligibility = data.eligibility.map(item => ({
            ...item,
          }));
          data.mode = 'individual';
        }
        break;
      }
    }

    return isValid;
  },

  showSummary(mode) {
    let summary = '';
    
    switch(mode) {
      case ModeS.ALL_PROGRAMS:
        const levelText = $('#allProgramsLevelSelect option:selected').text();
        summary = `<b>All Programs</b> for <b>Level:</b> ${levelText}`;
        break;
      case ModeS.ALL_LEVELS:
        const programText = $('#allLevelsProgramSelect option:selected').text();
        summary = `<b>All Levels</b> for <b>Program:</b> ${programText}`;
        break;
      case ModeS.UNIVERSAL:
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

    // Schedule details card events
    $('#addScheduleDetailRowBtn').on('click', () => {
      ScheduleDetailsCardManager.addCard();
    });

    $(document).on('click', '.remove-schedule-detail-btn', function() {
      ScheduleDetailsCardManager.removeCard($(this));
    });

    // Schedule change events
    $(document).on('change', '.schedule-select', function() {
      const $card = $(this).closest('.schedule-detail-card');
      const scheduleId = $(this).val();
      const $daySelect = $card.find('.schedule-day-select');
      const $slotSelect = $card.find('.schedule-slot-select');

      ScheduleDetailsCardManager.loadScheduleDaysSlots(scheduleId, $daySelect, $slotSelect);
    });

    $(document).on('change', '.schedule-day-select', function() {
      const $card = $(this).closest('.schedule-detail-card');
      const scheduleId = $card.find('.schedule-select').val();
      const dayIndex = $(this).val();
      const $slotSelect = $card.find('.schedule-slot-select');

      ScheduleDetailsCardManager.loadScheduleSlots(scheduleId, dayIndex, $slotSelect);
    });

    // Form submission
    $('#availableCourseForm').on('submit', (e) => {
      this.handleFormSubmit(e);
    });
  },

  initializeTables() {
    EligibilityTableManager.addRow();
    ScheduleDetailsCardManager.addCard();
  },

  getFormData() {
    const data = {
      _token: $('#availableCourseForm input[name="_token"]').val(),
      course_id: $('#course_id').val(),
      term_id: $('#term_id').val(),
      mode: $('input[name="mode"]:checked').val(),
      eligibility: [],
      schedule_details: [],
    };

    // Collect eligibility data (only for individual mode)
    if (data.mode === ModeS.INDIVIDUAL) {
      $('#eligibilityTable tbody tr').each(function() {
        const program_id = $(this).find('.program-select').val();
        const level_id = $(this).find('.level-select').val();
        if (program_id && level_id) {
          data.eligibility.push({ program_id, level_id });
        }
      });
    }

    // Collect schedule details data from cards
    $('#schedule-details-container .schedule-detail-card').each(function() {
      const schedule_id = $(this).find('.schedule-select').val();
      const activity_type = $(this).find('.activity-type-select').val();
      const schedule_day_id = $(this).find('.schedule-day-select').val();
      const schedule_slot_ids = $(this).find('.schedule-slot-select').val() || [];
      const group_number = $(this).find('.group-select').val();
      const min_capacity = $(this).find('.min-capacity-input').val();
      const max_capacity = $(this).find('.max-capacity-input').val();
      const location = $(this).find('.location-input').val();
      
      if (schedule_id && schedule_day_id !== "" && schedule_slot_ids.length > 0 && group_number) {
        data.schedule_details.push({
          schedule_id,
          activity_type,
          schedule_day_id,
          schedule_slot_ids,
          group_number,
          min_capacity,
          max_capacity,
          location
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
    if (!EligibilityModeManager.validateMode(data.mode, data)) {
      isValid = false;
    }

    // Validate schedule details
    if (!data.schedule_details || data.schedule_details.length === 0) {
      Utils.showError('Please add at least one schedule detail.');
      return false;
    }

    // Validate each schedule detail
    for (let i = 0; i < data.schedule_details.length; i++) {
      const detail = data.schedule_details[i];
      const scheduleNum = i + 1;

      if (!detail.schedule_id) {
        Utils.showError(`Please select a schedule in Schedule Detail ${scheduleNum}.`);
        return false;
      }
      if (!detail.activity_type) {
        Utils.showError(`Please select an activity type in Schedule Detail ${scheduleNum}.`);
        return false;
      }
      if (detail.schedule_day_id === "" || detail.schedule_day_id === undefined) {
        Utils.showError(`Please select a day in Schedule Detail ${scheduleNum}.`);
        return false;
      }
      if (!detail.schedule_slot_ids || !Array.isArray(detail.schedule_slot_ids) || detail.schedule_slot_ids.length === 0) {
        Utils.showError(`Please select at least one slot in Schedule Detail ${scheduleNum}.`);
        return false;
      }
      if (!detail.group_number) {
        Utils.showError(`Please select a group in Schedule Detail ${scheduleNum}.`);
        return false;
      }

      if (!detail.location) {
        Utils.showError(`Please enter a location in Schedule Detail ${scheduleNum}.`);
        return false;
      }

      // Validate that slots are consecutive
      const $card = $(`#schedule-details-container .schedule-detail-card:eq(${i})`);
      const $slotSelect = $card.find('.schedule-slot-select');
      if (!ScheduleDetailsCardManager.validateConsecutiveSlots($slotSelect)) {
        Utils.showError(`Selected slots must be consecutive in Schedule Detail ${scheduleNum}.`);
        return false;
      }

      // Validate capacity
      const minCap = parseInt(detail.min_capacity);
      const maxCap = parseInt(detail.max_capacity);
      
      if (!detail.min_capacity || isNaN(minCap) || minCap < 1) {
        Utils.showError(`Please enter a valid minimum capacity (at least 1) in Schedule Detail ${scheduleNum}.`);
        return false;
      }
      if (!detail.max_capacity || isNaN(maxCap) || maxCap < 1) {
        Utils.showError(`Please enter a valid maximum capacity (at least 1) in Schedule Detail ${scheduleNum}.`);
        return false;
      }
      if (maxCap < minCap) {
        Utils.showError(`Maximum capacity must be greater than or equal to minimum capacity in Schedule Detail ${scheduleNum}.`);
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
    
    // Hide form errors
    $('#formErrors').addClass('d-none').empty();
    
    try {
      const data = this.getFormData();
      
      if (!this.validateFormData(data)) {
        Utils.setLoadingState($submitBtn, false, loadingConfig);
        return;
      }
      
      // Show eligibility summary
      EligibilityModeManager.showSummary(data.mode);
      
      // Submit form
      const response = await ApiService.storeAvailableCourse(data);
      
      // Show success message and redirect
      Utils.showSuccess(response.message || 'Available course created successfully.');
      
      // Redirect to index page after a short delay
      setTimeout(() => {
        window.location.href = ROUTES.availableCourses.index;
      }, 1500);
      
    } catch (error) {
      this.handleSubmissionError(error);
    } finally {
      Utils.setLoadingState($submitBtn, false, loadingConfig);
    }
  },

  handleSubmissionError(xhr) {
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