
@extends('layouts.home')

@section('title', 'Admin Schedule Slots | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Statistics Cards Section --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 
                id="schedule-slots"
                label="Total Schedule Slots"
                color="primary"
                icon="bx bx-time-five"
            />
        </div>
    </div>

    {{-- Page Header Section --}}
    <x-ui.page-header 
        title="Schedule Slots"
        description="Manage all schedule slots and export in bulk using the options on the right."
        icon="bx bx-time-five"
    >
        <button 
            class="btn btn-primary mx-2" 
            id="addSlotBtn" 
            type="button" 
            data-bs-toggle="modal" 
            data-bs-target="#slotModal"
        >
            <i class="bx bx-plus me-1"></i> Add Slot
        </button>
    </x-ui.page-header>


    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable
        :headers="['Schedule', 'Day', 'Start Time', 'End Time', 'Duration', 'Order', 'Status', 'Action']"
        :columns="[
            ['data'=>'schedule', 'name' => 'schedule'],
            ['data' => 'day_of_week', 'name' => 'day_of_week'],
            ['data' => 'start_time', 'name' => 'start_time'],
            ['data' => 'end_time', 'name' => 'end_time'],
            ['data' => 'duration_minutes', 'name' => 'duration_minutes'],
            ['data' => 'slot_order', 'name' => 'slot_order'],
            ['data' => 'status', 'name' => 'status'],
            ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
        ]"
        :ajax-url="route('schedule-slots.datatable')"
        table-id="schedule-slots-table"
    />


    {{-- Modals Section --}}
  {{-- Add Slot Modal --}}
  <x-ui.modal 
    id="slotModal"
    title="Add Schedule Slot"
    size="lg"
    :scrollable="true"
    class="slot-modal"
  >
    <x-slot name="slot">
      <form id="slotForm" class="needs-validation" novalidate>
        <input type="hidden" id="slot_id" name="slot_id">
        <div class="row g-3">
          {{-- Schedule Selection --}}
          <div class="col-12">
                        <label for="schedule_id" class="form-label">Schedule <span class="text-danger">*</span></label>
                        <select class="form-select" id="schedule_id" name="schedule_id" required>
                            <option value="">Select Schedule</option>
                        </select>
                        <div class="invalid-feedback">Please select a schedule.</div>
                    </div>

                    {{-- Schedule Info Section --}}
                    <div class="col-12 schedule-info-section" style="display:none;">
                        {{-- Schedule Pattern Info --}}
                        <div class="alert alert-info mb-3">
                            <div class="d-flex gap-2">
                                <i class="bx bx-info-circle fs-5"></i>
                                <div>
                                    <h6 class="mb-1">Schedule Pattern</h6>
                                    <p class="mb-0" id="schedulePatternText"></p>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Date/Day Selection Card --}}
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle text-muted mb-3">Date/Day Selection</h6>
                                <div class="row g-3">
                                    {{-- Day Selection --}}
                                    <div class="col-md-12" id="daySelectionField" style="display: none;">
                                        <label for="day_of_week" class="form-label">Day of Week <span class="text-danger">*</span></label>
                                        <select class="form-select" id="day_of_week" name="day_of_week">
                                            <option value="">Select Day</option>
                                            @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                                <option value="{{ $day }}">{{ ucfirst($day) }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">Please select a day.</div>
                                    </div>

                                    {{-- Specific Date --}}
                                    <div class="col-md-12" id="specificDateField" style="display: none;">
                                        <label for="specific_date" class="form-label">Specific Date <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control datepicker" id="specific_date" name="specific_date" placeholder="Select date" data-date-format="yyyy-mm-dd" readonly>
                                            <span class="input-group-text"><i class="bx bx-calendar"></i></span>
                                        </div>
                                        <div class="invalid-feedback">Please select a date.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Time Selection Card --}}
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle text-muted mb-3">Time Selection</h6>
                                <div class="row g-3">
                                    {{-- Time Selection --}}
                                    <div class="col-md-6">
                                        <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                                        <div class="invalid-feedback">Please select a valid start time.</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="end_time" name="end_time" required>
                                        <div class="invalid-feedback">Please select a valid end time.</div>
                                    </div>

                                    {{-- Duration (Read-only) --}}
                                    <div class="col-12">
                                        <label for="duration_minutes" class="form-label">Duration</label>
                                        <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" 
                                               readonly>
                                        <small class="form-text text-muted">Duration is automatically calculated from start and end times</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Status Card --}}
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-subtitle text-muted mb-3">Status</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary" form="slotForm">
                <i class="bx bx-save me-1"></i> Save
            </button>
        </x-slot>
    </x-ui.modal>

    {{-- View Slot Modal --}}
    <x-ui.modal 
        id="viewSlotModal"
        title="Slot Details"
        size="lg"
        :scrollable="true"
        class="view-slot-modal"
    >
        <x-slot name="slot">
            <div id="slotDetailsSection" class="p-2">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="bx bx-time-five me-2"></i>
                            <span id="viewSlotScheduleTitle">Schedule Details</span>
                        </h5>
                        
                        <div class="row g-3">
                            {{-- Basic Info --}}
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <label class="text-muted mb-1">Day</label>
                                    <h6 id="viewSlotDay" class="mb-0">-</h6>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <label class="text-muted mb-1">Order</label>
                                    <h6 id="viewSlotOrder" class="mb-0">-</h6>
                                </div>
                            </div>

                            {{-- Time Details --}}
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <label class="text-muted mb-1">Start Time</label>
                                    <h6 id="viewSlotStart" class="mb-0">-</h6>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="detail-item">
                                    <label class="text-muted mb-1">End Time</label>
                                    <h6 id="viewSlotEnd" class="mb-0">-</h6>
                                </div>
                            </div>

                            {{-- Additional Info --}}
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <label class="text-muted mb-1">Duration</label>
                                    <h6 id="viewSlotDuration" class="mb-0">-</h6>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="detail-item">
                                    <label class="text-muted mb-1">Status</label>
                                    <h6 id="viewSlotActive" class="mb-0">-</h6>
                                </div>
                            </div>

                            {{-- Specific Date --}}
                            <div class="col-12">
                                <div class="detail-item">
                                    <label class="text-muted mb-1">Specific Date</label>
                                    <h6 id="viewSlotSpecificDate" class="mb-0 text-muted">-</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                <i class="bx bx-x me-1"></i> Close
            </button>
        </x-slot>
    </x-ui.modal>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/utils.js') }}"></script>
