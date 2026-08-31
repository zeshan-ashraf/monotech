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

    <div class="hmac-banner">
        <div class="hmac-banner-icon">
            <i data-feather="lock"></i>
        </div>
        <div>
            <h3>Your API secret never leaves this browser</h3>
            <p>We sign the compact JSON body with HMAC-SHA256. The secret is the key — it is not appended to the body, and it is not sent to the server.</p>
        </div>
    </div>

    <div class="hmac-card">
        <div class="hmac-card-head">
            <strong>Generate v1 signature</strong>
            <span>Paste credentials and JSON, then copy the signature or curl from the panel on the right.</span>
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
                            placeholder="Private API secret"
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
                <div class="hmac-editor">
                    <div class="hmac-editor-bar">
                        <span>Request body · JSON</span>
                        <span class="hmac-hint" id="hmac-json-status">Paste your request JSON</span>
                    </div>
                    <textarea
                        id="hmac-body"
                        class="hmac-textarea"
                        spellcheck="false"
                        wrap="off"
                        rows="14"
                    >{{ $payinSample }}</textarea>
                </div>
            </div>

            <div class="hmac-actions">
                <button type="submit" class="hmac-generate-btn">
                    <i data-feather="cpu"></i>
                    Generate signature
                </button>
                <p class="hmac-formula">
                    <code>hash_hmac('sha256', $body, $apiSecret)</code>
                </p>
            </div>
        </form>
    </div>
</div>
