@extends('admin.layout.app')
@section('title','Payout')
@push('css')
<link rel="stylesheet" href="{{ asset('admin/assets/dashboard/css/dataTables.bootstrap4.min.css') }}" />
@endpush
@section('content')
<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper container-xxl pt-0 px-0 pb-sm-0 pb-5">
        <div class="content-header row">
        </div>
        <div class="content-body mt-5">
            <section id="row-grouping-datatable">
                <div class="row">
                    
                    <div class="col-12">
                        <div class="card mt-2">
                            <div class="card-header border-bottom d-flex justify-content-between">
                                <h4 class="card-title text-capitalize">Payout Detail</h4>
                            </div>
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-6"><span class="bolder">Transaction Ref No</span></div>
                                    <div class="col-6">{{$item->transaction_reference}}</div>
                                    <div class="col-6"><span class="bolder">Transaction Amount</span></div>
                                    <div class="col-6">{{$item->amount}} PKR</div>
                                    <div class="col-6"><span class="bolder">Transaction Method</span></div>
                                    <div class="col-6">{{$item->transaction_type}}</div>
                                    <div class="col-6"><span class="bolder">Transaction Placed On</span></div>
                                    <div class="col-6">{{$item->created_at}}</div>
                                    <div class="col-6"><span class="bolder">Code</span></div>
                                    <div class="col-6"><span class="text-capitalize">{{$item->code}}</span></div>
                                     <div class="col-6"><span class="bolder">Message</span></div>
                                    <div class="col-6"><span class="text-capitalize">{{$item->message}}</span></div>
                                     <div class="col-6"><span class="bolder">Current Status</span></div>
                                    <div class="col-6">
                                        @if($item->status == "success")
                                            <span class="badge bg-success text-capitalize">{{$item->status}}</span>
                                        @elseif($item->status == "pending")
                                            <span class="badge bg-primary text-capitalize">{{$item->status}}</span>
                                        @else
                                            <span class="badge bg-danger text-capitalize">{{$item->status}}</span>
                                        @endif
                                    </div>
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
