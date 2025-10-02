@extends('layouts.home')

@section('title', 'Edit Available Course | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  {{-- Page Header --}}
  <x-ui.page-header 
    title="Edit Available Course"
    description="Manage eligibility and schedules for the selected course"
    icon="bx bx-edit"
  />

  {{-- Basic Course Information --}}
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Course Information</h5>
    </div>
    <div class="card-body">
      <form id="basicInfoForm">
        @csrf
        @method('PUT')
        <div class="row g-3">
          <div class="col-md-4">
            <label for="course_id" class="form-label">Course <span class="text-danger">*</span></label>
            <select class="form-select select2" id="course_id" name="course_id">
              <option value="">Select Course</option>
            </select>
          </div>
          <div class="col-md-4">
            <label for="term_id" class="form-label">Term <span class="text-danger">*</span></label>
            <select class="form-select select2" id="term_id" name="term_id">
              <option value="">Select Term</option>
            </select>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- Eligibility Management --}}
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="bx bx-shield-quarter me-2"></i>Eligibility Management</h5>
      <button type="button" class="btn btn-sm btn-success" id="addEligibilityBtn">
        <i class="bx bx-plus me-1"></i>Add Eligibility
      </button>
    </div>
    <div class="card-body">
      <x-ui.datatable 
        :tableId="'eligibilityTable'"
        :headers="['Program', 'Level', 'Groups', 'Actions']"
        :columns="[
          ['data' => 'program_name', 'name' => 'program_name'],
          ['data' => 'level_name', 'name' => 'level_name'],
          ['data' => 'groups', 'name' => 'groups', 'orderable' => false],
          ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
        ]"
        :ajaxUrl="route('available_courses.eligibility.datatable', $availableCourse->id)"
        :filterFields="[]"
      />
    </div>
  </div>

  {{-- Schedule Details Management --}}
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0"><i class="bx bx-time me-2"></i>Schedule Details</h5>
      <button type="button" class="btn btn-sm btn-success" id="addScheduleBtn">
        <i class="bx bx-plus me-1"></i>Add Schedule
      </button>
    </div>
    <div class="card-body">
      <x-ui.datatable 
        :tableId="'scheduleTable'"
        :headers="['Activity Type', 'Location', 'Groups', 'Day', 'Slots', 'Capacity', 'Actions']"
        :columns="[
          ['data' => 'activity_type', 'name' => 'activity_type'],
          ['data' => 'location', 'name' => 'location'],
          ['data' => 'groups', 'name' => 'groups', 'orderable' => false],
          ['data' => 'day', 'name' => 'day'],
          ['data' => 'slots', 'name' => 'slots', 'orderable' => false],
          ['data' => 'capacity', 'name' => 'capacity', 'orderable' => false],
          ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
        ]"
        :ajaxUrl="route('available_courses.schedules.datatable', $availableCourse->id)"
        :filterFields="[]"
      />
    </div>
  </div>
</div>

{{-- ========== MODALS ========== --}}

{{-- Eligibility Modal --}}
<div class="modal fade" id="eligibilityModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eligibilityModalTitle">Add Eligibility</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="eligibilityForm">
        <div class="modal-body">
          <input type="hidden" id="eligibility_id" name="eligibility_id">
          
          <div class="mb-3">
            <label for="program_id" class="form-label">Program <span class="text-danger">*</span></label>
            <select class="form-select select2-modal" id="program_id" name="program_id" required>
              <option value="">Select Program</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="level_id" class="form-label">Level <span class="text-danger">*</span></label>
            <select class="form-select select2-modal" id="level_id" name="level_id" required>
              <option value="">Select Level</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="group_numbers" class="form-label">Group <span class="text-danger">*</span></label>
            <select class="form-select select2-modal" id="group_numbers" name="group_numbers" required>
              <option value="">Select Group</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i>Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Add Schedule Modal --}}
