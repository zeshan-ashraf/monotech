@props([
    'status',
])

@php
    $map = [
        'success' => ['class' => 'success', 'label' => 'Success'],
        'pending' => ['class' => 'warning', 'label' => 'Pending'],
        'failed' => ['class' => 'danger', 'label' => 'Failed'],
    ];
    $badge = $map[$status] ?? ['class' => 'secondary', 'label' => ucfirst($status)];
@endphp

<span {{ $attributes->merge(['class' => "badge rounded-pill ops-status-badge bg-{$badge['class']} bg-opacity-10 text-{$badge['class']}"]) }}>
    {{ $badge['label'] }}
</span>
