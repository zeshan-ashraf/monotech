<aside class="api-docs-code-panel">
    @php
        $requestExample = $page['request_example'] ?? null;
        $responses = $page['responses'] ?? [];
        $firstEndpoint = $page['endpoints'][0] ?? null;
        if (!$requestExample && $firstEndpoint) {
            $requestExample = $firstEndpoint['request_example'] ?? null;
            $responses = $firstEndpoint['responses'] ?? [];
        }
        $endpointMethod = $page['method'] ?? ($firstEndpoint['method'] ?? 'POST');
        $endpointPath = $page['path'] ?? ($firstEndpoint['path'] ?? '');
        $endpointUrl = $endpointPath ? $baseUrl . $endpointPath : $baseUrl;
    @endphp

    @if($requestExample)
        <div class="api-docs-code-block">
            <div class="api-docs-code-header">
                <span>Example request</span>
                <button type="button" class="api-docs-copy-btn" data-copy-json="request-example">Copy</button>
            </div>
            <div class="api-docs-code-meta">{{ $endpointMethod }} {{ $endpointUrl }}</div>
            <pre><code id="request-example">{{ json_encode($requestExample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
        </div>
    @endif

    @foreach($responses as $response)
        <div class="api-docs-code-block api-docs-code-block--{{ $response['type'] }}">
            <div class="api-docs-code-header">
                <span>Example {{ strtolower($response['label']) }} response</span>
                <span class="api-docs-http-status api-docs-http-status--{{ $response['type'] }}">{{ $response['code'] }} {{ $response['label'] }}</span>
            </div>
            <pre><code>{{ json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
        </div>
    @endforeach

    @if(!empty($page['callback_sections']))
        @php $webhook = collect($page['callback_sections'])->firstWhere('type', 'success'); @endphp
        @if($webhook)
            <div class="api-docs-code-block">
                <div class="api-docs-code-header">
                    <span>Example callback (webhook)</span>
                </div>
                <pre><code>{{ json_encode($webhook['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
            </div>
        @endif
    @endif

    @if(!empty($page['endpoints']) && count($page['endpoints']) > 1)
        @foreach(array_slice($page['endpoints'], 1) as $endpoint)
            @if(!empty($endpoint['request_example']))
                <div class="api-docs-code-block">
                    <div class="api-docs-code-header">
                        <span>{{ $endpoint['title'] }} — request</span>
                    </div>
                    <div class="api-docs-code-meta">{{ $endpoint['method'] }} {{ $baseUrl }}{{ $endpoint['path'] }}</div>
                    <pre><code>{{ json_encode($endpoint['request_example'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                </div>
            @endif
            @foreach($endpoint['responses'] ?? [] as $response)
                <div class="api-docs-code-block api-docs-code-block--{{ $response['type'] }}">
                    <div class="api-docs-code-header">
                        <span>{{ $endpoint['title'] }} — {{ strtolower($response['label']) }}</span>
                        <span class="api-docs-http-status api-docs-http-status--{{ $response['type'] }}">{{ $response['code'] }}</span>
                    </div>
                    <pre><code>{{ json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                </div>
            @endforeach
        @endforeach
    @endif
</aside>
