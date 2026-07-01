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
            .text-red{
                color:red !important;
            }
            .text-green{
                color:green !important;
            }
            .form-switch .form-check-input{
                    margin-left: 0 !important;
            }

            .settlement-poll-card {
                overflow-x: clip;
                overflow-y: visible;
            }
            .settlement-poll-toolbar {
                display: flex;
                align-items: center;
                justify-content: flex-end;
                gap: 1rem;
                padding: 0.65rem 1rem;
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-bottom: 0;
                position: relative;
                overflow: visible;
            }
            .settlement-poll-toolbar > .settlement-poll-status,
            .settlement-poll-toolbar > .d-flex,
            .settlement-poll-toolbar > .dropdown {
                position: relative;
                z-index: 2;
            }
            .settlement-poll-toolbar .dropdown-menu {
                z-index: 1050;
            }
            .settlement-poll-toolbar.is-syncing .settlement-poll-toolbar__progress {
                opacity: 1;
            }
            .settlement-poll-toolbar__progress-track {
                position: absolute;
                left: 0;
                right: 0;
                top: 0;
                height: 3px;
                overflow: hidden;
                pointer-events: none;
                z-index: 1;
            }
            .settlement-poll-toolbar__progress {
                position: absolute;
                left: 0;
                top: 0;
                height: 100%;
                width: 30%;
                background: linear-gradient(90deg, #7367f0, #28c76f);
                opacity: 0;
                will-change: transform;
                animation: settlement-poll-progress 1.1s ease-in-out infinite;
                pointer-events: none;
            }
            @keyframes settlement-poll-progress {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(430%); }
            }
            .settlement-poll-toolbar__label {
                font-size: 0.85rem;
                font-weight: 600;
                color: #4b4b4b;
                text-transform: uppercase;
            }
            .settlement-poll-status {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                font-size: 0.8rem;
                color: #6e6b7b;
            }
            .settlement-poll-status__dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #b9b9c3;
            }
            .settlement-poll-status__dot.is-live {
                background: #28c76f;
                box-shadow: 0 0 0 0 rgba(40, 199, 111, 0.5);
                animation: settlement-poll-pulse 1.8s infinite;
            }
            .settlement-poll-status__dot.is-syncing {
                background: #ff9f43;
                box-shadow: 0 0 6px rgba(255, 159, 67, 0.8);
                animation: settlement-poll-blink 0.55s ease-in-out infinite alternate;
            }
            .settlement-poll-status__dot.is-off {
                background: #b9b9c3;
            }
            @keyframes settlement-poll-pulse {
                0% { box-shadow: 0 0 0 0 rgba(40, 199, 111, 0.45); }
                70% { box-shadow: 0 0 0 8px rgba(40, 199, 111, 0); }
                100% { box-shadow: 0 0 0 0 rgba(40, 199, 111, 0); }
            }
            @keyframes settlement-poll-spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            @keyframes settlement-poll-blink {
                from { opacity: 0.45; }
                to { opacity: 1; }
            }
            #settlement-poll-updated-at.poll-ts-flash {
                animation: settlement-ts-flash 0.7s ease;
            }
            @keyframes settlement-ts-flash {
                0%, 100% { color: #6e6b7b; }
                40% { color: #7367f0; font-weight: 700; }
            }
            [data-poll-scope] {
                contain: paint;
            }
            [data-poll-scope].poll-sync-flash {
                animation: settlement-sync-flash 0.55s ease !important;
            }
            @keyframes settlement-sync-flash {
                0% {
                    box-shadow: inset 0 0 0 0 rgba(115, 103, 240, 0);
                }
                35% {
                    box-shadow: inset 0 0 0 3px rgba(115, 103, 240, 0.55);
                }
                100% {
                    box-shadow: inset 0 0 0 0 rgba(115, 103, 240, 0);
                }
            }
            [data-poll-scope].poll-tick-up {
                animation: settlement-tick-up 0.85s ease !important;
            }
            [data-poll-scope].poll-tick-down {
                animation: settlement-tick-down 0.85s ease !important;
            }
            @keyframes settlement-tick-up {
                0% {
                    color: #00c853 !important;
                    box-shadow: inset 0 0 0 3px rgba(0, 200, 83, 0.95) !important;
                }
                55% {
                    color: #00c853 !important;
                    box-shadow: inset 0 0 0 2px rgba(0, 200, 83, 0.65) !important;
                }
                100% {
                    box-shadow: inset 0 0 0 0 transparent !important;
                }
            }
            @keyframes settlement-tick-down {
                0% {
                    color: #ff1744 !important;
                    box-shadow: inset 0 0 0 3px rgba(255, 23, 68, 0.95) !important;
                }
                55% {
                    color: #ff1744 !important;
                    box-shadow: inset 0 0 0 2px rgba(255, 23, 68, 0.65) !important;
                }
                100% {
                    box-shadow: inset 0 0 0 0 transparent !important;
                }
            }
            [data-poll-scope] .poll-tick-arrow {
                display: inline-block;
                font-size: 0.72em;
                font-weight: 800;
                margin-right: 2px;
                vertical-align: baseline;
                opacity: 0;
            }
            [data-poll-scope].poll-tick-up .poll-tick-arrow,
            [data-poll-scope].poll-tick-down .poll-tick-arrow {
                animation: settlement-arrow-pop 0.85s ease;
            }
            @keyframes settlement-arrow-pop {
                0% { opacity: 0; transform: translateY(4px); }
                20% { opacity: 1; transform: translateY(0); }
                80% { opacity: 1; }
                100% { opacity: 0; }
            }
            .settlement-poll-table-wrap {
                overflow-x: auto;
                overflow-y: visible;
            }
            #dashboard-ecommerce.settlement-poll-active {
                overflow-x: clip;
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
            <section id="dashboard-ecommerce" @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Manager") class="settlement-poll-active" @endif>
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
                                        <h5 class="text-white">No of Sub Stores: <span class="fw-bolder" style="font-size:14px" data-poll-scope="card" data-poll-metric="sub_stores">{{ $sub_stores }}</span> </h5>
                                    </div>
                                </div>
                            </div>
                            <div class="@if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Admin") col-md-3 @else col-md-3 @endif">
                                <div class="card bg-info">
                                    <div class="card-body pb-50">
                                        <h5 class="text-white">Today Payin No: <span class="fw-bolder" style="font-size:14px" data-poll-scope="card" data-poll-metric="payin_count">{{ $transaction }}</span> </h5>
                                    </div>
                                </div>
                            </div>
                            <div class="@if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Admin") col-md-2 @else col-md-3 @endif">
                                <div class="card bg-danger">
                                    <div class="card-body pb-50">
                                        <h5 class="text-white">Today Payout No: <span class="fw-bolder" style="font-size:14px" data-poll-scope="card" data-poll-metric="payout_count">{{ $payout }}</span> </h5>
                                    </div>
                                </div>
                            </div>
                            @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Admin")
                            <div class="col-md-3">
                                <div class="card bg-warning">
                                    <div class="card-body pb-50">
                                        <h5 class="text-black">Monthly EP Payin: <span class="fw-bolder" style="font-size:20px" data-poll-scope="card" data-poll-metric="monthly_ep_payin">{{ number_format(round($totalMonthlyAmount,0))}}</span> </h5>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        
                        
                        <div class="row justify-content-center align-items-center mt-1">
                            <div class="col-lg-12 col-12">
                                <div class="card card-company-table settlement-poll-card">
                                    @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Manager")
                                    <div class="settlement-poll-toolbar" id="settlement-poll-toolbar">
                                        <div class="settlement-poll-toolbar__progress-track" aria-hidden="true">
                                            <div class="settlement-poll-toolbar__progress"></div>
                                        </div>
                                        <div class="settlement-poll-status">
                                            <span class="settlement-poll-status__dot is-off" id="settlement-poll-status-dot"></span>
                                            <span id="settlement-poll-status-label">Paused</span>
                                            <span class="ms-1 text-muted" id="settlement-poll-change-summary"></span>
                                            <span class="ms-2" id="settlement-poll-updated-at"></span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="settlement-poll-toolbar__label">Auto Refresh</span>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch" id="settlement-auto-refresh">
                                            </div>
                                        </div>
                                        <div class="dropdown" id="settlement-poll-interval-dropdown">
                                            <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="settlement-poll-interval-toggle" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                                                <i class="fa fa-refresh"></i>
                                                <span id="settlement-poll-interval-label">30s</span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @foreach(['30s', '1m', '5m', '10m'] as $interval)
                                                    <li>
                                                        <button class="dropdown-item settlement-poll-interval {{ $interval === '30s' ? 'active' : '' }}" type="button" data-interval="{{ $interval }}">
                                                            {{ $interval }}
                                                        </button>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                    @endif
                                    <div class="card-body p-0">
                                        <div class="table-responsive settlement-poll-table-wrap">
                                            <table class="table table-bordered">
                                                <thead>
                                                    @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Manager")
                                                    <tr class="bg-warning">
                                                        <th colspan="6">Payout EP Setting</th>
                                                        <th colspan="@if (auth()->user()->user_role == "Super Admin") 7 @else 5 @endif"  rowspan="2">Surplus Amount Interface</th>
                                                        <th>JC</th>
                                                        <th>EP</th>
                                                        <th colspan="6">Action</th>
                                                    </tr>
                                                    <tr class="bg-warning">
                                                        @foreach($payout_setting as $item)
                                                            <th  colspan="2">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span class="mb-0">{{ $item->type }}</span>

                                                                    <div class="form-check mb-0">
                                                                        <input 
                                                                            class="form-check-input payout-radio"
                                                                            type="radio"
                                                                            name="payout_setting"
                                                                            value="{{ $item->id }}"
                                                                            data-id="{{ $item->id }}"
                                                                            @if($item->value == 1) checked @endif
                                                                        >
                                                                    </div>
                                                                </div>
                                                            </th>
                                                        @endforeach
                                                        <th data-poll-scope="surplus" data-poll-metric="jazzcash">{{number_format(round($surplusAmount->jazzcash,0))}}</th>
                                                        <th data-poll-scope="surplus" data-poll-metric="easypaisa">{{number_format(round($surplusAmount->easypaisa,0))}}</th>
                                                        <th colspan="6"><a data-target="#attributeModal" class="btn btn-primary waves-effect waves-float waves-light open_modal" data-url="{{route('admin.setting.modal_sec')}}">Add Amount</a></th>
                                                        
                                                    </tr>
                                                    @endif
                                                    <tr>
                                                        <th rowspan="2">
                                                            Client
                                                            @if(auth()->user()->user_role == "Super Admin")
                                                                <div class="dropdown" style="display:inline-block;">
                                                                    <button class="btn btn-light dropdown-toggle" type="button" id="userMultiSelectDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 14px; width: 120px;">
                                                                        Filter Users
                                                                    </button>
                                                                    <ul class="dropdown-menu p-2" aria-labelledby="userMultiSelectDropdown" style="max-height: 300px; overflow-y: auto; min-width: 180px;">
                                                                        <li>
                                                                            <label class="dropdown-item">
                                                                                <input type="checkbox" id="userFilterAll" checked> All
                                                                            </label>
                                                                        </li>
                                                                        @php
                                                                            $userIds = [];
                                                                        @endphp
                                                                        @foreach($data as $item)
                                                                            @php
                                                                                $user = $item['user'];
                                                                            @endphp
                                                                            @if(!in_array($user->id, $userIds))
                                                                                <li>
                                                                                    <label class="dropdown-item">
                                                                                        <input type="checkbox" class="userFilterCheckbox" value="{{ $user->id }}" checked> {{ $user->name }}
                                                                                    </label>
                                                                                </li>
                                                                                @php $userIds[] = $user->id; @endphp
                                                                            @endif
                                                                        @endforeach
                                                                    </ul>
                                                                </div>
                                                            @endif
                                                        </th>
                                                        @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Client")
                                                        <th rowspan="2">Previous Balance</th>
                                                        <th colspan="4">Payin</th>
                                                        <th colspan="4">Payout</th>
                                                        <th rowspan="2">USDT</th>
                                                        <th rowspan="2">Wallet Transfer</th>
                                                        @endif
                                                        <th rowspan="2">Unsettled (Payable)</th>
                                                        <th rowspan="2" colspan="2">Wallet</th>
                                                        @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Manager" || auth()->user()->user_role == "Client")
                                                        <th colspan="3" rowspan="3">Balance</th>
                                                        @endif
                                                        @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Manager")
                                                        <th rowspan="2">REV</th>
                                                        <th colspan="3" rowspan="2">USDT & Wallet</th>
                                                        @endif
                                                    </tr>
                                                    <tr>
                                                        @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Client")
                                                        <th>JC</th>
                                                        <th>EP</th>
                                                        <th>Total</th>
                                                        <th>Deduction</th>
                                                        <th>JC</th>
                                                        <th>IBFT</th>
                                                        <th>EP</th>
                                                        <th>Total</th>
                                                        @endif
                                                        {{--<th>JC</th>
                                                        <th>EP</th>
                                                        <th>Total</th>--}}
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($data as $item)
                                                        @php
                                                            $user = $item['user'];
                                                        @endphp
                                                    
                                                        @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Manager" || auth()->user()->id == $user->id)
                                                        <tr data-user-id="{{ $user->id }}">
                                                            <td class="client">{{ $user->name }}</td>
                                                    
                                                            @if(auth()->user()->user_role == "Super Admin" || auth()->user()->id == $user->id)
                                                                <td data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="prev_balance">{{ number_format($item['prev_balance']) }}</td>
                                                                <td class="bg-green" data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="jc_payin">{{ number_format($item['jc_payin']) }}</td>
                                                                <td class="bg-green" data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="ep_payin">{{ number_format($item['ep_payin']) }}</td>
                                                                <td class="bg-green font-weight-bold" data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="total_payin">{{ number_format($item['total_payin']) }}</td>
                                                                <td class="font-weight-bold text-red" data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="reverse_amount">{{ number_format($item['reverse_amount']) }}</td>
                                                                <td class="bg-red" data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="jc_payout">{{ number_format($item['jc_payout']) }}</td>
                                                                <td class="bg-red" data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="ibft_amount">{{ number_format($item['ibft_amount']) }}</td>
                                                                <td class="bg-red" data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="ep_payout">{{ number_format($item['ep_payout']) }}</td>
                                                                <td class="bg-red font-weight-bold" data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="total_payout">{{ number_format($item['total_payout']) }}</td>
                                                                <td data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="prev_usdt">{{ number_format($item['prev_usdt']) }}</td>
                                                                <td data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="wallet_transfer">{{ number_format($item['wallet_transfer']) }}</td>
                                                            @endif
                                                    
                                                            <td class="font-weight-bold text-red" data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="unsettled_amount">{{ number_format($item['unsettled_amount']) }}</td>
                                                            <td colspan="2" class="bg-gray font-weight-bold" data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="assigned_payout">{{ number_format($item['assigned_amount']->payout_balance ?? 0) }}</td>
                                                    
                                                            @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Manager" || auth()->user()->id == $user->id)
                                                            <td class="bg-warning" data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="unsettled_amount_balance">{{ number_format(round($item['unsettled_amount_balance'], 0)) }}</td>
                                                            @endif
                                                            @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Manager" || auth()->user()->id == 4 || auth()->user()->id == 23)
                                                            <td class="bg-warning">
                                                                <div class="d-flex justify-content-start">
                                                                        <a class="dropdown-item btn btn-primary w-auto open_modal me-1" 
                                                                           data-url="{{ route('admin.setting.modal') }}" 
                                                                           data-id="{{ $user->id }}">
                                                                            <i class="fa fa-edit"></i>
                                                                        </a>
                                                                </div>
                                                            </td>
                                                            @endif
                                                            @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Manager")
                                                            <td class="bg-warning">
                                                                <div class="form-check form-switch">
                                                                    <input 
                                                                        class="form-check-input toggle-switch" 
                                                                        type="checkbox" 
                                                                        data-id="{{ $user->id }}"
                                                                        data-type="auto"
                                                                        @if($item['setting']->auto == 1) checked @endif>
                                                                </div>
                                                            </td>
                                                            <td class="font-weight-bold text-green" data-poll-scope="row" data-user-id="{{ $user->id }}" data-poll-metric="rev_cln">{{ number_format($item['rev_cln']) }}</td>
                                                            <td>
                                                                <a data-target="#attributeModal"
                                                                    class="btn btn-primary waves-effect waves-float waves-light open_modal" 
                                                                    data-url="{{route('admin.settlement.modal',$item['set_id'])}}">
                                                                    Adjustment
                                                                </a>
                                                            </td>
                                                            @endif
                                                        </tr>
                                                        @endif
                                                    @endforeach
                                                    
                                                    @if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Manager")
                                                        <tr>
                                                            <td class="client font-weight-bold">Total</td>
                                                        
                                                            @if(auth()->user()->user_role == "Super Admin")
                                                                <td class="font-weight-bold" data-poll-scope="totals" data-poll-metric="prev_balance">{{ number_format($totals['prev_balance']) }}</td>
                                                                <td class="bg-green font-weight-bold" data-poll-scope="totals" data-poll-metric="jc_payin">{{ number_format($totals['jc_payin']) }}</td>
                                                                <td class="bg-green font-weight-bold" data-poll-scope="totals" data-poll-metric="ep_payin">{{ number_format($totals['ep_payin']) }}</td>
                                                                <td class="bg-green font-weight-bold" data-poll-scope="totals" data-poll-metric="total_payin">{{ number_format($totals['total_payin']) }}</td>
                                                                <td class="bg-green font-weight-bold" data-poll-scope="totals" data-poll-metric="reverse_amount">{{ number_format($totals['reverse_amount']) }}</td>
                                                                <td class="bg-red font-weight-bold" data-poll-scope="totals" data-poll-metric="jc_payout">{{ number_format($totals['jc_payout']) }}</td>
                                                                <td class="bg-red font-weight-bold" data-poll-scope="totals" data-poll-metric="total_ibft_amount">{{ number_format($totals['total_ibft_amount']) }}</td>
                                                                <td class="bg-red font-weight-bold" data-poll-scope="totals" data-poll-metric="ep_payout">{{ number_format($totals['ep_payout']) }}</td>
                                                                <td class="bg-red font-weight-bold" data-poll-scope="totals" data-poll-metric="total_payout">{{ number_format($totals['total_payout']) }}</td>
                                                                <td class="font-weight-bold" data-poll-scope="totals" data-poll-metric="prev_usdt">{{ number_format($totals['prev_usdt']) }}</td>
                                                                <td class="font-weight-bold" data-poll-scope="totals" data-poll-metric="wallet_transfer">{{ number_format($totals['wallet_transfer']) }}</td>
                                                            @endif
                                                        
                                                            <td class="font-weight-bold text-red" data-poll-scope="totals" data-poll-metric="unsettled_amount">{{ number_format($totals['unsettled_amount']) }}</td>
                                                            <td colspan="2" class="bg-gray font-weight-bold" data-poll-scope="totals" data-poll-metric="assigned_payout">{{ number_format($totals['assigned_payout']) }}</td>
                                                            <td colspan="3" class="bg-warning font-weight-bold" data-poll-scope="totals" data-poll-metric="unsettled_amount_balance">{{ number_format($totals['unsettled_amount_balance']) }}</td>
                                                            <td class="font-weight-bold text-green" data-poll-scope="totals" data-poll-metric="total_rev_cln">{{ number_format($totals['total_rev_cln']) }}</td>
                                                        </tr>
                                                    @endif

                                                </tbody>
                                                {{--<thead class="border">
                                                    <tr class="text-center">
                                                        <th>Date</th>
                                                        <th>Opening Bal</th>
                                                        <th>EP Payin</th>
                                                        <th>Complaint Deduction</th>
                                                        <th>Transfered to Wallet</th>
                                                        <th>USDT</th>
                                                        <th>Closing Bal/Unsettled</th>
                                                        @if(auth()->user()->user_role == "Super Admin")
                                                            <th rowspan="2">Action</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody class="border">
                                                    <tr class="text-center">
                                                        <td>{{ $results->date->format('d-M') }}</td>
                                                        <td>{{ number_format(round($results->opening_bal,0))}}</td>
                                                        <td>{{ number_format(round($results->ep_payin,0)) }}</td>
                                                        <td>{{ number_format(round($results->reverse_amount)) }}</td>
                                                        <td>{{ number_format(round($results->ep_payout,0)) }}</td>
                                                        <td>{{ number_format(round($results->usdt,0)) }}</td>
                                                        <td class="font-weight-bold text-red">{{ number_format($results->closing_bal) }}</td>
                                                        @if(auth()->user()->user_role == "Super Admin")
                                                            <td>
                                                                <a data-target="#attributeModal"
                                                                   class="btn btn-primary waves-effect waves-float waves-light open_modal" 
                                                                   data-url="{{route('admin.settlement.modal',$results->id)}}">
                                                                    Manual
                                                                </a>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                </tbody>--}}
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <x-dashboard-metrics.panel
                    :clients="$dashboardMetricClients"
                    :metrics-payload="$dashboardMetricsPayload"
                />
            </section>
        </div>
    </div>
