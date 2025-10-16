@extends('layouts.home')

@section('title', 'System Settings | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- ===== PAGE HEADER ===== --}}
    <x-ui.page-header 
        title="System Settings"
        description="Configure and manage system-wide settings and preferences."
        icon="bx bx-cog"
    >
    </x-ui.page-header>

    {{-- ===== SETTINGS TABS CARD ===== --}}
    <div class="card">
        <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <button 
                        class="nav-link active" 
                        id="enrollment-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#enrollment" 
                        type="button" 
                        role="tab" 
                        aria-controls="enrollment" 
                        aria-selected="true"
                    >
                        <i class="bx bx-user-plus me-1"></i> Enrollment Management
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                {{-- ===== ENROLLMENT MANAGEMENT TAB (active) ===== --}}
                <div class="tab-pane fade show active" id="enrollment" role="tabpanel" aria-labelledby="enrollment-tab">
                    <form id="enrollmentSettingsForm" method="POST">
                        @csrf
                        <div class="row g-4">
                            {{-- Enable/Disable Enrollment --}}
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-1">
                                                    <i class="bx bx-user-plus text-primary me-2"></i>
                                                    Enable Enrollment
                                                </h5>
                                                <p class="text-muted mb-0">
                                                    Allow students to enroll in courses. When disabled, no new enrollments can be created.
                                                </p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input 
                                                    class="form-check-input" 
                                                    type="checkbox" 
                                                    id="enable_enrollment" 
                                                    name="enable_enrollment"
                                                    style="width: 3rem; height: 1.5rem; cursor: pointer;"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Enable/Disable Create Enrollment --}}
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-1">
                                                    <i class="bx bx-pencil text-success me-2"></i>
                                                    Allow Create Enrollment
                                                </h5>
                                                <p class="text-muted mb-0">
                                                    Allow creation of student enrollments. This is disabled when Enrollment is disabled.
                                                </p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input 
                                                    class="form-check-input" 
                                                    type="checkbox" 
                                                    id="allow_create_enrollment" 
                                                    name="allow_create_enrollment"
                                                    style="width: 3rem; height: 1.5rem; cursor: pointer;"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Enable/Disable Delete Enrollment --}}
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="mb-1">
                                                    <i class="bx bx-trash text-danger me-2"></i>
                                                    Allow Delete Enrollment
                                                </h5>
                                                <p class="text-muted mb-0">
                                                    Allow deletion of student enrollments. When disabled, enrollments cannot be removed from the system.
                                                </p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input 
                                                    class="form-check-input" 
                                                    type="checkbox" 
                                                    id="allow_delete_enrollment" 
                                                    name="allow_delete_enrollment"
                                                    style="width: 3rem; height: 1.5rem; cursor: pointer;"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
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
                return function(...args) {
                    clearTimeout(t);
                    t = setTimeout(() => fn.apply(this, args), wait);
                };
            };

            const listener = debounce(function() {
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
            $(SELECTORS.enableEnrollment).on('change', function() {
                const on = $(this).is(':checked');
                $(SELECTORS.allowCreateEnrollment).prop('disabled', !on);
                $(SELECTORS.allowDeleteEnrollment).prop('disabled', !on);
                listener();
            });

            $(SELECTORS.allowCreateEnrollment + ',' + SELECTORS.allowDeleteEnrollment).on('change', listener);
        },
        // Watch master toggle and enable/disable subordinate options
        watchMasterToggle() {
            $(SELECTORS.enableEnrollment).on('change', function() {
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