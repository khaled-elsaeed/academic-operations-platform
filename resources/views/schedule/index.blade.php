
@extends('layouts.home')

@section('title', 'Admin Schedules | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- Statistics Cards --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 
                id="schedules"
                label="Total Schedules"
                color="primary"
                icon="bx bx-calendar"
            />
        </div>
    </div>

    {{-- Page Header & Action Buttons --}}
    <x-ui.page-header 
        title="Schedules"
        description="Manage all schedules and export in bulk using the options on the right."
        icon="bx bx-calendar"
    >

        {{-- Add Schedule Button --}}
        <a href="{{ route('schedules.create') }}" class="btn btn-primary me-2">
            <i class="bx bx-plus"></i> Add Schedule
        </a>
        
    </x-ui.page-header>


    {{-- Data Table --}}
    <x-ui.datatable
        :headers="[
            'Title',
            'Type',
            'Code',
            'Start Date',
            'End Date',
            'Term',
            'Slots',
            'Status',
            'Action'
        ]"
        :columns="[
            ['data' => 'title', 'name' => 'title'],
            ['data' => 'type', 'name' => 'type'],
            ['data' => 'code', 'name' => 'code'],
            ['data' => 'formatted_day_starts_at', 'name' => 'formatted_day_starts_at'],
            ['data' => 'formatted_day_ends_at', 'name' => 'formatted_day_ends_at'],
            ['data' => 'term', 'name' => 'term'],
            ['data' => 'slots_count', 'name' => 'slots_count'],
            ['data' => 'status', 'name' => 'status'],
            ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false]
        ]"
        :ajax-url="route('schedules.datatable')"
        table-id="schedules-table"
    />


    {{-- ===== MODALS SECTION ===== --}}
    {{-- View Schedule & Slots Modal --}}
    <x-ui.modal 
        id="viewScheduleModal"
        title="Schedule Details"
        size="lg"
        :scrollable="true"
        class="view-schedule-modal"
    >
        <x-slot name="slot">
            <div id="scheduleDetailsSection">
                <div class="mb-3">
                    <h5 class="mb-2"><i class="bx bx-calendar me-1"></i> <span id="viewScheduleTitle">Schedule Title</span></h5>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <strong>Type:</strong> <span id="viewScheduleType"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong> <span id="viewScheduleStatus"></span>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <strong>Start Date:</strong> <span id="viewScheduleStart"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>End Date:</strong> <span id="viewScheduleEnd"></span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <strong>Description:</strong>
                        <div id="viewScheduleDescription" class="text-muted"></div>
                    </div>
                </div>
                <hr>
                <div>
                    <h6 class="mb-3"><i class="bx bx-time-five me-1"></i> Created Slots</h6>
                    <div id="scheduleSlotsSection">
                        <table class="table table-bordered table-sm mb-0" id="scheduleSlotsTable" style="display:none;">
                            <thead>
                                <tr>
                                    <th>Slot #</th>
                                    <th>Day</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Duration</th>
                                    <th>Order</th>
                                    <th>Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Populated dynamically --}}
                            </tbody>
                        </table>
                        <div class="text-center text-muted py-3" id="noSlotsMsg" style="display:none;">
                            No slots found for this schedule.
                        </div>
                    </div>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Close
            </button>
        </x-slot>
    </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
/**
 * Constants and Configuration
 */
const ROUTES = {
    schedules: {
        stats: '{{ route('schedules.stats') }}',
        show: '{{ route('schedules.show', ':id') }}',
        destroy: '{{ route('schedules.destroy', ':id') }}'
    }
};

/**
 * Utility Functions
 */

const Utils = {
    showSuccess(message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: message,
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true
        });
    },

    showError(message) {
        Swal.fire({
            title: 'Error',
            html: message,
            icon: 'error'
        });
    },

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

  replaceRouteId(route, id) {
    return route.replace(':id', id);
  },

};

/**
 * API Service Layer
 */
