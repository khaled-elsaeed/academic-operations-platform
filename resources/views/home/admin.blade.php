@extends('layouts.home')

@section('title', 'Admin Home | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
  <!-- Statistics Cards -->
  <div class="row g-4 mb-3">
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

  <!-- Charts Section -->
  <div class="row g-4">
    <!-- Level-wise Student Distribution Bar Chart -->
    <div class="col-lg-6 col-12 mb-4">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-header bg-transparent border-bottom-0 pb-0">
          <div>
            <h4 class="card-title mb-1 fw-semibold text-dark">Students by Academic Level</h4>
            <p class="card-subtitle mb-0 text-muted">Distribution across academic levels</p>
          </div>
        </div>
        <div class="card-body pt-0">
          <div class="chart-container" style="position: relative; height: 300px;">
            <canvas id="levelChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- CGPA Distribution Histogram -->
    <div class="col-lg-6 col-12 mb-4">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-header bg-transparent border-bottom-0 pb-0">
          <div>
            <h4 class="card-title mb-1 fw-semibold text-dark">CGPA Distribution</h4>
            <p class="card-subtitle mb-0 text-muted">Academic performance overview</p>
          </div>
        </div>
        <div class="card-body pt-0">
          <div class="chart-container" style="position: relative; height: 300px;">
            <canvas id="cgpaChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/utils.js') }}?v={{ config('app.version') }}"></script>
<script>
/**
 * Dashboard Charts and Statistics Management
 * Handles loading and displaying all dashboard data including charts and stat cards
 */

// Chart instances
let levelChart, cgpaChart;

// Chart.js global defaults for modern, professional appearance
Chart.defaults.font.family = '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';
Chart.defaults.font.size = 13;
Chart.defaults.color = '#495057';
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.padding = 20;
Chart.defaults.plugins.legend.labels.font = {
  family: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
  size: 12,
  weight: '500'
};

/**
 * Fetches dashboard statistics from the server and updates all dashboard elements.
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
                initializeCharts(response.data);
            }
        },
        error: function() {
            console.error('Failed to load dashboard statistics');
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

/**
 * Initializes all dashboard charts with the provided data.
 * 
 * @function initializeCharts
 * @param {Object} data - The complete dashboard data object.
 * @returns {void}
 */
function initializeCharts(data) {
    // Initialize Level Distribution Bar Chart
    if (data.levelDistribution) {
        initializeLevelChart(data.levelDistribution);
    }

    // Initialize CGPA Distribution Histogram
    if (data.cgpaDistribution) {
        initializeCgpaChart(data.cgpaDistribution);
    }
}

/**
 * Initializes the Level Distribution Bar Chart with modern styling.
 * 
 * @function initializeLevelChart
 * @param {Object} data - Level distribution data with labels and data.
 * @returns {void}
 */
function initializeLevelChart(data) {
    const ctx = document.getElementById('levelChart').getContext('2d');
    
    // Create gradient for bars
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(105, 108, 255, 0.9)');
    gradient.addColorStop(1, 'rgba(105, 108, 255, 0.6)');
    
    levelChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Students',
                data: data.data,
                backgroundColor: gradient,
                borderColor: 'rgba(105, 108, 255, 1)',
                borderWidth: 2,
                borderRadius: {
                    topLeft: 8,
                    topRight: 8,
                    bottomLeft: 0,
                    bottomRight: 0
                },
                borderSkipped: false,
                hoverBackgroundColor: 'rgba(105, 108, 255, 1)',
                hoverBorderColor: 'rgba(105, 108, 255, 1)',
                hoverBorderWidth: 3,
                barThickness: 'flex',
                maxBarThickness: 60
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.85)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 12,
                    titleFont: {
                        family: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                        size: 14,
                        weight: '600'
                    },
                    bodyFont: {
                        family: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                        size: 13
                    },
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' students';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                            size: 12,
                            weight: '500'
                        },
                        color: '#495057'
                    },
                    border: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.08)',
                        drawBorder: false,
                        lineWidth: 1
                    },
                    ticks: {
                        font: {
                            family: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                            size: 12,
                            weight: '500'
                        },
                        color: '#495057',
                        padding: 10,
                        callback: function(value) {
                            return value + ' students';
                        }
                    },
                    border: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            }
        }
    });
}

/**
 * Initializes the CGPA Distribution Histogram with modern styling.
 * 
 * @function initializeCgpaChart
 * @param {Object} data - CGPA distribution data with labels and data.
 * @returns {void}
 */
function initializeCgpaChart(data) {
    const ctx = document.getElementById('cgpaChart').getContext('2d');
    
    // Create gradient for bars
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(255, 107, 107, 0.9)');
    gradient.addColorStop(1, 'rgba(255, 107, 107, 0.6)');
    
    cgpaChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [{
                label: 'Students',
                data: data.data,
                backgroundColor: gradient,
                borderColor: 'rgba(255, 107, 107, 1)',
                borderWidth: 2,
                borderRadius: {
                    topLeft: 8,
                    topRight: 8,
                    bottomLeft: 0,
                    bottomRight: 0
                },
                borderSkipped: false,
                hoverBackgroundColor: 'rgba(255, 107, 107, 1)',
                hoverBorderColor: 'rgba(255, 107, 107, 1)',
                hoverBorderWidth: 3,
                barThickness: 'flex',
                maxBarThickness: 60
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.85)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 12,
                    titleFont: {
                        family: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                        size: 14,
                        weight: '600'
                    },
                    bodyFont: {
                        family: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                        size: 13
                    },
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' students';
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'CGPA Range',
                        font: {
                            family: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                            size: 14,
                            weight: '600'
                        },
                        color: '#495057',
                        padding: 15
                    },
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                            size: 12,
                            weight: '500'
                        },
                        color: '#495057'
                    },
                    border: {
                        display: false
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Students',
                        font: {
                            family: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                            size: 14,
                            weight: '600'
                        },
                        color: '#495057',
                        padding: 15
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.08)',
                        drawBorder: false,
                        lineWidth: 1
                    },
                    ticks: {
                        font: {
                            family: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
                            size: 12,
                            weight: '500'
                        },
                        color: '#495057',
                        padding: 10,
                        callback: function(value) {
                            return value + ' students';
                        }
                    },
                    border: {
                        display: false
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            }
        }
    });
}

/**
 * Refreshes all charts with new data.
 * 
 * @function refreshCharts
 * @returns {void}
 */
function refreshCharts() {
    loadDashboardStats();
}

// Initialize dashboard when document is ready
$(document).ready(function () {
    loadDashboardStats();
    Utils.hidePageLoader();

});
</script>
@endpush
