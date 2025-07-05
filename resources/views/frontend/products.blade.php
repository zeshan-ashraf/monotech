@extends('layouts.main')
@section('title',' - Our Products')
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
                <h1 class="title">Products</h1>
                <h4 class="sub-title">
                    <span class="home">
                        <a href="{{ route('home')}}">
                            <span>Home</span>
                        </a>
                    </span>
                    <span class="icon">/</span>
                    <span class="inner">
                        <span>Our Products</span>
                    </span>
                </h4>
            </div>
        </div>
    </section>
    <!-- ./ page-header -->

    <section class="service-section-3 pt-120 pb-120">
        <div class="bg-shape"><img src="{{asset('assets/img/shapes/service-bg-shape.png')}}" alt="img"></div>
        <div class="container-2">
            <div class="section-heading text-center red-content">
                <h4 class="sub-heading" data-text-animation="fade-in" data-duration="1.5"><span class="left-shape"></span>Our Products</h4>
                <h2 class="section-title mb-0" data-text-animation data-split="word" data-duration="1">We will help to grow business</h2>
            </div>
            <div class="row gy-lg-0 gy-4 justify-content-center fade-wrapper">
                @foreach($list as $item)
                    <div class="col-lg-4 col-md-6">
                        <div class="service-item-3 fade-top">
                            <div class="service-thumb">
                                <img class="img-item" src="{{asset($item->image)}}" alt="service">
                            </div>
                            <div class="service-content">
                                <h3 class="title"><a href="#">{{$item->name}}</a></h3>
                                <h6>Rs. {{$item->price}}</h6>
                                <a href="{{ route('product.detail',$item->id)}}" class="bz-primary-btn red-btn">Buy Product (Rs. {{$item->price}}) <i class="fa-regular fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection