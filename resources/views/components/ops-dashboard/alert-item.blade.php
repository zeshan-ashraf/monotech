@props([
    'severity' => 'info',
    'icon',
    'title',
    'description',
    'time',
])

<div {{ $attributes->merge(['class' => "ops-alert-item ops-alert-item--{$severity}"]) }}>
    <div class="ops-alert-item__icon-wrap bg-{{ $severity }} bg-opacity-10 text-{{ $severity }}">
        <i class="fa {{ $icon }}"></i>
    </div>
    <div class="ops-alert-item__content flex-grow-1">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <h6 class="ops-alert-item__title mb-0">{{ $title }}</h6>
            <span class="ops-alert-item__time">{{ $time }}</span>
        </div>
        <p class="ops-alert-item__desc mb-0">{{ $description }}</p>
    </div>
</div>
