@extends('layouts.home')

@section('title', 'System Settings | AcadOps')

@push('styles')
    <style>
        .settings-section {
            margin-bottom: 1.5rem;
        }

        .settings-section-title {
            color: #495057;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }

        .settings-item {
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 0.375rem;
            margin-bottom: 0.75rem;
            background-color: #fbfcfd;
            transition: all 0.2s ease;
        }

        .settings-item:hover {
            border-color: #dee2e6;
            background-color: #f8f9fa;
        }

        .form-check-input-lg {
            width: 3rem;
            height: 1.5rem;
            cursor: pointer;
        }

        .list-group-item-action.active {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .list-group-item-action:hover {
            background-color: #f8f9fa;
        }

        .tab-content {
            min-height: 500px;
        }

        .badge {
            font-size: 0.65em;
        }
    </style>
@endpush

@section('page-content')
    <div class="container-xxl flex-grow-1 container-p-y">

        {{-- ===== PAGE HEADER ===== --}}
        <x-ui.page-header title="System Settings" description="Configure and manage system-wide settings and preferences."
            icon="bx bx-cog">
        </x-ui.page-header>

        {{-- ===== SYSTEM SETTINGS LAYOUT ===== --}}
        <div class="row">
            {{-- Settings Navigation Sidebar --}}
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 text-dark">
                            <i class="bx bx-list-ul me-2"></i>Settings Categories
                        </h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="#enrollment-settings" class="list-group-item list-group-item-action active"
                            id="enrollment-nav" data-bs-toggle="pill" role="tab">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-user-plus text-primary me-3"></i>
                                <div>
                                    <div class="fw-semibold">Enrollment</div>
                                    <small class="text-muted">Student enrollment controls</small>
                                </div>
                            </div>
                        </a>
                        <a href="#system-settings" class="list-group-item list-group-item-action" id="system-nav"
                            data-bs-toggle="pill" role="tab">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-cog text-secondary me-3"></i>
                                <div>
                                    <div class="fw-semibold">System</div>
                                    <small class="text-muted">General system settings</small>
                                </div>
                            </div>
                        </a>
                        <a href="#security-settings" class="list-group-item list-group-item-action" id="security-nav"
                            data-bs-toggle="pill" role="tab">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-shield text-warning me-3"></i>
                                <div>
                                    <div class="fw-semibold">Security</div>
                                    <small class="text-muted">Access & permissions</small>
                                </div>
                            </div>
                        </a>
                        <a href="#maintenance-settings" class="list-group-item list-group-item-action" id="maintenance-nav"
                            data-bs-toggle="pill" role="tab">
                            <div class="d-flex align-items-center">
                                <i class="bx bx-wrench text-info me-3"></i>
                                <div>
                                    <div class="fw-semibold">Maintenance</div>
                                    <small class="text-muted">System maintenance</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Settings Content Area --}}
            <div class="col-lg-9 col-md-8">
                <div class="tab-content">
                    {{-- ===== ENROLLMENT SETTINGS ===== --}}
                    <div class="tab-pane fade show active" id="enrollment-settings" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h5 class="mb-1">
                                            <i class="bx bx-user-plus text-primary me-2"></i>
                                            Enrollment Management
                                        </h5>
                                        <p class="text-muted small mb-0">Configure student enrollment system settings</p>
                                    </div>
                                    <div class="badge bg-primary">Active</div>
                                </div>
                            </div>
                            <div class="card-body">
                                <form id="enrollmentSettingsForm" method="POST">
                                    @csrf

                                    {{-- Master Control Section --}}
                                    <div class="settings-section mb-4">
                                        <h6 class="settings-section-title">
                                            <i class="bx bx-toggle-left me-2"></i>Master Control
                                        </h6>
                                        <div class="settings-item">
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <label class="form-label fw-semibold mb-1">Enable Enrollment
                                                        System</label>
                                                    <p class="text-muted small mb-0">
                                                        Master switch to enable or disable the entire enrollment system
                                                    </p>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input form-check-input-lg" type="checkbox"
                                                            id="enable_enrollment" name="enable_enrollment">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    {{-- Detailed Permissions --}}
                                    <div class="settings-section">
                                        <h6 class="settings-section-title">
                                            <i class="bx bx-shield-check me-2"></i>Permissions
                                        </h6>

                                        <div class="settings-item">
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <label class="form-label fw-semibold mb-1">Allow Create
                                                        Enrollment</label>
                                                    <p class="text-muted small mb-0">
                                                        Enable creation of new student enrollments
                                                    </p>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="allow_create_enrollment" name="allow_create_enrollment">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="settings-item">
                                            <div class="row align-items-center">
                                                <div class="col">
                                                    <label class="form-label fw-semibold mb-1">Allow Delete
                                                        Enrollment</label>
                                                    <p class="text-muted small mb-0">
                                                        Enable deletion of existing student enrollments
                                                    </p>
                                                </div>
                                                <div class="col-auto">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="allow_delete_enrollment" name="allow_delete_enrollment">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- ===== SYSTEM SETTINGS ===== --}}
                    <div class="tab-pane fade" id="system-settings" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h5 class="mb-1">
                                            <i class="bx bx-cog text-secondary me-2"></i>
                                            System Configuration
                                        </h5>
                                        <p class="text-muted small mb-0">General system-wide settings and preferences</p>
                                    </div>
                                    <div class="badge bg-secondary">Coming Soon</div>
                                </div>
                            </div>
                            <div class="card-body text-center py-5">
                                <i class="bx bx-cog display-4 text-muted mb-3"></i>
                                <h6 class="text-muted">System Settings</h6>
                                <p class="text-muted">Additional system configuration options will be available here.</p>
                            </div>
                        </div>
                    </div>

                    {{-- ===== SECURITY SETTINGS ===== --}}
                    <div class="tab-pane fade" id="security-settings" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h5 class="mb-1">
                                            <i class="bx bx-shield text-warning me-2"></i>
                                            Security & Access
                                        </h5>
                                        <p class="text-muted small mb-0">Security policies and access control settings</p>
                                    </div>
                                    <div class="badge bg-warning">Coming Soon</div>
                                </div>
                            </div>
                            <div class="card-body text-center py-5">
                                <i class="bx bx-shield display-4 text-muted mb-3"></i>
                                <h6 class="text-muted">Security Settings</h6>
                                <p class="text-muted">Security and access control options will be available here.</p>
                            </div>
                        </div>
                    </div>

                    {{-- ===== MAINTENANCE SETTINGS ===== --}}
                    <div class="tab-pane fade" id="maintenance-settings" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <h5 class="mb-1">
                                            <i class="bx bx-wrench text-info me-2"></i>
                                            Maintenance & Utilities
                                        </h5>
                                        <p class="text-muted small mb-0">System maintenance and utility functions</p>
                                    </div>
                                    <div class="badge bg-info">Coming Soon</div>
                                </div>
                            </div>
                            <div class="card-body text-center py-5">
                                <i class="bx bx-wrench display-4 text-muted mb-3"></i>
                                <h6 class="text-muted">Maintenance Settings</h6>
                                <p class="text-muted">System maintenance and utility options will be available here.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // ===========================
        // CONSTANTS AND CONFIGURATION
        // ===========================
        const ROUTES = {
            settings: {
                enrollment: {
                    get: '{{ route('settings.enrollment.get') }}',
                    update: '{{ route('settings.enrollment.update') }}'
                }
            }
        };

        const SELECTORS = {
            enrollmentForm: '#enrollmentSettingsForm',
            enableEnrollment: '#enable_enrollment',
            allowDeleteEnrollment: '#allow_delete_enrollment'
        };
        SELECTORS.allowCreateEnrollment = '#allow_create_enrollment';


        // ===========================
        // API SERVICE LAYER
        // ===========================
        const ApiService = {
            request(options) {
                return $.ajax(options);
            },
            fetchEnrollmentSettings() {
                return this.request({ url: ROUTES.settings.enrollment.get, method: 'GET' });
            },
            updateEnrollmentSettings(formData) {
                return this.request({
                    url: ROUTES.settings.enrollment.update,
                    method: 'POST',
                    data: formData,
                });
            }
        };



        // ===========================
        // SETTINGS MANAGEMENT
        // ===========================
        const SettingsManager = {
            loadEnrollmentSettings() {
                ApiService.fetchEnrollmentSettings()
                    .done((response) => {
                        if (response.success) {
                            const settings = response.data || {};
                            $(SELECTORS.enableEnrollment).prop('checked', settings.enable_enrollment == 1 || settings.enable_enrollment === true);
                            $(SELECTORS.allowCreateEnrollment).prop('checked', settings.allow_create_enrollment == 1 || settings.allow_create_enrollment === true);
                            $(SELECTORS.allowDeleteEnrollment).prop('checked', settings.allow_delete_enrollment == 1 || settings.allow_delete_enrollment === true);

                            // Toggle subordinate controls based on master switch
                            const masterOn = $(SELECTORS.enableEnrollment).is(':checked');
                            $(SELECTORS.allowCreateEnrollment).prop('disabled', !masterOn);
                            $(SELECTORS.allowDeleteEnrollment).prop('disabled', !masterOn);
                        }
                    })
                    .fail(() => {
                        Utils.showError('Failed to load enrollment settings');
                    });
            },

            // Auto-save settings when any checkbox changes. Debounced to avoid spamming the server.
            enableAutoSave() {
                const save = (payload) => {
                    // Ensure CSRF header
                    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('input[name="_token"]').val() } });
                    return ApiService.updateEnrollmentSettings(payload)
                        .done((response) => {
                            if (response.success) {
                                Utils.showSuccess('Enrollment settings saved');
                            } else {
                                Utils.showError(response.message || 'Failed to save enrollment settings.');
                            }
                        })
                        .fail((xhr) => {
                            let message = 'Failed to save enrollment settings.';
                            if (xhr.responseJSON) {
                                if (xhr.responseJSON.errors) {
                                    message = Utils.formatValidationErrors(xhr.responseJSON.errors);
                                } else if (xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                            }
                            Utils.showError(message);
                        });
                };

                const debounce = (fn, wait = 300) => {
                    let t = null;
                    return function (...args) {
                        clearTimeout(t);
                        t = setTimeout(() => fn.apply(this, args), wait);
                    };
                };

                const listener = debounce(function () {
                    const payload = {
                        enable_enrollment: $(SELECTORS.enableEnrollment).is(':checked') ? 1 : 0,
                        allow_create_enrollment: $(SELECTORS.allowCreateEnrollment).is(':checked') ? 1 : 0,
                        allow_delete_enrollment: $(SELECTORS.allowDeleteEnrollment).is(':checked') ? 1 : 0,
                        _token: $('input[name="_token"]').val()
                    };

                    // If master disabled, enforce subordinate off locally as well
                    if (payload.enable_enrollment === 0) {
                        $(SELECTORS.allowCreateEnrollment).prop('checked', false).prop('disabled', true);
                        $(SELECTORS.allowDeleteEnrollment).prop('checked', false).prop('disabled', true);
                        payload.allow_create_enrollment = 0;
                        payload.allow_delete_enrollment = 0;
                    }

                    save(payload);
                }, 300);

                // Attach change listeners
                $(SELECTORS.enableEnrollment).on('change', function () {
                    const on = $(this).is(':checked');
                    $(SELECTORS.allowCreateEnrollment).prop('disabled', !on);
                    $(SELECTORS.allowDeleteEnrollment).prop('disabled', !on);
                    listener();
                });

                $(SELECTORS.allowCreateEnrollment + ',' + SELECTORS.allowDeleteEnrollment).on('change', listener);
            },
            // Watch master toggle and enable/disable subordinate options
            watchMasterToggle() {
                $(SELECTORS.enableEnrollment).on('change', function () {
                    const on = $(this).is(':checked');
                    $(SELECTORS.allowCreateEnrollment).prop('disabled', !on);
                    $(SELECTORS.allowDeleteEnrollment).prop('disabled', !on);
                });
            },
        };

        // ===========================
        // UTILITY FUNCTIONS
        // ===========================
        const Utils = {
            /**
             * Show success notification
             */
            showSuccess(message, timer = 3000) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: message,
                    showConfirmButton: false,
                    timer: timer,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'colored-toast'
                    }
                });
            },


            /**
             * Show error notification
             */
            showError(message, title = 'Error') {
                Swal.fire({
                    title: title,
                    html: message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
            },

            /**
             * Show warning notification
             */
            showWarning(message, title = 'Warning') {
                return Swal.fire({
                    title: title,
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'Cancel'
                });
            },

            /**
             * Toggle button loading state
             */
            toggleButtonLoading(selector, isLoading, originalText = null) {
                const $btn = $(selector);

                if (isLoading) {
                    const text = originalText || $btn.html();
                    $btn.prop('disabled', true)
                        .data('original-text', text)
                        .html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
                } else {
                    $btn.prop('disabled', false)
                        .html($btn.data('original-text') || originalText || 'Submit');
                }
            },

            /**
             * Validate time range
             */
            validateTimeRange(startTime, endTime) {
                if (!startTime || !endTime) return true;
                return startTime < endTime;
            },

            /**
             * Validate date range
             */
            validateDateRange(startDate, endDate) {
                if (!startDate || !endDate) return true;
                return new Date(startDate) <= new Date(endDate);
            },

            /**
             * Format validation errors for display
             */
            formatValidationErrors(errors) {
                let errorHtml = '<ul class="mb-0 text-start">';
                Object.entries(errors).forEach(([field, messages]) => {
                    messages.forEach(message => {
                        errorHtml += `<li>${message}</li>`;
                    });
                });
                errorHtml += '</ul>';
                return errorHtml;
            },
            /**
             * Hide the page loader overlay.
             */
            hidePageLoader() {
                const loader = document.getElementById('pageLoader');
                if (loader) {
                    loader.classList.add('fade-out');
                    // Restore scrollbars when loader is hidden
                    document.documentElement.style.overflow = '';
                    document.body.style.overflow = '';
                }
            }
        };

        // ===========================
        // MAIN APPLICATION
        // ===========================
        const SettingsApp = {
            init() {
                SettingsManager.loadEnrollmentSettings();
                SettingsManager.watchMasterToggle();
                SettingsManager.enableAutoSave();
                Utils.hidePageLoader();
            }
        };

        $(document).ready(() => {
            SettingsApp.init();
        });
    </script>
@endpush