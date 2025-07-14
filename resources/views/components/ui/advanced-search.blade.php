@props([
    'title' => 'Advanced Search',
    'formId' => 'advancedSearchForm',
    'collapseId' => 'advancedSearchCollapse',
    'showClearButton' => false,
    'clearButtonText' => 'Clear Filters',
    'clearButtonId' => 'clearFiltersBtn',
])

<div class="collapse" id="{{ $collapseId }}">
    <div class="card p-4 mb-4">
        <h5 class="mb-3">{{ $title }}</h5>
        <form id="{{ $formId }}" class="row g-3 align-items-end" method="GET" autocomplete="off">
            {{ $slot }}
            @if($showClearButton)
                <div class="col-12">
                    <button class="btn btn-outline-secondary" id="{{ $clearButtonId }}" type="button">
                        <i class="bx bx-x"></i> {{ $clearButtonText }}
                    </button>
                </div>
            @endif
        </form>
    </div>
</div>