@extends('layouts.home')

@section('title', 'Student Enrollment | AcadOps')
@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  
  <!-- Page Header -->
  <x-ui.page-header 
    title="Student Enrollment (Grade Only)"
    description="Enroll students in courses with grade assignments"
    icon="bx bx-user-plus"
  />

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
            <label for="identifier" class="form-label fw-semibold text-dark">National ID or Academic ID</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-primary">
                <i class="bx bx-id-card text-primary"></i>
              </span>
              <input type="text" class="form-control border-primary" id="identifier" name="identifier" required 
                     placeholder="Enter National ID or Academic ID">
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

  <!-- Student Details Section -->
  <div id="studentDetailsSection" style="display:none;">
    
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

    <!-- Course Enrollment Section -->
    <div class="card mb-4 shadow-sm">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <i class="bx bx-book me-2 text-primary"></i>
          <h5 class="mb-0 text-dark">Course Enrollment</h5>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" id="addEnrollmentRowBtn">
          <i class="bx bx-plus me-1"></i>
          Add Course
        </button>
      </div>
      <div class="card-body">
        <form id="enrollmentForm">
          <input type="hidden" id="student_id" name="student_id">
          <div id="enrollmentRowsContainer">
            <!-- Dynamic enrollment rows will be added here -->
          </div>
          <div class="mt-3">
            <button type="submit" class="btn btn-success w-100" id="submitEnrollmentBtn" style="display:none;">
              <span class="normal-text">
                <i class="bx bx-check me-1"></i>
                Submit Enrollment
              </span>
              <span class="loading-text" style="display: none;">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Processing...
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Enrollment History -->
    <div class="card shadow-sm">
      <div class="card-header bg-light">
        <div class="d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center">
            <i class="bx bx-history me-2 text-primary"></i>
            <h5 class="mb-0 text-dark">Enrollment History</h5>
            <span class="badge bg-primary text-white ms-2" id="historyCount">0</span>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="input-group input-group-sm mb-3">
          <span class="input-group-text bg-light border-primary">
            <i class="bx bx-search text-primary"></i>
          </span>
          <input type="text" class="form-control border-primary" id="historySearch" 
                 placeholder="Search courses, terms, or grades...">
        </div>
        <div id="enrollmentHistoryContainer" style="max-height: 400px; overflow-y: auto;">
          <!-- History will be populated here -->
        </div>
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/utils.js') }}?v={{ config('app.version') }}"></script>
<script>
// ===========================
// ROUTES CONSTANTS
// ===========================
const ROUTES = {
    student: {
        find: '{{ route("enrollments.findStudent") }}',
        enrollments: '{{ route("enrollments.studentEnrollments") }}'
    },
    enrollment: {
        store: '{{ route("enrollments.storeWithoutSchedule") }}'
    },
    terms: {
        all: '{{ route("terms.all.with_inactive") }}'
    },
    courses: {
        all: '{{ route("courses.all") }}'
    }
};

// ===========================
// TRANSLATION CONSTANTS
// ===========================
const TRANSLATION = {
    placeholders: {
        selectTerm: 'Select academic term',
        selectCourse: 'Select a course',
        enterGrade: 'e.g., A+, B, C-'
    },
    messages: {
        studentNotFound: 'Student not found',
        enrollmentSuccess: 'Enrollment successful!',
        enrollmentError: 'Enrollment failed',
        noCoursesAdded: 'Please add at least one course',
        missingTerm: 'Please select an academic term for row',
        missingCourse: 'Please select a course for row'
    }
};

// ===========================
// API SERVICE
// ===========================
const ApiService = {
    request(options) {
        return $.ajax(options);
    },
    
    findStudent(identifier) {
        return this.request({
            url: ROUTES.student.find,
            method: 'POST',
            data: { identifier: identifier }
        });
    },
    
    getStudentEnrollments(studentId) {
        return this.request({
            url: ROUTES.student.enrollments,
            method: 'POST',
            data: { student_id: studentId }
        });
    },
    
    submitEnrollment(data) {
        return this.request({
            url: ROUTES.enrollment.store,
            method: 'POST',
            data: data,
            processData: false,
            contentType: false
        });
    },
    
    getTerms() {
        return this.request({
            url: ROUTES.terms.all,
            method: 'GET'
        });
    },
    
    searchCourses(params) {
        return this.request({
            url: ROUTES.courses.all,
            method: 'GET',
            data: params
        });
    }
};

