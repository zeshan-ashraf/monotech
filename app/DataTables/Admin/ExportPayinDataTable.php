<?php

namespace App\DataTables\Admin;

use App\Models\{ArcheivePayout, ArcheiveTransaction, BackupTransaction, Payout, Transaction, User};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Services\DataTable;

/**
 * Export Payin DataTable (self-contained slice companion).
 *
 * Slice files:
 * - app/Http/Controllers/Admin/ExportPayinController.php
 * - app/DataTables/Admin/ExportPayinDataTable.php
 * - app/Exports/ExportPayinExport.php
 * - resources/views/admin/export_payin/list.blade.php
 * - database/seeders/ExportPayinAndApiDocPermissionSeeder.php
 * - routes (admin.export_payin.*) + sidebar @can('Export Payin')
 */
class ExportPayinDataTable extends DataTable
{
    /** @var int On-screen preview cap (DataTables loads the full collection in memory). */
    public const DISPLAY_LIMIT = 1000;

    /** @var list<string> */
    public const STATUSES = ['failed', 'success', 'pending', 'reverse'];

    /** @var list<string> */
    public const DATE_RANGES = ['today', 'yesterday', 'this_week', 'this_month', 'last_month', 'this_year', 'custom'];

    public const AMOUNT_MIN = 1;

    public const AMOUNT_MAX = 50000;

    /** @var array<int, string> */
    private array $usersById = [];

    public function dataTable($query)
    {
        $usersById = $this->usersById;

        return datatables()
            ->collection($query)
            ->addColumn('client_name', function ($transaction) use ($usersById) {
                return $usersById[$transaction->user_id] ?? '-';
            })
            ->editColumn('status', function ($query) {
                $reason = $query->pp_message;
                $type = $query->status;

                return view('admin.transaction.badge', get_defined_vars());
            })
            ->editColumn('txn_type', function ($query) {
                return static::formatNetwork($query->txn_type ?? null);
            })
            ->editColumn('created_at', function ($query) {
                return $query->created_at ? $query->created_at->format('d-m-y H:i:s') : 'N/A';
            })
            ->editColumn('amount', function ($query) {
                return $query->amount;
            })
            ->rawColumns(['status']);
    }

