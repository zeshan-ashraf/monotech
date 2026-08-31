<div class="hmac-results" id="hmac-results">
    <div class="hmac-empty" id="hmac-empty">
        <i data-feather="key"></i>
        <h3>Signature output</h3>
        <p>Paste your API key, secret, and JSON, then generate. The signature and curl appear here.</p>
    </div>

    <div class="hmac-output is-hidden" id="hmac-output">
        <div class="hmac-error api-docs-code-block api-docs-code-block--error is-hidden" id="hmac-error-block">
            <div class="api-docs-code-header">
                <span>Cannot generate</span>
            </div>
            <pre><code id="hmac-error-text"></code></pre>
        </div>

        <div class="hmac-success-blocks" id="hmac-success-blocks">
            <div class="api-docs-code-block hmac-signature-block">
                <div class="api-docs-code-header">
                    <span>X-HMAC-Signature</span>
                    <button type="button" class="api-docs-copy-btn" data-copy-target="hmac-signature">Copy</button>
                </div>
                <pre><code id="hmac-signature"></code></pre>
            </div>

            <div class="api-docs-code-block">
                <div class="api-docs-code-header">
                    <span>Signed body (compact JSON)</span>
                    <button type="button" class="api-docs-copy-btn" data-copy-target="hmac-signed-body">Copy</button>
                </div>
                <div class="api-docs-code-meta">This exact string is signed and sent as the POST body</div>
                <pre><code id="hmac-signed-body"></code></pre>
            </div>

            <div class="api-docs-code-block">
                <div class="api-docs-code-header">
                    <span>cURL</span>
                    <button type="button" class="api-docs-copy-btn" data-copy-target="hmac-curl">Copy</button>
                </div>
                <div class="api-docs-code-meta" id="hmac-curl-meta"></div>
                <pre><code id="hmac-curl"></code></pre>
            </div>
        </div>
    </div>
</div>
