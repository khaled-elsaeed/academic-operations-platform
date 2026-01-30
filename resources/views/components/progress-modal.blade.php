<div class="modal fade"
     id="{{ $modalId }}"
     data-bs-backdrop="static"
     data-bs-keyboard="false"
     tabindex="-1"
     aria-labelledby="{{ $modalId }}Label"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">
                    <i class="bx bx-upload me-2"></i>{{ $modalTitle }}
                </h5>
            </div>

            <div class="modal-body">
                <p class="mb-3 text-muted">
                    {{ __('Please wait while we prepare your task. This may take several minutes...') }}
                </p>

                <div class="progress mb-3" style="height: 30px;">
                    <div id="{{ $modalId }}ProgressBar"
                         class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                         role="progressbar"
                         style="width: 0%"
                         aria-valuenow="0"
                         aria-valuemin="0"
                         aria-valuemax="100">
                        <span id="{{ $modalId }}ProgressText" class="fw-semibold">0%</span>
                    </div>
                </div>

                <div id="{{ $modalId }}ProgressDetails" class="text-center">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">{{ __('Status:') }}</span>
                        <span id="{{ $modalId }}Status" class="badge bg-label-info">
                            {{ __('Initializing...') }}
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-start">
                        <span class="text-muted small">{{ __('Message:') }}</span>
                        <span id="{{ $modalId }}StatusMessage"
                              class="small fw-semibold text-start ms-2">
                            {{ __('Preparing...') }}
                        </span>
                    </div>
                </div>

                <div id="{{ $modalId }}CompletedInfo"
                     class="alert alert-success mt-3 d-none">
                    <h6 id="{{ $modalId }}CompletedMessage"
                        class="alert-heading mb-2">
                        {{ __('Task completed successfully!') }}
                    </h6>

                    <table class="table table-sm table-borderless mb-0">
                        <tbody id="{{ $modalId }}CompletionTable"></tbody>
                    </table>
                </div>

                <div id="{{ $modalId }}ErrorInfo"
                     class="alert alert-danger mt-3 d-none">
                    <h6 class="alert-heading mb-2">{{ __('Task Failed') }}</h6>
                    <p id="{{ $modalId }}ErrorMessage" class="mb-0"></p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-secondary"
                        id="{{ $modalId }}CancelBtn">
                    <i class="bx bx-x me-1"></i>{{ __('Cancel') }}
                </button>

                <button type="button"
                        class="btn btn-primary d-none"
                        id="{{ $modalId }}DownloadBtn">
                    <i class="bx bx-download me-1"></i>{{ __('Download Now') }}
                </button>

                <button type="button"
                        class="btn btn-secondary d-none"
                        id="{{ $modalId }}CloseBtn"
                        data-bs-dismiss="modal">
                    {{ __('Close') }}
                </button>
            </div>

        </div>
    </div>
</div>
