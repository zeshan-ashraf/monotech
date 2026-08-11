<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\ExportPayinDataTable;
use App\Exports\ExportPayinExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            $request->validate([
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ]);
        }

        return $dataTable->render('admin.export_payin.list', get_defined_vars());
    }

    public function export(Request $request): BinaryFileResponse|StreamedResponse
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'format' => ['required', 'in:csv,xlsx'],
        ]);

        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $usersById = ExportPayinDataTable::resolveAllUserNames();
        $filename = 'export_payin_' . date('YmdHis');

        // CSV: stream row-by-row (avoids loading the full set / nginx 502).
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

        // Excel: collect up to EXPORT_MAX_ROWS (prefer CSV for very large ranges).
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
