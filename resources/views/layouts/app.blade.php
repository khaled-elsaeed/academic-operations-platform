<!DOCTYPE html>
<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-layout="vertical-menu"
>
  <head>
    {{-- Meta tags --}}
    @include('layouts.partials.meta')

    {{-- Stylesheets --}}
    @include('layouts.partials.styles')
    @stack('styles')

    {{-- Initial Loader Script --}}
    <script>
      // Hide scrollbars while loading
      document.documentElement.style.overflow = 'hidden';
      window.addEventListener('DOMContentLoaded', function() {
        document.body.style.overflow = 'hidden';
      });

      // Dynamic loading messages for the loader
      document.addEventListener('DOMContentLoaded', function() {
        const loadingMessages = [
          "Loading...",
          "Preparing your experience...",
          "Almost there...",
          "Fetching data...",
          "Hang tight, we're working on it...",
        ];
        let idx = 0;
        const loadingTextEl = document.getElementById('dynamicLoadingText');
        if (loadingTextEl) {
          setInterval(() => {
            idx = (idx + 1) % loadingMessages.length;
            loadingTextEl.textContent = loadingMessages[idx];
          }, 2500);
        }
      });
    </script>
  </head>

  <body>
    {{-- Orbital Page Loader --}}
    <div id="pageLoader" class="page-loader">
      <div class="loader-container">
        <div class="orbital-loader">
          <div class="orbital-center"></div>
          <div class="orbital-ring"></div>
          <div class="orbital-ring"></div>
          <div class="orbital-ring"></div>
          <div class="orbital-ring"></div>
        </div>
        <div class="loading-text" id="dynamicLoadingText">Loading...</div>
      </div>
    </div>

    {{-- Layout Wrapper --}}
    <div class="layout-wrapper layout-content-navbar d-flex flex-column min-vh-100">
      <div class="flex-grow-1">
        @yield('content')
      </div>
      {{-- Overlay --}}
      <div class="layout-overlay layout-menu-toggle"></div>
    </div>
    {{-- /Layout Wrapper --}}

    {{-- Scripts --}}
    @include('layouts.partials.scripts')
    @stack('scripts')
  </body>
</html>