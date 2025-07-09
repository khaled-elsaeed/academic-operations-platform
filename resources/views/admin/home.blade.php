@extends('layouts.home')

@section('title', 'Admin Home | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
<div class="row g-6 mb-6">
    <div class="col-12 col-sm-6 col-lg-3 mb-4">
      <x-ui.card.stat 
        color="primary"
        icon="bx bx-user"
        label="Total Students"
        id="students"
      />
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-4">
      <x-ui.card.stat 
        color="warning"
        icon="bx bx-chalkboard"
        label="Total Faculty"
        id="faculty"
      />
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-4">
      <x-ui.card.stat 
        color="danger"
        icon="bx bx-book"
        label="Total Programs"
        id="programs"
      />
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-4">
      <x-ui.card.stat 
        color="info"
        icon="bx bx-library"
        label="Total Courses"
        id="courses"
      />
    </div>
  </div>
   
</div>
@endsection

@push('scripts')
<script>
/**
 * Fetches dashboard statistics from the server and updates the stat cards.
 * This is the main entry point for loading and displaying dashboard stats.
 * 
 * @function loadDashboardStats
 * @returns {void}
 */
function loadDashboardStats() {
    $.ajax({
        url: '{{ route('admin.home.stats') }}',
        method: 'GET',
        success: function(response) {
            if (response.data) {
                populateStatCards(response.data);
            }
        }
    });
}

/**
 * Populates all stat cards with the provided data.
 * 
 * @function populateStatCards
 * @param {Object} data - The stats data object containing students, faculty, programs, and courses.
 * @returns {void}
 */
function populateStatCards(data) {
    updateStatCard('students', data.students.total, data.students.lastUpdatedTime);
    updateStatCard('faculty', data.faculty.total, data.faculty.lastUpdatedTime);
    updateStatCard('programs', data.programs.total, data.programs.lastUpdatedTime);
    updateStatCard('courses', data.courses.total, data.courses.lastUpdatedTime);
}

/**
 * Updates a single stat card with the given value and last updated time.
 * 
 * @function updateStatCard
 * @param {string} id - The stat card identifier (e.g., 'students').
 * @param {number|string} total - The value to display.
 * @param {string} lastUpdatedTime - The last updated time string.
 * @returns {void}
 */
function updateStatCard(id, total, lastUpdatedTime) {
    $(`#stat-${id}-value`).text(total).removeClass('d-none');
    $(`#stat-${id}-loader`).addClass('d-none');
    $(`#stat-${id}-last-updated`).text(lastUpdatedTime).removeClass('d-none');
    $(`#stat-${id}-last-updated-loader`).addClass('d-none');
}

$(document).ready(function () {
    loadDashboardStats();
});
</script>
@endpush
