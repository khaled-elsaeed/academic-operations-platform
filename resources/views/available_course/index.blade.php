@extends('layouts.home')

@section('title', 'Available Courses | AcadOps')

@section('page-content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Statistics Cards -->
        <div class="row mb-4 g-3">
            <div class="col-12 col-sm-6 col-lg-3">
                <x-ui.card.stat2 color="primary" icon="bx bx-book" :label="'Total Available Courses'"
                    id="available-courses" />
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <x-ui.card.stat2 color="info" icon="bx bx-globe" :label="'Universal Courses'" id="universal-courses" />
            </div>
        </div>

        <!-- Page Header -->
        <x-ui.page-header :title="'Available Courses'" :description="'List of all available courses for enrollment'"
            icon="bx bx-book">
            <div class="d-flex flex-wrap gap-2">
                @can('available_course.import')
                    <button class="btn btn-success" id="importBtn">
                        <i class="bx bx-upload"></i> Import
                    </button>
                @endcan
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#availableCourseSearchCollapse">
                    <i class="bx bx-filter-alt"></i> Filter
                </button>
            </div>
        </x-ui.page-header>

        <!-- Advanced Search -->
        <x-ui.advanced-search :title="'Search Available Courses'" formId="advancedAvailableCourseSearch"
            collapseId="availableCourseSearchCollapse" :collapsed="true">
            <div class="col-md-4">
                <label for="search_course" class="form-label">Course</label>
                <input type="text" class="form-control" id="search_course" name="search_course"
                    placeholder="Course Name or Code">
            </div>
            <div class="col-md-4">
                <label for="search_term" class="form-label">Term</label>
                <select class="form-select" id="search_term" name="search_term">
                    <option value="">All Terms</option>
                </select>
            </div>
            <div class="w-100"></div>
            <button class="btn btn-outline-secondary mt-2" id="clearAvailableCourseFiltersBtn" type="button">
                <i class="bx bx-x"></i> Clear
            </button>
        </x-ui.advanced-search>

        <!-- Data Table -->
        <x-ui.datatable.table :headers="['Course', 'Term', 'Eligibilities', 'Schedules', 'Enrollments', 'Actions']"
            :columns="[
            ['data' => 'course', 'name' => 'course'],
            ['data' => 'term', 'name' => 'term'],
            ['data' => 'eligibilities', 'name' => 'eligibilities'],
            ['data' => 'schedules', 'name' => 'schedules'],
            ['data' => 'enrollments', 'name' => 'enrollments'],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
        ]" :ajax-url="route('available_courses.datatable')" :table-id="'available-courses-table'"
            :filter-fields="['search_course', 'search_term']" />

        <!-- Modals -->
        @can('available_course.import')
            <!-- Import Modal -->
            <x-ui.modal id="importModal" :title="'Import Available Courses'" scrollable="true" class="import-modal">
                <x-slot name="slot">
                    <form id="importForm" enctype="multipart/form-data" novalidate>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <div class="flex-shrink-0">
                                        <i class="bx bx-info-circle fs-2 text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1 text-primary">Import Information</h6>
                                        <p class="mb-0 text-muted small">
                                            Upload an Excel file to import available courses.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-4">
                            <div class="col-lg-12">
                                <!-- File Upload -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Excel File <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="import_file" name="file" accept=".xlsx,.xls"
                                        required>
                                    <small class="text-muted">Required: Select an Excel file (.xlsx or .xls)</small>
                                    <div class="invalid-feedback d-block"></div>
                                </div>
                                <!-- Template Download -->
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-primary" id="downloadTemplateBtn">
                                        <i class="bx bx-download me-1"></i>Download Template
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </x-slot>
                <x-slot name="footer">
                    <div class="d-flex justify-content-between w-100">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bx bx-x me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-success" id="submitImportBtn">
                            <i class="bx bx-upload me-1"></i>Start Import
                        </button>
                    </div>
                </x-slot>
            </x-ui.modal>
        @endcan

        <!-- Progress Modal -->
        <x-progress-modal modalId="importProgressModal" modalTitle="Importing Available Courses" />

        <!-- Eligibility Modal -->
        <x-ui.modal id="eligibilityModal" :title="'Eligibilities'" size="xl" :scrollable="false">
            <x-slot name="slot">
                <div id="eligibilityContent"><!-- Filled by JS --></div>
            </x-slot>
            <x-slot name="footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </x-slot>
        </x-ui.modal>

        <!-- Schedules Modal -->
        <x-ui.modal id="schedulesModal" :title="'Course Schedules'" size="xl" :scrollable="true">
            <x-slot name="slot">
                <div id="schedulesContent"><!-- Filled by JS --></div>
            </x-slot>
            <x-slot name="footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </x-slot>
        </x-ui.modal>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/utils.js') }}"></script>
    <script>
        'use strict';

        // ===========================
        // ROUTES CONFIGURATION
        // ===========================
        const ROUTES = {
            availableCourses: {
                stats: @json(route('available_courses.stats')),
                template: @json(route('available_courses.template')),
                import: @json(route('available_courses.import')),
                importStatus: @json(route('available_courses.import.status', ['uuid' => ':uuid'])),
                importCancel: @json(route('available_courses.import.cancel', ['uuid' => ':uuid'])),
                importDownload: @json(route('available_courses.import.download', ['uuid' => ':uuid'])),
                destroy: @json(route('available_courses.destroy', ':id')),
                terms: @json(route('terms.all')),
                schedules: @json(route('available_courses.schedules.all', ':id')),
                eligibilities: @json(route('available_courses.eligibilities.all', ':id'))
            }
        };


        // ===========================
        // API SERVICE
        // ===========================
        const ApiService = {
            fetchStats() {
                return Utils.get(ROUTES.availableCourses.stats);
            },
            fetchTerms() {
                return Utils.get(ROUTES.availableCourses.terms);
            },
            fetchSchedules(id) {
                return Utils.get(Utils.replaceRouteId(ROUTES.availableCourses.schedules, id));
            },
            fetchEligibilities(id) {
                return Utils.get(Utils.replaceRouteId(ROUTES.availableCourses.eligibilities, id));
            }
        };

        // ===========================
        // MODAL MANAGERS
        // ===========================
        const ImportModal = Utils.createModalManager('importModal');
        const EligibilityModal = Utils.createModalManager('eligibilityModal');
        const SchedulesModal = Utils.createModalManager('schedulesModal');

        // ===========================
        // STATS MANAGER
        // ===========================
        const StatsManager = Utils.createStatsManager({
            apiMethod: ApiService.fetchStats,
            statsKeys: ['available-courses', 'universal-courses']
        });

        // ===========================
        // SELECT2 MANAGER
        // ===========================
        const Select2Manager = {
            init() {
                Utils.initSelect2('#search_term', {
                    placeholder: 'please select a term',
                    allowClear: true,
                    dropdownParent: $('#availableCourseSearchCollapse')
                });
            },
            async loadTerms() {
                try {
                    const response = await ApiService.fetchTerms();
                    if (Utils.isResponseSuccess(response)) {
                        const terms = Utils.getResponseData(response);
                        Utils.populateSelect('#search_term', terms, {
                            valueField: 'id',
                            textField: 'name',
                            placeholder: ''
                        }, true);
                    }
                } catch (error) {
                    Utils.handleError(error);
                }
            }
        };

        // ===========================
        // TEMPLATE MANAGER
        // ===========================
        const TemplateManager = {
            init() {
                $('#downloadTemplateBtn').on('click', async () => {
                    const $btn = $('#downloadTemplateBtn');

                    Utils.setLoadingState($btn, true, { loadingText: 'Downloading...' });

                    try {
                        const response = await $.ajax({
                            url: ROUTES.availableCourses.template,
                            method: 'GET',
                            xhrFields: { responseType: 'blob' }
                        });

                        const blob = new Blob([response], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'available_courses_template.xlsx';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);

                        Utils.showSuccess('Template downloaded successfully!');
                    } catch (error) {
                        Utils.showError('Failed to download template');
                    } finally {
                        Utils.setLoadingState($btn, false);
                    }
                });
            }
        };

        // ===========================
        // IMPORT TASK MANAGER
        // ===========================
        const ImportTaskManager = Utils.createAsyncTaskManager({
            startRoute: ROUTES.availableCourses.import,
            checkStatusRoute: ROUTES.availableCourses.importStatus,
            cancelRoute: ROUTES.availableCourses.importCancel,
            downloadRoute: ROUTES.availableCourses.importDownload,
            progressModalId: 'importProgressModal',
            taskName: 'Available Courses Import',
            onStart() {
                ImportModal.hide();
            },
            completionFields: [
                { key: 'processed', label: 'Records Processed', type: 'number' },
                { key: 'created', label: 'Records Created', type: 'number' },
                { key: 'updated', label: 'Records Updated', type: 'number' },
                { key: 'skipped', label: 'Records Skipped', type: 'number' }
            ],
            translations: {
                processing: 'The import is being processed. This may take a few minutes.',
                taskInitializing: 'The import task is initializing.',
                taskPreparing: 'The import task is preparing the data.',
                taskCompleted: 'The import task has completed.',
                taskFailed: 'The import task has failed.',
                statusCheckFailed: 'Failed to check the status of the import task.'
            }
        });

        // ===========================
        // IMPORT MANAGER
        // ===========================
        const ImportManager = {
            init() {
                $('#importBtn').on('click', () => {
                    $('#importForm')[0].reset();
                    ImportModal.show();
                });

                $('#submitImportBtn').on('click', () => {
                    const fileInput = $('#import_file')[0];
                    if (!fileInput.files[0]) {
                        Utils.showValidationError('#import_file', 'Please select a file to import');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('file', fileInput.files[0]);

                    ImportTaskManager.start(formData, {
                        button: $('#submitImportBtn')
                    });
                });
            }
        };

        // ===========================
        // DELETE MANAGER
        // ===========================
        const DeleteManager = {
            init() {
                $(document).on('click', '.deleteAvailableCourseBtn', async (e) => {
                    const id = Utils.getElementData(e.currentTarget, ['id']);
                    const { isConfirmed } = await Utils.showConfirmDialog({
                        title: ('Are you sure?'),
                        text: 'You won\'t be able to revert this!',
                        confirmButtonText: 'Yes, delete it!'
                    });

                    if (isConfirmed) {
                        try {
                            const response = await Utils.delete(Utils.replaceRouteId(ROUTES.availableCourses.destroy, id));
                            Utils.reloadDataTable('available-courses-table');
                            Utils.showSuccess(response.message);
                            StatsManager.refresh();
                        } catch (error) {
                            Utils.handleError(error);
                        }
                    }
                });
            }
        };

        // ===========================
        // ELIGIBILITY MANAGER
        // ===========================

        const EligibilityManager = {
            init() {
                $(document).on('click', '.eligibilitiesAvailableCourseBtn', async (e) => {
                    const id = Utils.getElementData(e.currentTarget, ['id']);
                    $('#eligibilityContent').html(Utils.getLoadingHtml());
                    EligibilityModal.show();

                    try {
                        const response = await ApiService.fetchEligibilities(id);
                        if (Utils.isResponseSuccess(response)) {
                            this.render(Utils.getResponseData(response));
                        }
                    } catch (error) {
                        $('#eligibilityContent').html(`<div class="alert alert-danger">${error.responseJSON?.message}</div>`);
                    }
                });
            },

            render(eligibilities) {
                const $content = $('#eligibilityContent').empty();

                // Check if eligibilities is empty or not an array
                if (!Array.isArray(eligibilities) || eligibilities.length === 0) {
                    $content.append(`
                                    <div class="alert alert-warning d-flex align-items-center">
                                        <i class="bx bx-info-circle fs-4 me-3"></i>
                                        <div>
                                            <h6 class="mb-1">No Eligibility Requirements</h6>
                                            <p class="mb-0">This course has no specific eligibility requirements set.</p>
                                        </div>
                                    </div>
                                `);
                    return;
                }

                // Group eligibilities by program first
                const groupedByProgram = eligibilities.reduce((acc, el) => {
                    const programId = el.program?.id || el.program_id;
                    const programName = el.program?.name || 'Unknown Program';
                    const programCode = el.program?.code || 'UNK';

                    if (!acc[programId]) {
                        acc[programId] = {
                            program: {
                                id: programId,
                                name: programName,
                                code: programCode
                            },
                            levels: {}
                        };
                    }

                    const levelId = el.level?.id || el.level_id;
                    const levelName = el.level?.name || 'Unknown';

                    if (!acc[programId].levels[levelId]) {
                        acc[programId].levels[levelId] = {
                            level: { id: levelId, name: levelName },
                            groups: []
                        };
                    }

                    acc[programId].levels[levelId].groups.push(el.group);
                    return acc;
                }, {});

                // Build tabbed interface
                let nav = '<ul class="nav nav-pills mb-4 justify-content-center" role="tablist">';
                let panes = '<div class="tab-content mt-3">';

                Object.values(groupedByProgram).forEach((programData, idx) => {
                    const tabId = `eligibility-program-tab-${idx}`;
                    const active = idx === 0 ? ' active' : '';

                    nav += `
                                    <li class="nav-item">
                                        <button class="nav-link${active} px-4 py-2" data-bs-toggle="tab" data-bs-target="#${tabId}">
                                            <i class="bx bx-graduation me-1"></i>${Utils.escapeHtml(programData.program.name)}
                                        </button>
                                    </li>
                                `;

                    let body = '<div class="row g-3">';

                    // For each level in this program
                    Object.values(programData.levels).forEach(levelData => {
                        const groups = levelData.groups.sort((a, b) => a - b);
                        const groupBadges = groups.map(group =>
                            `<span class="badge bg-success me-1 mb-1"><i class="bx bx-group me-1"></i>Group ${group}</span>`
                        ).join('');

                        body += `
                                        <div class="col-lg-6 col-md-12">
                                            <div class="card h-100 shadow-sm border-0 bg-light">
                                                <div class="card-header bg-primary text-white text-center py-2">
                                                    <h6 class="card-title mb-0 fw-bold text-white">
                                                        <i class="bx bx-layer me-1"></i>Level ${Utils.escapeHtml(levelData.level.name)}
                                                    </h6>
                                                </div>
                                                <div class="card-body p-3">
                                                    <div class="mb-2">
                                                        <small class="text-muted fw-semibold">
                                                            <i class="bx bx-group me-1"></i>Eligible Groups
                                                        </small>
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        ${groupBadges}
                                                    </div>
                                                    <div class="border-top mt-3 pt-2">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-muted fw-semibold">
                                                                <i class="bx bx-check-circle me-1"></i>Total Groups
                                                            </small>
                                                            <span class="badge bg-info">${groups.length}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                    });

                    body += '</div>';

                    panes += `<div class="tab-pane fade${active} show" id="${tabId}">${body}</div>`;
                });

                nav += '</ul>';
                panes += '</div>';

                // Add header
                const headerHtml = `
                                <div class="mb-3">
                                    <h5 class="text-center text-primary">
                                        <i class="bx bx-check-circle me-2"></i>Eligible Programs, Levels & Groups
                                    </h5>
                                </div>
                            `;

                $content.append(headerHtml + nav + panes);
            }
        };

        // ===========================
        // SCHEDULES MANAGER
        // ===========================
        const SchedulesManager = {
            init() {
                $(document).on('click', '.schedulesAvailableCourseBtn', async (e) => {
                    const id = Utils.getElementData(e.currentTarget, ['id']);
                    $('#schedulesContent').html(Utils.getLoadingHtml());
                    SchedulesModal.show();

                    try {
                        const response = await ApiService.fetchSchedules(id);
                        if (Utils.isResponseSuccess(response)) {
                            this.render(Utils.getResponseData(response));
                        }
                    } catch (error) {
                        $('#schedulesContent').html(`<div class="alert alert-danger">${error.responseJSON?.message || 'Failed to load schedules'}</div>`);
                    }
                });
            },
            render(schedules) {
                const $content = $('#schedulesContent').empty();

                if (!Array.isArray(schedules) || schedules.length === 0) {
                    $content.append(`<div class="alert alert-warning"><i class="bx bx-info-circle me-2"></i>No schedules found for this course.</div>`);
                    return;
                }

                // Group schedules by activity_type
                const groupedByActivity = schedules.reduce((acc, schedule) => {
                    const type = schedule.activity_type || 'Unknown';
                    if (!acc[type]) acc[type] = [];
                    acc[type].push(schedule);
                    return acc;
                }, {});

                let nav = '<ul class="nav nav-pills mb-4 justify-content-center" role="tablist">';
                let panes = '<div class="tab-content mt-3">';

                Object.keys(groupedByActivity).forEach((activityType, idx) => {
                    const activitySchedules = groupedByActivity[activityType];
                    const tabId = `schedule-tab-${idx}`;
                    const active = idx === 0 ? ' active' : '';

                    nav += `<li class="nav-item"><button class="nav-link${active} px-4 py-2" data-bs-toggle="tab" data-bs-target="#${tabId}"><i class="bx bx-calendar me-1"></i>${Utils.escapeHtml(activityType.charAt(0).toUpperCase() + activityType.slice(1))}</button></li>`;

                    let body = '<div class="row g-3">';

                    // Group by group number
                    const groupedByGroup = activitySchedules.reduce((acc, schedule) => {
                        const group = schedule.group || 1;
                        if (!acc[group]) acc[group] = [];
                        acc[group].push(schedule);
                        return acc;
                    }, {});

                    Object.keys(groupedByGroup).sort((a, b) => a - b).forEach(groupNum => {
                        const groupSchedules = groupedByGroup[groupNum];
                        const schedule = groupSchedules[0]; // All in group should be similar

                        // Calculate overall time range
                        let minStart = null, maxEnd = null;
                        const days = new Set();

                        schedule.schedule_assignments.forEach(assignment => {
                            const slot = assignment.schedule_slot;
                            if (slot.start_time && slot.end_time) {
                                if (!minStart || slot.start_time < minStart) minStart = slot.start_time;
                                if (!maxEnd || slot.end_time > maxEnd) maxEnd = slot.end_time;
                            }
                            if (slot.day_of_week) days.add(slot.day_of_week);
                        });

                        const timeRange = minStart && maxEnd ? `${minStart} - ${maxEnd}` : 'TBA';
                        const dayList = Array.from(days).map(d => d.charAt(0).toUpperCase() + d.slice(1)).join(', ') || 'TBA';

                        body += `
                                        <div class="col-lg-4 col-md-6">
                                            <div class="card h-100 shadow-sm border-0 bg-light">
                                                <div class="card-header bg-primary text-white text-center py-2">
                                                    <h6 class="card-title mb-0 fw-bold text-white">
                                                        <i class="bx bx-group me-1"></i>Group ${groupNum}
                                                    </h6>
                                                </div>
                                                <div class="card-body p-3">
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-12">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted fw-semibold"><i class="bx bx-map me-1"></i>Location</small>
                                                                <span class="badge bg-success">${Utils.escapeHtml(schedule.location || 'TBA')}</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <small class="text-muted fw-semibold"><i class="bx bx-user-check me-1"></i>Capacity</small>
                                                                <span class="fw-semibold text-dark">${Utils.escapeHtml(schedule.min_capacity || 0)} - ${Utils.escapeHtml(schedule.max_capacity || 0)}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="border-top pt-2">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <small class="text-muted fw-semibold"><i class="bx bx-calendar me-1"></i>Days</small>
                                                            <span class="fw-semibold text-primary">${dayList}</span>
                                                        </div>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-muted fw-semibold"><i class="bx bx-time me-1"></i>Time</small>
                                                            <span class="fw-semibold text-success">${timeRange}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>`;
                    });

                    body += '</div>';

                    panes += `<div class="tab-pane fade${active} show" id="${tabId}">${body}</div>`;
                });

                nav += '</ul>';
                panes += '</div>';

                $content.append(nav + panes);
            }
        };

        // ===========================
        // SEARCH MANAGER
        // ===========================
        const SearchManager = Utils.createSearchManager({
            searchFields: ['#search_course', '#search_term'],
            clearButtonId: '#clearAvailableCourseFiltersBtn',
            tableId: '#available-courses-table',
            debounceDelay: 500
        });

        // ===========================
        // INITIALIZATION
        // ===========================
        $(() => {
            StatsManager.init();
            Select2Manager.loadTerms().then(() => Select2Manager.init());
            SearchManager.init();
            ImportManager.init();
            TemplateManager.init();
            DeleteManager.init();
            EligibilityManager.init();
            SchedulesManager.init();
            ImportTaskManager.init();
            Utils.hidePageLoader();
        });
    </script>
@endpush