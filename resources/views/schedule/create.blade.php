@extends('layouts.home')

@section('title', 'Create Schedule | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    <x-ui.page-header 
        title="Create New Schedule"
        description="Create a new schedule with customizable slots and settings."
        icon="bx bx-calendar-plus"
    >
        <a href="{{ route('schedules.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back"></i> Back to Schedules
        </a>
    </x-ui.page-header>

    <form id="createScheduleForm" action="{{ route('schedules.store') }}" method="POST">
        @csrf

        <!-- Basic Information Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-info-circle me-2"></i>Basic Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="schedule_type_id" class="form-label fw-semibold">
                                <i class="bx bx-calendar-alt me-1"></i> Schedule Type <span class="text-danger">*</span>
                            </label>
                            <small class="form-text text-muted mb-2 d-block">Select the type of schedule.</small>
                            <select class="form-select select2-type" id="schedule_type_id" name="schedule_type_id" required>
                                <option value="">Select Schedule Type</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="term_id" class="form-label fw-semibold">
                                <i class="bx bx-calendar me-1"></i> Academic Term <span class="text-danger">*</span>
                            </label>
                            <small class="form-text text-muted mb-2 d-block">Select the academic term for the schedule.</small>
                            <select class="form-select select2-term" id="term_id" name="term_id" required>
                                <option value="">Select Academic Term</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">
                        <i class="bx bx-text me-1"></i> Description
                    </label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter schedule description..."></textarea>
                </div>

                <div class="mb-0">
                    <label for="status" class="form-label fw-semibold">
                        <i class="bx bx-check-circle me-1"></i> Status
                    </label>
                    <select class="form-select" id="status" name="status">
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="finalized">Finalized</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Schedule Pattern Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-calendar-event me-2"></i>Schedule Pattern
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="bx bx-repeat me-1"></i> Pattern Type <span class="text-danger">*</span>
                    </label>
                    <div class="mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="pattern" id="repetitive" value="repetitive">
                            <label class="form-check-label" for="repetitive">
                                <strong>Repetitive (Weekly)</strong>
                                <small class="d-block text-muted">Schedule repeats every week</small>
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="pattern" id="range" value="range">
                            <label class="form-check-label" for="range">
                                <strong>Date Range</strong>
                                <small class="d-block text-muted">Schedule runs within specific date range</small>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Repetitive (Weekly) Days Selection -->
                <div id="repetitiveDaysFields" style="display: none;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bx bx-calendar-week me-1"></i> Select Days of the Week <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" id="day_monday" value="monday">
                                <label class="form-check-label" for="day_monday">Monday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" id="day_tuesday" value="tuesday">
                                <label class="form-check-label" for="day_tuesday">Tuesday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" id="day_wednesday" value="wednesday">
                                <label class="form-check-label" for="day_wednesday">Wednesday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" id="day_thursday" value="thursday">
                                <label class="form-check-label" for="day_thursday">Thursday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" id="day_friday" value="friday">
                                <label class="form-check-label" for="day_friday">Friday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" id="day_saturday" value="saturday">
                                <label class="form-check-label" for="day_saturday">Saturday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days[]" id="day_sunday" value="sunday">
                                <label class="form-check-label" for="day_sunday">Sunday</label>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1">Choose at least one day for the repetitive schedule.</small>
                    </div>
                </div>
                <!-- Date Range Fields -->
                <div id="rangeFields" style="display: none;">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="start_date" class="form-label fw-semibold">
                                    <i class="bx bx-calendar-check me-1"></i> Start Date
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="end_date" class="form-label fw-semibold">
                                    <i class="bx bx-calendar-x me-1"></i> End Date
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Time Settings Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bx bx-time me-2"></i>Time Settings
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="day_starts_at" class="form-label fw-semibold">
                                <i class="bx bx-sun me-1"></i> Day Starts At <span class="text-danger">*</span>
                            </label>
                            <input type="time" class="form-control" id="day_starts_at" name="day_starts_at" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="day_ends_at" class="form-label fw-semibold">
                                <i class="bx bx-moon me-1"></i> Day Ends At <span class="text-danger">*</span>
                            </label>
                            <input type="time" class="form-control" id="day_ends_at" name="day_ends_at" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                            <label for="slot_duration_minutes" class="form-label fw-semibold">
                                <i class="bx bx-timer me-1"></i> Slot Duration <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="slot_duration_minutes" name="slot_duration_minutes" min="1" required>
                                <span class="input-group-text">minutes</span>
                            </div>
                        </div>
                        <div class="col-md-6" id="break_duration_field" style="display: none;">
                            <label for="break_duration_minutes" class="form-label fw-semibold">
                                <i class="bx bx-timer me-1"></i> Break Duration <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="break_duration_minutes" name="break_duration_minutes" min="1">
                                <span class="input-group-text">minutes</span>
                            </div>
                        </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="has_break" name="has_break">
                                <label class="form-check-label fw-semibold" for="has_break">
                                    <i class="bx bx-coffee me-1"></i> Include Break
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions Card -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="createBtn">
                        <i class="bx bx-save me-1"></i> Create Schedule
                    </button>
                    <a href="{{ route('schedules.index') }}" class="btn btn-secondary">
                        <i class="bx bx-x me-1"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
// ===========================
// APPLICATION CONFIGURATION
// ===========================
const CONFIG = {
    routes: {
        schedules: {
            store: '{{ route('schedules.store') }}',
            index: '{{ route('schedules.index') }}'
        },
        api: {
            terms: '{{ route('terms.all.with_inactive') }}',
            scheduleTypes: '{{ route('schedule-types.all') }}'
        }
    },
    defaults: {
        dayStart: '08:00',
        dayEnd: '17:00',
        slotDuration: 60,
        pattern: 'repetitive'
    }
};

// ===========================
// UTILITY FUNCTIONS
// ===========================
const Utils = {
    /**
     * Show success notification
     */
    showSuccess(message, timer = 3000) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: message,
            showConfirmButton: false,
            timer: timer,
            timerProgressBar: true,
            customClass: {
                popup: 'colored-toast'
            }
        });
    },

    /**
     * Show error notification
     */
    showError(message, title = 'Error') {
        Swal.fire({
            title: title,
            html: message,
            icon: 'error',
            confirmButtonText: 'OK',
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        });
    },

    /**
     * Show warning notification
     */
    showWarning(message, title = 'Warning') {
        return Swal.fire({
            title: title,
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes',
            cancelButtonText: 'Cancel'
        });
    },

    /**
     * Toggle button loading state
     */
    toggleButtonLoading(selector, isLoading, originalText = null) {
        const $btn = $(selector);
        
        if (isLoading) {
            const text = originalText || $btn.html();
            $btn.prop('disabled', true)
                .data('original-text', text)
                .html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
        } else {
            $btn.prop('disabled', false)
                .html($btn.data('original-text') || originalText || 'Submit');
        }
    },

    /**
     * Validate time range
     */
    validateTimeRange(startTime, endTime) {
        if (!startTime || !endTime) return true;
        return startTime < endTime;
    },

    /**
     * Validate date range
     */
    validateDateRange(startDate, endDate) {
        if (!startDate || !endDate) return true;
        return new Date(startDate) <= new Date(endDate);
    },

    /**
     * Format validation errors for display
     */
    formatValidationErrors(errors) {
        let errorHtml = '<ul class="mb-0 text-start">';
        Object.entries(errors).forEach(([field, messages]) => {
            messages.forEach(message => {
                errorHtml += `<li>${message}</li>`;
            });
        });
        errorHtml += '</ul>';
        return errorHtml;
    },
    /**
     * Hide the page loader overlay.
     */
    hidePageLoader() {
      const loader = document.getElementById('pageLoader');
      if (loader) {
        loader.classList.add('fade-out');
        // Restore scrollbars when loader is hidden
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
      }
    }
};

