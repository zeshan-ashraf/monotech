@extends('admin.layout.app')
@section('title','Pending Reversals')
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
    .countdown-timer {
        font-weight: bold;
        color: #ff6b6b;
    }
    .countdown-timer.expired {
        color: #ff0000;
    }
    .bulk-actions {
        margin-bottom: 15px;
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
                                <h4 class="card-title text-capitalize">Pending Transaction Reversals</h4>
                            </div>
                            <div class="card-body mt-3">
                                <div class="bulk-actions">
                                    <button id="bulk-reverse-btn" class="btn btn-danger btn-sm" disabled>
                                        Reverse Selected
                                    </button>
                                    <button id="bulk-cancel-btn" class="btn btn-warning btn-sm" disabled>
                                        Cancel Selected
                                    </button>
                                    <span id="selected-count" class="ml-2">0 selected</span>
                                </div>
                                <div class="table-responsive">
                                    {{ $dataTable->table(['class' => 'table text-center table-striped w-100'],true) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

@endsection

@push('js')
    @include('admin.components.datatablesScript')
    <script>
        let selectedTransactions = [];
        let selectedTableTypes = [];

        // Select all checkbox
        $(document).on('change', '#select-all-checkbox', function() {
            const isChecked = $(this).is(':checked');
            $('.transaction-checkbox').prop('checked', isChecked);
            
            if (isChecked) {
                $('.transaction-checkbox').each(function() {
                    const id = $(this).val();
                    const tableType = $(this).data('table-type');
                    if (!selectedTransactions.includes(id)) {
                        selectedTransactions.push(id);
                        selectedTableTypes.push(tableType);
                    }
                });
            } else {
                selectedTransactions = [];
                selectedTableTypes = [];
            }
            updateBulkButtons();
        });

        // Individual checkbox
        $(document).on('change', '.transaction-checkbox', function() {
            const id = $(this).val();
            const tableType = $(this).data('table-type');
            
            if ($(this).is(':checked')) {
                if (!selectedTransactions.includes(id)) {
                    selectedTransactions.push(id);
                    selectedTableTypes.push(tableType);
                }
            } else {
                const index = selectedTransactions.indexOf(id);
                if (index > -1) {
                    selectedTransactions.splice(index, 1);
                    selectedTableTypes.splice(index, 1);
                }
            }
            
            // Update select all checkbox
            const totalCheckboxes = $('.transaction-checkbox').length;
            const checkedCheckboxes = $('.transaction-checkbox:checked').length;
            $('#select-all-checkbox').prop('checked', totalCheckboxes === checkedCheckboxes);
            
            updateBulkButtons();
        });

        function updateBulkButtons() {
            const count = selectedTransactions.length;
            $('#selected-count').text(count + ' selected');
            $('#bulk-reverse-btn').prop('disabled', count === 0);
            $('#bulk-cancel-btn').prop('disabled', count === 0);
        }

        // Bulk reverse
        $(document).on('click', '#bulk-reverse-btn', function() {
            if (selectedTransactions.length === 0) {
                alert('Please select at least one transaction.');
                return;
            }

            if (!confirm('Are you sure you want to reverse ' + selectedTransactions.length + ' transaction(s)?')) {
                return;
            }

            $.ajax({
                url: '{{ route("admin.transaction.reversal.bulk_reverse") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    ids: selectedTransactions,
                    table_types: selectedTableTypes
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Failed to reverse transactions'));
                    }
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.message || 'Failed to reverse transactions'));
                }
            });
        });

        // Bulk cancel
        $(document).on('click', '#bulk-cancel-btn', function() {
            if (selectedTransactions.length === 0) {
                alert('Please select at least one transaction.');
                return;
            }

            if (!confirm('Are you sure you want to cancel reversal for ' + selectedTransactions.length + ' transaction(s)?')) {
                return;
            }

            let successCount = 0;
            let failCount = 0;
            let completed = 0;

            selectedTransactions.forEach(function(id, index) {
                const tableType = selectedTableTypes[index];
                
                $.ajax({
                    url: '{{ route("admin.transaction.reversal.cancel") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id,
                        table_type: tableType
                    },
                    success: function(response) {
                        if (response.success) {
                            successCount++;
                        } else {
                            failCount++;
                        }
                    },
                    error: function() {
                        failCount++;
                    },
                    complete: function() {
                        completed++;
                        if (completed === selectedTransactions.length) {
                            alert('Cancelled ' + successCount + ' transaction(s). ' + (failCount > 0 ? failCount + ' failed.' : ''));
                            location.reload();
                        }
                    }
                });
            });
        });

        // Cancel single reversal
        $(document).on('click', '.cancel-reversal-btn', function() {
            const id = $(this).data('id');
            const tableType = $(this).data('table-type');
            
            if (!confirm('Are you sure you want to cancel this reversal request?')) {
                return;
            }

            $.ajax({
                url: '{{ route("admin.transaction.reversal.cancel") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    table_type: tableType
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Failed to cancel reversal'));
                    }
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.message || 'Failed to cancel reversal'));
                }
            });
        });

        // Reverse now (single)
        $(document).on('click', '.reverse-now-btn', function() {
            const id = $(this).data('id');
            const tableType = $(this).data('table-type');
            
            if (!confirm('Are you sure you want to reverse this transaction immediately?')) {
                return;
            }

            $.ajax({
                url: '{{ route("admin.transaction.reversal.reverse_now") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: id,
                    table_type: tableType
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        location.reload();
                    } else {
                        alert('Error: ' + (response.message || 'Failed to reverse transaction'));
                    }
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.message || 'Failed to reverse transaction'));
                }
            });
        });

        // Countdown timer update function
        function updateCountdownTimers() {
            $('.countdown-timer').each(function() {
                const $timer = $(this);
                const deadline = parseInt($timer.data('deadline'));
                const reverseRequested = parseInt($timer.data('reverse-requested'));
                
                if (!deadline || !reverseRequested) {
                    $timer.text('00:00:00');
                    return;
                }

                function updateTimer() {
                    const now = Math.floor(Date.now() / 1000);
                    const remaining = deadline - now;

                    if (remaining <= 0) {
                        $timer.text('00:00:00').addClass('expired');
                        return;
                    }

                    const hours = Math.floor(remaining / 3600);
                    const minutes = Math.floor((remaining % 3600) / 60);
                    const seconds = remaining % 60;

                    const formatted = String(hours).padStart(2, '0') + ':' + 
                                     String(minutes).padStart(2, '0') + ':' + 
                                     String(seconds).padStart(2, '0');
                    
                    $timer.text(formatted);
                }

                updateTimer();
                setInterval(updateTimer, 1000);
            });
        }

        // Initialize countdown on page load
        $(document).ready(function() {
            updateCountdownTimers();
        });
    </script>
@endpush
