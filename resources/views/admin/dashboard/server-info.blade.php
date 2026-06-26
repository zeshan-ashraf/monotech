{{-- Server host details (live) — compact strip, separate from metric cards --}}
<section class="ops-card ops-server-host mb-3" aria-label="Server host details">
    <div class="ops-server-host__header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h6 class="ops-server-host__title mb-0">
            <i class="fa fa-server text-primary me-2"></i>Server Information
        </h6>
        <x-ops-dashboard.health-badge
            :status="$serverInfo['health']"
            :color="$serverInfo['health_color']"
        />
    </div>
    <div class="ops-server-host__grid">
        <div class="ops-server-host__item">
            <span class="ops-server-host__label">Hostname</span>
            <span class="ops-server-host__value">{{ $serverInfo['hostname'] }}</span>
        </div>
        <div class="ops-server-host__item">
            <span class="ops-server-host__label">OS</span>
            <span class="ops-server-host__value">{{ $serverInfo['os'] }}</span>
        </div>
        <div class="ops-server-host__item">
            <span class="ops-server-host__label">Uptime</span>
            <span class="ops-server-host__value">{{ $serverInfo['uptime'] }}</span>
        </div>
        <div class="ops-server-host__item">
            <span class="ops-server-host__label">IP Address</span>
            <span class="ops-server-host__value">{{ $serverInfo['ip_address'] }}</span>
        </div>
        <div class="ops-server-host__item">
            <span class="ops-server-host__label">Kernel</span>
            <span class="ops-server-host__value">{{ $serverInfo['kernel'] }}</span>
        </div>
    </div>
</section>
