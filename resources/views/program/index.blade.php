@extends('layouts.home')

@section('title', 'Program Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    
    {{-- ===== STATISTICS CARDS ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 
                id="programs"
                label="Total Programs"
                color="primary"
                icon="bx bx-book-open"
            />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 
                id="with-students"
                label="Programs with Students"
                color="success"
                icon="bx bx-user-check"
            />
        </div>
        <div class="col-sm-6 col-xl-4">
            <x-ui.card.stat2 
                id="without-students"
                label="Programs without Students"
                color="warning"
                icon="bx bx-user-x"
            />
        </div>
    </div>

    {{-- ===== PAGE HEADER & ACTION BUTTONS ===== --}}
    <x-ui.page-header 
        title="Programs"
        description="Manage all program records and add new programs using the options on the right."
        icon="bx bx-book-open"
    >
        <button class="btn btn-primary mx-2" id="addProgramBtn" type="button" data-bs-toggle="modal" data-bs-target="#programModal">
            <i class="bx bx-plus me-1"></i> Add Program
        </button>
        <button class="btn btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#programSearchCollapse" aria-expanded="false" aria-controls="programSearchCollapse">
            <i class="bx bx-filter-alt me-1"></i> Search
        </button>
    </x-ui.page-header>

    {{-- ===== ADVANCED SEARCH SECTION ===== --}}
    <x-ui.advanced-search 
        title="Advanced Search" 
        formId="advancedProgramSearch" 
        collapseId="programSearchCollapse"
        :collapsed="false"
    >
        <div class="col-md-4">
            <label for="search_name" class="form-label">Program Name:</label>
            <input type="text" class="form-control" id="search_name" placeholder="Program Name">
        </div>
        <div class="col-md-4">
            <label for="search_code" class="form-label">Program Code:</label>
            <input type="text" class="form-control" id="search_code" placeholder="Program Code">
        </div>
        <div class="col-md-4">
            <label for="search_faculty" class="form-label">Faculty:</label>
            <select class="form-control" id="search_faculty">
                <option value="">Select Faculty</option>
                <!-- Options loaded via AJAX -->
            </select>
        </div>
        <button class="btn btn-outline-secondary" id="clearFiltersBtn" type="button">
            <i class="bx bx-x"></i> Clear Filters
        </button>
    </x-ui.advanced-search>

    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable
        :headers="['ID', 'Name', 'Code', 'Faculty', 'Students Count', 'Action']"
        :columns="[
            ['data' => 'id', 'name' => 'id'],
            ['data' => 'name', 'name' => 'name'],
            ['data' => 'code', 'name' => 'code'],
            ['data' => 'faculty_name', 'name' => 'faculty_name'],
            ['data' => 'students_count', 'name' => 'students_count'],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
        ]"
        :ajax-url="route('programs.datatable')"
        table-id="programs-table"
        :filter-fields="['search_name', 'search_code', 'search_faculty']"
    />

    {{-- ===== MODALS SECTION ===== --}}
    {{-- Add/Edit Program Modal --}}
    <x-ui.modal 
        id="programModal"
        title="Add/Edit Program"
        size="lg"
        :scrollable="false"
        class="program-modal"
    >
        <x-slot name="slot">
            <form id="programForm">
                <input type="hidden" id="program_id" name="program_id">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Program Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="code" class="form-label">Program Code</label>
                        <input type="text" class="form-control" id="code" name="code" required>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="faculty_id" class="form-label">Faculty</label>
                        <select class="form-control" id="faculty_id" name="faculty_id" required>
                            <option value="">Select Faculty</option>
                            <!-- Options will be loaded via AJAX -->
                        </select>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Close
            </button>
            <button type="submit" class="btn btn-primary" id="saveProgramBtn" form="programForm">
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
  programs: {
    stats: '{{ route('programs.stats') }}',
    store: '{{ route('programs.store') }}',
    show: '{{ route('programs.show', ':id') }}',
    destroy: '{{ route('programs.destroy', ':id') }}',
    datatable: '{{ route('programs.datatable') }}',
    faculties: '{{ route('programs.faculties') }}'
  }
};

const SELECTORS = {
  programForm: '#programForm',
  programModal: '#programModal',
  addProgramBtn: '#addProgramBtn',
  saveProgramBtn: '#saveProgramBtn',
  programsTable: '#programs-table',
  clearFiltersBtn: '#clearFiltersBtn',
  searchName: '#search_name',
  searchCode: '#search_code',
  searchFaculty: '#search_faculty',
  facultySelect: '#faculty_id',
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
  }
};

