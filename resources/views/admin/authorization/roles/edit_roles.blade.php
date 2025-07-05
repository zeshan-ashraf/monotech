@extends('admin.layout.app')
@section('title', 'Roles')
@section('heading', 'Update Role')

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
                                    <h4 class="card-title">Role Edit</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-12">

                                            <form class="form form-horizontal roles-form"
                                                action="{{ route('admin.roles.update', $role->id) }}" method="POST">
                                                @csrf
                                                @method('patch')
                                                <div class="form-body">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="form-group row">
                                                                <div class="col-md-12">
                                                                    <div class="position-relative has-icon-left">
                                                                        <input type="text" id="fname-icon"
                                                                            class="form-control" name="name"
                                                                            value="{{ $role->name }}"
                                                                            placeholder="Role Name" autocomplete="off"
                                                                            required>
                                                                        <div class="form-control-position">
                                                                            <i class="feather icon-zap"></i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>



                                                        <div class="col-12">
                                                            <div class="table-responsive border rounded px-1 ">
                                                                 <div class="row border-bottom">
                                                                    <div class="col-12 d-flex align-items-center">
                                                                        <h6 class=" py-1 mx-1 mb-0 font-medium-2">Permission</h6>
                                                                        <input class="form-control search_permission" type="text" placeholder="Search permission">
                                                                    </div>
                                                                </div>
                                                                <ul class="list-group list-group-flush">

                                                                    @foreach ($permissions as $permission)
                                                                        <li class="list-group-item">
                                                                            <input class="form-check-input" type="checkbox"
                                                                                id="{{ $permission->id }}"
                                                                                value="{{ $permission->name }}"
                                                                                name="permissions[]"
                                                                                @if (in_array($permission->id, $role->permissions->pluck('id')->toArray())) checked @endif>
                                                                            <label
                                                                                class="form-check-label text-capitalize float-right font-weight-bold"
                                                                                for="{{ $permission->id }}">{{ str_replace('_', ' ', $permission->name) }}</label>
                                                                        </li>
                                                                    @endforeach

                                                                </ul>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-12 text-right mt-2 d-flex justify-content-end">

                                                            <div class="">
                                                                <button type="submit"
                                                                    class="btn btn-primary">Submit</button>
                                                                <a class="btn btn-danger"
                                                                    href="{{ url()->previous() }}">Cancel</a>
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
                </section>
            </div>
        </div>
    </div>



@endsection




