# High-Value Transaction Restriction Implementation Guide

This guide explains how to implement a high-value transaction restriction system that prevents transactions of 50,000+ from the same phone number for 10 minutes after a successful or pending transaction.

## What This System Does

- **Restricts high-value transactions**: Prevents transactions of 50,000+ from the same phone number
- **Time-based restriction**: Applies for 10 minutes after a successful or pending transaction
- **Status-based filtering**: Only considers transactions with 'success' or 'pending' status
- **Comprehensive logging**: Logs all restriction triggers for monitoring and debugging

## Files to Copy

### 1. Trait File
Copy this file to your project: `app/Traits/HighValueTransactionRestriction.php`

### 2. Database Requirements
Ensure your transactions table has these columns:
- `phone` (string) - Phone number
- `amount` (numeric) - Transaction amount
- `status` (string) - Transaction status
- `created_at` (timestamp) - When transaction was created

## Implementation Steps

### Step 1: Copy the Trait
```php
<?php

namespace App\Traits;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait HighValueTransactionRestriction
{
    /**
     * Check if high-value transaction restriction applies
     * Restricts transactions of 50000+ from the same phone number for 10 minutes
     * after a successful or pending transaction
     *
     * @param Request $request
     * @param string $requestId
     * @param float $startTime
     * @param int $thresholdAmount
     * @param int $restrictionMinutes
     * @return array|null Returns error response array if restriction applies, null otherwise
     */
    protected function checkHighValueTransactionRestriction(
        Request $request, 
        string $requestId, 
        float $startTime,
        int $thresholdAmount = 50000,
        int $restrictionMinutes = 10
    ): ?array {
        // Only check if the current transaction amount meets the threshold
        if ($request->amount >= $thresholdAmount) {
            $recentHighValueTransaction = Transaction::where('phone', $request->phone)
                ->where('amount', '>=', $thresholdAmount)
                ->whereIn('status', ['success', 'pending'])
                ->where('created_at', '>=', now()->subMinutes($restrictionMinutes))
                ->first();

            if ($recentHighValueTransaction) {
                // Log the restriction trigger
                if (method_exists($this, 'logger')) {
                    $this->logger->warning('High-value transaction restriction triggered', [
                        'request_id' => $requestId,
                        'phone' => $request->phone,
                        'amount' => $request->amount,
                        'threshold_amount' => $thresholdAmount,
                        'restriction_minutes' => $restrictionMinutes,
                        'recent_transaction_id' => $recentHighValueTransaction->id,
                        'recent_transaction_status' => $recentHighValueTransaction->status,
                        'recent_transaction_time' => $recentHighValueTransaction->created_at,
                        'execution_time' => microtime(true) - $startTime
                    ]);
                }

                return [
                    'status' => 'error',
                    'message' => "High-value transactions ({$thresholdAmount}+) are restricted for {$restrictionMinutes} minutes after a successful or pending transaction from this number.",
                    'code' => 429
                ];
            }
        }

        return null;
    }

    /**
     * Check if high-value transaction restriction applies with custom statuses
     *
     * @param Request $request
     * @param string $requestId
     * @param string $requestId
     * @param float $startTime
     * @param int $thresholdAmount
     * @param int $restrictionMinutes
     * @param array $restrictedStatuses
     * @return array|null Returns error response array if restriction applies, null otherwise
     */
    protected function checkHighValueTransactionRestrictionWithCustomStatuses(
        Request $request, 
        string $requestId, 
        float $startTime,
        int $thresholdAmount = 50000,
        int $restrictionMinutes = 10,
        array $restrictedStatuses = ['success', 'pending']
    ): ?array {
        // Only check if the current transaction amount meets the threshold
        if ($request->amount >= $thresholdAmount) {
            $recentHighValueTransaction = Transaction::where('phone', $request->phone)
                ->where('amount', '>=', $thresholdAmount)
                ->whereIn('status', $restrictedStatuses)
                ->where('created_at', '>=', now()->subMinutes($restrictionMinutes))
                ->first();

            if ($recentHighValueTransaction) {
                // Log the restriction trigger
                if (method_exists($this, 'logger')) {
                    $this->logger->warning('High-value transaction restriction triggered', [
                        'request_id' => $requestId,
                        'phone' => $request->phone,
                        'amount' => $request->amount,
                        'threshold_amount' => $thresholdAmount,
                        'restriction_minutes' => $restrictionMinutes,
                        'restricted_statuses' => $restrictedStatuses,
                        'recent_transaction_id' => $recentHighValueTransaction->id,
                        'recent_transaction_status' => $recentHighValueTransaction->status,
                        'recent_transaction_time' => $recentHighValueTransaction->created_at,
                        'execution_time' => microtime(true) - $startTime
                    ]);
                }

                return [
                    'status' => 'error',
                    'message' => "High-value transactions ({$thresholdAmount}+) are restricted for {$restrictionMinutes} minutes after a transaction with status: " . implode(', ', $restrictedStatuses) . " from this number.",
                    'code' => 429
                ];
            }
        }

        return null;
    }
}
```

