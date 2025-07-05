@extends('layouts.main')
@section('title',' - Home')
@section('content')

    <section class="hero-section-3" data-background="{{asset('assets/img/bg-img/hero-bg-3.jpg')}}">
        <div class="shapes">
            <div class="shape shape-1"><img src="assets/img/shapes/hero-bg-shape-2.png" alt="shape"></div>
            <div class="shape shape-2"><img src="assets/img/shapes/hero-bg-shape-3.png" alt="shape"></div>
        </div>
        <div class="container-2">
            <div class="hero-content hero-content-3">
                <div class="section-heading mb-40 red-content">
                    <h4 class="sub-heading" data-text-animation="fade-in" data-duration="1.5"><span class="left-shape"></span>Provides Best Payment Solutions</h4>
                    <p>It’s time to go cashless. We offer a comprehensive payment solution that lets your business manage cash flows, present digital invoices and receive digital payments through a fast, easy and reliable one-window interface. Your clients can opt payment methods of their choice which include Digital Wallets (Jazzcash, Easypaisa), digital bank transfers, debit/credit cards etc. Get Registered for Free</p>
                </div>
                <div class="hero-btn-wrap" style="--bz-color-theme-primary: #EC281C">
                    <a href="{{route('contactus')}}" class="bz-primary-btn">Contact With Us <i class="fa-regular fa-arrow-right"></i></a>
                    <a href="{{route('products')}}" class="bz-primary-btn hero-btn">Our Products</a>
                </div>
            </div>
        </div>
    </section>
    <!-- ./ hero-section -->

    <section class="promo-section pb-120">
        <div class="container-2">
            <div class="row gy-lg-0 gy-4 justify-content-center fade-wrapper">
                <div class="col-lg-4 col-md-6">
                    <div class="promo-item white-content">
                        <div class="bg-items">
                            <div class="bg-img"><img src="assets/img/images/promo-1.png" alt="promo"></div>
                            <div class="overlay"></div>
                            <div class="overlay-2"></div>
                        </div>
                        <h3 class="title">Payment Solution</h3>
                        <p>A payment gateway is a secure link that processes transactions by connecting to the banking network for authorization, settlement, and reporting.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="promo-item white-content">
                        <div class="bg-items">
                            <div class="bg-img"><img src="assets/img/images/promo-2.png" alt="promo"></div>
                            <div class="overlay"></div>
                            <div class="overlay-2"></div>
                        </div>
                        <h3 class="title">Growth Business</h3>
                        <p>Sell online without a website. Share Payment Links with customer via SMS, email or WhatsApp and get paid instantly.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="promo-item white-content">
                        <div class="bg-items">
                            <div class="bg-img"><img src="assets/img/images/promo-3.png" alt="promo"></div>
                            <div class="overlay"></div>
                            <div class="overlay-2"></div>
                        </div>
                        <h3 class="title">Connected People</h3>
                        <p>Our secure payment gateway is trusted by businesses worldwide. It ensures safe and seamless transactions for every need.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ./ promo-section -->

    <div class="sponsor-section pb-50">
        <div class="container">
            <h3 class="sponsor-text-wrap">
                <span></span>
                <span class="sponsor-text">Our Clients</span>
                <span></span>
            </h3>
            <div class="sponsor-carousel swiper">
                <div class="swiper-wrapper">
                    @foreach($list as $item)
                        <div class="swiper-slide">
                            <div class="sponsor-item text-center">
                                <a href="{{$item->url}}" target="_blank">
                                    <img src="{{asset($item->image)}}" alt="{{$item->name}}">
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <section class="about-section-3 pb-120">
        <div class="container-2">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="about-img-3 img-reveal">
                        <div class="img-overlay overlay-2"></div>
                        <img src="assets/img/images/about-img-5.png" alt="about">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="about-content-3 fade-wrapper">
                        <div class="section-heading red-content mb-20">
                            <h4 class="sub-heading" data-text-animation="fade-in" data-duration="1.5"><span class="left-shape"></span>Why Monotech?</h4>
                        </div>
                        <ul class="about-list fade-top">
                            <li><span class="number">01</span>Accept payments effortlessly through multiple channels (online, offline, and mobile).</li>
                            <li><span class="number">02</span>Expand customer reach through convenient payment options (credit/debit cards, mobile wallets, and online banking).</li>
                            <li><span class="number">03</span>Enhance transaction security with advanced fraud detection and PCI-DSS compliance.</li>
                            <li><span class="number">04</span>Streamline payment processing with real-time settlements and reconciliation.</li>
                            <li><span class="number">05</span>Access actionable insights through data analytics and reporting.</li>
                        </ul>
                        <div class="about-btn fade-top">
                            <a href="{{route('contactus')}}" class="bz-primary-btn red-btn">Contact With Us <i class="fa-regular fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ./ about-section -->

    <section class="service-section-3 pt-120 pb-120">
        <div class="bg-shape"><img src="assets/img/shapes/service-bg-shape.png" alt="img"></div>
        <div class="container-2">
            <div class="section-heading text-center red-content">
                <h4 class="sub-heading" data-text-animation="fade-in" data-duration="1.5"><span class="left-shape"></span>Our Services</h4>
                <h2 class="section-title mb-0" data-text-animation data-split="word" data-duration="1">We will help to grow business</h2>
            </div>
            <div class="row gy-lg-0 gy-4 justify-content-center fade-wrapper">
                <div class="col-lg-4 col-md-6">
                    <div class="service-item-3 fade-top">
                        <div class="service-thumb">
                            <img class="img-item" src="assets/img/service/service-img-1.png" alt="service">
                            <div class="icon"><img src="assets/img/icon/service-1.png" alt="icon"></div>
                        </div>
                        <div class="service-content">
                            <h3 class="title"><a href="#">Pay Any Time, Any Where</a></h3>
                            <p>Get reminders for upcoming bill payments and others things as well. We track due dates, so you don’t have to.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-item-3 fade-top">
                        <div class="service-thumb">
                            <img class="img-item" src="assets/img/service/service-img-2.png" alt="service">
                            <div class="icon"><img src="assets/img/icon/service-1.png" alt="icon"></div>
                        </div>
                        <div class="service-content">
                            <h3 class="title"><a href="#">Generate Invoice</a></h3>
                            <p>Automatically generates invoices, streamlining the billing process for efficient and accurate transactions.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="service-item-3 fade-top">
                        <div class="service-thumb">
                            <img class="img-item" src="assets/img/service/service-img-3.png" alt="service">
                            <div class="icon"><img src="assets/img/icon/service-1.png" alt="icon"></div>
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

    <section class="cta-section cta-2 pt-120 pb-120">
        <div class="bg-img"><img src="assets/img/bg-img/cta-bg.jpg" alt="img"></div>
        <div class="overlay"></div>
        <div class="overlay-2"></div>
        <div class="container-2">
            <div class="cta-wrap">
                <div class="cta-content">
                    <div class="section-heading mb-0 white-content">
                        <h4 class="sub-heading" data-text-animation="fade-in" data-duration="1.5"><span class="left-shape"></span>Explore Our Services</h4>
                        <h2 class="section-title mb-0" data-text-animation data-split="word" data-duration="1">Get Ready To Have Best Smart Payments in The World.</h2>
                    </div>
                </div>
                <div class="cta-btn-wrap">
                    <a href="{{ route('services')}}" class="bz-primary-btn red-btn">Get Our Service</a>
                </div>
            </div>
        </div>
    </section>
    <!-- ./ cta-section -->

    {{-- <section class="process-section-2 pt-120 pb-120">
        <div class="container-2">
            <div class="section-heading text-center red-content">
                <h4 class="sub-heading" data-text-animation="fade-in" data-duration="1.5"><span class="left-shape"></span>How We Works</h4>
                <h2 class="section-title mb-0" data-text-animation data-split="word" data-duration="1">We will help to grow business</h2>
            </div>
            <div class="row gy-lg-0 gy-4 fade-wrapper">
                <div class="col-lg-3 col-md-6">
                    <div class="process-item fade-top">
                        <div class="process-thumb img-reveal">
                            <div class="img-overlay overlay-2"></div>
                            <img src="assets/img/images/process-img-1.png" alt="process">
                            <span>step 1</span>
                        </div>
                        <div class="process-content">
                            <h3 class="title">Assessment</h3>
                            <p>Your company’s ability to deliver value to get you...</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="process-item fade-top">
                        <div class="process-thumb img-reveal">
                            <div class="img-overlay overlay-2"></div>
                            <img src="assets/img/images/process-img-2.png" alt="process">
                            <span>step 2</span>
                        </div>
                        <div class="process-content">
                            <h3 class="title">Strategy Build</h3>
                            <p>Your company’s ability to deliver value to get you...</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="process-item fade-top">
                        <div class="process-thumb img-reveal">
                            <div class="img-overlay overlay-2"></div>
                            <img src="assets/img/images/process-img-3.png" alt="process">
                            <span>step 3</span>
                        </div>
                        <div class="process-content">
                            <h3 class="title">Implementation</h3>
                            <p>Your company’s ability to deliver value to get you...</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="process-item fade-top">
                        <div class="process-thumb img-reveal">
                            <div class="img-overlay overlay-2"></div>
                            <img src="assets/img/images/process-img-4.png" alt="process">
                            <span>step 4</span>
                        </div>
                        <div class="process-content">
                            <h3 class="title">Monitoring</h3>
                            <p>Your company’s ability to deliver value to get you...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> --}}
    <!-- ./ process-section -->

    <div class="sponsor-section pb-120 mt-5">
        <div class="container">
            <h3 class="sponsor-text-wrap">
                <span></span>
                <span class="sponsor-text">Multiple Payment Choices</span>
                <span></span>
            </h3>
            <div class="sponsor-carousel swiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="sponsor-item text-center">
                            <a href="#"><img src="{{asset('assets/img/payment/payment-2.png')}}" alt="sponsor"></a>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="sponsor-item text-center">
                            <a href="#"><img src="{{asset('assets/img/payment/payment-4.png')}}" alt="sponsor"></a>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="sponsor-item text-center">
                            <a href="#"><img src="{{asset('assets/img/payment/payment-6.png')}}" alt="sponsor"></a>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="sponsor-item text-center">
                            <a href="#"><img src="{{asset('assets/img/payment/payment-7.png')}}" alt="sponsor"></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection