<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\ExportPayinDataTable;
use App\Exports\ExportPayinExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

        $rows = ExportPayinDataTable::searchResults(forExport: true);
        $usersById = ExportPayinDataTable::resolveUsersById($rows);
        $export = new ExportPayinExport($rows, $usersById);
        $filename = 'export_payin_' . date('YmdHis');

        if ($request->input('format') === 'csv') {
            return Excel::download($export, $filename . '.csv', ExcelFormat::CSV);
        }

        return Excel::download($export, $filename . '.xlsx', ExcelFormat::XLSX);
    }
}
