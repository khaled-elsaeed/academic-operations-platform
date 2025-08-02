@extends('layouts.home')

@section('title', 'Academic Term Management | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    {{-- ===== STATISTICS CARDS ===== --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 
                id="terms"
                label="Total Terms"
                color="primary"
                icon="bx bx-calendar"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 
                id="active"
                label="Active Terms"
                color="success"
                icon="bx bx-check-circle"
            />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.card.stat2 
                id="inactive"
                label="Inactive Terms"
                color="warning"
                icon="bx bx-x-circle"
            />
        </div>
    </div>

    {{-- ===== PAGE HEADER & ACTION BUTTONS ===== --}}
    <x-ui.page-header 
        title="Terms"
        description="Manage academic terms and their details."
        icon="bx bx-calendar"
    >
        @can('term.create')
            <button class="btn btn-primary" 
                    id="addTermBtn" 
                    type="button" 
                    data-bs-toggle="modal" 
                    data-bs-target="#termModal">
                <i class="bx bx-plus me-1"></i> Add Term
            </button>
        @endcan
        <button class="btn btn-secondary ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#termSearchCollapse" aria-expanded="false" aria-controls="termSearchCollapse">
            <i class="bx bx-filter-alt me-1"></i> Search
        </button>
    </x-ui.page-header>

    {{-- ===== ADVANCED SEARCH SECTION ===== --}}
    <x-ui.advanced-search 
        title="Advanced Search" 
        formId="advancedTermSearch" 
        collapseId="termSearchCollapse"
        :collapsed="false"
        :show-clear-button="true"
        clear-button-text="Clear Filters"
        clear-button-id="clearTermFiltersBtn"
    >
        <div class="col-md-3">
            <label for="search_season" class="form-label">Season:</label>
            <select class="form-control" id="search_season" name="search_season">
                <option value="">All Seasons</option>
                <option value="Fall">Fall</option>
                <option value="Spring">Spring</option>
                <option value="Summer">Summer</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="search_year" class="form-label">Academic Year:</label>
            <input type="text" class="form-control" id="search_year" name="search_year" placeholder="e.g., 2015-2016">
        </div>
        <div class="col-md-3">
            <label for="search_active" class="form-label">Status:</label>
            <select class="form-control" id="search_active" name="search_active">
                <option value="">All Status</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        </div>
    </x-ui.advanced-search>

    {{-- ===== DATA TABLE ===== --}}
    <x-ui.datatable
        :headers="['Season', 'Year', 'Code', 'Enrollments Count', 'Active', 'Action']"
        :columns="[
            ['data' => 'season', 'name' => 'season'],
            ['data' => 'year', 'name' => 'year'],
            ['data' => 'code', 'name' => 'code'],
            ['data' => 'enrollments_count', 'name' => 'enrollments_count'],
            ['data' => 'is_active', 'name' => 'is_active'],
            ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false],
        ]"
        :ajax-url="route('terms.datatable')"
        table-id="terms-table"
        :filter-fields="['search_season','search_year','search_active']"
    />

    {{-- ===== MODALS SECTION ===== --}}
    {{-- Add/Edit Term Modal --}}
    <x-ui.modal 
        id="termModal"
        title="Add/Edit Term"
        size="lg"
        :scrollable="false"
        class="term-modal"
    >
        <x-slot name="slot">
            <form id="termForm">
                @csrf
                <input type="hidden" id="term_id" name="term_id">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="season" class="form-label">Season <span class="text-danger">*</span></label>
                        <select class="form-select" id="season" name="season" required>
                            <option value="">Select Season</option>
                            <option value="Fall">Fall</option>
                            <option value="Spring">Spring</option>
                            <option value="Summer">Summer</option>
                        </select>
                        @error('season')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="year" name="year" required>
                        @error('year')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1">
                            <label class="form-check-label" for="is_active">
                                Active Term
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Close
            </button>
            <button type="submit" class="btn btn-primary" id="saveTermBtn" form="termForm">Save</button>
        </x-slot>
    </x-ui.modal>
</div>
@endsection

@push('scripts')
<script>
/**
 * Academic Term Management System JavaScript
 * Handles CRUD operations for academic terms
 */

