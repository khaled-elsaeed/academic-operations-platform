@extends('layouts.home')

@section('title', __('Edit Available Course | AcadOps'))

@section('page-content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Page Header -->
        <x-ui.page-header :title="__('Edit Available Course')" :description="__('Manage eligibility and schedules for the selected course')" icon="bx bx-edit">
            <a href="{{ route('available_courses.index') }}" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back"></i> Back to List
            </a>
        </x-ui.page-header>

        <!-- Basic Course Information -->


        <!-- Eligibility Management -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">
                    <i class="bx bx-shield-quarter me-2"></i>
                    Eligibility Management
                </span>
                <button type="button" class="btn btn-sm btn-success" id="addEligibilityBtn">
                    <i class="bx bx-plus"></i> Add Eligibility
                </button>
            </div>
            <div class="card-body">
                <x-ui.datatable.table :headers="[__('Program'), __('Level'), __('Groups'), __('Actions')]" :columns="[
            ['data' => 'program', 'name' => 'program'],
            ['data' => 'level', 'name' => 'level'],
            ['data' => 'groups', 'name' => 'groups', 'orderable' => false],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false]
        ]"
                    :ajax-url="route('available_courses.eligibilities.datatable', $availableCourse->id)"
                    :table-id="'eligibility-table'" :filter-fields="[]" />
            </div>
        </div>

        <!-- Schedule Details Management -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">
                    <i class="bx bx-time me-2"></i>
                    Schedule Details
                </span>
                <button type="button" class="btn btn-sm btn-success" id="addScheduleBtn">
                    <i class="bx bx-plus"></i> Add Schedule
                </button>
            </div>
            <div class="card-body">
                <x-ui.datatable.table :headers="[__('Activity Type'), __('Location'), __('Groups'), __('Day'), __('Slots'), __('Capacity'), __('Action')]" :columns="[
            ['data' => 'activity_type', 'name' => 'activity_type'],
            ['data' => 'location', 'name' => 'location'],
            ['data' => 'groups', 'name' => 'groups', 'orderable' => false],
            ['data' => 'day', 'name' => 'day'],
            ['data' => 'slots', 'name' => 'slots', 'orderable' => false],
            ['data' => 'capacity', 'name' => 'capacity', 'orderable' => false],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false]
        ]"
                    :ajax-url="route('available_courses.schedules.datatable', $availableCourse->id)"
                    :table-id="'schedule-table'" :filter-fields="[]" />
            </div>
        </div>
    </div>

    <!-- Eligibility Modal -->
    <x-ui.modal id="eligibilityModal" :title="__('Add Eligibility')" size="md">
        <x-slot name="slot">
            <form id="eligibilityForm" novalidate>
                <input type="hidden" id="eligibility_id" name="eligibility_id">

                <div class="mb-3">
                    <label for="program_id" class="form-label fw-semibold">
                        Program <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="program_id" name="program_id" required>
                        <option value="">Select Program</option>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-3">
                    <label for="level_id" class="form-label fw-semibold">
                        Level <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="level_id" name="level_id" required>
                        <option value="">Select Level</option>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-3">
                    <label for="group_numbers" class="form-label fw-semibold">
                        Group <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="group_numbers" name="group_numbers" required>
                        <option value="">Select Group</option>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="bx bx-x me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-primary" id="saveEligibilityBtn">
                <i class="bx bx-save me-1"></i>Save
            </button>
        </x-slot>
    </x-ui.modal>

    <!-- Edit Eligibility Modal -->
    <x-ui.modal id="editEligibilityModal" :title="__('Edit Eligibility')" size="md">
        <x-slot name="slot">
            <form id="editEligibilityForm" novalidate>
                <input type="hidden" id="edit_eligibility_id" name="eligibility_id">
                <input type="hidden" id="edit_available_course_eligibility_id" name="available_course_eligibility_id">

                <div class="mb-3">
                    <label for="edit_eligibility_program_id" class="form-label fw-semibold">
                        Program <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="edit_eligibility_program_id" name="program_id" required>
                        <option value="">Select Program</option>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-3">
                    <label for="edit_eligibility_level_id" class="form-label fw-semibold">
                        Level <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="edit_eligibility_level_id" name="level_id" required>
                        <option value="">Select Level</option>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>

                <div class="mb-3">
                    <label for="edit_eligibility_group_numbers" class="form-label fw-semibold">
                        Group <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="edit_eligibility_group_numbers" name="group_numbers" required>
                        <option value="">Select Group</option>
                    </select>
                    <div class="invalid-feedback"></div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="bx bx-x me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-primary" id="saveEditEligibilityBtn">
                <i class="bx bx-save me-1"></i>Update
            </button>
        </x-slot>
    </x-ui.modal>

    <!-- Add Schedule Modal -->
    <x-ui.modal id="addScheduleModal" :title="__('Add Schedule')" size="lg">
        <x-slot name="slot">
            <form id="addScheduleForm" novalidate>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="add_schedule_template_id" class="form-label fw-semibold">
                            Schedule Template <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="add_schedule_template_id" name="schedule_template_id" required>
                            <option value="">Select Schedule</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="add_activity_type" class="form-label fw-semibold">
                            Activity Type <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="add_activity_type" name="activity_type" required>
                            <option value="">Select Type</option>
                            <option value="lecture">Lecture</option>
                            <option value="tutorial">Tutorial</option>
                            <option value="lab">Lab</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="add_schedule_group_numbers" class="form-label fw-semibold">
                            Group <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="add_schedule_group_numbers" name="group_numbers" required>
                            <option value="">Select Group</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="add_location" class="form-label fw-semibold">
                            Location
                        </label>
                        <input type="text" class="form-control" id="add_location" name="location"
                            placeholder="Enter location">
                    </div>

                    <div class="col-md-6">
                        <label for="add_program_id" class="form-label fw-semibold">
                            Program <span class="text-muted small">(optional)</span>
                        </label>
                        <select class="form-select" id="add_program_id" name="program_id">
                            <option value="">Select Program</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="add_level_id" class="form-label fw-semibold">
                            Level <span class="text-muted small">(optional)</span>
                        </label>
                        <select class="form-select" id="add_level_id" name="level_id">
                            <option value="">Select Level</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="add_schedule_day_id" class="form-label fw-semibold">
                            Day <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="add_schedule_day_id" name="schedule_day_id" required disabled>
                            <option value="">Select Day</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="add_schedule_slot_ids" class="form-label fw-semibold">
                            Time Slots <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="add_schedule_slot_ids" name="schedule_slot_ids[]" multiple required
                            disabled style="min-height: 100px;">
                            <option value="">Select Slots</option>
                        </select>
                        <div class="form-text small">Select consecutive slots only</div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="add_min_capacity" class="form-label fw-semibold">
                            Min Capacity <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="add_min_capacity" name="min_capacity" min="1"
                            required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="add_max_capacity" class="form-label fw-semibold">
                            Max Capacity <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="add_max_capacity" name="max_capacity" min="1"
                            required>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="bx bx-x me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-primary" id="saveAddScheduleBtn">
                <i class="bx bx-save me-1"></i>Add Schedule
            </button>
        </x-slot>
    </x-ui.modal>

    <!-- Edit Schedule Modal -->
    <x-ui.modal id="editScheduleModal" :title="__('Edit Schedule')" size="lg">
        <x-slot name="slot">
            <form id="editScheduleForm" novalidate>
                <input type="hidden" id="edit_schedule_id" name="schedule_id">
                <input type="hidden" id="edit_available_course_schedule_id" name="available_course_schedule_id">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="edit_schedule_template_id" class="form-label fw-semibold">
                            Schedule Template <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="edit_schedule_template_id" name="schedule_template_id" required>
                            <option value="">Select Schedule</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="edit_activity_type" class="form-label fw-semibold">
                            Activity Type <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="edit_activity_type" name="activity_type" required>
                            <option value="">Select Type</option>
                            <option value="lecture">Lecture</option>
                            <option value="tutorial">Tutorial</option>
                            <option value="lab">Lab</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="edit_schedule_group_number" class="form-label fw-semibold">
                            Group <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="edit_schedule_group_number" name="group_number" required>
                            <option value="">Select Group</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="edit_location" class="form-label fw-semibold">
                            Location
                        </label>
                        <input type="text" class="form-control" id="edit_location" name="location"
                            placeholder="Enter location">
                    </div>

                    <div class="col-md-6">
                        <label for="edit_program_id" class="form-label fw-semibold">
                            Program <span class="text-muted small">(optional)</span>
                        </label>
                        <select class="form-select" id="edit_program_id" name="program_id">
                            <option value="">Select Program</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="edit_level_id" class="form-label fw-semibold">
                            Level <span class="text-muted small">(optional)</span>
                        </label>
                        <select class="form-select" id="edit_level_id" name="level_id">
                            <option value="">Select Level</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="edit_schedule_day_id" class="form-label fw-semibold">
                            Day <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="edit_schedule_day_id" name="schedule_day_id" required disabled>
                            <option value="">Select Day</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="edit_schedule_slot_ids" class="form-label fw-semibold">
                            Time Slots <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="edit_schedule_slot_ids" name="schedule_slot_ids[]" multiple required
                            disabled style="min-height: 100px;">
                            <option value="">Select Slots</option>
                        </select>
                        <div class="form-text small">Select consecutive slots only</div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="edit_min_capacity" class="form-label fw-semibold">
                            Min Capacity <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="edit_min_capacity" name="min_capacity" min="1"
                            required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="col-md-6">
                        <label for="edit_max_capacity" class="form-label fw-semibold">
                            Max Capacity <span class="text-danger">*</span>
                        </label>
                        <input type="number" class="form-control" id="edit_max_capacity" name="max_capacity" min="1"
                            required>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="bx bx-x me-1"></i>Cancel
            </button>
            <button type="button" class="btn btn-primary" id="saveEditScheduleBtn">
                <i class="bx bx-save me-1"></i>Update Schedule
            </button>
        </x-slot>
    </x-ui.modal>