<div class="modal fade" id="addScheduleModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Schedule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="addScheduleForm">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="add_schedule_template_id" class="form-label">Schedule Template <span class="text-danger">*</span></label>
              <select class="form-select select2-modal" id="add_schedule_template_id" name="schedule_template_id" required>
                <option value="">Select Schedule</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label for="add_activity_type" class="form-label">Activity Type <span class="text-danger">*</span></label>
              <select class="form-select select2-modal" id="add_activity_type" name="activity_type" required>
                <option value="">Select Type</option>
                <option value="lecture">Lecture</option>
                <option value="tutorial">Tutorial</option>
                <option value="lab">Lab</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label for="add_schedule_group_numbers" class="form-label">Group <span class="text-danger">*</span></label>
              <select class="form-select select2-modal" id="add_schedule_group_numbers" name="group_numbers" required>
                <option value="">Select Group</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label for="add_location" class="form-label">Location</label>
              <input type="text" class="form-control" id="add_location" name="location" placeholder="Enter location">
            </div>
            
            <div class="col-md-6">
              <label for="add_schedule_day_id" class="form-label">Day <span class="text-danger">*</span></label>
              <select class="form-select select2-modal" id="add_schedule_day_id" name="schedule_day_id" required disabled>
                <option value="">Select Day</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label for="add_schedule_slot_ids" class="form-label">Time Slots <span class="text-danger">*</span></label>
              <select class="form-select select2-modal" id="add_schedule_slot_ids" name="schedule_slot_ids[]" multiple required disabled>
                <option value="">Select Slots</option>
              </select>
              <div class="form-text">Select consecutive slots only</div>
            </div>
            
            <div class="col-md-6">
              <label for="add_min_capacity" class="form-label">Min Capacity <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="add_min_capacity" name="min_capacity" min="1" required>
            </div>
            
            <div class="col-md-6">
              <label for="add_max_capacity" class="form-label">Max Capacity <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="add_max_capacity" name="max_capacity" min="1" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i>Add Schedule
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Schedule Modal --}}
<div class="modal fade" id="editScheduleModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Schedule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="editScheduleForm">
        <div class="modal-body">
          <input type="hidden" id="edit_schedule_id" name="schedule_id">
          <input type="hidden" id="edit_available_course_schedule_id" name="available_course_schedule_id">
          
          <div class="row g-3">
            <div class="col-md-6">
              <label for="edit_schedule_template_id" class="form-label">Schedule Template <span class="text-danger">*</span></label>
              <select class="form-select select2-modal" id="edit_schedule_template_id" name="schedule_template_id" required>
                <option value="">Select Schedule</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label for="edit_activity_type" class="form-label">Activity Type <span class="text-danger">*</span></label>
              <select class="form-select select2-modal" id="edit_activity_type" name="activity_type" required>
                <option value="">Select Type</option>
                <option value="lecture">Lecture</option>
                <option value="tutorial">Tutorial</option>
                <option value="lab">Lab</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label for="edit_schedule_group_number" class="form-label">Group <span class="text-danger">*</span></label>
              <select class="form-select select2-modal" id="edit_schedule_group_number" name="group_number" required>
                <option value="">Select Group</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label for="edit_location" class="form-label">Location</label>
              <input type="text" class="form-control" id="edit_location" name="location" placeholder="Enter location">
            </div>
            
            <div class="col-md-6">
              <label for="edit_schedule_day_id" class="form-label">Day <span class="text-danger">*</span></label>
              <select class="form-select select2-modal" id="edit_schedule_day_id" name="schedule_day_id" required disabled>
                <option value="">Select Day</option>
              </select>
            </div>
            
            <div class="col-md-6">
              <label for="edit_schedule_slot_ids" class="form-label">Time Slots <span class="text-danger">*</span></label>
              <select class="form-select select2-modal" id="edit_schedule_slot_ids" name="schedule_slot_ids[]" multiple required disabled>
                <option value="">Select Slots</option>
              </select>
              <div class="form-text">Select consecutive slots only</div>
            </div>
            
            <div class="col-md-6">
              <label for="edit_min_capacity" class="form-label">Min Capacity <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="edit_min_capacity" name="min_capacity" min="1" required>
            </div>
            
            <div class="col-md-6">
              <label for="edit_max_capacity" class="form-label">Max Capacity <span class="text-danger">*</span></label>
              <input type="number" class="form-control" id="edit_max_capacity" name="max_capacity" min="1" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i>Update Schedule
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

