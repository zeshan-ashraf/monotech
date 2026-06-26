@extends('admin.layout.app')
@section('title', 'OPS Dashboard')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
    <div class="app-content content">
        <div class="content-overlay"></div>
        <div class="content-wrapper container-xxl pt-0 px-0 pb-sm-0 pb-5">
            <div class="content-body">
                <div class="ops-dashboard" id="ops-dashboard">
                    @include('admin.dashboard.server-info', ['serverInfo' => $serverInfo])
                </div>
            </div>
        </div>
    </div>
@endsection
