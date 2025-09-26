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
        // If type is provided, try to find user by name or ID
        if ($type) {
            // First try to find by user ID if type is numeric
            if (is_numeric($type)) {
                $targetUser = User::find($type);
                if ($targetUser && $targetUser->settlements()->exists() && $targetUser->user_role === 'Client' && $targetUser->active == 1) {
                    return $targetUser->id;
                }
            }
            
            // Try to find by name (case insensitive)
            $targetUser = User::whereRaw('LOWER(name) = ?', [strtolower($type)])
                ->whereHas('settlements')
                ->where('user_role', 'Client')
                ->where('active', 1)
                ->first();
            if ($targetUser) {
                return $targetUser->id;
            }
            
            // Check static mapping for legacy support
            if (isset($this->settlementTypes[$type])) {
                $mappedUserId = $this->settlementTypes[$type]['user_id'];
                $targetUser = User::find($mappedUserId);
                if ($targetUser && $targetUser->settlements()->exists() && $targetUser->user_role === 'Client' && $targetUser->active == 1) {
                    return $targetUser->id;
                }
            }
        }
        
        // For specific user IDs (legacy support)
        $userSettlementMap = [
            '2' => '2',   // OK Pay
            '4' => '4',   // PIQ Pay  
            '5' => '5',   // PK9 Pay
            '9' => '9',   // C7 PKR
            '14' => '14', // Money Pay
        ];
        
        if (isset($userSettlementMap[$user->id])) {
            return $userSettlementMap[$user->id];
        }
        
        // If user has settlements, use their own ID
        if ($user->settlements()->exists() && $user->user_role === 'Client' && $user->active == 1) {
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
    
    // Legacy methods for backward compatibility
    public function okList() { return $this->list(request(), 'ok'); }
    public function piqList() { return $this->list(request(), 'piq'); }
    public function pknList() { return $this->list(request(), 'pkn'); }
    public function cspkrList() { return $this->list(request(), 'cspkr'); }
    public function toppayList() { return $this->list(request(), 'toppay'); }
    public function corepayList() { return $this->list(request(), 'corepay'); }
    public function genxpayList() { return $this->list(request(), 'genxpay'); }
    public function moneypayList() { return $this->list(request(), 'moneypay'); }
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