{{-- ========== STYLES ========== --}}
@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/libs/datatables/dataTables.bootstrap5.min.css') }}?v={{ config('app.version') }}">
<link rel="stylesheet" href="{{ asset('vendor/libs/datatables/responsive.bootstrap5.min.css') }}?v={{ config('app.version') }}">
<style>
  table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control::before,
  table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control::before {
    top: 50%;
    left: 5px;
    height: 1em;
    width: 1em;
    margin-top: -9px;
    display: inline-block;
    color: white;
    border: .15em solid white;
    border-radius: 1em;
    box-shadow: 0 0 .2em #444;
    box-sizing: content-box;
    text-align: center;
    text-indent: 0;
    line-height: 1em;
    content: "+";
    background-color: #931a23;
  }

  table.dataTable.dtr-inline.collapsed > tbody > tr.parent > td.dtr-control:before,
  table.dataTable.dtr-inline.collapsed > tbody > tr.parent > th.dtr-control:before {
    content: "-";
    background-color: #8592a3;
  }

  .badge-group {
    display: inline-block;
    margin: 2px;
  }

  .capacity-badge {
    font-size: 0.85rem;
  }
</style>
@endpush

{{-- ========== SCRIPTS ========== --}}
@push('scripts')
<script src="{{ asset('js/utils.js') }}?v={{ config('app.version') }}"></script>
<script>
(function() {
  'use strict';

  // ========== CONFIGURATION ==========
  const ROUTES = {
    courses: { all: "{{ route('courses.all') }}" },
    terms: { all: "{{ route('terms.all.with_inactive') }}" },
    programs: { all: "{{ route('programs.all') }}" },
    levels: { all: "{{ route('levels.all') }}" },
    schedules: { all: "{{ route('schedules.all') }}" },
    scheduleDaysSlots: { get: "{{ route('schedules.days-slots', ':id') }}" },
    availableCourse: {
      show: "{{ route('available_courses.show', $availableCourse->id) }}",
      updateBasic: "{{ route('available_courses.update.basic', $availableCourse->id) }}",
      eligibility: {
        store: "{{ route('available_courses.eligibility.store', $availableCourse->id) }}",
        delete: "{{ route('available_courses.eligibility.delete', [$availableCourse->id, ':id']) }}"
      },
      schedule: {
        show: "{{ route('available_courses.schedules.show', [$availableCourse->id, ':id']) }}",
        store: "{{ route('available_courses.schedules.store', $availableCourse->id) }}",
        update: "{{ route('available_courses.schedules.update', [$availableCourse->id, ':id']) }}",
        delete: "{{ route('available_courses.schedules.delete', [$availableCourse->id, ':id']) }}"
      }
    }
  };

  let eligibilityTable, scheduleTable;
  let scheduleDaysData = {};

  // ========== TABLE MANAGEMENT ==========
  function initializeTableReferences() {
    setTimeout(function() {
      try {
        if ($.fn.DataTable.isDataTable('#eligibilityTable')) {
          eligibilityTable = $('#eligibilityTable').DataTable();
        }
        if ($.fn.DataTable.isDataTable('#scheduleTable')) {
          scheduleTable = $('#scheduleTable').DataTable();
        }
        
        if (!eligibilityTable || !scheduleTable) {
          setTimeout(function() {
            if (!eligibilityTable && $.fn.DataTable.isDataTable('#eligibilityTable')) {
              eligibilityTable = $('#eligibilityTable').DataTable();
            }
            if (!scheduleTable && $.fn.DataTable.isDataTable('#scheduleTable')) {
              scheduleTable = $('#scheduleTable').DataTable();
            }
          }, 500);
        }
      } catch (error) {
        console.error('Error capturing table references:', error);
      }
    }, 300);
  }

  function reloadTable(table, tableId) {
    if (table && table.ajax) {
      table.ajax.reload();
    } else {
      setTimeout(function() {
        if ($.fn.DataTable.isDataTable(tableId)) {
          $(tableId).DataTable().ajax.reload();
        }
      }, 100);
    }
  }

  // ========== DATA LOADING ==========
  async function loadDropdownData() {
    try {
      const [courses, terms, programs, levels, schedules, availableCourse] = await Promise.all([
        $.getJSON(ROUTES.courses.all),
        $.getJSON(ROUTES.terms.all),
        $.getJSON(ROUTES.programs.all),
        $.getJSON(ROUTES.levels.all),
        $.getJSON(ROUTES.schedules.all),
        $.getJSON(ROUTES.availableCourse.show)
      ]);

      Utils.populateSelect($('#course_id'), courses.data, { textField: 'name' }, true);
      Utils.populateSelect($('#term_id'), terms.data, { textField: 'name' }, true);
      
      $('#course_id').val(availableCourse.data.course_id);
      $('#term_id').val(availableCourse.data.term_id);

      setTimeout(() => {
        $('#course_id, #term_id').prop('disabled', true).trigger('change.select2');
      }, 300);

      Utils.populateSelect($('#program_id'), programs.data, { textField: 'name' }, true);
      Utils.populateSelect($('#level_id'), levels.data, { textField: 'name' }, true);
      Utils.populateSelect($('#add_schedule_template_id, #edit_schedule_template_id'), schedules.data, { textField: 'title' }, true);

      for (let i = 1; i <= 30; i++) {
        $('#group_numbers, #add_schedule_group_numbers, #edit_schedule_group_number').append(`<option value="${i}">Group ${i}</option>`);
      }

      Utils.initSelect2($('#course_id, #term_id, #mode'));
      
    } catch (error) {
      Utils.showError('Failed to load data');
      console.error(error);
    }
  }

  // ========== ELIGIBILITY MANAGEMENT ==========
  function initializeEligibility() {
    $('#addEligibilityBtn').on('click', function() {
      $('#eligibilityForm')[0].reset();
      $('#eligibility_id').val('');
      $('#eligibilityModalTitle').text('Add Eligibility');
      $('#eligibilityModal').modal('show');
      
      setTimeout(() => {
        Utils.initSelect2($('.select2-modal'), { dropdownParent: $('#eligibilityModal') });
      }, 100);
    });

    $('#eligibilityForm').on('submit', async function(e) {
      e.preventDefault();
      
      const data = {
        program_id: $('#program_id').val(),
        level_id: $('#level_id').val(),
        group_numbers: [$('#group_numbers').val()]
      };

      try {
        const response = await $.ajax({
          url: ROUTES.availableCourse.eligibility.store,
          method: 'POST',
          data: data,
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        Utils.showSuccess(response.message);
        $('#eligibilityModal').modal('hide');
        reloadTable(eligibilityTable, '#eligibilityTable');
      } catch (error) {
        Utils.handleAjaxError(error);
      }
    });

    $(document).on('click', '.deleteEligibilityBtn', function() {
      const id = $(this).data('id');
      
      Swal.fire({
        title: 'Are you sure?',
        text: "This eligibility will be deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            await $.ajax({
              url: ROUTES.availableCourse.eligibility.delete.replace(':id', id),
              method: 'DELETE',
              headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });
            
            Utils.showSuccess('Eligibility deleted successfully');
            reloadTable(eligibilityTable, '#eligibilityTable');
          } catch (error) {
            Utils.handleAjaxError(error);
          }
        }
      });
    });
  }

  // ========== SCHEDULE MANAGEMENT - ADD ==========
  function initializeAddSchedule() {
    $('#addScheduleBtn').on('click', function() {
      $('#addScheduleForm')[0].reset();
      $('#add_schedule_day_id, #add_schedule_slot_ids').prop('disabled', true);
      $('#addScheduleModal').modal('show');
      
      setTimeout(() => {
        Utils.initSelect2($('.select2-modal'), { dropdownParent: $('#addScheduleModal') });
        Utils.initSelect2($('#add_schedule_slot_ids'), { 
          closeOnSelect: false, 
          dropdownParent: $('#addScheduleModal') 
        });
      }, 100);
    });

    $(document).on('change', '#add_schedule_template_id', async function() {
      const scheduleId = $(this).val();
      
      if (!scheduleId) {
        $('#add_schedule_day_id').prop('disabled', true).empty().append('<option value="">Select Day</option>');
        $('#add_schedule_slot_ids').prop('disabled', true).empty().append('<option value="">Select Slots</option>');
        return;
      }

      try {
        const response = await $.getJSON(ROUTES.scheduleDaysSlots.get.replace(':id', scheduleId));
        scheduleDaysData[scheduleId] = response.data;
        
        const dayOptions = response.data.map(d => ({
          id: d.day_of_week,
          name: d.day_of_week.charAt(0).toUpperCase() + d.day_of_week.slice(1)
        }));

        $('#add_schedule_day_id').empty().prop('disabled', false);
        Utils.populateSelect($('#add_schedule_day_id'), dayOptions, { textField: 'name' }, true);
        
        $('#add_schedule_slot_ids').prop('disabled', true).empty().append('<option value="">Select Slots</option>');
      } catch (error) {
        Utils.showError('Failed to load schedule days');
      }
    });

    $(document).on('change', '#add_schedule_day_id', function() {
      const scheduleId = $('#add_schedule_template_id').val();
      const day = $(this).val();
      
      if (!day || !scheduleDaysData[scheduleId]) {
        $('#add_schedule_slot_ids').prop('disabled', true).empty().append('<option value="">Select Slots</option>');
        return;
      }

      const dayData = scheduleDaysData[scheduleId].find(d => d.day_of_week === day);
      
      if (dayData && dayData.slots) {
        const slotOptions = dayData.slots.map(s => ({
          id: s.id,
          name: `Slot ${s.slot_order}: ${s.label || s.start_time + ' - ' + s.end_time}`,
          order: s.slot_order
        }));

        $('#add_schedule_slot_ids').empty().prop('disabled', false);
        $('#add_schedule_slot_ids').append('<option value="">Select Slots</option>');
        slotOptions.forEach(slot => {
          $('#add_schedule_slot_ids').append(`<option value="${slot.id}" data-order="${slot.order}">${slot.name}</option>`);
        });
      } else {
        $('#add_schedule_slot_ids').prop('disabled', true).empty().append('<option value="">No slots available</option>');
      }
    });

    $('#addScheduleForm').on('submit', async function(e) {
      e.preventDefault();
      
      const data = {
        schedule_template_id: $('#add_schedule_template_id').val(),
        activity_type: $('#add_activity_type').val(),
        group_numbers: [$('#add_schedule_group_numbers').val()],
        location: $('#add_location').val(),
        schedule_day_id: $('#add_schedule_day_id').val(),
        schedule_slot_ids: $('#add_schedule_slot_ids').val(),
        min_capacity: $('#add_min_capacity').val(),
        max_capacity: $('#add_max_capacity').val()
      };

      const slotIds = $('#add_schedule_slot_ids').val();
      if (slotIds && slotIds.length > 0) {
        const slots = $('#add_schedule_slot_ids option:selected').map(function() {
          return parseInt($(this).attr('data-order'));
        }).get().sort((a, b) => a - b);

        for (let i = 1; i < slots.length; i++) {
          if (slots[i] !== slots[i-1] + 1) {
            Utils.showError('Selected slots must be consecutive');
            return;
          }
        }
      }

      try {
        const response = await $.ajax({
          url: ROUTES.availableCourse.schedule.store,
          method: 'POST',
          data: data,
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        Utils.showSuccess(response.message);
        $('#addScheduleModal').modal('hide');
        reloadTable(scheduleTable, '#scheduleTable');
      } catch (error) {
        Utils.handleAjaxError(error);
      }
    });
  }

  // ========== SCHEDULE MANAGEMENT - EDIT ==========
  function initializeEditSchedule() {
    $(document).on('click', '.editScheduleBtn', async function() {
      const id = $(this).data('id');
      
      try {
        const response = await $.getJSON(ROUTES.availableCourse.schedule.show.replace(':id', id));
        const data = response.data;
        
        $('#edit_schedule_id').val(id);
        $('#edit_available_course_schedule_id').val(data.id);
        $('#edit_activity_type').val(data.activity_type);
        $('#edit_schedule_group_number').val(data.group_number || data.group);
        $('#edit_location').val(data.location);
        $('#edit_min_capacity').val(data.min_capacity);
        $('#edit_max_capacity').val(data.max_capacity);
        
        $('#editScheduleModal').modal('show');
        
        setTimeout(async () => {
          if (data.schedule_template_id) {
            $('#edit_schedule_template_id').val(data.schedule_template_id).trigger('change');
            
            setTimeout(async () => {
              if (data.day_of_week) {
                $('#edit_schedule_day_id').val(data.day_of_week).trigger('change');
                
                setTimeout(() => {
                  if (data.slot_ids && data.slot_ids.length > 0) {
                    $('#edit_schedule_slot_ids').val(data.slot_ids).trigger('change');
                  }
                  
                  setTimeout(() => {
                    Utils.initSelect2($('.select2-modal'), { dropdownParent: $('#editScheduleModal') });
                    Utils.initSelect2($('#edit_schedule_slot_ids'), { 
                      closeOnSelect: false, 
                      dropdownParent: $('#editScheduleModal') 
                    });
                    $('#edit_activity_type').trigger('change');
                    $('#edit_schedule_group_number').trigger('change');
                  }, 100);
                }, 800);
              }
            }, 800);
          } else {
            if (data.slot_ids && data.slot_ids.length > 0) {
              setTimeout(() => {
                $('#edit_schedule_slot_ids').val(data.slot_ids).trigger('change');
                
                setTimeout(() => {
                  Utils.initSelect2($('.select2-modal'), { dropdownParent: $('#editScheduleModal') });
                  Utils.initSelect2($('#edit_schedule_slot_ids'), { 
                    closeOnSelect: false, 
                    dropdownParent: $('#editScheduleModal') 
                  });
                  $('#edit_activity_type').trigger('change');
                  $('#edit_schedule_group_number').trigger('change');
                }, 100);
              }, 300);
            } else {
              setTimeout(() => {
                Utils.initSelect2($('.select2-modal'), { dropdownParent: $('#editScheduleModal') });
                Utils.initSelect2($('#edit_schedule_slot_ids'), { 
                  closeOnSelect: false, 
                  dropdownParent: $('#editScheduleModal') 
                });
                $('#edit_activity_type').trigger('change');
                $('#edit_schedule_group_number').trigger('change');
              }, 300);
            }
          }
        }, 200);
      } catch (error) {
        console.error('Error loading schedule data:', error);
        Utils.showError('Failed to load schedule data: ' + (error.responseJSON?.message || error.message));
      }
    });

    $(document).on('change', '#edit_schedule_template_id', async function() {
      const scheduleId = $(this).val();
      
      if (!scheduleId) {
        $('#edit_schedule_day_id').prop('disabled', true).empty().append('<option value="">Select Day</option>');
        $('#edit_schedule_slot_ids').prop('disabled', true).empty().append('<option value="">Select Slots</option>');
        return;
      }

      try {
        const response = await $.getJSON(ROUTES.scheduleDaysSlots.get.replace(':id', scheduleId));
        scheduleDaysData[scheduleId] = response.data;
        
        const dayOptions = response.data.map(d => ({
          id: d.day_of_week,
          name: d.day_of_week.charAt(0).toUpperCase() + d.day_of_week.slice(1)
        }));

        $('#edit_schedule_day_id').empty().prop('disabled', false);
        Utils.populateSelect($('#edit_schedule_day_id'), dayOptions, { textField: 'name' }, true);
        
        $('#edit_schedule_slot_ids').prop('disabled', true).empty().append('<option value="">Select Slots</option>');
      } catch (error) {
        Utils.showError('Failed to load schedule days');
      }
    });

    $(document).on('change', '#edit_schedule_day_id', function() {
      const scheduleId = $('#edit_schedule_template_id').val();
      const day = $(this).val();
      
      if (!day || !scheduleDaysData[scheduleId]) {
        $('#edit_schedule_slot_ids').prop('disabled', true).empty().append('<option value="">Select Slots</option>');
        return;
      }

      const dayData = scheduleDaysData[scheduleId].find(d => d.day_of_week === day);
      
      if (dayData && dayData.slots) {
        const slotOptions = dayData.slots.map(s => ({
          id: s.id,
          name: `Slot ${s.slot_order}: ${s.label || s.start_time + ' - ' + s.end_time}`,
          order: s.slot_order
        }));

        $('#edit_schedule_slot_ids').empty().prop('disabled', false);
        $('#edit_schedule_slot_ids').append('<option value="">Select Slots</option>');
        slotOptions.forEach(slot => {
          $('#edit_schedule_slot_ids').append(`<option value="${slot.id}" data-order="${slot.order}">${slot.name}</option>`);
        });
      } else {
        $('#edit_schedule_slot_ids').prop('disabled', true).empty().append('<option value="">No slots available</option>');
      }
    });

    $('#editScheduleForm').on('submit', async function(e) {
      e.preventDefault();
      
      const id = $('#edit_schedule_id').val();
      const data = {
        schedule_template_id: $('#edit_schedule_template_id').val(),
        activity_type: $('#edit_activity_type').val(),
        group_number: $('#edit_schedule_group_number').val(),
        location: $('#edit_location').val(),
        schedule_day_id: $('#edit_schedule_day_id').val(),
        schedule_slot_ids: $('#edit_schedule_slot_ids').val(),
        min_capacity: $('#edit_min_capacity').val(),
        max_capacity: $('#edit_max_capacity').val()
      };

      const slotIds = $('#edit_schedule_slot_ids').val();
      if (slotIds && slotIds.length > 0) {
        const slots = $('#edit_schedule_slot_ids option:selected').map(function() {
          return parseInt($(this).attr('data-order'));
        }).get().sort((a, b) => a - b);

        for (let i = 1; i < slots.length; i++) {
          if (slots[i] !== slots[i-1] + 1) {
            Utils.showError('Selected slots must be consecutive');
            return;
          }
        }
      }

      try {
        const response = await $.ajax({
          url: ROUTES.availableCourse.schedule.update.replace(':id', id),
          method: 'PUT',
          data: data,
          headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        Utils.showSuccess(response.message);
        $('#editScheduleModal').modal('hide');
        reloadTable(scheduleTable, '#scheduleTable');
      } catch (error) {
        Utils.handleAjaxError(error);
      }
    });
  }

  // ========== SCHEDULE MANAGEMENT - DELETE ==========
  function initializeDeleteSchedule() {
    $(document).on('click', '.deleteScheduleBtn', function() {
      const id = $(this).data('id');
      
      Swal.fire({
        title: 'Are you sure?',
        text: "This schedule will be deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            await $.ajax({
              url: ROUTES.availableCourse.schedule.delete.replace(':id', id),
              method: 'DELETE',
              headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });
            
            Utils.showSuccess('Schedule deleted successfully');
            reloadTable(scheduleTable, '#scheduleTable');
          } catch (error) {
            Utils.handleAjaxError(error);
          }
        }
      });
    });
  }

  // ========== INITIALIZATION ==========
  $(document).ready(async function() {
    initializeTableReferences();
    initializeEligibility();
    initializeAddSchedule();
    initializeEditSchedule();
    initializeDeleteSchedule();
    
    await loadDropdownData();
    Utils.hidePageLoader();
  });

})();
</script>
@endpush