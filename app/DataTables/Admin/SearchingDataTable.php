<?php

namespace App\DataTables\Admin;

use App\Models\{ArcheiveTransaction, BackupTransaction, Transaction, User};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Yajra\DataTables\Services\DataTable;

class SearchingDataTable extends DataTable
{
    /** @var int Max rows fetched per source table to cap scan cost on archive/backup. */
    private const PER_TABLE_LIMIT = 100;

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
            })
            ->editColumn('detail', function ($query) {
                $user = auth()->user();
                $buttons = '';
                $buttons .= '<a href="' . route('admin.searching.callback.send', $query->id) . '" class="btn btn-success btn-sm">Send Callback</a> ';
                $buttons .= '<a href="' . route('admin.jazzcash.status-inquiry', ['id' => $query->txn_ref_no, 'type' => $query->txn_type]) . '" class="btn btn-primary btn-sm mt-1">Inquiry</a>';

                if ($user && method_exists($user, 'can') && $user->can('Reverse Transactions') && $query->status == 'success') {
                    $reverseRequested = $query->reverse_requested_at ?? null;

                    if (!$reverseRequested) {
                        $tableType = $query->table_type ?? 'transactions';
                        $buttons .= ' <button class="btn btn-warning btn-sm mt-1 mark-for-reversal-btn" data-id="' . $query->id . '" data-table-type="' . $tableType . '">Mark for Reversal</button>';
                    }
                }

                return $buttons;
            })
            ->editColumn('reverse', function ($query) {
                $user = auth()->user();

                if ($user->user_role == 'Super Admin' && $query->status == 'success') {
                    return '
                        <select class="form-control status-dropdown-reverse mt-1" data-id="' . $query->id . '">
                            <option value="" selected disabled>Select Option..</option>
                            <option value="reverse">Reverse</option>
                        </select>
                    ';
                }

                return '';
            })
            ->rawColumns(['detail', 'reverse']);
    }

    public function query(): Collection
    {
        if (!request()->params) {
            return collect();
        }

        $filters = $this->resolveFilters();
        $results = $filters['order_id']
            ? $this->searchByOrderReference($filters)
            : $this->searchWithFilters($filters, 'exact');

        $this->usersById = User::query()
            ->whereIn('id', $results->pluck('user_id')->filter()->unique())
            ->pluck('name', 'id')
            ->all();

        return $results->sortByDesc('created_at')->values();
    }

    /**
     * Tiered orderId lookup: exact → prefix → contains (last resort).
     * Stops at the first table that returns rows.
     */
    private function searchByOrderReference(array $filters): Collection
    {
        foreach (['exact', 'prefix', 'contains'] as $matchMode) {
            $results = $this->searchWithFilters($filters, $matchMode, stopOnFirstTableWithResults: true);

            if ($results->isNotEmpty()) {
                return $results;
            }
        }

        return collect();
    }

    /**
     * Search live → archive → backup in order. When $stopOnFirstTableWithResults
     * is true, skip remaining tables as soon as any rows are found.
     */
    private function searchWithFilters(
        array $filters,
        string $orderMatchMode,
        bool $stopOnFirstTableWithResults = true
    ): Collection {
        $results = collect();

        foreach ($this->sources() as $source) {
            $rows = $this->applySearchFilters($source['model']::query(), $filters, $orderMatchMode)
                ->orderByDesc('created_at')
                ->limit(self::PER_TABLE_LIMIT)
                ->get();

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
    private function sources(): array
    {
        return [
            ['model' => Transaction::class, 'type' => 'transactions'],
            ['model' => ArcheiveTransaction::class, 'type' => 'archeive_transactions'],
            ['model' => BackupTransaction::class, 'type' => 'backup_transactions'],
        ];
    }

    /**
     * @return array{txn_ref_no: ?string, phone: ?string, order_id: ?string, start_date: ?string, amount: ?float}
     */
    private function resolveFilters(): array
    {
        return [
            'txn_ref_no' => $this->trimFilter('transaction_Id'),
            'phone' => $this->trimFilter('phone'),
            'order_id' => $this->trimFilter('order_id'),
            'start_date' => request()->start_date
                ? Carbon::parse(request()->start_date)->toDateString()
                : null,
            'amount' => request()->filled('amount_min') ? (float) request()->amount_min : null,
        ];
    }

    private function trimFilter(string $key): ?string
    {
        $value = trim((string) request()->input($key, ''));

        return $value !== '' ? $value : null;
    }

    private function applySearchFilters(Builder $query, array $filters, string $orderMatchMode = 'exact'): Builder
    {
        return $query
            ->when($filters['txn_ref_no'], function (Builder $q) use ($filters) {
                $q->where('txn_ref_no', 'like', $filters['txn_ref_no'] . '%');
            })
            ->when($filters['phone'], function (Builder $q) use ($filters) {
                $q->where('phone', 'like', $filters['phone'] . '%');
            })
            ->when($filters['order_id'], function (Builder $q) use ($filters, $orderMatchMode) {
                $this->applyOrderIdFilter($q, $filters['order_id'], $orderMatchMode);
            })
            ->when($filters['start_date'], function (Builder $q) use ($filters) {
                $q->whereBetween('created_at', [
                    $filters['start_date'] . ' 00:00:00',
                    $filters['start_date'] . ' 23:59:59',
                ]);
            })
            ->when($filters['amount'] !== null, function (Builder $q) use ($filters) {
                $q->where('amount', '=', $filters['amount']);
            });
    }

    /** Form field order_id → column orderId only. */
    private function applyOrderIdFilter(Builder $query, string $term, string $matchMode): void
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
            ->dom('<"row align-items-center"<"col-md-2" l><"col-md-6" B><"col-md-4"f>><"table-responsive my-3" rt><"row align-items-center" <"col-md-6" i><"col-md-6" p>><"clear">')
            ->parameters([
                'buttons' => [
                    'excel',
                ],
                'processing' => true,
                'autoWidth' => false,
                'lengthChange' => false,
                'searching' => false,
                'drawCallback' => 'function () {
                        }',
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
            ['data' => 'detail', 'name' => 'detail', 'title' => 'Action', 'orderable' => false, 'searchable' => false, 'width' => '15%'],
            ['data' => 'reverse', 'name' => 'reverse', 'title' => 'Change Status', 'orderable' => false, 'searchable' => false, 'width' => '15%'],
        ];
    }

    protected function filename(): string
    {
        return 'Export_' . date('YmdHis');
    }

    protected function sheetName(): string
    {
        return 'Yearly Report';
    }
}
