@extends('layouts.home')

@section('title', 'Advisor Access Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- ===== STATISTICS CARDS ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 color="primary" icon="bx bx-user-check" label="Total Access Rules" id="total" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 color="success" icon="bx bx-check-circle" label="Active Rules" id="active" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 color="warning" icon="bx bx-x-circle" label="Inactive Rules" id="inactive" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 color="info" icon="bx bx-group" label="Unique Advisors" id="advisors" />
        </div>
    </div>

    {{-- ===== PAGE HEADER & ACTION BUTTONS ===== --}}
    <x-ui.page-header 
        title="Advisor Access"
        description="Manage advisor access permissions to students based on level and program."
        icon="bx bx-user-check"
    >
        <button class="btn btn-primary mx-2" id="addAccessRuleBtn">
            <i class="bx bx-plus me-1"></i> Add Access Rule
        </button>
    </x-ui.page-header>

    {{-- ===== ADVANCED SEARCH SECTION ===== --}}
    <x-ui.advanced-search 
        title="Advanced Search" 
        formId="advancedAdvisorAccessSearch" 
        collapseId="advisorAccessSearchCollapse"
        :collapsed="false"
    >
        <div class="col-md-3">
            <label for="search_advisor" class="form-label">Advisor:</label>
            <input type="text" class="form-control" id="search_advisor" placeholder="Advisor Name or ID">
        </div>
        <div class="col-md-3">
            <label for="search_level" class="form-label">Level:</label>
            <input type="text" class="form-control" id="search_level" placeholder="Level">
        </div>
        <div class="col-md-3">
            <label for="search_program" class="form-label">Program:</label>
            <input type="text" class="form-control" id="search_program" placeholder="Program">
        </div>
        <div class="col-md-3">
            <label for="search_status" class="form-label">Status:</label>
            <select class="form-control" id="search_status">
                <option value="">All</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
        <div class="w-100"></div>
        <button class="btn btn-outline-secondary mt-2 ms-2" id="clearAdvisorAccessFiltersBtn" type="button">
            <i class="bx bx-x"></i> Clear Filters
        </button>
    </x-ui.advanced-search>

    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable
        :headers="['Advisor', 'Level', 'Program', 'Status', 'Created At', 'Actions']"
        :columns="[
            ['data' => 'advisor', 'name' => 'advisor'],
            ['data' => 'level', 'name' => 'level'],
            ['data' => 'program', 'name' => 'program'],
            ['data' => 'status', 'name' => 'status'],
            ['data' => 'created_at', 'name' => 'created_at'],
            ['data' => 'actions', 'name' => 'actions', 'orderable' => false, 'searchable' => false],
        ]"
        :ajax-url="route('academic_advisor_access.datatable')"
        table-id="academic-advisor-access-table"
        :filter-fields="['search_advisor','search_level','search_program','search_status']"
    />
    />

    {{-- ===== MODALS SECTION ===== --}}
    {{-- Add/Edit Access Modal --}}
    <x-ui.modal 
        id="accessModal"
        title="Add/Edit Access Rule"
        size="lg"
        :scrollable="false"
        class="access-modal"
    >
        <x-slot name="slot">
            <form id="accessForm">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="advisor_id" class="form-label">Advisor <span class="text-danger">*</span></label>
                        <select id="advisor_id" name="advisor_id" class="form-select" required>
                            <option value="">Select Advisor</option>
                        </select>
                    </div>
          <div class="col-12 mb-3">
            <div class="card p-2">
              <label class="form-label mb-2">Level & Program Pairs <span class="text-danger">*</span></label>
              <div id="pairsContainer">
                <!-- Pair rows will be injected here -->
              </div>
              <div class="mt-2">
                <button type="button" id="addPairBtn" class="btn btn-sm btn-outline-primary">
                  <i class="bx bx-plus me-1"></i> Add Pair
                </button>
              </div>
              <div class="form-text mt-2">You can add multiple level & program pairs. Use the checkboxes to select "All Levels" or "All Programs" which will override pairs.</div>
            </div>
          </div>
                    <div class="col-md-6 mb-3">
                        <label for="is_active" class="form-label">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary" form="accessForm">Save</button>
        </x-slot>
    </x-ui.modal>

    {{-- View Access Modal --}}
    <x-ui.modal 
        id="viewAccessModal"
        title="Access Rule Details"
        size="md"
        :scrollable="false"
        class="view-access-modal"
    >
        <x-slot name="slot">
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="form-label fw-bold">Advisor:</label>
                    <p id="view-access-advisor" class="mb-0"></p>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label fw-bold">Level:</label>
                    <p id="view-access-level" class="mb-0"></p>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label fw-bold">Program:</label>
                    <p id="view-access-program" class="mb-0"></p>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label fw-bold">Status:</label>
                    <p id="view-access-status" class="mb-0"></p>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label fw-bold">Created At:</label>
                    <p id="view-access-created" class="mb-0"></p>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
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
  advisorAccess: {
    stats: '{{ route('academic_advisor_access.stats') }}',
    allAdvisors: '{{ route('academic_advisor_access.all') }}',
    allLevels: '{{ route('levels.all') }}',
    allPrograms: '{{ route('programs.all') }}',
    show: '{{ route('academic_advisor_access.show', ':id') }}',
    store: '{{ route('academic_advisor_access.store') }}',
    update: '{{ route('academic_advisor_access.update', ':id') }}',
    destroy: '{{ route('academic_advisor_access.destroy', ':id') }}',
    datatable: '{{ route('academic_advisor_access.datatable') }}'
  }
};

