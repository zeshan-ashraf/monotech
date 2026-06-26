@props([
    'title',
    'value',
    'subtitle',
    'icon',
    'color' => 'primary',
    'chartId',
])

<div class="ops-card ops-metric-card ops-metric-card--{{ $color }}">
    <div class="ops-metric-card__header d-flex align-items-start justify-content-between">
        <div class="ops-metric-card__icon-wrap bg-{{ $color }} bg-opacity-10 text-{{ $color }}">
            <i class="fa {{ $icon }}"></i>
        </div>
    </div>
    <div class="ops-metric-card__body">
        <p class="ops-metric-card__title mb-1">{{ $title }}</p>
        <h3 class="ops-metric-card__value mb-1">{{ $value }}</h3>
        <p class="ops-metric-card__subtitle mb-2">{{ $subtitle }}</p>
        <div id="{{ $chartId }}" class="ops-metric-card__chart"></div>
    </div>
</div>
