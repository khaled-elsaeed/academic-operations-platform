@extends('layouts.home')

@section('title', 'Admin Dashboard | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
<div class="row g-6 mb-6">
    <div class="col-12 col-sm-6 col-lg-3 mb-4">
      <x-ui.card.stat 
        color="primary"
        icon="bx bx-user"
        :value="0"
        label="Total Students"
        :lastUpdated="now()->format('j M, g:i A')"
      />
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-4">
      <x-ui.card.stat 
        color="warning"
        icon="bx bx-chalkboard"
        :value="0"
        label="Total Faculty"
        :lastUpdated="now()->format('j M, g:i A')"
      />
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-4">
      <x-ui.card.stat 
        color="danger"
        icon="bx bx-book"
        :value="0"
        label="Total Programs"
        :lastUpdated="now()->format('j M, g:i A')"
      />
    </div>
    <div class="col-12 col-sm-6 col-lg-3 mb-4">
      <x-ui.card.stat 
        color="info"
        icon="bx bx-library"
        :value="0"
        label="Total Courses"
        :lastUpdated="now()->format('j M, g:i A')"
      />
    </div>
  </div>
   
</div>
@endsection

