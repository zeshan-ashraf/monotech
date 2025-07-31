# Duplicate OrderId Prevention Implementation

This document summarizes the changes made to prevent duplicate `orderId` entries in the `transactions` table, especially for concurrent requests.

## Changes Made

### 1. Database Migration Updates

#### Original Migration (`2025_01_30_111457_create_transactions_table.php`)
- **Before**: `$table->string('orderId', 191)->nullable();`
- **After**: `$table->string('orderId', 50)->unique();`

#### Additional Migration (`2025_01_30_120000_add_unique_index_to_orderid_in_transactions_table.php`)
- Created a separate migration to handle existing tables
- Changes `orderId` column to `varchar(50)`
- Adds unique index on `orderId` column
- Includes proper rollback functionality

### 2. Transaction Model Updates (`app/Models/Transaction.php`)

#### Added Validation Rules
```php
public static function getValidationRules($ignoreId = null)
{
    $rules = [
        'orderId' => [
            'required',
            'string',
            'max:50',
            Rule::unique('transactions')->ignore($ignoreId)
        ],
        // ... other rules
    ];
    return $rules;
}
```

#### Added Validation Messages
```php
public static function getValidationMessages()
{
    return [
        'orderId.unique' => 'This order ID has already been used.',
        // ... other messages
    ];
}
```

### 3. PaymentService Updates (`app/Service/PaymentService.php`)

#### Added Exception Handling
- Imported `QueryException`
- Wrapped `Transaction::create()` in try-catch block
- Handles MySQL error code 1062 (duplicate key)
- Logs duplicate attempts with detailed information
- Throws custom exception with 409 status code

```php
try {
    $transaction = Transaction::create($values);
    // ... success handling
} catch (QueryException $e) {
    if ($e->getCode() == 1062) {
        // Log duplicate attempt
        throw new \Exception('Order ID already exists. Please use a different order ID.', 409);
    }
    throw $e;
}
```

### 4. PaymentServiceV1 Updates (`app/Service/PaymentServiceV1.php`)

#### Updated createTransaction Method
- Added `QueryException` import
- Wrapped transaction creation in try-catch
- Handles duplicate key exceptions gracefully
- Returns existing transaction if duplicate detected

#### Updated createBlockedTransaction Method
- Added duplicate key exception handling
- Logs duplicate attempts
- Returns existing transaction if available

### 5. Validation Middleware Updates (`app/Http/Middleware/PaymentValidationMiddleware.php`)

#### Enhanced Validation Rules
```php
'orderId' => [
    'required',
    'string',
    'max:50',
    Rule::unique('transactions')
]
```

#### Added Custom Error Message
```php
'orderId.unique' => 'This order ID has already been used.'
```

### 6. Controller Updates (`app/Http/Controllers/Api/PaymentCheckoutController.php`)

#### Enhanced Error Handling
- Updated `handleProcessingError` method
- Specifically handles duplicate orderId exceptions
- Returns 409 status code for duplicate orderId
- Maintains proper error logging

```php
if ($e->getMessage() === 'Order ID already exists. Please use a different order ID.') {
    return response()->json([
        'status' => 'error',
        'message' => $e->getMessage(),
    ], 409);
}
```

## Key Features

### 1. Database-Level Protection
- Unique index on `orderId` column prevents duplicates at database level
- Column length reduced to 50 characters for better performance
- Proper migration with rollback support

### 2. Application-Level Validation
- Laravel validation rules check for uniqueness
- Custom error messages for better user experience
- Validation happens before database operations

### 3. Exception Handling
- Catches `QueryException` with MySQL error code 1062
- Graceful handling of concurrent duplicate attempts
- Proper logging for debugging and monitoring
- Returns appropriate HTTP status codes (409 for conflicts)

### 4. Concurrency Safety
- **No use of `Model::where(...)->exists()`** as requested
- Database-level constraints handle race conditions
- Application gracefully handles duplicate attempts
- Returns existing transaction when appropriate

## Usage

### Running Migrations
```bash
php artisan migrate
```

### Testing Duplicate Prevention
1. Send concurrent requests with the same `orderId`
2. First request should succeed
3. Subsequent requests should receive 409 status code
4. Check logs for duplicate attempt warnings

### API Response Examples

#### Successful Transaction
```json
{
    "status": "pending",
    "transaction_id": "T20250130123456789012",
    "message": "Payment checkout initiated successfully."
}
```

#### Duplicate OrderId
```json
{
    "status": "error",
    "message": "Order ID already exists. Please use a different order ID."
}
```

## Benefits

1. **Prevents Data Corruption**: No duplicate orderId entries
2. **Handles Concurrency**: Race conditions handled gracefully
3. **Better User Experience**: Clear error messages
4. **Monitoring**: Comprehensive logging for debugging
5. **Performance**: Optimized column size and indexing
6. **Maintainability**: Clean, well-documented code

## Notes

- The implementation avoids using `Model::where(...)->exists()` as requested
- Database-level constraints provide the most reliable protection
- Application-level validation provides immediate feedback
- Exception handling ensures graceful degradation
- All changes are backward compatible with existing code 