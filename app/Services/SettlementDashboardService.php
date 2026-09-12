<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Payout;
use App\Models\PayoutSetting;
use App\Models\Setting;
use App\Models\Settlement;
use App\Models\SurplusAmount;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SettlementDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getPayloadForViewer(User $viewer): array
    {
        $grid = $this->buildGrid($viewer);

        return [
            'generated_at' => now()->toIso8601String(),
            'top_cards' => $this->getTopCards($viewer),
            'surplus' => $this->getSurplus(),
            'rows' => $grid['rows'],
            'totals' => $grid['totals'],
        ];
    }

    /**
     * @return array<string, int|float>
     */
    public function getTopCards(User $viewer): array
    {
        $cards = [
            'sub_stores' => Client::count(),
            'payin_count' => Transaction::count(),
            'payout_count' => Payout::count(),
        ];

        if (in_array($viewer->user_role, ['Super Admin', 'Admin'], true)) {
            $now = Carbon::now();
            $cards['monthly_ep_payin'] = (float) DB::table('settlements')
                ->whereBetween('created_at', [
                    $now->copy()->startOfMonth()->subHours(5),
                    $now->copy()->endOfMonth()->subHours(5),
                ])
                ->sum('ep_payin');
        }

        return $cards;
    }

    /**
     * @return array{jazzcash: float, easypaisa: float}
     */
    public function getSurplus(): array
    {
        $surplus = SurplusAmount::query()->find(1);

        return [
            'jazzcash' => round((float) ($surplus->jazzcash ?? 0), 0),
            'easypaisa' => round((float) ($surplus->easypaisa ?? 0), 0),
        ];
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, totals: array<string, float|int>, payout_setting: Collection, surplusAmount: SurplusAmount|null, totalMonthlyAmount: float}
     */
    public function getViewData(User $viewer): array
    {
        $grid = $this->buildGrid($viewer);
        $now = Carbon::now();

        $totalMonthlyAmount = DB::table('settlements')
            ->whereBetween('created_at', [
                $now->copy()->startOfMonth()->subHours(5),
                $now->copy()->endOfMonth()->subHours(5),
            ])
            ->sum('ep_payin');

        return [
            'data' => $grid['view_rows'],
            'totals' => $grid['totals'],
            'payout_setting' => PayoutSetting::all(),
            'surplusAmount' => SurplusAmount::query()->find(1),
            'totalMonthlyAmount' => (float) $totalMonthlyAmount,
        ];
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, float|int>, view_rows: array<int, array<string, mixed>>}
     */
    private function buildGrid(User $viewer): array
    {
        $clients = User::query()
            ->where('user_role', 'Client')
            ->where('active', 1)
            ->get();

        $clientIds = $clients->pluck('id')->all();
        $today = today();
        $yesterday = today()->subDay();

        $todaySettlements = Settlement::query()
            ->whereIn('user_id', $clientIds)
            ->whereDate('date', $today)
            ->get()
            ->keyBy('user_id');

        $yesterdayBalances = Settlement::query()
            ->whereIn('user_id', $clientIds)
            ->whereDate('date', $yesterday)
            ->pluck('closing_bal', 'user_id');

        $settings = Setting::query()
            ->whereIn('user_id', $clientIds)
            ->get()
            ->keyBy('user_id');

        $data = [];

        foreach ($clients as $client) {
            $userId = $client->id;
            $settlement = $todaySettlements->get($userId);
            $setting = $settings->get($userId);

            $prevBal = (float) ($yesterdayBalances[$userId] ?? 0);
            $epPayinAmount = (float) ($settlement->ep_payin ?? 0);
            $jcPayinAmount = (float) ($settlement->jc_payin ?? 0);
            $epPayoutAmount = (float) ($settlement->ep_payout ?? 0);
            $jcPayoutAmount = (float) ($settlement->jc_payout ?? 0);
            $revCln = (float) ($settlement->rev_cln ?? 0);
            $reverseAmount = (float) ($settlement->reverse_amount ?? 0);
            $payinSuccess = $epPayinAmount + $jcPayinAmount;
            $ibftAmount = (float) ($settlement->ibft_amount ?? 0);
            $payoutSuccess = $epPayoutAmount + $jcPayoutAmount + $ibftAmount;
            $prevUsdt = (float) ($settlement->usdt ?? 0);
            $prevWalletTrans = (float) ($settlement->wallet_transfer ?? 0);
            $unsettledAmount = (float) ($settlement->closing_bal ?? 0);
            $payoutBalance = (float) ($setting->payout_balance ?? 0);
            $balance = $unsettledAmount - $payoutBalance;

            $data[] = [
                'user' => $client,
                'prev_balance' => $prevBal,
                'jc_payin' => $jcPayinAmount,
                'ep_payin' => $epPayinAmount,
                'reverse_amount' => $reverseAmount,
                'total_payin' => $payinSuccess,
                'jc_payout' => $jcPayoutAmount,
                'ep_payout' => $epPayoutAmount,
                'total_payout' => $payoutSuccess,
                'prev_usdt' => $prevUsdt,
                'wallet_transfer' => $prevWalletTrans,
                'unsettled_amount' => $unsettledAmount,
                'unsettled_amount_balance' => $balance,
                'assigned_amount' => $setting,
                'setting' => $setting,
                'set_id' => $settlement->id ?? 0,
                'rev_cln' => $revCln,
                'ibft_amount' => $ibftAmount,
            ];
        }

        $totals = [
            'prev_balance' => 0,
            'jc_payin' => 0,
            'ep_payin' => 0,
            'reverse_amount' => 0,
            'total_payin' => 0,
            'jc_payout' => 0,
            'ep_payout' => 0,
            'total_payout' => 0,
            'prev_usdt' => 0,
            'wallet_transfer' => 0,
            'unsettled_amount' => 0,
            'unsettled_amount_balance' => 0,
            'assigned_jc' => 0,
            'assigned_ep' => 0,
            'assigned_payout' => 0,
            'total_rev_cln' => 0,
            'total_ibft_amount' => 0,
        ];

        foreach ($data as $item) {
            $totals['prev_balance'] += $item['prev_balance'] ?? 0;
            $totals['jc_payin'] += $item['jc_payin'] ?? 0;
            $totals['ep_payin'] += $item['ep_payin'] ?? 0;
            $totals['reverse_amount'] += $item['reverse_amount'] ?? 0;
            $totals['total_payin'] += $item['total_payin'] ?? 0;
            $totals['jc_payout'] += $item['jc_payout'] ?? 0;
            $totals['ep_payout'] += $item['ep_payout'] ?? 0;
            $totals['total_payout'] += $item['total_payout'] ?? 0;
            $totals['prev_usdt'] += $item['prev_usdt'] ?? 0;
            $totals['wallet_transfer'] += $item['wallet_transfer'] ?? 0;
            $totals['unsettled_amount'] += $item['unsettled_amount'] ?? 0;
            $totals['unsettled_amount_balance'] += $item['unsettled_amount_balance'] ?? 0;
            $totals['assigned_jc'] += $item['assigned_amount']->jazzcash ?? 0;
            $totals['assigned_ep'] += $item['assigned_amount']->easypaisa ?? 0;
            $totals['assigned_payout'] += $item['assigned_amount']->payout_balance ?? 0;
            $totals['total_rev_cln'] += $item['rev_cln'] ?? 0;
            $totals['total_ibft_amount'] += $item['ibft_amount'] ?? 0;
        }

        $viewRows = collect($data)
            ->sortBy(fn ($item) => $item['user']->id == 24 ? 1 : 0)
            ->values()
            ->all();

        $pollRows = collect($viewRows)
            ->filter(function ($item) use ($viewer) {
                $user = $item['user'];

                return in_array($viewer->user_role, ['Super Admin', 'Manager'], true)
                    || $viewer->id === $user->id;
            })
            ->map(fn ($item) => $this->serializeRowMetrics($item, $viewer))
            ->values()
            ->all();

        return [
            'rows' => $pollRows,
            'totals' => $this->serializeTotals($totals, $viewer),
            'view_rows' => $viewRows,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function serializeRowMetrics(array $item, User $viewer): array
    {
        $user = $item['user'];
        $metrics = [
            'user_id' => $user->id,
            'unsettled_amount' => round($item['unsettled_amount'], 0),
            'assigned_payout' => round((float) ($item['assigned_amount']->payout_balance ?? 0), 0),
        ];

        if (
            $viewer->user_role === 'Super Admin'
            || $viewer->id === $user->id
        ) {
            $metrics += [
                'prev_balance' => round($item['prev_balance'], 0),
                'jc_payin' => round($item['jc_payin'], 0),
                'ep_payin' => round($item['ep_payin'], 0),
                'total_payin' => round($item['total_payin'], 0),
                'reverse_amount' => round($item['reverse_amount'], 0),
                'jc_payout' => round($item['jc_payout'], 0),
                'ibft_amount' => round($item['ibft_amount'], 0),
                'ep_payout' => round($item['ep_payout'], 0),
                'total_payout' => round($item['total_payout'], 0),
                'prev_usdt' => round($item['prev_usdt'], 0),
                'wallet_transfer' => round($item['wallet_transfer'], 0),
            ];
        }

        if (
            in_array($viewer->user_role, ['Super Admin', 'Manager'], true)
            || $viewer->id === $user->id
        ) {
            $metrics['unsettled_amount_balance'] = round($item['unsettled_amount_balance'], 0);
        }

        if (in_array($viewer->user_role, ['Super Admin', 'Manager'], true)) {
            $metrics['rev_cln'] = round($item['rev_cln'], 0);
        }

        return $metrics;
    }

    /**
     * @param  array<string, float|int>  $totals
     * @return array<string, float|int>
     */
    private function serializeTotals(array $totals, User $viewer): array
    {
        if (!in_array($viewer->user_role, ['Super Admin', 'Manager'], true)) {
            return [];
        }

        $serialized = [
            'unsettled_amount' => round($totals['unsettled_amount'], 0),
            'assigned_payout' => round($totals['assigned_payout'], 0),
            'unsettled_amount_balance' => round($totals['unsettled_amount_balance'], 0),
            'total_rev_cln' => round($totals['total_rev_cln'], 0),
        ];

        if ($viewer->user_role === 'Super Admin') {
            $serialized = array_merge([
                'prev_balance' => round($totals['prev_balance'], 0),
                'jc_payin' => round($totals['jc_payin'], 0),
                'ep_payin' => round($totals['ep_payin'], 0),
                'total_payin' => round($totals['total_payin'], 0),
                'reverse_amount' => round($totals['reverse_amount'], 0),
                'jc_payout' => round($totals['jc_payout'], 0),
                'total_ibft_amount' => round($totals['total_ibft_amount'], 0),
                'ep_payout' => round($totals['ep_payout'], 0),
                'total_payout' => round($totals['total_payout'], 0),
                'prev_usdt' => round($totals['prev_usdt'], 0),
                'wallet_transfer' => round($totals['wallet_transfer'], 0),
            ], $serialized);
        }

        return $serialized;
    }
}
