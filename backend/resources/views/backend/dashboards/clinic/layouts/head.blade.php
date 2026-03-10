<!-- Title -->
<title>@yield('title')</title>

<!-- Favicon -->
<link rel="shortcut icon" href="{{ asset('backend/assets/images/favicon.ico') }}" type="image/x-icon" />

<!-- PWA  -->
<meta name="theme-color" content="#6777ef" />
<link rel="apple-touch-icon" href="{{ asset('logo.PNG') }}">
<link rel="manifest" href="{{ asset('/manifest.json') }}">


<!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Font -->
<link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Poppins:200,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900">

@yield('css')
<!--- Style css -->


<!-- <link href="{{ asset('backend/assets/datatables/jquery.dataTables.min.css') }}" rel="stylesheet"> -->

<!-- DataTables CSS -->
<!-- Combine all DataTables CSS into one file -->
<!-- <link href="{{asset('backend/assets/datatable/css/datatables.bundle.css')}}" rel="stylesheet" type="text/css" /> -->

<!-- Add preload for critical fonts -->
<link rel="preload" href="{{ asset('backend/assets/fonts/Almarai-Regular.ttf') }}" as="font" type="font/ttf"
          crossorigin="anonymous">

<!-- Add defer to non-critical CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" rel="stylesheet" media="print"
          onload="this.media='all'">

<!-- Remove Tailwind CDN and use compiled version -->
<!-- <link href="{{ asset('backend/assets/css/tailwind.min.css') }}" rel="stylesheet"> -->

<link rel="stylesheet" href="{{ asset('backend/assets/css/summernote.min.css') }}">



@livewireStyles

<!--- Style css -->
@if (App::getLocale() !== 'ar')
<link href="{{ asset('backend/assets/css/ltr.css') }}" rel="stylesheet">
@else
<link rel="preload" href="{{ asset('backend/assets/fonts/Almarai-Regular.ttf') }}" as="font" type="font/ttf"
          crossorigin="anonymous">
<link rel="preload" href="{{ asset('backend/assets/fonts/Almarai-Bold.ttf') }}" as="font" type="font/ttf"
          crossorigin="anonymous">

<link href="{{ asset('backend/assets/css/rtl.css') }}" rel="stylesheet">
@endif

<link href="{{ asset('backend/assets/css/responsive.css') }}" rel="stylesheet">

<!-- Keep the original DataTables CSS files for now -->
<link href="{{asset('backend/assets/datatable/css/dataTables.bootstrap5.css')}}" rel="stylesheet" type="text/css" />
<link href="{{asset('backend/assets/datatable/css/responsive.bootstrap5.css')}}" rel="stylesheet" type="text/css" />
<link href="{{asset('backend/assets/datatable/css/buttons.bootstrap5.css')}}" rel="stylesheet" type="text/css" />

<!-- Remove the non-existent tailwind.min.css -->
@if (config('app.env') !== 'production')
<script src="https://cdn.tailwindcss.com" defer></script>
@endif
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/emoji-mart@2.6.4/css/emoji-mart.css">

<style>
    .sidebar-search input {
        margin-top: 20px;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 14px;
}

</style>

@stack('styles')
