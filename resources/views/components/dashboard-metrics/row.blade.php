@props([
    'user',
    'metrics' => [],
])

@php
    $uid = $user->id;
    $name = $user->name;
    $jcSr = round($metrics['jc_success_rate'] ?? 0, 2);
    $epSr = round($metrics['ep_success_rate'] ?? 0, 2);
    $jcPending = (int) ($metrics['jc_pending'] ?? 0);
    $epPending = (int) ($metrics['ep_pending'] ?? 0);
@endphp

<div class="row mt-1 dashboard-metrics-row" data-user-id="{{ $uid }}">
    <div class="col-md-3">
        <div class="card shadow-lg card-graph">
            <div class="card-body">
                <h5 class="card-title font-weight-bold">{{ $name }} JazzCash Success Rate</h5>
                <div id="dm-jc-sr-{{ $uid }}" class="dashboard-metrics-chart" style="margin-top: -40px;"></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-graph-red">
            <div class="card-header">
                <h5 class="card-title font-weight-bold">{{ $name }} JazzCash Pending Orders</h5>
            </div>
            <div class="card-body" style="margin-top: -40px;">
                <div id="dm-jc-pending-{{ $uid }}" class="dashboard-metrics-chart"></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-lg card-graph">
            <div class="card-body">
                <h5 class="card-title font-weight-bold">{{ $name }} Easypaisa Success Rate</h5>
                <div id="dm-ep-sr-{{ $uid }}" class="dashboard-metrics-chart" style="margin-top: -40px;"></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card card-graph-green">
            <div class="card-header text-center">
                <h5 class="card-title font-weight-bold">{{ $name }} Easypaisa Pending Orders</h5>
            </div>
            <div class="card-body" style="margin-top: -40px;">
                <div id="dm-ep-pending-{{ $uid }}" class="dashboard-metrics-chart"></div>
            </div>
        </div>
    </div>
</div>
