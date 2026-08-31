@php
    $payinSample = json_encode([
        'orderId' => 'jc_dep_15',
        'amount' => '100',
        'phone' => '03244361494',
        'email' => 'customer@mail.com',
        'client_email' => 'your-client@email.com',
        'payment_method' => 'jazzcash',
        'callback_url' => 'https://yourcallback.com/api/callback/order',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    $payoutSample = json_encode([
        'orderId' => 'jc_payout_01',
        'amount' => '100',
        'phone' => '03244361494',
        'email' => 'customer@mail.com',
        'client_email' => 'your-client@email.com',
        'payout_method' => 'jazzcash',
        'callback_url' => 'https://yourcallback.com/api/callback/order',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
@endphp

<div
    class="hmac-tool"
    id="hmac-tool"
    data-base-url="{{ $baseUrl }}"
    data-payin-path="/v1/payment-checkout"
    data-payout-path="/v1/payout/checkout"
>
    <script type="application/json" id="hmac-payin-sample">{!! $payinSample !!}</script>
    <script type="application/json" id="hmac-payout-sample">{!! $payoutSample !!}</script>
    <div class="api-docs-notice api-docs-notice--info">
        <div class="api-docs-notice-title">
            <i data-feather="lock"></i>
            <span>Secret never leaves this browser</span>
        </div>
        <ul class="api-docs-notice-list">
            <li>Sign the compact JSON body. The API secret is the HMAC <strong>key</strong> — do not append it to the body.</li>
            <li>Pretty JSON is fine here. We compact it, then sign and put that same string in the curl.</li>
        </ul>
    </div>

    <form class="hmac-form" id="hmac-form" autocomplete="off" novalidate>
        <div class="hmac-field">
            <span class="hmac-label">Endpoint</span>
            <div class="hmac-segmented" role="group" aria-label="Endpoint">
                <button type="button" class="hmac-segment is-active" data-endpoint="payin">Pay-in</button>
                <button type="button" class="hmac-segment" data-endpoint="payout">Payout</button>
            </div>
        </div>

        <div class="hmac-grid">
            <div class="hmac-field">
                <label class="hmac-label" for="hmac-api-key">API Key ID</label>
                <input
                    type="text"
                    id="hmac-api-key"
                    class="hmac-input"
                    name="api_key"
                    placeholder="X-API-Key-ID"
                    spellcheck="false"
                    autocomplete="off"
                >
            </div>
            <div class="hmac-field">
                <label class="hmac-label" for="hmac-api-secret">API Secret</label>
                <div class="hmac-secret-wrap">
                    <input
                        type="password"
                        id="hmac-api-secret"
                        class="hmac-input"
                        name="api_secret"
                        placeholder="Your private API secret"
                        spellcheck="false"
                        autocomplete="off"
                    >
                    <button type="button" class="hmac-secret-toggle" id="hmac-secret-toggle" title="Show secret" aria-label="Show secret">
                        <i data-feather="eye"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="hmac-field">
            <div class="hmac-label-row">
                <label class="hmac-label" for="hmac-body">Request body (JSON)</label>
                <span class="hmac-hint" id="hmac-json-status">Paste your request JSON</span>
            </div>
            <textarea
                id="hmac-body"
                class="hmac-textarea"
                spellcheck="false"
                rows="14"
            >{{ $payinSample }}</textarea>
        </div>

        <div class="hmac-actions">
            <button type="submit" class="hmac-generate-btn">
                <i data-feather="cpu"></i>
                Generate signature
            </button>
            <p class="hmac-formula">
                <code>hash_hmac('sha256', $compactJsonBody, $apiSecret)</code>
            </p>
        </div>
    </form>
</div>
