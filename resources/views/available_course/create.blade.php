@extends('layouts.home')

@section('title', __('Add Available Course | AcadOps'))

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header -->
    <x-ui.page-header 
        :title="__('Add Available Course')"
        :description="__('Create a new available course for enrollment')"
        icon="bx bx-book-add"
    >
        <a href="{{ route('available_courses.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back"></i> Back to List
        </a>
    </x-ui.page-header>

    <!-- Add Available Course Form -->
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <i class="bx bx-book-add me-2"></i>
            <h5 class="mb-0">Available Course Details</h5>
        </div>
        <div class="card-body">
            <form id="availableCourseForm" novalidate>
                @csrf
                
                <!-- Basic Course Information -->
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="course_id" class="form-label fw-semibold">
                            Course <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="term_id" class="form-label fw-semibold">
                            Term <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" id="term_id" name="term_id" required>
                            <option value="">Select Term</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>

                <!-- Step 1: Eligibility Mode Selection -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom-0 pb-2">
                        <h6 class="mb-0 fw-bold">
                            <i class="bx bx-shield-quarter me-2"></i>
                            Step 1: Select Eligibility Mode
                            <i class="bx bx-info-circle text-primary ms-1" data-bs-toggle="tooltip" 
                               title="Choose how to assign this course: specific pairs, all programs, all levels, or universally"></i>
                        </h6>
                    </div>
                    <div class="card-body pt-2 pb-3">
                        <div class="d-flex flex-wrap gap-3 align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="mode_individual" value="individual" checked>
                                <label class="form-check-label" for="mode_individual">
                                    Individual
                                    <i class="bx bx-question-mark small text-muted" data-bs-toggle="tooltip" 
                                       title="Add specific program & level pairs"></i>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="mode_all_programs" value="all_programs">
                                <label class="form-check-label" for="mode_all_programs">
                                    All Programs
                                    <i class="bx bx-question-mark small text-muted" data-bs-toggle="tooltip" 
                                       title="Make available to all programs for a specific level"></i>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="mode_all_levels" value="all_levels">
                                <label class="form-check-label" for="mode_all_levels">
                                    All Levels
                                    <i class="bx bx-question-mark small text-muted" data-bs-toggle="tooltip" 
                                       title="Make available to all levels for a specific program"></i>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="mode_universal" value="universal">
                                <label class="form-check-label" for="mode_universal">
                                    Universal
                                    <i class="bx bx-question-mark small text-muted" data-bs-toggle="tooltip" 
                                       title="Make available to all programs and all levels"></i>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Eligibility Sections -->
                <div id="eligibility-individual-section" class="eligibility-section">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <span class="fw-bold">
                                <i class="bx bx-list-ul me-2"></i>
                                Step 2: Add Eligibility Pairs
                            </span>
                            <button type="button" class="btn btn-sm btn-success" id="addEligibilityRowBtn">
                                <i class="bx bx-plus"></i> Add Row
                            </button>
                        </div>
                        <div class="card-body p-2">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover mb-0 align-middle" id="eligibilityTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:40px;">#</th>
                                            <th>Program</th>
                                            <th>Level</th>
                                            <th style="width:120px;">Groups</th>
                                            <th style="width:60px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Rows will be added dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-text mt-2">
                                <i class="bx bx-info-circle me-1"></i>
                                Add at least one eligibility (program/level/group pair)
                            </div>
                        </div>
                    </div>
                </div>

                <div id="eligibility-all-programs-section" class="eligibility-section d-none">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <span class="fw-bold">Step 2: Choose a Specific Level</span>
                        </div>
                        <div class="card-body">
                            <label for="allProgramsLevelSelect" class="form-label fw-semibold">
                                Level <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="allProgramsLevelSelect">
                                <option value="">Select Level</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>

                <div id="eligibility-all-levels-section" class="eligibility-section d-none">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <span class="fw-bold">Step 2: Choose a Specific Program</span>
                        </div>
                        <div class="card-body">
                            <label for="allLevelsProgramSelect" class="form-label fw-semibold">
                                Program <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="allLevelsProgramSelect">
                                <option value="">Select Program</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>

                <div id="eligibility-universal-section" class="eligibility-section d-none">
                    <div class="card shadow-sm mb-4 border-primary">
                        <div class="card-body text-center">
                            <i class="bx bx-globe bx-lg text-primary mb-2"></i>
                            <div class="alert alert-info mb-0">
                                <strong>This course will be available for all programs and all levels</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Schedule Details -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <span class="fw-bold">
                            <i class="bx bx-time me-2"></i>
                            Step 3: Add Schedule Details
                        </span>
                        <button type="button" class="btn btn-sm btn-success" id="addScheduleDetailBtn">
                            <i class="bx bx-plus"></i> Add Schedule
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="schedule-details-container">
                            <!-- Schedule cards will be added here -->
                        </div>
                        <div class="form-text mt-2">
                            <i class="bx bx-info-circle me-1"></i>
                            Add one or more schedule details. Each schedule represents a specific time slot for the course
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i>
                        Add Available Course
                    </button>
                    <a href="{{ route('available_courses.index') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-x me-1"></i>
                        Cancel
                    </a>
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
                Schedule Detail <span class="schedule-number badge bg-primary"></span>
            </h6>
            <button type="button" class="btn btn-sm btn-outline-danger remove-schedule-btn">
                <i class="bx bx-trash"></i>
            </button>
        </div>
        
        <div class="row g-3">
            <!-- Schedule Selection -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">Schedule <span class="text-danger">*</span></label>
                <select class="form-select schedule-select">
                    <option value="">Select Schedule</option>
                </select>
                <div class="invalid-feedback"></div>
            </div>
            
            <!-- Activity Type -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">Activity Type <span class="text-danger">*</span></label>
                <select class="form-select activity-type-select">
                    <option value="">Select Type</option>
                    <option value="lecture">Lecture</option>
                    <option value="tutorial">Tutorial</option>
                    <option value="lab">Lab</option>
                </select>
                <div class="invalid-feedback"></div>
            </div>
            
            <!-- Group Numbers -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">Groups <span class="text-danger">*</span></label>
                <select multiple class="form-select group-select" style="min-height:100px;">
                    <!-- Options 1-20 will be populated dynamically -->
                </select>
                <div class="form-text small">Hold Ctrl/Cmd to select multiple</div>
                <div class="invalid-feedback"></div>
            </div>
            
            <!-- Location -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                <input type="text" class="form-control location-input" placeholder="Enter Location">
                <div class="invalid-feedback"></div>
            </div>
            
            <!-- Program (Optional) -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">Program <span class="text-muted small">(optional)</span></label>
                <select class="form-select program-select">
                    <option value="">Select Program</option>
                </select>
            </div>

            <!-- Level (Optional) -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">Level <span class="text-muted small">(optional)</span></label>
                <select class="form-select level-select">
                    <option value="">Select Level</option>
                </select>
            </div>
            
            <!-- Day Selection -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">Day <span class="text-danger">*</span></label>
                <select class="form-select day-select" disabled>
                    <option value="">Select Day</option>
                </select>
                <div class="invalid-feedback"></div>
            </div>
            
            <!-- Slot Selection -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">Slots <span class="text-danger">*</span></label>
                <select class="form-select slot-select" multiple disabled style="min-height: 100px;">
                    <!-- Options populated dynamically -->
                </select>
                <div class="form-text small">Select consecutive slots</div>
                <div class="invalid-feedback"></div>
                <div class="slot-summary mt-2 d-none">
                    <small class="text-muted">Selected: <span class="slot-summary-text fw-semibold text-primary"></span></small>
                </div>
            </div>
            
            <!-- Capacity -->
            <div class="col-md-4">
                <label class="form-label fw-semibold">Capacity <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">Min</span>
                    <input type="number" class="form-control min-capacity-input" placeholder="0" min="1">
                    <span class="input-group-text">Max</span>
                    <input type="number" class="form-control max-capacity-input" placeholder="0" min="1">
                </div>
                <div class="invalid-feedback"></div>
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

