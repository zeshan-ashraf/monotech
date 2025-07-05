@extends('admin.layout.app')
@section('title','Sub Store')
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
                                <h4 class="card-title text-capitalize">Sub Stores</h4>
                                <div class="">
                                    <a data-target="#attributeModal"
                                        class="btn btn-primary waves-effect waves-float waves-light open_modal" data-url="{{route('admin.client.modal')}}">Add
                                        Sub Store</a>
                                </div>
                            </div>
                            <div class="card-body p-0">
                        <div class="material-datatables">
                            <table class="table table-hover m-b-0 datatables" cellspacing="0" width="100%" style="width:100%">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Image</th>
                                        <th>Created On</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($list as $item)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$item->name}}</td>
                                        <td>
                                            <img src="{{asset($item->image)}}" width="100" alt="">
                                        </td>
                                        <td>{{$item->created_at->format('d-m-Y')}}</td>
                                        <td>
                                            <div class="d-flex justify-content-start">
                                                <a class="dropdown-item btn btn-primary w-auto open_modal me-1" data-url="{{route('admin.client.modal')}}" data-id="{{$item->id}}" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit" data-bs-original-title="Edit">
                                                    <i class="fa fa-edit" ></i>
                                                </a>
                                                <a onclick="deleteAlert('{{ route('admin.client.destroy', $item->id) }}')"
                                                class="dropdown-item delete-btn btn btn-danger w-auto mr-10p" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete" data-bs-original-title="Delete">
                                                    <i class="fa fa-trash" ></i>
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
