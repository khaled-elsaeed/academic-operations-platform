<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" dir="ltr" data-theme="theme-default" data-layout="vertical-menu">
   <head>
      <meta charset="utf-8" />
      <meta
         name="viewport"
         content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0"
         />
      <title>@yield('title', 'Auth | AcadOps')</title>
      <meta name="description" content="" />
      @include('layouts.partials.auth-styles')
   </head>
   <body>
      <!-- Content -->
      <div class="container-xxl">
         <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
               <!-- Register -->
               <div class="card">
                  <div class="card-body">
                     @yield('content')
                  </div>
               </div>
               <!-- /Register -->
            </div>
         </div>
      </div>
      <!-- / Content -->
      @include('layouts.partials.footer')

      @include('layouts.partials.auth-scripts')
   </body>
</html>