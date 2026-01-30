@extends('layouts.home')

@section('title', 'Admin Students | AcadOps')

@section('page-content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Statistics Cards -->
        <div class="row mb-4 g-3">
            <div class="col-12 col-sm-6 col-lg-4">
                <x-ui.card.stat2 color="primary" icon="bx bx-group" :label="'Total Students'" id="students" />
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
                <x-ui.card.stat2 color="danger" icon="bx bx-user-plus" :label="'Total Male Students'"
                    id="male-students" />
            </div>
            <div class="col-12 col-sm-6 col-lg-4">
                <x-ui.card.stat2 color="success" icon="bx bx-user-check" :label="'Total Female Students'"
                    id="female-students" />
            </div>
        </div>

        <!-- Page Header -->
        <x-ui.page-header :title="'Students'"
            :description="'Manage all student records, add new students, or import/export in bulk using the options on the right.'" icon="bx bx-group">
            <div class="d-flex flex-wrap gap-2">
                @can('student.create')
                    <button class="btn btn-primary" id="addStudentBtn">
                        <i class="bx bx-plus"></i> Add Student
                    </button>
                @endcan
                @can('student.import')
                    <button class="btn btn-success" id="importBtn">
                        <i class="bx bx-upload"></i> Import
                    </button>
                @endcan
                @can('student.export')
                    <button class="btn btn-info" id="exportBtn">
                        <i class="bx bx-download"></i> Export
                    </button>
                @endcan
                <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#studentSearchCollapse">
                    <i class="bx bx-filter-alt"></i> Filter
                </button>
            </div>
        </x-ui.page-header>

        <!-- Advanced Search -->
        <x-ui.advanced-search :title="'Search Students'" formId="advancedStudentSearch" collapseId="studentSearchCollapse"
            :collapsed="true">
            <div class="col-md-4">
                <label for="search_name" class="form-label">Name</label>
                <input type="text" class="form-control" id="search_name" name="search_name" placeholder="Student Name">
            </div>
            <div class="col-md-4">
                <label for="search_national_id" class="form-label">National ID</label>
                <input type="text" class="form-control" id="search_national_id" name="search_national_id"
                    placeholder="National ID">
            </div>
            <div class="col-md-4">
                <label for="search_academic_id" class="form-label">Academic ID</label>
                <input type="text" class="form-control" id="search_academic_id" name="search_academic_id"
                    placeholder="Academic ID">
            </div>
            <div class="w-100"></div>
            <div class="col-md-4">
                <label for="search_gender" class="form-label">Gender</label>
                <select class="form-select" id="search_gender" name="search_gender">
                    <option value="">All Genders</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="search_program" class="form-label">Program</label>
                <select class="form-select" id="search_program" name="search_program">
                    <option value="">All Programs</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="search_level" class="form-label">Level</label>
                <select class="form-select" id="search_level" name="search_level">
                    <option value="">All Levels</option>
                </select>
            </div>
            <button class="btn btn-outline-secondary mt-2" id="clearStudentFiltersBtn" type="button">
                <i class="bx bx-x"></i> Clear
            </button>
        </x-ui.advanced-search>

        <!-- Data Table -->
        <x-ui.datatable.table :headers="[
            'Name (EN)',
            'Name (AR)',
            'Academic ID',
            'National ID',
            'Academic Email',
            'Level',
            'CGPA',
            'Gender',
            'Program',
            'Actions',
        ]" :columns="[
            ['data' => 'name_en', 'name' => 'name_en'],
            ['data' => 'name_ar', 'name' => 'name_ar'],
            ['data' => 'academic_id', 'name' => 'academic_id'],
            ['data' => 'national_id', 'name' => 'national_id'],
            ['data' => 'academic_email', 'name' => 'academic_email'],
            ['data' => 'level', 'name' => 'level'],
            ['data' => 'cgpa', 'name' => 'cgpa'],
            ['data' => 'gender', 'name' => 'gender'],
            ['data' => 'program', 'name' => 'program', 'orderable' => false, 'searchable' => false],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
        ]" :ajax-url="route('students.datatable')" :table-id="'students-table'" :filter-fields="[
            'search_name',
            'search_national_id',
            'search_academic_id',
            'search_gender',
            'search_program',
            'search_level',
        ]" />

        <!-- Modals -->
        @can('student.create')
            <!-- Add/Edit Student Modal -->
            <x-ui.modal id="studentModal" :title="'Add/Edit Student'" size="lg" :scrollable="true" class="student-modal">
                <x-slot name="slot">
                    <form id="studentForm" novalidate>
                        <input type="hidden" id="student_id" name="student_id">

                        <div class="row g-3">
                            <!-- Name Fields -->
                            <div class="col-md-6">
                                <label for="name_en" class="form-label">Name (EN) <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name_en" name="name_en" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="name_ar" class="form-label">Name (AR) <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name_ar" name="name_ar" required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- ID Fields -->
                            <div class="col-md-6">
                                <label for="academic_id" class="form-label">Academic ID <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="academic_id" name="academic_id" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="national_id" class="form-label">National ID <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="national_id" name="national_id" required>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Email and Level -->
                            <div class="col-md-6">
                                <label for="academic_email" class="form-label">Academic Email <span
                                        class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="academic_email" name="academic_email"
                                    required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="level_id" class="form-label">Level <span class="text-danger">*</span></label>
                                <select class="form-select" id="level_id" name="level_id" required>
                                    <option value="">Select Level</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- CGPA and Gender -->
                            <div class="col-md-6">
                                <label for="cgpa" class="form-label">CGPA <span class="text-danger">*</span></label>
                                <input type="number" step="0.001" class="form-control" id="cgpa" name="cgpa"
                                    required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>

                            <!-- Program -->
                            <div class="col-md-6">
                                <label for="program_id" class="form-label">Program <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="program_id" name="program_id" required>
                                    <option value="">Select Program</option>
                                </select>
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </form>
                </x-slot>
                <x-slot name="footer">
                    <div class="d-flex justify-content-between w-100">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bx bx-x me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-primary" id="saveStudentBtn">
                            <i class="bx bx-save me-1"></i>Save
                        </button>
                    </div>
                </x-slot>
            </x-ui.modal>
        @endcan

        @can('student.import')
            <!-- Import Modal -->
            <x-ui.modal id="importModal" :title="'Import Students'" scrollable="true" class="import-modal">
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
                                            Upload an Excel file to import students.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-4">
                            <div class="col-lg-12">
                                <!-- File Upload -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Excel File <span
                                            class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="import_file" name="file"
                                        accept=".xlsx,.xls" required>
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

        @can('student.export')
            <!-- Export Modal -->
            <x-ui.modal id="exportModal" :title="'Export Students'" size="md" :scrollable="false" class="export-modal">
                <x-slot name="slot">
                    <form id="exportForm" novalidate>
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex align-items-center p-3 bg-light rounded">
                                    <div class="flex-shrink-0">
                                        <i class="bx bx-info-circle fs-2 text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1 text-primary">Export Information</h6>
                                        <p class="mb-0 text-muted small">
                                            Select filters to export specific students or leave blank for all.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="export_program_id" class="form-label">Program</label>
                                <select class="form-select" id="export_program_id" name="program_id">
                                    <option value="">All Programs</option>
                                </select>
                                <small class="text-muted">Optional: Leave blank to export all programs</small>
                            </div>
                            <div class="col-12">
                                <label for="export_level_id" class="form-label">Level</label>
                                <select class="form-select" id="export_level_id" name="level_id">
                                    <option value="">All Levels</option>
                                </select>
                                <small class="text-muted">Optional: Leave blank to export all levels</small>
                            </div>
                        </div>
                    </form>
                </x-slot>
                <x-slot name="footer">
                    <div class="d-flex justify-content-between w-100">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bx bx-x me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-info" id="submitExportBtn">
                            <i class="bx bx-download me-1"></i>Start Export
                        </button>
                    </div>
                </x-slot>
            </x-ui.modal>
        @endcan

        <!-- Download Enrollment Document Modal -->
        <x-ui.modal id="downloadEnrollmentModal" :title="'Download Enrollment Document'" size="md" :scrollable="false"
            class="download-enrollment-modal">
            <x-slot name="slot">
                <form id="downloadEnrollmentForm" novalidate>
                    <input type="hidden" id="modal_student_id" name="student_id">
                    <input type="hidden" id="download_type" name="download_type">

                    <div class="mb-3">
                        <label for="term_id" class="form-label">
                            Select Term
                            <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="term_id" name="term_id" required>
                            <option value="">Select Term</option>
                        </select>
                        <small class="form-text text-muted">
                            You must select a term to download the enrollment document.
                        </small>
                        <div class="invalid-feedback"></div>
                    </div>
                </form>
            </x-slot>
            <x-slot name="footer">
                <div class="d-flex justify-content-between w-100">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="downloadEnrollmentBtn">
                        <i class="bx bx-download me-1"></i>Download
                    </button>
                </div>
            </x-slot>
        </x-ui.modal>

        <!-- Progress Modals -->
        <x-progress-modal modalId="importProgressModal" modalTitle="Importing Students" />
        <x-progress-modal modalId="exportProgressModal" modalTitle="Exporting Students" />
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
            programs: {
                all: @json(route('programs.all'))
            },
            levels: {
                all: @json(route('levels.all'))
            },
            terms: {
                all: @json(route('terms.all'))
            },
            students: {
                stats: @json(route('students.stats')),
                store: @json(route('students.store')),
                show: @json(route('students.show', ':id')),
                destroy: @json(route('students.destroy', ':id')),
                import: @json(route('students.import')),
                importStatus: @json(route('students.import.status', ['uuid' => ':uuid'])),
                importCancel: @json(route('students.import.cancel', ['uuid' => ':uuid'])),
                importDownload: @json(route('students.import.download', ['uuid' => ':uuid'])),
                export: @json(route('students.export')),
                exportStatus: @json(route('students.export.status', ['uuid' => ':uuid'])),
                exportCancel: @json(route('students.export.cancel', ['uuid' => ':uuid'])),
                exportDownload: @json(route('students.export.download', ['uuid' => ':uuid'])),
                template: @json(route('students.template')),
                downloadPdf: @json(route('students.download.pdf', ':id')),
                downloadWord: @json(route('students.download.word', ':id'))
            }
        };

        // ===========================
        // API SERVICE
        // ===========================
        const ApiService = {
            fetchStats() {
                return Utils.get(ROUTES.students.stats);
            },
            fetchPrograms() {
                return Utils.get(ROUTES.programs.all);
            },
            fetchLevels() {
                return Utils.get(ROUTES.levels.all);
            },
            fetchTerms() {
                return Utils.get(ROUTES.terms.all);
            },
            fetchStudent(id) {
                return Utils.get(Utils.replaceRouteId(ROUTES.students.show, id));
            },
            saveStudent(data, id = null) {
                const url = id ? Utils.replaceRouteId(ROUTES.students.show, id) : ROUTES.students.store;
                const method = id ? 'PUT' : 'POST';
                return Utils.request(url, method, data);
            },
            deleteStudent(id) {
                return Utils.delete(Utils.replaceRouteId(ROUTES.students.destroy, id));
            }
        };

        // ===========================
        // MODAL MANAGERS
        // ===========================
        const StudentModal = Utils.createModalManager('studentModal');
        const ImportModal = Utils.createModalManager('importModal');
        const ExportModal = Utils.createModalManager('exportModal');
        const DownloadEnrollmentModal = Utils.createModalManager('downloadEnrollmentModal');

        // ===========================
        // STATS MANAGER
        // ===========================
        const StatsManager = Utils.createStatsManager({
            apiMethod: ApiService.fetchStats,
            statsKeys: ['students', 'male-students', 'female-students']
        });

        // ===========================
        // SELECT2 MANAGER
        // ===========================
        const Select2Manager = {
            init() {
                // Search filters
                Utils.initSelect2('#search_gender, #search_program, #search_level', {
                    placeholder: function() {
                        const id = $(this).attr('id');
                        if (id === 'search_gender') return 'All Genders';
                        if (id === 'search_program') return 'All Programs';
                        if (id === 'search_level') return 'All Levels';
                        return '';
                    },
                    allowClear: true,
                    dropdownParent: $('#studentSearchCollapse')
                });

                // Student modal
                Utils.initSelect2('#studentModal select', {
                    placeholder: function() {
                        const id = $(this).attr('id');
                        if (id === 'level_id') return 'Select Level';
                        if (id === 'gender') return 'Select Gender';
                        if (id === 'program_id') return 'Select Program';
                        return '';
                    },
                    allowClear: true,
                    dropdownParent: $('#studentModal')
                });

                // Download enrollment modal
                Utils.initSelect2('#downloadEnrollmentModal #term_id', {
                    placeholder: 'Select Term',
                    allowClear: true,
                    dropdownParent: $('#downloadEnrollmentModal')
                });

                // Export modal
                Utils.initSelect2('#exportModal select', {
                    placeholder: function() {
                        const id = $(this).attr('id');
                        if (id === 'export_program_id') return 'All Programs';
                        if (id === 'export_level_id') return 'All Levels';
                        return '';
                    },
                    allowClear: true,
                    dropdownParent: $('#exportModal')
                });
            },

            async loadPrograms(selector = '#program_id', selectedId = null) {
                try {
                    const response = await ApiService.fetchPrograms();
                    if (Utils.isResponseSuccess(response)) {
                        const programs = Utils.getResponseData(response);
                        Utils.populateSelect(selector, programs, {
                            valueField: 'id',
                            textField: 'name',
                            placeholder: ''
                        }, true);
                        if (selectedId) {
                            $(selector).val(selectedId).trigger('change');
                        }
                    }
                } catch (error) {
                    Utils.handleError(error);
                }
            },

            async loadLevels(selector = '#level_id', selectedId = null) {
                try {
                    const response = await ApiService.fetchLevels();
                    if (Utils.isResponseSuccess(response)) {
                        const levels = Utils.getResponseData(response);
                        Utils.populateSelect(selector, levels, {
                            valueField: 'id',
                            textField: 'name',
                            placeholder: ''
                        }, true);
                        if (selectedId) {
                            $(selector).val(selectedId).trigger('change');
                        }
                    }
                } catch (error) {
                    Utils.handleError(error);
                }
            },

            async loadTerms(selector = '#term_id', selectedId = null) {
                try {
                    const response = await ApiService.fetchTerms();
                    if (Utils.isResponseSuccess(response)) {
                        const terms = Utils.getResponseData(response);
                        Utils.populateSelect(selector, terms, {
                            valueField: 'id',
                            textField: 'name',
                            placeholder: ''
                        }, true);
                        if (selectedId) {
                            $(selector).val(selectedId).trigger('change');
                        }
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

                    Utils.setLoadingState($btn, true, {
                        loadingText: 'Downloading...'
                    });

                    try {
                        const response = await $.ajax({
                            url: ROUTES.students.template,
                            method: 'GET',
                            xhrFields: {
                                responseType: 'blob'
                            }
                        });

                        const blob = new Blob([response], {
                            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'students_template.xlsx';
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
            startRoute: ROUTES.students.import,
            checkStatusRoute: ROUTES.students.importStatus,
            cancelRoute: ROUTES.students.importCancel,
            downloadRoute: ROUTES.students.importDownload,
            progressModalId: 'importProgressModal',
            taskName: 'Students Import',
            onStart() {
                ImportModal.hide();
            },
            onComplete() {
                Utils.reloadDataTable('students-table');
                StatsManager.refresh();
            },
            completionFields: [{
                    key: 'processed',
                    label: 'Records Processed',
                    type: 'number'
                },
                {
                    key: 'created',
                    label: 'Records Created',
                    type: 'number'
                },
                {
                    key: 'updated',
                    label: 'Records Updated',
                    type: 'number'
                },
                {
                    key: 'skipped',
                    label: 'Records Skipped',
                    type: 'number'
                }
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
            startRoute: ROUTES.students.export,
            checkStatusRoute: ROUTES.students.exportStatus,
            cancelRoute: ROUTES.students.exportCancel,
            downloadRoute: ROUTES.students.exportDownload,
            progressModalId: 'exportProgressModal',
            taskName: 'Students Export',
            onStart() {
                ExportModal.hide();
            },
            completionFields: [{
                key: 'total',
                label: 'Total Records Exported',
                type: 'number'
            }],
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
                    $('#exportForm')[0].reset();
                    Select2Manager.loadPrograms('#export_program_id');
                    Select2Manager.loadLevels('#export_level_id');
                    ExportModal.show();
                });

                $('#submitExportBtn').on('click', () => {
                    const formData = new FormData($('#exportForm')[0]);

                    ExportTaskManager.start(formData, {
                        button: $('#submitExportBtn')
                    });
                });
            }
        };

        // ===========================
        // STUDENT CRUD MANAGER
        // ===========================
        const StudentManager = {
            init() {
                this.handleAdd();
                this.handleEdit();
                this.handleDelete();
                this.handleSave();
            },

            handleAdd() {
                $('#addStudentBtn').on('click', () => {
                    $('#studentForm')[0].reset();
                    $('#student_id').val('');
                    Utils.clearValidationErrors('#studentForm');

                    $('#studentModal .modal-title').text('Add Student');
                    $('#saveStudentBtn').html('<i class="bx bx-save me-1"></i>Save');

                    Select2Manager.loadPrograms();
                    Select2Manager.loadLevels();

                    StudentModal.show();
                });
            },

            handleEdit() {
                $(document).on('click', '.editStudentBtn', async (e) => {
                    const id = Utils.getElementData(e.currentTarget, ['id']);

                    try {
                        const response = await ApiService.fetchStudent(id);
                        if (Utils.isResponseSuccess(response)) {
                            const student = Utils.getResponseData(response);

                            $('#student_id').val(student.id);
                            $('#name_en').val(student.name_en);
                            $('#name_ar').val(student.name_ar);
                            $('#academic_id').val(student.academic_id);
                            $('#national_id').val(student.national_id);
                            $('#academic_email').val(student.academic_email);
                            $('#cgpa').val(student.cgpa);
                            $('#gender').val(student.gender).trigger('change');

                            await Select2Manager.loadPrograms('#program_id', student.program_id);
                            await Select2Manager.loadLevels('#level_id', student.level_id);

                            $('#studentModal .modal-title').text('Edit Student');
                            $('#saveStudentBtn').html('<i class="bx bx-save me-1"></i>Update');

                            StudentModal.show();
                        }
                    } catch (error) {
                        Utils.handleError(error);
                    }
                });
            },

            handleDelete() {
                $(document).on('click', '.deleteStudentBtn', async (e) => {
                    const id = Utils.getElementData(e.currentTarget, ['id']);
                    const {
                        isConfirmed
                    } = await Utils.showConfirmDialog({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        confirmButtonText: 'Yes, delete it!'
                    });

                    if (isConfirmed) {
                        try {
                            const response = await ApiService.deleteStudent(id);
                            Utils.reloadDataTable('students-table');
                            Utils.showSuccess(response.message || 'Student has been deleted.');
                            StatsManager.refresh();
                        } catch (error) {
                            Utils.handleError(error);
                        }
                    }
                });
            },

            handleSave() {
                $('#saveStudentBtn').on('click', async () => {
                    Utils.clearValidationErrors('#studentForm');

                    const studentId = $('#student_id').val();
                    const formData = Utils.serializeForm('#studentForm');
                    const $btn = $('#saveStudentBtn');

                    Utils.setLoadingState($btn, true, {
                        loadingText: studentId ? 'Updating...' : 'Saving...'
                    });

                    try {
                        const response = await ApiService.saveStudent(formData, studentId || null);

                        if (Utils.isResponseSuccess(response)) {
                            StudentModal.hide();
                            Utils.reloadDataTable('students-table');
                            Utils.showSuccess(response.message || 'Student has been saved successfully.');
                            StatsManager.refresh();
                        }
                    } catch (error) {
                        if (error.status === 422) {
                            Utils.displayValidationErrors(error.responseJSON.errors, '#studentForm');
                        } else {
                            Utils.handleError(error);
                        }
                    } finally {
                        Utils.setLoadingState($btn, false);
                    }
                });
            }
        };

        // ===========================
        // DOWNLOAD MANAGER
        // ===========================
        const DownloadManager = {
            init() {
                this.handleDownloadEnrollment();
                this.handleDownloadPdf();
                this.handleDownloadWord();
                this.handleDownloadProcess();
            },

            handleDownloadEnrollment() {
                $(document).on('click', '.downloadEnrollmentBtn', function() {
                    const studentId = $(this).data('id');
                    DownloadManager.setupDownloadModal(studentId, 'legacy', 'Download Enrollment Document');
                });
            },

            handleDownloadPdf() {
                $(document).on('click', '.downloadPdfBtn', function(e) {
                    e.preventDefault();
                    const studentId = $(this).data('id');
                    DownloadManager.setupDownloadModal(studentId, 'pdf', 'Download Enrollment as PDF');
                });
            },

            handleDownloadWord() {
                $(document).on('click', '.downloadWordBtn', function(e) {
                    e.preventDefault();
                    const studentId = $(this).data('id');
                    DownloadManager.setupDownloadModal(studentId, 'word', 'Download Enrollment as Word');
                });
            },

            setupDownloadModal(studentId, downloadType, modalTitle) {
                $('#modal_student_id').val(studentId);
                $('#download_type').val(downloadType);

                Select2Manager.loadTerms('#term_id').then(() => {
                    $('#downloadEnrollmentModal .modal-title').text(modalTitle);
                    DownloadEnrollmentModal.show();
                });
            },

            handleDownloadProcess() {
                $('#downloadEnrollmentBtn').on('click', async () => {
                    const studentId = $('#modal_student_id').val();
                    const termId = $('#term_id').val();
                    const downloadType = $('#download_type').val();

                    if (!termId) {
                        Utils.showValidationError('#term_id', 'Please select a term.');
                        return;
                    }

                    DownloadEnrollmentModal.hide();

                    const $loadingSwal = Swal.fire({
                        title: 'Generating Document...',
                        text: 'Please wait while we prepare your document.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    try {
                        let url;
                        switch (downloadType) {
                            case 'pdf':
                                url = Utils.replaceRouteId(ROUTES.students.downloadPdf, studentId);
                                break;
                            case 'word':
                                url = Utils.replaceRouteId(ROUTES.students.downloadWord, studentId);
                                break;
                            default:
                                url = '/enrollment/download/' + studentId;
                        }

                        url += '?term_id=' + termId;

                        const response = await Utils.get(url);

                        if (response.url) {
                            window.open(response.url, '_blank');
                            Swal.close();
                        } else {
                            Utils.showError('Invalid response from server.');
                        }
                    } catch (error) {
                        Swal.close();
                        Utils.handleError(error);
                    }
                });
            }
        };

        // ===========================
        // SEARCH MANAGER
        // ===========================
        const SearchManager = Utils.createSearchManager({
            searchFields: ['#search_name', '#search_national_id', '#search_academic_id', '#search_gender',
                '#search_program', '#search_level'
            ],
            clearButtonId: '#clearStudentFiltersBtn',
            tableId: '#students-table',
            debounceDelay: 500
        });

        // ===========================
        // INITIALIZATION
        // ===========================
        $(() => {
            StatsManager.init();
            Select2Manager.init();
            Select2Manager.loadPrograms('#search_program');
            Select2Manager.loadLevels('#search_level');
            SearchManager.init();
            StudentManager.init();
            ImportManager.init();
            ExportManager.init();
            TemplateManager.init();
            DownloadManager.init();
            ImportTaskManager.init();
            ExportTaskManager.init();
            Utils.hidePageLoader();
        });
    </script>
@endpush