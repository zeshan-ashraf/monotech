@props([
    'status',
    'color' => 'success',
])

<span {{ $attributes->merge(['class' => "ops-health-badge ops-health-badge--{$color}"]) }}>
    <span class="ops-health-badge__dot"></span>
    {{ $status }}
</span>
