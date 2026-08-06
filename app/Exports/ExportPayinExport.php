<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExportPayinExport implements FromCollection, WithHeadings, WithMapping
{
    /** @var array<int, string> */
    private array $usersById;

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
            'Order Id',
            'Client Name',
            'Trans Id',
            'Phone',
            'Trans Ref No',
            'Trans Type',
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
        return [
            $row->orderId,
            $this->usersById[$row->user_id] ?? '-',
            $row->transactionId,
            $row->phone,
            $row->txn_ref_no,
            $row->txn_type,
            $row->amount,
            $row->status,
            $row->created_at ? $row->created_at->format('d-m-Y H:i:s') : 'N/A',
        ];
    }
}
