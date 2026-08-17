@extends('admin.layout.app')
@section('title','Export Transactions')
@push('css')
<link rel="stylesheet" href="{{ asset('admin/assets/dashboard/css/dataTables.bootstrap4.min.css') }}" />
<style>
    #dataTable .badge {
        font-size: 0.65rem;
        padding: 0.18em 0.4em;
        font-weight: 600;
        line-height: 1.2;
    }
    #dataTable .status-badge-wrap .copy-btn svg {
        width: 12px;
        height: 12px;
    }
    table.dataTable td,
    table.dataTable th {
        padding: 5px 5px;
        vertical-align: middle;
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
                                                <div class="col-lg-2 col-md-4">
                                                    <div class="form-group">
                                                        <label>Transaction Type</label>
                                                        @php $selectedTransactionType = request()->input('transaction_type', 'payin'); @endphp
                                                        <select name="transaction_type" class="form-control">
                                                            <option value="payin" {{ $selectedTransactionType === 'payin' ? 'selected' : '' }}>Payin</option>
                                                            <option value="payout" {{ $selectedTransactionType === 'payout' ? 'selected' : '' }}>Payout</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2 col-md-4">
                                                    <div class="form-group">
                                                        <label>Network</label>
                                                        @php $selectedNetwork = request()->input('network', ''); @endphp
                                                        <select name="network" class="form-control">
                                                            <option value="" {{ $selectedNetwork === '' || $selectedNetwork === 'all' ? 'selected' : '' }}>All</option>
                                                            <option value="easypaisa" {{ $selectedNetwork === 'easypaisa' ? 'selected' : '' }}>Easypaisa</option>
                                                            <option value="jazzcash" {{ $selectedNetwork === 'jazzcash' ? 'selected' : '' }}>Jazzcash</option>
                                                        </select>
                                                    </div>
                                                </div>
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
                                                @php
                                                    $selectedDateRange = request()->input('date_range', 'today');
                                                    if (!in_array($selectedDateRange, \App\DataTables\Admin\ExportPayinDataTable::DATE_RANGES, true)) {
                                                        $selectedDateRange = 'today';
                                                    }
                                                    $showCustomDates = $selectedDateRange === 'custom';
                                                @endphp
                                                <div class="col-lg-2 col-md-4">
                                                    <div class="form-group">
                                                        <label>Date</label>
                                                        <select name="date_range" id="export-date-range" class="form-control">
                                                            <option value="today" {{ $selectedDateRange === 'today' ? 'selected' : '' }}>Today</option>
                                                            <option value="yesterday" {{ $selectedDateRange === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                                            <option value="this_week" {{ $selectedDateRange === 'this_week' ? 'selected' : '' }}>This week</option>
                                                            <option value="this_month" {{ $selectedDateRange === 'this_month' ? 'selected' : '' }}>This month</option>
                                                            <option value="last_month" {{ $selectedDateRange === 'last_month' ? 'selected' : '' }}>Last month</option>
                                                            <option value="this_year" {{ $selectedDateRange === 'this_year' ? 'selected' : '' }}>This year</option>
                                                            <option value="custom" {{ $selectedDateRange === 'custom' ? 'selected' : '' }}>Custom</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2 col-md-4 js-custom-date" @if(!$showCustomDates) style="display:none" @endif>
                                                    <div class="form-group">
                                                        <label>Start Date <span class="text-danger">*</span></label>
                                                        <input type="date" name="start_date"
                                                            class="form-control"
                                                            value="{{ request()->start_date }}"
                                                            @if($showCustomDates) required @endif>
                                                    </div>
                                                </div>
                                                <div class="col-lg-2 col-md-4 js-custom-date" @if(!$showCustomDates) style="display:none" @endif>
                                                    <div class="form-group">
                                                        <label>End Date <span class="text-danger">*</span></label>
                                                        <input type="date" name="end_date"
                                                            class="form-control"
                                                            value="{{ request()->end_date }}"
                                                            @if($showCustomDates) required @endif>
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
                    $isPayout = \App\DataTables\Admin\ExportPayinDataTable::isPayoutRequest();
                    $statusColors = [
                        'failed' => 'bg-danger',
                        'success' => 'bg-success',
                        'pending' => 'bg-warning',
                        'reverse' => 'bg-secondary',
                    ];
                @endphp
                @if($summary)
                @php $summaryCol = !empty($summary['show_sr']) ? 'col-md-3' : 'col-md-4'; @endphp
                <div class="row">
                    <div class="{{ $summaryCol }}">
                        <div class="card bg-primary">
                            <div class="card-body pb-50">
                                <h5 class="text-white">Dated: <span class="fw-bolder" style="font-size:20px">{{ $summary['date_label'] }}</span></h5>
                            </div>
                        </div>
                    </div>
                    <div class="{{ $summaryCol }}">
                        <div class="card bg-success">
                            <div class="card-body pb-50">
                                <h5 class="text-white">
                                    Total {{ $isPayout ? 'Payout' : 'Payin' }}:
                                    <span class="fw-bolder" style="font-size:20px">{{ number_format(round($summary['total_payin'], 2)) }} PKR</span>
                                </h5>
                            </div>
                        </div>
                    </div>
                    <div class="{{ $summaryCol }}">
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
                    <div class="{{ $summaryCol }}">
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
                                    @if($isPayout)
                                        (current payouts + archived payouts).
                                    @else
                                        (live + archive + backup).
                                    @endif
                                    Summary cards use full matching totals.
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
    <script>
        (function () {
            const form = document.querySelector('form[action="{{ route('admin.export_payin.list') }}"]');
            if (!form) {
                return;
            }

            const dateRangeSelect = form.querySelector('#export-date-range');
            const startInput = form.querySelector('input[name="start_date"]');
            const endInput = form.querySelector('input[name="end_date"]');
            const customFields = form.querySelectorAll('.js-custom-date');

            function pad(n) {
                return String(n).padStart(2, '0');
            }

            function formatYmd(date) {
                return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
            }

            function presetDates(preset) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                let start = new Date(today);
                let end = new Date(today);

                if (preset === 'yesterday') {
                    start.setDate(start.getDate() - 1);
                    end = new Date(start);
                } else if (preset === 'this_week') {
                    const diff = (today.getDay() + 6) % 7;
                    start.setDate(today.getDate() - diff);
                } else if (preset === 'this_month') {
                    start = new Date(today.getFullYear(), today.getMonth(), 1);
                } else if (preset === 'last_month') {
                    start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    end = new Date(today.getFullYear(), today.getMonth(), 0);
                } else if (preset === 'this_year') {
                    start = new Date(today.getFullYear(), 0, 1);
                }

                return { start: formatYmd(start), end: formatYmd(end) };
            }

            function applyDateRange() {
                const preset = dateRangeSelect.value;
                const isCustom = preset === 'custom';

                customFields.forEach(function (el) {
                    el.style.display = isCustom ? '' : 'none';
                });
                startInput.required = isCustom;
                endInput.required = isCustom;

                if (!isCustom) {
                    const dates = presetDates(preset);
                    startInput.value = dates.start;
                    endInput.value = dates.end;
                }
            }

            function canSubmitDates() {
                if (dateRangeSelect.value !== 'custom') {
                    return true;
                }
                return Boolean(startInput.value && endInput.value);
            }

            dateRangeSelect.addEventListener('change', applyDateRange);
            form.addEventListener('submit', applyDateRange);
            applyDateRange();

            form.querySelector('select[name="transaction_type"]')?.addEventListener('change', function () {
                applyDateRange();
                if (canSubmitDates()) {
                    form.submit();
                }
            });
        })();
    </script>
@endpush