.eligibility-section {
    transition: opacity 0.3s ease;
}

.schedule-number {
    font-size: 0.75rem;
    font-weight: 600;
}

.remove-schedule-btn {
    padding: 4px 8px;
}

/* Multiple select styling */
select[multiple] {
    overflow-y: auto;
}

select[multiple] option {
    padding: 6px 10px;
    margin: 2px 0;
}

select[multiple] option:checked {
    background: linear-gradient(0deg, #5a6acf 0%, #5a6acf 100%);
    color: white !important;
}

.slot-summary {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 6px 10px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .d-flex.gap-3 {
        flex-direction: column;
        gap: 1rem !important;
    }
}
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/utils.js') }}"></script>
<script>
'use strict';

// ===========================
// ROUTES CONFIGURATION
// ===========================
const ROUTES = {
    courses: { all: @json(route('courses.all')) },
    terms: { all: @json(route('terms.all.with_inactive')) },
    programs: { all: @json(route('programs.all')) },
    levels: { all: @json(route('levels.all')) },
    schedules: { 
        all: @json(route('schedules.all')),
        daysSlots: '{{ route('schedules.days-slots', ':id') }}'
    },
    availableCourses: { 
        store: @json(route('available_courses.store')),
        index: @json(route('available_courses.index'))
    }
};

const MODES = {
    INDIVIDUAL: 'individual',
    ALL_PROGRAMS: 'all_programs',
    ALL_LEVELS: 'all_levels',
    UNIVERSAL: 'universal'
};

// ===========================
// API SERVICE
// ===========================
const ApiService = {
    fetchCourses() {
        return Utils.get(ROUTES.courses.all);
    },
    fetchTerms() {
        return Utils.get(ROUTES.terms.all);
    },
    fetchPrograms() {
        return Utils.get(ROUTES.programs.all);
    },
    fetchLevels() {
        return Utils.get(ROUTES.levels.all);
    },
    fetchSchedules() {
        return Utils.get(ROUTES.schedules.all);
    },
    fetchScheduleDaysSlots(scheduleId) {
        return Utils.get(Utils.replaceRouteId(ROUTES.schedules.daysSlots, scheduleId));
    },
    storeAvailableCourse(data) {
        return Utils.post(ROUTES.availableCourses.store, data);
    }
};

// ===========================
// DATA STORE
// ===========================
const DataStore = {
    programs: [],
    levels: [],
    schedules: [],
    scheduleDaysCache: {},

    async initialize() {
        try {
            const [coursesRes, termsRes, programsRes, levelsRes, schedulesRes] = await Promise.all([
                ApiService.fetchCourses(),
                ApiService.fetchTerms(),
                ApiService.fetchPrograms(),
                ApiService.fetchLevels(),
                ApiService.fetchSchedules()
            ]);

            if (!Utils.isResponseSuccess(coursesRes) || !Utils.isResponseSuccess(termsRes) || 
                !Utils.isResponseSuccess(programsRes) || !Utils.isResponseSuccess(levelsRes) ||
                !Utils.isResponseSuccess(schedulesRes)) {
                throw new Error('Failed to load required data');
            }

            this.programs = Utils.getResponseData(programsRes);
            this.levels = Utils.getResponseData(levelsRes);
            this.schedules = Utils.getResponseData(schedulesRes);

            // Populate main dropdowns
            Utils.populateSelect('#course_id', Utils.getResponseData(coursesRes), {
                textField: 'name',
                placeholder: ''
            }, true);

            Utils.populateSelect('#term_id', Utils.getResponseData(termsRes), {
                textField: 'name',
                placeholder: ''
            }, true);

            // Populate eligibility mode dropdowns
            Utils.populateSelect('#allProgramsLevelSelect', this.levels, {
                textField: 'name',
                placeholder: ''
            }, true);

            Utils.populateSelect('#allLevelsProgramSelect', this.programs, {
                textField: 'name',
                placeholder: ''
            }, true);

            return true;
        } catch (error) {
            Utils.handleError(error);
            return false;
        }
    }
};

// ===========================
// SELECT2 MANAGER
// ===========================
const Select2Manager = {
    init() {
        Utils.initSelect2('#course_id', { placeholder: 'Select Course' });
        Utils.initSelect2('#term_id', { placeholder: 'Select Term' });
        Utils.initSelect2('#allProgramsLevelSelect', { placeholder: 'Select Level' });
        Utils.initSelect2('#allLevelsProgramSelect', { placeholder: 'Select Program' });
    },

    initEligibilityRow($row) {
        $row.find('.program-select').each(function() {
            Utils.initSelect2($(this), { placeholder: 'Select Program' });
        });
        $row.find('.level-select').each(function() {
            Utils.initSelect2($(this), { placeholder: 'Select Level' });
        });
        $row.find('.group-select').each(function() {
            Utils.initSelect2($(this), { 
                placeholder: 'Select Groups',
                closeOnSelect: false
            });
        });
    },

    initScheduleCard($card) {
        $card.find('.schedule-select').each(function() {
            Utils.initSelect2($(this), { placeholder: 'Select Schedule' });
        });
        $card.find('.activity-type-select').each(function() {
            Utils.initSelect2($(this), { placeholder: 'Select Type' });
        });
        $card.find('.day-select').each(function() {
            Utils.initSelect2($(this), { placeholder: 'Select Day' });
        });
        $card.find('.slot-select').each(function() {
            Utils.initSelect2($(this), { 
                placeholder: 'Select Slots',
                closeOnSelect: false
            });
        });
        $card.find('.group-select').each(function() {
            Utils.initSelect2($(this), { 
                placeholder: 'Select Groups',
                closeOnSelect: false
            });
        });
        $card.find('.program-select').each(function() {
            Utils.initSelect2($(this), { 
                placeholder: 'Select Program',
                allowClear: true
            });
        });
        $card.find('.level-select').each(function() {
            Utils.initSelect2($(this), { 
                placeholder: 'Select Level',
                allowClear: true
            });
        });
    }
};

// ===========================
// ELIGIBILITY TABLE MANAGER
// ===========================
const EligibilityTableManager = {
    rowCounter: 0,

    renderRow(index, selected = {}) {
        let html = `<tr data-index="${index}">`;
        html += `<td class="align-middle text-center row-number"></td>`;
        
        // Program select
        html += `<td><select class="form-select program-select" name="eligibility[${index}][program_id]" required>`;
        html += `<option value="">Select Program</option>`;
        DataStore.programs.forEach(prog => {
            const sel = prog.id == selected.program_id ? 'selected' : '';
            html += `<option value="${prog.id}" ${sel}>${Utils.escapeHtml(prog.name)}</option>`;
        });
        html += `</select><div class="invalid-feedback"></div></td>`;
        
        // Level select
        html += `<td><select class="form-select level-select" name="eligibility[${index}][level_id]" required>`;
        html += `<option value="">Select Level</option>`;
        DataStore.levels.forEach(level => {
            const sel = level.id == selected.level_id ? 'selected' : '';
            html += `<option value="${level.id}" ${sel}>${Utils.escapeHtml(level.name)}</option>`;
        });
        html += `</select><div class="invalid-feedback"></div></td>`;
        
        // Groups multi-select
        html += `<td><select multiple class="form-select group-select" name="eligibility[${index}][group_ids][]" required style="min-height:80px;">`;
        for (let g = 1; g <= 20; g++) {
            const sel = (Array.isArray(selected.group_ids) && selected.group_ids.includes(String(g))) ? 'selected' : '';
            html += `<option value="${g}" ${sel}>Group ${g}</option>`;
        }
        html += `</select><div class="invalid-feedback"></div></td>`;
        
        // Remove button
        html += `<td class="align-middle text-center">`;
        html += `<button type="button" class="btn btn-sm btn-danger remove-row-btn"><i class="bx bx-trash"></i></button>`;
        html += `</td></tr>`;
        
        return html;
    },

    addRow(selected = {}) {
        const index = this.rowCounter++;
        const $tbody = $('#eligibilityTable tbody');
        $tbody.append(this.renderRow(index, selected));
        
        const $newRow = $tbody.find('tr').last();
        Select2Manager.initEligibilityRow($newRow);
        this.updateRowNumbers();
    },

    removeRow($btn) {
        $btn.closest('tr').remove();
        this.updateRowNumbers();
    },

    updateRowNumbers() {
        $('#eligibilityTable tbody tr').each((idx, row) => {
            $(row).find('.row-number').text(idx + 1);
        });
    },

    clearRows() {
        $('#eligibilityTable tbody').empty();
        this.rowCounter = 0;
    },

    getData() {
        const data = [];
        $('#eligibilityTable tbody tr').each(function() {
            const program_id = $(this).find('.program-select').val();
            const level_id = $(this).find('.level-select').val();
            const group_ids = $(this).find('.group-select').val() || [];
            
            if (program_id && level_id && group_ids.length > 0) {
                data.push({ program_id, level_id, group_ids });
            }
        });
        return data;
    }
};

// ===========================
// SCHEDULE DETAILS MANAGER
// ===========================
const ScheduleDetailsManager = {
    cardCounter: 0,

    renderCard(index, selected = {}) {
        const $template = $('#schedule-detail-template');
        const $card = $($template.html());
        
        $card.attr('data-index', index);
        $card.find('.schedule-number').text(index + 1);
        
        // Set names
        $card.find('.schedule-select').attr('name', `schedule_details[${index}][schedule_id]`);
        $card.find('.activity-type-select').attr('name', `schedule_details[${index}][activity_type]`);
        $card.find('.group-select').attr('name', `schedule_details[${index}][group_numbers][]`);
        $card.find('.location-input').attr('name', `schedule_details[${index}][location]`);
        $card.find('.program-select').attr('name', `schedule_details[${index}][program_id]`);
        $card.find('.level-select').attr('name', `schedule_details[${index}][level_id]`);
        $card.find('.day-select').attr('name', `schedule_details[${index}][schedule_day_id]`);
        $card.find('.slot-select').attr('name', `schedule_details[${index}][schedule_slot_ids][]`);
        $card.find('.min-capacity-input').attr('name', `schedule_details[${index}][min_capacity]`);
        $card.find('.max-capacity-input').attr('name', `schedule_details[${index}][max_capacity]`);
        
        // Populate schedules
        const $scheduleSelect = $card.find('.schedule-select');
        DataStore.schedules.forEach(sch => {
            const sel = sch.id == selected.schedule_id ? 'selected' : '';
            $scheduleSelect.append(`<option value="${sch.id}" ${sel}>${Utils.escapeHtml(sch.title)}</option>`);
        });
        
        // Populate programs
        const $programSelect = $card.find('.program-select');
        DataStore.programs.forEach(prog => {
            const sel = prog.id == selected.program_id ? 'selected' : '';
            $programSelect.append(`<option value="${prog.id}" ${sel}>${Utils.escapeHtml(prog.name)}</option>`);
        });
        
        // Populate levels
        const $levelSelect = $card.find('.level-select');
        DataStore.levels.forEach(level => {
            const sel = level.id == selected.level_id ? 'selected' : '';
            $levelSelect.append(`<option value="${level.id}" ${sel}>${Utils.escapeHtml(level.name)}</option>`);
        });
        
        // Populate groups (1-20)
        const $groupSelect = $card.find('.group-select');
        for (let g = 1; g <= 20; g++) {
            const sel = (Array.isArray(selected.group_numbers) && selected.group_numbers.includes(String(g))) ? 'selected' : '';
            $groupSelect.append(`<option value="${g}" ${sel}>${Utils.escapeHtml(@json(__('Group')))} ${g}</option>`);
        }
        
        // Set other values
        if (selected.activity_type) $card.find('.activity-type-select').val(selected.activity_type);
        if (selected.location) $card.find('.location-input').val(selected.location);
        if (selected.min_capacity) $card.find('.min-capacity-input').val(selected.min_capacity);
        if (selected.max_capacity) $card.find('.max-capacity-input').val(selected.max_capacity);
        
        return $card;
    },

    addCard(selected = {}) {
        const index = this.cardCounter++;
        const $card = this.renderCard(index, selected);
        $('#schedule-details-container').append($card);
        Select2Manager.initScheduleCard($card);
        this.updateCardNumbers();
        return $card;
    },

    removeCard($btn) {
        $btn.closest('.schedule-detail-card').remove();
        this.updateCardNumbers();
    },

    updateCardNumbers() {
        $('#schedule-details-container .schedule-detail-card').each((idx, card) => {
            $(card).attr('data-index', idx);
            $(card).find('.schedule-number').text(idx + 1);
        });
    },

    clearCards() {
        $('#schedule-details-container').empty();
        this.cardCounter = 0;
    },

    async loadDaysSlots(scheduleId, $card) {
        const $daySelect = $card.find('.day-select');
        const $slotSelect = $card.find('.slot-select');
        
        $daySelect.empty().prop('disabled', true).append(`<option value="">Select Day</option>`);
        $slotSelect.empty().prop('disabled', true);
        
        if (!scheduleId) return;
        
        try {
            if (DataStore.scheduleDaysCache[scheduleId]) {
                this.populateDays(scheduleId, $daySelect);
                return;
            }
            
            const response = await ApiService.fetchScheduleDaysSlots(scheduleId);
            
            if (Utils.isResponseSuccess(response)) {
                DataStore.scheduleDaysCache[scheduleId] = Utils.getResponseData(response);
                this.populateDays(scheduleId, $daySelect);
            }
        } catch (error) {
            Utils.handleError(error);
        }
    },

    populateDays(scheduleId, $daySelect) {
        const days = DataStore.scheduleDaysCache[scheduleId];
        if (!days || !Array.isArray(days)) return;
        
        days.forEach(dayObj => {
            const dayName = dayObj.day_of_week.charAt(0).toUpperCase() + dayObj.day_of_week.slice(1);
            $daySelect.append(`<option value="${Utils.escapeHtml(dayObj.day_of_week)}">${Utils.escapeHtml(dayName)}</option>`);
        });
        
        $daySelect.prop('disabled', false);
        Utils.initSelect2($daySelect, { placeholder: 'Select Day' });
    },

    loadSlots(scheduleId, selectedDay, $slotSelect) {
        $slotSelect.empty().prop('disabled', true);
        
        if (!scheduleId || !selectedDay) return;
        
        const days = DataStore.scheduleDaysCache[scheduleId];
        if (!days) return;
        
        const dayData = days.find(d => d.day_of_week === selectedDay);
        if (!dayData || !dayData.slots) return;
        
        dayData.slots.forEach(slot => {
            const label = slot.label || `${slot.start_time} - ${slot.end_time}`;
            $slotSelect.append(`<option value="${slot.id}" data-order="${slot.slot_order}">Slot ${slot.slot_order}: ${Utils.escapeHtml(label)}</option>`);
        });
        
        $slotSelect.prop('disabled', false);
        Utils.initSelect2($slotSelect, { 
            placeholder: 'Select Slots',
            closeOnSelect: false
        });
    },

    validateConsecutiveSlots($slotSelect) {
        const selectedValues = $slotSelect.val() || [];
        const $card = $slotSelect.closest('.schedule-detail-card');
        const $feedback = $card.find('.slot-select + .form-text').next('.invalid-feedback');
        const $summary = $card.find('.slot-summary');
        const $summaryText = $card.find('.slot-summary-text');
        
        if (selectedValues.length === 0) {
            $feedback.text('').removeClass('d-block');
            $summary.addClass('d-none');
            $slotSelect.removeClass('is-invalid');
            return true;
        }
        
        const selectedSlots = selectedValues.map(val => {
            const $option = $slotSelect.find(`option[value="${val}"]`);
            return {
                id: val,
                order: parseInt($option.attr('data-order')),
                label: $option.text()
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
            $feedback.text('').removeClass('d-block');
            $slotSelect.removeClass('is-invalid');
            return true;
        }
        
        // Check consecutive
        let isConsecutive = true;
        for (let i = 1; i < selectedSlots.length; i++) {
            if (selectedSlots[i].order !== selectedSlots[i - 1].order + 1) {
                isConsecutive = false;
                break;
            }
        }
        
        if (!isConsecutive) {
            $feedback.text('Selected slots must be consecutive').addClass('d-block');
            $slotSelect.addClass('is-invalid');
            return false;
        }
        
        $feedback.text('').removeClass('d-block');
        $slotSelect.removeClass('is-invalid');
        return true;
    },

    getData() {
        const data = [];
        $('#schedule-details-container .schedule-detail-card').each(function() {
            const schedule_id = $(this).find('.schedule-select').val();
            const activity_type = $(this).find('.activity-type-select').val();
            const schedule_day_id = $(this).find('.day-select').val();
            const schedule_slot_ids = $(this).find('.slot-select').val() || [];
            const group_numbers = $(this).find('.group-select').val() || [];
            const location = $(this).find('.location-input').val();
            const program_id = $(this).find('.program-select').val() || null;
            const level_id = $(this).find('.level-select').val() || null;
            const min_capacity = $(this).find('.min-capacity-input').val();
            const max_capacity = $(this).find('.max-capacity-input').val();
            
            if (schedule_id && activity_type && schedule_day_id && schedule_slot_ids.length > 0 && 
                group_numbers.length > 0 && location && min_capacity && max_capacity) {
                data.push({
                    schedule_id,
                    activity_type,
                    schedule_day_id,
                    schedule_slot_ids,
                    group_numbers,
                    location,
                    program_id,
                    level_id,
                    min_capacity,
                    max_capacity
                });
            }
        });
        return data;
    }
};

// ===========================
// ELIGIBILITY MODE MANAGER
// ===========================
const EligibilityModeManager = {
    init() {
        $('[data-bs-toggle="tooltip"]').tooltip();
        
        $('input[name="mode"]').on('change', (e) => {
            this.showSection(e.target.value);
        });
        
        this.showSection(MODES.INDIVIDUAL);
    },

    showSection(mode) {
        $('.eligibility-section').addClass('d-none');
        
        $('#allProgramsLevelSelect, #allLevelsProgramSelect')
            .removeClass('is-invalid')
            .val('')
            .trigger('change')
            .next('.invalid-feedback').text('');
        
        switch (mode) {
            case MODES.INDIVIDUAL:
                $('#eligibility-individual-section').removeClass('d-none');
                break;
            case MODES.ALL_PROGRAMS:
                $('#eligibility-all-programs-section').removeClass('d-none');
                break;
            case MODES.ALL_LEVELS:
                $('#eligibility-all-levels-section').removeClass('d-none');
                break;
            case MODES.UNIVERSAL:
                $('#eligibility-universal-section').removeClass('d-none');
                break;
        }
    }
};

// ===========================
// FORM MANAGER
// ===========================
const FormManager = {
    init() {
        this.bindEvents();
        EligibilityTableManager.addRow();
        ScheduleDetailsManager.addCard();
    },

    bindEvents() {
        $('#addEligibilityRowBtn').on('click', () => EligibilityTableManager.addRow());
        
        $(document).on('click', '.remove-row-btn', function() {
            EligibilityTableManager.removeRow($(this));
        });
        
        $('#addScheduleDetailBtn').on('click', () => ScheduleDetailsManager.addCard());
        
        $(document).on('click', '.remove-schedule-btn', function() {
            ScheduleDetailsManager.removeCard($(this));
        });
        
        $(document).on('change', '.schedule-select', function() {
            const $card = $(this).closest('.schedule-detail-card');
            const scheduleId = $(this).val();
            ScheduleDetailsManager.loadDaysSlots(scheduleId, $card);
        });
        
        $(document).on('change', '.day-select', function() {
            const $card = $(this).closest('.schedule-detail-card');
            const scheduleId = $card.find('.schedule-select').val();
            const selectedDay = $(this).val();
            const $slotSelect = $card.find('.slot-select');
            ScheduleDetailsManager.loadSlots(scheduleId, selectedDay, $slotSelect);
        });
        
        $(document).on('change', '.slot-select', function() {
            ScheduleDetailsManager.validateConsecutiveSlots($(this));
        });
        
        $('#availableCourseForm').on('submit', (e) => this.handleSubmit(e));
    },

    getFormData() {
        const mode = $('input[name="mode"]:checked').val();
        const data = {
            course_id: $('#course_id').val(),
            term_id: $('#term_id').val(),
            mode: mode,
            eligibility: [],
            schedule_details: ScheduleDetailsManager.getData()
        };
        
        switch (mode) {
            case MODES.INDIVIDUAL:
                data.eligibility = EligibilityTableManager.getData();
                break;
            case MODES.ALL_PROGRAMS:
                data.level_id = $('#allProgramsLevelSelect').val();
                break;
            case MODES.ALL_LEVELS:
                data.program_id = $('#allLevelsProgramSelect').val();
                break;
        }
        
        return data;
    },

    validateForm(data) {
        let isValid = true;
        
        if (!data.course_id) {
            Utils.showValidationError('#course_id', @json(__('Please select a course')));
            isValid = false;
        }
        
        if (!data.term_id) {
            Utils.showValidationError('#term_id', @json(__('Please select a term')));
            isValid = false;
        }
        
        // Validate eligibility based on mode
        switch (data.mode) {
            case MODES.INDIVIDUAL:
                if (!data.eligibility || data.eligibility.length === 0) {
                    Utils.showError(@json(__('Please add at least one eligibility pair')));
                    isValid = false;
                }
                break;
            case MODES.ALL_PROGRAMS:
                if (!data.level_id) {
                    Utils.showValidationError('#allProgramsLevelSelect', @json(__('Please select a level')));
                    isValid = false;
                }
                break;
            case MODES.ALL_LEVELS:
                if (!data.program_id) {
                    Utils.showValidationError('#allLevelsProgramSelect', @json(__('Please select a program')));
                    isValid = false;
                }
                break;
        }
        
        // Validate schedule details
        if (!data.schedule_details || data.schedule_details.length === 0) {
            Utils.showError(@json(__('Please add at least one schedule detail')));
            isValid = false;
        }
        
        // Validate each schedule detail
        data.schedule_details.forEach((detail, idx) => {
            const $card = $(`#schedule-details-container .schedule-detail-card:eq(${idx})`);
            
            if (!ScheduleDetailsManager.validateConsecutiveSlots($card.find('.slot-select'))) {
                Utils.showError(@json(__('Selected slots must be consecutive in Schedule Detail')) + ` ${idx + 1}`);
                isValid = false;
            }
            
            const minCap = parseInt(detail.min_capacity);
            const maxCap = parseInt(detail.max_capacity);
            
            if (isNaN(minCap) || minCap < 1) {
                Utils.showError(@json(__('Invalid minimum capacity in Schedule Detail')) + ` ${idx + 1}`);
                isValid = false;
            }
            
            if (isNaN(maxCap) || maxCap < 1) {
                Utils.showError(@json(__('Invalid maximum capacity in Schedule Detail')) + ` ${idx + 1}`);
                isValid = false;
            }
            
            if (maxCap < minCap) {
                Utils.showError(@json(__('Maximum capacity must be >= minimum capacity in Schedule Detail')) + ` ${idx + 1}`);
                isValid = false;
            }
        });
        
        return isValid;
    },

    async handleSubmit(e) {
        e.preventDefault();
        
        const $submitBtn = $('button[type="submit"]');
        const loadingConfig = {
            loadingText: @json(__('Adding...')),
            loadingIcon: 'bx bx-loader-alt bx-spin me-1',
            normalText: @json(__('Add Available Course')),
            normalIcon: 'bx bx-plus me-1'
        };
        
        Utils.setLoadingState($submitBtn, true, loadingConfig);
        
        try {
            const data = this.getFormData();
            
            if (!this.validateForm(data)) {
                Utils.setLoadingState($submitBtn, false, loadingConfig);
                return;
            }
            
            const response = await ApiService.storeAvailableCourse(data);
            
            Utils.showSuccess(response.message || @json(__('Available course created successfully')));
            
            setTimeout(() => {
                window.location.href = ROUTES.availableCourses.index;
            }, 1500);
            
        } catch (error) {
            Utils.handleError(error);
            Utils.setLoadingState($submitBtn, false, loadingConfig);
        }
    }
};

// ===========================
// INITIALIZATION
// ===========================
$(() => {
    DataStore.initialize().then((success) => {
        if (success) {
            Select2Manager.init();
            EligibilityModeManager.init();
            FormManager.init();
            Utils.hidePageLoader();
        }
    });
});
</script>
@endpush