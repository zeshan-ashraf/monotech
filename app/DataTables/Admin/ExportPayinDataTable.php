<?php

namespace App\DataTables\Admin;

use App\Models\{ArcheiveTransaction, BackupTransaction, Transaction, User};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
    /** @var int Max rows for list display and CSV/Excel export (avoids nginx 502 on large ranges). */
    public const PER_TABLE_LIMIT = 100;

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
            ->editColumn('created_at', function ($query) {
                return $query->created_at ? $query->created_at->format('d-m-y H:i:s') : 'N/A';
            })
            ->editColumn('amount', function ($query) {
                return $query->amount . ' PKR';
            });
    }

    public function query(): Collection
    {
        if (!request()->params) {
            return collect();
        }

        if (!static::hasRequiredDateRange()) {
            return collect();
        }

        $results = static::searchResults();
        $this->usersById = static::resolveUsersById($results);

        return $results;
    }

    /**
     * Shared search used by list + CSV/Excel export.
     * Same 100-row cap as Payin Search (live → archive → backup, stop on first hit).
     */
    public static function searchResults(): Collection
    {
        $filters = static::resolveFilters();

        if (!$filters['start_date'] || !$filters['end_date']) {
            return collect();
        }

        $results = $filters['order_id']
            ? static::searchByOrderReference($filters, self::PER_TABLE_LIMIT, true)
            : static::searchWithFilters($filters, 'exact', self::PER_TABLE_LIMIT, true);

        return $results->sortByDesc('created_at')->values();
    }

    public static function resolveUsersById(Collection $results): array
    {
        return User::query()
            ->whereIn('id', $results->pluck('user_id')->filter()->unique())
            ->pluck('name', 'id')
            ->all();
    }

    public static function hasRequiredDateRange(): bool
    {
        return request()->filled('start_date') && request()->filled('end_date');
    }

    private static function searchByOrderReference(
        array $filters,
        ?int $limit,
        bool $stopOnFirstTableWithResults
    ): Collection {
        foreach (['exact', 'prefix', 'contains'] as $matchMode) {
            $results = static::searchWithFilters($filters, $matchMode, $limit, $stopOnFirstTableWithResults);

            if ($results->isNotEmpty()) {
                return $results;
            }
        }

        return collect();
    }

    private static function searchWithFilters(
        array $filters,
        string $orderMatchMode,
        ?int $limit,
        bool $stopOnFirstTableWithResults = true
    ): Collection {
        $results = collect();

        foreach (static::sources() as $source) {
            $query = static::applySearchFilters($source['model']::query(), $filters, $orderMatchMode)
                ->orderByDesc('created_at');

            if ($limit !== null) {
                $query->limit($limit);
            }

            $rows = $query->get();

            foreach ($rows as $row) {
                $row->table_type = $source['type'];
                $results->push($row);
            }

            if ($stopOnFirstTableWithResults && $results->isNotEmpty()) {
                return $results;
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
     * @return array{txn_ref_no: ?string, phone: ?string, order_id: ?string, start_date: ?string, end_date: ?string, amount: ?float}
     */
    private static function resolveFilters(): array
    {
        return [
            'txn_ref_no' => static::trimFilter('transaction_Id'),
            'phone' => static::trimFilter('phone'),
            'order_id' => static::trimFilter('order_id'),
            'start_date' => request()->start_date
                ? Carbon::parse(request()->start_date)->toDateString()
                : null,
            'end_date' => request()->end_date
                ? Carbon::parse(request()->end_date)->toDateString()
                : null,
            'amount' => request()->filled('amount_min') ? (float) request()->amount_min : null,
        ];
    }

    private static function trimFilter(string $key): ?string
    {
        $value = trim((string) request()->input($key, ''));

        return $value !== '' ? $value : null;
    }

    private static function applySearchFilters(Builder $query, array $filters, string $orderMatchMode = 'exact'): Builder
    {
        if (auth()->user()->user_role === 'Client') {
            $query->where('user_id', auth()->id());
        }

        return $query
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
            ->when($filters['amount'] !== null, function (Builder $q) use ($filters) {
                $q->where('amount', $filters['amount']);
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
                'searching' => false,
                'drawCallback' => 'function () {}',
            ]);
    }

    protected function getColumns()
    {
        return [
            ['data' => 'orderId', 'name' => 'orderId', 'title' => 'Order Id', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'client_name', 'name' => 'user.name', 'title' => 'Client Name', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'transactionId', 'name' => 'transactionId', 'title' => 'Trans Id', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'phone', 'name' => 'phone', 'title' => 'Phone', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'txn_ref_no', 'name' => 'txn_ref_no', 'title' => 'Trans Ref No', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'txn_type', 'name' => 'txn_type', 'title' => 'Trans type', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'amount', 'name' => 'amount', 'title' => 'Amount', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'status', 'name' => 'status', 'title' => 'Status', 'orderable' => true, 'searchable' => true, 'width' => 30],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'Created at', 'orderable' => true, 'searchable' => true, 'width' => 30],
        ];
    }

    protected function filename(): string
    {
        return 'Export_Payin_' . date('YmdHis');
    }
}
