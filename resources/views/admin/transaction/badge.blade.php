@if($type == 'success')
    <span class="badge bg-success text-capitalize">{{$type}}</span>
@elseif($type == 'pending')
    <span class="badge bg-primary text-capitalize">{{$type}}</span>
@elseif($type == 'failed')
    <span class="status-badge-wrap">
        <span class="badge bg-danger text-capitalize"
              data-bs-toggle="tooltip"
              title="{{ $reason }}">
            {{ $type }}
        </span>
        @if(!empty($reason))
        <button type="button"
                class="copy-btn"
                data-text="{{ e($reason) }}"
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                title="Copy Error"
                aria-label="Copy Error">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
            </svg>
        </button>
        @endif
    </span>
@elseif($type == 'reverse')
    <span class="badge bg-secondary text-capitalize text-status">{{$type}}</span>
@elseif($type == 'blocked')
    <span class="badge bg-info text-capitalize text-status" data-bs-toggle="tooltip" data-bs-placement="top" title="{{$reason}}">{{$type}}</span>
@else 
    <span class="badge bg-warning text-capitalize text-status">{{$type}}</span>
@endif

