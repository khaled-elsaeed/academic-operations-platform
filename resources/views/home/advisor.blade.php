@extends('layouts.home')

@section('title', 'Advisor Home | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-3">
    <div class="col-12 col-sm-6 col-lg-4 mb-4">
      <x-ui.card.stat 
        color="primary"
        icon="bx bx-user"
        label="My Advisees"
        id="advisees"
      />
    </div>
    <div class="col-12 col-sm-6 col-lg-4 mb-4">
      <x-ui.card.stat 
        color="info"
        icon="bx bx-library"
        label="Courses Enrolled"
        id="courses"
      />
    </div>
    <div class="col-12 col-sm-6 col-lg-4 mb-4">
      <x-ui.card.stat 
        color="success"
        icon="bx bx-bar-chart"
        label="Avg. CGPA"
        id="avg-cgpa"
      />
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
function loadAdvisorDashboardStats() {
    $.ajax({
        url: '{{ route('advisor.home.stats') }}',
        method: 'GET',
        success: function(response) {
            if (response.data) {
                populateStatCards(response.data);
            }
        },
        error: function() {
            console.error('Failed to load advisor dashboard statistics');
        }
    });
}

function populateStatCards(data) {
    updateStatCard('advisees', data.advisees.total, data.advisees.lastUpdatedTime);
    updateStatCard('courses', data.courses.total, data.courses.lastUpdatedTime);
    updateStatCard('avg-cgpa', data.advisees.avgCgpa, data.advisees.lastUpdatedTime);
}

function updateStatCard(id, total, lastUpdatedTime) {
    $(`#stat-${id}-value`).text(total).removeClass('d-none');
    $(`#stat-${id}-loader`).addClass('d-none');
    $(`#stat-${id}-last-updated`).text(lastUpdatedTime).removeClass('d-none');
    $(`#stat-${id}-last-updated-loader`).addClass('d-none');
}

$(document).ready(function () {
    loadAdvisorDashboardStats();
});
</script>
@endpush 