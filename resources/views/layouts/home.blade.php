@extends('layouts.app')

@section('content')
  <div class="layout-container">
    <!-- Sidebar -->
    <x-navigation.sidebar />
    <!-- / Sidebar -->

    <div class="layout-page">
      <!-- Navbar -->
      <x-navigation.navbar />
      <!-- / Navbar -->

      <div class="content-wrapper">
        <!-- Content -->
        @yield('page-content')
        <!-- / Content -->

        <div class="content-backdrop fade"></div>
      </div>
      <!-- / Content wrapper -->

      @include('layouts.partials.footer')
    </div>
    <!-- / Layout page -->
  </div>
@endsection