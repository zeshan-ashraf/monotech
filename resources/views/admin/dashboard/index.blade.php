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

                    @include('admin.dashboard.navbar', ['refreshIntervals' => $refreshIntervals])

                    <div class="ops-dashboard__main">

                        {{-- Live server host details --}}
                        @include('admin.dashboard.server-info', ['serverInfo' => $serverInfo])

                        {{-- Live overview metric cards --}}
                        @include('admin.dashboard.cards', ['overviewCards' => $overviewCards])

                        {{-- PHP-FPM & MySQL (placeholder) --}}
                        <div class="row g-3 mb-3">
                            <div class="col-xl-7">
                                @include('admin.dashboard.phpfpm', ['phpFpm' => $phpFpm])
                            </div>
                            <div class="col-xl-5">
                                @include('admin.dashboard.mysql', ['mysql' => $mysql])
                            </div>
                        </div>

                        {{-- Payments & Alerts (placeholder) --}}
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

                        {{-- Bottom history charts (placeholder) --}}
                        @include('admin.dashboard.bottom-charts')

                    </div>
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
