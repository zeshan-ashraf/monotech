{{-- Server information panel (Phase 2 — live metrics) --}}
<section class="ops-card ops-server-panel" aria-label="Server information">

    {{-- Panel header --}}
    <div class="ops-server-panel__header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="ops-server-panel__title mb-0">
            <i class="fa fa-server text-primary me-2"></i>Server Information
        </h5>
        <x-ops-dashboard.health-badge
            :status="$serverInfo['health']"
            :color="$serverInfo['health_color']"
        />
    </div>

    {{-- Host details --}}
    <div class="ops-server-panel__body">
        <div class="ops-server-info__grid ops-server-info__grid--host">
            <div class="ops-server-info__item">
                <span class="ops-server-info__label"><i class="fa fa-desktop me-1"></i>Hostname</span>
                <span class="ops-server-info__value">{{ $serverInfo['hostname'] }}</span>
            </div>
            <div class="ops-server-info__item">
                <span class="ops-server-info__label"><i class="fa fa-linux me-1"></i>OS</span>
                <span class="ops-server-info__value">{{ $serverInfo['os'] }}</span>
            </div>
            <div class="ops-server-info__item">
                <span class="ops-server-info__label"><i class="fa fa-clock-o me-1"></i>Uptime</span>
                <span class="ops-server-info__value">{{ $serverInfo['uptime'] }}</span>
            </div>
            <div class="ops-server-info__item">
                <span class="ops-server-info__label"><i class="fa fa-globe me-1"></i>IP Address</span>
                <span class="ops-server-info__value">{{ $serverInfo['ip_address'] }}</span>
            </div>
            <div class="ops-server-info__item">
                <span class="ops-server-info__label"><i class="fa fa-cog me-1"></i>Kernel</span>
                <span class="ops-server-info__value">{{ $serverInfo['kernel'] }}</span>
            </div>
        </div>

        {{-- Resource metrics --}}
        <div class="row g-3 ops-server-metrics">

            {{-- CPU --}}
            <div class="col-xl col-md-6">
                <x-ops-dashboard.server-metric
                    title="CPU Usage"
                    icon="fa-microchip"
                    color="primary"
                    :value="$serverInfo['cpu']['usage']"
                    :subtitle="$serverInfo['cpu']['cores']"
                />
            </div>

            {{-- RAM --}}
            <div class="col-xl col-md-6">
                <x-ops-dashboard.server-metric
                    title="RAM Usage"
                    icon="fa-server"
                    color="success"
                    :value="$serverInfo['ram']['used']"
                    :subtitle="$serverInfo['ram']['percentage'] . ' of ' . $serverInfo['ram']['total']"
                />
            </div>

            {{-- Disk --}}
            <div class="col-xl col-md-6">
                <x-ops-dashboard.server-metric
                    title="Disk Usage"
                    icon="fa-hdd-o"
                    color="info"
                    :value="$serverInfo['disk']['used']"
                    :subtitle="$serverInfo['disk']['percentage'] . ' of ' . $serverInfo['disk']['total']"
                />
            </div>

            {{-- Load average --}}
            <div class="col-xl col-md-6">
                <div class="ops-server-metric ops-server-metric--warning h-100">
                    <div class="ops-server-metric__icon bg-warning bg-opacity-10 text-warning">
                        <i class="fa fa-tachometer"></i>
                    </div>
                    <div class="ops-server-metric__content">
                        <p class="ops-server-metric__title mb-1">Load Average</p>
                        <h4 class="ops-server-metric__value mb-2">{{ $serverInfo['load_average']['current'] }}</h4>
                        <div class="ops-load-grid">
                            <div class="ops-load-grid__item">
                                <span class="ops-load-grid__label">1m</span>
                                <span class="ops-load-grid__value">{{ $serverInfo['load_average']['1m'] }}</span>
                            </div>
                            <div class="ops-load-grid__item">
                                <span class="ops-load-grid__label">5m</span>
                                <span class="ops-load-grid__value">{{ $serverInfo['load_average']['5m'] }}</span>
                            </div>
                            <div class="ops-load-grid__item">
                                <span class="ops-load-grid__label">15m</span>
                                <span class="ops-load-grid__value">{{ $serverInfo['load_average']['15m'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Network I/O --}}
            <div class="col-xl col-md-6">
                <div class="ops-server-metric ops-server-metric--secondary h-100">
                    <div class="ops-server-metric__icon bg-secondary bg-opacity-10 text-secondary">
                        <i class="fa fa-exchange"></i>
                    </div>
                    <div class="ops-server-metric__content">
                        <p class="ops-server-metric__title mb-1">Network I/O</p>
                        <h4 class="ops-server-metric__value mb-2">{{ $serverInfo['network']['total'] }}</h4>
                        <div class="ops-network-stats">
                            <span class="ops-network-stats__item text-info">
                                <i class="fa fa-arrow-down me-1"></i>{{ $serverInfo['network']['download'] }}
                            </span>
                            <span class="ops-network-stats__item text-success">
                                <i class="fa fa-arrow-up me-1"></i>{{ $serverInfo['network']['upload'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
