@extends('admin.layout.app')
@section('title','Roles')

@section('heading','Roles & Permissions')

@section('content')

<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="content-wrapper container-xxl pt-0 px-0 pb-sm-0 pb-5">
        <div class="content-header row">
        </div>
        <div class="content-body">
            <section class="invoice-preview-wrapper">
                <div class="row invoice-preview">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Roles</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="card collapse-icon accordion-icon-rotate min-height-500">

                                                <div class="accordion" id="accordionExample" data-toggle-hover="true">

                                                    @foreach($roles as $role)

                                                    <div class="collapse-margin">

                                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $role->id }}" aria-expanded="true" aria-controls="collapse{{ $role->id }}">
                                                            <span class="lead collapse-title fw-bold">
                                                                {{ $role->name }}
                                                            </span>
                                                          </button>
                                                        <div id="collapse{{ $role->id }}" class="collapse" data-bs-parent="#accordionExample">
                                                            <div class="card-body">
                                                                @foreach($role->permissions as $permission)
                                                                    <span  class="badge bg-primary text-capitalize">{{ str_replace('_', ' ', $permission->name) }}</span>
                                                                @endforeach
                                                                <hr>
                                                                <div class="text-end">
                                                                    {{-- <a href="{{ route('admin.roles.delete', $role->id) }}" class="btn btn-danger font-weight-bold"
                                                                        onclick="return confirm('Do you realy want to delete this role? The user connected with this role will be exempted when you delete it.')">Delete {{ $role->name }}</a> --}}
                                                                    <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-relief-dark">Update</a>
                                                                </div>

                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr class="m-0">

                                                    @endforeach

                                                </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="card min-height-500">
                                            <div class="card-body">
                                                <h4 class="pb-1">Add New Role</h4>

                                                @if (count($errors) > 0)
                                                    <div class="alert alert-danger">
                                                        <ul>
                                                            @foreach ($errors->all() as $error)
                                                                <li>{{ $error }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif

                                                <form class="form form-horizontal roles-form" action="{{ route('admin.roles.store') }}" method="POST">
                                                    @csrf
                                                    <div class="form-body">
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="form-group row">
                                                                    <div class="col-md-12">
                                                                        <div class="position-relative has-icon-left">
                                                                            <input type="text" id="fname-icon" class="form-control" name="name" placeholder="Role Name" autocomplete="off" required>
                                                                            <div class="form-control-position">
                                                                                <i class="feather icon-zap"></i>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>



                                                            <div class="col-12">
                                                                <div class="table-responsive border rounded px-1">
                                                                            
                                                                    <div class="row border-bottom">
                                                                        <div class="col-12 d-flex align-items-center">
                                                                            <input class="form-check-input permission_all mx-25 py-0 mx-1 px-50" type="checkbox" id="" checked>
                                                                            <h6 class=" py-1 mx-1 mb-0 font-medium-2">Permission</h6>
                                                                            <input class="form-control search_permission" type="text" placeholder="Search permission">
                                                                        </div>
                                                                    </div>
                                                                    <ul class="list-group list-group-flush list-group" style="max-height: 280px;overflow: auto;">
                                                                        @foreach($permissions as $permission)
                                                                        <li class="list-group-item">
                                                                            <input class="form-check-input check_per" type="checkbox" id="{{ $permission->id }}" value="{{ $permission->name }}" name="permissions[]" checked>
                                                                            <label class="form-check-label text-capitalize float-right font-weight-bold" for="{{ $permission->id }}">{{ str_replace('_', ' ', $permission->name) }}</label>
                                                                        </li>
                                                                        @endforeach

                                                                    </ul>
                                                                </div>
                                                            </div>

                                                            <div class="col-md-12 text-right mt-2 d-flex justify-content-end">

                                                                <div class="">
                                                                    <button type="submit" class="btn btn-primary ms-50">Submit</button>
                                                                    <a class="btn btn-danger" href="{{ url()->previous() }}">Cancel</a>
                                                                </div>


                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
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

