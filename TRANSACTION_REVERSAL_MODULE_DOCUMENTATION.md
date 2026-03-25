# Transaction Reversal Module - Complete File List and Setup Guide

This document lists all files and changes required to implement the Transaction Reversal Module in another project.

## Overview
The Transaction Reversal Module allows administrators to:
- Mark transactions for reversal (with a 6-hour countdown)
- Cancel reversal requests before the deadline
- Reverse transactions immediately (bypassing the 6-hour wait)
- Bulk reverse multiple transactions
- Automatically reverse transactions after 6 hours via cron job

---

## 📁 Required Files

### 1. **Controller**
**File:** `app/Http/Controllers/Admin/TransactionReversalController.php`
- Handles all reversal-related HTTP requests
- Methods: `index()`, `markForReversal()`, `cancelReversal()`, `reverseNow()`, `bulkReverse()`
- Requires permission: `Reverse Transactions`

### 2. **Service Class**
**File:** `app/Services/TransactionReversalService.php`
- Core business logic for reversal operations
- Handles marking, canceling, and processing reversals
- Supports multiple transaction tables: `transactions`, `archeive_transactions`, `backup_transactions`
- Configurable wait hours via `.env` variable `REVERSAL_WAIT_HOURS` (default: 6)

### 3. **DataTable Class**
**File:** `app/DataTables/Admin/ReversalDataTable.php`
- Yajra DataTables configuration for pending reversals list
- Displays countdown timers, action buttons, and transaction details
- Supports bulk selection and actions

### 4. **View Template**
**File:** `resources/views/admin/transaction/reversals.blade.php`
- Main UI for pending reversals page
- Includes JavaScript for countdown timers, bulk actions, and AJAX requests
- Uses DataTables for data display

### 5. **Console Command**
**File:** `app/Console/Commands/AutoReverseTransactions.php`
- Scheduled command to auto-reverse transactions after 6 hours
- Should run every 5 minutes (configured in `Kernel.php`)

### 6. **Migration**
**File:** `database/migrations/2025_12_08_120000_add_reverse_requested_at_to_transactions_tables.php`
- Adds `reverse_requested_at` timestamp column to:
  - `transactions` table
  - `archeive_transactions` table
  - `backup_transactions` table

---

## 🔧 Required Changes to Existing Files

### 1. **Routes File**
**File:** `routes/admin.php`

**Add these routes:**
```php
Route::as('transaction.reversal.')->prefix('transaction/reversal')->group(function () {
    Route::get('/list', [\App\Http\Controllers\Admin\TransactionReversalController::class, 'index'])->name('list');
    Route::post('/mark', [\App\Http\Controllers\Admin\TransactionReversalController::class, 'markForReversal'])->name('mark');
    Route::post('/cancel', [\App\Http\Controllers\Admin\TransactionReversalController::class, 'cancelReversal'])->name('cancel');
    Route::post('/reverse-now', [\App\Http\Controllers\Admin\TransactionReversalController::class, 'reverseNow'])->name('reverse_now');
    Route::post('/bulk-reverse', [\App\Http\Controllers\Admin\TransactionReversalController::class, 'bulkReverse'])->name('bulk_reverse');
});
```

### 2. **Console Kernel**
**File:** `app/Console/Kernel.php`

**Add to `$commands` array:**
```php
protected $commands = [
    // ... existing commands ...
    \App\Console\Commands\AutoReverseTransactions::class,
];
```

**Add to `schedule()` method:**
```php
// Auto-reverse transactions after 6 hours
$event = $schedule->command('transactions:auto-reverse')->everyFiveMinutes();
$wrapSchedule($event, 'transactions:auto-reverse'); // If you have wrapSchedule function
// OR simply:
$schedule->command('transactions:auto-reverse')->everyFiveMinutes();
```

### 3. **Transaction Models**
**Files:**
- `app/Models/Transaction.php`
- `app/Models/ArcheiveTransaction.php`
- `app/Models/BackupTransaction.php`

**Add to `$fillable` array:**
```php
protected $fillable = [
    // ... existing fields ...
    'reverse_requested_at',
];
```

**Note:** If you don't have `ArcheiveTransaction` or `BackupTransaction` models, you can modify the service to only use the `Transaction` model.

### 4. **Sidebar Navigation**
**File:** `resources/views/admin/layout/include/sidebar.blade.php`

