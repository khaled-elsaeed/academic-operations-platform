<div @if($id) id="{{ $id }}" @endif class="card card-border-shadow-{{ $color }} h-100 card-hover-effect">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
            <div class="content-left">
                <span class="text-heading">{{ $label }}</span>
                <div class="d-flex align-items-center my-1">
                    <span class="stat-loader me-2" id="{{ $id ? $id . '-loader' : '' }}">
                        <span class="spinner-border spinner-border-sm text-{{ $color }}" role="status" aria-hidden="true"></span>
                    </span>
                    <h4 class="mb-0 me-2 stat-value d-none" id="{{ $id ? $id . '-value' : '' }}">--</h4>
                </div>
                <small class="mb-0 text-body-secondary">
                    Last update: 
                    <span class="stat-last-updated-loader" id="{{ $id ? $id . '-last-updated-loader' : '' }}">
                        <span class="spinner-border spinner-border-sm text-{{ $color }}" role="status" aria-hidden="true"></span>
                    </span>
                    <span class="stat-last-updated d-none" id="{{ $id ? $id . '-last-updated' : '' }}">--</span>
                </small>
            </div>
            <div class="avatar">
                <span class="avatar-initial rounded bg-label-{{ $color }}">
                    <i class="icon-base {{ $icon }} icon-lg"></i>
                </span>
            </div>
        </div>
    </div>
</div> 