@extends('layouts.home')

@section('title', 'Faculty Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    
  {{-- ===== STATISTICS CARDS ===== --}}
  <div class="row g-4 mb-4">
      <div class="col-sm-6 col-xl-4">
          <x-ui.card.stat2 
              id="faculties"
              label="Total Faculties"
              color="primary"
              icon="bx bx-building"
          />
      </div>
      <div class="col-sm-6 col-xl-4">
          <x-ui.card.stat2 
              id="with-programs"
              label="Faculties with Programs"
              color="success"
              icon="bx bx-check-circle"
          />
      </div>
      <div class="col-sm-6 col-xl-4">
          <x-ui.card.stat2 
              id="without-programs"
              label="Faculties without Programs"
              color="warning"
              icon="bx bx-x-circle"
          />
      </div>
  </div>

  {{-- ===== PAGE HEADER & ACTION BUTTONS ===== --}}
  <x-ui.page-header 
      title="Faculties"
      description="Manage all faculty records and add new faculties using the options on the right."
      icon="bx bx-building"
  >
      @can('faculty.create')
      <button class="btn btn-primary mx-2" id="addFacultyBtn" type="button" data-bs-toggle="modal" data-bs-target="#facultyModal">
          <i class="bx bx-plus me-1"></i> Add Faculty
      </button>
      @endcan
      <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#facultySearchCollapse" aria-expanded="false" aria-controls="facultySearchCollapse">
          <i class="bx bx-filter-alt me-1"></i> Search
      </button>
  </x-ui.page-header>

  {{-- ===== ADVANCED SEARCH SECTION ===== --}}
  <x-ui.advanced-search 
      title="Advanced Search" 
      formId="advancedFacultySearch" 
      collapseId="facultySearchCollapse"
      :collapsed="false"
  >
      <div class="col-md-4">
          <label for="search_name" class="form-label">Faculty Name:</label>
          <input type="text" class="form-control" id="search_name" placeholder="Faculty Name">
      </div>
      <button class="btn btn-outline-secondary" id="clearFiltersBtn" type="button">
          <i class="bx bx-x"></i> Clear Filters
      </button>
  </x-ui.advanced-search>

  {{-- ===== DATA TABLE ===== --}}
  <x-ui.datatable
      :headers="['Name', 'Programs Count', 'Action']"
      :columns="[
          ['data' => 'name', 'name' => 'name'],
          ['data' => 'programs_count', 'name' => 'programs_count'],
          ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
      ]"
      :ajax-url="route('faculties.datatable')"
      table-id="faculties-table"
      :filter-fields="['search_name']"
  />

  {{-- ===== MODALS SECTION ===== --}}
  
  {{-- Add/Edit Faculty Modal --}}
  <x-ui.modal 
      id="facultyModal"
      title="Add/Edit Faculty"
      size="md"
      :scrollable="false"
      class="faculty-modal"
  >
      <x-slot name="slot">
          <form id="facultyForm">
              <input type="hidden" id="faculty_id" name="faculty_id">
              <div class="row">
                  <div class="col-md-12 mb-3">
                      <label for="name" class="form-label">Faculty Name</label>
                      <input type="text" class="form-control" id="name" name="name" required>
                  </div>
              </div>
          </form>
      </x-slot>
      <x-slot name="footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              Close
          </button>
          <button type="submit" class="btn btn-primary" id="saveFacultyBtn" form="facultyForm">
              Save
          </button>
      </x-slot>
  </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
// ===========================
// CONSTANTS AND CONFIGURATION
// ===========================
const ROUTES = {
  faculties: {
    stats: '{{ route('faculties.stats') }}',
    store: '{{ route('faculties.store') }}',
    show: '{{ route('faculties.show', ':id') }}',
    destroy: '{{ route('faculties.destroy', ':id') }}',
    datatable: '{{ route('faculties.datatable') }}'
  }
};

