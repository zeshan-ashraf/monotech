@extends('admin.layout.app')
@section('title','Searching')
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
                                <h4 class="card-title text-capitalize">Payin Search</h4>
                            </div>
                            <div class="card-body mt-3">
                                <div>
                                    <div class="toolbar w-100">
                                        <form method="GET" action="{{route('admin.searching.list')}}">
                                            <input type="hidden" name="params" value="true">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Phone</label>
                                                        <input type="text" name="phone" id="fp-range"
                                                            class="form-control flatpickr-range  flatpickr-input"
                                                            value="{{request()->phone}}" autocomplete="off">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Transaction Id</label>
                                                        <input type="text" name="transaction_Id" id="fp-range"
                                                            class="form-control flatpickr-range  flatpickr-input"
                                                            value="{{request()->transaction_Id}}" autocomplete="off">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Order Id</label>
                                                        <input type="text" name="order_id" id="fp-range"
                                                            class="form-control flatpickr-range  flatpickr-input"
                                                            value="{{request()->order_id}}" autocomplete="off">
                                                    </div>
                                                </div>
                                                <div class="col-md-3 mt-2">
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
    $(document).on('change', '.status-dropdown-reverse', function() {
            var status = $(this).val();
            var id = $(this).data('id');
    
            $.ajax({
                url: '{{ route("admin.transaction.change_status_reverse") }}', // Correct route
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}', // CSRF protection
                    id: id,
                    status: status
                },
                success: function(response) {
                    location.reload(); // Reload page to reflect changes
                },
                error: function(xhr, status, error) {
                    alert('Failed to update status: ' + xhr.responseJSON.message);
                }
            });
        });
    </script>
@endpush