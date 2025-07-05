@extends('admin.layout.app')
@section('title','Security')
@section('content')
<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="content-wrapper container-xxl pt-0 px-0 pb-sm-0 pb-5">
        <div class="content-body">
            <div class="row">
                <div class="col-12">
                    <ul class="nav nav-pills mb-2">
                        <!-- account -->
                        <li class="nav-item">
                            <a class="nav-link " href="{{route('admin.profile')}}">
                                <i data-feather="user" class="font-medium-3 me-50"></i>
                                <span class="fw-bold">Account</span>
                            </a>
                        </li>
                        <!-- security -->
                        <li class="nav-item">
                            <a class="nav-link active" href="{{route('admin.account.settings')}}">
                                <i data-feather="lock" class="font-medium-3 me-50"></i>
                                <span class="fw-bold">Settings</span>
                            </a>
                        </li>
                    </ul>

                    <!-- security -->

                    <div class="card">
                        <div class="card-header border-bottom">
                            <h4 class="card-title">Change Password</h4>
                        </div>
                        <div class="card-body pt-1">
                            <!-- form -->
                            <form class="validate-form" method="POST" action="{{route('admin.security.update')}}">
                                @csrf
                                <div class="row">
                                    <div class="col-12 col-sm-6 mb-1">
                                        <label class="form-label" for="account-new-password">New Password</label>
                                        <div class="input-group form-password-toggle input-group-merge">
                                            <input type="password" id="account-new-password" name="password"
                                                class="form-control" placeholder="Enter new password" />
                                            <div class="input-group-text cursor-pointer">
                                                <i data-feather="eye"></i>
                                            </div>
                                        </div>
                                        @error('password')
                                        <p class="error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-12 col-sm-6 mb-1">
                                        <label class="form-label" for="account-retype-new-password">Retype New
                                            Password</label>
                                        <div class="input-group form-password-toggle input-group-merge">
                                            <input type="password" class="form-control" id="account-retype-new-password"
                                                name="password_confirmation" placeholder="Confirm your new password" />
                                            <div class="input-group-text cursor-pointer"><i data-feather="eye"></i>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-12">
                                        <p class="fw-bolder">Password requirements</p>
                                        <ul class="ps-1 ms-25">
                                            <li class="mb-50">Minimum 8 characters long - the more, the better</li>
                                            <li class="mb-50">At least one lowercase character</li>
                                            <li>At least one number, symbol, or whitespace character</li>
                                        </ul>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary me-1 mt-1">Save changes</button>
                                    </div>
                                </div>
                            </form>
                            <!--/ form -->
                        </div>
                    </div>




                </div>
            </div>
            <!-- two factor auth modal -->


        </div>
    </div>
</div>
@endsection
@push('js')

@endpush
