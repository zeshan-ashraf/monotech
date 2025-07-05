@extends('layouts.main')
@section('title',' - Product Detail')
@push('css')
    <style>
        .service-item-3 .service-thumb{
            height: 50% !important;
        }
        .service-item-3 .service-thumb .img-item
        {
            filter: none !important;
        }
    </style>
@section('content')

    <section class="page-header">
        <div class="bg-img" data-background="{{asset('assets/img/bg-img/page-header-bg.jpg')}}"></div>
        <div class="overlay"></div>
        <div class="shapes">
            <div class="shape shape-1"><img src="{{asset('assets/img/shapes/pager-header-shape-1.png')}}" alt="shape"></div>
            <div class="shape shape-2"><img src="{{asset('assets/img/shapes/pager-header-shape-2.png')}}" alt="shape"></div>
        </div>
        <div class="container">
            <div class="page-header-content">
                <h1 class="title">Product Detail</h1>
                <h4 class="sub-title">
                    <span class="home">
                        <a href="{{ route('home')}}">
                            <span>Home</span>
                        </a>
                    </span>
                    <span class="icon">/</span>
                    <span class="inner">
                        <a href="{{ route('products')}}">
                            <span>Our Products</span>
                        </a>
                    </span>
                    <span class="icon">/</span>
                    <span class="inner">
                        <span>Product Detail</span>
                    </span>
                </h4>
            </div>
        </div>
    </section>
    <!-- ./ page-header -->

    <div class="team-details-form">
        <div class="heading-area text-center">
            <h3 class="title">Buy Product</h3>
        </div>
        <form action="{{route('checkout')}}" method="post" role="form">
            @csrf
            <input type="hidden" name="client_email" value="megasolution@novaconnect.com">
            <input type="hidden" name="amount" value="{{$item->price}}">
            <div class="row">
                <div class="col-md-8 m-auto">
                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th width="10%">Image</th>
                                <th scope="col">Product Name</th>
                                <th scope="col">Price</th>
                                <th scope="col">Quantity</th>
                                <th scope="col">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><img src="{{asset($item->image)}}" style="width:150px;height:150px">
                                </td>
                                <td>{{$item->name}}</td>
                                <td>{{$item->price}}</td>
                                <td>1</td>
                                <td>{{$item->price}}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-8 m-auto">
                    <h4 class="text-center">Billing Detail</h4>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-8 m-auto">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="text" name="first_name" class="form-control rounded-pill py-4 px-3"
                                    id="first_name" placeholder="Enter First Name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="text" name="last_name" class="form-control rounded-pill py-4 px-3"
                                    id="last_name" placeholder="Enter Last Name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="text" name="phone" class="form-control rounded-pill py-4 px-3"
                                    id="phone" placeholder="Enter Phone Number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="email" class="form-control rounded-pill py-4 px-3" name="email"
                                    id="email" placeholder="Enter Email">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <input type="text" class="form-control rounded-pill py-4 px-3" name="address"
                                    id="address" placeholder="Enter Address">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div id="payment" class="woocommerce-checkout-payment">
                                <h3>Payment Methods</h3>
                                <ul class="wc_payment_methods payment_methods methods list-unstyled">
                                    <li class="wc_payment_method payment_method_jazzcash">
                                        <span class="custom-checkbox">
                                            <input id="payment_method_jazzcash" type="radio" class="input-radio"
                                                name="payment_method" value="jazzcash" checked="checked"
                                                data-order_button_text="">
                                            <label for="payment_method_jazzcash">
                                                <img width="90" src="{{asset('assets/img/jazzcash.jfif')}}"
                                                    alt="JazzCash Debit/Credit Card"> <span
                                                    class="checkmark"></span>
                                            </label>
                                        </span>
                                    </li>
                                    <li class="wc_payment_method payment_method_easypaisa">
                                        <span class="custom-checkbox">
                                            <input id="payment_method_easypaisa" type="radio" class="input-radio"
                                                name="payment_method" value="easypaisa" data-order_button_text="">
                                            <label for="payment_method_easypaisa">
                                                <img width="105" src="{{asset('assets/img/easypaisa.jpg')}}"
                                                    alt="Easypaisa"> <span class="checkmark"></span>
                                            </label>
                                        </span>

                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="g-recaptcha" data-sitekey="6Lf4xscqAAAAAEx6EhfK3bQaVFnHmjl-czxwk_yN"></div>
                        </div>
                        <div class="col-md-12">
                            <div class="text-center"><button type="submit" class="btn btn-primary rounded-pill px-4"
                                    title="Send Message">Rs. {{$item->price}} - Checkout</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
@section('js')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>

</script>
@endsection