</div>
@endsection
@push('js')
<script>
$(document).ready(function () {
    $('.toggle-switch').on('change', function () {
        const isChecked = $(this).is(':checked'); 
        const id = $(this).data('id');
        const type = $(this).data('type');
        // Send AJAX request to update backend
        updateToggleStatus(id, type, isChecked);
    });

    function updateToggleStatus(id, type, status) {
        $.ajax({
            url: '{{ route("admin.setting.api.suspend") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            contentType: 'application/json',
            data: JSON.stringify({ id: id, type: type, status: status ? 1 : 0 }),
            success: function (response) {
                console.log('Toggle updated:', response);
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
            },
        });
    }
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Multi-select user filter logic
    const allCheckbox = document.getElementById('userFilterAll');
    const userCheckboxes = document.querySelectorAll('.userFilterCheckbox');

    function updateTableVisibility() {
        const checkedUserIds = Array.from(userCheckboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        if (allCheckbox.checked || checkedUserIds.length === userCheckboxes.length) {
            // Show all rows
            document.querySelectorAll('tbody tr[data-user-id]').forEach(row => {
                row.style.display = '';
            });
            allCheckbox.checked = true;
            userCheckboxes.forEach(cb => cb.checked = true);
        } else {
            document.querySelectorAll('tbody tr[data-user-id]').forEach(row => {
                if (checkedUserIds.includes(row.getAttribute('data-user-id'))) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            allCheckbox.checked = false;
        }
    }

    allCheckbox.addEventListener('change', function () {
        if (this.checked) {
            userCheckboxes.forEach(cb => cb.checked = true);
        } else {
            userCheckboxes.forEach(cb => cb.checked = false);
        }
        updateTableVisibility();
    });

    userCheckboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            const checkedCount = Array.from(userCheckboxes).filter(cb => cb.checked).length;
            if (checkedCount === userCheckboxes.length) {
                allCheckbox.checked = true;
            } else {
                allCheckbox.checked = false;
            }
            updateTableVisibility();
        });
    });

    // Initial state
    updateTableVisibility();
});
</script>
<script>
    $(document).ready(function () {

        $('.payout-radio').on('change', function () {

            const id = $(this).data('id');

            updateCheckedTogglePayout(id);
        });

        function updateCheckedTogglePayout(id) {
            var URL = '{{ route("admin.setting.payout_setting") }}';

            $.ajax({
                url: URL,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                contentType: 'application/json',
                data: JSON.stringify({ id: id }),
                success: function (response) {
                    console.log(response);
                },
                error: function (xhr) {
                    console.error('Error:', xhr.responseText);
                },
            });
        }
    });
</script>
@if(auth()->user()->user_role == "Super Admin" || auth()->user()->user_role == "Manager")
<script>
    window.settlementDashboardPollConfig = {
        enabled: true,
        url: @json(route('admin.dashboard.settlement_grid')),
    };
</script>
<script src="{{ asset('js/settlement-dashboard-poll.js') }}"></script>
@endif
@endpush