<script>
/**
 * Schedule Slot Management System JavaScript
 * Handles CRUD operations for schedule slots
 */

// ===========================
// CONSTANTS AND CONFIGURATION
// ===========================

const ROUTES = {
  slots: {
    stats: '{{ route('schedule-slots.stats') }}',
    store: '{{ route('schedule-slots.store') }}',
    show: '{{ route('schedule-slots.show', ':id') }}',
    update: '{{ route('schedule-slots.update', ':id') }}',
    destroy: '{{ route('schedule-slots.destroy', ':id') }}',
    toggleStatus: '{{ route('schedule-slots.toggle-status', ':id') }}'
  },
  schedules: {
    all: '{{ route('schedules.all') }}',
    show: '{{ route('schedules.show', ':id') }}'
  }
};

// ===========================
// API SERVICE LAYER
// ===========================

const ApiService = {
  request(options) {
    return $.ajax(options);
  },

  fetchAllSchedules() {
    return this.request({
      url: ROUTES.schedules.all,
      method: 'GET'
    });
  },

  fetchSchedule(id) {
    return this.request({
      url: Utils.replaceRouteId(ROUTES.schedules.show, id),
      method: 'GET'
    });
  },

  fetchSlotStats() {
    return this.request({
      url: ROUTES.slots.stats,
      method: 'GET'
    });
  },

  fetchSlot(id) {
    return this.request({
      url: Utils.replaceRouteId(ROUTES.slots.show, id),
      method: 'GET'
    });
  },

  saveSlot(formData, id = null) {
    const url = id ? Utils.replaceRouteId(ROUTES.slots.update, id) : ROUTES.slots.store;
    const method = id ? 'PUT' : 'POST';
    return this.request({
      url: url,
      method: method,
      data: formData,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });
  },

  deleteSlot(id) {
    return this.request({
      url: Utils.replaceRouteId(ROUTES.slots.destroy, id),
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });
  },

  toggleSlotStatus(id) {
    return this.request({
      url: Utils.replaceRouteId(ROUTES.slots.toggleStatus, id),
      method: 'PATCH',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      }
    });
  }
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================