    public function query(): Collection
    {
        if (!request()->params) {
            return collect();
        }

        if (!static::hasRequiredDateRange()) {
            return collect();
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $results = static::isPayoutRequest()
            ? static::searchPayoutResults(self::DISPLAY_LIMIT)
            : static::searchResults(self::DISPLAY_LIMIT);
        $this->usersById = static::resolveUsersById($results);

        return $results;
    }

    /**
     * Shared search used by list preview.
     * Searches live → archive → backup and merges until $limit is reached (null = all).
     */
    public static function searchResults(?int $limit = self::DISPLAY_LIMIT): Collection
    {
        $filters = static::resolveFilters();

        if (!$filters['start_date'] || !$filters['end_date']) {
            return collect();
        }

        $results = $filters['order_id']
            ? static::searchByOrderReference($filters, $limit)
            : static::searchWithFilters($filters, 'exact', $limit);

        return $results->sortByDesc('created_at')->values();
    }

    /**
     * Aggregate counts/amounts across live + archive + backup (same filters as list).
     * Status filter is ignored here so we can build both grand totals and per-status cards.
     *
     * @return array{
     *     date_label: string,
     *     selected_status: ?string,
     *     show_sr: bool,
     *     show_status_breakdown: bool,
     *     total_payin: float,
     *     total_orders: int,
     *     success_rate: float,
     *     by_status: array<string, array{count: int, amount: float}>
     * }
     */
    public static function summaryStats(): array
    {
        if (static::isPayoutRequest()) {
            return static::payoutSummaryStats();
        }

        $filters = static::resolveFilters();
        $selectedStatus = $filters['status'];

        $byStatus = [];
        foreach (self::STATUSES as $status) {
            $byStatus[$status] = ['count' => 0, 'amount' => 0.0];
        }

        // Only scan every status when the Status filter is All (breakdown cards).
        // A specific status (e.g. success) must stay in the WHERE so indexes can be used.
        $aggregateFilters = $filters;
        if ($selectedStatus !== null) {
            $aggregateFilters['status'] = $selectedStatus;
        } else {
            $aggregateFilters['status'] = null;
        }

        if ($filters['start_date'] && $filters['end_date']) {
            foreach (static::sources() as $source) {
                $rows = static::applySearchFilters($source['model']::query(), $aggregateFilters, 'exact')
                    ->toBase()
                    ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total')
                    ->groupBy('status')
                    ->get();

                foreach ($rows as $row) {
                    $key = strtolower((string) $row->status);
                    if (!isset($byStatus[$key])) {
                        $byStatus[$key] = ['count' => 0, 'amount' => 0.0];
                    }
                    $byStatus[$key]['count'] += (int) $row->cnt;
                    $byStatus[$key]['amount'] += (float) $row->total;
                }
            }
        }

        $totalOrdersAll = (int) collect($byStatus)->sum('count');
        $successCount = (int) ($byStatus['success']['count'] ?? 0);
        $successAmount = (float) ($byStatus['success']['amount'] ?? 0);

        $dateLabel = '';
        if ($filters['start_date'] && $filters['end_date']) {
            $dateLabel = Carbon::parse($filters['start_date'])->format('d-m-Y')
                . ' to '
                . Carbon::parse($filters['end_date'])->format('d-m-Y');
        }

        if ($selectedStatus === null) {
            return [
                'date_label' => $dateLabel,
                'selected_status' => null,
                'show_sr' => true,
                'show_status_breakdown' => true,
                'total_payin' => $successAmount,
                'total_orders' => $totalOrdersAll,
                'success_rate' => $totalOrdersAll > 0
                    ? round(($successCount / $totalOrdersAll) * 100, 2)
                    : 0.0,
                'by_status' => $byStatus,
            ];
        }

        $statusCount = (int) ($byStatus[$selectedStatus]['count'] ?? 0);
        $statusAmount = (float) ($byStatus[$selectedStatus]['amount'] ?? 0);

        return [
            'date_label' => $dateLabel,
            'selected_status' => $selectedStatus,
            'show_sr' => false,
            'show_status_breakdown' => false,
            'total_payin' => $statusAmount,
            'total_orders' => $statusCount,
            'success_rate' => 0.0,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Stream all matching rows for export (live → archive → backup, no row cap).
     * Yields model instances; caller should not retain the full set in memory.
     *
     * @return \Generator<int, object>
     */
    public static function exportRowCursor(): \Generator
    {
        if (static::isPayoutRequest()) {
            yield from static::exportPayoutRowCursor();

            return;
        }

        $filters = static::resolveFilters();

        if (!$filters['start_date'] || !$filters['end_date']) {
            return;
        }

        $matchModes = $filters['order_id']
            ? ['exact', 'prefix', 'contains']
            : ['exact'];

        foreach ($matchModes as $matchMode) {
            $modeEmitted = 0;

            foreach (static::sources() as $source) {
                $query = static::applySearchFilters($source['model']::query(), $filters, $matchMode)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id');

                foreach ($query->cursor() as $row) {
                    $row->table_type = $source['type'];
                    yield $row;
                    $modeEmitted++;
                }
            }

            // For order_id: stop after the first match mode that finds rows.
            if ($filters['order_id'] && $modeEmitted > 0) {
                return;
            }
        }
    }

    public static function resolveUsersById(Collection $results): array
    {
        return User::query()
            ->whereIn('id', $results->pluck('user_id')->filter()->unique())
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<int, string> */
    public static function resolveAllUserNames(): array
    {
        return User::query()->pluck('name', 'id')->all();
    }

    public static function hasRequiredDateRange(): bool
    {
        $dates = static::resolvePresetDates();

        return !empty($dates['start_date']) && !empty($dates['end_date']);
    }

    /**
     * Normalize date_range on the incoming request and fill start/end for presets.
     */
    public static function applyIncomingDateRange(Request $request): void
    {
        $preset = strtolower(trim((string) $request->input('date_range', '')));

        if ($preset === '' && ($request->filled('start_date') || $request->filled('end_date'))) {
            $preset = 'custom';
        } elseif ($preset === '' || !in_array($preset, self::DATE_RANGES, true)) {
            $preset = 'today';
        }

        $request->merge(['date_range' => $preset]);

        if ($preset !== 'custom') {
            $request->merge(static::presetDates($preset));
        }
    }

    /**
     * @return array{start_date: ?string, end_date: ?string}
     */
    public static function resolvePresetDates(?string $preset = null): array
    {
        $preset = strtolower((string) ($preset ?? request()->input('date_range', 'today')));

        if (!in_array($preset, self::DATE_RANGES, true)) {
            $preset = 'today';
        }

        if ($preset === 'custom') {
            return [
                'start_date' => request()->filled('start_date')
                    ? Carbon::parse(request()->start_date)->toDateString()
                    : null,
                'end_date' => request()->filled('end_date')
                    ? Carbon::parse(request()->end_date)->toDateString()
                    : null,
            ];
        }

        return static::presetDates($preset);
    }

    /**
     * @return array{start_date: string, end_date: string}
     */
    public static function presetDates(string $preset): array
    {
        $today = Carbon::today();

        return match ($preset) {
            'yesterday' => [
                'start_date' => $today->copy()->subDay()->toDateString(),
                'end_date' => $today->copy()->subDay()->toDateString(),
            ],
            'this_week' => [
                'start_date' => $today->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                'end_date' => $today->toDateString(),
            ],
            'this_month' => [
                'start_date' => $today->copy()->startOfMonth()->toDateString(),
                'end_date' => $today->toDateString(),
            ],
            'last_month' => [
                'start_date' => $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'end_date' => $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'this_year' => [
                'start_date' => $today->copy()->startOfYear()->toDateString(),
                'end_date' => $today->toDateString(),
            ],
            default => [
                'start_date' => $today->toDateString(),
                'end_date' => $today->toDateString(),
            ],
        };
    }

    public static function isPayoutRequest(): bool
    {
        return strtolower((string) request()->input('transaction_type', 'payin')) === 'payout';
    }

    private static function searchByOrderReference(array $filters, ?int $limit): Collection
    {
        foreach (['exact', 'prefix', 'contains'] as $matchMode) {
            $results = static::searchWithFilters($filters, $matchMode, $limit);

            if ($results->isNotEmpty()) {
                return $results;
            }
        }

        return collect();
    }

    private static function searchWithFilters(
        array $filters,
        string $orderMatchMode,
        ?int $limit
    ): Collection {
        $results = collect();

        foreach (static::sources() as $source) {
            $remaining = $limit !== null ? $limit - $results->count() : null;

            if ($remaining !== null && $remaining <= 0) {
                break;
            }

            $query = static::applySearchFilters($source['model']::query(), $filters, $orderMatchMode)
                ->select([
                    'id',
                    'user_id',
                    'orderId',
                    'transactionId',
                    'phone',
                    'txn_ref_no',
                    'txn_type',
                    'amount',
                    'status',
                    'created_at',
                    'pp_message',
                ])
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            if ($remaining !== null) {
                $query->limit($remaining);
            }

            foreach ($query->get() as $row) {
                $row->table_type = $source['type'];
                $results->push($row);
            }
        }

        return $results;
    }

    /**
     * @return list<array{model: class-string, type: string}>
     */
    private static function sources(): array
    {
        return [
            ['model' => Transaction::class, 'type' => 'transactions'],
            ['model' => ArcheiveTransaction::class, 'type' => 'archeive_transactions'],
            ['model' => BackupTransaction::class, 'type' => 'backup_transactions'],
        ];
    }

    /**
     * @return array{
     *     txn_ref_no: ?string,
     *     phone: ?string,
     *     order_id: ?string,
     *     start_date: ?string,
     *     end_date: ?string,
     *     amount_from: ?float,
     *     amount_to: ?float,
     *     status: ?string,
     *     user_id: ?int,
     *     network: ?string
     * }
     */
    private static function resolveFilters(): array
    {
        $authUser = auth()->user();
        $userId = null;

        // Clients always see only their own rows.
        if ($authUser && $authUser->user_role === 'Client') {
            $userId = (int) $authUser->id;
        } elseif ($authUser && $authUser->user_role === 'Super Admin') {
            $requestedUserId = request()->input('user_id');
            if ($requestedUserId !== null && $requestedUserId !== '' && $requestedUserId !== 'All') {
                $userId = (int) $requestedUserId;
            }
        }

        $status = request()->has('status')
            ? static::trimFilter('status')
            : 'success';
        $allowedStatuses = self::STATUSES;
        if ($status !== null && !in_array($status, $allowedStatuses, true)) {
            $status = null;
        }

        $network = strtolower((string) (static::trimFilter('network') ?? ''));
        if (!in_array($network, ['jazzcash', 'easypaisa'], true)) {
            $network = null;
        }

        $dates = static::resolvePresetDates();
        $amountRange = static::resolveAmountRange();

        return [
            'txn_ref_no' => static::trimFilter('transaction_Id'),
            'phone' => static::trimFilter('phone'),
            'order_id' => static::trimFilter('order_id'),
            'start_date' => $dates['start_date'],
            'end_date' => $dates['end_date'],
            'amount_from' => $amountRange['amount_from'],
            'amount_to' => $amountRange['amount_to'],
            'status' => $status,
            'user_id' => $userId,
            'network' => $network,
        ];
    }

    /**
     * @return array{amount_from: ?float, amount_to: ?float}
     */
    public static function resolveAmountRange(): array
    {
        $from = request()->filled('amount_from') ? (float) request()->amount_from : null;
        $to = request()->filled('amount_to') ? (float) request()->amount_to : null;

        if ($from === null && $to === null) {
            return ['amount_from' => null, 'amount_to' => null];
        }

        $from = $from ?? (float) self::AMOUNT_MIN;
        $to = $to ?? (float) self::AMOUNT_MAX;
        $from = max((float) self::AMOUNT_MIN, min((float) self::AMOUNT_MAX, $from));
        $to = max((float) self::AMOUNT_MIN, min((float) self::AMOUNT_MAX, $to));

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        // Full slider span is "any amount". Applying BETWEEN 1 AND 50000
        // prevents MySQL from using created_at/status indexes.
        if ((int) $from === self::AMOUNT_MIN && (int) $to === self::AMOUNT_MAX) {
            return ['amount_from' => null, 'amount_to' => null];
        }

        return ['amount_from' => $from, 'amount_to' => $to];
    }

    public static function formatNetwork(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            'jazzcash' => 'JC',
            'easypaisa' => 'EP',
            default => $value !== null && $value !== '' ? (string) $value : '-',
        };
    }

    private static function trimFilter(string $key): ?string
    {
        $value = trim((string) request()->input($key, ''));

        return $value !== '' ? $value : null;
    }

    private static function applySearchFilters(Builder $query, array $filters, string $orderMatchMode = 'exact'): Builder
    {
        return $query
            ->when($filters['user_id'] !== null, function (Builder $q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            })
            ->when($filters['status'], function (Builder $q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when($filters['network'], function (Builder $q) use ($filters) {
                $q->where('txn_type', $filters['network']);
            })
            ->when($filters['txn_ref_no'], function (Builder $q) use ($filters) {
                $q->where('txn_ref_no', 'like', $filters['txn_ref_no'] . '%');
            })
            ->when($filters['phone'], function (Builder $q) use ($filters) {
                $q->where('phone', 'like', $filters['phone'] . '%');
            })
            ->when($filters['order_id'], function (Builder $q) use ($filters, $orderMatchMode) {
                static::applyOrderIdFilter($q, $filters['order_id'], $orderMatchMode);
            })
            ->when($filters['start_date'] && $filters['end_date'], function (Builder $q) use ($filters) {
                $q->whereBetween('created_at', [
                    $filters['start_date'] . ' 00:00:00',
                    $filters['end_date'] . ' 23:59:59',
                ]);
            })
            ->when($filters['amount_from'] !== null && $filters['amount_to'] !== null, function (Builder $q) use ($filters) {
                $q->whereBetween('amount', [$filters['amount_from'], $filters['amount_to']]);
            });
    }

    private static function applyOrderIdFilter(Builder $query, string $term, string $matchMode): void
    {
        if ($matchMode === 'exact') {
            $query->where('orderId', $term);

            return;
        }

        $pattern = $matchMode === 'prefix' ? $term . '%' : '%' . $term . '%';
        $query->where('orderId', 'like', $pattern);
    }

    /**
     * Combined payout preview (payouts UNION ALL archeive_payouts), capped like Payin.
     */
    public static function searchPayoutResults(?int $limit = self::DISPLAY_LIMIT): Collection
    {
        $filters = static::resolveFilters();

        if (!$filters['start_date'] || !$filters['end_date']) {
            return collect();
        }

        if ($filters['order_id']) {
            foreach (['exact', 'prefix', 'contains'] as $matchMode) {
                $results = static::payoutUnionResults($filters, $matchMode, $limit);

                if ($results->isNotEmpty()) {
                    return $results;
                }
            }

            return collect();
        }

        return static::payoutUnionResults($filters, 'exact', $limit);
    }

    /**
     * Stream filtered payout rows from both tables, globally sorted by created_at DESC.
     * Uses a single UNION ALL query so MySQL can stream without loading either table into PHP.
     *
     * @return \Generator<int, object>
     */
    public static function exportPayoutRowCursor(): \Generator
    {
        $filters = static::resolveFilters();

        if (!$filters['start_date'] || !$filters['end_date']) {
            return;
        }

        $matchModes = $filters['order_id']
            ? ['exact', 'prefix', 'contains']
            : ['exact'];

        foreach ($matchModes as $matchMode) {
            $modeEmitted = 0;

            foreach (static::payoutUnionQuery($filters, $matchMode, null)->cursor() as $row) {
                yield static::hydratePayoutRow($row);
                $modeEmitted++;
            }

            if ($filters['order_id'] && $modeEmitted > 0) {
                return;
            }
        }
    }

    /**
     * @return array{
     *     date_label: string,
     *     selected_status: ?string,
     *     show_sr: bool,
     *     show_status_breakdown: bool,
     *     total_payin: float,
     *     total_orders: int,
     *     success_rate: float,
     *     by_status: array<string, array{count: int, amount: float}>
     * }
     */
    private static function payoutSummaryStats(): array
    {
        $filters = static::resolveFilters();
        $selectedStatus = $filters['status'];

        $byStatus = [];
        foreach (self::STATUSES as $status) {
            $byStatus[$status] = ['count' => 0, 'amount' => 0.0];
        }

        $aggregateFilters = $filters;
        if ($selectedStatus !== null) {
            $aggregateFilters['status'] = $selectedStatus;
        } else {
            $aggregateFilters['status'] = null;
        }

        if ($filters['start_date'] && $filters['end_date']) {
            foreach (static::payoutSources() as $source) {
                $rows = static::applyPayoutSearchFilters($source['model']::query(), $aggregateFilters, 'exact')
                    ->toBase()
                    ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total')
                    ->groupBy('status')
                    ->get();

                foreach ($rows as $row) {
                    $key = strtolower((string) $row->status);
                    if (!isset($byStatus[$key])) {
                        $byStatus[$key] = ['count' => 0, 'amount' => 0.0];
                    }
                    $byStatus[$key]['count'] += (int) $row->cnt;
                    $byStatus[$key]['amount'] += (float) $row->total;
                }
            }
        }

        $totalOrdersAll = (int) collect($byStatus)->sum('count');
        $successCount = (int) ($byStatus['success']['count'] ?? 0);
        $successAmount = (float) ($byStatus['success']['amount'] ?? 0);

        $dateLabel = '';
        if ($filters['start_date'] && $filters['end_date']) {
            $dateLabel = Carbon::parse($filters['start_date'])->format('d-m-Y')
                . ' to '
                . Carbon::parse($filters['end_date'])->format('d-m-Y');
        }

        if ($selectedStatus === null) {
            return [
                'date_label' => $dateLabel,
                'selected_status' => null,
                'show_sr' => true,
                'show_status_breakdown' => true,
                'total_payin' => $successAmount,
                'total_orders' => $totalOrdersAll,
                'success_rate' => $totalOrdersAll > 0
                    ? round(($successCount / $totalOrdersAll) * 100, 2)
                    : 0.0,
                'by_status' => $byStatus,
            ];
        }

        $statusCount = (int) ($byStatus[$selectedStatus]['count'] ?? 0);
        $statusAmount = (float) ($byStatus[$selectedStatus]['amount'] ?? 0);

        return [
            'date_label' => $dateLabel,
            'selected_status' => $selectedStatus,
            'show_sr' => false,
            'show_status_breakdown' => false,
            'total_payin' => $statusAmount,
            'total_orders' => $statusCount,
            'success_rate' => 0.0,
            'by_status' => $byStatus,
        ];
    }

    /**
     * @return list<array{model: class-string, type: string}>
     */
    private static function payoutSources(): array
    {
        return [
            ['model' => Payout::class, 'type' => 'payouts'],
            ['model' => ArcheivePayout::class, 'type' => 'archeive_payouts'],
        ];
    }

    private static function payoutUnionResults(array $filters, string $orderMatchMode, ?int $limit): Collection
    {
        return collect(static::payoutUnionQuery($filters, $orderMatchMode, $limit)->get())
            ->map(fn ($row) => static::hydratePayoutRow($row))
            ->values();
    }

    /**
     * UNION ALL of payouts + archeive_payouts, globally ordered by created_at DESC.
     * When $limit is set, each source is also limited first so the preview does not sort the full history.
     */
    private static function payoutUnionQuery(array $filters, string $orderMatchMode, ?int $limit): QueryBuilder
    {
        $live = static::payoutBaseQuery('payouts', $filters, $orderMatchMode);
        $archive = static::payoutBaseQuery('archeive_payouts', $filters, $orderMatchMode);

        if ($limit !== null) {
            $live->orderByDesc('created_at')->orderByDesc('id')->limit($limit);
            $archive->orderByDesc('created_at')->orderByDesc('id')->limit($limit);
        }

        $union = DB::query()
            ->fromSub($live, 'payouts_live')
            ->unionAll(DB::query()->fromSub($archive, 'payouts_archive'));

        $combined = DB::query()
            ->fromSub($union, 'combined_payouts')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($limit !== null) {
            $combined->limit($limit);
        }

        return $combined;
    }

    private static function payoutBaseQuery(string $table, array $filters, string $orderMatchMode): QueryBuilder
    {
        $query = DB::table($table)->select([
            'id',
            'user_id',
            'orderId',
            DB::raw('transaction_id as `transactionId`'),
            'phone',
            DB::raw('transaction_reference as `txn_ref_no`'),
            DB::raw('transaction_type as `txn_type`'),
            'amount',
            'status',
            'created_at',
            DB::raw('message as `pp_message`'),
            DB::raw("'" . $table . "' as `table_type`"),
        ]);

        return static::applyPayoutSearchFilters($query, $filters, $orderMatchMode);
    }

    private static function applyPayoutSearchFilters($query, array $filters, string $orderMatchMode = 'exact')
    {
        return $query
            ->when($filters['user_id'] !== null, function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            })
            ->when($filters['status'], function ($q) use ($filters) {
                $q->where('status', $filters['status']);
            })
            ->when($filters['network'], function ($q) use ($filters) {
                $q->where('transaction_type', $filters['network']);
            })
            ->when($filters['txn_ref_no'], function ($q) use ($filters) {
                $q->where('transaction_reference', 'like', $filters['txn_ref_no'] . '%');
            })
            ->when($filters['phone'], function ($q) use ($filters) {
                $q->where('phone', 'like', $filters['phone'] . '%');
            })
            ->when($filters['order_id'], function ($q) use ($filters, $orderMatchMode) {
                static::applyPayoutOrderIdFilter($q, $filters['order_id'], $orderMatchMode);
            })
            ->when($filters['start_date'] && $filters['end_date'], function ($q) use ($filters) {
                $q->whereBetween('created_at', [
                    $filters['start_date'] . ' 00:00:00',
                    $filters['end_date'] . ' 23:59:59',
                ]);
            })
            ->when($filters['amount_from'] !== null && $filters['amount_to'] !== null, function ($q) use ($filters) {
                $q->whereBetween('amount', [$filters['amount_from'], $filters['amount_to']]);
            });
    }

    private static function applyPayoutOrderIdFilter($query, string $term, string $matchMode): void
    {
        if ($matchMode === 'exact') {
            $query->where('orderId', $term);

            return;
        }

        $pattern = $matchMode === 'prefix' ? $term . '%' : '%' . $term . '%';
        $query->where('orderId', 'like', $pattern);
    }

    private static function hydratePayoutRow(object $row): object
    {
        if (isset($row->created_at) && $row->created_at && !($row->created_at instanceof Carbon)) {
            $row->created_at = Carbon::parse($row->created_at);
        } elseif (empty($row->created_at)) {
            $row->created_at = null;
        }

        return $row;
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('dataTable')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('<"row align-items-center"<"col-md-2" l><"col-md-6" B><"col-md-4"f>><"table-responsive my-3" rt><"row align-items-center" <"col-md-6" i><"col-md-4" p>><"clear">')
            ->parameters([
                'buttons' => [],
                'processing' => true,
                'autoWidth' => false,
                'lengthChange' => true,
                'searching' => true,
                'ordering' => false,
                'order' => [],
                'drawCallback' => 'function () {}',
            ]);
    }

    protected function getColumns()
    {
        return [
            ['data' => 'orderId', 'name' => 'orderId', 'title' => 'Order Id', 'orderable' => false, 'searchable' => true, 'width' => 30],
            ['data' => 'client_name', 'name' => 'user.name', 'title' => 'Client Name', 'orderable' => false, 'searchable' => true, 'width' => 30],
            ['data' => 'transactionId', 'name' => 'transactionId', 'title' => 'Trans Id', 'orderable' => false, 'searchable' => true, 'width' => 30],
            ['data' => 'phone', 'name' => 'phone', 'title' => 'Phone', 'orderable' => false, 'searchable' => true, 'width' => 30],
            ['data' => 'txn_ref_no', 'name' => 'txn_ref_no', 'title' => 'Trans Ref No', 'orderable' => false, 'searchable' => true, 'width' => 30],
            ['data' => 'txn_type', 'name' => 'txn_type', 'title' => 'Network', 'orderable' => false, 'searchable' => true, 'width' => 30],
            ['data' => 'amount', 'name' => 'amount', 'title' => 'Amount', 'orderable' => false, 'searchable' => true, 'width' => 30],
            ['data' => 'status', 'name' => 'status', 'title' => 'Status', 'orderable' => false, 'searchable' => true, 'width' => 30],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created at', 'orderable' => false, 'searchable' => true, 'width' => 30],
        ];
    }

    protected function filename(): string
    {
        return 'Export_Payin_' . date('YmdHis');
    }
}
