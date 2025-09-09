@extends('layouts.home')

@section('title', 'Schedules Committee Home | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Welcome Section -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <div class="row align-items-center">
            <div class="col-md-8">
              <h2 class="card-title mb-3 fw-bold text-primary">Welcome to Schedules Committee Dashboard</h2>
              <p class="card-text text-muted mb-3">
                Hello, <strong>{{ auth()->user()->name }}</strong>! Welcome to the Schedules Committee portal. 
                Here you'll be able to manage and oversee academic schedules, coordinate course offerings, 
                and ensure optimal resource allocation for the academic institution.
              </p>
              <p class="card-text text-muted">
                <i class="bx bx-info-circle me-1"></i>
                Statistics and analytics features are coming soon to provide you with comprehensive insights 
                into scheduling patterns and academic resource utilization.
              </p>
            </div>
            <div class="col-md-4 text-center">
              <div class="illustration-container">
                <i class="bx bx-calendar-check display-1 text-primary opacity-75"></i>
                <div class="mt-3">
                  <div class="d-flex justify-content-center gap-2">
                    <i class="bx bx-time text-info" style="font-size: 2rem;"></i>
                    <i class="bx bx-book-open text-warning" style="font-size: 2rem;"></i>
                    <i class="bx bx-group text-success" style="font-size: 2rem;"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Coming Soon Features -->
  <div class="row g-4">
    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body text-center p-4">
          <div class="mb-3">
            <i class="bx bx-bar-chart-alt-2 display-4 text-primary"></i>
          </div>
          <h5 class="card-title fw-semibold">Schedule Analytics</h5>
          <p class="card-text text-muted small">
            Comprehensive analytics and reporting for course schedules, room utilization, and faculty workload distribution.
          </p>
          <span class="badge bg-warning text-dark">Coming Soon</span>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body text-center p-4">
          <div class="mb-3">
            <i class="bx bx-calendar-event display-4 text-info"></i>
          </div>
          <h5 class="card-title fw-semibold">Schedule Management</h5>
          <p class="card-text text-muted small">
            Tools for creating, modifying, and optimizing academic schedules with conflict detection and resource planning.
          </p>
          <span class="badge bg-warning text-dark">Coming Soon</span>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body text-center p-4">
          <div class="mb-3">
            <i class="bx bx-trending-up display-4 text-success"></i>
          </div>
          <h5 class="card-title fw-semibold">Performance Metrics</h5>
          <p class="card-text text-muted small">
            Track and analyze scheduling efficiency, resource utilization rates, and student satisfaction metrics.
          </p>
          <span class="badge bg-warning text-dark">Coming Soon</span>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/utils.js') }}?v={{ config('app.version') }}"></script>
<script>
$(document).ready(function () {
    Utils.hidePageLoader();
});
</script>
@endpush 