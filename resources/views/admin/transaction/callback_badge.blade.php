@php
    $sentOut = ((int) ($callbackSent ?? 0) === 1) || !empty($callbackSentAt);
    $gotReply = !empty($callbackResponseAt);
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
    @if($sentOut && $gotReply)
        <i class="fas fa-check-double text-success font-medium-3"
           data-bs-toggle="tooltip"
           data-bs-html="true"
           title="{!! $tip !!}"
           aria-label="Callback sent and reply received"></i>
    @elseif($sentOut)
        <i class="fas fa-check text-success font-medium-3"
           data-bs-toggle="tooltip"
           data-bs-html="true"
           title="{!! $tip !!}"
           aria-label="Callback sent, waiting for reply"></i>
    @else
        <span class="text-muted"
              data-bs-toggle="tooltip"
              data-bs-html="true"
              title="{!! $tip !!}">Not Sent</span>
    @endif
</span>