const SELECTORS = {
  accessTable: '#academic-advisor-access-table',
  addAccessBtn: '#addAccessBtn',
  accessModal: '#accessModal',
  accessForm: '#accessForm',
  saveAccessBtn: '#saveAccessBtn',
  advisorSelect: '#advisor_id',
  levelSelect: '#level_id',
  programSelect: '#program_id',
  allLevelsCheckbox: '#all_levels',
  allProgramsCheckbox: '#all_programs',
  isActiveSwitch: '#is_active',
  viewAccessModal: '#viewAccessModal',
  viewAccessAdvisor: '#view-access-advisor',
  viewAccessLevel: '#view-access-level',
  viewAccessProgram: '#view-access-program',
  viewAccessStatus: '#view-access-status',
  viewAccessCreated: '#view-access-created',
};

// ===========================
// UTILITY FUNCTIONS
// ===========================
const Utils = {
  showError(message) {
    Swal.fire({
      title: 'Error',
      html: message,
      icon: 'error'
    });
  },
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
  fetchStats() {
    return this.request({ url: ROUTES.advisorAccess.stats, method: 'GET' });
  },
  fetchAdvisors() {
    return this.request({ url: ROUTES.advisorAccess.allAdvisors, method: 'GET' });
  },
  fetchLevels() {
    return this.request({ url: ROUTES.advisorAccess.allLevels, method: 'GET' });
  },
  fetchPrograms() {
    return this.request({ url: ROUTES.advisorAccess.allPrograms, method: 'GET' });
  },
  fetchAccess(id) {
    return this.request({ url: Utils.replaceRouteId(ROUTES.advisorAccess.show, id), method: 'GET' });
  },
  saveAccess(data, id = null) {
    const url = id ? Utils.replaceRouteId(ROUTES.advisorAccess.update, id) : ROUTES.advisorAccess.store;
    const method = id ? 'PUT' : 'POST';
    return this.request({ url, method, data });
  },
  deleteAccess(id) {
    return this.request({ url: Utils.replaceRouteId(ROUTES.advisorAccess.destroy, id), method: 'DELETE' });
  }
};

