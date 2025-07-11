@extends('layouts.app')

@section('content')
  <div class="layout-container">
    <!-- Sidebar -->
    <x-navigation.sidebar />
    <!-- / Sidebar -->
    <!-- Layout container -->
    <div class="layout-page">
      <!-- Navbar -->
      <x-navigation.navbar />
      <!-- / Navbar -->
      <!-- Content wrapper -->
      <div class="content-wrapper">
        <!-- Content -->
        @yield('page-content')
        <!-- / Content -->
        <div class="content-backdrop fade"></div>
      </div>
      <!-- Content wrapper -->
    </div>
    <!-- / Layout page -->
  </div>
@endsection