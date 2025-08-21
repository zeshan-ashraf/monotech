<?php

// ============================================================================
// HIGH-VALUE TRANSACTION RESTRICTION - QUICK IMPLEMENTATION TEMPLATE
// ============================================================================
// 
// COPY THIS ENTIRE FILE TO YOUR PROJECT AND FOLLOW THE STEPS BELOW
//
// ============================================================================

// STEP 1: Create the trait file at: app/Traits/HighValueTransactionRestriction.php
// (Copy the trait content from the documentation)

// STEP 2: Add this to your controller class:
use App\Traits\HighValueTransactionRestriction;

class YourController extends Controller
{
    use HighValueTransactionRestriction;
    
    // ... existing code ...
}

// STEP 3: Add this validation code in your method (after validation, before processing):
/*
public function yourMethod(Request $request)
{
    $requestId = uniqid('req_');
    $startTime = microtime(true);
    
    // ... your existing validation code ...
    
    // ADD THIS LINE:
    $restrictionCheck = $this->checkHighValueTransactionRestriction($request, $requestId, $startTime);
    if ($restrictionCheck) {
        return response()->json($restrictionCheck, $restrictionCheck['code']);
    }
    
    // ... continue with your existing logic ...
}
*/

// ============================================================================
// ALTERNATIVE: INLINE IMPLEMENTATION (if you don't want to use traits)
// ============================================================================

// Add this code directly in your method instead of using the trait:

/*
// Check if high-value transaction restriction applies (50000+ transactions within 10 minutes)
if ($request->amount >= 50000) {
    $recentHighValueTransaction = Transaction::where('phone', $request->phone)
        ->where('amount', '>=', 50000)
        ->whereIn('status', ['success', 'pending'])
        ->where('created_at', '>=', now()->subMinutes(10))
        ->first();

    if ($recentHighValueTransaction) {
        return response()->json([
            'status' => 'error',
            'message' => 'High-value transactions (50,000+) are restricted for 10 minutes after a successful or pending transaction from this number.',
        ], 429);
    }
}
*/

// ============================================================================
// CUSTOMIZATION OPTIONS
// ============================================================================

// Change threshold amount (default: 50000)
// $restrictionCheck = $this->checkHighValueTransactionRestriction($request, $requestId, $startTime, 100000);

// Change restriction time (default: 10 minutes)
// $restrictionCheck = $this->checkHighValueTransactionRestriction($request, $requestId, $startTime, 50000, 30);

// Custom statuses
// $restrictionCheck = $this->checkHighValueTransactionRestrictionWithCustomStatuses(
//     $request, $requestId, $startTime, 50000, 10, ['success', 'pending', 'processing']
// );

// ============================================================================
// REQUIRED DATABASE COLUMNS
// ============================================================================
// Your transactions table must have:
// - phone (string)
// - amount (numeric)
// - status (string)
// - created_at (timestamp)

// ============================================================================
// TESTING
// ============================================================================
// 1. Make a transaction of 50,000+ with status 'success' or 'pending'
// 2. Try another 50,000+ transaction from same phone within 10 minutes
// 3. Should get restriction error
// 4. Wait 10+ minutes, restriction should be lifted

// ============================================================================
// RESPONSE FORMAT
// ============================================================================
// When restricted:
// {
//     "status": "error",
//     "message": "High-value transactions (50000+) are restricted for 10 minutes after a successful or pending transaction from this number.",
//     "code": 429
// }
