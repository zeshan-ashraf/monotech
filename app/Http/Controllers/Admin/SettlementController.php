<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Transaction,Payout,Settlement,User};
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SettlementController extends Controller
{
    // Settlement type mapping with user IDs and names
    private $settlementTypes = [
        'ok' => ['user_id' => '2', 'name' => 'OK Pay'],
        'piq' => ['user_id' => '4', 'name' => 'PIQ Pay'],
        'pkn' => ['user_id' => '5', 'name' => 'PK9 Pay'],
        'cspkr' => ['user_id' => '9', 'name' => 'C7 PKR'],
        'toppay' => ['user_id' => '10', 'name' => 'Top Pay'],
        'corepay' => ['user_id' => '12', 'name' => 'Core Pay'],
        'genxpay' => ['user_id' => '13', 'name' => 'Genx Pay'],
        'moneypay' => ['user_id' => '14', 'name' => 'Money Pay'],
    ];

    public function __construct()
    {
        $this->middleware(['permission:Settlement']);
    }
    
    /**
     * Unified method to handle all settlement types
     */
    public function list(Request $request, $type = null)
    {
        $user = auth()->user();
        
        // Determine user ID based on type or current user
        $userId = $this->getUserIdForSettlement($type, $user);
        
        if (!$userId) {
            abort(404, 'Settlement type not found');
        }
        
        // Build query
        $query = Settlement::where('user_id', $userId);
        
        // Special handling for zig settlement
        if ($type === 'zig') {
            $query->whereDate('date', '>=', '2025-09-16');
        }
        
        $results = $query->orderBy('date', 'DESC')->get();
        
        // Add transaction count for each settlement
        foreach ($results as $summary) {
            $date = $summary->date;
            $transactionCount = Transaction::where('user_id', $userId)
                ->whereDate('created_at', $date)
                ->whereIn('status', ['success', 'failed'])
                ->count();
            $summary->transaction_count = $transactionCount;
        }
        
        // Determine which view to use
        $view = ($type === 'zig') ? 'admin.settlement.zig_list' : 'admin.settlement.list';
        
        return view($view, get_defined_vars());
    }
    
    /**
     * Get user ID for settlement based on type or current user
     */
    private function getUserIdForSettlement($type, $user)
    {
        // If type is provided, try to find user by ID
        if ($type) {
            // First try to find by user ID if type is numeric
            if (is_numeric($type)) {
                $targetUser = User::find($type);
                if ($targetUser && $targetUser->settlements()->exists() && $targetUser->user_role === 'Client' && $targetUser->active == 1 && !str_contains($targetUser->email, 'test@')) {
                    return $targetUser->id;
                }
            }
            
            // Try to find by name (case insensitive)
            $targetUser = User::whereRaw('LOWER(name) = ?', [strtolower($type)])
                ->whereHas('settlements')
                ->where('user_role', 'Client')
                ->where('active', 1)
                ->where('email', 'not like', '%test@%')
                ->first();
            if ($targetUser) {
                return $targetUser->id;
            }
        }
        
        // If user has settlements, use their own ID
        if ($user->settlements()->exists() && $user->user_role === 'Client' && $user->active == 1 && !str_contains($user->email, 'test@')) {
            return $user->id;
        }
        
        return null;
    }
    
   
    
    /**
     * Get all active settlement users from database
     */
    public function getActiveSettlementUsers()
    {
        return User::getActiveSettlementUsers();
    }
    
    /**
     * Get settlement users for sidebar
     */
    public function getSettlementUsersForSidebar()
    {
        return User::getSettlementUsersForSidebar();
    }
    
    // Legacy methods for backward compatibility (only keeping essential ones)
    public function zigList() { return $this->list(request(), 'zig'); }
    public function modal(Request $request)
    {
        $id = $request->id;
        $item = DB::table('settlements')->where('id',$id)->first();
        $html = view('admin.settlement.modal',get_defined_vars())->render();
        return response()->json(['html'=>$html]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'usdt'=>'required',
        ]);
        $item = Settlement::findOrFail($request->id);
        $totalUsdt = $item->usdt+$request->usdt;
        $item->usdt = $totalUsdt;
        $item->settled = $item->settled+$totalUsdt;
        $item->save();
        $msg = "Summary Updated Successfully!";
        return redirect()->back()->with('message',$msg);
    }
}