// ===========================
// DROPDOWN MANAGEMENT
// ===========================
const DropdownManager = {
  loadAdvisors(selectedId = null) {
    return ApiService.fetchAdvisors()
      .done((response) => {
        const select = $(SELECTORS.advisorSelect);
        select.empty().append('<option value="">Select Advisor</option>');
        (response.data || response).forEach((advisor) => {
          select.append($('<option>', { value: advisor.id, text: advisor.name }));
        });
        if (selectedId) select.val(selectedId);
        select.trigger('change');
      })
      .fail(() => {
        Utils.showError('Failed to load advisors');
      });
  },
  loadLevels(selectedId = null) {
    return ApiService.fetchLevels()
      .done((response) => {
        // populate all level selects
        const rows = $('#pairsContainer').find('select[name^="level_id"]');
        rows.each(function() {
          const sel = $(this);
          const current = sel.val();
          sel.empty().append('<option value="">Select Level</option>');
          (response.data || response).forEach((level) => {
            sel.append($('<option>', { value: level.id, text: level.name }));
          });
          if (selectedId) sel.val(selectedId);
          else sel.val(current);
          sel.trigger('change');
        });
      })
      .fail(() => {
        Utils.showError('Failed to load levels');
      });
  },
  loadPrograms(selectedId = null) {
    return ApiService.fetchPrograms()
      .done((response) => {
        const rows = $('#pairsContainer').find('select[name^="program_id"]');
        rows.each(function() {
          const sel = $(this);
          const current = sel.val();
          sel.empty().append('<option value="">Select Program</option>');
          (response.data || response).forEach((program) => {
            sel.append($('<option>', { value: program.id, text: program.name }));
          });
          if (selectedId) sel.val(selectedId);
          else sel.val(current);
          sel.trigger('change');
        });
      })
      .fail(() => {
        Utils.showError('Failed to load programs');
      });
  }
};

// Pair row template and manager
const PairManager = {
  pairIndex: 0,
  createRow(levelId = '', programId = '') {
    const idx = this.pairIndex++;
    const row = $(
      `<div class="d-flex gap-2 align-items-center mb-2 pair-row" data-idx="${idx}">
          <select name="level_id[${idx}]" class="form-select level-select flex-grow-1"><option value="">Loading...</option></select>
          <select name="program_id[${idx}]" class="form-select program-select flex-grow-1"><option value="">Loading...</option></select>
          <button type="button" class="btn btn-outline-danger btn-sm remove-pair-btn"><i class="bx bx-trash"></i></button>
        </div>`
    );
    if (levelId) row.find('select[name^="level_id"]').val(levelId);
    if (programId) row.find('select[name^="program_id"]').val(programId);
    return row;
  },
  addPair(levelId = '', programId = '') {
    const row = this.createRow(levelId, programId);
    $('#pairsContainer').append(row);
    // initialize select2 on the new selects
    row.find('.level-select, .program-select').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $(SELECTORS.accessModal) });
    // load options
    DropdownManager.loadLevels();
    DropdownManager.loadPrograms();
  },
  removePair(elem) {
    $(elem).closest('.pair-row').remove();
  },
  collectPairs() {
    const pairs = [];
    $('#pairsContainer').find('.pair-row').each(function() {
      const level = $(this).find('select[name^="level_id"]').val();
      const program = $(this).find('select[name^="program_id"]').val();
      if (level || program) {
        pairs.push({ level_id: level || null, program_id: program || null });
      }
    });
    return pairs;
  }
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================
const StatsManager = {
  loadStats() {
    Utils.toggleLoadingState('total', true);
    Utils.toggleLoadingState('active', true);
    Utils.toggleLoadingState('inactive', true);
    Utils.toggleLoadingState('advisors', true);
    ApiService.fetchStats()
      .done((response) => {
        if (response.success) {
          $('#total-value').text(response.data.total.total ?? '--');
          $('#total-last-updated').text(response.data.total.lastUpdateTime ?? '--');
          $('#active-value').text(response.data.active.total ?? '--');
          $('#active-last-updated').text(response.data.active.lastUpdateTime ?? '--');
          $('#inactive-value').text(response.data.inactive.total ?? '--');
          $('#inactive-last-updated').text(response.data.inactive.lastUpdateTime ?? '--');
          $('#advisors-value').text(response.data.uniqueAdvisors.total ?? '--');
          $('#advisors-last-updated').text(response.data.uniqueAdvisors.lastUpdateTime ?? '--');
        } else {
          $('#total-value, #active-value, #inactive-value, #advisors-value').text('N/A');
          $('#total-last-updated, #active-last-updated, #inactive-last-updated, #advisors-last-updated').text('N/A');
        }
        Utils.toggleLoadingState('total', false);
        Utils.toggleLoadingState('active', false);
        Utils.toggleLoadingState('inactive', false);
        Utils.toggleLoadingState('advisors', false);
      })
      .fail(() => {
        $('#total-value, #active-value, #inactive-value, #advisors-value').text('N/A');
        $('#total-last-updated, #active-last-updated, #inactive-last-updated, #advisors-last-updated').text('N/A');
        Utils.toggleLoadingState('total', false);
        Utils.toggleLoadingState('active', false);
        Utils.toggleLoadingState('inactive', false);
        Utils.toggleLoadingState('advisors', false);
        Utils.showError('Failed to load access statistics');
      });
  }
};

