{{-- Application Runtime monitoring (live metrics) --}}
<section class="ops-section mb-3" aria-label="Application Runtime" id="ops-runtime-panel">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <h5 class="ops-panel__title mb-0">
            <i class="fa fa-heartbeat text-primary me-2"></i>Application Runtime
        </h5>
        <span class="ops-traffic-live" id="ops-runtime-live" title="Live refresh every 5 seconds">
            <span class="ops-traffic-live__dot"></span>
            Live
        </span>
    </div>

    {{-- Summary cards --}}
    <div class="row g-3 mb-3" id="ops-runtime-summary">
        @foreach($runtime['summary'] as $card)
            <div class="col-xl-3 col-md-6">
                <div
                    class="ops-card ops-metric-card ops-metric-card--{{ $card['color'] }}"
                    data-runtime-summary="{{ $card['key'] }}"
                >
                    <div class="ops-metric-card__header d-flex align-items-start justify-content-between">
                        <div class="ops-metric-card__icon-wrap bg-{{ $card['color'] }} bg-opacity-10 text-{{ $card['color'] }}">
                            <i class="fa {{ $card['icon'] }}"></i>
                        </div>
                        <x-ops-dashboard.health-badge
                            :status="$card['status_label']"
                            :color="$card['status_color']"
                            data-field="status"
                        />
                    </div>
                    <div class="ops-metric-card__body">
                        <p class="ops-metric-card__title mb-1">{{ $card['title'] }}</p>
                        <h3 class="ops-metric-card__value mb-1" data-field="value">{{ $card['value'] }}</h3>
                        <p class="ops-metric-card__subtitle mb-0" data-field="subtitle">{{ $card['subtitle'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- PHP-FPM & Scheduler details --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-6">
            <section class="ops-card ops-panel h-100" aria-label="PHP-FPM details">
                <div class="ops-panel__header">
                    <h5 class="ops-panel__title mb-0">
                        <i class="fa fa-code text-primary me-2"></i>PHP-FPM Details
                    </h5>
                </div>
                <div class="ops-panel__body" data-runtime-section="php_fpm">
                    <div class="row g-3">
                        @foreach([
                            ['key' => 'total_workers', 'label' => 'Total Workers'],
                            ['key' => 'busy_workers', 'label' => 'Busy Workers'],
                            ['key' => 'idle_workers', 'label' => 'Idle Workers'],
                            ['key' => 'listen_queue', 'label' => 'Listen Queue'],
                            ['key' => 'max_children_reached', 'label' => 'Max Children Reached'],
                            ['key' => 'slow_requests', 'label' => 'Slow Requests'],
                            ['key' => 'avg_response_ms', 'label' => 'Avg Response Time (ms)'],
                            ['key' => 'requests_per_second', 'label' => 'Requests / Sec'],
                        ] as $metric)
                            <div class="col-sm-6">
                                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                    <span class="text-muted small">{{ $metric['label'] }}</span>
                                    <strong data-field="{{ $metric['key'] }}">{{ $runtime['php_fpm'][$metric['key']] ?? '—' }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Worker Utilization</span>
                            <strong data-field="worker_utilization">{{ $runtime['php_fpm']['worker_utilization'] ?? 0 }}%</strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div
                                class="progress-bar bg-{{ $runtime['php_fpm']['status_color'] ?? 'primary' }}"
                                role="progressbar"
                                data-field="utilization_bar"
                                style="width: {{ min(100, (float) ($runtime['php_fpm']['worker_utilization'] ?? 0)) }}%"
                            ></div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-6">
            <section class="ops-card ops-panel h-100" aria-label="Scheduler details">
                <div class="ops-panel__header">
                    <h5 class="ops-panel__title mb-0">
                        <i class="fa fa-clock-o text-warning me-2"></i>Scheduler Details
                    </h5>
                </div>
                <div class="ops-panel__body" data-runtime-section="scheduler">
                    @foreach([
                        ['key' => 'status_label', 'label' => 'Scheduler Status'],
                        ['key' => 'last_tick', 'label' => 'Last Tick'],
                        ['key' => 'next_tick', 'label' => 'Next Tick'],
                        ['key' => 'scheduled_commands', 'label' => 'Scheduled Commands'],
                        ['key' => 'running_commands', 'label' => 'Running Commands'],
                        ['key' => 'failed_today', 'label' => 'Failed Commands Today'],
                        ['key' => 'avg_runtime', 'label' => 'Average Runtime'],
                        ['key' => 'longest_runtime', 'label' => 'Longest Runtime'],
                    ] as $metric)
                        <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                            <span class="text-muted small">{{ $metric['label'] }}</span>
                            <strong data-field="{{ $metric['key'] }}">{{ $runtime['scheduler'][$metric['key']] ?? '—' }}</strong>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    {{-- Queue details --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <section class="ops-card ops-panel" aria-label="Queue details">
                <div class="ops-panel__header">
                    <h5 class="ops-panel__title mb-0">
                        <i class="fa fa-tasks text-info me-2"></i>Queue Details
                    </h5>
                </div>
                <div class="ops-panel__body" data-runtime-section="queue">
                    <div class="row g-3">
                        @foreach([
                            ['key' => 'pending_jobs', 'label' => 'Pending Jobs'],
                            ['key' => 'processing_jobs', 'label' => 'Processing Jobs'],
                            ['key' => 'failed_jobs', 'label' => 'Failed Jobs'],
                            ['key' => 'retrying_jobs', 'label' => 'Retry Jobs'],
                            ['key' => 'avg_runtime', 'label' => 'Average Runtime'],
                            ['key' => 'longest_running_for', 'label' => 'Longest Running'],
                            ['key' => 'worker_count', 'label' => 'Worker Count'],
                            ['key' => 'status_label', 'label' => 'Queue Status'],
                        ] as $metric)
                            <div class="col-xl-3 col-md-4 col-sm-6">
                                <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                    <span class="text-muted small">{{ $metric['label'] }}</span>
                                    <strong data-field="{{ $metric['key'] }}">{{ $runtime['queue'][$metric['key']] ?? '—' }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- Stuck processes --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <section class="ops-card ops-panel" aria-label="Stuck processes">
                <div class="ops-panel__header d-flex align-items-center justify-content-between">
                    <h5 class="ops-panel__title mb-0">
                        <i class="fa fa-exclamation-triangle text-danger me-2"></i>Stuck Processes
                    </h5>
                    <span class="ops-badge ops-badge--warning" data-field="stuck_total">{{ $runtime['stuck_processes']['total'] ?? 0 }}</span>
                </div>
                <div class="ops-panel__body p-0">
                    <div class="ops-table-wrap">
                        <table class="ops-table table mb-0">
                            <thead>
                                <tr>
                                    <th>Process Type</th>
                                    <th>Name</th>
                                    <th>PID</th>
                                    <th>Started</th>
                                    <th>Running For</th>
                                    <th>Status</th>
                                    <th>Recommendation</th>
                                </tr>
                            </thead>
                            <tbody id="ops-runtime-stuck-table">
                                @forelse($runtime['stuck_processes']['processes'] ?? [] as $process)
                                    <tr>
                                        <td>{{ $process['type_label'] }}</td>
                                        <td>{{ $process['name'] }}</td>
                                        <td>{{ $process['pid'] ?? '—' }}</td>
                                        <td>{{ $process['started'] }}</td>
                                        <td>{{ $process['running_for'] }}</td>
                                        <td>
                                            <x-ops-dashboard.health-badge
                                                :status="$process['status_label']"
                                                :color="$process['status_color']"
                                            />
                                        </td>
                                        <td>{{ $process['recommendation'] }}</td>
                                    </tr>
                                @empty
                                    <tr data-empty-row="1">
                                        <td colspan="7" class="text-center text-muted py-4">No stuck processes detected</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- Operations recommendations --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <section class="ops-card ops-panel" aria-label="Operations recommendations">
                <div class="ops-panel__header">
                    <h5 class="ops-panel__title mb-0">
                        <i class="fa fa-lightbulb-o text-warning me-2"></i>Operations Recommendations
                    </h5>
                </div>
                <div class="ops-panel__body">
                    <div class="row g-3" id="ops-runtime-recommendations">
                        @foreach($runtime['recommendations'] as $recommendation)
                            <div class="col-xl-4 col-md-6">
                                <div class="ops-card h-100 p-3 border">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <x-ops-dashboard.health-badge
                                            :status="ucfirst($recommendation['severity'])"
                                            :color="$recommendation['severity_color']"
                                        />
                                        <strong>{{ $recommendation['title'] }}</strong>
                                    </div>
                                    <p class="text-muted small mb-2">{{ $recommendation['description'] }}</p>
                                    <p class="mb-0 small"><strong>Action:</strong> {{ $recommendation['action'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