### Step 2: Add Trait to Your Controller
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HighValueTransactionRestriction;

class YourController extends Controller
{
    use HighValueTransactionRestriction;
    
    // Your existing code...
}
```

### Step 3: Add Validation in Your Method
```php
public function yourMethod(Request $request)
{
    $requestId = uniqid('req_');
    $startTime = microtime(true);
    
    // Your existing validation...
    
    // Add this line after validation and before processing:
    $restrictionCheck = $this->checkHighValueTransactionRestriction($request, $requestId, $startTime);
    if ($restrictionCheck) {
        return response()->json($restrictionCheck, $restrictionCheck['code']);
    }
    
    // Continue with your existing logic...
}
```

## Customization Options

### 1. Change Threshold Amount
```php
// Default: 50000
$restrictionCheck = $this->checkHighValueTransactionRestriction($request, $requestId, $startTime, 100000);
```

### 2. Change Restriction Time
```php
// Default: 10 minutes
$restrictionCheck = $this->checkHighValueTransactionRestriction($request, $requestId, $startTime, 50000, 30);
```

### 3. Custom Statuses
```php
// Use custom method for different statuses
$restrictionCheck = $this->checkHighValueTransactionRestrictionWithCustomStatuses(
    $request, 
    $requestId, 
    $startTime, 
    50000, 
    10, 
    ['success', 'pending', 'processing']
);
```

## Response Format

When restriction is triggered, the system returns:
```json
{
    "status": "error",
    "message": "High-value transactions (50000+) are restricted for 10 minutes after a successful or pending transaction from this number.",
    "code": 429
}
```

## Logging

The system logs all restriction triggers with detailed information:
- Request ID
- Phone number
- Transaction amount
- Threshold amount
- Restriction minutes
- Recent transaction details
- Execution time

## Database Query Performance

The restriction check uses this optimized query:
```sql
SELECT * FROM transactions 
WHERE phone = ? 
AND amount >= ? 
AND status IN ('success', 'pending') 
AND created_at >= ? 
LIMIT 1
```

**Recommendation**: Add an index on `(phone, amount, status, created_at)` for better performance.

## Testing

Test the restriction with:
1. A transaction of 50,000+ with status 'success' or 'pending'
2. Try another transaction of 50,000+ from the same phone within 10 minutes
3. Verify the restriction is triggered
4. Wait 10+ minutes and verify the restriction is lifted

## Troubleshooting

### Common Issues:
1. **Trait not found**: Ensure the trait file is in the correct namespace
2. **Model not found**: Verify your Transaction model exists and has the required columns
3. **Logger not working**: The trait checks if logger exists before using it
4. **Performance issues**: Add database indexes for better query performance

### Debug Mode:
Add logging to see what's happening:
```php
Log::info('Restriction check', [
    'phone' => $request->phone,
    'amount' => $request->amount,
    'threshold' => 50000
]);
```

## Security Considerations

- This restriction is client-side enforced and should be complemented with server-side validation
- Consider rate limiting at the API level for additional security
- Monitor logs for potential abuse patterns
- The restriction is based on phone numbers, so ensure phone number validation is robust

## Migration to Other Projects

1. Copy the trait file
2. Ensure your Transaction model has the required columns
3. Add the trait to your controller
4. Add the validation call in your method
5. Test thoroughly
6. Adjust thresholds and timing as needed for your business rules
