@props([
    'label',
    'value',
    'color' => 'primary',
    'metricKey',
    'chartId' => null,
])

<div {{ $attributes->merge(['class' => "ops-payment-stat ops-traffic-stat ops-traffic-stat--{$color}"]) }} data-metric="{{ $metricKey }}">
    <div class="ops-payment-stat__top d-flex align-items-start justify-content-between">
        <p class="ops-payment-stat__label mb-0">{{ $label }}</p>
        <span class="ops-payment-stat__dot bg-{{ $color }}"></span>
    </div>
    <h4 class="ops-payment-stat__value text-{{ $color }} mb-1" data-field="value">
        {{ is_numeric($value) ? number_format($value) : $value }}
    </h4>
    @if($chartId)
        <div id="{{ $chartId }}" class="ops-payment-stat__chart"></div>
    @endif
</div>
