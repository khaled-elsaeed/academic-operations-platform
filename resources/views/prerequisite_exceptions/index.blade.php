@extends('layouts.home')

@section('title', 'Prerequisite Exceptions Management | AcadOps')

@section('page-content')
    <div class="container-xxl flex-grow-1 container-p-y">
        {{-- ===== STATISTICS CARDS ===== --}}
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-4">
                <x-ui.card.stat2 id="exceptions" label="Total Exceptions" color="primary" icon="bx bx-error" />
            </div>
            <div class="col-sm-6 col-xl-4">
                <x-ui.card.stat2 id="active-exceptions" label="Active Exceptions" color="success"
                    icon="bx bx-check-circle" />
            </div>
            <div class="col-sm-6 col-xl-4">
                <x-ui.card.stat2 id="inactive-exceptions" label="Inactive Exceptions" color="danger"
                    icon="bx bx-x-circle" />
            </div>
        </div>

        {{-- ===== PAGE HEADER & ACTION BUTTONS ===== --}}
        <x-ui.page-header title="Prerequisite Exceptions"
            description="Manage exceptions to course prerequisites for students." icon="bx bx-error">
            @can('prerequisite_exception.create')
                <button class="btn btn-primary mx-2" id="addExceptionBtn" type="button" data-bs-toggle="modal"
                    data-bs-target="#exceptionModal">
                    <i class="bx bx-plus me-1"></i> Add Exception
                </button>
                <button class="btn btn-outline-primary mx-2" id="importExceptionsBtn" type="button" data-bs-toggle="modal"
                    data-bs-target="#importExceptionsModal">
                    <i class="bx bx-upload me-1"></i> Import
                </button>
                <a class="btn btn-outline-secondary mx-2" href="{{ route('prerequisite-exceptions.download-template') }}">
                    <i class="bx bx-download me-1"></i> Template
                </a>
            @endcan
            <button class="btn btn-secondary" type="button" data-bs-toggle="collapse"
                data-bs-target="#exceptionSearchCollapse" aria-expanded="false" aria-controls="exceptionSearchCollapse">
                <i class="bx bx-filter-alt me-1"></i> Search
            </button>
        </x-ui.page-header>

        {{-- ===== ADVANCED SEARCH SECTION ===== --}}
        <x-ui.advanced-search title="Advanced Search" formId="advancedExceptionSearch" collapseId="exceptionSearchCollapse"
            :collapsed="false">
            <div class="col-md-3">
                <label for="search_student_name" class="form-label">Student Name:</label>
                <input type="text" class="form-control" id="search_student_name" placeholder="Student Name">
            </div>
            <div class="col-md-3">
                <label for="search_academic_id" class="form-label">Academic ID:</label>
                <input type="text" class="form-control" id="search_academic_id" placeholder="Academic ID">
            </div>
            <div class="col-md-3">
                <label for="search_course" class="form-label">Course:</label>
                <input type="text" class="form-control" id="search_course" placeholder="Course Code/Title">
            </div>
            <div class="col-md-3">
                <label for="search_prerequisite" class="form-label">Prerequisite:</label>
                <input type="text" class="form-control" id="search_prerequisite" placeholder="Prerequisite Code/Title">
            </div>
            <div class="col-md-3">
                <label for="search_term" class="form-label">Term:</label>
                <input type="text" class="form-control" id="search_term" placeholder="Term">
            </div>
            <div class="w-100"></div>
            <button class="btn btn-outline-secondary mt-2 ms-2" id="clearExceptionFiltersBtn" type="button">
                <i class="bx bx-x"></i> Clear Filters
            </button>
        </x-ui.advanced-search>

        {{-- ===== DATA TABLE ===== --}}
        <x-ui.datatable :headers="['Student', 'Academic ID', 'Course', 'Prerequisite', 'Term', 'Status', 'Reason', 'Action']" :columns="[
            ['data' => 'student', 'name' => 'student'],
            ['data' => 'academic_id', 'name' => 'academic_id'],
            ['data' => 'course', 'name' => 'course'],
            ['data' => 'prerequisite', 'name' => 'prerequisite'],
            ['data' => 'term', 'name' => 'term'],
            ['data' => 'is_active', 'name' => 'is_active'],
            ['data' => 'reason', 'name' => 'reason'],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
        ]"
            :ajax-url="route('prerequisite-exceptions.datatable')" table-id="prerequisite-exceptions-table"
            :filter-fields="['search_student_name','search_academic_id','search_course','search_prerequisite','search_term']" />

        {{-- ===== MODALS SECTION ===== --}}
        <x-ui.modal id="exceptionModal" title="Add/Edit Prerequisite Exception" size="lg" :scrollable="false"
            class="exception-modal">
            <x-slot name="slot">
                <form id="exceptionForm">
                    <input type="hidden" id="exception_id" name="exception_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="student_id" class="form-label">Student</label>
                            <select class="form-select select2" id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="term_id" class="form-label">Term</label>
                            <select class="form-select select2" id="term_id" name="term_id" required>
                                <option value="">Select Term</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="course_id" class="form-label">Course (that has prerequisites)</label>
                            <select class="form-select select2" id="course_id" name="course_id" required>
                                <option value="">Select Course</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="prerequisite_id" class="form-label">Prerequisite to Skip</label>
                            <select class="form-select select2" id="prerequisite_id" name="prerequisite_id" required
                                disabled>
                                <option value="">Select Course First</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="is_active" class="form-label">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                    checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3"
                                placeholder="Reason for granting prerequisite exception..."></textarea>
                        </div>
                    </div>
                </form>
            </x-slot>
            <x-slot name="footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Close
                </button>
                <button type="submit" class="btn btn-primary" id="saveExceptionBtn" form="exceptionForm">Save</button>
            </x-slot>
        </x-ui.modal>

        {{-- Import Modal --}}
        <x-ui.modal id="importExceptionsModal" title="Import Prerequisite Exceptions" size="md" :scrollable="false"
            class="import-exceptions-modal">
            <x-slot name="slot">
                <form id="importExceptionsForm">
                    <div class="mb-3">
                        <label for="exceptions_file" class="form-label">Upload Excel File (.xlsx or .xls)</label>
                        <input type="file" class="form-control" id="exceptions_file" name="exceptions_file"
                            accept=".xlsx,.xls" required>
                    </div>
                </form>
                <div class="small text-muted">
                    Ensure columns: academic_id, course_code, prerequisite_code, term_code, reason, is_active
                </div>
            </x-slot>
            <x-slot name="footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" id="importExceptionsSubmitBtn"
                    form="importExceptionsForm">Import</button>
            </x-slot>
        </x-ui.modal>
    </div>