// ===========================
// SELECT2 INITIALIZATION
// ===========================
const Select2Manager = {
  initAccessModalSelect2() {
    $(`${SELECTORS.advisorSelect}, ${SELECTORS.levelSelect}, ${SELECTORS.programSelect}`).select2({
      theme: 'bootstrap-5',
      placeholder: function(){
        return $(this).attr('id') === 'advisor_id' ? 'Select Advisor' :
               $(this).attr('id') === 'level_id' ? 'Select Level' :
               'Select Program';
      },
      allowClear: true,
      width: '100%',
      dropdownParent: $(SELECTORS.accessModal)
    });
  }
};

// ===========================
// ADVISOR ACCESS CRUD & MODALS
// ===========================
let currentAccessId = null;
const AdvisorAccessManager = {

  init() {
    this.bindEvents();
    this.saveAccess();
  },

  bindEvents() {
    console.log('Binding events for AdvisorAccessManager');
    $(document).on('click', '#addAccessRuleBtn', this.openAddAccessModal.bind(this));
    $(document).on('click', '.edit-access', this.editAccess.bind(this));
    $(document).on('click', '.delete-access', this.deleteAccess.bind(this));
    $(document).on('click', '.view-access', this.viewAccess.bind(this));
    // pair row handlers
    $(document).on('click', '#addPairBtn', function() { PairManager.addPair(); });
    $(document).on('click', '.remove-pair-btn', function() { PairManager.removePair(this); });
  },

  openAddAccessModal() {
    currentAccessId = null;
    $(SELECTORS.accessForm)[0].reset();
    DropdownManager.loadAdvisors();
    // reset pairs container and add one empty pair
    $('#pairsContainer').empty();
    PairManager.pairIndex = 0;
    PairManager.addPair();
    $(SELECTORS.isActiveSwitch).prop('checked', true);
    $(SELECTORS.allLevelsCheckbox).prop('checked', false);
    $(SELECTORS.allProgramsCheckbox).prop('checked', false);
    $(SELECTORS.levelSelect).prop('disabled', false);
    $(SELECTORS.programSelect).prop('disabled', false);
    $(SELECTORS.accessModal).modal('show');
  },
  editAccess(event) {
    const accessId = $(event.currentTarget).data('id');
    console.log('Editing access for ID:', accessId);
    console.log('Event target:', event.currentTarget);

    currentAccessId = accessId;
    ApiService.fetchAccess(accessId)
      .done((response) => {
        console.log('Edit response:', response);
        if (response.success) {
          const access = response.data;
          DropdownManager.loadAdvisors(access.advisor_id);
          // populate pairs
          $('#pairsContainer').empty();
          PairManager.pairIndex = 0;
          if (access.pairs && access.pairs.length) {
            access.pairs.forEach(p => PairManager.addPair(p.level_id, p.program_id));
          } else {
            // legacy single values
            PairManager.addPair(access.level_id, access.program_id);
          }
          DropdownManager.loadLevels();
          DropdownManager.loadPrograms();
          $(SELECTORS.isActiveSwitch).prop('checked', access.is_active);
          $(SELECTORS.allLevelsCheckbox).prop('checked', access.all_levels);
          $(SELECTORS.allProgramsCheckbox).prop('checked', access.all_programs);
          $(SELECTORS.accessModal).modal('show');
        }
      })
      .fail(() => {
        Utils.showError('Failed to load access rule data');
      });
  },
  saveAccess() {
    $(SELECTORS.accessForm).on('submit', function(e) {
      e.preventDefault();

      // Build a plain object from the form so we can normalize checkbox values
      const formArray = $(this).serializeArray();
      const data = {};
      formArray.forEach(item => {
        data[item.name] = item.value;
      });

      // Attach advisor and normalize is_active
      data.advisor_id = $(SELECTORS.advisorSelect).val();
      data.is_active = $(SELECTORS.isActiveSwitch).is(':checked') ? 1 : 0;

      // Attach all_levels / all_programs
      data.all_levels = $(SELECTORS.allLevelsCheckbox).is(':checked') ? 1 : 0;
      data.all_programs = $(SELECTORS.allProgramsCheckbox).is(':checked') ? 1 : 0;

      // Attach pairs
      data.pairs = PairManager.collectPairs();

      // Close modal before making AJAX request
      $(SELECTORS.accessModal).modal('hide');

      ApiService.saveAccess(data, currentAccessId)
        .done(() => {
          $(SELECTORS.accessTable).DataTable().ajax.reload(null, false);
          Utils.showSuccess('Access rule has been saved successfully.');
          StatsManager.loadStats();
        })
        .fail((xhr) => {
          const message = xhr.responseJSON?.message || 'An error occurred. Please check your input.';
          Utils.showError(message);
        });
    });
  },
  deleteAccess(event) {
    const accessId = $(event.currentTarget).data('id');
    console.log('Deleting access for ID:', accessId);
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
        ApiService.deleteAccess(accessId)
          .done(() => {
            $(SELECTORS.accessTable).DataTable().ajax.reload(null, false);
            Utils.showSuccess('Access rule has been deleted.');
            StatsManager.loadStats();
          })
          .fail((xhr) => {
            const message = xhr.responseJSON?.message || 'Failed to delete access rule.';
            Utils.showError(message);
          });
      }
    });
  },
  viewAccess(event) {
    const accessId = $(event.currentTarget).data('id');
    console.log('Viewing access for ID:', accessId);
    ApiService.fetchAccess(accessId)
      .done((response) => {
        console.log('View response:', response);
        if (response.success) {
          const access = response.data;
          $(SELECTORS.viewAccessAdvisor).text(access.advisor ? access.advisor.name : 'N/A');
          $(SELECTORS.viewAccessLevel).text(access.level ? access.level.name : 'N/A');
          $(SELECTORS.viewAccessProgram).text(access.program ? access.program.name : 'N/A');
          $(SELECTORS.viewAccessStatus).text(access.is_active ? 'Active' : 'Inactive');
          $(SELECTORS.viewAccessCreated).text(new Date(access.created_at).toLocaleString());
          $(SELECTORS.viewAccessModal).modal('show');
        }
      })
      .fail(() => {
        Utils.showError('Failed to load access rule data');
      });
  }
};