// ===========================
// API SERVICE
// ===========================
const ApiService = {
    /**
     * Base request method
     */
    request(options) {
        const defaults = {
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        };
        
        return $.ajax({...defaults, ...options});
    },

    /**
     * Fetch academic terms
     */
    fetchTerms(scheduleTypeId = null) {
        const params = scheduleTypeId ? { schedule_type_id: scheduleTypeId } : {};
        
        return this.request({
            url: CONFIG.routes.api.terms,
            method: 'GET',
            data: params
        });
    },

    /**
     * Fetch schedule types
     */
    fetchScheduleTypes() {
        return this.request({
            url: CONFIG.routes.api.scheduleTypes,
            method: 'GET'
        });
    },

    /**
     * Create new schedule
     */
    createSchedule(formData) {
        return this.request({
            url: CONFIG.routes.schedules.store,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false
        });
    }
};

// ===========================
// SELECT2 MANAGER
// ===========================
const Select2Manager = {
    /**
     * Initialize all select2 dropdowns
     */
    init() {
        this.initScheduleTypeSelect();
        this.initTermSelect();
        this.loadScheduleTypes();
        this.loadTerms(); // Load terms initially like schedule types
    },

    /**
     * Initialize schedule type select2
     */
    initScheduleTypeSelect() {
        $('#schedule_type_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Schedule Type',
            allowClear: true,
            width: '100%'
        });
    },

    /**
     * Initialize term select2
     */
    initTermSelect() {
        $('#term_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Academic Term',
            allowClear: true,
            width: '100%'
        });
    },

    /**
     * Load and populate schedule types
     */
    loadScheduleTypes() {
        const $select = $('#schedule_type_id');
        
        ApiService.fetchScheduleTypes()
            .done(response => {
                $select.empty().append('<option value="">Select Schedule Type</option>');
                
                if (response.data?.length > 0) {
                    response.data.forEach(type => {
                        $select.append(`<option value="${type.id}">${type.name}</option>`);
                    });
                }
                
                $select.trigger('change');
            })
            .fail(xhr => {
                console.error('Failed to fetch schedule types:', xhr);
                Utils.showError('Failed to load schedule types. Please refresh the page.');
            });
    },

    /**
     * Load and populate terms
     */
    loadTerms(scheduleTypeId = null) {
        const $select = $('#term_id');
        
        // Show loading state
        $select.empty()
               .append('<option value="">Loading terms...</option>')
               .prop('disabled', true);
        
        ApiService.fetchTerms(scheduleTypeId)
            .done(response => {
                $select.empty()
                       .append('<option value="">Select Academic Term</option>')
                       .prop('disabled', false);
                
                if (response.data?.length > 0) {
                    response.data.forEach(term => {
                        const termText = `${term.name} (${term.start_date} - ${term.end_date})`;
                        $select.append(`<option value="${term.id}">${termText}</option>`);
                    });
                } else {
                    $select.append('<option value="" disabled>No terms available</option>');
                }
                
                $select.trigger('change');
            })
            .fail(xhr => {
                console.error('Failed to fetch terms:', xhr);
                $select.empty()
                       .append('<option value="" disabled>Failed to load terms</option>')
                       .prop('disabled', false);
                Utils.showError('Failed to load academic terms. Please try again.');
            });
    }
};

