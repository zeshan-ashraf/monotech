@php
    $sent = (int) ($callbackSent ?? 0) === 1;
    $reply = trim((string) ($callbackResponse ?? ''));
@endphp
<span class="status-badge-wrap">
    <span class="badge {{ $sent ? 'bg-success' : 'bg-secondary' }}"
          @if($reply !== '') data-bs-toggle="tooltip" title="{{ e($reply) }}" @endif>
        {{ $sent ? 'Sent' : 'Not Sent' }}
    </span>
</span>