@endsection

@push('scripts')
    <script>
        /**
         * Prerequisite Exceptions Management System JavaScript
         * Handles CRUD operations for prerequisite exceptions
         */

        // ===========================
        // UTILITY FUNCTIONS
        // ===========================
        const Utils = {
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
            showError(message) {
                Swal.fire('Error', message, 'error');
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
            hidePageLoader() {
                const loader = document.getElementById('pageLoader');
                if (loader) {
                    loader.classList.add('fade-out');
                    document.documentElement.style.overflow = '';
                    document.body.style.overflow = '';
                }
            }
        };

        // ===========================
        // IMPORT FUNCTIONALITY
        // ===========================
        const ImportManager = {
            handleImportExceptions() {
                $('#importExceptionsForm').on('submit', function (e) {
                    e.preventDefault();
                    const formData = new FormData(e.target);
                    const $submitBtn = $('#importExceptionsSubmitBtn');
                    $submitBtn.prop('disabled', true).text('Importing...');

                    $.ajax({
                        url: '{{ route("prerequisite-exceptions.import") }}',
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    })
                        .done((response) => {
                            $('#importExceptionsModal').modal('hide');
                            Utils.showSuccess(response.message);
                            $('#prerequisite-exceptions-table').DataTable().ajax.reload(null, false);
                            if (response.data?.errors?.length > 0) {
                                ImportManager.showImportErrors(response.data.errors, response.data.imported_count);
                            }
                            StatsManager.loadExceptionStats();
                        })
                        .fail((xhr) => {
                            const response = xhr.responseJSON;
                            if (response?.errors && Object.keys(response.errors).length > 0) {
                                const errorMessages = [];
                                Object.keys(response.errors).forEach(field => {
                                    if (Array.isArray(response.errors[field])) {
                                        errorMessages.push(...response.errors[field]);
                                    } else {
                                        errorMessages.push(response.errors[field]);
                                    }
                                });
                                Utils.showError(errorMessages.join('<br>'));
                            } else {
                                const message = response?.message || 'Import failed. Please check your file.';
                                Utils.showError(message);
                            }
                        })
                        .always(() => {
                            $submitBtn.prop('disabled', false).text('Import');
                            $('#importExceptionsForm')[0].reset();
                        });
                });
            },

            showImportErrors(errors, importedCount) {
                let html = `<div class="text-start">Imported ${importedCount} rows with ${errors.length} error rows.<br/><br/>`;
                html += '<ul style="max-height:260px;overflow:auto;">';
                errors.forEach((err) => {
                    const details = Array.isArray(err.errors) ? err.errors : (err.errors?.general || []);
                    html += `<li><strong>Row ${err.row}:</strong> ${details.join(' | ')}</li>`;
                });
                html += '</ul></div>';

                Swal.fire({
                    title: 'Import Completed',
                    html,
                    icon: 'info'
                });
            }
        };

        // ===========================
        // STATS MANAGER
        // ===========================
        const StatsManager = {
            loadExceptionStats() {
                Utils.toggleLoadingState('exceptions', true);
                Utils.toggleLoadingState('active-exceptions', true);
                Utils.toggleLoadingState('inactive-exceptions', true);
                $.ajax({
                    url: '{{ route("prerequisite-exceptions.stats") }}',
                    method: 'GET',
                    success: function (response) {
                        const data = response.data;
                        $('#exceptions-value').text(data.total.total ?? '--');
                        $('#exceptions-last-updated').text(data.total.lastUpdateTime ?? '--');
                        $('#active-exceptions-value').text(data.active.total ?? '--');
                        $('#active-exceptions-last-updated').text(data.active.lastUpdateTime ?? '--');
                        $('#inactive-exceptions-value').text(data.inactive.total ?? '--');
                        $('#inactive-exceptions-last-updated').text(data.inactive.lastUpdateTime ?? '--');
                        Utils.toggleLoadingState('exceptions', false);
                        Utils.toggleLoadingState('active-exceptions', false);
                        Utils.toggleLoadingState('inactive-exceptions', false);
                    },
                    error: function () {
                        $('#exceptions-value, #active-exceptions-value, #inactive-exceptions-value').text('N/A');
                        $('#exceptions-last-updated, #active-exceptions-last-updated, #inactive-exceptions-last-updated').text('N/A');
                        Utils.toggleLoadingState('exceptions', false);
                        Utils.toggleLoadingState('active-exceptions', false);
                        Utils.toggleLoadingState('inactive-exceptions', false);
                        Utils.showError('Failed to load exception statistics');
                    }
                });
            }
        };

        // ===========================
        // DROPDOWN MANAGER
        // ===========================
        const DropdownManager = {
            loadStudents(selectedId = null) {
                return $.ajax({
                    url: '{{ route("prerequisite-exceptions.students") }}',
                    method: 'GET',
                    success: function (response) {
                        const data = response.data;
                        const $studentSelect = $('#student_id');
                        $studentSelect.empty().append('<option value="">Select Student</option>');
                        data.forEach(function (student) {
                            $studentSelect.append(
                                $('<option>', { value: student.id, text: student.text })
                            );
                        });
                        if (selectedId) {
                            $studentSelect.val(selectedId).trigger('change');
                        }
                        if (!$studentSelect.hasClass('select2-hidden-accessible')) {
                            $studentSelect.select2({
                                theme: 'bootstrap-5',
                                placeholder: 'Select Student',
                                allowClear: true,
                                width: '100%',
                                dropdownParent: $('#exceptionModal')
                            });
                        }
                    },
                    error: function () {
                        Utils.showError('Failed to load students');
                    }
                });
            },
            loadTerms(selectedId = null) {
                return $.ajax({
                    url: '{{ route("terms.all.with_inactive") }}',
                    method: 'GET',
                    success: function (response) {
                        const data = response.data;
                        const $termSelect = $('#term_id');
                        $termSelect.empty().append('<option value="">Select Term</option>');
                        data.forEach(function (term) {
                            $termSelect.append(
                                $('<option>', { value: term.id, text: term.name })
                            );
                        });
                        if (selectedId) {
                            $termSelect.val(selectedId).trigger('change');
                        }
                        if (!$termSelect.hasClass('select2-hidden-accessible')) {
                            $termSelect.select2({
                                theme: 'bootstrap-5',
                                placeholder: 'Select Term',
                                allowClear: true,
                                width: '100%',
                                dropdownParent: $('#exceptionModal')
                            });
                        }
                    },
                    error: function () {
                        Utils.showError('Failed to load terms');
                    }
                });
            },
            loadCourses(selectedId = null) {
                return $.ajax({
                    url: '{{ route("prerequisite-exceptions.courses") }}',
                    method: 'GET',
                    success: function (response) {
                        const data = response.data;
                        const $courseSelect = $('#course_id');
                        $courseSelect.empty().append('<option value="">Select Course</option>');
                        data.forEach(function (course) {
                            $courseSelect.append(
                                $('<option>', { value: course.id, text: course.text })
                            );
                        });
                        if (selectedId) {
                            $courseSelect.val(selectedId).trigger('change');
                        }
                        if (!$courseSelect.hasClass('select2-hidden-accessible')) {
                            $courseSelect.select2({
                                theme: 'bootstrap-5',
                                placeholder: 'Select Course',
                                allowClear: true,
                                width: '100%',
                                dropdownParent: $('#exceptionModal')
                            });
                        }
                    },
                    error: function () {
                        Utils.showError('Failed to load courses');
                    }
                });
            },
            loadPrerequisites(courseId, selectedId = null) {
                const $prerequisiteSelect = $('#prerequisite_id');
                if (!courseId) {
                    $prerequisiteSelect.empty().append('<option value="">Select Course First</option>');
                    $prerequisiteSelect.prop('disabled', true);
                    return;
                }
                return $.ajax({
                    url: `{{ route("prerequisite-exceptions.index") }}/courses/${courseId}/prerequisites`,
                    method: 'GET',
                    success: function (response) {
                        const data = response.data;
                        $prerequisiteSelect.empty().append('<option value="">Select Prerequisite</option>');
                        data.forEach(function (prereq) {
                            $prerequisiteSelect.append(
                                $('<option>', { value: prereq.id, text: prereq.text })
                            );
                        });
                        $prerequisiteSelect.prop('disabled', false);
                        if (selectedId) {
                            $prerequisiteSelect.val(selectedId).trigger('change');
                        }
                        if (!$prerequisiteSelect.hasClass('select2-hidden-accessible')) {
                            $prerequisiteSelect.select2({
                                theme: 'bootstrap-5',
                                placeholder: 'Select Prerequisite',
                                allowClear: true,
                                width: '100%',
                                dropdownParent: $('#exceptionModal')
                            });
                        }
                    },
                    error: function () {
                        Utils.showError('Failed to load prerequisites');
                    }
                });
            }
        };

        // ===========================
        // EXCEPTION MANAGER
        // ===========================
        const ExceptionManager = {
            handleAddExceptionBtn() {
                $('#addExceptionBtn').on('click', function () {
                    $('#exceptionForm')[0].reset();
                    $('#exception_id').val('');
                    $('#exceptionModal .modal-title').text('Add Prerequisite Exception');
                    $('#saveExceptionBtn').text('Save');
                    $('#is_active').prop('checked', true);
                    // Destroy existing select2 instances
                    if ($('#student_id').hasClass('select2-hidden-accessible')) {
                        $('#student_id').select2('destroy');
                    }
                    if ($('#term_id').hasClass('select2-hidden-accessible')) {
                        $('#term_id').select2('destroy');
                    }
                    if ($('#course_id').hasClass('select2-hidden-accessible')) {
                        $('#course_id').select2('destroy');
                    }
                    if ($('#prerequisite_id').hasClass('select2-hidden-accessible')) {
                        $('#prerequisite_id').select2('destroy');
                    }
                    // Reset prerequisite dropdown
                    $('#prerequisite_id').empty().append('<option value="">Select Course First</option>').prop('disabled', true);
                    // Load dropdowns
                    DropdownManager.loadStudents();
                    DropdownManager.loadTerms();
                    DropdownManager.loadCourses();
                    $('#exceptionModal').modal('show');
                });
            },
            handleCourseChange() {
                $(document).on('change', '#course_id', function () {
                    const courseId = $(this).val();
                    DropdownManager.loadPrerequisites(courseId);
                });
            },
            handleEditExceptionBtn() {
                $(document).on('click', '.editExceptionBtn', function () {
                    const exceptionId = $(this).data('id');
                    $.ajax({
                        url: `{{ route('prerequisite-exceptions.index') }}/${exceptionId}`,
                        method: 'GET',
                        success: function (response) {
                            if (response.success) {
                                const exception = response.data;
                                $('#exception_id').val(exception.id);
                                $('#reason').val(exception.reason);
                                $('#is_active').prop('checked', exception.is_active);
                                // Destroy existing select2 instances
                                if ($('#student_id').hasClass('select2-hidden-accessible')) {
                                    $('#student_id').select2('destroy');
                                }
                                if ($('#term_id').hasClass('select2-hidden-accessible')) {
                                    $('#term_id').select2('destroy');
                                }
                                if ($('#course_id').hasClass('select2-hidden-accessible')) {
                                    $('#course_id').select2('destroy');
                                }
                                if ($('#prerequisite_id').hasClass('select2-hidden-accessible')) {
                                    $('#prerequisite_id').select2('destroy');
                                }
                                // Load dropdowns with selected values
                                DropdownManager.loadStudents(exception.student_id);
                                DropdownManager.loadTerms(exception.term_id);
                                DropdownManager.loadCourses(exception.course_id).then(() => {
                                    DropdownManager.loadPrerequisites(exception.course_id, exception.prerequisite_id);
                                });
                                $('#exceptionModal .modal-title').text('Edit Prerequisite Exception');
                                $('#saveExceptionBtn').text('Update');
                                $('#exceptionModal').modal('show');
                            } else {
                                Utils.showError(response.message || 'Failed to load exception details');
                            }
                        },
                        error: function () {
                            Utils.showError('Failed to load exception details');
                        }
                    });
                });
            },
            handleExceptionFormSubmit() {
                $('#exceptionForm').on('submit', function (e) {
                    e.preventDefault();
                    const exceptionId = $('#exception_id').val();
                    const isEdit = exceptionId !== '';
                    const url = isEdit
                        ? `{{ route('prerequisite-exceptions.index') }}/${exceptionId}`
                        : '{{ route("prerequisite-exceptions.store") }}';
                    const method = isEdit ? 'PUT' : 'POST';
                    $.ajax({
                        url: url,
                        method: method,
                        data: $(this).serialize(),
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {
                            if (response.success) {
                                $('#exceptionModal').modal('hide');
                                Utils.showSuccess(response.message);
                                $('#prerequisite-exceptions-table').DataTable().ajax.reload();
                                StatsManager.loadExceptionStats();
                            } else {
                                Utils.showError(response.message || 'Operation failed');
                            }
                        },
                        error: function (xhr) {
                            $('#exceptionModal').modal('hide');
                            const message = xhr.responseJSON?.message || 'An error occurred';
                            Utils.showError(message);
                        }
                    });
                });
            },
            handleDeactivateExceptionBtn() {
                $(document).on('click', '.deactivateExceptionBtn', function () {
                    const exceptionId = $(this).data('id');
                    Swal.fire({
                        title: 'Deactivate Exception?',
                        text: 'Are you sure you want to deactivate this exception?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, deactivate it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `{{ route('prerequisite-exceptions.index') }}/${exceptionId}/deactivate`,
                                method: 'PATCH',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function (response) {
                                    if (response.success) {
                                        Utils.showSuccess(response.message);
                                        $('#prerequisite-exceptions-table').DataTable().ajax.reload();
                                        StatsManager.loadExceptionStats();
                                    } else {
                                        Utils.showError(response.message || 'Failed to deactivate exception');
                                    }
                                },
                                error: function () {
                                    Utils.showError('Failed to deactivate exception');
                                }
                            });
                        }
                    });
                });
            },
            handleActivateExceptionBtn() {
                $(document).on('click', '.activateExceptionBtn', function () {
                    const exceptionId = $(this).data('id');
                    Swal.fire({
                        title: 'Activate Exception?',
                        text: 'Are you sure you want to activate this exception?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, activate it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `{{ route('prerequisite-exceptions.index') }}/${exceptionId}/activate`,
                                method: 'PATCH',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function (response) {
                                    if (response.success) {
                                        Utils.showSuccess(response.message);
                                        $('#prerequisite-exceptions-table').DataTable().ajax.reload();
                                        StatsManager.loadExceptionStats();
                                    } else {
                                        Utils.showError(response.message || 'Failed to activate exception');
                                    }
                                },
                                error: function (xhr) {
                                    const message = xhr.responseJSON?.message || 'Failed to activate exception';
                                    Utils.showError(message);
                                }
                            });
                        }
                    });
                });
            },
            handleDeleteExceptionBtn() {
                $(document).on('click', '.deleteExceptionBtn', function () {
                    const exceptionId = $(this).data('id');
                    Swal.fire({
                        title: 'Delete Exception?',
                        text: 'Are you sure you want to delete this exception? This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: `{{ route('prerequisite-exceptions.index') }}/${exceptionId}`,
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function (response) {
                                    if (response.success) {
                                        Utils.showSuccess(response.message);
                                        $('#prerequisite-exceptions-table').DataTable().ajax.reload();
                                        StatsManager.loadExceptionStats();
                                    } else {
                                        Utils.showError(response.message || 'Failed to delete exception');
                                    }
                                },
                                error: function () {
                                    Utils.showError('Failed to delete exception');
                                }
                            });
                        }
                    });
                });
            }
        };

        // ===========================
        // SEARCH MANAGER
        // ===========================
        const SearchManager = {
            initializeAdvancedSearch() {
                $('#search_student_name, #search_academic_id, #search_course, #search_prerequisite, #search_term').on('keyup change', function () {
                    $('#prerequisite-exceptions-table').DataTable().ajax.reload();
                });
                $('#clearExceptionFiltersBtn').on('click', function () {
                    $('#search_student_name, #search_academic_id, #search_course, #search_prerequisite, #search_term').val('');
                    $('#prerequisite-exceptions-table').DataTable().ajax.reload();
                });
            }
        };

        // ===========================
        // MAIN APPLICATION
        // ===========================
        const PrerequisiteExceptionApp = {
            init() {
                StatsManager.loadExceptionStats();
                ExceptionManager.handleAddExceptionBtn();
                ExceptionManager.handleCourseChange();
                ExceptionManager.handleEditExceptionBtn();
                ExceptionManager.handleExceptionFormSubmit();
                ExceptionManager.handleDeactivateExceptionBtn();
                ExceptionManager.handleActivateExceptionBtn();
                ExceptionManager.handleDeleteExceptionBtn();
                SearchManager.initializeAdvancedSearch();
                $('#exceptionModal').on('hidden.bs.modal', function () {
                    if ($('#student_id').hasClass('select2-hidden-accessible')) {
                        $('#student_id').select2('destroy');
                    }
                    if ($('#term_id').hasClass('select2-hidden-accessible')) {
                        $('#term_id').select2('destroy');
                    }
                    if ($('#course_id').hasClass('select2-hidden-accessible')) {
                        $('#course_id').select2('destroy');
                    }
                    if ($('#prerequisite_id').hasClass('select2-hidden-accessible')) {
                        $('#prerequisite_id').select2('destroy');
                    }
                });
                ImportManager.handleImportExceptions();
                Utils.hidePageLoader();
            }
        };

        $(document).ready(function () {
            PrerequisiteExceptionApp.init();
        });
    </script>
@endpush