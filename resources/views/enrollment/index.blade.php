@extends('layouts.home')

@section('title', 'Admin Enrollment | AcadOps')

@section('page-content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Statistics Cards -->
        <div class="row mb-4 g-3">
            <div class="col-12 col-sm-6 col-lg-3">
                <x-ui.card.stat2 color="primary" icon="bx bx-book-open" :label="'Total Enrollments'" id="enrollments" />
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <x-ui.card.stat2 color="secondary" icon="bx bx-book-open" :label="'Graded Enrollments'" id="graded-enrollments" />
            </div>
        </div>

        <!-- Page Header -->
        <x-ui.page-header :title="'Enrollments'" :description="'Manage all student enrollments and import/export in bulk'"
            icon="bx bx-book-open">
            <div class="d-flex flex-wrap gap-2">
                @can('enrollment.import')
                    <button class="btn btn-primary" id="importBtn">
                        <i class="bx bx-upload"></i> Import
                    </button>
                @endcan
                @can('enrollment.export')
                    <button class="btn btn-success" id="exportBtn">
                        <i class="bx bx-download"></i> Export
                    </button>
                @endcan
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#enrollmentSearchCollapse">
                    <i class="bx bx-filter-alt"></i> Filter
                </button>
            </div>
        </x-ui.page-header>

        <!-- Advanced Search -->
        <x-ui.advanced-search :title="'Search Enrollments'" formId="advancedEnrollmentSearch"
            collapseId="enrollmentSearchCollapse" :collapsed="true">
            <div class="col-md-3">
                <label for="search_student" class="form-label">Student</label>
                <input type="text" class="form-control" id="search_student" name="search_student"
                    placeholder="Name or Academic ID">
            </div>

            <div class="col-md-3">
                <label for="search_course" class="form-label">Course</label>
                <input type="text" class="form-control" id="search_course" name="search_course"
                    placeholder="Course Name or Code">
            </div>

            <div class="col-md-3">
                <label for="search_term" class="form-label">Term</label>
                <input type="text" class="form-control" id="search_term" name="search_term"
                    placeholder="Season, Year, or Code">
            </div>

            <div class="col-md-3">
                <label for="search_grade" class="form-label">Grade</label>
                <select class="form-select" id="search_grade" name="search_grade">
                    <option value="">All Grades</option>
                    <option value="A+">A+</option>
                    <option value="A">A</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B">B</option>
                    <option value="B-">B-</option>
                    <option value="C+">C+</option>
                    <option value="C">C</option>
                    <option value="C-">C-</option>
                    <option value="D+">D+</option>
                    <option value="D">D</option>
                    <option value="F">F</option>
                    <option value="no-grade">No Grade</option>
                </select>
            </div>

            <div class="w-100"></div>
            <button class="btn btn-outline-secondary mt-2" id="clearEnrollmentFiltersBtn" type="button">
                <i class="bx bx-x"></i> Clear
            </button>
        </x-ui.advanced-search>

        <!-- Data Table -->
        <x-ui.datatable.table :headers="['Student', 'Course', 'Term', 'Grade', 'Actions']" :columns="[
            ['data' => 'student', 'name' => 'student'],
            ['data' => 'course', 'name' => 'course'],
            ['data' => 'term', 'name' => 'term'],
            ['data' => 'grade', 'name' => 'grade'],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
        ]" :ajax-url="route('enrollments.datatable')" :table-id="'enrollments-table'"
            :filter-fields="['search_student', 'search_course', 'search_term', 'search_grade']" />

        <!-- Modals -->
        @can('enrollment.import')
            <!-- Import Modal -->
            <x-ui.modal id="importModal" :title="'Import Enrollments'" scrollable="false" class="import-modal">
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
                                            Upload an Excel file to import enrollments. Use the system template or SIS template for correct formatting.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-4">
                            <div class="col-lg-12">
                                <!-- Template Selection -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Template <span class="text-danger">*</span></label>
                                    <select class="form-select" id="template_select" name="template_select" required>
                                        <option value="">Select a template</option>
                                        <option value="system">System Template</option>
                                        <option value="sis">SIS Template</option>
                                    </select>
                                    <small class="text-muted">Required: Select a template</small>
                                    <div class="invalid-feedback d-block"></div>
                                </div>
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
                        <button type="button" class="btn btn-primary" id="submitImportBtn">
                            <i class="bx bx-upload me-1"></i>Start Import
                        </button>
                    </div>
                </x-slot>
            </x-ui.modal>
        @endcan

        @can('enrollment.export')
            <!-- Export Modal -->
            <x-ui.modal id="exportModal" :title="'Export Enrollments'" scrollable="false" class="export-modal">
                <x-slot name="slot">
                    <form id="exportForm">
                        <div class="mb-3">
                            <label for="export_term_id" class="form-label">Term</label>
                            <select class="form-select" id="export_term_id" name="term_id">
                                <option value="">All Terms</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="export_program_id" class="form-label">Program</label>
                            <select class="form-select" id="export_program_id" name="program_id">
                                <option value="">All Programs</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="export_level_id" class="form-label">Level</label>
                            <select class="form-select" id="export_level_id" name="level_id">
                                <option value="">All Levels</option>
                            </select>
                        </div>
                    </form>
                </x-slot>
                <x-slot name="footer">
                    <div class="d-flex justify-content-end w-100">
                        <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-success" id="submitExportBtn">
                            <i class="bx bx-download me-1"></i>Export
                        </button>
                    </div>
                </x-slot>
            </x-ui.modal>
        @endcan

        <!-- Progress Modal -->
        <x-progress-modal modalId="importProgressModal" modalTitle="Importing Enrollments" />
        <x-progress-modal modalId="exportProgressModal" modalTitle="Exporting Enrollments" />

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
            enrollments: {
                stats: @json(route('enrollments.stats')),
                destroy: @json(route('enrollments.destroy', ':id')),
                import: @json(route('enrollments.import')),
                importStatus: @json(route('enrollments.import.status', ['uuid' => ':uuid'])),
                importCancel: @json(route('enrollments.import.cancel', ['uuid' => ':uuid'])),
                importDownload: @json(route('enrollments.import.download', ['uuid' => ':uuid'])),
                template: @json(route('enrollments.template')),
                export: @json(route('enrollments.export')),
                exportStatus: @json(route('enrollments.export.status', ['uuid' => ':uuid'])),
                exportCancel: @json(route('enrollments.export.cancel', ['uuid' => ':uuid'])),
                exportDownload: @json(route('enrollments.export.download', ['uuid' => ':uuid']))
            },
            terms: {
                all: @json(route('terms.all.with_inactive'))
            },
            programs: {
                all: @json(route('programs.all'))
            },
            levels: {
                all: @json(route('levels.all'))
            }
        };

        // ===========================
        // API SERVICE
        // ===========================
        const ApiService = {
            fetchStats() {
                return Utils.get(ROUTES.enrollments.stats);
            },
            deleteEnrollment(id) {
                return Utils.delete(Utils.replaceRouteId(ROUTES.enrollments.destroy, id));
            },
            importEnrollments(formData) {
                return Utils.post(ROUTES.enrollments.import, formData);
            },
            exportEnrollments(formData) {
                return Utils.post(ROUTES.enrollments.export, formData);
            },
            downloadTemplate() {
                return $.ajax({
                    url: ROUTES.enrollments.template,
                    method: 'GET',
                    xhrFields: { responseType: 'blob' }
                });
            },
            fetchTerms() {
                return Utils.get(ROUTES.terms.all);
            },
            fetchPrograms() {
                return Utils.get(ROUTES.programs.all);
            },
            fetchLevels() {
                return Utils.get(ROUTES.levels.all);
            }
        };

        // ===========================
        // MODAL MANAGERS
        // ===========================
        const ImportModal = Utils.createModalManager('importModal');
        const ExportModal = Utils.createModalManager('exportModal');

        // ===========================
        // STATS MANAGER
        // ===========================
        const StatsManager = Utils.createStatsManager({
            apiMethod: ApiService.fetchStats,
            statsKeys: ['enrollments', 'graded-enrollments']
        });

        // ===========================
        // SELECT2 MANAGER
        // ===========================
        const Select2Manager = {
            init() {
                Utils.initSelect2('#export_term_id', { dropdownParent: $('#exportModal') });
                Utils.initSelect2('#export_program_id', { dropdownParent: $('#exportModal') });
                Utils.initSelect2('#export_level_id', { dropdownParent: $('#exportModal') });
            },

            async loadExportOptions() {
                try {
                    const [termsRes, programsRes, levelsRes] = await Promise.all([
                        ApiService.fetchTerms(),
                        ApiService.fetchPrograms(),
                        ApiService.fetchLevels()
                    ]);

                    if (Utils.isResponseSuccess(termsRes)) {
                        Utils.populateSelect('#export_term_id', Utils.getResponseData(termsRes), {
                            valueField: 'id', textField: 'name', placeholder: 'All Terms'
                        });
                    }
                    if (Utils.isResponseSuccess(programsRes)) {
                        Utils.populateSelect('#export_program_id', Utils.getResponseData(programsRes), {
                            valueField: 'id', textField: 'name', placeholder: 'All Programs'
                        });
                    }
                    if (Utils.isResponseSuccess(levelsRes)) {
                        Utils.populateSelect('#export_level_id', Utils.getResponseData(levelsRes), {
                            valueField: 'id', textField: 'name', placeholder: 'All Levels'
                        });
                    }


                } catch (error) {
                    console.error("Failed to load export options", error);
                    Utils.showError("Failed to load some export options");
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
                        const response = await ApiService.downloadTemplate();
                        const blob = new Blob([response], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'enrollments_template.xlsx';
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
            startRoute: ROUTES.enrollments.import,
            checkStatusRoute: ROUTES.enrollments.importStatus,
            cancelRoute: ROUTES.enrollments.importCancel,
            downloadRoute: ROUTES.enrollments.importDownload,
            progressModalId: 'importProgressModal',
            taskName: 'Enrollments Import',
            onStart() {
                ImportModal.hide();
            },
            completionFields: [
                { key: 'processed', label: 'Records Processed', type: 'number' },
                { key: 'created', label: 'Records Created', type: 'number' },
                { key: 'updated', label: 'Records Updated', type: 'number' },
                { key: 'failed', label: 'Records Failed', type: 'number' }
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
        // EXPORT TASK MANAGER
        // ===========================
        const ExportTaskManager = Utils.createAsyncTaskManager({
            startRoute: ROUTES.enrollments.export,
            checkStatusRoute: ROUTES.enrollments.exportStatus,
            cancelRoute: ROUTES.enrollments.exportCancel,
            downloadRoute: ROUTES.enrollments.exportDownload,
            progressModalId: 'exportProgressModal',
            taskName: 'Enrollments Export',
            onStart() {
                ExportModal.hide();
            },
            completionFields: [],
            translations: {
                processing: 'The export is being processed. This may take a few minutes.',
                taskInitializing: 'The export task is initializing.',
                taskPreparing: 'The export task is preparing the data.',
                taskCompleted: 'The export task has completed.',
                taskFailed: 'The export task has failed.',
                statusCheckFailed: 'Failed to check the status of the export task.'
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
                    formData.append('template_select', $('#template_select').val());
                    ImportTaskManager.start(formData, {
                        button: $('#submitImportBtn')
                    });
                });
            }
        };

        // ===========================
        // EXPORT MANAGER
        // ===========================
        const ExportManager = {
            init() {
                $('#exportBtn').on('click', () => {
                    ExportModal.show();
                });

                $('#submitExportBtn').on('click', () => {
                    const termId = $('#export_term_id').val();
                    const programId = $('#export_program_id').val();
                    const levelId = $('#export_level_id').val();

                    const formData = new FormData();
                    if (termId) formData.append('term_id', termId);
                    if (programId) formData.append('program_id', programId);
                    if (levelId) formData.append('level_id', levelId);

                    ExportTaskManager.start(formData, {
                        button: $('#submitExportBtn')
                    });
                });
            }
        };

        // ===========================
        // DELETE MANAGER
        // ===========================
        const DeleteManager = {
            init() {
                $(document).on('click', '.deleteEnrollmentBtn', async (e) => {
                    const id = Utils.getElementData(e.currentTarget, ['id']);
                    const { isConfirmed } = await Utils.showConfirmDialog({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        confirmButtonText: 'Yes, delete it!'
                    });

                    if (isConfirmed) {
                        try {
                            const response = await ApiService.deleteEnrollment(id);
                            Utils.reloadDataTable('enrollments-table');
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
        // SEARCH MANAGER
        // ===========================
        const SearchManager = Utils.createSearchManager({
            searchFields: ['#search_student', '#search_course', '#search_term', '#search_grade'],
            clearButtonId: '#clearEnrollmentFiltersBtn',
            tableId: '#enrollments-table',
            debounceDelay: 500
        });

        // ===========================
        // INITIALIZATION
        // ===========================
        $(async () => {
            StatsManager.init();
            SearchManager.init();
            ImportManager.init();
            ImportTaskManager.init();
            ExportManager.init();
            ExportTaskManager.init();
            TemplateManager.init();
            DeleteManager.init();
            Select2Manager.init();
            await Select2Manager.loadExportOptions();

            Utils.hidePageLoader();
        });

    </script>
@endpush