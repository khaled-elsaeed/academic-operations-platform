<!-- Favicon -->
<link rel="icon" type="image/x-icon" href="{{ asset('img/favicon/favicon.ico') }}" />

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
    href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
    rel="stylesheet"
/>

<!-- Icons. Uncomment required icon fonts -->
<link rel="stylesheet" href="{{ asset('vendor/fonts/boxicons.css') }}?v={{ config('app.version') }}" />

<!-- Core CSS -->
<link rel="stylesheet" href="{{ asset('vendor/css/core.css') }}?v={{ config('app.version') }}" class="template-customizer-core-css" />
<link rel="stylesheet" href="{{ asset('vendor/css/theme-default.css') }}?v={{ config('app.version') }}" class="template-customizer-theme-css" />
<link rel="stylesheet" href="{{ asset('css/demo.css') }}?v={{ config('app.version') }}" />

<!-- Vendors CSS -->
<link rel="stylesheet" href="{{ asset('vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}?v={{ config('app.version') }}" />

<!-- Page CSS -->
<!-- Page -->
<link rel="stylesheet" href="{{ asset('vendor/css/pages/page-auth.css') }}?v={{ config('app.version') }}" />
<!-- Helpers -->
<script src="{{ asset('vendor/js/helpers.js') }}?v={{ config('app.version') }}"></script>

<!--! Template customizer & Theme config files MUST be included after core stylesheets and helpers.js in the <head> section -->
<!--? Config:  Mandatory theme config file contain global vars & default theme options, Set your preferred theme option in this file.  -->
<script src="{{ asset('js/config.js') }}?v={{ config('app.version') }}"></script>