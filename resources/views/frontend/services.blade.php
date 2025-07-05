@extends('layouts.main')
@section('title',' - Our Services')
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
                    <h1 class="title">Our Services</h1>
                    <h4 class="sub-title">
                        <span class="home">
                            <a href="{{ route('home')}}">
                                <span>Home</span>
                            </a>
                        </span>
                        <span class="icon">/</span>
                        <span class="inner">
                            <span>Our Services</span>
                        </span>
                    </h4>
                </div>
            </div>
        </section>
        <!-- ./ page-header -->

        <section class="service-section-4 pt-120 pb-120">
            <div class="container">
                <div class="row gy-4 fade-wrapper">
                    <div class="col-lg-4 col-md-6">
                        <div class="service-item-4 fade-top">
                            <div class="service-thumb">
                                <img class="main-img" src="{{asset('assets/img/images/faq-img.png')}}" alt="service">
                                <div class="icon"><img src="{{asset('assets/img/icon/service-icon-5.png')}}" alt="icon"></div>
                            </div>
                            <div class="service-content">
                                <h3 class="title"><a href="#">Pay Any Time, Any Where</a></h3>
                                <p>Reminders for upcoming bill payments. We remember due dates so you don’t have to.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="service-item-4 fade-top">
                            <div class="service-thumb">
                                <img class="main-img" src="{{asset('assets/img/images/strength-img-1.png')}}" alt="service">
                                <div class="icon"><img src="{{asset('assets/img/icon/service-icon-6.png')}}" alt="icon"></div>
                            </div>
                            <div class="service-content">
                                <h3 class="title"><a href="#">Pay with Credit Card</a></h3>
                                <p>No Hidden charges, no annual fee, and no SMS surcharge.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="service-item-4 fade-top">
                            <div class="service-thumb">
                                <img class="main-img" src="{{asset('assets/img/images/process-img-1.png')}}" alt="service">
                                <div class="icon"><img src="{{asset('assets/img/icon/service-icon-7.png')}}" alt="icon"></div>
                            </div>
                            <div class="service-content">
                                <h3 class="title"><a href="#">Direct Funds</a></h3>
                                <p>It just takes a few moments for you to transfer money to your family, friends or business partners using Monotech.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="service-item-4 fade-top">
                            <div class="service-thumb">
                                <img class="main-img" src="{{asset('assets/img/images/thumb-02.jpg')}}" alt="service">
                                <div class="icon"><img src="{{asset('assets/img/icon/service-icon-8.png')}}" alt="icon"></div>
                            </div>
                            <div class="service-content">
                                <h3 class="title"><a href="#">Payment Link</a></h3>
                                <p>Monotech enables its customers with the freedom of making payments for their online purchases in a convenient, secure and swift manner!</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="service-item-4 fade-top">
                            <div class="service-thumb">
                                <img class="main-img" src="{{asset('assets/img/images/thumb-03.jpg')}}" alt="service">
                                <div class="icon"><img src="{{asset('assets/img/icon/service-icon-9.png')}}" alt="icon"></div>
                            </div>
                            <div class="service-content">
                                <h3 class="title"><a href="#">Generate Invoice</a></h3>
                                <p>Automatically generates invoices, streamlining the billing process for efficient and accurate transactions.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="service-item-4 fade-top">
                            <div class="service-thumb">
                                <img class="main-img" src="{{asset('assets/img/images/thumb-01.jpg')}}" alt="service">
                                <div class="icon"><img src="{{asset('assets/img/icon/service-icon-10.png')}}" alt="icon"></div>
                            </div>
                            <div class="service-content">
                                <h3 class="title"><a href="#">Integration</a></h3>
                                <p>Monotech seamlessly integrates with various platforms, streamlining financial transactions for businesses</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- ./ service-section -->

        <section class="service-counter pt-120 pb-120">
            <div class="bg-item">
                <div class="bg-img" data-background="assets/img/bg-img/service-counter-bg.jpg"></div>
                <div class="overlay"></div>
                <div class="shapes">
                    <div class="shape shape-1"><img src="{{asset('assets/img/shapes/service-counter-shape-1.png')}}" alt="shape"></div>
                    <div class="shape shape-2"><img src="{{asset('assets/img/shapes/service-counter-shape-2.png')}}" alt="shape"></div>
                </div>
            </div>
            <div class="container">
                <div class="section-heading text-center white-content">
                    <h4 class="sub-heading" data-text-animation="fade-in" data-duration="1.5"><span class="left-shape"></span>company strength</h4>
                    <h2 class="section-title mb-0" data-text-animation data-split="word" data-duration="1">Our Team Build Amazing Growth</h2>
                </div>
                <div class="row gy-lg-0 gy-4 justify-content-center fade-wrapper">
                    <div class="col-lg-4 col-md-6">
                        <div class="counter-card fade-top">
                            <div class="icon"><img src="{{asset('assets/img/icon/counter-1.png')}}" alt="icon"></div>
                            <div class="content">
                                <h3 class="title"><span class="odometer" data-count="858">0</span>+</h3>
                                <p>Live Projects</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="counter-card fade-top">
                            <div class="icon"><img src="{{asset('assets/img/icon/counter-2.png')}}" alt="icon"></div>
                            <div class="content">
                                <h3 class="title"><span class="odometer" data-count="16">0</span>k+</h3>
                                <p>Service Complete</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="counter-card fade-top">
                            <div class="icon"><img src="{{asset('assets/img/icon/counter-3.png')}}" alt="icon"></div>
                            <div class="content">
                                <h3 class="title"><span class="odometer" data-count="92">0</span>+</h3>
                                <p>Global Awards</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- ./ service-section -->
 @endsection