const StatsManager = {
  toggleLoadingState(elementId, isLoading) {
    const $value = $(`#${elementId}-value`);
    const $loader = $(`#${elementId}-loader`);
    const $updated = $(`#${elementId}-last-updated`);
    const $updatedLoader = $(`#${elementId}-last-updated-loader`);

    if (isLoading) {
      $value.addClass('d-none');
      $loader.removeClass('d-none');
      $updated.addClass('d-none');
      $updatedLoader.removeClass('d-none');
    } else {
      $value.removeClass('d-none');
      $loader.addClass('d-none');
      $updated.removeClass('d-none');
      $updatedLoader.addClass('d-none');
  }
},

loadSlotStats() {
  StatsManager.toggleLoadingState('schedule-slots', true);
    ApiService.fetchSlotStats()
      .done((response) => {
        const data = response.data;
        $('#schedule-slots-value').text(data.total.count ?? '--');
        $('#schedule-slots-last-updated').text(data.total.lastUpdateTime ?? '--');
        StatsManager.toggleLoadingState('schedule-slots', false);
      })
      .fail(() => {
        $('#schedule-slots-value').text('N/A');
        $('#schedule-slots-last-updated').text('N/A');
        StatsManager.toggleLoadingState('schedule-slots', false);
        Utils.showError('Failed to load slot statistics');
      });
  }
};

// ===========================
// SLOT VIEW & DELETE OPERATIONS
// ===========================

