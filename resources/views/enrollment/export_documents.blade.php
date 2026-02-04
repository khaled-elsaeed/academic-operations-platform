@extends('layouts.home')

@section('title', 'Export Enrollment Documents | AcadOps')

@section('page-content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <x-ui.page-header title="Export Enrollment Documents"
            description="Generate enrollment PDF packets by student or by cohort and download them as a single ZIP."
            icon="bx bx-file-archive">
            <div class="d-flex gap-2">
                <a href="{{ route('enrollments.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Enrollments
                </a>
            </div>
        </x-ui.page-header>

        <div class="card">
            <div class="card-body">
                <div class="alert alert-info d-flex align-items-start" role="alert">
                    <i class="bx bx-info-circle me-2 fs-4 mt-1"></i>
                    <div>
                        Export a single student from the Individual tab or export cohorts by program/level from the Groups
                        tab. Leave filters blank to export all results for the selected mode.
                    </div>
                </div>

                <form id="exportDocumentsForm">
                    <ul class="nav nav-tabs mb-3" id="exportTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="individual-tab" data-bs-toggle="tab"
                                data-bs-target="#individual" type="button" role="tab" aria-controls="individual"
                                aria-selected="true">
                                Individual
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="groups-tab" data-bs-toggle="tab" data-bs-target="#groups"
                                type="button" role="tab" aria-controls="groups" aria-selected="false">
                                Groups
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-1">
                        <div class="tab-pane fade show active" id="individual" role="tabpanel"
                            aria-labelledby="individual-tab">
                            <div class="row g-4">
                                <div class="col-lg-4 col-md-6">
                                    <label for="academic_id" class="form-label fw-semibold">Academic ID</label>
                                    <input type="text" id="academic_id" name="academic_id" class="form-control"
                                        placeholder="Search by Academic ID">
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <label for="national_id" class="form-label fw-semibold">National ID</label>
                                    <input type="text" id="national_id" name="national_id" class="form-control"
                                        placeholder="Search by National ID">
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <label for="individual_term_id" class="form-label fw-semibold">Term <span
                                            class="text-danger">*</span></label>
                                    <select id="individual_term_id" name="term_id" class="form-select">
                                        <option value="">Select Term</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="groups" role="tabpanel" aria-labelledby="groups-tab">
                            <div class="row g-4">
                                <div class="col-lg-3 col-md-6">
                                    <label for="level_id" class="form-label fw-semibold">Level <span
                                            class="text-danger">*</span></label>
                                    <select id="level_id" name="level_id" class="form-select">
                                        <option value="">Select Level</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="program_id" class="form-label fw-semibold">Programs <span
                                            class="text-danger">*</span></label>
                                    <select id="program_id" name="program_id[]" class="form-select" multiple>
                                        <option value="">Select Programs</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <label for="group_term_id" class="form-label fw-semibold">Term <span
                                            class="text-danger">*</span></label>
                                    <select id="group_term_id" name="term_id" class="form-select">
                                        <option value="">Select Term</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="submit" id="exportDocsBtn" class="btn btn-success">
                            <i class="bx bx-download me-1"></i> Export
                        </button>
                        <a href="{{ route('enrollments.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Progress Modal -->
        <x-progress-modal modalId="exportDocsProgressModal" modalTitle="Exporting Enrollment Documents" />
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
            exportDocuments: {
                start: @json(route('enrollments.exportDocuments')),
                status: @json(route('enrollments.exportDocuments.status', ['uuid' => ':uuid'])),
                cancel: @json(route('enrollments.exportDocuments.cancel', ['uuid' => ':uuid'])),
                download: @json(route('enrollments.exportDocuments.download', ['uuid' => ':uuid']))
            },
            terms: @json(route('terms.all.with_inactive')),
            programs: @json(route('programs.all')),
            levels: @json(route('levels.all'))
        };

        const SELECTORS = {
            form: '#exportDocumentsForm',
            submitBtn: '#exportDocsBtn',
            individualTermSelect: '#individual_term_id',
            groupTermSelect: '#group_term_id',
            programSelect: '#program_id',
            levelSelect: '#level_id'
        };

        // ===========================
        // API SERVICE
        // ===========================
        const ApiService = {
            fetchTerms() {
                return Utils.get(ROUTES.terms);
            },
            fetchPrograms() {
                return Utils.get(ROUTES.programs);
            },
            fetchLevels() {
                return Utils.get(ROUTES.levels);
            },
            exportDocuments(formData) {
                return Utils.post(ROUTES.exportDocuments.start, formData);
            }
        };

        // ===========================
        // SELECT2 MANAGER
        // ===========================
        const Select2Manager = {
            init() {
                Utils.initSelect2(SELECTORS.individualTermSelect, {
                    placeholder: 'Select Term'
                });
                Utils.initSelect2(SELECTORS.groupTermSelect, {
                    placeholder: 'Select Term'
                });
                Utils.initSelect2(SELECTORS.programSelect, {
                    placeholder: 'Select Programs',
                    allowClear: true,
                    multiple: true
                });
                Utils.initSelect2(SELECTORS.levelSelect, {
                    placeholder: 'Select Level'
                });
            },

            async loadOptions() {
                try {
                    const [termsRes, programsRes, levelsRes] = await Promise.all([
                        ApiService.fetchTerms(),
                        ApiService.fetchPrograms(),
                        ApiService.fetchLevels()
                    ]);

                    if (Utils.isResponseSuccess(termsRes)) {
                        const terms = Utils.getResponseData(termsRes, []);
                        Utils.populateSelect(SELECTORS.individualTermSelect, terms, {
                            placeholder: 'Select Term'
                        }, true);
                        Utils.populateSelect(SELECTORS.groupTermSelect, terms, {
                            placeholder: 'Select Term'
                        }, true);
                    }

                    if (Utils.isResponseSuccess(programsRes)) {
                        Utils.populateSelect(SELECTORS.programSelect, Utils.getResponseData(programsRes, []), {
                            placeholder: 'Select Program'
                        }, true);
                    }

                    if (Utils.isResponseSuccess(levelsRes)) {
                        Utils.populateSelect(SELECTORS.levelSelect, Utils.getResponseData(levelsRes, []), {
                            placeholder: 'Select Level'
                        }, true);
                    }
                } catch (error) {
                    Utils.handleError(error, 'Failed to load filter options.');
                }
            }
        };

        // ===========================
        // EXPORT TASK MANAGER
        // ===========================
        const ExportTaskManager = Utils.createAsyncTaskManager({
            startRoute: ROUTES.exportDocuments.start,
            checkStatusRoute: ROUTES.exportDocuments.status,
            cancelRoute: ROUTES.exportDocuments.cancel,
            downloadRoute: ROUTES.exportDocuments.download,
            progressModalId: 'exportDocsProgressModal',
            taskName: 'Enrollment Documents Export',
            onStart() {
                // Nothing extra needed
            },
            completionFields: [{
                    key: 'total_students',
                    label: 'Total Students',
                    type: 'number'
                },
                {
                    key: 'documents_generated',
                    label: 'Documents Generated',
                    type: 'number'
                },
                {
                    key: 'skipped',
                    label: 'Skipped',
                    type: 'number'
                }
            ],
            translations: {
                processing: 'The export is being processed. This may take a few minutes for large cohorts.',
                taskInitializing: 'The export task is initializing.',
                taskPreparing: 'The export task is preparing the documents.',
                taskCompleted: 'The export task has completed.',
                taskFailed: 'The export task has failed.',
                statusCheckFailed: 'Failed to check the status of the export task.'
            }
        });

        // ===========================
        // EXPORT PAGE MANAGER
        // ===========================
        const ExportDocsPage = {
            init() {
                this.bindEvents();
                this.syncActiveTerm();
            },

            bindEvents() {
                $(SELECTORS.form).on('submit', this.handleSubmit.bind(this));
                $('button[data-bs-toggle="tab"]').on('shown.bs.tab', () => this.syncActiveTerm());
            },

            syncActiveTerm() {
                const isIndividual = $('#individual').hasClass('show active');
                $(SELECTORS.individualTermSelect).prop('disabled', !isIndividual);
                $(SELECTORS.groupTermSelect).prop('disabled', isIndividual);
                $(SELECTORS.programSelect).prop('disabled', isIndividual);
                $(SELECTORS.levelSelect).prop('disabled', isIndividual);
            },

            handleSubmit(e) {
                e.preventDefault();
                this.syncActiveTerm();

                const isIndividual = $('#individual').hasClass('show active');

                // Validate
                if (isIndividual) {
                    const termId = $(SELECTORS.individualTermSelect).val();
                    const academicId = $('#academic_id').val().trim();
                    const nationalId = $('#national_id').val().trim();

                    if (!termId) {
                        Utils.showError('Please select a term.');
                        return;
                    }
                    if (!academicId && !nationalId) {
                        Utils.showError('Please enter Academic ID or National ID.');
                        return;
                    }
                } else {
                    const termId = $(SELECTORS.groupTermSelect).val();
                    const programIds = $(SELECTORS.programSelect).val();
                    const levelId = $(SELECTORS.levelSelect).val();

                    if (!termId) {
                        Utils.showError('Please select a term.');
                        return;
                    }
                    if (!levelId) {
                        Utils.showError('Please select a level.');
                        return;
                    }
                    if (!programIds || programIds.length === 0) {
                        Utils.showError('Please select at least one program.');
                        return;
                    }
                }

                const formData = new FormData(document.querySelector(SELECTORS.form));

                ExportTaskManager.start(formData, {
                    button: $(SELECTORS.submitBtn)
                });
            }
        };

        // ===========================
        // INITIALIZATION
        // ===========================
        $(async () => {
            Select2Manager.init();
            await Select2Manager.loadOptions();
            ExportDocsPage.init();
            ExportTaskManager.init();
            Utils.hidePageLoader();
        });
    </script>
@endpush