@extends('layouts.home')

@section('title', 'Weekly Teaching Schedule | AcadOps')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/weekly-teaching.css') }}">
@endpush

@section('page-content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Statistics Cards -->
        <div class="row mb-4 g-3" id="statsRow" style="display: none;">
            <div class="col-12 col-sm-6 col-lg-3">
                <x-ui.card.stat2 color="primary" icon="bx bx-book-open" :label="'Total Courses'" id="stat_total_courses" />
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <x-ui.card.stat2 color="info" icon="bx bx-calendar-check" :label="'Total Sessions'"
                    id="stat_total_sessions" />
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <x-ui.card.stat2 color="success" icon="bx bx-chalkboard" :label="'Lectures'" id="stat_lectures" />
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <x-ui.card.stat2 color="warning" icon="bx bx-group" :label="'Tutorials / Labs'" id="stat_tutorials_labs" />
            </div>
        </div>

        <!-- Page Header -->
        <x-ui.page-header :title="'Weekly Teaching Schedule'" :description="'View and filter weekly teaching schedules by program, and level.'" icon="bx bx-calendar-week">
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('schedules.index') }}" class="btn btn-outline-secondary">
                    <i class="bx bx-list-ul"></i> All Schedules
                </a>
                <button class="btn btn-primary" id="refreshScheduleBtn">
                    <i class="bx bx-refresh"></i> Refresh
                </button>
            </div>
        </x-ui.page-header>

        <!-- Filter Card -->
        <div class="card filter-card mb-4">
            <div class="card-header bg-light d-flex align-items-center">
                <i class="bx bx-filter-alt me-2 text-primary"></i>
                <h5 class="mb-0 text-dark">Filter Schedule</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="filter_schedule" class="form-label fw-semibold text-dark">
                            <i class="bx bx-calendar-alt me-1"></i> Schedule Template
                        </label>
                        <select class="form-select select2-schedule" id="filter_schedule" name="schedule_id">
                            <option value="">Select Schedule</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_program" class="form-label fw-semibold text-dark">
                            <i class="bx bx-book-bookmark me-1"></i> Program
                        </label>
                        <select class="form-select select2-program" id="filter_program" name="program_id">
                            <option value="">All Programs</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_level" class="form-label fw-semibold text-dark">
                            <i class="bx bx-layer me-1"></i> Level
                        </label>
                        <select class="form-select select2-level" id="filter_level" name="level_id">
                            <option value="">All Levels</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="filter_group" class="form-label fw-semibold text-dark">
                            <i class="bx bx-group me-1"></i> Group
                        </label>
                        <select class="form-select select2-group" id="filter_group" name="group">
                            <option value="">All Groups</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button class="btn btn-outline-secondary" id="clearFiltersBtn" type="button">
                            <i class="bx bx-x"></i> Clear Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="schedule-legend" id="scheduleLegend" style="display: none;">
            <div class="legend-item">
                <div class="legend-color lecture"></div>
                <span>Lecture</span>
            </div>
            <div class="legend-item">
                <div class="legend-color tutorial"></div>
                <span>Tutorial</span>
            </div>
            <div class="legend-item">
                <div class="legend-color lab"></div>
                <span>Lab</span>
            </div>
        </div>

        <!-- Schedule Grid Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bx bx-grid-alt me-2"></i>
                    <span id="scheduleTitle">Weekly Schedule</span>
                </h5>
                <div class="view-switcher btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary btn-sm active" data-view="grid">
                        <i class="bx bx-grid-alt"></i> Grid
                    </button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-view="list">
                        <i class="bx bx-list-ul"></i> List
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Empty State (shown initially) -->
                <div class="empty-schedule" id="emptyState">
                    <i class="bx bx-calendar-exclamation"></i>
                    <h5>No Schedule Selected</h5>
                    <p class="text-muted">Please select a schedule template from the filters above to view the
                        weekly teaching schedule.</p>
                </div>

                <!-- Loading State -->
                <div id="loadingState" style="display: none;">
                    <div class="schedule-grid">
                        <table class="schedule-table table">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">Day</th>
                                    <th class="text-center time-slot-header">Period 1</th>
                                    <th class="text-center time-slot-header">Period 2</th>
                                    <th class="text-center time-slot-header">Period 3</th>
                                    <th class="text-center time-slot-header">Period 4</th>
                                    <th class="text-center time-slot-header">Period 5</th>
                                    <th class="text-center time-slot-header">Period 6</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
                                @endphp
                                @foreach($days as $day)
                                    <tr>
                                        <td class="day-cell fw-semibold">
                                            <div class="schedule-skeleton" style="height: 20px; width: 80px;"></div>
                                        </td>
                                        @for($j = 0; $j < 6; $j++)
                                            <td class="schedule-cell">
                                                <div class="schedule-skeleton"></div>
                                            </td>
                                        @endfor
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Grid View -->
                <div class="table-responsive-xl" id="gridView" style="display: none;">
                    <div class="schedule-grid-wrapper">
                        <table class="schedule-table table" id="scheduleTable">
                            <thead id="scheduleTableHead">
                                <tr>
                                    <th style="width: 100px;" class="day-header sticky-column">Day</th>
                                    <!-- Time slot headers will be dynamically populated -->
                                </tr>
                            </thead>
                            <tbody id="scheduleTableBody">
                                <!-- Dynamically populated -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- List View -->
                <div id="listView" style="display: none;">
                    <div class="accordion" id="dayAccordion">
                        <!-- Dynamically populated -->
                    </div>
                </div>

                <!-- No Data State -->
                <div class="empty-schedule" id="noDataState" style="display: none;">
                    <i class="bx bx-calendar-x"></i>
                    <h5>No Sessions Found</h5>
                    <p class="text-muted">No teaching sessions found for the selected filters. Try adjusting your filters or
                        selecting a different schedule.</p>
                </div>
            </div>
        </div>

        <!-- Session Details Modal -->
        <x-ui.modal id="sessionDetailModal" title="Session Details" size="md">
            <x-slot name="slot">
                <div id="sessionDetailContent">
                    <div class="mb-3">
                        <h5 class="mb-1" id="modal_course_code">-</h5>
                        <p class="text-muted mb-0" id="modal_course_name">-</p>
                    </div>
                    <hr>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-muted small">Day</div>
                            <div class="fw-semibold" id="modal_day">-</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Time</div>
                            <div class="fw-semibold" id="modal_time">-</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Activity Type</div>
                            <div id="modal_activity_type">-</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Group</div>
                            <div class="fw-semibold" id="modal_group">-</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Location</div>
                            <div class="fw-semibold" id="modal_location">-</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Program</div>
                            <div class="fw-semibold" id="modal_program">-</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Level</div>
                            <div class="fw-semibold" id="modal_level">-</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Capacity</div>
                            <div class="fw-semibold" id="modal_capacity">-</div>
                        </div>
                    </div>
                    <div id="modal_slots_info" class="mt-3" style="display: none;">
                        <!-- Multiple slots info will be inserted here -->
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
    <script src="{{ asset('js/utils.js') }}?v={{ config('app.version') }}"></script>
    <script>
        'use strict';

        // ===========================
        // GLOBAL STATE
        // ===========================
        const AppState = {
            currentView: 'grid',
            currentData: null,
            filters: {
                schedule_id: null,
                program_id: null,
                level_id: null,
                group: null
            },

            reset() {
                this.currentData = null;
                this.filters = {
                    schedule_id: null,
                    program_id: null,
                    level_id: null,
                    group: null
                };
            },

            setFilter(key, value) {
                this.filters[key] = value || null;
            },

            getFilters() {
                return { ...this.filters };
            }
        };

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
            schedules: {
                all: @json(route('schedules.all')),
                weeklyData: @json(route('schedules.weekly-teaching-data')),
                groups: @json(route('schedules.groups', ':id')).replace(':id', '{id}')
            }
        };

        // ===========================
        // CONSTANTS
        // ===========================
        const DAY_ORDER = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday'];

        const ACTIVITY_COLORS = {
            'lecture': 'primary',
            'tutorial': 'success',
            'lab': 'warning'
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
                return Utils.get(ROUTES.schedules.all, { params: { type: 'weekly' } });
            },
            fetchWeeklyData(params) {
                return Utils.get(ROUTES.schedules.weeklyData, { params: params });
            },
            fetchGroups(scheduleId) {
                return Utils.get(ROUTES.schedules.groups.replace('{id}', scheduleId));
            }
        };

        // ===========================
        // MODAL MANAGER
        // ===========================
        const SessionModal = Utils.createModalManager('sessionDetailModal');

        // ===========================
        // UI MANAGER
        // ===========================
        const UIManager = {
            showLoading() {
                $('#emptyState, #gridView, #listView, #noDataState, #statsRow, #scheduleLegend').hide();
                $('#loadingState').show();
            },

            showEmpty() {
                $('#loadingState, #gridView, #listView, #noDataState, #statsRow, #scheduleLegend').hide();
                $('#emptyState').show();
            },

            showNoData() {
                $('#loadingState, #gridView, #listView, #emptyState, #statsRow, #scheduleLegend').hide();
                $('#noDataState').show();
            },

            showGrid(data) {
                $('#loadingState, #emptyState, #noDataState, #listView').hide();
                $('#gridView, #statsRow, #scheduleLegend').show();
                this.renderGridView(data);
            },

            showList(data) {
                $('#loadingState, #emptyState, #noDataState, #gridView').hide();
                $('#listView, #statsRow, #scheduleLegend').show();
                this.renderListView(data);
            },

            getActivityColor(type) {
                return ACTIVITY_COLORS[type] || 'secondary';
            },

            renderGridView(data) {
                const { slots, assignments } = data;
                const tbody = $('#scheduleTableBody');
                const thead = $('#scheduleTableHead tr');
                tbody.empty();

                if (!slots || slots.length === 0) {
                    this.showNoData();
                    return;
                }

                // Group slots by slot_order (time slot)
                const slotsByOrder = {};
                slots.forEach(slot => {
                    const order = slot.slot_order;
                    if (!slotsByOrder[order]) {
                        slotsByOrder[order] = {
                            start_time: slot.start_time,
                            end_time: slot.end_time,
                            days: {}
                        };
                    }
                    slotsByOrder[order].days[slot.day_of_week.toLowerCase()] = slot;
                });

                // Create assignment lookup by multiple slot IDs for grouped courses
                const assignmentsBySlot = {};
                const assignmentsByDay = {};

                if (assignments && assignments.length > 0) {
                    assignments.forEach(assignment => {
                        // Handle assignments that span multiple slots
                        if (assignment.slot_ids && assignment.slot_ids.length > 0) {
                            assignment.slot_ids.forEach(slotId => {
                                if (!assignmentsBySlot[slotId]) {
                                    assignmentsBySlot[slotId] = [];
                                }
                                assignmentsBySlot[slotId].push(assignment);
                            });
                        } else {
                            // Fallback for single slot assignments
                            const slotId = assignment.schedule_slot_id;
                            if (!assignmentsBySlot[slotId]) {
                                assignmentsBySlot[slotId] = [];
                            }
                            assignmentsBySlot[slotId].push(assignment);
                        }

                        // Group by days for multi-slot courses
                        if (assignment.days && assignment.days.length > 0) {
                            assignment.days.forEach(day => {
                                const dayLower = day.toLowerCase();
                                if (!assignmentsByDay[dayLower]) {
                                    assignmentsByDay[dayLower] = [];
                                }
                                assignmentsByDay[dayLower].push(assignment);
                            });
                        }
                    });
                }

                // Get sorted time slots
                const sortedOrders = Object.keys(slotsByOrder).sort((a, b) => parseInt(a) - parseInt(b));

                // Update table header with time slots
                thead.find('th:not(.day-header)').remove();
                sortedOrders.forEach(order => {
                    const slotInfo = slotsByOrder[order];
                    const timeDisplay = `${slotInfo.start_time || '-'} - ${slotInfo.end_time || '-'}`;
                    thead.append(`<th class="text-center time-slot-header">${timeDisplay}</th>`);
                });

                // Render rows for each day
                DAY_ORDER.forEach(day => {
                    const dayCapitalized = day.charAt(0).toUpperCase() + day.slice(1);
                    const row = $('<tr>');

                    // Day name cell
                    row.append(`
                                <td class="day-cell fw-semibold text-capitalize">
                                    <div class="day-name">${dayCapitalized}</div>
                                </td>
                            `);

                    // Time slot cells for this day
                    sortedOrders.forEach(order => {
                        const slotInfo = slotsByOrder[order];
                        const slot = slotInfo.days[day];
                        const cell = $('<td class="schedule-cell">');

                        if (slot) {
                            const slotAssignments = assignmentsBySlot[slot.id] || [];
                            const processedAssignments = new Set(); // Track processed assignments to avoid duplicates

                            slotAssignments.forEach(assignment => {
                                // Skip if already processed (for multi-slot courses)
                                if (processedAssignments.has(assignment.id)) {
                                    return;
                                }
                                processedAssignments.add(assignment.id);

                                // Create enhanced slot info for multi-slot courses
                                const enhancedSlotInfo = {
                                    ...slotInfo,
                                    combined_start_time: assignment.combined_start_time || slotInfo.start_time,
                                    combined_end_time: assignment.combined_end_time || slotInfo.end_time,
                                    is_combined: assignment.has_multiple_slots
                                };

                                const item = this.createScheduleItem(assignment, day, enhancedSlotInfo);
                                cell.append(item);
                            });
                        }

                        row.append(cell);
                    });

                    tbody.append(row);
                });
            },

            renderListView(data) {
                const { slots, assignments } = data;
                const accordion = $('#dayAccordion');
                accordion.empty();

                if (!assignments || assignments.length === 0) {
                    this.showNoData();
                    return;
                }

                // Group assignments by day
                const assignmentsByDay = {};
                DAY_ORDER.forEach(day => {
                    assignmentsByDay[day] = [];
                });

                // Create slot lookup
                const slotLookup = {};
                slots.forEach(slot => {
                    slotLookup[slot.id] = slot;
                });

                assignments.forEach(assignment => {
                    const slot = slotLookup[assignment.schedule_slot_id];
                    if (slot) {
                        const day = slot.day_of_week.toLowerCase();
                        if (assignmentsByDay[day]) {
                            assignmentsByDay[day].push({
                                ...assignment,
                                slot: slot
                            });
                        }
                    }
                });

                // Render accordion
                DAY_ORDER.forEach((day, index) => {
                    const dayAssignments = assignmentsByDay[day];
                    const dayName = day.charAt(0).toUpperCase() + day.slice(1);
                    const isExpanded = index === 0;

                    const accordionItem = $(`
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading_${day}">
                                        <button class="accordion-button ${isExpanded ? '' : 'collapsed'}" type="button"
                                                data-bs-toggle="collapse" data-bs-target="#collapse_${day}"
                                                aria-expanded="${isExpanded}" aria-controls="collapse_${day}">
                                            <span class="me-2"><i class="bx bx-calendar-event"></i></span>
                                            ${dayName}
                                            <span class="badge bg-primary ms-2">${dayAssignments.length}</span>
                                        </button>
                                    </h2>
                                    <div id="collapse_${day}" class="accordion-collapse collapse ${isExpanded ? 'show' : ''}"
                                         aria-labelledby="heading_${day}" data-bs-parent="#dayAccordion">
                                        <div class="accordion-body">
                                            ${dayAssignments.length === 0
                            ? '<p class="text-muted text-center mb-0">No sessions scheduled</p>'
                            : this.renderListItems(dayAssignments, day)
                        }
                                        </div>
                                    </div>
                                </div>
                            `);

                    accordion.append(accordionItem);
                });
            },

            renderListItems(assignments, day) {
                // Sort by start time
                assignments.sort((a, b) => {
                    const timeA = a.slot?.start_time || '';
                    const timeB = b.slot?.start_time || '';
                    return timeA.localeCompare(timeB);
                });

                let html = '<div class="list-group list-group-flush">';

                assignments.forEach(assignment => {
                    const activityClass = (assignment.activity_type || 'lecture').toLowerCase();
                    html += `
                                <div class="list-group-item list-group-item-action schedule-list-item"
                                     data-assignment='${JSON.stringify(assignment)}' data-day="${day}">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="badge bg-label-${this.getActivityColor(activityClass)} me-2">${assignment.activity_type || 'Lecture'}</span>
                                                <strong>${assignment.course_code || '-'}</strong>
                                            </div>
                                            <p class="mb-1 text-muted small">${assignment.course_name || '-'}</p>
                                            <div class="d-flex gap-3 text-muted small">
                                                <span><i class="bx bx-time-five me-1"></i>${assignment.slot?.start_time || '-'} - ${assignment.slot?.end_time || '-'}</span>
                                                <span><i class="bx bx-group me-1"></i>Group ${assignment.group || '-'}</span>
                                                ${assignment.location ? `<span><i class="bx bx-map me-1"></i>${assignment.location}</span>` : ''}
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-icon btn-outline-primary view-session-btn">
                                            <i class="bx bx-show"></i>
                                        </button>
                                    </div>
                                </div>
                            `;
                });

                html += '</div>';
                return html;
            },

            createScheduleItem(assignment, day, slotInfo) {
                const activityClass = (assignment.activity_type || 'lecture').toLowerCase();
                const hasMultipleSlots = assignment.has_multiple_slots || false;
                const slotCount = assignment.slot_count || 1;

                const multiSlotClass = hasMultipleSlots ? 'multi-slot' : '';
                const slotCountIndicator = hasMultipleSlots && slotCount > 1 ?
                    `<div class="course-group-indicator">${slotCount}</div>` : '';

                // Use combined time range for multi-slot courses
                const displayStartTime = slotInfo.combined_start_time || slotInfo.start_time;
                const displayEndTime = slotInfo.combined_end_time || slotInfo.end_time;
                const timeRange = (displayStartTime && displayEndTime) ? 
                    `${displayStartTime} - ${displayEndTime}` : '';

                const item = $(`
                            <div class="schedule-item ${activityClass} ${multiSlotClass}"
                                 data-assignment='${JSON.stringify(assignment)}'
                                 data-day="${day}"
                                 data-slot-info='${JSON.stringify(slotInfo)}'>
                                ${slotCountIndicator}
                                <div class="course-code">${assignment.course_code || '-'}</div>
                                <div class="course-name">${assignment.course_name || '-'}</div>
                                <span class="activity-badge bg-label-${this.getActivityColor(activityClass)}">${assignment.activity_type || 'Lecture'}</span>
                                <div class="group-info">Group ${assignment.group || '-'}</div>
                                ${assignment.location ? `<div class="location-info"><i class="bx bx-map"></i>${assignment.location}</div>` : ''}
                                ${timeRange ? `<div class="time-range text-muted small"><i class="bx bx-time"></i>${timeRange}</div>` : ''}
                                ${hasMultipleSlots ? `<div class="multi-slot-info text-muted small"><i class="bx bx-calendar"></i>${slotCount} slots combined</div>` : ''}
                            </div>
                        `);

                return item;
            },

            updateStats(data) {
                const { stats } = data;
                if (stats) {
                    $('#stat_total_courses').text(stats.total_courses || 0);
                    $('#stat_total_sessions').text(stats.total_sessions || 0);
                    $('#stat_lectures').text(stats.lectures || 0);
                    $('#stat_tutorials_labs').text((stats.tutorials || 0) + (stats.labs || 0));
                }
            },

            showSessionModal(assignment, day, slotInfo) {
                const capitalizeFirst = str => str ? str.charAt(0).toUpperCase() + str.slice(1) : '-';

                $('#modal_course_code').text(assignment.course_code || '-');
                $('#modal_course_name').text(assignment.course_name || '-');
                $('#modal_day').text(capitalizeFirst(day));
                
                const displayStartTime = slotInfo?.combined_start_time || slotInfo?.start_time;
                const displayEndTime = slotInfo?.combined_end_time || slotInfo?.end_time;
                const timeDisplay = (displayStartTime && displayEndTime) ? 
                    `${displayStartTime} - ${displayEndTime}` : '-';
                    
                $('#modal_time').text(timeDisplay);
                $('#modal_activity_type').html(`<span class="badge bg-label-${this.getActivityColor((assignment.activity_type || '').toLowerCase())}">${assignment.activity_type || '-'}</span>`);
                $('#modal_group').text(assignment.group || '-');
                $('#modal_location').text(assignment.location || '-');
                $('#modal_program').text(assignment.program_name || 'All Programs');
                $('#modal_level').text(assignment.level_name || 'All Levels');
                $('#modal_capacity').text(assignment.max_capacity ? `${assignment.current_capacity || 0} / ${assignment.max_capacity}` : '-');

                if (assignment.has_multiple_slots && assignment.slot_count > 1) {
                    const daysList = assignment.days && assignment.days.length > 0 ? 
                        assignment.days.map(d => capitalizeFirst(d)).join(', ') : 'Multiple days';
                    
                    $('#modal_slots_info').html(`
                            <div class="alert alert-info mb-0">
                                <i class="bx bx-info-circle me-2"></i>
                                This course spans <strong>${assignment.slot_count} time slots</strong> across ${daysList}.<br>
                                <small class="text-muted">Time shown: ${timeDisplay} (combined range)</small>
                            </div>
                        `).show();
                } else {
                    $('#modal_slots_info').hide();
                }

                SessionModal.show();
            }
        };

        // ===========================
        // SELECT2 MANAGER
        // ===========================
        const Select2Manager = {
            init() {
                Utils.initSelect2('#filter_schedule', {
                    placeholder: 'Select Schedule',
                    allowClear: true,
                    width: '100%'
                });

                Utils.initSelect2('#filter_program', {
                    placeholder: 'All Programs',
                    allowClear: true,
                    width: '100%'
                });

                Utils.initSelect2('#filter_level', {
                    placeholder: 'All Levels',
                    allowClear: true,
                    width: '100%'
                });

                Utils.initSelect2('#filter_group', {
                    placeholder: 'All Groups',
                    allowClear: true,
                    width: '100%'
                });
            },

            async loadFilterOptions() {
                try {
                    const [schedulesRes, programsRes, levelsRes] = await Promise.all([
                        ApiService.fetchSchedules(),
                        ApiService.fetchPrograms(),
                        ApiService.fetchLevels()
                    ]);

                    if (Utils.isResponseSuccess(schedulesRes)) {
                        Utils.populateSelect('#filter_schedule', Utils.getResponseData(schedulesRes), {
                            valueField: 'id',
                            textField: 'title',
                            placeholder: 'Select Schedule'
                        }, true);
                    }

                    if (Utils.isResponseSuccess(programsRes)) {
                        Utils.populateSelect('#filter_program', Utils.getResponseData(programsRes), {
                            valueField: 'id',
                            textField: 'name',
                            placeholder: 'All Programs'
                        }, true);
                    }

                    if (Utils.isResponseSuccess(levelsRes)) {
                        Utils.populateSelect('#filter_level', Utils.getResponseData(levelsRes), {
                            valueField: 'id',
                            textField: 'name',
                            placeholder: 'All Levels'
                        }, true);
                    }

                } catch (error) {
                    console.error('Failed to load filter options', error);
                    Utils.showError('Failed to load some filter options');
                }
            },

            async loadSchedules() {
                try {
                    const response = await ApiService.fetchSchedules();
                    if (Utils.isResponseSuccess(response)) {
                        Utils.populateSelect('#filter_schedule', Utils.getResponseData(response), {
                            valueField: 'id',
                            textField: 'title',
                            placeholder: 'Select Schedule'
                        }, true);
                    }
                } catch (error) {
                    console.error('Error loading schedules:', error);
                }
            },

            clearSchedules() {
                Utils.populateSelect('#filter_schedule', [], {
                    valueField: 'id',
                    textField: 'title',
                    placeholder: 'Select Schedule'
                }, true);
            }
        };

        // ===========================
        // FILTER MANAGER
        // ===========================
        const FilterManager = {
            init() {
                this.bindEvents();
            },

            bindEvents() {
                // Schedule change - automatically load weekly data
                $('#filter_schedule').on('change', (e) => {
                    const scheduleId = $(e.target).val();
                    AppState.setFilter('schedule_id', scheduleId);

                    if (scheduleId) {
                        // Load groups for this schedule
                        this.loadGroupsForSchedule(scheduleId);
                        this.loadScheduleData();
                    } else {
                        AppState.currentData = null;
                        UIManager.showEmpty();
                        $('#scheduleTitle').text('Weekly Schedule');
                        // Clear groups dropdown
                        Utils.populateSelect('#filter_group', [], {
                            placeholder: 'All Groups'
                        });
                    }
                });

                // Program/Level change - refresh data if schedule is selected
                $('#filter_program').on('change', (e) => {
                    AppState.setFilter('program_id', $(e.target).val());
                    if (AppState.filters.schedule_id) {
                        this.loadScheduleData();
                    }
                });

                $('#filter_level').on('change', (e) => {
                    AppState.setFilter('level_id', $(e.target).val());
                    if (AppState.filters.schedule_id) {
                        this.loadScheduleData();
                    }
                });

                $('#filter_group').on('change', (e) => {
                    AppState.setFilter('group', $(e.target).val());
                    if (AppState.filters.schedule_id) {
                        this.loadScheduleData();
                    }
                });

                // Clear filters
                $('#clearFiltersBtn').on('click', () => this.clearFilters());

                // Refresh
                $('#refreshScheduleBtn').on('click', () => {
                    if (AppState.filters.schedule_id) {
                        this.loadScheduleData();
                    } else {
                        Utils.showWarning('Please select a schedule first');
                    }
                });

                // View switcher
                $('.view-switcher .btn').on('click', (e) => {
                    const btn = $(e.currentTarget);
                    const view = btn.data('view');

                    $('.view-switcher .btn').removeClass('active');
                    btn.addClass('active');

                    AppState.currentView = view;
                    if (AppState.currentData) {
                        if (view === 'grid') {
                            UIManager.showGrid(AppState.currentData);
                        } else {
                            UIManager.showList(AppState.currentData);
                        }
                    }
                });

                // Schedule item click
                $(document).on('click', '.schedule-item, .schedule-list-item', function (e) {
                    if ($(e.target).closest('.view-session-btn').length) return;

                    const assignment = $(this).data('assignment');
                    const day = $(this).data('day');
                    let slotInfo = $(this).data('slot-info');

                    // For list view items, get slot info from assignment
                    if (!slotInfo && assignment.slot) {
                        slotInfo = {
                            start_time: assignment.slot.start_time,
                            end_time: assignment.slot.end_time
                        };
                    }

                    UIManager.showSessionModal(assignment, day, slotInfo || {});
                });

                // View button in list
                $(document).on('click', '.view-session-btn', function (e) {
                    e.stopPropagation();
                    const parent = $(this).closest('.schedule-list-item');
                    parent.trigger('click');
                });
            },

            async loadScheduleData() {
                const filters = AppState.getFilters();

                if (!filters.schedule_id) {
                    Utils.showWarning('Please select a schedule template to view the weekly schedule.');
                    return;
                }

                console.log('Loading schedule data with filters:', filters);
                UIManager.showLoading();

                // Clean up filters - remove null/empty values
                const cleanFilters = {};
                Object.keys(filters).forEach(key => {
                    if (filters[key] !== null && filters[key] !== '' && filters[key] !== 'null') {
                        cleanFilters[key] = filters[key];
                    }
                });

                console.log('Clean filters being sent:', cleanFilters);

                try {
                    const response = await ApiService.fetchWeeklyData(cleanFilters);
                    console.log('Weekly data response:', response);

                    if (Utils.isResponseSuccess(response)) {
                        if (response.data && (response.data.assignments.length > 0 || response.data.slots.length > 0)) {
                            AppState.currentData = response.data;
                            UIManager.updateStats(response.data);

                            // Update title
                            const scheduleName = $('#filter_schedule option:selected').text();
                            $('#scheduleTitle').text(scheduleName || 'Weekly Schedule');

                            if (AppState.currentView === 'grid') {
                                UIManager.showGrid(response.data);
                            } else {
                                UIManager.showList(response.data);
                            }
                        } else {
                            UIManager.showNoData();
                            Utils.showInfo('No course assignments found for the selected filters.');
                        }
                    } else {
                        UIManager.showNoData();
                        Utils.showError(response.message || 'Failed to load schedule data');
                    }
                } catch (error) {
                    console.error('Error fetching schedule data:', error);
                    UIManager.showEmpty();
                    Utils.showError('Failed to load schedule data. Please try again.');
                }
            },

            clearFilters() {
                // Clear Select2 values
                $('#filter_schedule').val('').trigger('change');
                $('#filter_program').val('').trigger('change');
                $('#filter_level').val('').trigger('change');
                $('#filter_group').val('').trigger('change');

                AppState.reset();
                UIManager.showEmpty();
                $('#scheduleTitle').text('Weekly Schedule');
            },

            async loadGroupsForSchedule(scheduleId) {
                try {
                    const response = await ApiService.fetchGroups(scheduleId);
                    if (Utils.isResponseSuccess(response)) {
                        const groups = Utils.getResponseData(response) || [];
                        const groupOptions = groups.map(group => ({
                            id: group,
                            text: `Group ${group}`
                        }));
                        Utils.populateSelect('#filter_group', groupOptions, {
                            placeholder: 'All Groups'
                        });
                    } else {
                        Utils.populateSelect('#filter_group', [], {
                            placeholder: 'All Groups'
                        });
                    }
                } catch (error) {
                    console.error('Error fetching groups:', error);
                    Utils.populateSelect('#filter_group', [], {
                        placeholder: 'All Groups'
                    });
                }
            }
        };

        // ===========================
        // INITIALIZATION
        // ===========================
        $(async () => {
            Select2Manager.init();
            await Select2Manager.loadFilterOptions();
            FilterManager.init();

            Utils.hidePageLoader();
        });
    </script>
@endpush