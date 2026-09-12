@props([
    'gateway',
    'cards',
    'paymentStats',
])

<div class="ops-gateway-payments" data-gateway="{{ $gateway['key'] }}">
    <div class="ops-gateway-payments__header d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2">
            <span
                class="ops-gateway-payments__badge"
                style="--ops-gateway-color: {{ $gateway['brand_color'] }}"
            >
                <i class="fa {{ $gateway['icon'] }}"></i>
            </span>
            <h6 class="ops-gateway-payments__title mb-0">{{ $gateway['name'] }}</h6>
        </div>
        <div class="ops-gateway-payments__stats d-flex flex-wrap gap-3">
            <span class="ops-panel__footer-stat text-muted small mb-0">
                Avg. API Response:
                <strong class="text-body ops-gateway-avg" data-gateway="{{ $gateway['key'] }}">{{ $paymentStats['avg'] }}</strong>
            </span>
            <span class="ops-panel__footer-stat text-muted small mb-0">
                Max API Response:
                <strong class="text-danger ops-gateway-max" data-gateway="{{ $gateway['key'] }}">{{ $paymentStats['max'] }}</strong>
            </span>
        </div>
    </div>

    <div class="row g-3">
        @foreach($cards as $payment)
            <div class="col-sm-6 col-xl-3">
                <x-ops-dashboard.payment-stat
                    :label="$payment['label']"
                    :value="$payment['value']"
                    :color="$payment['color']"
                    :chart-id="'ops-payment-' . $gateway['key'] . '-' . $payment['key']"
                />
            </div>
        @endforeach
    </div>
</div>
