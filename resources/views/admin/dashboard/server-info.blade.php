{{-- Server information block (sidebar footer) --}}
<div class="ops-server-info">
    <h6 class="ops-server-info__title">Server Info</h6>
    <dl class="ops-server-info__list mb-0">
        <div class="ops-server-info__row">
            <dt>Hostname</dt>
            <dd>{{ $serverInfo['hostname'] }}</dd>
        </div>
        <div class="ops-server-info__row">
            <dt>OS</dt>
            <dd>{{ $serverInfo['os'] }}</dd>
        </div>
        <div class="ops-server-info__row">
            <dt>Uptime</dt>
            <dd>{{ $serverInfo['uptime'] }}</dd>
        </div>
        <div class="ops-server-info__row">
            <dt>IP Address</dt>
            <dd>{{ $serverInfo['ip'] }}</dd>
        </div>
        <div class="ops-server-info__row">
            <dt>Kernel</dt>
            <dd>{{ $serverInfo['kernel'] }}</dd>
        </div>
    </dl>
    <div class="ops-server-info__health mt-2">
        <span class="text-muted small">System Health</span>
        <x-ops-dashboard.health-badge :status="$serverInfo['health']" :color="$serverInfo['health_color']" />
    </div>
</div>