const SlotManager = {
  currentSlotId: null,
  selectedSchedule: null,

  initializeScheduleSelect() {
    $('#schedule_id').select2({
      theme: 'bootstrap-5',
      placeholder: 'Select Schedule',
      allowClear: true,
      width: '100%', 
      dropdownParent: $('#slotModal')
    });
    
    // Initialize datepicker
    $('.datepicker').datepicker({
      autoclose: true,
      todayHighlight: true,
      orientation: 'bottom',
      container: '#slotModal',
      format: 'yyyy-mm-dd'
    });
    
    this.loadSchedules();
  },

  loadSchedules() {
    const $select = $('#schedule_id');
    
    // Show loading state
    $select.empty().append('<option value="">Loading schedules...</option>');
    $select.prop('disabled', true);
    
    ApiService.fetchAllSchedules()
      .done(response => {
        $select.empty().append('<option value="">Select Schedule</option>');
        
        if (response.data?.length > 0) {
          response.data.forEach(schedule => {
            $select.append(`<option value="${schedule.id}" data-pattern="${schedule.schedule_type.repetition_pattern}">
              ${schedule.title} 
            </option>`);
          });
        }
        
        $select.prop('disabled', false);
      })
      .fail(xhr => {
        console.error('Failed to fetch schedules:', xhr);
        Utils.showError('Failed to load schedules. Please refresh the page.');
        $select.empty().append('<option value="">Failed to load schedules</option>');
        $select.prop('disabled', true);
      });
  },

  handleScheduleSelection() {
    $('#schedule_id').on('change', function() {
      const scheduleId = $(this).val();
      if (!scheduleId) {
        SlotManager.resetScheduleFields();
        return;
      }

      ApiService.fetchSchedule(scheduleId)
        .done((response) => {
          const schedule = response.data;
          SlotManager.selectedSchedule = schedule;
          SlotManager.updateFormBasedOnSchedule(schedule);
        })
        .fail((xhr) => {
          Utils.handleAjaxError(xhr,xhr.responseJSON?.message);
        });
    });
  },

  updateFormBasedOnSchedule(schedule) {
    // Show schedule info section
    $('.schedule-info-section').show();
    
    // Update schedule pattern text
    $('#schedulePatternText').text(
      schedule.schedule_type.is_repetitive && schedule.schedule_type.repetition_pattern === 'weekly' 
        ? 'This is a weekly repeating schedule. Please select a day of the week.' 
        : 'This is a date range schedule. Please select a specific date.'
    );

    // Update time input constraints
    $('#start_time').attr({
      'min': schedule.day_starts_at,
      'max': schedule.day_ends_at
    });
    $('#end_time').attr({
      'min': schedule.day_starts_at,
      'max': schedule.day_ends_at
    });


    // Reset fields first
    $('#daySelectionField, #specificDateField').hide();
    $('#day_of_week, #specific_date').val('').prop('required', false);

    // Show/hide fields based on schedule pattern
    if (schedule.schedule_type.is_repetitive && schedule.schedule_type.repetition_pattern === 'weekly') {
      $('#daySelectionField').fadeIn();
      $('#day_of_week').prop('required', true);
    } else {
      $('#specificDateField').fadeIn();
      $('#specific_date').prop('required', true);
      
      // Set min/max dates if available
      if (schedule.start_date && schedule.end_date) {
        $('.datepicker').datepicker('setStartDate', schedule.start_date);
        $('.datepicker').datepicker('setEndDate', schedule.end_date);
      }
    }
  },

  calculateDuration() {
    const startTime = $('#start_time').val();
    const endTime = $('#end_time').val();
    
    if (startTime && endTime) {
      const start = new Date(`1970-01-01T${startTime}`);
      const end = new Date(`1970-01-01T${endTime}`);
      
      if (end >= start) {
        const durationInMs = end - start;
        const minutes = Math.floor(durationInMs / 60000);
        $('#duration_minutes').val(minutes);
      } else {
        $('#duration_minutes').val('');
      }
    } else {
      $('#duration_minutes').val('');
    }
  },

  initializeTimeHandlers() {
    // Calculate duration when time changes
    $('#start_time, #end_time').on('change', () => {
      this.calculateDuration();
    });

    // Validate end time is after start time
    $('#end_time').on('change', function() {
      const startTime = $('#start_time').val();
      const endTime = $(this).val();
      
      if (startTime && endTime) {
        const startDate = new Date(`1970-01-01T${startTime}`);
        const endDate = new Date(`1970-01-01T${endTime}`);
        
        if (endDate < startDate) {
          $(this).addClass('is-invalid');
          $(this).siblings('.invalid-feedback').text('End time must be after start time');
        } else {
          $(this).removeClass('is-invalid');
        }
      }
    });
  },

  resetScheduleFields() {
    SlotManager.selectedSchedule = null;
    $('.schedule-info-section').hide();
    $('#schedulePatternText').text('');
    $('#scheduleTimeRangeText').text('');
    $('#daySelectionField').hide();
    $('#specificDateField').hide();
    $('#day_of_week, #specific_date').prop('required', false);
    $('#day_of_week').val('');
    $('#specific_date').val('').datepicker('clearDates');
    $('.datepicker').datepicker('setStartDate', null);
    $('.datepicker').datepicker('setEndDate', null);
    $('#start_time, #end_time').val('').attr('min', '').attr('max', '');
    $('#duration_minutes').val('');
  },

  handleAddSlot() {
    $(document).on('click', '#addSlotBtn', function() {
      SlotManager.currentSlotId = null;
      SlotManager.resetForm();
      $('#slotModal .modal-title').text('Add Schedule Slot');
      $('#slotModal').modal('show');
    });
  },



  handleViewSlot() {
    $(document).on('click', '.viewSlotBtn', function () {
      const slotId = $(this).data('id');
      SlotManager.clearViewModal();
      
      ApiService.fetchSlot(slotId)
        .done((response) => {
          const slot = response.data;
          SlotManager.populateViewModal(slot);
          $('#viewSlotModal').modal('show');
        })
        .fail((xhr) => {
          Utils.handleAjaxError(xhr,xhr.responseJSON?.message);
        });
    });
  },
  handleDeleteSlot() {
    $(document).on('click', '.deleteSlotBtn', function () {
      const slotId = $(this).data('id');
      Utils.showConfirmDialog({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          ApiService.deleteSlot(slotId)
            .done(() => {
              $('#schedule-slots-table').DataTable().ajax.reload(null, false);
              Utils.showSuccess('Slot has been deleted.');
              StatsManager.loadSlotStats();
            })
            .fail((xhr) => {
              Utils.handleAjaxError(xhr, xhr.responseJSON?.message);
            });
        }
      });
    });
  },

  handleToggleStatus() {
    $(document).on('click', '.toggleStatusBtn', function() {
      const slotId = $(this).data('id');
      ApiService.toggleSlotStatus(slotId)
        .done(() => {
          $('#schedule-slots-table').DataTable().ajax.reload(null, false);
          Utils.showSuccess('Slot status has been updated.');
          StatsManager.loadSlotStats();
        })
        .fail((xhr) => {
          Utils.handleAjaxError(xhr, xhr.responseJSON?.message);
        });
    });
  },

  handleFormSubmit() {
    $('#slotForm').on('submit', function(e) {
      e.preventDefault();
      // Ensure a schedule is selected (select2 may hide the native select or it may be disabled while loading)
      const scheduleVal = (typeof $('#schedule_id').select2 === 'function') ? $('#schedule_id').select2('val') : $('#schedule_id').val();

      if (!scheduleVal) {
        // mark invalid and prevent submit
        $('#schedule_id').addClass('is-invalid');
        $('#schedule_id').siblings('.invalid-feedback').text('Please select a schedule.');
        return;
      }

      // make sure the native select is enabled so it is included in serialization
      if ($('#schedule_id').prop('disabled')) {
        $('#schedule_id').prop('disabled', false);
      }

      // explicitly set the native select value from select2 (if used)
      $('#schedule_id').val(scheduleVal);

      const formData = $(this).serialize();
      const $submitBtn = $('#slotModal button[type="submit"]');
      const originalText = $submitBtn.text();

      $submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

      ApiService.saveSlot(formData, SlotManager.currentSlotId)
        .done(() => {
          $('#slotModal').modal('hide');
          $('#schedule-slots-table').DataTable().ajax.reload(null, false);
          Utils.showSuccess('Slot has been saved successfully.');
          StatsManager.loadSlotStats();
        })
        .fail((xhr) => {
          Utils.handleAjaxError(xhr, xhr.responseJSON?.message);
        })
        .always(() => {
          $submitBtn.prop('disabled', false).text(originalText);
        });
    });
  },

  resetForm() {
    $('#slotForm')[0].reset();
    $('#slot_id').val('');
  },

  populateForm(slot) {
    $('#slot_id').val(slot.id);
    // Set schedule_id and trigger change for dependent fields
    if (slot.schedule_id) {
      $('#schedule_id').val(slot.schedule_id).trigger('change');
    }
    $('#day_of_week').val(slot.day_of_week);
    $('#specific_date').val(slot.specific_date);
    $('#start_time').val(slot.start_time);
    $('#end_time').val(slot.end_time);
    $('#duration_minutes').val(slot.duration_minutes);
    $('#order').val(slot.slot_order);
    $('#is_active').prop('checked', slot.is_active);
  },

  clearViewModal() {
    $('#viewSlotScheduleTitle').text('');
    $('#viewSlotDay').text('');
    $('#viewSlotOrder').text('');
    $('#viewSlotStart').text('');
    $('#viewSlotEnd').text('');
    $('#viewSlotDuration').text('');
    $('#viewSlotActive').text('');
    $('#viewSlotSpecificDate').text('');
  },

  showViewModalError() {
    $('#viewSlotScheduleTitle').text('Error loading slot');
    $('#viewSlotDay').text('');
    $('#viewSlotOrder').text('');
    $('#viewSlotStart').text('');
    $('#viewSlotEnd').text('');
    $('#viewSlotDuration').text('');
    $('#viewSlotActive').text('');
    $('#viewSlotSpecificDate').text('Failed to load slot details.');
  },

  populateViewModal(slot) {
    $('#viewSlotScheduleTitle').text(slot.schedule_title || 'N/A');
    $('#viewSlotDay').text(slot.day_of_week ? slot.day_of_week.charAt(0).toUpperCase() + slot.day_of_week.slice(1) : '-');
    $('#viewSlotOrder').text(slot.slot_order || '');
    $('#viewSlotStart').text(slot.formatted_start_time || '--');
    $('#viewSlotEnd').text(slot.formatted_end_time || '--');
    $('#viewSlotDuration').text(slot.duration_minutes ? `${slot.duration_minutes} min` : '--');
    $('#viewSlotActive').text(slot.is_active ? 'Yes' : 'No');
    $('#viewSlotSpecificDate').text(slot.formatted_specific_date || '');
  }
};


// ===========================
// MAIN APPLICATION
// ===========================

const SlotManagementApp = {
  init() {
    StatsManager.loadSlotStats();
    SlotManager.initializeScheduleSelect();
    SlotManager.initializeTimeHandlers();
    SlotManager.handleScheduleSelection();
    SlotManager.handleAddSlot();

    SlotManager.handleViewSlot();
    SlotManager.handleDeleteSlot();
    SlotManager.handleToggleStatus();
    SlotManager.handleFormSubmit();
    Utils.hidePageLoader();

  }
};

// ===========================
// DOCUMENT READY
// ===========================

$(document).ready(() => {
  SlotManagementApp.init();
});
</script>
@endpush
