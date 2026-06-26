{{-- Server information strip --}}
<div class="ops-card ops-server-info mb-3">
    <div class="ops-server-info__header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h6 class="ops-server-info__title mb-0">
            <i class="fa fa-server text-primary me-1"></i> Server Info
        </h6>
        <x-ops-dashboard.health-badge :status="$serverInfo['health']" :color="$serverInfo['health_color']" />
    </div>
    <div class="ops-server-info__grid">
        <div class="ops-server-info__item">
            <span class="ops-server-info__label">Hostname</span>
            <span class="ops-server-info__value">{{ $serverInfo['hostname'] }}</span>
        </div>
        <div class="ops-server-info__item">
            <span class="ops-server-info__label">OS</span>
            <span class="ops-server-info__value">{{ $serverInfo['os'] }}</span>
        </div>
        <div class="ops-server-info__item">
            <span class="ops-server-info__label">Uptime</span>
            <span class="ops-server-info__value">{{ $serverInfo['uptime'] }}</span>
        </div>
        <div class="ops-server-info__item">
            <span class="ops-server-info__label">IP Address</span>
            <span class="ops-server-info__value">{{ $serverInfo['ip'] }}</span>
        </div>
        <div class="ops-server-info__item">
            <span class="ops-server-info__label">Kernel</span>
            <span class="ops-server-info__value">{{ $serverInfo['kernel'] }}</span>
        </div>
    </div>
</div>
