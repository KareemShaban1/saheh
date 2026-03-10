@extends('backend.dashboards.clinic.layouts.master')

@section('title')
{{ trans('backend/dashboard_trans.Dashboard') }} - Vue
@endsection

@section('css')
<style type="text/css">
    a[disabled="disabled"] {
        pointer-events: none;
    }
    #vue-app {
        min-height: 400px;
    }
</style>
@endsection

@section('page-header')
<h4 class="page-title">{{ trans('backend/dashboard_trans.Dashboard') }} - Vue</h4>
@endsection

@section('content')
<!-- Vue App Container -->
<div id="vue-app">
    <!-- Loading fallback while Vue loads -->
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <p class="mt-2">Loading Vue Dashboard...</p>
    </div>
</div>
@endsection

@push('scripts')
@vite(['resources/js/vue-app.js'])
@endpush







