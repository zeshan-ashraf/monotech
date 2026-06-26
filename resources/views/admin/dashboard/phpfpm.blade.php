{{-- PHP-FPM status panel --}}
<section class="ops-card ops-panel h-100" aria-label="PHP-FPM status">
    <div class="ops-panel__header">
        <h5 class="ops-panel__title mb-0">
            <i class="fa fa-code text-primary me-2"></i>PHP-FPM Status
        </h5>
    </div>
    <div class="ops-panel__body">
        <div class="row align-items-center g-3">
            {{-- Donut worker chart --}}
            <div class="col-md-5 col-lg-4 text-center">
                <div id="ops-phpfpm-donut" class="ops-phpfpm-donut"></div>
                <p class="text-muted small mb-0 mt-1">{{ $phpFpm['total_workers'] }} Total Workers</p>
            </div>

            {{-- Worker legend --}}
            <div class="col-md-7 col-lg-3">
                <ul class="ops-legend list-unstyled mb-0">
                    <li><span class="ops-legend__dot bg-primary"></span>Busy <strong>{{ $phpFpm['busy'] }}</strong></li>
                    <li><span class="ops-legend__dot bg-success"></span>Idle <strong>{{ $phpFpm['idle'] }}</strong></li>
                    <li><span class="ops-legend__dot bg-warning"></span>Queue <strong>{{ $phpFpm['queue'] }}</strong></li>
                    <li><span class="ops-legend__dot bg-danger"></span>Max Children Hit <strong>{{ $phpFpm['max_children_hit'] }}</strong></li>
                </ul>
            </div>

            {{-- Request metrics with sparklines --}}
            <div class="col-lg-5">
                <div class="ops-mini-metrics">
                    <div class="ops-mini-metric">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Requests / Sec</span>
                            <strong>{{ $phpFpm['requests_per_sec'] }}</strong>
                        </div>
                        <div id="ops-phpfpm-requests" class="ops-mini-chart"></div>
                    </div>
                    <div class="ops-mini-metric">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Avg. Response Time</span>
                            <strong>{{ $phpFpm['avg_response_ms'] }} ms</strong>
                        </div>
                        <div id="ops-phpfpm-response" class="ops-mini-chart"></div>
                    </div>
                    <div class="ops-mini-metric mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Slow Requests</span>
                            <strong class="text-warning">{{ $phpFpm['slow_requests'] }}</strong>
                        </div>
                        <div id="ops-phpfpm-slow" class="ops-mini-chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