**Add menu item (inside appropriate permission check):**
```blade
@can('Reverse Transactions')
<li class="@if (url()->current() == route('admin.transaction.reversal.list')) active @endif nav-item">
    <a class="d-flex align-items-center" href="{{ route('admin.transaction.reversal.list') }}">
        <i data-feather="rotate-ccw"></i>Pending Reversals
    </a>
</li>
@endcan
```

### 5. **Badge View (Optional but Recommended)**
**File:** `resources/views/admin/transaction/badge.blade.php`

This view is used by the DataTable to display transaction status badges. If you don't have it, create it:
```blade
@if($type == 'success')
    <span class="badge bg-success text-capitalize">{{$type}}</span>
@elseif($type == 'pending')
    <span class="badge bg-primary text-capitalize">{{$type}}</span>
@elseif($type == 'failed')
    <span class="badge bg-danger text-capitalize" data-bs-toggle="tooltip" data-bs-placement="top" title="{{$reason}}">{{$type}}</span>
@elseif($type == 'reverse')
    <span class="badge bg-warning text-capitalize">{{$type}}</span>
@else
    <span class="badge bg-info text-capitalize">{{$type}}</span>
@endif
```

### 6. **DataTables Script Include (If Not Exists)**
**File:** `resources/views/admin/components/datatablesScript.blade.php`

The reversals view includes this file. If it doesn't exist, create it with your DataTables initialization scripts, or remove the include from `reversals.blade.php` if not needed.

---

## 🔐 Permission Setup

**Required Permission:** `Reverse Transactions`

You need to:
1. Create the permission in your permission system (e.g., using Spatie Laravel Permission or similar)
2. Assign it to appropriate roles/users
3. The controller middleware `['permission:Reverse Transactions']` will enforce this

**Quick Method to Create Permission:**
```bash
# Run the artisan command
php artisan permission:create-reverse-transactions

# Or use Tinker
php artisan tinker
>>> use App\Models\Permission;
>>> Permission::create(['name' => 'Reverse Transactions', 'guard_name' => 'web']);
```

**Assign Permission to Role:**
- Via Admin UI: Go to `/admin/roles`, edit a role, check "Reverse Transactions" checkbox, and save
- Via Tinker:
  ```php
  use App\Models\Role;
  $role = Role::find(1); // Replace with your role ID
  $role->givePermissionTo('Reverse Transactions');
  ```

---

## 📦 Dependencies

### Required Packages:
1. **Yajra DataTables** - For the reversals list table
   ```bash
   composer require yajra/laravel-datatables-oracle
   ```

2. **Carbon** - Already included in Laravel (for date/time handling)

3. **Spatie Laravel Permission** - For permission management
   ```bash
   composer require spatie/laravel-permission
   ```

### Database Requirements:
- Tables: `transactions`, `archeive_transactions`, `backup_transactions` (or adjust service to use your table names)
- Column: `reverse_requested_at` (timestamp, nullable) - added via migration
- Column: `status` (string) - should support values: 'success', 'reverse', 'pending', 'failed'

---

## ⚙️ Configuration

### Environment Variables
**File:** `.env`

Add (optional, defaults to 6 hours):
```env
REVERSAL_WAIT_HOURS=6
```

### Cron Job Setup
Ensure Laravel's scheduler is running in your server's crontab:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔄 Optional: Integration with Transaction List

If you want to add "Mark for Reversal" buttons in your transaction list pages:

**File:** `app/DataTables/Admin/SearchingDataTable.php` (or your transaction DataTable)

**Add to action column:**
```php
->addColumn('actions', function ($query) {
    $user = auth()->user();
    $buttons = '';
    
    // ... existing buttons ...
    
    // Add Mark for Reversal button if user has permission and transaction is success
    if ($user && method_exists($user, 'can') && $user->can('Reverse Transactions') && $query->status == 'success') {
        $reverseRequested = isset($query->reverse_requested_at) ? $query->reverse_requested_at : null;
        
        if (!$reverseRequested) {
            $tableType = 'transactions'; // Adjust based on your table structure
            $buttons .= ' <button class="btn btn-warning btn-sm mt-1 mark-for-reversal-btn" data-id="' . $query->id . '" data-table-type="' . $tableType . '">Mark for Reversal</button>';
        }
    }
    
    return $buttons;
})
```