// ===========================
// SEARCH FUNCTIONALITY
// ===========================
const SearchManager = {
  initializeAdvancedSearch() {
    $('#search_advisor, #search_level, #search_program, #search_status').on('keyup change', function() {
      $('#academic-advisor-access-table').DataTable().ajax.reload();
    });
    $('#clearAdvisorAccessFiltersBtn').on('click', function() {
      $('#search_advisor, #search_level, #search_program, #search_status').val('');
      $('#academic-advisor-access-table').DataTable().ajax.reload();
    });
  }
};

// ===========================
// MAIN APPLICATION
// ===========================
const AdvisorAccessApp = {
  init() {
    StatsManager.loadStats();
    AdvisorAccessManager.init();
    Select2Manager.initAccessModalSelect2();
    // Checkbox logic for all_levels and all_programs
    $(SELECTORS.allLevelsCheckbox).on('change', function() {
      $(SELECTORS.levelSelect).prop('disabled', this.checked);
      if (this.checked) {
        $(SELECTORS.levelSelect).val('').trigger('change');
      }
    });
    $(SELECTORS.allProgramsCheckbox).on('change', function() {
      $(SELECTORS.programSelect).prop('disabled', this.checked);
      if (this.checked) {
        $(SELECTORS.programSelect).val('').trigger('change');
      }
    });
    Utils.hidePageLoader();

  }
};

$(document).ready(() => {
  AdvisorAccessApp.init();
  SearchManager.initializeAdvancedSearch();
});
</script>
@endpush 