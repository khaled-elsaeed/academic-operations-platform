@extends('layouts.auth')

@section('title', 'Forgot Password | AcadOps')

@section('content')
@if (
    session('status')
)
    <div class="alert alert-success">
        {{ session('status') }}
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
<!-- Logo -->
<div class="app-brand justify-content-center">
    <a href="/" class="app-brand-link gap-2">
        <span class="app-brand-logo demo">
            <!-- SVG logo here (copy from layout if needed) -->
        </span>
        <span class="app-brand-text demo text-body fw-bolder text-primary">AcadOps</span>
    </a>
</div>
<!-- /Logo -->
<h4 class="mb-2">Forgot your password? 🔒</h4>
<p class="mb-4">Enter your email and we'll send you instructions to reset your password</p>
<form id="formForgotPassword" class="mb-3" action="{{ route('password.email') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required />
    </div>
    <div class="mb-3">
        <button class="btn btn-primary d-grid w-100" type="submit">Send Reset Link</button>
    </div>
</form>
<p class="text-center">
    <a href="{{ route('login') }}">
        <span>Back to sign in</span>
    </a>
</p>
@endsection 