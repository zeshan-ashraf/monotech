@extends('admin.layout.app')
@section('title','Client Fee')
@push('css')
<link rel="stylesheet" href="{{ asset('admin/assets/dashboard/css/dataTables.bootstrap4.min.css') }}" />
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
                        <div class="card">
                            <div class="card-header border-bottom d-flex justify-content-between">
                                <h4 class="card-title text-capitalize">Client Fee</h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="material-datatables">
                                    <table class="table table-hover m-b-0 datatables" cellspacing="0" width="100%" style="width:100%">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>JC Payin Fee</th>
                                                <th>EP Payin Fee</th>
                                                <th>JC Payout Fee</th>
                                                <th>EP Payout Fee</th>
                                                <th>Per Transaction Payin Fee</th>
                                                <th>Per Transaction Payout Fee</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($list as $item)
                                            <tr>
                                                <td>{{$loop->iteration}}</td>
                                                <td>{{$item->name}}</td>
                                                <td>{{$item->payin_fee}}</td>
                                                <td>{{$item->payin_ep_fee}}</td>
                                                <td>{{$item->payout_fee}}</td>
                                                <td>{{$item->payout_ep_fee}}</td>
                                                <td>{{$item->per_payin_fee}}</td>
                                                <td>{{$item->per_payout_fee}}</td>
                                                <td>
                                                    <div class="d-flex justify-content-start">
                                                        <a class="dropdown-item btn btn-primary w-auto open_modal me-1" data-url="{{route('admin.client.modal.sec')}}" data-id="{{$item->id}}" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" data-bs-original-title="Edit">
                                                            <i class="fa fa-edit" ></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
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