// ===========================
// CONSTANTS & CONFIGURATION
// ===========================

const ROUTES = {
    terms: {
        stats: '{{ route('terms.stats') }}',
        store: '{{ route('terms.store') }}',
        show: '{{ route('terms.show', ':id') }}',
        update: '{{ route('terms.update', ':id') }}',
        destroy: '{{ route('terms.destroy', ':id') }}'
    }
};

const SELECTORS = {
    termForm: '#termForm',
    termModal: '#termModal',
    addTermBtn: '#addTermBtn',
    saveTermBtn: '#saveTermBtn',
    clearTermFiltersBtn: '#clearTermFiltersBtn',
    termsTable: '#terms-table',
    searchSeason: '#search_season',
    searchYear: '#search_year',
    searchActive: '#search_active'
};

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
        Swal.fire({
            title: 'Error',
            html: message,
            icon: 'error'
        });
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

    replaceRouteId(route, id) {
        return route.replace(':id', id);
    },

    normalizeSeason(season) {
        if (!season) return '';
        return season.charAt(0).toUpperCase() + season.slice(1).toLowerCase();
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
// API SERVICE LAYER
// ===========================

const ApiService = {
    request(options) {
        return $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            ...options
        });
    },

    fetchTermStats() {
        return this.request({
            url: ROUTES.terms.stats,
            method: 'GET'
        });
    },

    fetchTerm(id) {
        return this.request({
            url: Utils.replaceRouteId(ROUTES.terms.show, id),
            method: 'GET'
        });
    },

    saveTerm(formData, id = null) {
        const url = id ? Utils.replaceRouteId(ROUTES.terms.update, id) : ROUTES.terms.store;
        return this.request({
            url: url,
            method:'POST',
            data: formData,
            processData: false,
            contentType: false
        });
    },

    deleteTerm(id) {
        return this.request({
            url: Utils.replaceRouteId(ROUTES.terms.destroy, id),
            method: 'DELETE'
        });
    }
};

// ===========================
// STATISTICS MANAGEMENT
// ===========================

const StatsManager = {
    loadTermStats() {
        Utils.toggleLoadingState('terms', true);
        Utils.toggleLoadingState('active', true);
        Utils.toggleLoadingState('inactive', true);

        ApiService.fetchTermStats()
            .done((response) => {
                const data = response.data;
                $('#terms-value').text(data.total.total ?? '--');
                $('#terms-last-updated').text(data.total.lastUpdateTime ?? '--');
                $('#active-value').text(data.active.total ?? '--');
                $('#active-last-updated').text(data.active.lastUpdateTime ?? '--');
                $('#inactive-value').text(data.inactive.total ?? '--');
                $('#inactive-last-updated').text(data.inactive.lastUpdateTime ?? '--');

                Utils.toggleLoadingState('terms', false);
                Utils.toggleLoadingState('active', false);
                Utils.toggleLoadingState('inactive', false);
            })
            .fail(() => {
                $('#terms-value, #active-value, #inactive-value').text('N/A');
                $('#terms-last-updated, #active-last-updated, #inactive-last-updated').text('N/A');
                Utils.toggleLoadingState('terms', false);
                Utils.toggleLoadingState('active', false);
                Utils.toggleLoadingState('inactive', false);
                Utils.showError('Failed to load term statistics');
            });
    }
};

// ===========================
// TERM CRUD OPERATIONS
// ===========================

