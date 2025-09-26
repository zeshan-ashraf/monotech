@extends('admin.layout.app')
@section('title','Dashboard')
@push('css')
    <link rel="stylesheet"
        href="{{ asset('admin/assets/dashboard/css/dataTables.bootstrap4.min.css') }}" />
        <style>
            .table:not(.table-dark):not(.table-light) thead:not(.table-dark) th, .table:not(.table-dark):not(.table-light) tfoot:not(.table-dark) th {
                background-color: #808080bf;
                color:#000 !important;
                border: 1px solid #343a40 !important;
            }
            .table thead th, .table tfoot th{
                font-size: 0.9rem;
                vertical-align: middle;
                text-transform: uppercase !important;
            }
            .table {
                width: 100%;
                border-collapse: collapse;
            }
        
            .table thead {
                /*background-color: #343a40;*/
                color: white;
            }
        
            .table th, .table td {
                text-align: center;
                padding: 10px;
                border: 1px solid #000;
                color: #000;
            }
        
            .table tbody tr:nth-child(even) {
                background-color: #f8f9fa;
            }
        
            .table tbody tr:hover {
                background-color: #e9ecef;
            }
        
            .table th {
                font-size:20px;
                font-weight: bold;
            }
            .table td{
                font-size:18px;
                /*color:black !important;*/
            }
            .table-bordered {
                border: 1px solid #000;
            }
            /*.bg-green{*/
            /*    background-color: #58c38a80 !important;*/
            /*}*/
            /*.bg-red{*/
            /*    background-color:#ff00007a !important;*/
            /*}*/
            /*.bg-gray{*/
            /*    background-color:#df720694 !important;*/
            /*}*/
            .client{
                text-transform: uppercase;
            }
            .card-graph{
                background: #c8c9c2;
                height:75%;
            }
            .card-graph-red{
                background: #df720694;
                height:75%;
            }
            .card-graph-green{
                background: #58c38a80;
                height:75%;
            }
            .font-weight-bold{
                font-weight: 600 !important;
            }
            .text-red{
                color:red !important;
            }
            .form-switch .form-check-input{
                    margin-left: 0 !important;
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
            <!-- Dashboard Ecommerce Starts -->
            <section id="dashboard-ecommerce">
                <div class="row match-height">
                    <!-- Statistics Card -->
                    @php
                        $users= \App\Models\User::count();
                        $transaction= \App\Models\Transaction::count();
                        $payout= \App\Models\Payout::count();
                        $sub_stores= \App\Models\Client::count();
                    @endphp
                    <div class="col-xl-12 col-md-12 col-12">
                        <div class="row">
                        <div class="@if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Admin") col-md-2 @else col-md-3 @endif">
                                <div class="card bg-primary">
                                    <div class="card-body pb-50">
                                        <h5 class="text-white">Dated: <span class="fw-bolder" style="font-size:14px">{{ now()->format('d-m-Y') }}</span> </h5>
                                    </div>
                                </div>
                            </div>
                            <div class="@if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Admin") col-md-2 @else col-md-3 @endif">
                                <div class="card bg-success">
                                    <div class="card-body pb-50">
                                        <h5 class="text-white">No of Sub Stores: <span class="fw-bolder" style="font-size:14px">{{ $sub_stores }}</span> </h5>
                                    </div>
                                </div>
                            </div>
                            <div class="@if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Admin") col-md-3 @else col-md-3 @endif">
                                <div class="card bg-info">
                                    <div class="card-body pb-50">
                                        <h5 class="text-white">Today Payin No: <span class="fw-bolder" style="font-size:14px">{{ $transaction }}</span> </h5>
                                    </div>
                                </div>
                            </div>
                            <div class="@if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Admin") col-md-2 @else col-md-3 @endif">
                                <div class="card bg-danger">
                                    <div class="card-body pb-50">
                                        <h5 class="text-white">Today Payout No: <span class="fw-bolder" style="font-size:14px">{{ $payout }}</span> </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row justify-content-center align-items-center mt-1">
                            <div class="col-lg-12 col-12">
                                <div class="card card-company-table">
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Payin</th>
                                                        <th>PNL</th>
                                                        <th>Withdraw</th>
                                                        <th>Total PNL</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>{{ $item->date->format('d-M') }}</td>
                                                        <td>{{ number_format(round($item->jc_payin,0)) }}</td>
                                                        <td>{{ number_format(round($item->pnl_amount,0)) }}</td>
                                                        <td>{{ number_format(round($item->usdt_pnl_amount,0)) }}</td>
                                                        <td>{{ number_format($item->total_pnl_amount) }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
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
@push('js')