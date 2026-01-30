@extends('layouts.home')

@section('title', 'Student Enrollment | AcadOps')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/enrollment.css') }}">
@endpush

@section('page-content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Page Header -->
        <x-ui.page-header title="Student Enrollment" description="Search and enroll students in available courses"
            icon="bx bx-user-plus">
        </x-ui.page-header>

        <!-- Student Search Section -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-light d-flex align-items-center">
                <i class="bx bx-search me-2 text-primary"></i>
                <h5 class="mb-0 text-dark">Find Student</h5>
            </div>
            <div class="card-body">
                <form id="findStudentForm">
                    <div class="row align-items-end">
                        <div class="col-12 col-md-8 mb-3 mb-md-0">
                            <label for="identifier" class="form-label fw-semibold text-dark">National ID or Academic
                                ID</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-primary">
                                    <i class="bx bx-id-card text-primary"></i>
                                </span>
                                <input type="text" class="form-control border-primary" id="identifier" name="identifier"
                                    required placeholder="Enter National ID or Academic ID">
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" class="btn btn-primary w-100 shadow-sm">
                                <i class="bx bx-search me-1"></i>
                                Find Student
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Student Details and Enrollment Section -->
        <div id="studentDetails" style="display:none;">

            <!-- Student Information Card -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light d-flex align-items-center">
                    <i class="bx bx-user me-2 text-primary"></i>
                    <h5 class="mb-0 text-dark">Student Information</h5>
                </div>
                <div class="card-body">
                    <div id="studentInfo" class="row">
                        <!-- Student details will be populated here -->
                    </div>
                </div>
            </div>

            <!-- Term Selection -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light d-flex align-items-center">
                    <i class="bx bx-calendar me-2 text-primary"></i>
                    <h5 class="mb-0 text-dark">Academic Term</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 col-md-8">
                            <label for="term_id" class="form-label fw-semibold text-dark">
                                <i class="bx bx-calendar-alt me-1"></i> Academic Term <span class="text-danger">*</span>
                            </label>
                            <small class="form-text text-muted mb-2 d-block">Please select the academic term for
                                enrollment.</small>
                            <select class="form-select select2-term border-primary" id="term_id" name="term_id" required
                                aria-label="Academic Term">
                                <option value="">Please select an academic term</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enrollment Guide Card -->
            <div class="card mb-4 shadow-sm" id="guidingCard" style="display:none;">
                <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="bx bx-compass me-2 fs-4"></i>
                        <div>
                            <h5 class="mb-0 text-white">Enrollment Guide</h5>
                            <small class="opacity-75">Semester <span id="guideSemesterNo">-</span></small>
                        </div>
                    </div>
                    <div id="guideSummaryStats" class="d-flex gap-3">
                        <!-- Stats will be populated here -->
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="guidingInfo">
                        <div class="row g-0" id="guidingContent">
                            <!-- Passed Courses -->
                            <div class="col-md-6 col-lg-3 border-end">
                                <div class="p-3 h-100">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="mb-0 fw-semibold">
                                            <i class="bx bx-check-circle text-success me-1"></i>Passed
                                        </h6>
                                        <span class="badge bg-success" id="passedCount">0</span>
                                    </div>
                                    <div id="passedCoursesList" class="guide-list"
                                        style="max-height: 200px; overflow-y: auto;"></div>
                                </div>
                            </div>
                            <!-- Failed/Incomplete -->
                            <div class="col-md-6 col-lg-3 border-end">
                                <div class="p-3 h-100 bg-light">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="mb-0 fw-semibold">
                                            <i class="bx bx-error-circle text-danger me-1"></i>Failed/Incomplete
                                        </h6>
                                        <span class="badge bg-danger" id="failedCount">0</span>
                                    </div>
                                    <div id="failedCoursesList" class="guide-list"
                                        style="max-height: 200px; overflow-y: auto;"></div>
                                </div>
                            </div>
                            <!-- Current Semester Plan -->
                            <div class="col-md-6 col-lg-3 border-end">
                                <div class="p-3 h-100">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="mb-0 fw-semibold">
                                            <i class="bx bx-book-open text-primary me-1"></i>Current Semester
                                        </h6>
                                        <span class="badge bg-primary" id="studyPlanCount">0</span>
                                    </div>
                                    <div id="studyPlanCoursesList" class="guide-list"
                                        style="max-height: 200px; overflow-y: auto;"></div>
                                </div>
                            </div>
                            <!-- Missing from Previous -->
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 h-100 bg-light">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="mb-0 fw-semibold">
                                            <i class="bx bx-calendar-x text-warning me-1"></i>Missing (Previous)
                                        </h6>
                                        <span class="badge bg-warning text-dark" id="missingCount">0</span>
                                    </div>
                                    <div id="missingCoursesList" class="guide-list"
                                        style="max-height: 200px; overflow-y: auto;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Credit Hour Info Box -->
            <div id="chInfoBox" class="alert alert-info shadow-sm mt-3" style="display:none;">
                <strong class="text-dark">Maximum Allowed Credit Hours (CH):</strong>
                <table class="table table-bordered table-sm mb-0 mt-2" style="background:white;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-dark">Season</th>
                            <th class="text-dark">CGPA</th>
                            <th class="text-dark">Max CH</th>
                            <th class="text-dark">For Graduation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="ch-row" data-season="Fall" data-cgpa="lt2">
                            <td class="text-dark">Fall</td>
                            <td class="text-dark">&lt;2</td>
                            <td class="text-dark fw-bold">14</td>
                            <td class="text-dark">+3</td>
                        </tr>
                        <tr class="ch-row" data-season="Fall" data-cgpa="2to3">
                            <td class="text-dark">Fall</td>
                            <td class="text-dark">&ge;2 and &lt;3</td>
                            <td class="text-dark fw-bold">18</td>
                            <td class="text-dark">+3</td>
                        </tr>
                        <tr class="ch-row" data-season="Fall" data-cgpa="gte3">
                            <td class="text-dark">Fall</td>
                            <td class="text-dark">&ge;3</td>
                            <td class="text-dark fw-bold">21</td>
                            <td class="text-dark">+3</td>
                        </tr>
                        <tr class="ch-row" data-season="Spring" data-cgpa="lt2">
                            <td class="text-dark">Spring</td>
                            <td class="text-dark">&lt;2</td>
                            <td class="text-dark fw-bold">14</td>
                            <td class="text-dark">+3</td>
                        </tr>
                        <tr class="ch-row" data-season="Spring" data-cgpa="2to3">
                            <td class="text-dark">Spring</td>
                            <td class="text-dark">&ge;2 and &lt;3</td>
                            <td class="text-dark fw-bold">18</td>
                            <td class="text-dark">+3</td>
                        </tr>
                        <tr class="ch-row" data-season="Spring" data-cgpa="gte3">
                            <td class="text-dark">Spring</td>
                            <td class="text-dark">&ge;3</td>
                            <td class="text-dark fw-bold">21</td>
                            <td class="text-dark">+3</td>
                        </tr>
                        <tr class="ch-row" data-season="Summer" data-cgpa="any">
                            <td class="text-dark">Summer</td>
                            <td class="text-dark">Any</td>
                            <td class="text-dark fw-bold">9</td>
                            <td class="text-dark">+3</td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-2 small text-dark">
                    <strong>Note:</strong> To exceed the maximum credit hours for graduation, an administrator must grant
                    permission.
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="row">
                <!-- Enrollment History -->
                <div class="col-12 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-light">
                            <div
                                class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between mb-2">
                                <div class="d-flex align-items-center mb-2 mb-sm-0">
                                    <i class="bx bx-history me-2 text-primary"></i>
                                    <h5 class="mb-0 text-dark">Enrollment History</h5>
                                    <span class="badge bg-primary text-white ms-2" id="historyCount">0</span>
                                </div>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-primary">
                                    <i class="bx bx-search text-primary"></i>
                                </span>
                                <input type="text" class="form-control border-primary" id="historySearch"
                                    placeholder="Search courses, terms, or grades...">
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="enrollmentHistoryBox" class="enrollment-history-container">
                                <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                                    <div class="text-center text-muted">
                                        <i class="bx bx-search-alt-2 display-4 mb-3 text-primary"></i>
                                        <p class="text-dark">Find a student to view enrollment history</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Courses -->
                <div class="col-12 col-lg-8 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-light">
                            <div
                                class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between mb-2">
                                <div class="d-flex align-items-center mb-2 mb-sm-0">
                                    <i class="bx bx-book me-2 text-primary"></i>
                                    <h5 class="mb-0 text-dark">Available Courses</h5>
                                    <span class="badge bg-primary text-white ms-2" id="coursesCount">0</span>
                                </div>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-primary">
                                    <i class="bx bx-search text-primary"></i>
                                </span>
                                <input type="text" class="form-control border-primary" id="coursesSearch"
                                    placeholder="Search course names or codes...">
                            </div>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="exceptionForDifferentLevels">
                                <label class="form-check-label" for="exceptionForDifferentLevels">Allow courses from other
                                    levels</label>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="enrollForm">
                                <input type="hidden" id="student_id" name="student_id">
                                <div id="coursesBox" class="courses-container">
                                    <div class="d-flex justify-content-center align-items-center"
                                        style="min-height: 200px;">
                                        <div class="text-center text-muted">
                                            <i class="bx bx-book-bookmark display-4 mb-3 text-primary"></i>
                                            <p class="text-dark">Select a term to view available courses</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-success w-100 shadow-sm" id="enrollBtn"
                                        style="display: none;">
                                        <i class="bx bx-plus me-1"></i>
                                        Enroll Selected Courses
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Schedule Visualization -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow-sm" id="weeklyScheduleCard" style="display:none;">
                        <div class="card-header bg-light">
                            <div
                                class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between">
                                <div class="d-flex align-items-center mb-2 mb-sm-0">
                                    <i class="bx bx-calendar-week me-2 text-primary"></i>
                                    <h5 class="mb-0 text-dark">Weekly Schedule Preview</h5>
                                </div>
                                <div class="mt-2 mt-sm-0">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="downloadTimetableBtn"
                                        style="display:none;">
                                        <i class="bx bx-download me-1"></i>
                                        Download Timetable
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Conflict Warning Alert -->
                            <div id="scheduleConflictAlert" class="alert alert-warning" style="display:none;">
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-error-circle me-2 text-warning"></i>
                                    <div>
                                        <strong class="text-dark">Schedule Conflicts Detected!</strong>
                                        <p class="mb-0 small text-dark">Red highlighted time slots indicate overlapping
                                            classes. Please review your course selections.</p>
                                    </div>
                                </div>
                            </div>

                            <div id="weeklySchedule" class="schedule-grid">
                                <!-- Schedule will be populated here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Credit Hours Summary -->
            <div class="row mt-4">
                <div class="col-12">
                    <div id="creditHoursSummary" style="display:none;">
                        <div class="alert alert-info mb-0 p-3 shadow-sm">
                            <div class="row text-center">
                                <div class="col-6 col-md-3 mb-3 mb-md-0">
                                    <div class="credit-hours-item">
                                        <i class="bx bx-book-open text-primary mb-1"></i>
                                        <div class="credit-hours-label">Current Enrollment</div>
                                        <div class="credit-hours-value" id="currentEnrollmentHours">0</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-3 mb-md-0">
                                    <div class="credit-hours-item">
                                        <i class="bx bx-plus-circle text-success mb-1"></i>
                                        <div class="credit-hours-label">Selected Additional</div>
                                        <div class="credit-hours-value" id="selectedCH">0</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-3 mb-md-0">
                                    <div class="credit-hours-item">
                                        <i class="bx bx-target-lock text-warning mb-1"></i>
                                        <div class="credit-hours-label">Max Allowed</div>
                                        <div class="credit-hours-value" id="maxCH">0</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="credit-hours-item">
                                        <i class="bx bx-time text-info mb-1"></i>
                                        <div class="credit-hours-label">Remaining</div>
                                        <div class="credit-hours-value" id="remainingCH">0</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-dark fw-semibold">Credit Hours Usage</small>
                                    <small class="text-dark fw-semibold" id="usagePercentage">0%</small>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" id="usageProgressBar" role="progressbar"
                                        style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Exception alert container -->
                        <div id="exceptionAlert" style="display:none;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Type Selection Modal -->
        <x-ui.modal id="activitySelectionModal" scrollable="true" size="xl" title="Select Activity Schedule">
            <div id="courseActivityInfo" class="mb-3">
                <!-- Course info will be populated here -->
            </div>
            <div class="alert alert-info">
                <i class="bx bx-info-circle me-2"></i>
                <strong>Important:</strong> You can only select one activity from each activity type. Please choose the
                schedule that works best for you.
            </div>
            <div id="activitiesList">
                <!-- Activity types will be populated here -->
            </div>
            <x-slot name="footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmActivitySelection">
                    <i class="bx bx-check me-1"></i>
                    Confirm Selection
                </button>
            </x-slot>
        </x-ui.modal>

        <!-- Prerequisites Modal -->
        <x-ui.modal id="prerequisitesModal" scrollable="true" size="lg" title="Missing Prerequisites">
            <div class="alert alert-warning">
                <i class="bx bx-error-circle me-2"></i>
                <span class="text-dark">The following prerequisites are required but not completed:</span>
            </div>
            <div id="missingPrerequisitesList">
                <!-- Missing prerequisites will be populated here -->
            </div>
            <x-slot name="footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
            student: null,
            term: { id: null, name: null },
            enrollments: {
                history: [],  
                current: [],  
                selected: []  
            },
            courses: {
                available: [],
                filtered: []
            },
            schedules: {
                enrolled: [], 
                selected: [], 
                raw: new Map()
            },
            selections: {
                courses: new Set(),
                courseGroups: new Map(),
                activityTypes: new Map()
            },

            reset() {
                this.student = null;
                this.term = { id: null, name: null };
                this.enrollments = { history: [], current: [], selected: [] };
                this.courses = { available: [], filtered: [] };
                this.schedules = { enrolled: [], selected: [], raw: new Map() };
                this.selections = { courses: new Set(), courseGroups: new Map(), activityTypes: new Map() };
            },

            resetSelections() {
                this.enrollments.selected = [];
                this.schedules.selected = [];
                this.selections.courses.clear();
                this.selections.courseGroups.clear();
                this.selections.activityTypes.clear();
            },

            setStudent(student) {
                this.student = student;
            },

            setTerm(termId, termName) {
                this.term.id = termId;
                this.term.name = termName;
                this.updateCurrentEnrollments();
            },

            setEnrollmentHistory(enrollments) {
                this.enrollments.history = enrollments || [];
                this.updateCurrentEnrollments();
            },

            updateCurrentEnrollments() {
                if (!this.term.id) {
                    this.enrollments.current = [];
                    this.schedules.enrolled = [];
                    return;
                }

                this.enrollments.current = this.enrollments.history.filter(e => e.term?.id == this.term.id);
                this.schedules.enrolled = [];

                this.enrollments.current.forEach(enrollment => {
                    if (enrollment.schedules && Array.isArray(enrollment.schedules)) {
                        enrollment.schedules.forEach(schedule => {
                            this.schedules.enrolled.push({
                                course: enrollment.course,
                                activity: schedule.activity,
                                group: schedule.group_number,
                                source: 'enrolled'
                            });
                        });
                    }
                });
            },

            getAllSchedules() {
                return [...this.schedules.enrolled, ...this.schedules.selected];
            }
        };

        // ===========================
        // ROUTES & API
        // ===========================
        const ROUTES = {
            enrollment: {
                studentEnrollments: @json(route('enrollments.studentEnrollments')),
                prerequisites: @json(route('courses.prerequisites')),
                remainingCreditHours: @json(route('enrollments.remainingCreditHours')),
                store: @json(route('enrollments.store')),
                downloadPdf: @json(route('students.download.pdf', ':id')),
                guiding: @json(route('enrollments.guiding')),
                terms: @json(route('terms.all')),
                termsWithInactive: @json(route('terms.all.with_inactive'))
            },
            student: {
                find: @json(route('students.show', ':id')),
            },
            availableCourses: {
                availabeForStudent: @json(route('available_courses.by_student')),
                schedules: @json(route('available_courses.schedules.all', ':id')),
            }
        };

        const ApiService = {
            findStudent(identifier) {
                return Utils.get(Utils.replaceRouteId(ROUTES.student.find, identifier));
            },
            fetchStudentEnrollments(studentId) {
                return Utils.post(ROUTES.enrollment.studentEnrollments, { student_id: studentId });
            },
            fetchAvailableCourses(studentId, termId, exception) {
                return Utils.post(ROUTES.availableCourses.availabeForStudent, {
                    student_id: studentId,
                    term_id: termId,
                    exceptionForDifferentLevels: exception ? 1 : 0
                });
            },
            fetchPrerequisites(studentId, courseIds) {
                return Utils.post(ROUTES.enrollment.prerequisites, { student_id: studentId, course_ids: courseIds });
            },
            fetchCourseSchedules(courseId) {
                const url = Utils.replaceRouteId(ROUTES.availableCourses.schedules, courseId);
                return Utils.get(url);
            },
            fetchRemainingCreditHours(studentId, termId) {
                return Utils.post(ROUTES.enrollment.remainingCreditHours, { student_id: studentId, term_id: termId });
            },
            submitEnrollment(formData) {
                return Utils.post(ROUTES.enrollment.store, formData, { processData: false, contentType: false });
            },
            fetchGuiding(studentId, termId) {
                return Utils.post(ROUTES.enrollment.guiding, { student_id: studentId, term_id: termId });
            },
            fetchTerms(admin = false) {
                const url = admin ? ROUTES.enrollment.termsWithInactive : ROUTES.enrollment.terms;
                return Utils.get(url);
            }
        };

        const ActivityModal = Utils.createModalManager('activitySelectionModal');
        const PrerequisiteModal = Utils.createModalManager('prerequisitesModal');

        // ===========================
        // MANAGERS
        // ===========================
        const Select2Manager = {
            init() {
                Utils.initSelect2('#term_id', {
                    placeholder: 'Please select an academic term',
                    allowClear: true,
                    width: '100%'
                });
            },
            async loadTerms() {
                const isAdmin = window.user?.hasRole?.('admin') || false;
                try {
                    const response = await ApiService.fetchTerms(isAdmin);
                    if (Utils.isResponseSuccess(response)) {
                        const terms = Utils.getResponseData(response);
                        Utils.populateSelect('#term_id', terms, {
                            valueField: 'id',
                            textField: 'name',
                            placeholder: 'Please select an academic term'
                        }, true);
                    }
                } catch (error) {
                    Utils.handleError(error);
                }
            }
        };

        const StudentManager = {
            init() {
                $('#findStudentForm').on('submit', async (e) => {
                    e.preventDefault();
                    const identifier = $('#identifier').val().trim();
                    if (!identifier) return;

                    const $btn = $('#findStudentForm button[type="submit"]');
                    Utils.setLoadingState($btn, true, { loadingText: 'Searching...' });

                    try {
                        const response = await ApiService.findStudent(identifier);
                        if (Utils.isResponseSuccess(response)) {
                            const student = Utils.getResponseData(response);
                            AppState.setStudent(student);
                            this.display(student);
                            $('#student_id').val(student.id);

                            const enrollmentsResponse = await ApiService.fetchStudentEnrollments(student.id);
                            if (Utils.isResponseSuccess(enrollmentsResponse)) {
                                AppState.setEnrollmentHistory(Utils.getResponseData(enrollmentsResponse) || []);
                                EnrollmentHistoryManager.load();
                                EnrollmentApp.resetCourseRelated();
                            }
                        }
                    } catch (error) {
                        Utils.handleError(error);
                        $('#studentDetails').hide();
                    } finally {
                        Utils.setLoadingState($btn, false);
                    }
                });
            },
            display(student) {
                const html = `
                        <div class="col-12 col-md-4 mb-3 mb-md-0">
                            <div class="student-info-item"><small class="text-muted">Full Name (English)</small><h6 class="mb-0 text-dark">${Utils.escapeHtml(student.name_en)}</h6></div>
                            <div class="student-info-item"><small class="text-muted">Full Name (Arabic)</small><h6 class="mb-0 text-dark">${Utils.escapeHtml(student.name_ar || 'N/A')}</h6></div>
                            <div class="student-info-item"><small class="text-muted">Academic Email</small><h6 class="mb-0 text-dark">${Utils.escapeHtml(student.academic_email)}</h6></div>
                            <div class="student-info-item"><small class="text-muted">Program</small><h6 class="mb-0 text-dark">${Utils.escapeHtml(student.program?.name || 'Not Assigned')}</h6></div>
                        </div>
                        <div class="col-12 col-md-4 mb-3 mb-md-0">
                            <div class="student-info-item"><small class="text-muted">Academic ID</small><h6 class="mb-0 text-dark">${Utils.escapeHtml(student.academic_id)}</h6></div>
                            <div class="student-info-item"><small class="text-muted">National ID</small><h6 class="mb-0 text-dark">${Utils.escapeHtml(student.national_id)}</h6></div>
                            <div class="student-info-item"><small class="text-muted">Level</small><h6 class="mb-0 text-dark">Level ${Utils.escapeHtml(student.level?.name || 'N/A')}</h6></div>
                            <div class="student-info-item"><small class="text-muted">CGPA</small><h6 class="mb-0 text-dark">${student.cgpa || 'N/A'}</h6></div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="student-info-item"><small class="text-muted">Total Units Taken</small><h6 class="mb-0 text-dark">${student.taken_hours || 0} Units</h6></div>
                            <div class="student-info-item"><small class="text-muted">Remaining Hours</small><h6 class="mb-0 text-dark">Unknown</h6></div>
                        </div>
                    `;
                $('#studentInfo').html(html);
                $('#studentDetails').show();
            }
        };

        const TermManager = {
            init() {
                $('#term_id').on('change', () => {
                    const termId = $('#term_id').val() || null;
                    const termName = $('#term_id option:selected').text();
                    AppState.setTerm(termId, termName);
                    AppState.resetSelections();
                    ScheduleManager.initialize();
                    GuidingManager.load();
                    AvailableCoursesManager.load();
                    CreditHoursManager.updateInfoBoxByTermName(termName);
                });
            }
        };

        const EnrollmentHistoryManager = {
            load() {
                if (!AppState.student) {
                    Utils.showEmptyState('#enrollmentHistoryBox', 'bx-search-alt-2', 'Find a student to view enrollment history');
                    $('#historyCount').text('0');
                    return;
                }
                const history = AppState.enrollments.history;
                $('#historyCount').text(history.length);
                this.render(history);
            },
            render(enrollments) {
                if (enrollments.length === 0) {
                    Utils.showEmptyState('#enrollmentHistoryBox', 'bx-info-circle', 'No enrollment history found');
                    return;
                }

                let html = '';
                enrollments.forEach(enrollment => {
                    const courseName = Utils.escapeHtml(enrollment.course?.name || 'Unknown Course');
                    const termName = Utils.escapeHtml(enrollment.term?.name || 'Unknown Term');
                    const gradeBadge = enrollment.grade
                        ? `<span class="badge bg-primary text-white"><i class="bx bx-star me-1"></i>Grade: <strong>${Utils.escapeHtml(enrollment.grade)}</strong></span>`
                        : '<span class="badge bg-secondary text-white"><i class="bx bx-time me-1"></i>No Grade Yet</span>';

                    html += `
                            <div class="history-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1 text-dark">${courseName}</h6>
                                        <p class="text-muted mb-1"><i class="bx bx-calendar me-1"></i><strong>${termName}</strong></p>
                                        <div class="mt-1">${gradeBadge}</div>
                                    </div>
                                    <span class="badge bg-success text-white">Enrolled</span>
                                </div>
                            </div>
                        `;
                });
                $('#enrollmentHistoryBox').html(html);
            },
            filter(searchTerm) {
                if (!AppState.enrollments.history.length) return;
                const filtered = AppState.enrollments.history.filter(item => {
                    const search = searchTerm.toLowerCase();
                    return (item.course?.name || '').toLowerCase().includes(search) ||
                        (item.course?.code || '').toLowerCase().includes(search) ||
                        (item.term?.name || '').toLowerCase().includes(search) ||
                        (item.grade || '').toLowerCase().includes(search);
                });
                this.render(filtered);
            }
        };

        const AvailableCoursesManager = {
            async load() {
                if (!AppState.student || !AppState.term.id) {
                    Utils.showEmptyState('#coursesBox', 'bx-book-bookmark', 'Select a term to view available courses');
                    $('#coursesCount').text('0');
                    this.hideActions();
                    return;
                }

                const exception = $('#exceptionForDifferentLevels').is(':checked');
                Utils.showLoading('#coursesBox', 'Loading available courses...');

                try {
                    const response = await ApiService.fetchAvailableCourses(AppState.student.id, AppState.term.id, exception);
                    if (Utils.isResponseSuccess(response)) {
                        const courses = Utils.getResponseData(response) || [];
                        AppState.courses.available = courses;
                        AppState.courses.filtered = courses;
                        $('#coursesCount').text(courses.length);
                        if (courses.length === 0) {
                            Utils.showEmptyState('#coursesBox', 'bx-info-circle', 'No available courses for this term');
                            this.hideActions();
                        } else {
                            await this.loadPrerequisitesAndRender(courses);
                            this.showActions();
                            CreditHoursManager.load();
                        }
                    }
                } catch (error) {
                    Utils.handleError(error);
                    Utils.showErrorState('#coursesBox', 'Failed to load available courses');
                    this.hideActions();
                }
            },
            async loadPrerequisitesAndRender(courses) {
                const courseIds = courses.map(c => c.available_course_id);
                try {
                    const response = await ApiService.fetchPrerequisites(AppState.student.id, courseIds);
                    const prereqs = Utils.isResponseSuccess(response) ? Utils.getResponseData(response) : [];
                    this.render(courses, prereqs);
                } catch {
                    this.render(courses, []);
                }
            },
            render(courses, prereqs) {
                let html = '';
                courses.forEach(course => {
                    const remaining = parseInt(course.remaining_capacity) || 0;
                    const capacityClass = remaining > 10 ? 'success' : remaining > 5 ? 'warning' : 'danger';
                    const remainingText = remaining === 0 ? 'Full' : `${remaining} spot${remaining === 1 ? '' : 's'} left`;

                    const coursePrereqs = prereqs.filter(p => p.required_for_course_id == course.available_course_id);
                    const hasUnfulfilled = coursePrereqs.some(p => !p.is_enrolled);
                    const canEnroll = !hasUnfulfilled;
                    const disabled = !canEnroll ? 'disabled' : '';

                    html += `
                            <div class="course-item ${!canEnroll ? 'disabled' : ''}" data-course-id="${course.available_course_id}">
                                <div class="form-check">
                                    <input class="form-check-input course-checkbox" type="checkbox" name="available_course_ids[]"
                                           value="${course.available_course_id}" data-credit-hours="${course.credit_hours}"
                                           id="course_${course.available_course_id}" ${disabled}>
                                    <label class="form-check-label w-100" for="course_${course.available_course_id}">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div style="flex:1;">
                                                <h6 class="mb-1 text-dark">${Utils.escapeHtml(course.name)}</h6>
                                                <p class="text-muted mb-0 small">
                                                    <i class="bx bx-book me-1"></i><strong>${Utils.escapeHtml(course.code || 'N/A')}</strong>
                                                    <span class="ms-2"><i class="bx bx-timer me-1"></i><strong class="text-primary">${course.credit_hours}</strong> CH</span>
                                                </p>
                                                ${this.renderPrerequisites(coursePrereqs)}
                                                <div class="selected-group-info" id="groupInfo_${course.available_course_id}" style="display:none;"></div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-${capacityClass} mb-2">${remainingText}</span>
                                                ${hasUnfulfilled ? '<div><span class="badge bg-danger"><i class="bx bx-lock me-1"></i>Prerequisites Required</span></div>' : ''}
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        `;
                });
                $('#coursesBox').html(html);
                this.bindCheckboxes();
            },
            renderPrerequisites(prereqs) {
                if (prereqs.length === 0) {
                    return '<div class="mt-2"><small class="text-success"><i class="bx bx-check me-1"></i>No prerequisites</small></div>';
                }

                let html = `<div class="mt-2"><small class="text-dark fw-semibold"><i class="bx bx-link me-1"></i>Prerequisites:</small></div>`;
                prereqs.forEach(p => {
                    const statusClass = p.is_enrolled ? 'text-success' : 'text-danger';
                    const icon = p.is_enrolled ? 'bx-check-circle' : 'bx-x-circle';
                    const badge = p.is_enrolled ? 'bg-success' : 'bg-danger';
                    const text = p.is_enrolled ? 'Completed' : 'Missing';
                    const onclick = !p.is_enrolled ? `PrerequisiteManager.showMissing([${JSON.stringify(p).replace(/"/g, '&quot;')}])` : '';
                    html += `<div class="small ${statusClass}" style="cursor:${!p.is_enrolled ? 'pointer' : 'default'}" ${onclick ? 'onclick="' + onclick + '"' : ''}>
                            <i class="bx ${icon} me-1"></i>${Utils.escapeHtml(p.course_name)} (${Utils.escapeHtml(p.course_code)})
                            <span class="badge ${badge} ms-1">${text}</span>
                        </div>`;
                });
                return html;
            },
            bindCheckboxes() {
                $(document).off('change', '.course-checkbox').on('change', '.course-checkbox', function () {
                    const courseId = $(this).val();
                    if ($(this).is(':checked')) {
                        // Temporarily uncheck until activity selection is confirmed
                        $(this).prop('checked', false);
                        ActivitySelectionManager.show(courseId);
                    } else {
                        AppState.selections.courseGroups.delete(courseId);
                        AppState.selections.courses.delete(courseId);
                        $(`#groupInfo_${courseId}`).hide();
                        $(this).closest('.course-item').removeClass('selected');
                        UI.updateEnrollButton();
                        CreditHoursManager.update();
                        ScheduleManager.update();
                    }
                });
            },
            hideActions() {
                $('#enrollBtn').hide();
                $('#creditHoursSummary').hide();
                $('#exceptionAlert').hide();
            },
            showActions() {
                $('#enrollBtn').show();
                UI.updateEnrollButton();
            },
            filter(searchTerm) {
                if (!AppState.courses.available.length) return;
                const filtered = AppState.courses.available.filter(c => {
                    return (c.name || '').toLowerCase().includes(searchTerm.toLowerCase()) || (c.code || '').toLowerCase().includes(searchTerm.toLowerCase());
                });
                AppState.courses.filtered = filtered;
                if (filtered.length > 0) {
                    this.render(filtered, []);
                } else {
                    Utils.showEmptyState('#coursesBox', 'bx-search-alt', 'No courses match your search');
                }
            }
        };

        const PrerequisiteManager = {
            showMissing(prereqs) {
                let html = '';
                prereqs.forEach(p => {
                    html += `
                            <div class="alert alert-danger mb-2">
                                <i class="bx bx-x-circle me-2"></i>
                                <strong>${Utils.escapeHtml(p.course_name)}</strong> (${Utils.escapeHtml(p.course_code || 'N/A')})
                                <small class="d-block">${p.credit_hours || 'N/A'} CH</small>
                            </div>
                        `;
                });
                $('#missingPrerequisitesList').html(html);
                PrerequisiteModal.show();
            }
        };

        // ===========================
        // ACTIVITY SELECTION
        // ===========================
        const ActivitySelectionManager = {
            currentCourseId: null,
            async show(courseId) {
                const course = AppState.courses.available.find(c => c.available_course_id == courseId);
                if (!course) {
                    Utils.showError('Course data not found');
                    return;
                }

                $('#courseActivityInfo').html(`
                        <div class="alert alert-info">
                            <h6>${Utils.escapeHtml(course.name)}</h6>
                            <small>Code: <strong>${Utils.escapeHtml(course.code || 'N/A')}</strong> | CH: <strong>${course.credit_hours}</strong></small>
                        </div>
                    `);

                Utils.showLoading('#activitiesList', 'Loading schedules...');
                this.currentCourseId = courseId;
                ActivityModal.show();

                try {
                    const response = await ApiService.fetchCourseSchedules(courseId);
                    if (Utils.isResponseSuccess(response)) {
                        const schedulesData = Utils.getResponseData(response) || [];
                        AppState.schedules.raw.set(courseId, schedulesData);
                        const transformedData = this.transformScheduleData(schedulesData);
                        AppState.selections.activityTypes.set(courseId, transformedData);
                        this.render(transformedData, courseId);
                    }
                } catch (error) {
                    Utils.handleError(error);
                    $('#activitiesList').html('<div class="alert alert-danger">Failed to load schedules</div>');
                }
            },

            transformScheduleData(schedulesData) {
                const groupedByType = {};

                schedulesData.forEach(schedule => {
                    const type = schedule.activity_type;
                    if (!groupedByType[type]) {
                        groupedByType[type] = [];
                    }

                    if (schedule.schedule_assignments && Array.isArray(schedule.schedule_assignments)) {
                        schedule.schedule_assignments.forEach(assignment => {
                            if (assignment.schedule_slot) {
                                const scheduleSlot = assignment.schedule_slot;
                                groupedByType[type].push({
                                    id: schedule.id,
                                    activity_type: schedule.activity_type,
                                    group_number: schedule.group,
                                    start_time: scheduleSlot.start_time,
                                    end_time: scheduleSlot.end_time,
                                    day_of_week: scheduleSlot.day_of_week,
                                    location: schedule.location || '',
                                    enrolled_count: assignment.enrolled || 0,
                                    max_capacity: schedule.max_capacity || schedule.capacity || 0,
                                    min_capacity: schedule.min_capacity || 0
                                });
                            }
                        });
                    }
                });

                return groupedByType;
            },

            render(groupedByType, courseId) {
                if (!groupedByType || Object.keys(groupedByType).length === 0) {
                    $('#activitiesList').html('<div class="alert alert-warning">No schedules available</div>');
                    return;
                }

                let html = '';
                Object.keys(groupedByType).forEach(type => {
                    const schedules = groupedByType[type];
                    if (schedules.length === 0) return;

                    const icon = type === 'lecture' ? 'bx-book-open' : type === 'lab' ? 'bx-flask' : 'bx-edit';
                    html += `
                            <div class="activity-type-section mb-4" data-activity-type="${type}">
                                <div class="activity-type-header bg-light p-3 rounded-top border">
                                    <h5 class="mb-0 text-dark">
                                        <i class="bx ${icon} me-2"></i>
                                        ${Utils.escapeHtml(type.charAt(0).toUpperCase() + type.slice(1))}
                                        <span class="badge bg-primary ms-2">${schedules.length}</span>
                                        <span class="badge bg-danger ms-1 text-white" style="font-size:0.75em;">* Required</span>
                                    </h5>
                                </div>
                                <div class="activity-options border border-top-0 rounded-bottom p-3">
                        `;

                    schedules.forEach(s => {
                        const conflict = TimeConflictManager.checkActivity(s);
                        const disabled = conflict ? 'disabled' : '';
                        const conflictClass = conflict ? 'border-danger activity-disabled' : '';

                        html += `
                                <div class="activity-option mb-2 ${conflict ? 'activity-disabled' : ''}" data-activity-id="${s.id}" data-activity-type="${type}">
                                    <div class="card ${conflictClass}">
                                        <div class="card-body p-3">
                                            <div class="form-check">
                                                <input class="form-check-input activity-radio" type="radio" name="activity_${type}"
                                                       value="${s.id}" data-activity='${JSON.stringify(s).replace(/'/g, "&#39;")}' ${disabled}>
                                                <label class="form-check-label w-100">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1 text-dark">Group ${s.group_number} ${conflict ? '<span class="badge bg-danger ms-2">CONFLICT</span>' : ''}</h6>
                                                            <div class="small text-muted">
                                                                <div><i class="bx bx-time me-1"></i>${Utils.formatTimeRange(s.start_time, s.end_time)}</div>
                                                                <div><i class="bx bx-calendar me-1"></i>${Utils.escapeHtml(s.day_of_week || 'TBA')}</div>
                                                                ${s.location ? `<div><i class="bx bx-map me-1"></i>${Utils.escapeHtml(s.location)}</div>` : ''}
                                                                ${conflict ? '<div class="text-danger"><i class="bx bx-error-circle me-1"></i>Conflicts with current schedule</div>' : ''}
                                                            </div>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="badge bg-info">${s.enrolled_count || 0}/${s.max_capacity}</span>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                    });

                    html += `</div></div>`;
                });

                $('#activitiesList').html(html);
                this.bindEvents();
            },

            bindEvents() {
                $(document).off('change', '.activity-radio').on('change', '.activity-radio', () => {
                    this.refreshConflicts();
                    this.updateConfirmButton();
                });
                $('#confirmActivitySelection').off('click').on('click', () => this.confirm());
            },

            refreshConflicts() {
                $('.activity-option').each(function () {
                    const $option = $(this);
                    const $input = $option.find('.activity-radio');
                    const activityData = $input.data('activity');
                    if (!activityData) return;

                    const conflict = TimeConflictManager.checkActivity(activityData);
                    const $card = $option.find('.card');

                    if (conflict && !$input.is(':checked')) {
                        $input.prop('disabled', true);
                        $option.addClass('activity-disabled');
                        $card.addClass('border-danger');
                        if (!$option.find('.badge.bg-danger').length) {
                            $option.find('h6').append('<span class="badge bg-danger ms-2">CONFLICT</span>');
                            $option.find('.small').append('<div class="text-danger"><i class="bx bx-error-circle me-1"></i>Conflicts with current schedule</div>');
                        }
                    } else if (!conflict) {
                        $input.prop('disabled', false);
                        $option.removeClass('activity-disabled');
                        $card.removeClass('border-danger');
                        $option.find('.badge.bg-danger, .text-danger').remove();
                    }
                });
            },

            updateConfirmButton() {
                const allSelected = $('.activity-type-section').toArray().every(section =>
                    $(section).find('.activity-radio:checked').length > 0
                );
                $('#confirmActivitySelection').prop('disabled', !allSelected);
            },

            confirm() {
                const courseId = this.currentCourseId;
                const selected = $('.activity-radio:checked');
                if (selected.length === 0) {
                    return Utils.showError('Please select at least one activity per type');
                }

                const activities = [];
                selected.each(function () {
                    const activityData = $(this).data('activity');
                    if (activityData) {
                        activities.push(activityData);
                    }
                });

                const course = AppState.courses.available.find(c => c.available_course_id == courseId);
                if (!course) {
                    return Utils.showError('Course data not found');
                }

                const courseData = {
                    course_id: courseId,
                    selected_activities: activities,
                    course: course
                };

                const conflicts = TimeConflictManager.checkConflicts(courseData, courseId);
                if (conflicts.length > 0) {
                    TimeConflictManager.showWarning(conflicts, () => this.finalize(courseId, courseData));
                } else {
                    this.finalize(courseId, courseData);
                }
            },

            finalize(courseId, courseData) {
                const groupData = {
                    course: courseData.course,
                    group_activities: courseData.selected_activities
                };
                AppState.selections.courseGroups.set(courseId, groupData);
                AppState.selections.courses.add(courseId);

                const summary = courseData.selected_activities.map(a =>
                    `<span class="badge bg-success me-1">${a.activity_type.charAt(0).toUpperCase() + a.activity_type.slice(1)} G${a.group_number}</span>`
                ).join('');

                $(`#groupInfo_${courseId}`).html(`
                        <small class="text-primary fw-semibold"><i class="bx bx-chalkboard me-1"></i>Selected: ${summary}</small>
                    `).show();

                $(`#course_${courseId}`).prop('checked', true);
                $(`.course-item[data-course-id="${courseId}"]`).addClass('selected');

                ActivityModal.hide();
                UI.updateEnrollButton();
                CreditHoursManager.update();
                ScheduleManager.update();
            }
        };

        // ===========================
        // TIME CONFLICT MANAGER 
        // ===========================
        const TimeConflictManager = {
            hasConflict(a1, a2) {
                if (!a1.day_of_week || !a2.day_of_week) return false;
                if (a1.day_of_week.toLowerCase() !== a2.day_of_week.toLowerCase()) return false;
                const s1 = Utils.parseTime(a1.start_time);
                const e1 = Utils.parseTime(a1.end_time);
                const s2 = Utils.parseTime(a2.start_time);
                const e2 = Utils.parseTime(a2.end_time);
                if (!s1 || !e1 || !s2 || !e2) return false;
                return s1 < e2 && s2 < e1;
            },

            checkActivity(activity) {
                const allSchedules = AppState.getAllSchedules();
                for (const item of allSchedules) {
                    if (this.hasConflict(item.activity || item, activity)) {
                        return true;
                    }
                }
                return false;
            },

            checkConflicts(newCourseData, currentCourseId) {
                const conflicts = [];
                AppState.selections.courseGroups.forEach((data, id) => {
                    if (id != currentCourseId && data.group_activities) {
                        data.group_activities.forEach(act => {
                            newCourseData.selected_activities.forEach(newAct => {
                                if (this.hasConflict(act, newAct)) {
                                    conflicts.push({ type: 'selected', course: data.course.name, existing: act, new: newAct });
                                }
                            });
                        });
                    }
                });

                AppState.schedules.enrolled.forEach(item => {
                    newCourseData.selected_activities.forEach(newAct => {
                        if (this.hasConflict(item.activity, newAct)) {
                            conflicts.push({ type: 'enrolled', course: item.course.name, existing: item.activity, new: newAct });
                        }
                    });
                });

                for (let i = 0; i < newCourseData.selected_activities.length; i++) {
                    for (let j = i + 1; j < newCourseData.selected_activities.length; j++) {
                        if (this.hasConflict(newCourseData.selected_activities[i], newCourseData.selected_activities[j])) {
                            conflicts.push({ type: 'intra', course: newCourseData.course.name, existing: newCourseData.selected_activities[i], new: newCourseData.selected_activities[j] });
                        }
                    }
                }

                return conflicts;
            },

            showWarning(conflicts, onConfirm) {
                let html = '';
                conflicts.forEach((c, i) => {
                    const typeLabel = c.type === 'enrolled' ? 'Already Enrolled' : c.type === 'intra' ? 'Same Course' : 'Selected Course';
                    html += `
                            <div class="alert alert-warning mb-2">
                                <strong>Conflict ${i + 1}: ${Utils.escapeHtml(c.course)}</strong> <span class="badge bg-warning">${typeLabel}</span>
                                <div class="small">
                                    Existing: ${c.existing.day_of_week} ${c.existing.start_time}-${c.existing.end_time} (${c.existing.activity_type})
                                    <br>New: ${c.new.day_of_week} ${c.new.start_time}-${c.new.end_time} (${c.new.activity_type})
                                </div>
                            </div>
                        `;
                });

                Utils.showConfirmDialog({
                    title: 'Schedule Conflicts Detected',
                    html: html + '<p>Do you want to proceed anyway?</p>',
                    confirmButtonText: 'Proceed Anyway',
                    cancelButtonText: 'Cancel'
                }).then(result => {
                    if (result.isConfirmed) onConfirm();
                });
            }
        };

        // ===========================
        // SCHEDULE MANAGER
        // ===========================
        const ScheduleManager = {
            initialize() {
                if (!AppState.student || !AppState.term.id) {
                    $('#weeklyScheduleCard').hide();
                    $('#downloadTimetableBtn').hide();
                    return;
                }

                AppState.updateCurrentEnrollments();
                if (AppState.schedules.enrolled.length > 0) {
                    this.render();
                    $('#weeklyScheduleCard').show();
                    $('#downloadTimetableBtn').show();
                } else {
                    $('#weeklyScheduleCard').hide();
                    $('#downloadTimetableBtn').hide();
                }
            },

            update() {
                AppState.schedules.selected = [];
                AppState.selections.courseGroups.forEach((data, courseId) => {
                    if ($(`#course_${courseId}`).is(':checked')) {
                        data.group_activities.forEach(act => {
                            AppState.schedules.selected.push({
                                course: data.course,
                                activity: act,
                                group: act.group_number,
                                source: 'selected'
                            });
                        });
                    }
                });

                const allSchedules = AppState.getAllSchedules();
                if (allSchedules.length === 0) {
                    $('#weeklyScheduleCard').hide();
                    $('#downloadTimetableBtn').hide();
                    return;
                }

                this.render();
                $('#weeklyScheduleCard').show();
                $('#downloadTimetableBtn').show();
            },

            render() {
                const activities = AppState.getAllSchedules();
                const days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
                const timeSlots = [
                    '9:00-9:50', '9:50-10:40', '10:40-11:30', '11:30-12:20',
                    '12:20-13:10', '13:10-14:00', '14:00-14:50', '14:50-15:40'
                ];


                const scheduleEl = document.getElementById('weeklySchedule');
                if (scheduleEl && scheduleEl.style) {
                    scheduleEl.style.display = 'grid';
                    scheduleEl.style.gridTemplateColumns = `100px repeat(${timeSlots.length}, 1fr)`;
                }

                let html = `<div class="schedule-header">Time</div>`;
                timeSlots.forEach(slot => {
                    html += `<div class="schedule-header time-slot-header">${slot}</div>`;
                });

                days.forEach(day => {
                    html += `<div class="schedule-header day-header">${day}</div>`;

                    timeSlots.forEach(slot => {
                        let cellContent = '';
                        let hasClass = false;
                        let tooltipContent = '';

                        activities.forEach(item => {
                            const activity = item.activity || item;
                            if (activity.day_of_week?.toLowerCase() === day.toLowerCase() && this.isActivityInTimeSlot(slot, activity.start_time, activity.end_time)) {
                                hasClass = true;
                                const courseName = Utils.escapeHtml(item.course?.name || 'Unknown Course');
                                const activityType = Utils.escapeHtml(activity.activity_type || 'Unknown');
                                const groupNumber = activity.group_number || 'N/A';
                                const location = activity.location ? ` at ${Utils.escapeHtml(activity.location)}` : '';

                                cellContent += `<div class="activity-block" style="background-color: ${this.getActivityColor(activity.activity_type)};">
                                        <strong>${courseName}</strong><br>
                                        <small>${activityType} G${groupNumber}${location}</small>
                                    </div>`;

                                tooltipContent += `${courseName} (${activityType} G${groupNumber}) ${activity.start_time}-${activity.end_time}${location}\n`;
                            }
                        });

                        const cellClass = hasClass ? 'schedule-cell has-class' : 'schedule-cell';
                        const tooltip = tooltipContent ? `data-bs-toggle="tooltip" title="${Utils.escapeHtml(tooltipContent.trim())}"` : '';

                        html += `<div class="${cellClass}" ${tooltip}>${cellContent}</div>`;
                    });
                });

                $('#weeklySchedule').html(html);

                $('[data-bs-toggle="tooltip"]').tooltip('dispose');
                $('[data-bs-toggle="tooltip"]').tooltip({
                    placement: 'top',
                    container: 'body'
                });
            },

            isActivityInTimeSlot(timeSlot, startTime, endTime) {
                if (!startTime || !endTime) return false;

                const slotParts = timeSlot.split(/[–-]/).map(t => t.trim());
                if (slotParts.length < 2) return false;

                const slotStart = Utils.parseTime(slotParts[0]);
                const slotEnd = Utils.parseTime(slotParts[1]);
                const activityStart = Utils.parseTime(startTime);
                const activityEnd = Utils.parseTime(endTime);

                if (slotStart === null || slotEnd === null || activityStart === null || activityEnd === null) {
                    return false;
                }

                return (activityStart < slotEnd) && (activityEnd > slotStart);
            },

            getActivityColor(activityType) {
                const colors = {
                    'lecture': '#007bff',
                    'lab': '#28a745',
                    'tutorial': '#ffc107',
                    'seminar': '#dc3545'
                };
                return colors[activityType] || '#6c757d';
            }
        };

        // ===========================
        // CREDIT HOURS MANAGER
        // ===========================
        const CreditHoursManager = {
            async load() {
                if (!AppState.student || !AppState.term.id) return;

                try {
                    const response = await ApiService.fetchRemainingCreditHours(AppState.student.id, AppState.term.id);
                    if (Utils.isResponseSuccess(response)) {
                        const data = Utils.getResponseData(response);
                        $('#currentEnrollmentHours').text(data.current_enrollment_hours || 0);
                        $('#maxCH').text(data.max_allowed_hours || 0);
                        $('#remainingCH').text(data.remaining_hours || 0);
                        this.update();
                        $('#creditHoursSummary').show();
                        this.showException(data.exception_hours);
                    }
                } catch (error) {
                    Utils.handleError(error);
                }
            },

            update() {
                let selectedCH = 0;
                $('.course-checkbox:checked').each(function () {
                    selectedCH += parseInt($(this).data('credit-hours')) || 0;
                });
                const current = parseInt($('#currentEnrollmentHours').text()) || 0;
                const total = current + selectedCH;
                const max = parseInt($('#maxCH').text()) || 0;
                const remaining = Math.max(0, max - total);

                $('#selectedCH').text(selectedCH);
                $('#remainingCH').text(remaining);

                const percent = max > 0 ? Math.min((total / max) * 100, 100) : 0;
                $('#usageProgressBar').css('width', percent + '%').removeClass('bg-success bg-warning bg-danger')
                    .addClass(percent < 70 ? 'bg-success' : percent < 90 ? 'bg-warning' : 'bg-danger');
                $('#usagePercentage').text(Math.round(percent) + '%');
            },

            showException(hours) {
                if (hours > 0) {
                    $('#exceptionAlert').html(`
                            <div class="alert alert-warning mt-2">
                                <i class="bx bx-shield-check me-2"></i>
                                <strong>Admin Exception:</strong> +${hours} additional credit hours granted
                            </div>
                        `).show();
                } else {
                    $('#exceptionAlert').hide();
                }
            },

            updateInfoBoxByTermName(termName) {
                if (!termName) {
                    $('#chInfoBox').hide();
                    return;
                }
                let season = '';
                termName = termName.toLowerCase();
                if (termName.includes('fall')) season = 'Fall';
                else if (termName.includes('spring')) season = 'Spring';
                else if (termName.includes('summer')) season = 'Summer';

                if (season) {
                    this.updateInfoBox(season);
                } else {
                    $('#chInfoBox').hide();
                }
            },

            updateInfoBox(season) {
                if (!season) return;

                const seasonCapitalized = season.charAt(0).toUpperCase() + season.slice(1);
                $('#chInfoBox').show();
                $('.ch-row').hide();

                if (season.toLowerCase() === 'summer') {
                    $(`.ch-row[data-season='${seasonCapitalized}']`).show();
                } else {
                    const studentCgpa = AppState.student ? parseFloat(AppState.student.cgpa) : null;
                    if (studentCgpa !== null) {
                        if (studentCgpa < 2) {
                            $(`.ch-row[data-season='${seasonCapitalized}'][data-cgpa='lt2']`).show();
                        } else if (studentCgpa >= 2 && studentCgpa < 3) {
                            $(`.ch-row[data-season='${seasonCapitalized}'][data-cgpa='2to3']`).show();
                        } else {
                            $(`.ch-row[data-season='${seasonCapitalized}'][data-cgpa='gte3']`).show();
                        }
                    } else {
                        $(`.ch-row[data-season='${seasonCapitalized}']`).show();
                    }
                }
            }
        };

        // Guiding Manager, Submission Manager, UI, Search, Download (keeping existing code)
        // ... (remaining managers continue with the pattern, using AppState)

        const GuidingManager = {
            async load() {
                if (!AppState.student || !AppState.term.id) {
                    $('#guidingCard').hide();
                    return;
                }
                $('#guidingCard').show();

                try {
                    const response = await ApiService.fetchGuiding(AppState.student.id, AppState.term.id);
                    if (Utils.isResponseSuccess(response)) {
                        this.render(Utils.getResponseData(response));
                    } else {
                        $('#guidingCard').hide();
                    }
                } catch (error) {
                    Utils.handleError(error);
                    $('#guidingCard').hide();
                }
            },
            render(data) {
                // Keep existing render logic
                const history = data.courses_history || {};
                const studyPlan = data.study_plan_courses || {};
                const missing = data.missing_courses || {};

                $('#guideSemesterNo').text(studyPlan.semester_no || data.semester_no || '-');

                const passedCount = (history.passed_courses || []).length;
                const failedCount = (history.failed_courses || []).length + (history.incomplete_courses || []).length;
                const studyPlanCoreCount = (studyPlan.courses || []).length;
                const electiveCount = studyPlan.elective_info?.count || 0;
                const missingCoreCount = (missing.core || []).length;
                const missingElectiveCount = missing.electives?.count || 0;

                $('#passedCount').text(passedCount);
                $('#failedCount').text(failedCount);
                $('#studyPlanCount').text(studyPlanCoreCount + (electiveCount > 0 ? '+' + electiveCount : ''));
                $('#missingCount').text(missingCoreCount + (missingElectiveCount > 0 ? '+' + missingElectiveCount : ''));

                $('#guideSummaryStats').html(`
                        <div class="text-center">
                            <small class="text-muted d-block">Completed</small>
                            <span class="fw-bold text-success">${passedCount}</span>
                        </div>
                        <div class="text-center">
                            <small class="text-muted d-block">Current</small>
                            <span class="fw-bold text-primary">${studyPlanCoreCount + electiveCount}</span>
                        </div>
                        <div class="text-center">
                            <small class="text-muted d-block">Missing</small>
                            <span class="fw-bold text-warning">${missingCoreCount + missingElectiveCount}</span>
                        </div>
                    `);

                this.renderList('#passedCoursesList', history.passed_courses || [], 'course');

                const failedWithStatus = (history.failed_courses || []).map(c => ({ ...c, statusBadge: { text: 'Failed', class: 'bg-label-danger' } }));
                const incompleteWithStatus = (history.incomplete_courses || []).map(c => ({ ...c, statusBadge: { text: 'Incomplete', class: 'bg-label-warning' } }));
                this.renderList('#failedCoursesList', [...failedWithStatus, ...incompleteWithStatus], 'course');

                const studyPlanWithStatus = (studyPlan.courses || []).map(c => {
                    if (c.is_passed) {
                        return { ...c, statusBadge: { text: 'Passed', class: 'bg-label-success' } };
                    } else if (c.is_incomplete) {
                        return { ...c, statusBadge: { text: 'Enrolled', class: 'bg-label-warning' } };
                    }
                    return c;
                });
                this.renderList('#studyPlanCoursesList', studyPlanWithStatus, 'course', true);

                if (studyPlan.elective_info && studyPlan.elective_info.count > 0) {
                    const slotCodes = (studyPlan.elective_info.codes || []).join(', ');
                    const codesDisplay = slotCodes ? ` (${slotCodes})` : '';
                    let electiveHtml = `<div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="text-primary">Elective Courses${codesDisplay}: Choose ${studyPlan.elective_info.count}</strong>
                            </div>
                            <div class="ms-1">`;
                    this.renderElectivePool(studyPlan.elective_info.pool, electiveHtml, '#studyPlanCoursesList');
                }

                this.renderList('#missingCoursesList', missing.core || [], 'course', true);

                if (missing.electives && missing.electives.count > 0) {
                    const slotCodes = (missing.electives.codes || []).join(', ');
                    const codesDisplay = slotCodes ? ` (${slotCodes})` : '';
                    let electiveHtml = `<div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="text-warning">Missing Electives${codesDisplay}: Need ${missing.electives.count}</strong>
                            </div>
                            <div class="ms-1">`;
                    this.renderElectivePool(missing.electives.pool, electiveHtml, '#missingCoursesList');
                }
            },
            renderElectivePool(pool, containerHtml, targetSelector) {
                let html = containerHtml;
                if (Array.isArray(pool) && pool.length > 0) {
                    pool.forEach(item => {
                        const courseName = this.formatCourseName(item.course);
                        let itemColorClass = 'text-secondary';
                        let statusBadge = '';
                        let statusIcon = 'bx-caret-right';

                        if (item.is_passed) {
                            itemColorClass = 'text-success';
                            statusIcon = 'bx-check-circle';
                            statusBadge = `<span class="badge bg-label-success ms-1" style="font-size:0.7em">Already Taken</span>`;
                        } else if (item.is_incomplete) {
                            itemColorClass = 'text-danger';
                            statusIcon = 'bx-error';
                            statusBadge = `<span class="badge bg-label-danger ms-1" style="font-size:0.7em">Incomplete/Failed</span>`;
                        } else if (item.available === false) {
                            itemColorClass = 'text-danger';
                            statusIcon = 'bx-lock-alt';
                            statusBadge = `<span class="badge bg-label-danger ms-1" style="font-size:0.7em">Locked</span>`;
                        } else {
                            itemColorClass = 'text-warning';
                            statusIcon = 'bx-info-circle';
                            statusBadge = `<span class="badge bg-label-warning ms-1" style="font-size:0.7em">Available</span>`;
                        }

                        html += `<div class="ps-3 mb-1 small ${itemColorClass} d-flex align-items-center">
                                <i class="bx ${statusIcon} me-1"></i>
                                <span>${courseName}</span>
                                ${statusBadge}
                            </div>`;
                    });
                }
                html += `</div></div>`;
                $(targetSelector).append(html);
            },
            renderList(selector, items, path, checkAvailability = false) {
                const $list = $(selector);
                $list.empty();

                if (items.length === 0) {
                    $list.append('<div class="text-muted italic">None</div>');
                    return;
                }

                items.forEach(item => {
                    const course = this.getValueByPath(item, path);
                    if (!course) return;

                    const name = this.formatCourseName(course);
                    let html = `<div><i class="bx bx-caret-right text-primary me-1"></i>${name}`;

                    if (item.statusBadge) {
                        html += ` <span class="badge ${item.statusBadge.class} ms-1" style="font-size: 0.65em;">${item.statusBadge.text}</span>`;
                    }

                    if (checkAvailability && item.available === false) {
                        html += ` <span class="badge bg-label-danger ms-1" title="${item.reason || 'Locked'}"><i class="bx bx-lock-alt"></i></span>`;
                    }

                    html += '</div>';
                    $list.append(html);
                });
            },
            formatCourseName(course) {
                if (!course) return 'Unknown';
                if (course.name) return course.name;
                if (course.title && course.code) return `${course.title} (${course.code})`;
                return course.title || course.code || 'Unknown';
            },
            getValueByPath(obj, path) {
                return path.split('.').reduce((acc, part) => acc && acc[part], obj);
            }
        };

        const SubmissionManager = {
            init() {
                $('#enrollForm').on('submit', e => {
                    e.preventDefault();
                    this.submit();
                });
            },
            async submit() {
                if ($('.course-checkbox:checked').length === 0) {
                    return Utils.showError('Select at least one course');
                }

                const formData = new FormData();
                formData.append('student_id', AppState.student.id);
                formData.append('term_id', AppState.term.id);

                let index = 0;
                AppState.selections.courseGroups.forEach((groupData, courseId) => {
                    if ($(`#course_${courseId}`).is(':checked')) {
                        const scheduleIds = groupData.group_activities.map(act => act.id);
                        formData.append(`enrollments[${index}][available_course_id]`, courseId);
                        scheduleIds.forEach(id => {
                            formData.append(`enrollments[${index}][selected_schedule_ids][]`, id);
                        });
                        formData.append(`enrollments[${index}][create_schedule]`, true);
                        index++;
                    }
                });

                const $btn = $('#enrollBtn');
                Utils.setLoadingState($btn, true, { loadingText: 'Enrolling...' });

                try {
                    const response = await ApiService.submitEnrollment(formData);
                    if (Utils.isResponseSuccess(response)) {
                        Utils.showSuccess('Enrollment successful!');
                        
                        // Refresh enrollment history
                        const enrollmentsResponse = await ApiService.fetchStudentEnrollments(AppState.student.id);
                        if (Utils.isResponseSuccess(enrollmentsResponse)) {
                            AppState.setEnrollmentHistory(Utils.getResponseData(enrollmentsResponse) || []);
                            EnrollmentHistoryManager.load();
                        }
                        
                        // Reset selections and reload UI
                        AppState.resetSelections();
                        ScheduleManager.initialize();
                        AvailableCoursesManager.load();
                        UI.updateEnrollButton();
                        CreditHoursManager.load();
                        GuidingManager.load();
                    }
                } catch (error) {
                    Utils.handleError(error);
                } finally {
                    Utils.setLoadingState($btn, false);
                }
            }
        };

        const UI = {
            updateEnrollButton() {
                const count = $('.course-checkbox:checked').length;
                const $btn = $('#enrollBtn');
                if (count > 0) {
                    $btn.html(`<i class="bx bx-plus me-1"></i>Enroll Selected Courses (${count})`).prop('disabled', false);
                } else {
                    $btn.html('<i class="bx bx-plus me-1"></i>Enroll Selected Courses').prop('disabled', true);
                }
            }
        };

        const SearchManager = {
            init() {
                $('#historySearch').on('input', Utils.debounce(e => {
                    EnrollmentHistoryManager.filter(e.target.value.toLowerCase());
                }, 300));
                $('#coursesSearch').on('input', Utils.debounce(e => {
                    AvailableCoursesManager.filter(e.target.value.toLowerCase());
                }, 300));
                $('#exceptionForDifferentLevels').on('change', () => AvailableCoursesManager.load());
            }
        };

        const TimetableDownloadManager = {
            init() {
                $(document).on('click', '#downloadTimetableBtn', async function () {
                    const $btn = $(this);
                    Utils.setLoadingState($btn, true, { loadingText: 'Preparing...' });

                    try {
                        const scheduleElement = document.getElementById('weeklyScheduleCard');
                        if (!scheduleElement) {
                            Utils.showError('Schedule not found');
                            return;
                        }

                        const canvas = await html2canvas(scheduleElement, {
                            scale: 2,
                            useCORS: true,
                            allowTaint: true,
                            backgroundColor: '#ffffff'
                        });

                        const link = document.createElement('a');
                        link.download = `timetable_${AppState.student.id}_${AppState.term.id}.png`;
                        link.href = canvas.toDataURL('image/png');
                        link.click();

                        Utils.showSuccess('Timetable downloaded successfully!');
                    } catch (error) {
                        console.error('Download failed:', error);
                        Utils.showError('Failed to download timetable');
                    } finally {
                        Utils.setLoadingState($btn, false);
                    }
                });
            }
        };

        const EnrollmentApp = {
            async init() {
                await Select2Manager.loadTerms();
                Select2Manager.init();
                StudentManager.init();
                TermManager.init();
                SearchManager.init();
                SubmissionManager.init();
                TimetableDownloadManager.init();
                Utils.hidePageLoader();
            },
            resetCourseRelated() {
                AppState.resetSelections();
                $('#weeklyScheduleCard').hide();
                $('#guidingCard').hide();
                AvailableCoursesManager.load();
                ScheduleManager.initialize();
                GuidingManager.load();
            }
        };

        $(function () {
            EnrollmentApp.init();
        });
    </script>
@endpush