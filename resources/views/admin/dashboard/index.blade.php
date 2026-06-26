@extends('admin.layout.app')
@section('title', 'OPS Dashboard')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
    {{-- OPS Dashboard: full-bleed monitoring layout inside admin panel --}}
    <div class="app-content content ops-dashboard-page p-0">
        <div class="content-wrapper p-0 m-0 w-100">
            <div class="ops-dashboard" id="ops-dashboard">

                {{-- Top navigation bar --}}
                @include('admin.dashboard.navbar', ['refreshIntervals' => $refreshIntervals])

                <div class="ops-dashboard__body">

                    {{-- Left sidebar navigation + server info --}}
                    @include('admin.dashboard.sidebar', [
                        'sidebarNav' => $sidebarNav,
                        'serverInfo' => $serverInfo,
                    ])

                    {{-- Main dashboard content --}}
                    <main class="ops-dashboard__main">

                        {{-- Overview metric cards row --}}
                        @include('admin.dashboard.cards', ['overviewCards' => $overviewCards])

                        {{-- PHP-FPM & MySQL status row --}}
                        <div class="row g-3 mb-3">
                            <div class="col-xl-7">
                                @include('admin.dashboard.phpfpm', ['phpFpm' => $phpFpm])
                            </div>
                            <div class="col-xl-5">
                                @include('admin.dashboard.mysql', ['mysql' => $mysql])
                            </div>
                        </div>

                        {{-- Payments & Alerts row --}}
                        <div class="row g-3 mb-3">
                            <div class="col-xl-8">
                                @include('admin.dashboard.payments', [
                                    'payments' => $payments,
                                    'transactions' => $transactions,
                                    'paymentStats' => $paymentStats,
                                ])
                            </div>
                            <div class="col-xl-4">
                                @include('admin.dashboard.alerts', ['alerts' => $alerts])
                            </div>
                        </div>

                        {{-- Bottom history charts row --}}
                        @include('admin.dashboard.bottom-charts')

                    </main>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        window.opsDashboardData = @json($chartData);
    </script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