// ===========================
// FORM MANAGER
// ===========================
const FormManager = {
    /**
     * Initialize form functionality
     */
    init() {
        this.setDefaults();
        this.bindEvents();
        this.initValidation();
    },

    /**
     * Set default form values
     */
    setDefaults() {
        $('#day_starts_at').val(CONFIG.defaults.dayStart);
        $('#day_ends_at').val(CONFIG.defaults.dayEnd);
        $('#slot_duration_minutes').val(CONFIG.defaults.slotDuration);
        $('#repetitive').prop('checked', true);
    },

    /**
     * Bind form events
     */
    bindEvents() {
        // Schedule type change
        $('#schedule_type_id').on('change', this.handleScheduleTypeChange.bind(this));
        
        // Pattern radio change
        $('input[name="pattern"]').on('change', this.handlePatternChange.bind(this));
        
        // Break checkbox change
        $('#has_break').on('change', this.handleBreakChange.bind(this));
        
        // Form submission
        $('#createScheduleForm').on('submit', this.handleFormSubmit.bind(this));
        
        // Time validation
        $('#day_starts_at, #day_ends_at').on('change', this.validateTimes.bind(this));
        
        // Date validation
        $('#start_date, #end_date').on('change', this.validateDates.bind(this));
    },

    /**
     * Handle schedule type change
     */
    handleScheduleTypeChange(e) {
        const scheduleTypeId = $(e.target).val();
        
        if (scheduleTypeId) {
            // Reload terms filtered by schedule type
            Select2Manager.loadTerms(scheduleTypeId);
        } else {
            // Reload all terms when no schedule type is selected
            Select2Manager.loadTerms();
        }
    },

    /**
     * Handle pattern radio change
     */
    handlePatternChange(e) {
        const pattern = $(e.target).val();

        if (pattern === 'range') {
            $('#repetitiveDaysFields').hide();
            $('#rangeFields').show();
            $('#start_date, #end_date').prop('required', true);
            $('input[name="days[]"]').prop('required', false);
        } else {
            $('#rangeFields').hide();
            $('#repetitiveDaysFields').show();
            $('#start_date, #end_date').prop('required', false);
            $('input[name="days[]"]').prop('required', false);
        }
    },

    /**
     * Handle break checkbox change
     */
    handleBreakChange(e) {
        const isChecked = $(e.target).is(':checked');
        
        if (isChecked) {
            $('#break_duration_field').show();
            $('#break_duration_minutes').prop('required', true);
        } else {
            $('#break_duration_field').hide();
            $('#break_duration_minutes').prop('required', false).val('');
        }
    },


    /**
     * Validate time inputs
     */
    validateTimes() {
        const startTime = $('#day_starts_at').val();
        const endTime = $('#day_ends_at').val();
        
        if (startTime && endTime && !Utils.validateTimeRange(startTime, endTime)) {
            Utils.showWarning('End time must be after start time.');
            $('#day_ends_at').focus();
            return false;
        }
        return true;
    },

    /**
     * Validate date inputs
     */
    validateDates() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        
        if (startDate && endDate && !Utils.validateDateRange(startDate, endDate)) {
            Utils.showWarning('End date must be after or equal to start date.');
            $('#end_date').focus();
            return false;
        }
        return true;
    },

    /**
     * Initialize form validation
     */
    initValidation() {
        $('#createScheduleForm').addClass('needs-validation');
    },

    /**
     * Handle form submission
     */
    handleFormSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            return;
        }

        const formData = new FormData($('#createScheduleForm')[0]);
        
        Utils.toggleButtonLoading('#createBtn', true);

        ApiService.createSchedule(formData)
            .done(response => {
                Utils.showSuccess('Schedule created successfully!', 2000);
                
                setTimeout(() => {
                    window.location.href = CONFIG.routes.schedules.index;
                }, 1500);
            })
            .fail(xhr => {
                let errorMessage = 'An error occurred while creating the schedule.';
                
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    errorMessage = Utils.formatValidationErrors(xhr.responseJSON.errors);
                } else if (xhr.responseJSON?.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                Utils.showError(errorMessage);
            })
            .always(() => {
                Utils.toggleButtonLoading('#createBtn', false);
            });
    },

    /**
     * Validate entire form
     */
    validateForm() {
        let isValid = true;
        const form = $('#createScheduleForm')[0];

        // HTML5 validation
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            isValid = false;
        }

        // Custom validations
        if (!this.validateTimes()) {
            isValid = false;
        }

        const selectedPattern = $('input[name="pattern"]:checked').val();
        if (selectedPattern === 'range' && !this.validateDates()) {
            isValid = false;
        }

        return isValid;
    }
};

// ===========================
// MAIN APPLICATION
// ===========================
const ScheduleCreationApp = {
    /**
     * Initialize the application
     */
    init() {
        Select2Manager.init();
        FormManager.init();
    }
};

$(document).ready(function() {
    ScheduleCreationApp.init();
    Utils.hidePageLoader();

});

</script>
@endpush