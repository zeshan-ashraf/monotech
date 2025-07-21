<?php

use Illuminate\Support\Facades\Mail;
use App\Models\{Transaction, Payout, Settlement, Setting, User};
use Carbon\Carbon;

function sendMail($data)
{
    try {
        return $mail = Mail::send($data['view'], ['data' => $data['data']], function ($message) use ($data) {
          $message->to($data['to'], $data['to'])
          ->from(config('mail.from.address'), config('mail.from.name'))
            ->subject($data['subject']);
        });

    } catch (\Exception $e) {
    }
}
function formatAmount($amount) {
    if ($amount >= 1000 && $amount < 1000000) {
        return number_format($amount / 1000, 1) . 'K';
    } elseif ($amount >= 1000000 && $amount < 1000000000) {
        return number_format($amount / 1000000, 1) . 'M';
    } elseif ($amount >= 1000000000) {
        return number_format($amount / 1000000000, 1) . 'B';
    }

    return number_format($amount);
}
function parseFormattedAmount($formattedAmount) {
    $lastChar = strtoupper(substr($formattedAmount, -1));
    $amount = (float) $formattedAmount;

    switch ($lastChar) {
        case 'K':
            return $amount * 1000;
        case 'M':
            return $amount * 1000000;
        case 'B':
            return $amount * 1000000000;
        default:
            return (float) $formattedAmount; // Return as-is if no suffix
    }
}
function getUnsettlement($id){
    $epPayinAmount = Settlement::where('user_id', $id)->whereDate('date', today())->value('ep_payin') ?? 0;
    $payinSuccess=Transaction::where('user_id',$id)
        ->where('status','success')
        ->whereDate('created_at',today())
        ->sum('amount');
    $payoutSuccess=Payout::where('user_id',$id)
        ->where('status','success')
        ->whereDate('created_at',today())
        ->sum('amount');
    $prevBal=Settlement::where('user_id',$id)
        ->whereDate('date',today()->subDay()->format('Y-m-d'))
        ->select('closing_bal')
        ->value('closing_bal');
    $prevUsdt=Settlement::where('user_id',$id)
        ->whereDate('date',today()->format('Y-m-d'))
        ->select('usdt')
        ->value('usdt');
    $assignedAmount=Setting::where('user_id',$id)->select('jazzcash','easypaisa','payout_balance')->first();
    $user=User::find($id);
    $payinFee=$user->payin_fee;
    $payoutFee=$user->payout_fee;
    if ($id == 2) {
        $payinSuccess = $epPayinAmount;
    } 
    $unSettledAmount= $prevBal + $payinSuccess - ($payinSuccess*$payinFee + $payoutSuccess + $payoutSuccess*$payoutFee + $assignedAmount->payout_balance + $prevUsdt);
    
    return $unSettledAmount;
}

function srCount($id){
    
    $totalPayinSuccessCount = Transaction::where('user_id', $id)
        ->whereDate('created_at', Carbon::today())
        ->where('status', 'success')
        ->count();
    
    $totalPayinFailedCount = Transaction::where('user_id', $id)
        ->whereDate('created_at', Carbon::today())
        ->where('status', 'failed')
        ->count();

    $totalPayinTransactionsCount = $totalPayinSuccessCount + $totalPayinFailedCount;

    $payinSuccessRate = $totalPayinTransactionsCount > 0
        ? ($totalPayinSuccessCount / $totalPayinTransactionsCount) * 100
        : 0;
    return $payinSuccessRate;
}
function payinJCFunc($id){
    
    $jcPayinAmount = Transaction::where('user_id', $id)
        ->where('status', 'success')
        ->where('txn_type', 'jazzcash')
        ->whereDate('created_at', Carbon::today())
        ->sum('amount');
    return $jcPayinAmount;
}
function payinEPFunc($id){
    
    $epPayinAmount = Transaction::where('user_id', $id)
        ->where('status', 'success')
        ->where('txn_type', 'easypaisa')
        ->whereDate('created_at', Carbon::today())
        ->sum('amount');
    return $epPayinAmount;
}
function payoutJCFunc($id){
    
    $jcpayoutAmount = Payout::where('user_id', $id)
        ->where('status', 'success')
        ->where('transaction_type', 'jazzcash')
        ->whereDate('created_at', Carbon::today())
        ->sum('amount');
    return $jcpayoutAmount;
}
function payoutEPFunc($id){
    $epPayoutAmount = Settlement::where('user_id', $id)->whereDate('date', today())->value('ep_payout');
    return $epPayoutAmount;
}