// ===========================
// API SERVICE LAYER
// ===========================
const ApiService = {
  request(options) {
    return $.ajax(options);
  },
  fetchProgramStats() {
    return this.request({ url: ROUTES.programs.stats, method: 'GET' });
  },
  fetchFaculties() {
    return this.request({ url: ROUTES.programs.faculties, method: 'GET' });
  },
  fetchProgram(id) {
    return this.request({ url: Utils.replaceRouteId(ROUTES.programs.show, id), method: 'GET' });
  },
  saveProgram(data, id = null) {
    const url = id ? Utils.replaceRouteId(ROUTES.programs.show, id) : ROUTES.programs.store;
    const method = id ? 'PUT' : 'POST';
    return this.request({ url, method, data });
  },
  deleteProgram(id) {
    return this.request({ url: Utils.replaceRouteId(ROUTES.programs.destroy, id), method: 'DELETE' });
  }
};

// ===========================
// DROPDOWN MANAGEMENT
// ===========================
const DropdownManager = {
  loadFaculties(selector = SELECTORS.searchFaculty, selectedId = null) {
    return ApiService.fetchFaculties()
      .done((response) => {
        const faculties = response.data;
        const $select = $(selector);
        $select.empty().append('<option value="">Select Faculty</option>');
        faculties.forEach((faculty) => {
          $select.append($('<option>', { value: faculty.id, text: faculty.name }));
        });
        if (selectedId) {
          $select.val(selectedId);
        }
        $select.trigger('change');
      })
      .fail(() => {
        Utils.showError('Failed to load faculties');
      });
  }
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================
const StatsManager = {
  loadProgramStats() {
    Utils.toggleLoadingState('programs', true);
    Utils.toggleLoadingState('with-students', true);
    Utils.toggleLoadingState('without-students', true);
    ApiService.fetchProgramStats()
      .done((response) => {
        const data = response.data;
        $('#programs-value').text(data.total.total ?? '--');
        $('#programs-last-updated').text(data.total.lastUpdateTime ?? '--');
        $('#with-students-value').text(data.withStudents.total ?? '--');
        $('#with-students-last-updated').text(data.withStudents.lastUpdateTime ?? '--');
        $('#without-students-value').text(data.withoutStudents.total ?? '--');
        $('#without-students-last-updated').text(data.withoutStudents.lastUpdateTime ?? '--');
        Utils.toggleLoadingState('programs', false);
        Utils.toggleLoadingState('with-students', false);
        Utils.toggleLoadingState('without-students', false);
      })
      .fail(() => {
        $('#programs-value, #with-students-value, #without-students-value').text('N/A');
        $('#programs-last-updated, #with-students-last-updated, #without-students-last-updated').text('N/A');
        Utils.toggleLoadingState('programs', false);
        Utils.toggleLoadingState('with-students', false);
        Utils.toggleLoadingState('without-students', false);
        Utils.showError('Failed to load program statistics');
      });
  }
};

// ===========================
// PROGRAM CRUD OPERATIONS
// ===========================
const ProgramManager = {
  handleAddProgram() {
    $(SELECTORS.addProgramBtn).on('click', () => {
      $(SELECTORS.programForm)[0].reset();
      $('#program_id').val('');
      $(SELECTORS.programModal + ' .modal-title').text('Add Program');
      $(SELECTORS.saveProgramBtn).text('Save');
      DropdownManager.loadFaculties(SELECTORS.facultySelect);
      $(SELECTORS.programModal).modal('show');
    });
  },
  handleProgramFormSubmit() {
    $(SELECTORS.programForm).on('submit', function (e) {
      e.preventDefault();
      const programId = $('#program_id').val();
      const formData = $(SELECTORS.programForm).serialize();
      const $submitBtn = $(SELECTORS.saveProgramBtn);
      const originalText = $submitBtn.text();
      $submitBtn.prop('disabled', true).text('Saving...');
      ApiService.saveProgram(formData, programId || null)
        .done(() => {
          $(SELECTORS.programModal).modal('hide');
          $(SELECTORS.programsTable).DataTable().ajax.reload(null, false);
          Utils.showSuccess('Program has been saved successfully.');
          StatsManager.loadProgramStats();
        })
        .fail((xhr) => {
          $(SELECTORS.programModal).modal('hide');
          const message = xhr.responseJSON?.message || 'An error occurred. Please check your input.';
          Utils.showError(message);
        })
        .always(() => {
          $submitBtn.prop('disabled', false).text(originalText);
        });
    });
  },
  handleEditProgram() {
    $(document).on('click', '.editProgramBtn', function () {
      const programId = $(this).data('id');
      ApiService.fetchProgram(programId)
        .done((program) => {
          const prog = program.data ? program.data : program;
          $('#program_id').val(prog.id);
          $('#name').val(prog.name);
          $('#code').val(prog.code);
          DropdownManager.loadFaculties(SELECTORS.facultySelect, prog.faculty_id);
          $(SELECTORS.programModal + ' .modal-title').text('Edit Program');
          $(SELECTORS.saveProgramBtn).text('Update');
          $(SELECTORS.programModal).modal('show');
        })
        .fail(() => {
          Utils.showError('Failed to fetch program data.');
        });
    });
  },
  handleDeleteProgram() {
    $(document).on('click', '.deleteProgramBtn', function () {
      const programId = $(this).data('id');
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
          ApiService.deleteProgram(programId)
            .done(() => {
              $(SELECTORS.programsTable).DataTable().ajax.reload(null, false);
              Utils.showSuccess('Program has been deleted.');
              StatsManager.loadProgramStats();
            })
            .fail((xhr) => {
              const message = xhr.responseJSON?.message || 'Failed to delete program.';
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
    this.initSearchSelect2();
    this.bindSearchEvents();
    DropdownManager.loadFaculties(SELECTORS.searchFaculty);
  },
  initSearchSelect2() {
    $(SELECTORS.searchFaculty).select2({
      theme: 'bootstrap-5',
      placeholder: 'Select Faculty',
      allowClear: true,
      width: '100%',
      dropdownParent: $('#programSearchCollapse')
    });
  },
  bindSearchEvents() {
    $(SELECTORS.clearFiltersBtn).on('click', () => {
      $(`${SELECTORS.searchName}, ${SELECTORS.searchCode}, ${SELECTORS.searchFaculty}`).val('').trigger('change');
      $(SELECTORS.programsTable).DataTable().ajax.reload();
    });
    $(`${SELECTORS.searchName}, ${SELECTORS.searchCode}, ${SELECTORS.searchFaculty}`).on('keyup change', () => {
      $(SELECTORS.programsTable).DataTable().ajax.reload();
    });
  }
};

// ===========================
// SELECT2 INITIALIZATION
// ===========================
const Select2Manager = {
  initProgramModalSelect2() {
    $(SELECTORS.facultySelect).select2({
      theme: 'bootstrap-5',
      placeholder: 'Select Faculty',
      allowClear: true,
      width: '100%',
      dropdownParent: $(SELECTORS.programModal)
    });
  }
};

// ===========================
// MAIN APPLICATION
// ===========================
const ProgramManagementApp = {
  init() {
    StatsManager.loadProgramStats();
    ProgramManager.handleAddProgram();
    ProgramManager.handleProgramFormSubmit();
    ProgramManager.handleEditProgram();
    ProgramManager.handleDeleteProgram();
    Select2Manager.initProgramModalSelect2();
    SearchManager.initializeAdvancedSearch();
  }
};

$(document).ready(() => {
  ProgramManagementApp.init();
});
</script>
@endpush 