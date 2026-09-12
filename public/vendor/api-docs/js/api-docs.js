document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = btn.getAttribute('data-copy-target');
            var el = document.getElementById(targetId);
            if (!el) return;
            copyText(el.textContent.trim(), btn);
        });
    });

    document.querySelectorAll('[data-copy-json]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-copy-json');
            var el = document.getElementById(id);
            if (!el) return;
            copyText(el.textContent.trim(), btn);
        });
    });

    function copyText(text, btn) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                flashCopied(btn);
            });
            return;
        }

        var textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        flashCopied(btn);
    }

    function flashCopied(btn) {
        var original = btn.innerHTML;
        btn.textContent = 'Copied!';
        setTimeout(function () {
            btn.innerHTML = original;
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }, 1500);
    }

    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    initHmacGenerator();

    function initHmacGenerator() {
        var tool = document.getElementById('hmac-tool');
        if (!tool) {
            return;
        }

        var form = document.getElementById('hmac-form');
        var apiKeyInput = document.getElementById('hmac-api-key');
        var secretInput = document.getElementById('hmac-api-secret');
        var bodyInput = document.getElementById('hmac-body');
        var jsonStatus = document.getElementById('hmac-json-status');
        var secretToggle = document.getElementById('hmac-secret-toggle');
        var emptyState = document.getElementById('hmac-empty');
        var output = document.getElementById('hmac-output');
        var errorBlock = document.getElementById('hmac-error-block');
        var errorText = document.getElementById('hmac-error-text');
        var successBlocks = document.getElementById('hmac-success-blocks');
        var signatureEl = document.getElementById('hmac-signature');
        var signedBodyEl = document.getElementById('hmac-signed-body');
        var curlEl = document.getElementById('hmac-curl');
        var curlMeta = document.getElementById('hmac-curl-meta');
        var currentEndpoint = 'payin';

        document.querySelectorAll('.hmac-segment').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.hmac-segment').forEach(function (item) {
                    item.classList.remove('is-active');
                });
                btn.classList.add('is-active');
                currentEndpoint = btn.getAttribute('data-endpoint');
                var payinSample = readSample('hmac-payin-sample');
                var payoutSample = readSample('hmac-payout-sample');
                var current = bodyInput.value;
                var isDefault = current === payinSample || current === payoutSample || current.trim() === '';
                if (isDefault) {
                    bodyInput.value = currentEndpoint === 'payout' ? payoutSample : payinSample;
                    validateJsonPreview();
                }
            });
        });

        secretToggle.addEventListener('click', function () {
            var showing = secretInput.type === 'text';
            secretInput.type = showing ? 'password' : 'text';
            secretToggle.setAttribute('title', showing ? 'Show secret' : 'Hide secret');
            secretToggle.setAttribute('aria-label', showing ? 'Show secret' : 'Hide secret');
            secretToggle.innerHTML = showing ? '<i data-feather="eye"></i>' : '<i data-feather="eye-off"></i>';
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });

        bodyInput.addEventListener('input', validateJsonPreview);

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            generate();
        });

        validateJsonPreview();

        function readSample(id) {
            var el = document.getElementById(id);
            return el ? el.textContent.trim() : '';
        }

        function compactBody() {
            var raw = bodyInput.value.trim();
            if (!raw) {
                throw new Error('Request body is required.');
            }

            try {
                return JSON.stringify(JSON.parse(raw));
            } catch (e) {
                throw new Error('Request body is not valid JSON. Fix the JSON, then generate again.');
            }
        }

        function validateJsonPreview() {
            var raw = bodyInput.value.trim();
            if (!raw) {
                jsonStatus.textContent = 'Paste your request JSON';
                jsonStatus.className = 'hmac-hint';
                return;
            }

            try {
                JSON.parse(raw);
                jsonStatus.textContent = 'Valid JSON — will be compacted before signing';
                jsonStatus.className = 'hmac-hint is-ok';
            } catch (e) {
                jsonStatus.textContent = 'Invalid JSON';
                jsonStatus.className = 'hmac-hint is-bad';
            }
        }

        function endpointPath() {
            return currentEndpoint === 'payout'
                ? tool.getAttribute('data-payout-path')
                : tool.getAttribute('data-payin-path');
        }

        function endpointUrl() {
            return String(tool.getAttribute('data-base-url') || '').replace(/\/$/, '') + endpointPath();
        }

        function toHex(buffer) {
            return Array.from(new Uint8Array(buffer))
                .map(function (byte) {
                    return byte.toString(16).padStart(2, '0');
                })
                .join('');
        }

        function hmacSha256Hex(message, secret) {
            var encoder = new TextEncoder();
            return crypto.subtle
                .importKey('raw', encoder.encode(secret), { name: 'HMAC', hash: 'SHA-256' }, false, ['sign'])
                .then(function (key) {
                    return crypto.subtle.sign('HMAC', key, encoder.encode(message));
                })
                .then(toHex);
        }

        function buildCurl(url, apiKey, signature, body) {
            return [
                "curl --location '" + url + "' \\",
                "  --header 'Content-Type: application/json' \\",
                "  --header 'X-API-Key-ID: " + apiKey + "' \\",
                "  --header 'X-HMAC-Signature: " + signature + "' \\",
                "  --data-raw '" + body.replace(/'/g, "'\\''") + "'"
            ].join('\n');
        }

        function showError(message) {
            emptyState.classList.add('is-hidden');
            output.classList.remove('is-hidden');
            errorBlock.classList.remove('is-hidden');
            successBlocks.classList.add('is-hidden');
            errorText.textContent = message;
        }

        function showSuccess(signature, body, curl, url) {
            emptyState.classList.add('is-hidden');
            output.classList.remove('is-hidden');
            errorBlock.classList.add('is-hidden');
            successBlocks.classList.remove('is-hidden');
            signatureEl.textContent = signature;
            signedBodyEl.textContent = body;
            curlEl.textContent = curl;
            curlMeta.textContent = 'POST ' + url;
        }

        function generate() {
            var apiKey = apiKeyInput.value.trim();
            var secret = secretInput.value;
            var body;

            if (!apiKey) {
                showError('API Key ID is required for the curl header X-API-Key-ID.');
                return;
            }

            if (!secret) {
                showError('API Secret is required. It is used only in this browser as the HMAC key.');
                return;
            }

            try {
                body = compactBody();
            } catch (e) {
                showError(e.message);
                return;
            }

            hmacSha256Hex(body, secret)
                .then(function (signature) {
                    showSuccess(signature, body, buildCurl(endpointUrl(), apiKey, signature, body), endpointUrl());
                })
                .catch(function () {
                    showError('Could not generate HMAC in this browser. Use HTTPS or a modern browser.');
                });
        }
    }
});