const TermManager = {
    handleAddTerm() {
        $(SELECTORS.addTermBtn).on('click', () => {
            $(SELECTORS.termForm)[0].reset();
            $('#term_id').val('');
            $(SELECTORS.termModal + ' .modal-title').text('Add Term');
            $(SELECTORS.saveTermBtn).text('Save');
            $(SELECTORS.termModal).modal('show');
        });
    },

    handleTermFormSubmit() {
        $(SELECTORS.termForm).on('submit', (e) => {
            e.preventDefault();
            const termId = $('#term_id').val();
            const formData = new FormData(e.target);
            formData.set('is_active', formData.get('is_active') ? '1' : '0');

            const $submitBtn = $(SELECTORS.saveTermBtn);
            const originalText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text('Saving...');

            ApiService.saveTerm(formData, termId || null)
                .done((response) => {
                    $(SELECTORS.termModal).modal('hide');
                    $(SELECTORS.termsTable).DataTable().ajax.reload(null, false);
                    Utils.showSuccess(response.message || 'Term saved successfully.');
                    StatsManager.loadTermStats();
                })
                .fail((xhr) => {
                    const response = xhr.responseJSON;
                    const errorMessages = response?.errors
                        ? Object.values(response.errors).flat().join('<br>')
                        : response?.message || 'An error occurred. Please check your input.';
                    Utils.showError(errorMessages);
                })
                .always(() => {
                    $submitBtn.prop('disabled', false).text(originalText);
                });
        });
    },

    handleEditTerm() {
        $(document).on('click', '.editTermBtn', function () {
            const termId = $(this).data('id');

            ApiService.fetchTerm(termId)
                .done((response) => {
                    const term = response.data;

                    // Normalize season to match select options
                    const normalizedSeason = Utils.normalizeSeason(term.season);

                    // Populate form fields
                    $('#term_id').val(term.id);
                    $('#season').val(normalizedSeason).trigger('change');
                    $('#year').val(term.year);
                    $('#is_active').prop('checked', !!term.is_active);

                    // Update modal
                    $(SELECTORS.termModal + ' .modal-title').text('Edit Term');
                    $(SELECTORS.saveTermBtn).text('Update');
                    $(SELECTORS.termModal).modal('show');
                })
                .fail(() => {
                    Utils.showError('Failed to load term details.');
                });
        });
    },

    handleDeleteTerm() {
        $(document).on('click', '.deleteTermBtn', function () {
            const termId = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    ApiService.deleteTerm(termId)
                        .done((response) => {
                            $(SELECTORS.termsTable).DataTable().ajax.reload(null, false);
                            Utils.showSuccess(response.message || 'Term has been deleted.');
                            StatsManager.loadTermStats();
                        })
                        .fail((xhr) => {
                            const response = xhr.responseJSON;
                            const message = response?.message || 'Failed to delete term.';
                            Utils.showError(message);
                        });
                }
            });
        });
    }
};

// ===========================
// FORM VALIDATION & HELPERS
// ===========================

const FormManager = {
    handleSeasonYearChange() {
        $('#season, #year').on('change', function() {
            const season = $('#season').val();
            const year = $('#year').val();

            if (season && year) {
                let seasonCode = '';
                switch (season) {
                    case 'Fall': seasonCode = '1'; break;
                    case 'Spring': seasonCode = '2'; break;
                    case 'Summer': seasonCode = '3'; break;
                }

                if (seasonCode) {
                    const shortYear = year.toString().slice(-2);
                    const generatedCode = shortYear + seasonCode;
                    $('#code').val(generatedCode);
                }
            }
        });
    }
};

// ===========================
// SEARCH FUNCTIONALITY
// ===========================

const SearchManager = {
    initializeAdvancedSearch() {
        let searchTimeout;

        $(SELECTORS.searchYear).on('keyup', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                $(SELECTORS.termsTable).DataTable().ajax.reload();
            }, 500);
        });

        $(SELECTORS.searchSeason + ', ' + SELECTORS.searchActive).on('change', function() {
            $(SELECTORS.termsTable).DataTable().ajax.reload();
        });

        $(SELECTORS.clearTermFiltersBtn).on('click', function() {
            $(SELECTORS.searchSeason + ', ' + SELECTORS.searchYear + ', ' + SELECTORS.searchActive).val('');
            $(SELECTORS.termsTable).DataTable().ajax.reload();
        });
    }
};

// ===========================
// INITIALIZATION
// ===========================

$(document).ready(function () {
    StatsManager.loadTermStats();
    TermManager.handleAddTerm();
    TermManager.handleTermFormSubmit();
    TermManager.handleEditTerm();
    TermManager.handleDeleteTerm();
    FormManager.handleSeasonYearChange();
    SearchManager.initializeAdvancedSearch();
    Utils.hidePageLoader();

});
</script>
@endpush
