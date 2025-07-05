@extends('admin.layout.app')
@section('title','SR Calculator')
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
    h3{
        color:white;
        font-weight:700 !important;
    }
    span,p{
        font-size:26px;
    }
    .span-time{
        font-size:18px;
        margin-left:5px;
    }
    .watermark{
        font-size:14px;
        text-align:right;
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
                                <h4 class="card-title text-capitalize">SR Calculator</h4>
                            </div>
                            <div class="card-body mt-3">
                                <div>
                                    <div class="toolbar w-100">
                                        <form method="GET" action="{{route('admin.searching.sr_list')}}">
                                            <input type="hidden" name="params" value="true">
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Client</label>
                                                        <select name="user_id" class="form-control flatpickr-range  flatpickr-input">
                                                            <option selected disabled>Select Client</option>
                                                            <option value="All" {{"All" == request()->user_id ? "selected" : ""}}>All</option>
                                                            @foreach($users as $item)
                                                                <option value="{{$item->id}}" {{$item->id == request()->user_id ? "selected" : ""}}>{{$item->name}}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Network</label>
                                                        <select name="trans_type" class="form-control flatpickr-range  flatpickr-input">
                                                            <option selected disabled>Select Network</option>
                                                            <option value="all"  {{"all" == request()->trans_type ? "selected" : ""}}>All</option>
                                                            <option value="easypaisa" {{"easypaisa" == request()->trans_type ? "selected" : ""}}>Easypaisa</option>
                                                            <option value="jazzcash" {{"jazzcash" == request()->trans_type ? "selected" : ""}}>Jazzcash</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Time</label>
                                                        <select name="time" class="form-control flatpickr-range  flatpickr-input">
                                                            <option selected disabled>Select Time</option>
                                                            <option value="1"  {{"1" == request()->time ? "selected" : ""}}>1 Minute</option>
                                                            <option value="2"  {{"2" == request()->time ? "selected" : ""}}>2 Minutes</option>
                                                            <option value="10"  {{"10" == request()->time ? "selected" : ""}}>10 Minutes</option>
                                                            <option value="30" {{"30" == request()->time ? "selected" : ""}}>30 Minutes</option>
                                                            <option value="60" {{"60" == request()->time ? "selected" : ""}}>1 Hour</option>
                                                            <option value="180" {{"180" == request()->time ? "selected" : ""}}>3 Hour</option>
                                                        </select>
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
                <div class="row invoice-preview mt-3">
                    <div class="col-md-5 m-auto">
                        <div class="card" style="border-radius: 15px; overflow: hidden; box-shadow: 0px 0px 10px rgba(0,0,0,0.1);">
                            <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #6a64ae; color: white;">
                                <h3 class="mb-0">{{ $client->name ?? "All"}} 
                                    <span class="span-time text-warning">
                                    @if (request()->time == "1")
                                        (Last 1 Minute)
                                    @elseif (request()->time == "2")
                                        (Last 2 Minutes)
                                    @elseif (request()->time == "10")
                                        (Last 10 Minutes)
                                    @elseif (request()->time == "30")
                                        (Last 30 Minutes)
                                    @elseif (request()->time == "60")
                                        (Last 1 Hour)
                                    @else
                                        (Last 3 Hour)
                                    @endif
                                    </span>
                                </h3>
                                <h3 class="mb-0">{{ $successRate }}%</h3>
                            </div>
                            <div class="card-body " style="background-color: #d2e2cc;">
                                <p class="mt-3"><strong>Total Amount:</strong> {{ number_format($totalAmount) }} </p>
                                <p class="mt-3">
                                    <span class="text-dark"><strong>TPM:</strong> {{ number_format($transactionPerMint) }}</span>
                                </p>
                                <p class="mt-3">
                                    <span class="text-primary me-5"><strong>Total:</strong> {{ $totalTransactions }}</span>
                                    <span class="text-success me-5"><strong>Success:</strong> {{ $successfulTransactions }}</span>
                                    <span class="text-danger me-5"><strong>Fail:</strong> {{ $totalTransactions - $successfulTransactions }}</span>
                                </p>
                                <p class="watermark">
                                    Dated: {{ now()->format('d-m-Y H:i') }}
                                </p>
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
    <script>
    
    </script>
@endpush
