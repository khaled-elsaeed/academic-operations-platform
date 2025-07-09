<div class="border rounded p-4 mb-4 bg-white bg-opacity-0">
  <div class="d-flex flex-wrap justify-content-between align-items-center">
    <div class="mb-2 mb-md-0">
      <div class="d-flex align-items-center mb-1">
        @if(isset($icon))
          <i class="{{ $icon }} fs-3 me-2 text-primary"></i>
        @endif
        <h3 class="fw-bold mb-0">{{ $title }}</h3>
      </div>
      @if(isset($description))
        <div class="text-muted small">{{ $description }}</div>
      @endif
    </div>
    <div class="mt-3 mt-md-0">
      {{ $slot }}
    </div>
  </div>
</div> 