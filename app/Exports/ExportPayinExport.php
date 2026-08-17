<?php

namespace App\Exports;

use App\DataTables\Admin\ExportPayinDataTable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportPayinExport implements FromCollection, WithHeadings, WithMapping
{
    /** @var array<int, string> */
    private array $usersById;

    private int $serial = 0;

    public function __construct(
        private readonly Collection $rows,
        array $usersById = []
    ) {
        $this->usersById = $usersById;
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'Order Id',
            'Client Name',
            'Trans Id',
            'Phone',
            'Trans Ref No',
            'Network',
            'Amount',
            'Status',
            'Created At',
        ];
    }

    /**
     * @param  mixed  $row
     */
    public function map($row): array
    {
        $this->serial++;

        return [
            $this->serial,
            $row->orderId,
            $this->usersById[$row->user_id] ?? '-',
            $row->transactionId,
            $row->phone,
            $row->txn_ref_no,
            ExportPayinDataTable::formatNetwork($row->txn_type ?? null),
            $row->amount,
            $row->status,
            $row->created_at ? $row->created_at->format('d-m-Y H:i:s') : 'N/A',
        ];
    }
}