**Add JavaScript handler (in your transaction list view):**
```javascript
// Mark for Reversal button handler
$(document).on('click', '.mark-for-reversal-btn', function() {
    var id = $(this).data('id');
    var tableType = $(this).data('table-type');
    
    if (!confirm('Are you sure you want to mark this transaction for reversal? A 6-hour countdown will start.')) {
        return;
    }
    
    $.ajax({
        url: '{{ route("admin.transaction.reversal.mark") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id: id,
            table_type: tableType
        },
        success: function(response) {
            if (response.success) {
                alert(response.message);
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Failed to mark transaction for reversal'));
            }
        },
        error: function(xhr) {
            alert('Error: ' + (xhr.responseJSON?.message || 'Failed to mark transaction for reversal'));
        }
    });
});
```

---

## 📋 Migration Steps

1. **Copy all required files** to your project
2. **Run the migration:**
   ```bash
   php artisan migrate
   ```
3. **Create the permission:**
   ```bash
   php artisan permission:create-reverse-transactions
   ```
4. **Update existing files** (routes, Kernel, models, sidebar)
5. **Assign permission to roles** via admin UI or Tinker
6. **Test the module:**
   - Access `/admin/transaction/reversal/list`
   - Mark a transaction for reversal
   - Verify countdown timer works
   - Test cancel and reverse now actions
   - Verify cron job runs and auto-reverses transactions

---

## 🎯 Key Features

1. **6-Hour Countdown:** Transactions marked for reversal have a 6-hour waiting period
2. **Auto-Reversal:** Cron job automatically reverses transactions after 6 hours
3. **Immediate Reversal:** Admins can bypass the wait and reverse immediately
4. **Bulk Operations:** Select and reverse/cancel multiple transactions at once
5. **Multi-Table Support:** Works with transactions, archived transactions, and backup transactions
6. **Permission-Based:** Only users with `Reverse Transactions` permission can access

---

## ⚠️ Important Notes

1. **Table Names:** If your project uses different table names, update the `TransactionReversalService.php` to use your model names
2. **Status Values:** Ensure your transaction status field supports 'reverse' as a valid status value
3. **User Relationship:** The service expects a `user()` relationship on transaction models
4. **Archive/Backup Models:** If you don't have `ArcheiveTransaction` or `BackupTransaction`, modify the service to only check the main `Transaction` model
5. **DataTables:** Ensure Yajra DataTables is properly configured in your project
6. **Guard Name:** The permission uses `guard_name = 'web'` - adjust if your project uses a different guard

---

## 📝 File Summary

### New Files to Create (6 files):
1. `app/Http/Controllers/Admin/TransactionReversalController.php`
2. `app/Services/TransactionReversalService.php`
3. `app/DataTables/Admin/ReversalDataTable.php`
4. `resources/views/admin/transaction/reversals.blade.php`
5. `app/Console/Commands/AutoReverseTransactions.php`
6. `database/migrations/2025_12_08_120000_add_reverse_requested_at_to_transactions_tables.php`

### Files to Modify (5-7 files):
1. `routes/admin.php` - Add routes
2. `app/Console/Kernel.php` - Register command and schedule
3. `app/Models/Transaction.php` - Add fillable field
4. `app/Models/ArcheiveTransaction.php` - Add fillable field (if exists)
5. `app/Models/BackupTransaction.php` - Add fillable field (if exists)
6. `resources/views/admin/layout/include/sidebar.blade.php` - Add menu item
7. `resources/views/admin/transaction/badge.blade.php` - Add 'reverse' status (if exists)

---

## ✅ Testing Checklist

- [ ] Migration runs successfully
- [ ] Permission created and assigned
- [ ] Routes accessible
- [ ] Menu item appears in sidebar
- [ ] Can mark transaction for reversal
- [ ] Countdown timer displays correctly
- [ ] Can cancel reversal request
- [ ] Can reverse immediately
- [ ] Bulk reverse works
- [ ] Cron job runs and auto-reverses transactions
- [ ] Reversed transactions show correct status

---

## 🚀 Quick Start Commands

```bash
# 1. Run migration
php artisan migrate

# 2. Create permission
php artisan permission:create-reverse-transactions

# 3. Assign permission to role (via Tinker)
php artisan tinker
>>> use App\Models\Role;
>>> $role = Role::find(1);
>>> $role->givePermissionTo('Reverse Transactions');

# 4. Clear cache
php artisan cache:clear
php artisan permission:cache-reset

# 5. Test the module
# Visit: /admin/transaction/reversal/list
```

---

**End of Documentation**
