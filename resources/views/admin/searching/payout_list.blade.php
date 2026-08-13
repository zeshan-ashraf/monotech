@extends('admin.layout.app')
@section('title','Payout Searching')
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
                                <h4 class="card-title text-capitalize">Payout Search</h4>
                            </div>
                            <div class="card-body mt-3">
                                <div>
                                    <div class="toolbar w-100">
                                        <form method="GET" action="{{route('admin.searching.payout_list')}}">
                                            <input type="hidden" name="params" value="true">
                                            <div class="row g-1 align-items-end">
                                                @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Manager")
                                                <div class="col-lg-2 col-md-4">
                                                    <div class="form-group">
                                                        <label>Phone Number</label>
                                                        <input type="text" name="phone"
                                                            class="form-control"
                                                            value="{{request()->phone}}" autocomplete="off">
                                                    </div>
                                                </div>
                                                @endif
                                                {{--<div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Transaction Ref No</label>
                                                        <input type="text" name="transaction_ref_no" id="fp-range"
                                                            class="form-control flatpickr-range  flatpickr-input"
                                                            value="{{request()->transaction_ref_no}}" autocomplete="off">
                                                    </div>
                                                </div>--}}
                                                <div class="col-lg-2 col-md-4">
                                                    <div class="form-group">
                                                        <label>Order Id</label>
                                                        <input type="text" name="order_id"
                                                            class="form-control"
                                                            value="{{request()->order_id}}" autocomplete="off">
                                                    </div>
                                                </div>
                                                <div class="col-lg-2 col-md-4">
                                                    <div class="form-group">
                                                        <label>Date</label>
                                                        <input type="date" name="start_date"
                                                            class="form-control"
                                                            value="{{ request()->start_date }}">
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
                <div class="row invoice-preview">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header border-bottom d-flex justify-content-between">
                                <h4 class="card-title text-capitalize">Results</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    {{ $dataTable->table(['class' => 'table text-center table-striped w-100'],true) }}
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
$(document).on('click', '.btn-settle', function() {
    var button = $(this);
    var orderId = button.attr('data-order');
    var tableName = button.attr('data-table');
    var originalHtml = button.html();

    button.prop('disabled', true).addClass('disabled').html('...');

    $.ajax({
        url: '{{ route("admin.payout.settle") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            order_id: orderId,
            table_name: tableName
        },
        success: function(response) {
            if (response.success) {
                var $row = button.closest('tr');
                var unsettleBtn = $('<button type="button" class="btn btn-warning btn-sm btn-unsettle mt-1"></button>')
                    .attr('data-order', orderId)
                    .attr('data-table', tableName)
                    .text('Unsettle');
                button.replaceWith(unsettleBtn);

                var statusCell = $row.find('.payout-status-cell');
                if (statusCell.length && statusCell.find('.settled-badge').length === 0) {
                    statusCell.append(' <span class="badge bg-warning settled-badge">Settled</span>');
                }
            } else {
                button.prop('disabled', false).removeClass('disabled').html(originalHtml);
                alert(response.message || 'Failed to settle payout');
            }
        },
        error: function(xhr) {
            button.prop('disabled', false).removeClass('disabled').html(originalHtml);
            var message = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Failed to settle payout';
            alert(message);
        }
    });
});

$(document).on('click', '.btn-unsettle', function() {
    var button = $(this);
    var orderId = button.attr('data-order');
    var tableName = button.attr('data-table');
    var originalHtml = button.html();

    button.prop('disabled', true).addClass('disabled').html('...');

    $.ajax({
        url: '{{ route("admin.payout.unsettle") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            order_id: orderId,
            table_name: tableName
        },
        success: function(response) {
            if (response.success) {
                var settleBtn = $('<button type="button" class="btn btn-warning btn-sm btn-settle mt-1"></button>')
                    .attr('data-order', orderId)
                    .attr('data-table', tableName)
                    .text('Settle');
                var $row = button.closest('tr');
                button.replaceWith(settleBtn);
                $row.find('.settled-badge').remove();
            } else {
                button.prop('disabled', false).removeClass('disabled').html(originalHtml);
                alert(response.message || 'Failed to unsettle payout');
            }
        },
        error: function(xhr) {
            button.prop('disabled', false).removeClass('disabled').html(originalHtml);
            var message = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Failed to unsettle payout';
            alert(message);
        }
    });
});
    </script>
    <script>
        $(document).on('change', '.status-dropdown', function() {
            var status = $(this).val();
            var id = $(this).data('id');
    
            $.ajax({
                url: '{{ route("admin.transaction.change_status") }}', // Correct route
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}', // CSRF protection
                    id: id,
                    status: status
                },
                success: function(response) {
                    alert('Status updated successfully!');
                    location.reload(); // Reload page to reflect changes
                },
                error: function(xhr, status, error) {
                    alert('Failed to update status: ' + xhr.responseJSON.message);
                }
            });
        });
    </script>
@endpush
