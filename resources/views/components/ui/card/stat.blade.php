<div @if($id) id="{{ $id }}" @endif class="card card-border-shadow-{{ $color }} h-100 card-hover-effect">
    <div class="card-body">
        <div class="d-flex align-items-center mb-2">
            <div class="avatar me-4">
                <span class="avatar-initial rounded bg-label-{{ $color }}">
                    <i class="icon-base {{ $icon }} icon-lg"></i>
                </span>
            </div>
            <h4 class="mb-0">
                <span class="stat-loader" id="{{ $id ? $id . '-loader' : '' }}">
                    <span class="spinner-border spinner-border-sm text-{{ $color }}" role="status" aria-hidden="true"></span>
                </span>
                <span class="stat-value d-none" id="{{ $id ? $id . '-value' : '' }}"></span>
            </h4>
        </div>
        <p class="mb-2">{{ $label }}</p>
        <p class="mb-0">
            <small class="text-body-secondary">
                Last updated: 
                <span class="stat-last-updated-loader" id="{{ $id ? $id . '-last-updated-loader' : '' }}">
                    <span class="spinner-border spinner-border-sm text-{{ $color }}" role="status" aria-hidden="true"></span>
                </span>
                <span class="stat-last-updated d-none" id="{{ $id ? $id . '-last-updated' : '' }}"></span>
            </small>
        </p>
    </div>
</div> 