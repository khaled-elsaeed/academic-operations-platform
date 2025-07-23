
@extends('layouts.home')

@section('title', 'Admin Schedule Slots | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    

    {{-- ===== STATISTICS CARDS ===== --}}
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

    {{-- ===== PAGE HEADER ===== --}}
    <x-ui.page-header 
        title="Schedule Slots"
        description="Manage all schedule slots and export in bulk using the options on the right."
        icon="bx bx-time-five"
    >
    </x-ui.page-header>


    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable
        :headers="['Schedule', 'Day', 'Start Time', 'End Time', 'Duration', 'Order', 'Active', 'Action']"
        :columns="[
            ['data' => 'schedule_title', 'name' => 'schedule_title'],
            ['data' => 'day_of_week', 'name' => 'day_of_week'],
            ['data' => 'start_time', 'name' => 'start_time'],
            ['data' => 'end_time', 'name' => 'end_time'],
            ['data' => 'duration_minutes', 'name' => 'duration_minutes'],
            ['data' => 'slot_order', 'name' => 'slot_order'],
            ['data' => 'is_active', 'name' => 'is_active'],
            ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
        ]"
        :ajax-url="route('schedule-slots.datatable')"
        table-id="schedule-slots-table"
    />


    {{-- ===== MODALS SECTION ===== --}}
    {{-- View Slot Modal --}}
    <x-ui.modal 
        id="viewSlotModal"
        title="Slot Details"
        size="lg"
        :scrollable="true"
        class="view-slot-modal"
    >
        <x-slot name="slot">
            <div id="slotDetailsSection">
                <div class="mb-3">
                    <h5 class="mb-2"><i class="bx bx-time-five me-1"></i> <span id="viewSlotScheduleTitle">Schedule</span></h5>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <strong>Day:</strong> <span id="viewSlotDay"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Order:</strong> <span id="viewSlotOrder"></span>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <strong>Start Time:</strong> <span id="viewSlotStart"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>End Time:</strong> <span id="viewSlotEnd"></span>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <strong>Duration:</strong> <span id="viewSlotDuration"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Active:</strong> <span id="viewSlotActive"></span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <strong>Specific Date:</strong>
                        <div id="viewSlotSpecificDate" class="text-muted"></div>
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
 * Schedule Slot Management System JavaScript
 * Handles viewing and deleting schedule slots
 */

// ===========================
// CONSTANTS AND CONFIGURATION
// ===========================

const ROUTES = {
  slots: {
    stats: '{{ route('schedule-slots.stats') }}',
    show: '{{ route('schedule-slots.show', ':id') }}',
    destroy: '{{ route('schedule-slots.destroy', ':id') }}'
  }
};

// ===========================
// UTILITY FUNCTIONS
// ===========================

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

// ===========================
// API SERVICE LAYER
// ===========================

const ApiService = {
  request(options) {
    return $.ajax(options);
  },

  fetchSlotStats() {
    return this.request({
      url: ROUTES.slots.stats,
      method: 'GET'
    });
  },

  fetchSlot(id){
    return this.request({
      url: Utils.replaceRouteId(ROUTES.slots.show, id),
      method: 'GET'
    });
  },

  deleteSlot(id) {
    return this.request({
      url: Utils.replaceRouteId(ROUTES.slots.destroy, id),
      method: 'DELETE'
    });
  },
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================

const StatsManager = {
  loadSlotStats() {
    Utils.toggleLoadingState('schedule-slots', true);
    ApiService.fetchSlotStats()
      .done((response) => {
        const data = response.data;
        $('#schedule-slots-value').text(data.total.count ?? '--');
        $('#schedule-slots-last-updated').text(data.total.lastUpdateTime ?? '--');
        Utils.toggleLoadingState('schedule-slots', false);
      })
      .fail(() => {
        $('#schedule-slots-value').text('N/A');
        $('#schedule-slots-last-updated').text('N/A');
        Utils.toggleLoadingState('schedule-slots', false);
        Utils.showError('Failed to load slot statistics');
      });
  }
};

// ===========================
// SLOT VIEW & DELETE OPERATIONS
// ===========================

const SlotManager = {
  handleViewSlot() {
    $(document).on('click', '.viewSlotBtn', function () {
      const slotId = $(this).data('id');
      SlotManager.clearSlotModal();
      $('#viewSlotModal').modal('show');

      ApiService.fetchSlot(slotId)
        .done((response) => {
          // The API returns { success, message, data, code }
          const slot = response.data;
          SlotManager.populateSlotModal(slot);
        })
        .fail(() => {
          SlotManager.showSlotModalError();
        });
    });
  },

  clearSlotModal() {
    $('#viewSlotScheduleTitle').text('');
    $('#viewSlotDay').text('');
    $('#viewSlotOrder').text('');
    $('#viewSlotStart').text('');
    $('#viewSlotEnd').text('');
    $('#viewSlotDuration').text('');
    $('#viewSlotActive').text('');
    $('#viewSlotSpecificDate').text('');
  },

  showSlotModalError() {
    $('#viewSlotScheduleTitle').text('Error loading slot');
    $('#viewSlotDay').text('');
    $('#viewSlotOrder').text('');
    $('#viewSlotStart').text('');
    $('#viewSlotEnd').text('');
    $('#viewSlotDuration').text('');
    $('#viewSlotActive').text('');
    $('#viewSlotSpecificDate').text('Failed to load slot details.');
  },

  populateSlotModal(slot) {
    // Schedule Title
    $('#viewSlotScheduleTitle').text(slot.schedule_title || 'N/A');

    // Day of week
    $('#viewSlotDay').text(slot.day_of_week ? slot.day_of_week : '-');

    // Order
    $('#viewSlotOrder').text(slot.slot_order || '');

    // Start/End Times
    $('#viewSlotStart').text(slot.start_time ? slot.start_time : '--');
    $('#viewSlotEnd').text(slot.end_time ? slot.end_time : '--');

    // Duration
    $('#viewSlotDuration').text(slot.duration_minutes ? `${slot.duration_minutes} min` : '--');

    // Active
    $('#viewSlotActive').text(slot.is_active ? 'Yes' : 'No');

    // Specific Date
    $('#viewSlotSpecificDate').text(slot.specific_date ? slot.specific_date : '');
  },

  handleDeleteSlot() {
    $(document).on('click', '.deleteSlotBtn', function () {
      const slotId = $(this).data('id');
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
          ApiService.deleteSlot(slotId)
            .done(() => {
              $('#schedule-slots-table').DataTable().ajax.reload(null, false);
              Utils.showSuccess('Slot has been deleted.');
              StatsManager.loadSlotStats();
            })
            .fail(() => {
              Utils.showError('Failed to delete slot.');
            });
        }
      });
    });
  }
};


// ===========================
// MAIN APPLICATION
// ===========================

const SlotManagementApp = {
  init() {
    StatsManager.loadSlotStats();
    SlotManager.handleDeleteSlot();
    SlotManager.handleViewSlot();
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
