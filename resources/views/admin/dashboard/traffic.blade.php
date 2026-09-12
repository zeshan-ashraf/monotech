{{-- API Traffic panel (live Redis metrics) --}}
<section class="ops-card ops-panel ops-traffic-panel mb-3" aria-label="API traffic" id="ops-traffic-panel">
    <div class="ops-panel__header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="ops-panel__title mb-0">
            <i class="fa fa-exchange text-primary me-2"></i>API Traffic
        </h5>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="ops-traffic-windows btn-group btn-group-sm" role="group" aria-label="Traffic time window">
                @foreach($traffic['windows'] as $window)
                    <button
                        type="button"
                        class="btn ops-traffic-window {{ (int) $traffic['window_minutes'] === (int) $window ? 'active' : '' }}"
                        data-minutes="{{ $window }}"
                    >
                        {{ $window }}m
                    </button>
                @endforeach
            </div>
            <span class="ops-traffic-live" id="ops-traffic-live" title="Live refresh every 5 seconds">
                <span class="ops-traffic-live__dot"></span>
                Live
            </span>
        </div>
    </div>

    <div class="ops-panel__body">
        <div class="row g-3 mb-3">
            @foreach($traffic['cards'] as $card)
                <div class="col-xl col-md-4 col-sm-6">
                    <x-ops-dashboard.traffic-stat
                        :label="$card['label']"
                        :value="$card['value']"
                        :color="$card['color']"
                        :metric-key="$card['key']"
                        :chart-id="$card['chart'] ? 'ops-traffic-spark-' . $card['key'] : null"
                    />
                </div>
            @endforeach
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-8">
                <div class="ops-traffic-chart-wrap">
                    <h6 class="ops-subtitle mb-2">
                        Incoming Requests
                        <span class="text-muted fw-normal">(<span id="ops-traffic-chart-window">{{ $traffic['window_minutes'] }}</span> min window)</span>
                    </h6>
                    <div id="ops-traffic-incoming-chart" class="ops-traffic-chart"></div>
                </div>
            </div>
            <div class="col-xl-4">
                <h6 class="ops-subtitle mb-2">Traffic by API</h6>
                <div class="ops-traffic-api-list" id="ops-traffic-api-list">
                    @foreach($traffic['api_rows'] as $row)
                        <div class="ops-traffic-api-row" data-api="{{ $row['key'] }}">
                            <div class="ops-traffic-api-row__meta d-flex justify-content-between mb-1">
                                <span class="ops-traffic-api-row__label">{{ $row['label'] }}</span>
                                <span class="ops-traffic-api-row__value" data-field="incoming">{{ number_format($row['incoming']) }}</span>
                            </div>
                            <div class="ops-traffic-api-row__track">
                                <div
                                    class="ops-traffic-api-row__bar bg-primary"
                                    data-field="bar"
                                    style="width: {{ $row['percent'] }}%"
                                ></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="ops-traffic-errors d-flex flex-wrap gap-2" id="ops-traffic-errors">
            @foreach($traffic['errors'] as $error)
                <span class="ops-traffic-error badge rounded-pill bg-{{ $error['color'] }} bg-opacity-10 text-{{ $error['color'] }}" data-error="{{ $error['key'] }}">
                    {{ $error['label'] }}: <strong data-field="value">{{ number_format($error['value']) }}</strong>
                </span>
            @endforeach
        </div>
    </div>
</section>
