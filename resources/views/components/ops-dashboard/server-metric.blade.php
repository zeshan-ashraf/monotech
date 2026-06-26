@props([
    'title',
    'icon',
    'color' => 'primary',
    'value',
    'subtitle',
])

<div {{ $attributes->merge(['class' => "ops-server-metric ops-server-metric--{$color} h-100"]) }}>
    <div class="ops-server-metric__icon bg-{{ $color }} bg-opacity-10 text-{{ $color }}">
        <i class="fa {{ $icon }}"></i>
    </div>
    <div class="ops-server-metric__content">
        <p class="ops-server-metric__title mb-1">{{ $title }}</p>
        <h4 class="ops-server-metric__value mb-1">{{ $value }}</h4>
        <p class="ops-server-metric__subtitle mb-0">{{ $subtitle }}</p>
    </div>
</div>
