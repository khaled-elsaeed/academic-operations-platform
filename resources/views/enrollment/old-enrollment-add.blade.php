@extends('layouts.home')

@section('title', 'Student Enrollment (Grade Only) | AcadOps')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/enrollment.css') }}">
@endpush

@section('page-content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Page Header -->
        <x-ui.page-header title="Student Enrollment (Grade Only)"
            description="Enroll students in courses with grade assignments (without schedule)"
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

            <!-- Main Content Area -->
            <div class="row">
                <!-- Course Enrollment Section -->
                <div class="col-12 col-lg-8 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-light">
                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between mb-2">
                                <div class="d-flex align-items-center mb-2 mb-sm-0">
                                    <i class="bx bx-book me-2 text-primary"></i>
                                    <h5 class="mb-0 text-dark">Course Enrollment</h5>
                                    <span class="badge bg-primary text-white ms-2" id="enrollmentCount">0</span>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="addEnrollmentRowBtn">
                                    <i class="bx bx-plus me-1"></i>
                                    Add Course
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="enrollmentForm">
                                <input type="hidden" id="student_id" name="student_id">
                                <div id="enrollmentRowsContainer" class="courses-container">
                                    <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                                        <div class="text-center text-muted">
                                            <i class="bx bx-book-bookmark display-4 mb-3 text-primary"></i>
                                            <p class="text-dark">Find a student to add course enrollments</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-success w-100 shadow-sm" id="submitEnrollmentBtn" style="display:none;">
                                        <i class="bx bx-check me-1"></i>
                                        Submit Enrollment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Enrollment History -->
                <div class="col-12 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-light">
                            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between mb-2">
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
                            <div id="enrollmentHistoryBox" class="enrollment-history-container" style="max-height: 400px; overflow-y: auto;">
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
            </div>
        </div>
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
            enrollments: {
                history: [],
                rows: []
            },

            reset() {
                this.student = null;
                this.enrollments = { history: [], rows: [] };
            },

            setStudent(student) {
                this.student = student;
            },

            setEnrollmentHistory(enrollments) {
                this.enrollments.history = enrollments || [];
            }
        };

        // ===========================
        // ROUTES & API
        // ===========================
        const ROUTES = {
            enrollment: {
                studentEnrollments: @json(route('enrollments.studentEnrollments')),
                store: @json(route('enrollments.storeWithoutSchedule')),
                termsWithInactive: @json(route('terms.all.with_inactive'))
            },
            student: {
                find: @json(route('students.show', ':id')),
            },
            courses: {
                all: @json(route('courses.all'))
            }
        };

        const ApiService = {
            findStudent(identifier) {
                return Utils.get(Utils.replaceRouteId(ROUTES.student.find, identifier));
            },
            fetchStudentEnrollments(studentId) {
                return Utils.post(ROUTES.enrollment.studentEnrollments, { student_id: studentId });
            },
            submitEnrollment(formData) {
                return Utils.post(ROUTES.enrollment.store, formData, { processData: false, contentType: false });
            },
            fetchTerms() {
                return Utils.get(ROUTES.enrollment.termsWithInactive);
            }
        };

        // ===========================
        // MANAGERS
        // ===========================
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
                            $('#studentDetails').show();

                            // Load enrollment history
                            const enrollmentsResponse = await ApiService.fetchStudentEnrollments(student.id);
                            if (Utils.isResponseSuccess(enrollmentsResponse)) {
                                AppState.setEnrollmentHistory(Utils.getResponseData(enrollmentsResponse));
                            }
                            EnrollmentHistoryManager.load();
                            EnrollmentRowManager.addInitialRow();
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
                    </div>
                `;
                $('#studentInfo').html(html);
            }
        };

        // ===========================
        // ENROLLMENT ROW MANAGER
        // ===========================
        const EnrollmentRowManager = {
            rowCount: 0,
            termsLoaded: false,
            termsData: [],

            init() {
                this.bindEvents();
                this.loadTermsData();
            },

            bindEvents() {
                $('#addEnrollmentRowBtn').on('click', () => {
                    this.addRow();
                });

                $(document).on('click', '.remove-enrollment-row', function() {
                    $(this).closest('.enrollment-row').remove();
                    EnrollmentRowManager.updateSubmitButton();
                    EnrollmentRowManager.updateCount();
                });
            },

            async loadTermsData() {
                try {
                    const response = await ApiService.fetchTerms();
                    if (Utils.isResponseSuccess(response)) {
                        this.termsData = Utils.getResponseData(response);
                        this.termsLoaded = true;
                    }
                } catch (error) {
                    Utils.handleError(error);
                }
            },

            addInitialRow() {
                $('#enrollmentRowsContainer').empty();
                this.rowCount = 0;
                this.addRow();
            },

            addRow() {
                this.rowCount++;
                const rowHtml = this.generateRowHtml(this.rowCount);
                $('#enrollmentRowsContainer').append(rowHtml);

                const $newRow = $('#enrollmentRowsContainer .enrollment-row:last');
                this.initializeSelects($newRow);
                this.updateSubmitButton();
                this.updateCount();
            },

            generateRowHtml(rowNumber) {
                return `
                    <div class="enrollment-row border rounded p-3 mb-3 bg-light" data-row="${rowNumber}">
                        <div class="row align-items-end">
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="bx bx-calendar-alt me-1"></i> Academic Term <span class="text-danger">*</span>
                                </label>
                                <select class="form-select term-select border-primary" id="term_${rowNumber}"
                                        name="enrollment_data[${rowNumber}][term_id]" required>
                                    <option value="">Select academic term</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2 mb-md-0">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="bx bx-book me-1"></i> Course <span class="text-danger">*</span>
                                </label>
                                <select class="form-select course-select border-primary" id="course_${rowNumber}"
                                        name="enrollment_data[${rowNumber}][course_id]" required>
                                    <option value="">Select a course</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="form-label fw-semibold text-dark">
                                    <i class="bx bx-star me-1"></i> Grade
                                </label>
                                <input type="text" class="form-control grade-input border-primary"
                                       name="enrollment_data[${rowNumber}][grade]"
                                       placeholder="e.g., A+, B, C-"
                                       maxlength="5">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-danger w-100 remove-enrollment-row"
                                        title="Remove this course">
                                    <i class="bx bx-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            },

            initializeSelects($row) {
                const $termSelect = $row.find('.term-select');
                const $courseSelect = $row.find('.course-select');

                // Populate terms
                this.populateTerms($termSelect);

                // Initialize term select with Select2
                Utils.initSelect2($termSelect, {
                    placeholder: 'Select academic term',
                    allowClear: true,
                    width: '100%'
                });

                // Initialize course select with Select2 and AJAX
                $courseSelect.select2({
                    theme: 'bootstrap-5',
                    placeholder: 'Select a course',
                    allowClear: true,
                    width: '100%',
                    ajax: {
                        url: ROUTES.courses.all,
                        dataType: 'json',
                        delay: 300,
                        data: function(params) {
                            return {
                                student_id: AppState.student?.id,
                                term_id: $termSelect.val(),
                                search: params.term
                            };
                        },
                        processResults: function(data) {
                            const items = Array.isArray(data) ? data : (data.data || data.courses || []);
                            return {
                                results: items.map(item => {
                                    const course = item.course || item;
                                    return {
                                        id: course.id || item.available_course_id,
                                        text: `${course.code || ''} - ${course.name || ''} (${course.credit_hours || 0} CH)`
                                    };
                                })
                            };
                        },
                        cache: true
                    }
                });

                // Update course options when term changes
                $termSelect.on('change', function() {
                    $courseSelect.val(null).trigger('change');
                });
            },

            populateTerms($select) {
                if (this.termsData.length > 0) {
                    this.termsData.forEach(term => {
                        $select.append(`<option value="${term.id}">${Utils.escapeHtml(term.name)}</option>`);
                    });
                }
            },

            updateSubmitButton() {
                const hasRows = $('.enrollment-row').length > 0;
                $('#submitEnrollmentBtn').toggle(hasRows);
            },

            updateCount() {
                const count = $('.enrollment-row').length;
                $('#enrollmentCount').text(count);
            },

            collectData() {
                const data = [];
                $('.enrollment-row').each(function() {
                    const termId = $(this).find('.term-select').val();
                    const courseId = $(this).find('.course-select').val();
                    const grade = $(this).find('.grade-input').val();

                    if (termId && courseId) {
                        data.push({
                            term_id: termId,
                            course_id: courseId,
                            grade: grade || null
                        });
                    }
                });
                return data;
            }
        };

        // ===========================
        // ENROLLMENT HISTORY MANAGER
        // ===========================
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
                    const courseCode = Utils.escapeHtml(enrollment.course?.code || '');
                    const termName = Utils.escapeHtml(enrollment.term?.name || 'Unknown Term');
                    const gradeBadge = enrollment.grade
                        ? `<span class="badge bg-primary text-white"><i class="bx bx-star me-1"></i>Grade: <strong>${Utils.escapeHtml(enrollment.grade)}</strong></span>`
                        : '<span class="badge bg-secondary text-white"><i class="bx bx-time me-1"></i>No Grade Yet</span>';

                    html += `
                        <div class="history-item border rounded p-3 mb-2">
                            <h6 class="mb-1 text-dark">${courseName}</h6>
                            <p class="text-muted mb-1 small">
                                <i class="bx bx-code-alt me-1"></i>${courseCode}
                            </p>
                            <p class="text-muted mb-2">
                                <i class="bx bx-calendar me-1"></i>
                                <strong>${termName}</strong>
                            </p>
                            ${gradeBadge}
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

        // ===========================
        // SUBMISSION MANAGER
        // ===========================
        const SubmissionManager = {
            init() {
                $('#enrollmentForm').on('submit', e => {
                    e.preventDefault();
                    this.submit();
                });
            },

            async submit() {
                const enrollmentData = EnrollmentRowManager.collectData();

                if (enrollmentData.length === 0) {
                    return Utils.showWarning('Please add at least one course');
                }

                const formData = new FormData();
                formData.append('student_id', AppState.student.id);
                formData.append('enrollment_type', 'without_schedule');
                formData.append('create_schedule', 'false');

                enrollmentData.forEach((item, index) => {
                    formData.append(`enrollment_data[${index}][term_id]`, item.term_id);
                    formData.append(`enrollment_data[${index}][course_id]`, item.course_id);
                    if (item.grade) {
                        formData.append(`enrollment_data[${index}][grade]`, item.grade);
                    }
                });

                const $btn = $('#submitEnrollmentBtn');
                Utils.setLoadingState($btn, true, { loadingText: 'Enrolling...' });

                try {
                    const response = await ApiService.submitEnrollment(formData);
                    if (Utils.isResponseSuccess(response)) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Enrollment Successful!',
                            html: `Successfully enrolled in <strong>${enrollmentData.length}</strong> course(s) with grades.`,
                            confirmButtonText: 'Continue'
                        });

                        // Reset form and reload history
                        EnrollmentRowManager.addInitialRow();
                        const enrollmentsResponse = await ApiService.fetchStudentEnrollments(AppState.student.id);
                        if (Utils.isResponseSuccess(enrollmentsResponse)) {
                            AppState.setEnrollmentHistory(Utils.getResponseData(enrollmentsResponse));
                        }
                        EnrollmentHistoryManager.load();
                    }
                } catch (error) {
                    Utils.handleError(error);
                } finally {
                    Utils.setLoadingState($btn, false);
                }
            }
        };

        // ===========================
        // SEARCH MANAGER
        // ===========================
        const SearchManager = {
            init() {
                $('#historySearch').on('input', Utils.debounce(e => {
                    EnrollmentHistoryManager.filter(e.target.value.toLowerCase());
                }, 300));
            }
        };

        // ===========================
        // MAIN APPLICATION
        // ===========================
        const EnrollmentApp = {
            async init() {
                StudentManager.init();
                EnrollmentRowManager.init();
                SubmissionManager.init();
                SearchManager.init();
                Utils.hidePageLoader();
            }
        };

        $(function() {
            EnrollmentApp.init();
        });
    </script>
@endpush