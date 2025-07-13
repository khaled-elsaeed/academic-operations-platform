<!DOCTYPE html>
<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-layout="vertical-menu"
>
  <head>
    @include('layouts.partials.meta')

    @include('layouts.partials.styles')

    @stack('styles')
    
  </head>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar d-flex flex-column min-vh-100">

      <div class="flex-grow-1">
        @yield('content')
      </div>

      <!-- Overlay -->
      <div class="layout-overlay layout-menu-toggle"></div>

    </div>

    <!-- / Layout wrapper -->
    @include('layouts.partials.scripts')
    @stack('scripts')
  </body>
</html>
