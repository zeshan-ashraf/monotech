<div class="api-docs-endpoint-bar">
    <span class="api-docs-method api-docs-method--{{ strtolower($method) }}">{{ $method }}</span>
    <code class="api-docs-endpoint-url" id="api-docs-endpoint-{{ md5($url) }}">{{ $url }}</code>
    <button type="button" class="api-docs-copy-btn" data-copy-target="api-docs-endpoint-{{ md5($url) }}" title="Copy URL">
        <i data-feather="copy"></i>
    </button>
</div>
