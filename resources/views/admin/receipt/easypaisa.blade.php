
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Success</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #2e2b34;
        }
        .card-custom {
            max-width: 375px;
            margin: 50px auto;
            border-radius: 10px;
            border-top: 2px dashed black;
            border-bottom: 2px dashed black;
        }
        .success-icon {
            width: 50px;
            margin: 8px auto;
            display: block;
        }
        .success-title {
            color: #28b15e;
            font-weight: bold;
            font-size: 1.5rem;
        }
        .transaction-details p {
            margin: 0;
            font-size: 0.95rem;
        }
        .total-amount {
            color: #28b15e;
            font-weight: bold;
            font-size: 1.4rem;
        }
        .easypaisa-logo {
            max-width: 10vw;
            height: auto;
        }
        .big-x {
            font-size: 18px;
            color: black;
        }
        .float-left {
            float: left;
        }
        .bold-font {
            font-size: 18px;
            font-weight: bold;
        }
        .card-footer {
            padding: 10px;
        }
        .d-flex {
            display: flex;
        }
        .gap-3 {
            gap: 1rem;
        }
        .align-items-center {
            align-items: center;
        }
        .text-end {
            text-align: right;
        }
        .text-muted {
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow card-custom">
            <div class="card-header bg-light text-center position-relative">
                <div class="big-x text-end">X</div>
                <img src="{{asset('images/tick.png')}}" alt="success-icon" class="success-icon">
                <div>
                    <img src="{{asset('images/ep-logo-new.png')}}" alt="easypaisa-logo" class="easypaisa-logo">
                </div>
                <h4 class="success-title mb-1">Transaction Successful</h4>
                <p class="text-muted" style="font-size: 14px;">Your payment has been made</p>
            </div>
            <div class="card-body transaction-details">
                <p class="text-muted">{{ $item->created_at->format('d-M-Y h:i A') }}</p>
                <p class="text-muted mb-2">TID# {{$item->transaction_reference}}</p>

                <p><strong>Company/Merchant Name:</strong></p>
                <p class="mb-2">Mono Tech SMC Pvt. Limited</p>
                <p><strong>Paid To:</strong></p>
                <p class="mb-2">{{ $item->phone }}</p>
                <p><strong>Amount:</strong></p>
                <p class="mb-2">Rs. {{number_format($item->amount)}}</p>
                <p><strong>Fee / Charge:</strong></p>
                <p class="mb-2">No Charge</p>
                <p class="total-amount">Total Amount:</p>
                <p>Rs. {{number_format($item->amount)}}</p>
            </div>
            <div class="card-footer bg-light text-end">
                <div class="d-flex justify-content-end gap-3">
                    <div class="d-flex flex-column align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-share" viewBox="0 0 16 16">
                            <path d="M13.5 1a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3M11 2.5a2.5 2.5 0 1 1 .603 1.628l-6.718 3.12a2.5 2.5 0 0 1 0 1.504l6.718 3.12a2.5 2.5 0 1 1-.488.876l-6.718-3.12a2.5 2.5 0 1 1 0-3.256l6.718-3.12A2.5 2.5 0 0 1 11 2.5m-8.5 4a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3m11 5.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3"/>
                        </svg>
                        <span class="mt-2 text-center" style="font-size: 12px">Share</span>
                    </div>
                    <div class="d-flex flex-column align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-images" viewBox="0 0 16 16">
                            <path d="M4.502 9a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                            <path d="M14.002 13a2 2 0 0 1-2 2h-10a2 2 0 0 1-2-2V5A2 2 0 0 1 2 3a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v8a2 2 0 0 1-1.998 2M14 2H4a1 1 0 0 0-1 1h9.002a2 2 0 0 1 2 2v7A1 1 0 0 0 15 11V3a1 1 0 0 0-1-1M2.002 4a1 1 0 0 0-1 1v8l2.646-2.354a.5.5 0 0 1 .63-.062l2.66 1.773 3.71-3.71a.5.5 0 0 1 .577-.094l1.777 1.947V5a1 1 0 0 0-1-1z"/>
                        </svg>
                        <span class="mt-2" style="font-size: 12px">Save to Gallery</span>
                    </div>
                    <div class="d-flex flex-column align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/>
                        </svg>
                        <span class="mt-2" style="font-size: 12px">Save as PDF</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