@endsection

@push('styles')
    <style>
        .badge-group {
            display: inline-block;
            margin: 2px;
        }

        .capacity-badge {
            font-size: 0.85rem;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('js/utils.js') }}"></script>
    <script>
        'use strict';

        // ===========================
        // ROUTES CONFIGURATION
        // ===========================
        const ROUTES = {
            programs: { all: @json(route('programs.all')) },
            levels: { all: @json(route('levels.all')) },
            schedules: {
                all: @json(route('schedules.all')),
                daysSlots: '{{ route('schedules.days-slots', ':id') }}'
            },
            availableCourse: {
                show: @json(route('available_courses.show', $availableCourse->id)),
                eligibility: {
                    store: @json(route('available_courses.eligibilities.store', $availableCourse->id)),
                    delete: '{{ route('available_courses.eligibilities.delete', [$availableCourse->id, ':id']) }}',
                    show: '{{ route('available_courses.eligibilities.show', [$availableCourse->id, ':id']) }}',
                    update: '{{ route('available_courses.eligibilities.update', [$availableCourse->id, ':id']) }}'
                },
                schedule: {
                    show: '{{ route('available_courses.schedules.show', [$availableCourse->id, ':id']) }}',
                    store: @json(route('available_courses.schedules.store', $availableCourse->id)),
                    update: '{{ route('available_courses.schedules.update', [$availableCourse->id, ':id']) }}',
                    delete: '{{ route('available_courses.schedules.delete', [$availableCourse->id, ':id']) }}'
                }
            }
        };

        // ===========================
        // API SERVICE
        // ===========================
        const ApiService = {

            fetchPrograms() {
                return Utils.get(ROUTES.programs.all);
            },
            fetchLevels() {
                return Utils.get(ROUTES.levels.all);
            },
            fetchSchedules() {
                return Utils.get(ROUTES.schedules.all);
            },
            fetchAvailableCourse() {
                return Utils.get(ROUTES.availableCourse.show);
            },
            fetchScheduleDaysSlots(scheduleId) {
                return Utils.get(Utils.replaceRouteId(ROUTES.schedules.daysSlots, scheduleId));
            },
            fetchScheduleDetails(scheduleId) {
                return Utils.get(Utils.replaceRouteId(ROUTES.availableCourse.schedule.show, scheduleId));
            },
            storeEligibility(data) {
                return Utils.post(ROUTES.availableCourse.eligibility.store, data);
            },
            deleteEligibility(id) {
                return Utils.delete(Utils.replaceRouteId(ROUTES.availableCourse.eligibility.delete, id));
            },
            fetchEligibility(id) {
                return Utils.get(Utils.replaceRouteId(ROUTES.availableCourse.eligibility.show, id));
            },
            updateEligibility(id, data) {
                return Utils.put(Utils.replaceRouteId(ROUTES.availableCourse.eligibility.update, id), data);
            },
            storeSchedule(data) {
                return Utils.post(ROUTES.availableCourse.schedule.store, data);
            },
            updateSchedule(id, data) {
                return Utils.put(Utils.replaceRouteId(ROUTES.availableCourse.schedule.update, id), data);
            },
            deleteSchedule(id) {
                return Utils.delete(Utils.replaceRouteId(ROUTES.availableCourse.schedule.delete, id));
            }
        };

        // ===========================
        // DATA STORE
        // ===========================
        const DataStore = {
            programs: [],
            levels: [],
            schedules: [],
            scheduleDaysCache: {},

            async initialize() {
                try {
                    const [programsRes, levelsRes, schedulesRes, availableCourseRes] = await Promise.all([
                        ApiService.fetchPrograms(),
                        ApiService.fetchLevels(),
                        ApiService.fetchSchedules(),
                        ApiService.fetchAvailableCourse()
                    ]);

                    if (!Utils.isResponseSuccess(programsRes) || !Utils.isResponseSuccess(levelsRes) ||
                        !Utils.isResponseSuccess(schedulesRes) || !Utils.isResponseSuccess(availableCourseRes)) {
                        throw new Error('Failed to load required data');
                    }

                    this.programs = Utils.getResponseData(programsRes);
                    this.levels = Utils.getResponseData(levelsRes);
                    this.schedules = Utils.getResponseData(schedulesRes);
                    const availableCourse = Utils.getResponseData(availableCourseRes);

                    return true;

                    return true;
                } catch (error) {
                    Utils.handleError(error);
                    return false;
                }
            },

            populateGroups($select, max = 30, selected = null) {
                const groups = [];
                for (let i = 1; i <= max; i++) {
                    groups.push({ id: i, name: @json(__('Group')) + ' ' + i });
                }
                Utils.populateSelect($select, groups, { placeholder: @json(__('Select Group')), selected: selected });
            }
        };

        // ===========================
        // SELECT MANAGER
        // ===========================
        const SelectManager = {
            init() {
            },

            initModal($modal) {
                $modal.find('select').each(function () {
                    const isMultiple = $(this).prop('multiple');
                    Utils.initSelect2($(this), {
                        placeholder: $(this).find('option:first').text(),
                        dropdownParent: $modal,
                        closeOnSelect: !isMultiple
                    });
                });
            },

            populateAddEligibilityModal() {
                Utils.populateSelect('#program_id', DataStore.programs, { placeholder: @json(__('Select Program')) });
                Utils.populateSelect('#level_id', DataStore.levels, { placeholder: @json(__('Select Level')) });
                DataStore.populateGroups($('#group_numbers'));
            },

            populateEditEligibilityModal(data) {
                Utils.populateSelect('#edit_eligibility_program_id', DataStore.programs, { placeholder: @json(__('Select Program')), selected: data.program_id });
                Utils.populateSelect('#edit_eligibility_level_id', DataStore.levels, { placeholder: @json(__('Select Level')), selected: data.level_id });
                DataStore.populateGroups($('#edit_eligibility_group_numbers'), 30, data.group);
            },

            populateAddScheduleModal() {
                Utils.populateSelect('#add_schedule_template_id', DataStore.schedules, { placeholder: @json(__('Select Schedule')) });
                Utils.populateSelect('#add_program_id', DataStore.programs, { placeholder: @json(__('Select Program')) });
                Utils.populateSelect('#add_level_id', DataStore.levels, { placeholder: @json(__('Select Level')) });
                DataStore.populateGroups($('#add_schedule_group_numbers'));
            },

            populateEditScheduleModal(data) {
                Utils.populateSelect('#edit_schedule_template_id', DataStore.schedules, { placeholder: @json(__('Select Schedule')), selected: data.schedule_template_id });
                Utils.populateSelect('#edit_program_id', DataStore.programs, { placeholder: @json(__('Select Program')), selected: data.program_id });
                Utils.populateSelect('#edit_level_id', DataStore.levels, { placeholder: @json(__('Select Level')), selected: data.level_id });
                DataStore.populateGroups($('#edit_schedule_group_number'), 30, data.group_number || data.group);
            }
        };

        // ===========================
        // MODAL MANAGERS
        // ===========================
        const EligibilityModal = Utils.createModalManager('eligibilityModal');
        const AddScheduleModal = Utils.createModalManager('addScheduleModal');
        const EditScheduleModal = Utils.createModalManager('editScheduleModal');
        const EditEligibilityModal = Utils.createModalManager('editEligibilityModal');

        // ===========================
        // ELIGIBILITY MANAGER
        // ===========================
        const EligibilityManager = {
            init() {
                $('#addEligibilityBtn').on('click', () => this.showAddModal());
                $('#saveEligibilityBtn').on('click', () => this.save());
                $('#saveEditEligibilityBtn').on('click', () => this.saveEdit());
                $(document).on('click', '.editEligibilityBtn', (e) => this.showEditModal(e));
                $(document).on('click', '.deleteEligibilityBtn', (e) => this.delete(e));
            },

            showAddModal() {
                $('#eligibilityForm')[0].reset();
                $('#eligibility_id').val('');

                // Populate dropdowns
                SelectManager.populateAddEligibilityModal();

                EligibilityModal.show();
                setTimeout(() => SelectManager.initModal($('#eligibilityModal')), 100);
            },

            async save() {
                const $btn = $('#saveEligibilityBtn');
                const loadingConfig = {
                    loadingText: @json(__('Saving...')),
                    loadingIcon: 'bx bx-loader-alt bx-spin me-1',
                    normalText: @json(__('Save')),
                    normalIcon: 'bx bx-save me-1'
                };

                const data = {
                    program_id: $('#program_id').val(),
                    level_id: $('#level_id').val(),
                    group_numbers: [$('#group_numbers').val()]
                };

                if (!data.program_id || !data.level_id || !data.group_numbers[0]) {
                    Utils.showError(@json(__('Please fill all required fields')));
                    return;
                }

                Utils.setLoadingState($btn, true, loadingConfig);

                try {
                    const response = await ApiService.storeEligibility(data);
                    Utils.showSuccess(response.message || @json(__('Eligibility added successfully')));
                    EligibilityModal.hide();
                    Utils.reloadDataTable('eligibility-table');
                } catch (error) {
                    Utils.handleError(error);
                } finally {
                    Utils.setLoadingState($btn, false, loadingConfig);
                }
            },

            async delete(e) {
                const id = Utils.getElementData(e.currentTarget, ['id']);
                const { isConfirmed } = await Utils.showConfirmDialog({
                    title: @json(__('Are you sure?')),
                    text: @json(__('This eligibility will be deleted!')),
                    confirmButtonText: @json(__('Yes, delete it!'))
                });

                if (isConfirmed) {
                    try {
                        const response = await ApiService.deleteEligibility(id);
                        Utils.showSuccess(response.message || @json(__('Eligibility deleted successfully')));
                        Utils.reloadDataTable('eligibility-table');
                    } catch (error) {
                        Utils.handleError(error);
                    }
                }
            },

            async showEditModal(e) {
                const id = Utils.getElementData(e.currentTarget, ['id']);

                try {
                    const response = await ApiService.fetchEligibility(id);
                    if (!Utils.isResponseSuccess(response)) {
                        throw new Error('Failed to load eligibility details');
                    }

                    const data = Utils.getResponseData(response);

                    $('#edit_eligibility_id').val(id);
                    $('#edit_available_course_eligibility_id').val(data.id);
                    
                    // Populate dropdowns for edit
                    SelectManager.populateEditEligibilityModal(data);

                    EditEligibilityModal.show();
                    setTimeout(() => SelectManager.initModal($('#editEligibilityModal')), 100);

                } catch (error) {
                    Utils.handleError(error);
                }
            },

            async saveEdit() {
                const $btn = $('#saveEditEligibilityBtn');
                const id = $('#edit_eligibility_id').val();
                
                const loadingConfig = {
                    loadingText: @json(__('Updating...')),
                    loadingIcon: 'bx bx-loader-alt bx-spin me-1',
                    normalText: @json(__('Update')),
                    normalIcon: 'bx bx-save me-1'
                };

                const data = {
                    program_id: $('#edit_eligibility_program_id').val(),
                    level_id: $('#edit_eligibility_level_id').val(),
                    group_numbers: [$('#edit_eligibility_group_numbers').val()]
                };

                if (!data.program_id || !data.level_id || !data.group_numbers[0]) {
                    Utils.showError(@json(__('Please fill all required fields')));
                    return;
                }

                Utils.setLoadingState($btn, true, loadingConfig);

                try {
                    const response = await ApiService.updateEligibility(id, data);
                    Utils.showSuccess(response.message || @json(__('Eligibility updated successfully')));
                    EditEligibilityModal.hide();
                    Utils.reloadDataTable('eligibility-table');
                } catch (error) {
                    Utils.handleError(error);
                } finally {
                    Utils.setLoadingState($btn, false, loadingConfig);
                }
            }
        };

        // ===========================
        // SCHEDULE MANAGER
        // ===========================
        const ScheduleManager = {
            init() {
                $('#addScheduleBtn').on('click', () => this.showAddModal());
                $('#saveAddScheduleBtn').on('click', () => this.saveAdd());
                $('#saveEditScheduleBtn').on('click', () => this.saveEdit());

                $(document).on('click', '.editScheduleBtn', (e) => this.showEditModal(e));
                $(document).on('click', '.deleteScheduleBtn', (e) => this.delete(e));

                // Schedule template change handlers
                $(document).on('change', '#add_schedule_template_id', (e) => this.loadDaysSlots(e.target, 'add'));
                $(document).on('change', '#edit_schedule_template_id', (e) => this.loadDaysSlots(e.target, 'edit'));

                // Day change handlers
                $(document).on('change', '#add_schedule_day_id', (e) => this.loadSlots(e.target, 'add'));
                $(document).on('change', '#edit_schedule_day_id', (e) => this.loadSlots(e.target, 'edit'));
            },

            showAddModal() {
                $('#addScheduleForm')[0].reset();
                $('#add_schedule_day_id, #add_schedule_slot_ids').prop('disabled', true);

                // Populate dropdowns
                SelectManager.populateAddScheduleModal();

                AddScheduleModal.show();
                setTimeout(() => SelectManager.initModal($('#addScheduleModal')), 100);
            },

            async showEditModal(e) {
                const id = Utils.getElementData(e.currentTarget, ['id']);

                try {
                    const response = await ApiService.fetchScheduleDetails(id);
                    if (!Utils.isResponseSuccess(response)) {
                        throw new Error('Failed to load schedule details');
                    }

                    const data = Utils.getResponseData(response);

                    $('#edit_schedule_id').val(id);
                    $('#edit_available_course_schedule_id').val(data.id);
                    $('#edit_activity_type').val(data.activity_type);
                    $('#edit_location').val(data.location);
                    $('#edit_min_capacity').val(data.min_capacity);
                    $('#edit_max_capacity').val(data.max_capacity);

                    // Populate dropdowns
                    SelectManager.populateEditScheduleModal(data);

                    EditScheduleModal.show();

                    // Load days/slots after modal is shown
                    setTimeout(async () => {
                        if (data.schedule_template_id) {
                            await this.loadDaysSlots($('#edit_schedule_template_id')[0], 'edit');

                            setTimeout(() => {
                                if (data.day_of_week) {
                                    $('#edit_schedule_day_id').val(data.day_of_week).trigger('change');

                                    setTimeout(() => {
                                        if (data.slot_ids && data.slot_ids.length > 0) {
                                            $('#edit_schedule_slot_ids').val(data.slot_ids).trigger('change');
                                        }

                                        setTimeout(() => {
                                            SelectManager.initModal($('#editScheduleModal'));
                                        }, 100);
                                    }, 500);
                                }
                            }, 500);
                        } else {
                            SelectManager.initModal($('#editScheduleModal'));
                        }
                    }, 200);

                } catch (error) {
                    Utils.handleError(error);
                }
            },

            async loadDaysSlots(element, prefix) {
                const scheduleId = $(element).val();
                const $daySelect = $(`#${prefix}_schedule_day_id`);
                const $slotSelect = $(`#${prefix}_schedule_slot_ids`);

                $daySelect.empty().prop('disabled', true).append(`<option value="">${Utils.escapeHtml(@json(__('Select Day')))}</option>`);
                $slotSelect.empty().prop('disabled', true);

                if (!scheduleId) return;

                try {
                    if (DataStore.scheduleDaysCache[scheduleId]) {
                        this.populateDays(scheduleId, $daySelect);
                        return;
                    }

                    const response = await ApiService.fetchScheduleDaysSlots(scheduleId);

                    if (Utils.isResponseSuccess(response)) {
                        DataStore.scheduleDaysCache[scheduleId] = Utils.getResponseData(response);
                        this.populateDays(scheduleId, $daySelect);
                    }
                } catch (error) {
                    Utils.handleError(error);
                }
            },

            populateDays(scheduleId, $daySelect) {
                const days = DataStore.scheduleDaysCache[scheduleId];
                if (!days || !Array.isArray(days)) return;

                days.forEach(dayObj => {
                    const dayName = dayObj.day_of_week.charAt(0).toUpperCase() + dayObj.day_of_week.slice(1);
                    $daySelect.append(`<option value="${Utils.escapeHtml(dayObj.day_of_week)}">${Utils.escapeHtml(dayName)}</option>`);
                });

                $daySelect.prop('disabled', false);
            },

            loadSlots(element, prefix) {
                const $daySelect = $(element);
                const scheduleId = $(`#${prefix}_schedule_template_id`).val();
                const selectedDay = $daySelect.val();
                const $slotSelect = $(`#${prefix}_schedule_slot_ids`);

                $slotSelect.empty().prop('disabled', true);

                if (!scheduleId || !selectedDay) return;

                const days = DataStore.scheduleDaysCache[scheduleId];
                if (!days) return;

                const dayData = days.find(d => d.day_of_week === selectedDay);
                if (!dayData || !dayData.slots) return;

                dayData.slots.forEach(slot => {
                    const label = slot.label || `${slot.start_time} - ${slot.end_time}`;
                    $slotSelect.append(`<option value="${slot.id}" data-order="${slot.slot_order}">${Utils.escapeHtml(@json(__('Slot')))} ${slot.slot_order}: ${Utils.escapeHtml(label)}</option>`);
                });

                $slotSelect.prop('disabled', false);
            },

            validateConsecutiveSlots(slotIds, $slotSelect) {
                if (!slotIds || slotIds.length === 0) return true;

                const slots = $slotSelect.find('option:selected').map(function () {
                    return parseInt($(this).attr('data-order'));
                }).get().sort((a, b) => a - b);

                for (let i = 1; i < slots.length; i++) {
                    if (slots[i] !== slots[i - 1] + 1) {
                        return false;
                    }
                }

                return true;
            },

            async saveAdd() {
                const $btn = $('#saveAddScheduleBtn');
                const loadingConfig = {
                    loadingText: @json(__('Adding...')),
                    loadingIcon: 'bx bx-loader-alt bx-spin me-1',
                    normalText: @json(__('Add Schedule')),
                    normalIcon: 'bx bx-save me-1'
                };

                const data = {
                    schedule_template_id: $('#add_schedule_template_id').val(),
                    activity_type: $('#add_activity_type').val(),
                    group_numbers: [$('#add_schedule_group_numbers').val()],
                    location: $('#add_location').val(),
                    program_id: $('#add_program_id').val() || null,
                    level_id: $('#add_level_id').val() || null,
                    schedule_day_id: $('#add_schedule_day_id').val(),
                    schedule_slot_ids: $('#add_schedule_slot_ids').val(),
                    min_capacity: $('#add_min_capacity').val(),
                    max_capacity: $('#add_max_capacity').val()
                };

                if (!data.schedule_template_id || !data.activity_type || !data.group_numbers[0] ||
                    !data.schedule_day_id || !data.schedule_slot_ids || data.schedule_slot_ids.length === 0 ||
                    !data.min_capacity || !data.max_capacity) {
                    Utils.showError(@json(__('Please fill all required fields')));
                    return;
                }

                if (!this.validateConsecutiveSlots(data.schedule_slot_ids, $('#add_schedule_slot_ids'))) {
                    Utils.showError(@json(__('Selected slots must be consecutive')));
                    return;
                }

                Utils.setLoadingState($btn, true, loadingConfig);

                try {
                    const response = await ApiService.storeSchedule(data);
                    Utils.showSuccess(response.message || @json(__('Schedule added successfully')));
                    AddScheduleModal.hide();
                    Utils.reloadDataTable('schedule-table');
                } catch (error) {
                    Utils.handleError(error);
                } finally {
                    Utils.setLoadingState($btn, false, loadingConfig);
                }
            },

            async saveEdit() {
                const $btn = $('#saveEditScheduleBtn');
                const loadingConfig = {
                    loadingText: @json(__('Updating...')),
                    loadingIcon: 'bx bx-loader-alt bx-spin me-1',
                    normalText: @json(__('Update Schedule')),
                    normalIcon: 'bx bx-save me-1'
                };

                const id = $('#edit_schedule_id').val();
                const data = {
                    schedule_template_id: $('#edit_schedule_template_id').val(),
                    activity_type: $('#edit_activity_type').val(),
                    group_number: $('#edit_schedule_group_number').val(),
                    location: $('#edit_location').val(),
                    program_id: $('#edit_program_id').val() || null,
                    level_id: $('#edit_level_id').val() || null,
                    schedule_day_id: $('#edit_schedule_day_id').val(),
                    schedule_slot_ids: $('#edit_schedule_slot_ids').val(),
                    min_capacity: $('#edit_min_capacity').val(),
                    max_capacity: $('#edit_max_capacity').val()
                };

                if (!data.schedule_template_id || !data.activity_type || !data.group_number ||
                    !data.schedule_day_id || !data.schedule_slot_ids || data.schedule_slot_ids.length === 0 ||
                    !data.min_capacity || !data.max_capacity) {
                    Utils.showError(@json(__('Please fill all required fields')));
                    return;
                }

                if (!this.validateConsecutiveSlots(data.schedule_slot_ids, $('#edit_schedule_slot_ids'))) {
                    Utils.showError(@json(__('Selected slots must be consecutive')));
                    return;
                }

                Utils.setLoadingState($btn, true, loadingConfig);

                try {
                    const response = await ApiService.updateSchedule(id, data);
                    Utils.showSuccess(response.message || @json(__('Schedule updated successfully')));
                    EditScheduleModal.hide();
                    Utils.reloadDataTable('schedule-table');
                } catch (error) {
                    Utils.handleError(error);
                } finally {
                    Utils.setLoadingState($btn, false, loadingConfig);
                }
            },

            async delete(e) {
                const id = Utils.getElementData(e.currentTarget, ['id']);
                const { isConfirmed } = await Utils.showConfirmDialog({
                    title: @json(__('Are you sure?')),
                    text: @json(__('This schedule will be deleted!')),
                    confirmButtonText: @json(__('Yes, delete it!'))
                });

                if (isConfirmed) {
                    try {
                        const response = await ApiService.deleteSchedule(id);
                        Utils.showSuccess(response.message || @json(__('Schedule deleted successfully')));
                        Utils.reloadDataTable('schedule-table');
                    } catch (error) {
                        Utils.handleError(error);
                    }
                }
            }
        };

        // ===========================
        // INITIALIZATION
        // ===========================
        $(() => {
            DataStore.initialize().then((success) => {
                if (success) {
                    SelectManager.init();
                    EligibilityManager.init();
                    ScheduleManager.init();
                    Utils.hidePageLoader();
                }
            });
        });
    </script>
@endpush