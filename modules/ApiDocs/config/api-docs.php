<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand & URLs — edit per project (Nova Connect / Khushi / Mono)
    |--------------------------------------------------------------------------
    */
    'brand' => [
        'name' => env('API_DOCS_BRAND_NAME', 'Monotech'),
        'logo' => env('API_DOCS_LOGO', 'favicon.ico'),
        'support_email' => env('API_DOCS_SUPPORT_EMAIL', 'info@monotech.pk'),
        'api_version' => env('API_DOCS_API_VERSION', 'v1'),
        'server_ip' => env('API_DOCS_SERVER_IP', ''),
    ],

    'base_url' => env('API_DOCS_BASE_URL', 'https://monotech.pk/api'),

    /*
    |--------------------------------------------------------------------------
    | Docs navigation (design sidebar — do not change keys without updating routes)
    |--------------------------------------------------------------------------
    */
    'menu' => [
        [
            'id' => 'get-started',
            'label' => 'Get Started',
            'icon' => 'rocket',
        ],
        [
            'id' => 'payment-checkout',
            'label' => 'Payment Checkout',
            'icon' => 'credit-card',
        ],
        [
            'id' => 'payment-payout',
            'label' => 'Payment Payout',
            'icon' => 'send',
        ],
        [
            'id' => 'status-check',
            'label' => 'Status Check',
            'icon' => 'shield',
        ],
        [
            'id' => 'dashboard-data',
            'label' => 'Dashboard Data',
            'icon' => 'bar-chart-2',
        ],
        [
            'id' => 'callbacks',
            'label' => 'Callbacks',
            'icon' => 'git-branch',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Page content — sourced from Monotech API Documentation.docx (14 June 2026)
    | plus codebase for Status Check / Dashboard / Callbacks gaps
    |--------------------------------------------------------------------------
    */
    'pages' => [

        'get-started' => [
            'title' => 'Get Started',
            'show_endpoint' => false,
            'description' => 'Welcome to the :brand API documentation. Use these endpoints to integrate JazzCash and Easypaisa payment collection (pay-in) and payout flows into your application. Last updated 14 June 2026.',
            'notices' => [
                [
                    'title' => 'Important — read before integrating',
                    'type' => 'mandatory',
                    'items' => [
                        'These are <strong>production APIs</strong>. If you need a real-time testing number, please contact us.',
                        'Our server IP: <code>:server_ip</code> — whitelist this IP on your side if required for callbacks.',
                        'For <strong>payouts</strong>, you must provide a list of your server IPs so we can whitelist them before payout access is enabled.',
                    ],
                ],
            ],
            'sections' => [
                [
                    'heading' => 'Authentication',
                    'body' => 'Each request must include a valid <code>client_email</code> provided by the :brand administrator. Your account must have API access enabled for the selected payment or payout method.<br><br><strong>v1 APIs (HMAC):</strong> Contact us to get your API Key ID and secret. Send <code>X-API-Key-ID</code> and <code>X-HMAC-Signature</code> headers with every v1 request. Signature string format: <code>request body + api_secret</code>, then HMAC-SHA256.',
                ],
                [
                    'heading' => 'Base URL',
                    'body' => 'All API requests are sent to <code>:base_url</code>',
                ],
                [
                    'heading' => 'Request format',
                    'body' => 'Send parameters as <code>application/json</code> via <code>POST</code> (or <code>GET</code> where documented). Use lowercase values for <code>payment_method</code> and <code>payout_method</code> (<code>jazzcash</code>, <code>easypaisa</code>).',
                ],
                [
                    'heading' => 'HMAC signature generation (client-side)',
                    'body' => 'Contact us for <code>api_key</code> and <code>secret</code>. Signature string format: <code>(request body + api_secret)</code>, then apply HMAC-SHA256.<br><br><strong>Example PHP:</strong><pre style="background:#0f172a;color:#e2e8f0;padding:1rem;border-radius:8px;overflow:auto;font-size:0.82rem;"><code>$body = json_encode([/* your request body */]);
$dataToSign = $body;
$signature = hash_hmac(\'sha256\', $dataToSign, $apiSecret);
// Send headers: X-API-Key-ID, X-HMAC-Signature, Content-Type: application/json</code></pre>
<strong>Example request:</strong><pre style="background:#0f172a;color:#e2e8f0;padding:1rem;border-radius:8px;overflow:auto;font-size:0.82rem;"><code>POST /api/v1/payment-checkout HTTP/1.1
Host: monotech.pk
Content-Type: application/json
X-API-Key-ID: test_123
X-HMAC-Signature: aefb98a... [64 hex chars]

{
  "orderId": "update-test-01",
  "amount": "5",
  "phone": "03244361494",
  "email": "270785@gmail.com",
  "client_email": "abc@gmail.com",
  "payment_method": "easypaisa",
  "callback_url": "https://xyz.com/api/notify/order"
}</code></pre>',
                ],
                [
                    'heading' => 'Callbacks',
                    'body' => 'Provide an <code>https://</code> callback URL when initiating pay-in or payout. :brand will POST the transaction result to your URL when processing completes. See the Callbacks section for payload formats.',
                ],
                [
                    'heading' => 'Amount limits',
                    'body' => '<strong>JazzCash:</strong> maximum 50,000 per transaction.<br><strong>Easypaisa:</strong> limits are configured per merchant account (min/max). Contact :brand if you need your limits adjusted.',
                ],
                [
                    'heading' => 'Support',
                    'body' => 'Need help? Contact <a href="mailto::support_email">:support_email</a>. For v1 API key and secret, contact us.',
                ],
            ],
        ],

        'payment-checkout' => [
            'title' => 'Payment Checkout (Pay In)',
            'show_endpoint' => false,
            'description' => 'Initiate a payment checkout (pay-in) process. :brand provides both <strong>v1 HMAC</strong> and <strong>non-HMAC</strong> endpoints. All body parameters below must be sent as JSON.',
            'endpoints' => [
                [
                    'title' => 'Payment Checkout v1 (HMAC)',
                    'method' => 'POST',
                    'path' => '/v1/payment-checkout',
                    'description' => 'Uses HMAC (Hash-based Message Authentication Code) for request validation. Each client is issued a public API Key ID and a secret. Requests must include a signature based on the request contents and secret.',
                    'headers' => [
                        ['name' => 'X-API-Key-ID', 'type' => 'string', 'description' => 'Your public API key ID (used to identify your client).'],
                        ['name' => 'X-HMAC-Signature', 'type' => 'string', 'description' => 'HMAC SHA256 signature generated using your private secret key.'],
                        ['name' => 'Content-Type', 'type' => 'string', 'description' => 'Must be <code>application/json</code>.'],
                    ],
                    'parameters' => [
                        ['name' => 'orderId', 'type' => 'string', 'required' => true, 'description' => 'Unique identifier for the order.', 'example' => 'ORD123456'],
                        ['name' => 'amount', 'type' => 'string', 'required' => true, 'description' => 'Total amount for the payment.', 'example' => '1000'],
                        ['name' => 'phone', 'type' => 'string', 'required' => true, 'description' => 'Customer phone number.', 'example' => '03001234567'],
                        ['name' => 'email', 'type' => 'string', 'required' => true, 'description' => 'Customer email address.', 'example' => 'customer@mail.com'],
                        ['name' => 'client_email', 'type' => 'string', 'required' => true, 'description' => 'Merchant email provided by :brand administrator.', 'example' => 'client@mail.com'],
                        ['name' => 'payment_method', 'type' => 'string', 'required' => true, 'description' => 'Payment method: <code>easypaisa</code> or <code>jazzcash</code> (lowercase).', 'example' => 'jazzcash'],
                        ['name' => 'callback_url', 'type' => 'string', 'required' => true, 'description' => 'URL where :brand sends payment status after processing.', 'example' => 'https://yourcallback.com'],
                    ],
                    'request_example' => [
                        'orderId' => 'ORD123456',
                        'amount' => '1000',
                        'phone' => '03001234567',
                        'email' => 'customer@mail.com',
                        'client_email' => 'client@mail.com',
                        'payment_method' => 'jazzcash',
                        'callback_url' => 'https://yourcallback.com',
                    ],
                    'responses' => [
                        [
                            'code' => 200,
                            'label' => 'Success',
                            'type' => 'success',
                            'description' => 'Payment checkout initiated successfully.',
                            'body' => [
                                'status' => 'success',
                                'message' => 'Payment checkout initiated',
                                'reference_id' => 'TXN-99887766',
                            ],
                        ],
                        [
                            'code' => 401,
                            'label' => 'Auth Error',
                            'type' => 'error',
                            'description' => 'Missing/invalid headers, expired request, or replay (nonce already used).',
                            'body' => [
                                'message' => 'Missing authentication headers',
                            ],
                        ],
                        [
                            'code' => 400,
                            'label' => 'Error',
                            'type' => 'error',
                            'description' => 'Missing or malformed JSON body.',
                            'body' => [
                                'message' => 'Invalid input',
                            ],
                        ],
                    ],
                    'error_codes' => [
                        ['code' => '401', 'description' => 'Missing authentication headers — one or more required headers missing.'],
                        ['code' => '401', 'description' => 'Invalid API key ID — not found or inactive.'],
                        ['code' => '401', 'description' => 'Invalid signature — HMAC does not match expected.'],
                        ['code' => '401', 'description' => 'Request expired — timestamp outside 5-minute window.'],
                        ['code' => '401', 'description' => 'Nonce already used — replay attempt detected.'],
                        ['code' => '400', 'description' => 'Invalid input — missing or malformed JSON body.'],
                    ],
                    'notes' => [
                        'Contact :brand to get your API Key ID and secret for v1 APIs.',
                        'Ensure <code>payment_method</code> is either <code>easypaisa</code> or <code>jazzcash</code> (lowercase).',
                        'All body parameters are required to complete the payment checkout process.',
                    ],
                ],
                [
                    'title' => 'Payment Checkout (Non-HMAC)',
                    'method' => 'POST',
                    'path' => '/payin/checkout',
                    'description' => 'Initiate a payment checkout process without HMAC headers. Requires the following parameters in the request body.',
                    'parameters' => [
                        ['name' => 'orderId', 'type' => 'string', 'required' => true, 'description' => 'Unique identifier for the order.', 'example' => 'ORD123456'],
                        ['name' => 'amount', 'type' => 'string', 'required' => true, 'description' => 'Total amount for the payment.', 'example' => '1000'],
                        ['name' => 'phone', 'type' => 'string', 'required' => true, 'description' => 'Customer phone number.', 'example' => '03001234567'],
                        ['name' => 'email', 'type' => 'string', 'required' => true, 'description' => 'Customer email address.', 'example' => 'customer@mail.com'],
                        ['name' => 'client_email', 'type' => 'string', 'required' => true, 'description' => 'Merchant email provided by :brand administrator.', 'example' => 'client@mail.com'],
                        ['name' => 'payment_method', 'type' => 'string', 'required' => true, 'description' => 'Payment method: <code>easypaisa</code> or <code>jazzcash</code> (lowercase).', 'example' => 'jazzcash'],
                        ['name' => 'callback_url', 'type' => 'string', 'required' => true, 'description' => 'URL where :brand sends payment status after processing.', 'example' => 'https://yourcallback.com'],
                    ],
                    'request_example' => [
                        'orderId' => 'ORD123456',
                        'amount' => '1000',
                        'phone' => '03001234567',
                        'email' => 'customer@mail.com',
                        'client_email' => 'client@mail.com',
                        'payment_method' => 'jazzcash',
                        'callback_url' => 'https://yourcallback.com',
                    ],
                    'responses' => [
                        [
                            'code' => 200,
                            'label' => 'Success',
                            'type' => 'success',
                            'description' => 'Payment checkout initiated successfully.',
                            'body' => [
                                'success' => true,
                                'message' => 'Payment checkout initiated successfully.',
                                'transaction_id' => 'T2024......',
                            ],
                        ],
                        [
                            'code' => 400,
                            'label' => 'Error',
                            'type' => 'error',
                            'description' => 'Missing or invalid parameters.',
                            'body' => [
                                'status' => 'error',
                                'message' => 'Your payout cannot be processed due to There was a problem with your request. Please recheck the parameters/format and try again. , please try again.',
                                'error_code' => '400',
                            ],
                        ],
                    ],
                    'error_codes' => [
                        ['code' => '400', 'description' => 'Missing or invalid parameters in the request.'],
                        ['code' => '500', 'description' => 'Internal server error while processing the request.'],
                    ],
                    'notes' => [
                        'Ensure <code>payment_method</code> is either <code>easypaisa</code> or <code>jazzcash</code>.',
                        'All parameters are required to complete the payment checkout process.',
                        'Use proper validation before sending the request to avoid errors.',
                    ],
                ],
            ],
        ],

        'payment-payout' => [
            'title' => 'Payment Payout',
            'show_endpoint' => false,
            'description' => 'Initiate a payout transaction to the specified recipient. :brand provides both <strong>v1 HMAC</strong> and <strong>non-HMAC</strong> endpoints. Payout access requires your server IPs to be whitelisted.',
            'endpoints' => [
                [
                    'title' => 'Payment Payout v1 (HMAC)',
                    'method' => 'POST',
                    'path' => '/v1/payout/checkout',
                    'description' => 'Initiate a payout using HMAC authentication. Each client is issued a public API Key ID and a secret. Requests must include a signature based on the request contents and secret. Content-Type: <code>application/json</code>.',
                    'headers' => [
                        ['name' => 'X-API-Key-ID', 'type' => 'string', 'description' => 'Your public API key ID (used to identify your client).'],
                        ['name' => 'X-HMAC-Signature', 'type' => 'string', 'description' => 'HMAC SHA256 signature generated using your private secret key.'],
                        ['name' => 'Content-Type', 'type' => 'string', 'description' => 'Must be <code>application/json</code>.'],
                    ],
                    'parameters' => [
                        ['name' => 'orderId', 'type' => 'string', 'required' => true, 'description' => 'Unique identifier for the order.', 'example' => 'ORD123456'],
                        ['name' => 'amount', 'type' => 'string', 'required' => true, 'description' => 'Amount to be paid out to the client.', 'example' => '5000.00'],
                        ['name' => 'phone', 'type' => 'string', 'required' => true, 'description' => 'Recipient phone number for the payout.', 'example' => '923001234567'],
                        ['name' => 'email', 'type' => 'string', 'required' => true, 'description' => 'Customer email address.', 'example' => 'customer@mail.com'],
                        ['name' => 'client_email', 'type' => 'string', 'required' => true, 'description' => 'Merchant email provided by :brand administrator.', 'example' => 'client@mail.com'],
                        ['name' => 'payout_method', 'type' => 'string', 'required' => true, 'description' => 'Payout method: <code>easypaisa</code> or <code>jazzcash</code> (lowercase).', 'example' => 'jazzcash'],
                        ['name' => 'callback_url', 'type' => 'string', 'required' => true, 'description' => 'URL where :brand sends payout status after processing.', 'example' => 'https://yourcallback.com'],
                    ],
                    'request_example' => [
                        'orderId' => 'your order id number',
                        'amount' => '5000',
                        'phone' => '923001234567',
                        'email' => 'customer@mail.com',
                        'client_email' => 'client@mail.com',
                        'payout_method' => 'jazzcash',
                        'callback_url' => 'your callback url',
                    ],
                    'responses' => [
                        [
                            'code' => 200,
                            'label' => 'Success',
                            'type' => 'success',
                            'description' => 'Payout processed successfully.',
                            'body' => [
                                'success' => true,
                                'message' => 'Payout processed successfully.',
                                'transaction_id' => '202606121781245785591531',
                            ],
                        ],
                        [
                            'code' => 200,
                            'label' => 'Pending',
                            'type' => 'success',
                            'description' => 'Payout pending confirmation from Easypaisa.',
                            'body' => [
                                'status' => 'pending',
                                'message' => 'Payout is pending confirmation from Easypaisa.',
                                'transaction_id' => 'ABC123456789',
                            ],
                        ],
                        [
                            'code' => 400,
                            'label' => 'Error',
                            'type' => 'error',
                            'description' => 'Missing or invalid parameters.',
                            'body' => [
                                'status' => 'error',
                                'message' => 'Missing or invalid parameters.',
                                'error_code' => '400',
                            ],
                        ],
                        [
                            'code' => 422,
                            'label' => 'Validation Error',
                            'type' => 'error',
                            'description' => 'Request validation failed.',
                            'body' => [
                                'errors' => [
                                    'phone' => ['The phone field is required.'],
                                    'amount' => ['The amount field is required.'],
                                ],
                            ],
                        ],
                        [
                            'code' => 401,
                            'label' => 'Auth Error',
                            'type' => 'error',
                            'description' => 'Missing/invalid HMAC headers.',
                            'body' => [
                                'message' => 'Missing authentication headers',
                            ],
                        ],
                        [
                            'code' => 403,
                            'label' => 'Forbidden',
                            'type' => 'error',
                            'description' => 'Client IP is not whitelisted for payouts.',
                            'body' => [
                                'error' => 'Unauthorized IP',
                            ],
                        ],
                    ],
                    'error_codes' => [
                        ['code' => '401', 'description' => 'Missing authentication headers — one or more required headers missing.'],
                        ['code' => '401', 'description' => 'Invalid API key ID — not found or inactive.'],
                        ['code' => '401', 'description' => 'Invalid signature — HMAC does not match expected.'],
                        ['code' => '400', 'description' => 'Invalid input — missing or malformed JSON body / missing or invalid parameters.'],
                        ['code' => '422', 'description' => 'Validation failed — see errors object for details.'],
                        ['code' => '403', 'description' => 'Unauthorized IP — client IP not whitelisted.'],
                        ['code' => '500', 'description' => 'Internal server error while processing the request.'],
                    ],
                    'notes' => [
                        'Ensure <code>payout_method</code> is either <code>easypaisa</code> or <code>jazzcash</code>.',
                        'All parameters are required to complete the payout transaction.',
                        'Double-check the phone number and email before submitting the request.',
                        'Your server IP must be whitelisted before payout access is enabled.',
                    ],
                ],
                [
                    'title' => 'Payment Payout (Non-HMAC)',
                    'method' => 'POST',
                    'path' => '/payout/checkout',
                    'description' => 'Initiate a payout transaction without HMAC headers. Your server IP must still be whitelisted.',
                    'parameters' => [
                        ['name' => 'orderId', 'type' => 'string', 'required' => true, 'description' => 'Unique identifier for the order.', 'example' => 'ORD123456'],
                        ['name' => 'amount', 'type' => 'string', 'required' => true, 'description' => 'Amount to be paid out to the client.', 'example' => '5000.00'],
                        ['name' => 'phone', 'type' => 'string', 'required' => true, 'description' => 'Recipient phone number for the payout.', 'example' => '923001234567'],
                        ['name' => 'email', 'type' => 'string', 'required' => true, 'description' => 'Customer email address.', 'example' => 'customer@mail.com'],
                        ['name' => 'client_email', 'type' => 'string', 'required' => true, 'description' => 'Merchant email provided by :brand administrator.', 'example' => 'client@mail.com'],
                        ['name' => 'payout_method', 'type' => 'string', 'required' => true, 'description' => 'Payout method: <code>easypaisa</code> or <code>jazzcash</code> (lowercase).', 'example' => 'jazzcash'],
                        ['name' => 'callback_url', 'type' => 'string', 'required' => true, 'description' => 'URL where :brand sends payout status after processing.', 'example' => 'https://yourcallback.com'],
                    ],
                    'request_example' => [
                        'orderId' => 'your order id number',
                        'amount' => '5000',
                        'phone' => '923001234567',
                        'email' => 'customer@mail.com',
                        'client_email' => 'client@mail.com',
                        'payout_method' => 'jazzcash',
                        'callback_url' => 'your callback url',
                    ],
                    'responses' => [
                        [
                            'code' => 200,
                            'label' => 'Success',
                            'type' => 'success',
                            'description' => 'Payout initiated successfully.',
                            'body' => [
                                'status' => 'success',
                                'message' => 'Payout initiated successfully.',
                                'transaction_id' => 'T2024.....',
                            ],
                        ],
                        [
                            'code' => 400,
                            'label' => 'Error',
                            'type' => 'error',
                            'description' => 'Missing or invalid parameters.',
                            'body' => [
                                'status' => 'error',
                                'message' => 'Missing or invalid parameters.',
                                'error_code' => '400',
                            ],
                        ],
                    ],
                    'error_codes' => [
                        ['code' => '400', 'description' => 'Missing or invalid parameters in the request.'],
                        ['code' => '500', 'description' => 'Internal server error while processing the request.'],
                    ],
                    'notes' => [
                        'Ensure <code>payout_method</code> is either <code>easypaisa</code> or <code>jazzcash</code>.',
                        'All parameters are required to complete the payout transaction.',
                        'Double-check the phone number and email before submitting the request to ensure the payout goes to the correct recipient.',
                    ],
                ],
            ],
        ],

        'status-check' => [
            'title' => 'Status Check',
            'show_endpoint' => false,
            'description' => 'Check the status of a pay-in or payout transaction by order ID.',
            'endpoints' => [
                [
                    'title' => 'Pay-in Status Check',
                    'method' => 'POST',
                    'path' => '/payin-status-check',
                    'description' => 'Returns pay-in transaction details for the given order ID.',
                    'parameters' => [
                        ['name' => 'orderId', 'type' => 'string', 'required' => true, 'description' => 'The order ID used when initiating the pay-in transaction.', 'example' => 'DPD.....'],
                    ],
                    'request_example' => [
                        'orderId' => 'DPD.....',
                    ],
                    'responses' => [
                        [
                            'code' => 200,
                            'label' => 'Success',
                            'type' => 'success',
                            'description' => 'Returns the transaction record (prefers successful status if multiple exist).',
                            'body' => [
                                'order' => [
                                    'id' => 7015999,
                                    'user_id' => 28,
                                    'phone' => '03012005018',
                                    'orderId' => '120080670_20251001205906',
                                    'amount' => 1000,
                                    'txn_ref_no' => 'T20251001175907a8c74',
                                    'transactionId' => '40715150268',
                                    'txn_type' => 'easypaisa',
                                    'pp_code' => '0000',
                                    'pp_message' => 'SUCCESS',
                                    'status' => 'success',
                                    'src' => 'Nova',
                                    'url' => 'https://callback.example.com/callback.aspx',
                                    'created_at' => '2025-10-01T12:59:07.000000Z',
                                    'updated_at' => '2025-10-01T12:59:22.000000Z',
                                    'retry_count' => 0,
                                    'cron_failure_reason' => null,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Payout Status Check',
                    'method' => 'POST',
                    'path' => '/payout-status-check',
                    'description' => 'Returns payout transaction details for the given order ID.',
                    'parameters' => [
                        ['name' => 'orderId', 'type' => 'string', 'required' => true, 'description' => 'The order ID used when initiating the payout transaction.', 'example' => 'DPD.....'],
                    ],
                    'request_example' => [
                        'orderId' => 'DPD.....',
                    ],
                    'responses' => [
                        [
                            'code' => 200,
                            'label' => 'Success',
                            'type' => 'success',
                            'description' => 'Returns the payout record (prefers successful status if multiple exist).',
                            'body' => [
                                'order' => [
                                    'orderId' => 'DPD.....',
                                    'amount' => '5000',
                                    'status' => 'success',
                                    'transaction_reference' => 'T2024...',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],

        'dashboard-data' => [
            'title' => 'Dashboard Data',
            'method' => 'GET',
            'path' => '/get-dashboard-data',
            'description' => 'Returns merchant dashboard stats for the given client — unsettled balance (after fee) and wallet balance.',
            'parameters' => [
                ['name' => 'client_email', 'type' => 'string', 'required' => true, 'description' => 'Merchant email provided by :brand administrator.', 'example' => 'client@gmail.com'],
            ],
            'request_example' => [
                'client_email' => 'client@gmail.com',
            ],
            'responses' => [
                [
                    'code' => 200,
                    'label' => 'Success',
                    'type' => 'success',
                    'description' => 'Dashboard stats for the merchant account.',
                    'body' => [
                        'Unsettled (After Fee)' => '10,640',
                        'Wallet' => [
                            'wallet' => '2,070',
                        ],
                    ],
                ],
            ],
            'notes' => [
                'Pass <code>client_email</code> as a request parameter (query string or body).',
                'Values are formatted with thousand separators (e.g. <code>10,640</code>).',
                '<code>Unsettled (After Fee)</code> is calculated from previous balance, today\'s successful pay-in/payout, and fees.',
                '<code>Wallet.wallet</code> reflects the merchant payout balance.',
                'A v1 variant also exists at <code>GET /v1/get-dashboard-data</code> (API key auth) with additional fields (Previous Balance, Payin, Payout, JC, EP, Total, USDT).',
            ],
        ],

        'callbacks' => [
            'title' => 'Callbacks',
            'show_endpoint' => false,
            'description' => 'When a pay-in or payout completes, :brand sends an HTTP POST to your <code>callback_url</code> with the result payload.',
            'callback_sections' => [
                [
                    'title' => 'Pay-in — Success (documented)',
                    'type' => 'success',
                    'body' => [
                        'orderId' => 'T2024......',
                        'amount' => '',
                        'status_code' => 'Success',
                    ],
                ],
                [
                    'title' => 'Pay-in — Success (runtime payload)',
                    'type' => 'success',
                    'body' => [
                        'orderId' => 'ORD123456',
                        'tid' => '40715150268',
                        'amount' => '1000',
                        'status' => 'success',
                    ],
                ],
                [
                    'title' => 'Payout — Success',
                    'type' => 'success',
                    'body' => [
                        'orderId' => 'Your order_id',
                        'amount' => '',
                        'status' => 'success',
                    ],
                ],
                [
                    'title' => 'Payout — Success (runtime payload)',
                    'type' => 'success',
                    'body' => [
                        'orderId' => 'ORD123456',
                        'tid' => 'T2024...',
                        'amount' => '5000',
                        'status' => 'success',
                    ],
                ],
                [
                    'title' => 'Payout — Failed',
                    'type' => 'error',
                    'body' => [
                        'orderId' => 'Your order_id',
                        'message' => '',
                        'status' => 'failed',
                    ],
                ],
                [
                    'title' => 'Payout — Failed (runtime payload)',
                    'type' => 'error',
                    'body' => [
                        'orderId' => 'ORD123456',
                        'tid' => 'T2024...',
                        'message' => 'Your payout cannot be processed due to ... , please try again.',
                        'status' => 'failed',
                    ],
                ],
            ],
            'notes' => [
                'Callbacks are sent as HTTP POST to the <code>callback_url</code> you provide in the original request.',
                'Your endpoint should respond with HTTP 2xx to acknowledge receipt.',
                'Documented Word payloads and current runtime payloads are both shown above — prefer matching your integration to the fields you actually receive.',
                'Failed payout callbacks include a <code>message</code> field describing the failure reason.',
            ],
        ],

    ],

];
