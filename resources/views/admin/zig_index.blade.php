@extends('admin.layout.app')
@section('title','Dashboard')
@push('css')
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
                                                        <th>Name</th>
                                                        <th>Previous Balance</th>
                                                        <th>Payin</th>
                                                        <th>Payout</th>
                                                        <th>USDT</th>
                                                        <th>Unsettled (Payable)</th>
                                                        <th>Wallet</th>
                                                        <th>Balance</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="client">{{ $client->name }}</td>
                                                        <td>{{ number_format($prevBal) }}</td>
                                                        <td class="bg-green">{{ number_format($jcPayinAmount) }}</td>
                                                        <td class="bg-red">{{ number_format($jcPayoutAmount) }}</td>
                                                        <td>{{ number_format($prevUsdt) }}</td>
                                                        <td class="font-weight-bold text-red">{{ number_format($unsettletdAmount) }}</td>
                                                        <td class="bg-gray">{{ number_format($assignedAmount->jazzcash ?? 0) }}</td>
                                                        <td class="bg-warning">{{ number_format(round($balance, 0)) }}</td>
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