const ApiService = {
    request(options) {
        return $.ajax(options);
    },

    fetchScheduleStats() {
        return this.request({
            url: ROUTES.schedules.stats,
            method: 'GET'
        });
    },

  fetchSchedule(id){
    return this.request({
      url: Utils.replaceRouteId(ROUTES.schedules.show, id),
      method: 'GET'
    });
  },

  deleteSchedule(id) {
    return this.request({
      url: Utils.replaceRouteId(ROUTES.schedules.destroy, id),
      method: 'DELETE'
    });
  },
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================

const StatsManager = {
  loadScheduleStats() {
    Utils.toggleLoadingState('schedules', true);
    ApiService.fetchScheduleStats()
      .done((response) => {
        const data = response.data;
        $('#schedules-value').text(data.total.count ?? '--');
        $('#schedules-last-updated').text(data.total.lastUpdateTime ?? '--');
        Utils.toggleLoadingState('schedules', false);
      })
      .fail(() => {
        $('#schedules-value').text('N/A');
        $('#schedules-last-updated').text('N/A');
        Utils.toggleLoadingState('schedules', false);
        Utils.showError('Failed to load schedule statistics');
      });
  }
};

/**
 * Schedule CRUD & View Operations
 */
const ScheduleManager = {
    handleViewSchedule() {
        $(document).on('click', '.viewScheduleBtn', function() {
            const scheduleId = $(this).data('id');
            ScheduleManager.clearScheduleModal();
            $('#viewScheduleModal').modal('show');

      ApiService.fetchSchedule(scheduleId)
        .done((response) => {
          const schedule = response.data;
          ScheduleManager.populateScheduleModal(schedule);
        })
        .fail(() => {
          ScheduleManager.showScheduleModalError();
        });
    });
  },

  clearScheduleModal() {
    $('#viewScheduleTitle').text('');
    $('#viewScheduleType').text('');
    $('#viewScheduleStatus').text('');
    $('#viewScheduleTerm').text('');
    $('#viewScheduleStart').text('');
    $('#viewScheduleEnd').text('');
    $('#viewScheduleDescription').text('');
    $('#scheduleSlotsTable').hide();
    $('#noSlotsMsg').hide();
  },

  showScheduleModalError() {
    $('#viewScheduleTitle').text('Error loading schedule');
    $('#viewScheduleType').text('');
    $('#viewScheduleStatus').text('');
    $('#viewScheduleTerm').text('');
    $('#viewScheduleStart').text('');
    $('#viewScheduleEnd').text('');
    $('#viewScheduleDescription').text('');
    $('#scheduleSlotsTable').hide();
    $('#noSlotsMsg').show().text('Failed to load schedule details.');
  },

  populateScheduleModal(schedule) {
    $('#viewScheduleTitle').text(schedule.title || 'N/A');

    let scheduleType = 'N/A';
    scheduleType = schedule.schedule_type.name;
    $('#viewScheduleType').text(scheduleType);

    $('#viewScheduleStatus').text(schedule.status ? schedule.status : 'N/A');

    let termName = 'N/A';
    if (schedule.term && schedule.term.name) {
      termName = schedule.term.name;
    }
    $('#viewScheduleTerm').text(termName);

    $('#viewScheduleStart').text(schedule.formatted_start_date ? schedule.formatted_start_date : '--');
    $('#viewScheduleEnd').text(schedule.formatted_end_date ? schedule.formatted_end_date : '--');
    $('#viewScheduleDescription').text(schedule.description || '');
    ScheduleManager.populateSlotsTable(schedule);
  },

  populateSlotsTable(schedule) {
    const slots = Array.isArray(schedule.slots) ? schedule.slots : [];
    const $tbody = $('#scheduleSlotsTable tbody');
    $tbody.empty();

    if (slots.length === 0) {
      $('#scheduleSlotsTable').hide();
      $('#noSlotsMsg').show().text('No slots available.');
      return;
    }

    let slotIndex = 1;
    slots.forEach(function(slot) {
      let dayDisplay = slot.day_of_week ? slot.day_of_week : '-';
      const startTime = slot.formatted_start_time ? slot.formatted_start_time : '--';
      const endTime = slot.formatted_end_time ? slot.formatted_end_time : '--';
      const duration = slot.duration_minutes ? `${slot.duration_minutes} min` : '--';
      const slotOrder = slot.slot_order || '';
      let specificDate = slot.formatted_specific_date ? `<br><small class="text-muted">${slot.formatted_specific_date}</small>` : '';
      let isActive = slot.is_active ? 'Yes' : 'No';

      $tbody.append(
        `<tr>
          <td>${slotIndex++}</td>
          <td>${dayDisplay}</td>
          <td>${startTime}</td>
          <td>${endTime}</td>
          <td>${duration}</td>
          <td>${slotOrder}</td>
          <td>${isActive}${specificDate}</td>
        </tr>`
      );
    });

    $('#scheduleSlotsTable').show();
    $('#noSlotsMsg').hide();
  },

  handleDeleteSchedule() {
    $(document).on('click', '.deleteScheduleBtn', function () {
      const scheduleId = $(this).data('id');
      Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          ApiService.deleteSchedule(scheduleId)
            .done(() => {
              $('#schedules-table').DataTable().ajax.reload(null, false);
              Utils.showSuccess('Schedule has been deleted.');
              StatsManager.loadScheduleStats();
            })
            .fail(() => {
              Utils.showError('Failed to delete schedule.');
            });
        }
      });
    });
  }
};


/**
 * Main Application
 */
const ScheduleManagementApp = {
    init() {
        StatsManager.loadScheduleStats();
        ScheduleManager.handleDeleteSchedule();
        ScheduleManager.handleViewSchedule();
    }
};

/**
 * Document Ready
 */

$(document).ready(() => {
  ScheduleManagementApp.init();
});
</script>
@endpush
