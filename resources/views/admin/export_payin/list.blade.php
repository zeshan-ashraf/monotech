@extends('admin.layout.app')
@section('title','Export Transactions')
@push('css')
<link rel="stylesheet" href="{{ asset('admin/assets/dashboard/css/dataTables.bootstrap4.min.css') }}" />
<style>
    .dark-layout .dataTables_wrapper .table.dataTable thead .sorting_asc:before {
        opacity: 0 !important;
    }
    .dark-layout .dataTables_wrapper .table.dataTable thead .sorting_asc:after {
        opacity: 0 !important;
    }
    .dark-layout .dataTables_wrapper .table.dataTable thead .sorting_desc:before {
        opacity: 0 !important;
    }
    .dark-layout .dataTables_wrapper .table.dataTable thead .sorting_desc:after {
        opacity: 0 !important;
    }
    .dark-layout .dataTables_wrapper .table.dataTable thead .sorting:before, .dark-layout .dataTables_wrapper .table.dataTable thead .sorting:after{
        opacity: 0 !important;
    }
</style>
@endpush
@section('content')
<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="content-wrapper container-xxl pt-0 px-0 pb-sm-0 pb-5">
        <div class="content-header row">
        </div>
        <div class="content-body">
            <section id="row-grouping-datatable">
                <div class="row">
                    <div class="col-12">
                        <div class="card w-100">
                            <div class="card-header border-bottom d-flex justify-content-between">
                                <h4 class="card-title text-capitalize">Export Transactions</h4>
                            </div>
                            <div class="card-body mt-3">
                                <div>
                                    <div class="toolbar w-100">
                                        <form method="GET" action="{{ route('admin.export_payin.list') }}">
                                            <input type="hidden" name="params" value="true">
                                            <div class="row g-1 align-items-end">
                                                @if(auth()->user()->user_role === 'Super Admin')
                                                <div class="col-lg-2 col-md-4">
                                                    <div class="form-group">
                                                        <label>Clients</label>
                                                        <select name="user_id" class="form-control">
                                                            <option value="">All</option>
                                                            @foreach(($users ?? []) as $item)
                                                                <option value="{{ $item->id }}" {{ (string) $item->id === (string) request()->user_id ? 'selected' : '' }}>
                                                                    {{ $item->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                @endif
                                                <div class="col-lg-2 col-md-4">
                                                    <div class="form-group">
                                                        <label>Status</label>
                                                        @php $selectedStatus = request()->has('status') ? (string) request()->status : 'success'; @endphp
                                                        <select name="status" class="form-control">
                                                            <option value="" {{ $selectedStatus === '' ? 'selected' : '' }}>All</option>
                                                            <option value="failed" {{ $selectedStatus === 'failed' ? 'selected' : '' }}>Failed</option>
                                                            <option value="success" {{ $selectedStatus === 'success' ? 'selected' : '' }}>Success</option>
                                                            <option value="pending" {{ $selectedStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                                            <option value="reverse" {{ $selectedStatus === 'reverse' ? 'selected' : '' }}>Reverse</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2 col-md-4">
                                                    <div class="form-group">
                                                        <label>Phone</label>
                                                        <input type="text" name="phone"
                                                            class="form-control"
                                                            value="{{ request()->phone }}" autocomplete="off">
                                                    </div>
                                                </div>
                                                <div class="col-lg-2 col-md-4">
                                                    <div class="form-group">
                                                        <label>Amount</label>
                                                        <input type="number" step="0.01" min="0" name="amount_min"
                                                            class="form-control"
                                                            value="{{ request()->amount_min }}" autocomplete="off">
                                                    </div>
                                                </div>
                                                <div class="col-lg-2 col-md-4">
                                                    <div class="form-group">
                                                        <label>Start Date <span class="text-danger">*</span></label>
                                                        <input type="date" name="start_date"
                                                            class="form-control"
                                                            value="{{ request()->start_date }}"
                                                            required>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2 col-md-4">
                                                    <div class="form-group">
                                                        <label>End Date <span class="text-danger">*</span></label>
                                                        <input type="date" name="end_date"
                                                            class="form-control"
                                                            value="{{ request()->end_date }}"
                                                            required>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2 col-md-4">
                                                    <button type="submit" class="btn btn-outline-primary waves-effect">
                                                        <i data-feather='search'></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if(request()->params)
                @php
                    $summary = $summary ?? null;
                    $statusColors = [
                        'failed' => 'bg-danger',
                        'success' => 'bg-success',
                        'pending' => 'bg-warning',
                        'reverse' => 'bg-secondary',
                    ];
                @endphp
                @if($summary)
                <div class="row">
                    <div class="col-md-3">
                        <div class="card bg-primary">
                            <div class="card-body pb-50">
                                <h5 class="text-white">Dated: <span class="fw-bolder" style="font-size:20px">{{ $summary['date_label'] }}</span></h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success">
                            <div class="card-body pb-50">
                                <h5 class="text-white">
                                    Total Payin:
                                    <span class="fw-bolder" style="font-size:20px">{{ number_format(round($summary['total_payin'], 2)) }} PKR</span>
                                </h5>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning">
                            <div class="card-body pb-50">
                                <h5 class="text-white">
                                    No Of Orders:
                                    <span class="fw-bolder" style="font-size:20px">{{ number_format($summary['total_orders']) }}</span>
                                </h5>
                            </div>
                        </div>
                    </div>
                    @if($summary['show_sr'])
                    <div class="col-md-3">
                        <div class="card bg-info">
                            <div class="card-body pb-50">
                                <h5 class="text-white">
                                    SR:
                                    <span class="fw-bolder" style="font-size:20px">{{ number_format($summary['success_rate'], 2) }}%</span>
                                </h5>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                @if(!empty($summary['show_status_breakdown']))
                <div class="row">
                    @foreach(['failed' => 'Failed', 'success' => 'Success', 'pending' => 'Pending', 'reverse' => 'Reverse'] as $statusKey => $statusLabel)
                        @php $stat = $summary['by_status'][$statusKey] ?? ['count' => 0, 'amount' => 0]; @endphp
                        <div class="col-md-3">
                            <div class="card {{ $statusColors[$statusKey] ?? 'bg-secondary' }}">
                                <div class="card-body pb-50">
                                    <h5 class="text-white mb-1">{{ $statusLabel }}</h5>
                                    <h5 class="text-white mb-0">
                                        Orders: <span class="fw-bolder" style="font-size:18px">{{ number_format($stat['count']) }}</span>
                                    </h5>
                                    <h5 class="text-white mb-0">
                                        Amount: <span class="fw-bolder" style="font-size:18px">{{ number_format(round($stat['amount'], 2)) }} PKR</span>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @endif
                @endif
                <div class="row invoice-preview">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-1">
                                <h4 class="card-title text-capitalize mb-0">Results</h4>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('admin.export_payin.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                                       class="btn btn-outline-secondary btn-sm">
                                        Export CSV
                                    </a>
                                    <a href="{{ route('admin.export_payin.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}"
                                       class="btn btn-outline-success btn-sm">
                                        Export Excel
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info mb-2 py-1 px-2 small">
                                    On-screen preview shows up to {{ \App\DataTables\Admin\ExportPayinDataTable::DISPLAY_LIMIT }} rows
                                    (live + archive + backup). Summary cards use full matching totals.
                                    Use <strong>Export CSV</strong> for all matching rows.
                                </div>
                                <div class="table-responsive">
                                    {{ $dataTable->table(['class' => 'table text-center table-striped w-100'], true) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </section>
        </div>
    </div>
</div>

@endsection

@push('js')
    @include('admin.components.datatablesScript')
@endpush
