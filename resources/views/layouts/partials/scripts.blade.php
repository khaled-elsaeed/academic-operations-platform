<!-- Helpers -->
<script src="{{ asset('vendor/js/helpers.js') }}?v={{ config('app.version') }}"></script>

<!-- Core JS -->
<!-- build:js vendor/js/core.js -->
<script src="{{ asset('vendor/libs/jquery/jquery.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('vendor/libs/popper/popper.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('vendor/js/bootstrap.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('vendor/js/menu.js') }}?v={{ config('app.version') }}"></script>
<!-- endbuild -->

<!-- Vendor JS -->
<script src="{{ asset('vendor/libs/apex-charts/apexcharts.js') }}?v={{ config('app.version') }}"></script>

<!-- Main JS -->
<script src="{{ asset('js/main.js') }}?v={{ config('app.version') }}"></script>

<script src="{{ asset('vendor/libs/sweetalert2/sweetalert2.all.min.js') }}?v={{ config('app.version') }}"></script>

<script>
  // Attach CSRF token to all AJAX requests globally
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
</script>
