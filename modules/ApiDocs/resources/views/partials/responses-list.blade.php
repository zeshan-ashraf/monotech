<h2 class="api-docs-section-title">Responses</h2>
<div class="api-docs-responses-summary">
    @foreach($responses as $response)
        <div class="api-docs-response-row">
            <span class="api-docs-status api-docs-status--{{ $response['type'] }}">{{ $response['code'] }} {{ $response['label'] }}</span>
            <span>{{ $response['description'] }}</span>
        </div>
    @endforeach
</div>
