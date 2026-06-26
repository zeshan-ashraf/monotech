@props([
    'label',
    'value',
    'color' => 'primary',
    'chartId',
])

<div {{ $attributes->merge(['class' => "ops-payment-stat ops-payment-stat--{$color}"]) }}>
    <div class="ops-payment-stat__top d-flex align-items-start justify-content-between">
        <p class="ops-payment-stat__label mb-0">{{ $label }}</p>
        <span class="ops-payment-stat__dot bg-{{ $color }}"></span>
    </div>
    <h4 class="ops-payment-stat__value text-{{ $color }} mb-1">{{ is_numeric($value) ? number_format($value) : $value }}</h4>
    <div id="{{ $chartId }}" class="ops-payment-stat__chart"></div>
</div>
