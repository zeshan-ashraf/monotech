@extends('admin.layout.app')
@section('title','Profile')
@section('content')
<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="content-wrapper container-xxl pt-0 px-0 pb-sm-0 pb-5">
        <div class="content-header row">

        </div>
        <div class="content-body">
            <div class="row">
                <div class="col-12">
                    <ul class="nav nav-pills mb-2">
                        <li class="nav-item">
                            <a class="nav-link active" href="{{route('admin.profile')}}">
                                <i data-feather="user" class="font-medium-3 me-50"></i>
                                <span class="fw-bold">Account</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{route('admin.account.settings')}}">
                                <i data-feather="lock" class="font-medium-3 me-50"></i>
                                <span class="fw-bold">Settings</span>
                            </a>
                        </li>
                    </ul>
                    <div class="card">
                        <div class="card-header border-bottom">
                            <h4 class="card-title">Profile Details</h4>
                        </div>
                        <div class="card-body py-2 my-25">
                            <form class="validate-form mt-2 pt-50" method="POST"
                                action="{{route('admin.profile.save')}}" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="d-flex justify-content-between flex-column col-xl-6 col-21"
                                        style="width: 100%">
                                        <div class="d-flex justify-content-start"><span
                                                class="b-avatar badge-light-danger rounded"
                                                style="width: 80px; height: 80px;"><span class="b-avatar-img"><img
                                                        class="profile-img" src="{{asset('admin/images/person.jpg')}}" alt="avatar"
                                                        width="100%"></span></span>
                                            <div class="d-flex flex-column ml-1" style="margin-left:20px">
                                                <label for="account-upload"
                                                    class="btn btn-sm btn-primary mb-75 mr-75 ">Upload</label>
                                                <input type="file" name="profile_image" class="upload-image"
                                                    id="account-upload" hidden accept="image/*" />
                                                <button type="button"
                                                    class="btn mb-75 mr-75 btn-outline-secondary btn-sm reset-image">
                                                    Reset
                                                </button>
                                            </div>
                                        </div>
                                        <p class="card-text text-danger">Allowed JPG or PNG. Max size 800kB.</p>
                                    </div>
                                    <div class="col-12 col-sm-6 mb-1">
                                        <label class="form-label">Name</label>
                                        <input type="text" class="form-control" required name="first_name" value="{{$user->name}}" />
                                    </div>

                                    <div class="col-12 col-sm-6 mb-1">
                                        <label class="form-label" for="accountEmail">Email</label>
                                        <input type="email" class="form-control" name="email"
                                            value="{{$user->email}}" />
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary mt-1 me-1">Save changes</button>
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
@endsection
@push('js')
<script>
    $(document).ready(function () {
        var readURL = function (input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $('.profile-img').attr('src', e.target.result);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        $(".upload-image").on('change', function () {
            readURL(this);
        });
        $(".reset-image").on('click', function () {
            let image = '{{$user->profile_image}}';
            $('.profile-img').attr('src', image);
        });

    });

</script>
@endpush
