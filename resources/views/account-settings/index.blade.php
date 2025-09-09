@extends('layouts.home')

@section('title', 'Account Settings')

@section('page-content')
<div class="content-wrapper">
    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row fv-plugins-icon-container">
            <div class="col-md-12">
                <div class="nav-align-top mb-4">
                    <ul class="nav nav-tabs" id="accountTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="account-tab" data-bs-toggle="tab" data-bs-target="#account-content" type="button" role="tab" aria-controls="account-content" aria-selected="true">
                                <i class="icon-base bx bx-user icon-sm me-1_5"></i> Account
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-content" type="button" role="tab" aria-controls="security-content" aria-selected="false">
                                <i class="icon-base bx bx-lock-alt icon-sm me-1_5"></i> Security
                            </button>
                        </li>
                    </ul>
                </div>
                
                <div class="tab-content" id="accountTabsContent">
                    <!-- Account Tab Content -->
                    <div class="tab-pane fade show active" id="account-content" role="tabpanel" aria-labelledby="account-tab" tabindex="0">
                        <div class="card mb-6">
                            <!-- Account -->
                            <div class="card-body">
                                                            <div class="d-flex align-items-start align-items-sm-center gap-4 pb-4 border-bottom mb-4">
                                <img src="{{ asset('img/avatars/default.png') }}" alt="user-avatar" class="d-block w-px-100 h-px-100 rounded me-4" id="uploadedAvatar">
                                <div class="button-wrapper">
                                    <label for="upload" class="btn btn-primary me-3 mb-3" tabindex="0">
                                        <span class="d-none d-sm-block">Upload new photo</span>
                                        <i class="icon-base bx bx-upload d-block d-sm-none"></i>
                                        <input type="file" id="upload" class="account-file-input" hidden="" accept="image/png, image/jpeg">
                                    </label>
                                    <button type="button" class="btn btn-label-secondary account-image-reset mb-3">
                                        <i class="icon-base bx bx-reset d-block d-sm-none"></i>
                                        <span class="d-none d-sm-block">Reset</span>
                                    </button>
                                    <div class="text-muted small">Allowed JPG, GIF or PNG. Max size of 800K</div>
                                </div>
                            </div>
                            </div>
                            
                            <div class="card-body pt-4">
                                <form id="formAccountSettings" method="POST" action="{{ route('account-settings.update') }}" class="fv-plugins-bootstrap5 fv-plugins-framework" novalidate="novalidate">
                                    @csrf
                                    @method('PUT')
                                    <div class="row g-4">
                                        <div class="col-md-6 form-control-validation fv-plugins-icon-container mb-3">
                                            <label for="firstName" class="form-label fw-semibold mb-2">First Name</label>
                                            <input class="form-control" type="text" id="firstName" name="first_name" value="{{ $user->first_name }}" autofocus="">
                                            <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-6 form-control-validation fv-plugins-icon-container mb-3">
                                            <label for="lastName" class="form-label fw-semibold mb-2">Last Name</label>
                                            <input class="form-control" type="text" name="last_name" id="lastName" value="{{ $user->last_name }}">
                                            <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback"></div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label fw-semibold mb-2">E-mail</label>
                                            <input class="form-control" type="text" id="email" name="email" value="{{ $user->email }}" placeholder="john.doe@example.com">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="gender" class="form-label fw-semibold mb-2">Gender</label>
                                            <select id="gender" name="gender" class="form-select">
                                                <option value="male" {{ $user->gender === 'male' ? 'selected' : '' }}>Male</option>
                                                <option value="female" {{ $user->gender === 'female' ? 'selected' : '' }}>Female</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-3 border-top">
                                        <button type="submit" class="btn btn-primary me-3">Save changes</button>
                                        <button type="reset" class="btn btn-label-secondary">Cancel</button>
                                    </div>
                                </form>
                            </div>
                            <!-- /Account -->
                        </div>
                    </div>

                    <!-- Security Tab Content -->
                    <div class="tab-pane fade" id="security-content" role="tabpanel" aria-labelledby="security-tab" tabindex="0">
                        <div class="card mb-6">
                            <div class="card-header">
                                <h5 class="card-title">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form id="formSecuritySettings" method="POST" action="{{ route('account-settings.update-password') }}" class="fv-plugins-bootstrap5 fv-plugins-framework" novalidate="novalidate">
                                    @csrf
                                    @method('PUT')
                                    <div class="row g-4">
                                        <div class="col-md-6 mb-3">
                                            <label for="security_current_password" class="form-label fw-semibold mb-2">Current Password</label>
                                            <input type="password" id="security_current_password" name="current_password" class="form-control" placeholder="Enter current password" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="security_new_password" class="form-label fw-semibold mb-2">New Password</label>
                                            <input type="password" id="security_new_password" name="new_password" class="form-control" placeholder="Enter new password" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="security_new_password_confirmation" class="form-label fw-semibold mb-2">Confirm New Password</label>
                                            <input type="password" id="security_new_password_confirmation" name="new_password_confirmation" class="form-control" placeholder="Confirm new password" required>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-3 border-top">
                                        <button type="submit" class="btn btn-primary me-3">Update Password</button>
                                        <button type="reset" class="btn btn-label-secondary">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- / Content -->
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/utils.js') }}?v={{ config('app.version') }}"></script>
<script>
$(document).ready(function() {

    // Handle account settings form submission
    $('#formAccountSettings').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: response.message,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else {
                    // Handle business logic errors (like incorrect password)
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (response && response.errors && Object.keys(response.errors).length > 0) {
                    let errorMessage = '';
                    Object.values(response.errors).forEach(function(errors) {
                        errorMessage += errors.join('\n') + '\n';
                    });
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: errorMessage,
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response?.message || 'Failed to update account settings',
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    });

    // Handle security settings form submission
    $('#formSecuritySettings').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: response.message,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else {
                    // Handle business logic errors (like incorrect password)
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        confirmButtonText: 'OK'
                    });
                }
                // Clear password fields after successful update
                $('#formSecuritySettings')[0].reset();
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (response && response.errors && Object.keys(response.errors).length > 0) {
                    let errorMessage = '';
                    Object.values(response.errors).forEach(function(errors) {
                        errorMessage += errors.join('\n') + '\n';
                    });
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: errorMessage,
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response?.message || 'Failed to update password',
                        confirmButtonText: 'OK'
                    });
                }
            }
        });
    });

    // Handle avatar upload (placeholder functionality)
    $('#upload').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#uploadedAvatar').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    // Handle avatar reset
    $('.account-image-reset').on('click', function() {
        $('#uploadedAvatar').attr('src', '{{ asset("img/avatars/default.png") }}');
        $('#upload').val('');
    });

    Utils.hidePageLoader();

});
</script>
@endpush 