<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Receipt</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #1a1a1a;
        }

        .receipt-container {
            max-width: 350px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            text-align: center;
            overflow: hidden;
        }

        .receipt-header {
            background-color: #000;
            color: #fff;
            padding: 15px;
            font-size: 18px;
            font-weight: bold;
        }

        .transaction-details {
            padding: 15px;
            line-height: 1.6;
        }

        .transaction-details h2 {
            margin: 10px 0;
            font-size: 28px;
            color: #000;
        }

        .transaction-details small {
            display: block;
            color: #888;
            font-size: 12px;
        }

        .details-list {
            text-align: left;
            margin: 20px 10px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .details-list p {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 14px;
            color: #333;
        }

        .details-list p strong {
            font-weight: 600;
        }

        .footer {
            background-color: #f8f8f8;
            padding: 15px 10px;
            font-size: 12px;
            color: #555;
        }

        .footer img {
            width: 80px;
            margin-top: 10px;
        }

        .action-buttons {
            margin: 10px 0;
        }

        .action-buttons button {
            background-color: #ff6f00;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px;
        }

        .action-buttons button:hover {
            opacity: 0.9;
        }

        .note {
            font-size: 12px;
            color: #d32f2f;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            Transaction Successful
        </div>

        <!-- Transaction Details -->
        <div class="transaction-details">
            <small>TID: {{ $item->transaction_reference}}</small>
            <small>{{ $item->created_at->format('M d, Y') }} at {{ $item->created_at->format('H:i') }}</small>
            <h2>Rs. {{number_format($item->amount)}} .00</h2>
            <p>Amount Paid</p>
        </div>

        <!-- Additional Info -->
        <div class="details-list">
            <p><strong>Fee:</strong> Rs. 0.00</p>
            <p><strong>To:</strong> {{ $item->phone }}</p>
            <p><strong>From:</strong> Mono Tech SMC Pvt. Limited</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Securely paid via</p>
            <img src="{{asset('images/jazzcash.jpg')}}" alt="JazzCash Logo">
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button>Share</button>
            <button>Save</button>
        </div>

        <!-- Note -->
        <div class="note">
            Never share your MPIN with anyone
        </div>
    </div>
</body>
</html>

