@php
    $sent = (int) ($callbackSent ?? 0) === 1;
    $reply = trim((string) ($callbackResponse ?? ''));
    $sentAt = \App\Support\PayinCallbackTracker::formatTimestamp($callbackSentAt ?? null);
    $responseAt = \App\Support\PayinCallbackTracker::formatTimestamp($callbackResponseAt ?? null);

    $tipParts = [
        'Sent: ' . ($sentAt ?: '-'),
        'Reply: ' . ($responseAt ?: '-'),
    ];
    if ($reply !== '') {
        $tipParts[] = $reply;
    }
    $tip = implode('<br>', array_map('e', $tipParts));
@endphp
<span class="status-badge-wrap">
    <span class="badge {{ $sent ? 'bg-success' : 'bg-secondary' }}"
          data-bs-toggle="tooltip"
          data-bs-html="true"
          title="{!! $tip !!}">
        {{ $sent ? 'Sent' : 'Not Sent' }}
    </span>
</span>
