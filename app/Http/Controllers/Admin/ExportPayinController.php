<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\ExportPayinDataTable;
use App\Exports\ExportPayinExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export Payin — self-contained slice (copy of Payin Search without row actions).
 *
 * Drop into another Laravel app with:
 * - this controller
 * - ExportPayinDataTable
 * - ExportPayinExport
 * - resources/views/admin/export_payin/list.blade.php
 * - ExportPayinAndApiDocPermissionSeeder
 * - routes + sidebar snippet below
 *
 * Routes:
 *   Route::as('export_payin.')->prefix('export-payin')->group(function () {
 *       Route::get('/list', [ExportPayinController::class, 'list'])->name('list');
 *       Route::get('/export', [ExportPayinController::class, 'export'])->name('export');
 *   });
 *
 * Sidebar:
 *   @can('Export Payin')
 *       <li class="nav-item">
 *           <a href="{{ route('admin.export_payin.list') }}">Export Payin</a>
 *       </li>
 *   @endcan
 *
 * Permission: Export Payin
 */
class ExportPayinController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:Export Payin']);
    }

    public function list(Request $request, ExportPayinDataTable $dataTable)
    {
        if ($request->boolean('params')) {
            ExportPayinDataTable::applyIncomingDateRange($request);

            $request->validate([
                'date_range' => ['required', Rule::in(ExportPayinDataTable::DATE_RANGES)],
                'start_date' => ['required_if:date_range,custom', 'nullable', 'date'],
                'end_date' => ['required_if:date_range,custom', 'nullable', 'date', 'after_or_equal:start_date'],
                'status' => ['nullable', Rule::in(['', 'failed', 'success', 'pending', 'reverse', 'settled'])],
                'user_id' => ['nullable'],
                'transaction_type' => ['nullable', Rule::in(['payin', 'payout'])],
                'network' => ['nullable', Rule::in(['', 'all', 'easypaisa', 'jazzcash'])],
                'amount_from' => ['nullable', 'numeric', 'min:1', 'max:50000'],
                'amount_to' => ['nullable', 'numeric', 'min:1', 'max:50000', 'gte:amount_from'],
            ]);
        }

        $users = collect();
        if (auth()->user()->user_role === 'Super Admin') {
            $users = User::query()
                ->where('active', 1)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $summary = null;
        $isDataTableAjax = $request->ajax() || $request->has('draw');
        if ($request->boolean('params') && ! $isDataTableAjax) {
            @set_time_limit(120);
            $summary = ExportPayinDataTable::summaryStats();
        }

        return $dataTable->render('admin.export_payin.list', get_defined_vars());
    }

    public function export(Request $request): BinaryFileResponse|StreamedResponse
    {
        ExportPayinDataTable::applyIncomingDateRange($request);

        $request->validate([
            'date_range' => ['required', Rule::in(ExportPayinDataTable::DATE_RANGES)],
            'start_date' => ['required_if:date_range,custom', 'nullable', 'date'],
            'end_date' => ['required_if:date_range,custom', 'nullable', 'date', 'after_or_equal:start_date'],
            'format' => ['required', 'in:csv,xlsx'],
            'status' => ['nullable', Rule::in(['', 'failed', 'success', 'pending', 'reverse', 'settled'])],
            'user_id' => ['nullable'],
            'transaction_type' => ['nullable', Rule::in(['payin', 'payout'])],
            'network' => ['nullable', Rule::in(['', 'all', 'easypaisa', 'jazzcash'])],
            'amount_from' => ['nullable', 'numeric', 'min:1', 'max:50000'],
            'amount_to' => ['nullable', 'numeric', 'min:1', 'max:50000', 'gte:amount_from'],
        ]);

        // No PHP time cap — large date ranges can take several minutes.
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '0');

        $usersById = ExportPayinDataTable::resolveAllUserNames();
        $filename = (ExportPayinDataTable::isPayoutRequest() ? 'export_payout_' : 'export_payin_') . date('YmdHis');

        // CSV: stream every matching row (no row cap; preferred for large exports).
        if ($request->input('format') === 'csv') {
            $export = new ExportPayinExport(collect(), $usersById);

            return response()->streamDownload(function () use ($export) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $export->headings());

                foreach (ExportPayinDataTable::exportRowCursor() as $row) {
                    fputcsv($handle, $export->map($row));
                }

                fclose($handle);
            }, $filename . '.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        // Excel still buffers in memory — prefer CSV when the range is very large.
        @ini_set('memory_limit', '1024M');
        $rows = new Collection();
        foreach (ExportPayinDataTable::exportRowCursor() as $row) {
            $rows->push($row);
        }

        return Excel::download(
            new ExportPayinExport($rows, $usersById),
            $filename . '.xlsx',
            ExcelFormat::XLSX
        );
    }
}
