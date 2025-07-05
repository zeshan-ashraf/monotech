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
                                            <div class="row">
                                                @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Manager")
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Phone Number</label>
                                                        <input type="text" name="phone" id="fp-range"
                                                            class="form-control flatpickr-range  flatpickr-input"
                                                            value="{{request()->phone}}">
                                                    </div>
                                                </div>
                                                @endif
                                                {{--<div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Transaction Ref No</label>
                                                        <input type="text" name="transaction_ref_no" id="fp-range"
                                                            class="form-control flatpickr-range  flatpickr-input"
                                                            value="{{request()->transaction_ref_no}}">
                                                    </div>
                                                </div>--}}
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Order Id</label>
                                                        <input type="text" name="order_id" id="fp-range"
                                                            class="form-control flatpickr-range  flatpickr-input"
                                                            value="{{request()->order_id}}">
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

    </script>
@endpush