const SELECTORS = {
  facultyForm: '#facultyForm',
  facultyModal: '#facultyModal',
  addFacultyBtn: '#addFacultyBtn',
  saveFacultyBtn: '#saveFacultyBtn',
  facultiesTable: '#faculties-table',
  clearFiltersBtn: '#clearFiltersBtn',
  searchName: '#search_name',
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
// API SERVICE LAYER
// ===========================
const ApiService = {
  request(options) {
    return $.ajax(options);
  },
  fetchFacultyStats() {
    return this.request({ url: ROUTES.faculties.stats, method: 'GET' });
  },
  fetchFaculty(id) {
    return this.request({ url: Utils.replaceRouteId(ROUTES.faculties.show, id), method: 'GET' });
  },
  saveFaculty(data, id = null) {
    const url = id ? Utils.replaceRouteId(ROUTES.faculties.show, id) : ROUTES.faculties.store;
    const method = id ? 'PUT' : 'POST';
    return this.request({ url, method, data });
  },
  deleteFaculty(id) {
    return this.request({ url: Utils.replaceRouteId(ROUTES.faculties.destroy, id), method: 'DELETE' });
  }
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================
const StatsManager = {
  loadFacultyStats() {
    Utils.toggleLoadingState('faculties', true);
    Utils.toggleLoadingState('with-programs', true);
    Utils.toggleLoadingState('without-programs', true);
    ApiService.fetchFacultyStats()
      .done((response) => {
        const data = response.data;
        $('#faculties-value').text(data.total.total ?? '--');
        $('#faculties-last-updated').text(data.total.lastUpdateTime ?? '--');
        $('#with-programs-value').text(data.withPrograms.total ?? '--');
        $('#with-programs-last-updated').text(data.withPrograms.lastUpdateTime ?? '--');
        $('#without-programs-value').text(data.withoutPrograms.total ?? '--');
        $('#without-programs-last-updated').text(data.withoutPrograms.lastUpdateTime ?? '--');
        Utils.toggleLoadingState('faculties', false);
        Utils.toggleLoadingState('with-programs', false);
        Utils.toggleLoadingState('without-programs', false);
      })
      .fail(() => {
        $('#faculties-value, #with-programs-value, #without-programs-value').text('N/A');
        $('#faculties-last-updated, #with-programs-last-updated, #without-programs-last-updated').text('N/A');
        Utils.toggleLoadingState('faculties', false);
        Utils.toggleLoadingState('with-programs', false);
        Utils.toggleLoadingState('without-programs', false);
        Utils.showError('Failed to load faculty statistics');
      });
  }
};

// ===========================
// FACULTY CRUD OPERATIONS
// ===========================
const FacultyManager = {
  handleAddFaculty() {
    $(SELECTORS.addFacultyBtn).on('click', () => {
      $(SELECTORS.facultyForm)[0].reset();
      $('#faculty_id').val('');
      $(SELECTORS.facultyModal + ' .modal-title').text('Add Faculty');
      $(SELECTORS.saveFacultyBtn).text('Save');
      $(SELECTORS.facultyModal).modal('show');
    });
  },
  handleFacultyFormSubmit() {
    $(SELECTORS.facultyForm).on('submit', function (e) {
      e.preventDefault();
      const facultyId = $('#faculty_id').val();
      const formData = $(SELECTORS.facultyForm).serialize();
      const $submitBtn = $(SELECTORS.saveFacultyBtn);
      const originalText = $submitBtn.text();
      $submitBtn.prop('disabled', true).text('Saving...');
      ApiService.saveFaculty(formData, facultyId || null)
        .done(() => {
          $(SELECTORS.facultyModal).modal('hide');
          $(SELECTORS.facultiesTable).DataTable().ajax.reload(null, false);
          Utils.showSuccess('Faculty has been saved successfully.');
          StatsManager.loadFacultyStats();
        })
        .fail((xhr) => {
          $(SELECTORS.facultyModal).modal('hide');
          const message = xhr.responseJSON?.message || 'An error occurred. Please check your input.';
          Utils.showError(message);
        })
        .always(() => {
          $submitBtn.prop('disabled', false).text(originalText);
        });
    });
  },
  handleEditFaculty() {
    $(document).on('click', '.editFacultyBtn', function () {
      const facultyId = $(this).data('id');
      ApiService.fetchFaculty(facultyId)
        .done((response) => {
          const fac = response.data;
          $('#faculty_id').val(fac.id);
          $('#name').val(fac.name);
          $(SELECTORS.facultyModal + ' .modal-title').text('Edit Faculty');
          $(SELECTORS.saveFacultyBtn).text('Update');
          $(SELECTORS.facultyModal).modal('show');
        })
        .fail(() => {
          Utils.showError('Failed to fetch faculty data.');
        });
    });
  },
  handleDeleteFaculty() {
    $(document).on('click', '.deleteFacultyBtn', function () {
      const facultyId = $(this).data('id');
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
          ApiService.deleteFaculty(facultyId)
            .done(() => {
              $(SELECTORS.facultiesTable).DataTable().ajax.reload(null, false);
              Utils.showSuccess('Faculty has been deleted.');
              StatsManager.loadFacultyStats();
            })
            .fail((xhr) => {
              const message = xhr.responseJSON?.message || 'Failed to delete faculty.';
              Utils.showError(message);
            });
        }
      });
    });
  }
};

// ===========================
// SEARCH FUNCTIONALITY
// ===========================
const SearchManager = {
  initializeAdvancedSearch() {
    this.bindSearchEvents();
  },
  bindSearchEvents() {
    $(SELECTORS.clearFiltersBtn).on('click', () => {
      $(SELECTORS.searchName).val('');
      if (window.facultiesTableTable) {
        window.facultiesTableTable.ajax.reload();
      }
    });
  }
};


// ===========================
// MAIN APPLICATION
// ===========================
const FacultyManagementApp = {
  init() {
    StatsManager.loadFacultyStats();
    FacultyManager.handleAddFaculty();
    FacultyManager.handleFacultyFormSubmit();
    FacultyManager.handleEditFaculty();
    FacultyManager.handleDeleteFaculty();
    SearchManager.initializeAdvancedSearch();
    Utils.hidePageLoader();

  }
};

$(document).ready(() => {
  FacultyManagementApp.init();
});
</script>
@endpush 