@props([
    'clients' => collect(),
    'metricsPayload' => [],
])

@if($clients->isNotEmpty())
    @once
        @push('css')
            <style>
                .card-graph {
                    background: #c8c9c2;
                    height: 75%;
                }
                .card-graph-red {
                    background: #df720694;
                    height: 75%;
                }
                .card-graph-green {
                    background: #58c38a80;
                    height: 75%;
                }
            </style>
        @endpush
    @endonce

    <div id="dashboard-metrics-panel">
        @foreach($clients as $client)
            @php
                $metrics = collect($metricsPayload)->firstWhere('user_id', $client->id) ?? [];
            @endphp
            <x-dashboard-metrics.row :user="$client" :metrics="$metrics" />
        @endforeach
    </div>

    @once
        @push('js')
            <script src="{{ asset('js/dashboard-metrics.js') }}"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (typeof window.DashboardMetrics === 'undefined') {
                        return;
                    }

                    window.DashboardMetrics.init({
                        apiUrl: @json(route('admin.dashboard.metrics')),
                        pollIntervalSeconds: @json((int) config('dashboard_metrics.poll_interval_seconds', 20)),
                        clients: @json($metricsPayload),
                    });
                });
            </script>
        @endpush
    @endonce
@endif
