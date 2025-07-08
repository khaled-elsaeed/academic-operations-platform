@props([
    'color' => 'primary', // e.g. primary, warning, danger, info
    'icon' => 'bx bx-user', // icon class
    'value' => 0,
    'label' => '',
    'lastUpdated' => null // optional, if not provided, don't show
])
<div class="card card-border-shadow-{{ $color }} h-100 card-hover-effect">
    <div class="card-body">
        <div class="d-flex align-items-center mb-2">
            <div class="avatar me-4">
                <span class="avatar-initial rounded bg-label-{{ $color }}">
                    <i class="icon-base {{ $icon }} icon-lg"></i>
                </span>
            </div>
            <h4 class="mb-0">{{ $value }}</h4>
        </div>
        <p class="mb-2">{{ $label }}</p>
        @if($lastUpdated)
            <p class="mb-0">
                <small class="text-body-secondary">Last updated: {{ $lastUpdated }}</small>
            </p>
        @endif
    </div>
</div> 