// ===========================
// STUDENT MANAGER
// ===========================
const StudentManager = {
    currentStudentId: null,
    
    init() {
        this.bindEvents();
    },
    
    bindEvents() {
        $('#findStudentForm').on('submit', (e) => {
            e.preventDefault();
            this.searchStudent();
        });
    },
    
    searchStudent() {
        const identifier = $('#identifier').val();
        
        ApiService.findStudent(identifier)
            .done((response) => {
                if (response.success && response.data) {
                    this.displayStudent(response.data);
                    this.currentStudentId = response.data.id;
                    $('#student_id').val(response.data.id);
                    $('#studentDetailsSection').show();
                    EnrollmentHistoryManager.loadHistory(response.data.id);
                    EnrollmentRowManager.addInitialRow();
                }
            })
            .fail((xhr) => {
                $('#studentDetailsSection').hide();
                Utils.showError(xhr.responseJSON?.message || TRANSLATION.messages.studentNotFound);
            });
    },
    
    displayStudent(student) {
        const html = `
            <div class="col-12 col-md-4">
                <div class="student-info-item">
                    <small class="text-muted">Full Name (English)</small>
                    <h6 class="mb-0 text-dark">${student.name_en || 'N/A'}</h6>
                </div>
                <div class="student-info-item">
                    <small class="text-muted">Full Name (Arabic)</small>
                    <h6 class="mb-0 text-dark">${student.name_ar || 'N/A'}</h6>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="student-info-item">
                    <small class="text-muted">Academic ID</small>
                    <h6 class="mb-0 text-dark">${student.academic_id || 'N/A'}</h6>
                </div>
                <div class="student-info-item">
                    <small class="text-muted">National ID</small>
                    <h6 class="mb-0 text-dark">${student.national_id || 'N/A'}</h6>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="student-info-item">
                    <small class="text-muted">Program</small>
                    <h6 class="mb-0 text-dark">${student.program?.name || 'Not Assigned'}</h6>
                </div>
                <div class="student-info-item">
                    <small class="text-muted">Level</small>
                    <h6 class="mb-0 text-dark">Level ${student.level?.name || 'N/A'}</h6>
                </div>
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
    
    init() {
        this.bindEvents();
    },
    
    bindEvents() {
        $('#addEnrollmentRowBtn').on('click', () => {
            this.addRow();
        });
        
        $(document).on('click', '.remove-enrollment-row', function() {
            $(this).closest('.enrollment-row').remove();
            EnrollmentRowManager.updateSubmitButton();
        });
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
    },
    
    generateRowHtml(rowNumber) {
        return `
            <div class="enrollment-row border rounded p-3 mb-3 bg-light" data-row="${rowNumber}">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            Academic Term <span class="text-danger">*</span>
                        </label>
                        <select class="form-select term-select" id="term_${rowNumber}" 
                                name="enrollment_data[${rowNumber}][term_id]" required>
                            <option value="">${TRANSLATION.placeholders.selectTerm}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Course <span class="text-danger">*</span>
                        </label>
                        <select class="form-select course-select" id="course_${rowNumber}" 
                                name="enrollment_data[${rowNumber}][course_id]" required>
                            <option value="">${TRANSLATION.placeholders.selectCourse}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Grade</label>
                        <input type="text" class="form-control grade-input" 
                               name="enrollment_data[${rowNumber}][grade]" 
                               placeholder="${TRANSLATION.placeholders.enterGrade}" 
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
        
        // Initialize term select
        this.loadTerms($termSelect);
        
        // Initialize course select with Select2
        $courseSelect.select2({
            theme: 'bootstrap-5',
            placeholder: TRANSLATION.placeholders.selectCourse,
            allowClear: true,
            width: '100%',
            ajax: {
                url: ROUTES.courses.all,
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return {
                        student_id: StudentManager.currentStudentId,
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
    
    loadTerms($select) {
        ApiService.getTerms()
            .done((response) => {
                const terms = response.data || [];
                terms.forEach(term => {
                    $select.append(`<option value="${term.id}">${term.name}</option>`);
                });
                
                $select.select2({
                    theme: 'bootstrap-5',
                    placeholder: TRANSLATION.placeholders.selectTerm,
                    allowClear: true,
                    width: '100%'
                });
            });
    },
    
    updateSubmitButton() {
        const hasRows = $('.enrollment-row').length > 0;
        $('#submitEnrollmentBtn').toggle(hasRows);
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
    originalData: [],
    
    init() {
        this.bindEvents();
    },
    
    bindEvents() {
        $('#historySearch').on('input', (e) => {
            const searchTerm = $(e.target).val().toLowerCase();
            this.filterHistory(searchTerm);
        });
    },
    
    loadHistory(studentId) {
        ApiService.getStudentEnrollments(studentId)
            .done((response) => {
                const history = response.data || [];
                this.originalData = history;
                this.displayHistory(history);
                $('#historyCount').text(history.length);
            })
            .fail(() => {
                this.displayEmpty();
                $('#historyCount').text('0');
            });
    },
    
    displayHistory(enrollments) {
        if (enrollments.length === 0) {
            this.displayEmpty();
            return;
        }
        
        let html = '';
        enrollments.forEach(enrollment => {
            html += `
                <div class="border rounded p-3 mb-2">
                    <h6 class="mb-1 text-dark">
                        ${enrollment.course?.name || 'Course #' + enrollment.course_id}
                    </h6>
                    <p class="text-muted mb-1">
                        <i class="bx bx-calendar me-1"></i>
                        <strong>${enrollment.term?.name || 'Term #' + enrollment.term_id}</strong>
                    </p>
                    ${enrollment.grade ? 
                        `<span class="badge bg-primary text-white">
                            <i class="bx bx-star me-1"></i>Grade: ${enrollment.grade}
                        </span>` : 
                        `<span class="badge bg-secondary text-white">
                            <i class="bx bx-time me-1"></i>No Grade Yet
                        </span>`
                    }
                </div>
            `;
        });
        
        $('#enrollmentHistoryContainer').html(html);
    },
    
    displayEmpty() {
        $('#enrollmentHistoryContainer').html(`
            <div class="text-center text-muted py-4">
                <i class="bx bx-info-circle display-4 mb-3"></i>
                <p>No enrollment history found</p>
            </div>
        `);
    },
    
    filterHistory(searchTerm) {
        if (!this.originalData.length) return;
        
        const filtered = this.originalData.filter(enrollment => {
            const courseName = (enrollment.course?.name || '').toLowerCase();
            const termName = (enrollment.term?.name || '').toLowerCase();
            const grade = (enrollment.grade || '').toLowerCase();
            
            return courseName.includes(searchTerm) || 
                   termName.includes(searchTerm) || 
                   grade.includes(searchTerm);
        });
        
        this.displayHistory(filtered);
    }
};

// ===========================
// ENROLLMENT SUBMISSION MANAGER
// ===========================
const EnrollmentSubmissionManager = {
    init() {
        this.bindEvents();
    },
    
    bindEvents() {
        $('#enrollmentForm').on('submit', (e) => {
            e.preventDefault();
            this.submitEnrollment();
        });
    },
    
    submitEnrollment() {
        const enrollmentData = EnrollmentRowManager.collectData();
        
        // Validate data
        if (enrollmentData.length === 0) {
            Utils.showWarning(TRANSLATION.messages.noCoursesAdded);
            return;
        }
        
        // Prepare form data
        const formData = new FormData();
        formData.append('student_id', StudentManager.currentStudentId);
        formData.append('enrollment_type', 'without_schedule');
        formData.append('create_schedule', 'false');
        
        enrollmentData.forEach((item, index) => {
            formData.append(`enrollment_data[${index}][term_id]`, item.term_id);
            formData.append(`enrollment_data[${index}][course_id]`, item.course_id);
            if (item.grade) {
                formData.append(`enrollment_data[${index}][grade]`, item.grade);
            }
        });
        
        // Show loading state
        const $submitBtn = $('#submitEnrollmentBtn');
        Utils.setLoadingState($submitBtn, true);
        
        // Submit data
        ApiService.submitEnrollment(formData)
            .done((response) => {
                this.handleSuccess(response, enrollmentData.length);
            })
            .fail((xhr) => {
                this.handleError(xhr);
            })
            .always(() => {
                Utils.setLoadingState($submitBtn, false);
            });
    },
    
    handleSuccess(response, enrolledCount) {
        Swal.fire({
            icon: 'success',
            title: TRANSLATION.messages.enrollmentSuccess,
            html: `Successfully enrolled in <strong>${enrolledCount}</strong> course(s) with grades.`,
            confirmButtonText: 'Continue'
        }).then(() => {
            // Reset form and reload history
            EnrollmentRowManager.addInitialRow();
            EnrollmentHistoryManager.loadHistory(StudentManager.currentStudentId);
        });
    },
    
    handleError(xhr) {
        let errorMessage = TRANSLATION.messages.enrollmentError;
        if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
        }
        
        Utils.showError(errorMessage);
    }
};



// ===========================
// MAIN APPLICATION
// ===========================
const EnrollmentApp = {
    init() {
        StudentManager.init();
        EnrollmentRowManager.init();
        EnrollmentHistoryManager.init();
        EnrollmentSubmissionManager.init();
        Utils.hidePageLoader();
    }
};

// ===========================
// DOCUMENT READY
// ===========================
$(document).ready(function() {
    try {
        EnrollmentApp.init();
    } catch (error) {
        console.error('Error initializing EnrollmentApp:', error);
    }
});
</script>
@endpush