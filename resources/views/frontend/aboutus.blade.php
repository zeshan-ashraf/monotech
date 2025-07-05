@extends('layouts.main')
@section('title',' - About us')
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
                <h1 class="title">About Us</h1>
                <h4 class="sub-title">
                    <span class="home">
                        <a href="{{ route('home')}}">
                            <span>Home</span>
                        </a>
                    </span>
                    <span class="icon">/</span>
                    <span class="inner">
                        <span>About Us</span>
                    </span>
                </h4>
            </div>
        </div>
    </section>
    <!-- ./ page-header -->

    <section class="about-section-4 pt-120 pb-120"> 
        <div class="shapes">
            <div class="shape"><img src="{{asset('assets/img/shapes/about-shape-3.png')}}" alt="shape"></div>
        </div>
        <div class="container">
            <div class="row align-items-center fade-wrapper">
                <div class="col-lg-6 col-md-12">
                    <div class="about-content-4">
                        <div class="section-heading mb-30">
                            <h4 class="sub-heading" data-text-animation="fade-in" data-duration="1.5"><span class="left-shape"></span>About Our Company</h4>
                        </div>
                        <p class="fade-top">Our company Monotech, which specializes in financial technology, is enthusiastic about payments and has embarked on a mission to transform the daily financial practices of Pakistanis. We are glad to say that, despite the many hard-won victories we have made in support of the nation's transition to financial inclusion and digitization, our path is far from over.</p>
                        <div class="about-contact-items fade-top">
                            <div class="about-author">
                                <img src="{{asset('assets/img/images/about-author.png')}}" alt="img">
                                <h4 class="name"><span>Chairman</span>Muhammad Ashraf</h4>
                            </div>
                        </div>
                        <div class="about-btn-wrap fade-top">
                            <a href="#" class="bz-primary-btn about-btn">Read Details <i class="fa-regular fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12">
                    <div class="about-img-wrap-4 fade-top">
                        <div class="about-img img-1">
                            <img src="{{asset('assets/img/images/about-img-6.png')}}" alt="about">
                        </div>
                        <div class="about-img img-2">
                            <img src="{{asset('assets/img/images/about-img-7.png')}}" alt="about">
                        </div>
                        <div class="about-counter">
                            <div class="shape"><img src="{{asset('assets/img/shapes/about-counter-shape.png')}}" alt="shape"></div>
                            <h3 class="title"><span class="odometer" data-count="1589">0</span></h3>
                            <p>Successful Query</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ./ about-section -->

    {{-- <section class="about-cta fade-wrapper">
        <div class="container">
            <div class="about-cta-wrap pt-120 pb-120 text-center fade-top">
                <div class="bg-item">
                    <div class="bg-img" data-background="assets/img/bg-img/about-cta-bg.jpg"></div>
                    <div class="overlay"></div>
                    <div class="overlay-2"></div>
                    <div class="shape"><img src="{{asset('assets/img/shapes/about-cta-shape.png')}}" alt="shape"></div>
                </div>
                <div class="section-heading white-content mb-40">
                    <h4 class="sub-heading" data-text-animation="fade-in" data-duration="1.5"><span class="left-shape"></span>About Our Company</h4>
                    <h2 class="section-title mb-0" data-text-animation data-split="word" data-duration="1">Revolutionize your digital presence with our strategic services</h2>
                </div>
                <a href="about.html" class="bz-primary-btn">Read Details <i class="fa-regular fa-arrow-right"></i></a>
            </div>
        </div>
    </section> --}}
    <!-- ./ about-cta -->

    <section class="feature-section pt-120 pb-120">
        <div class="container">
            <div class="section-heading">
                <h4 class="sub-heading" data-text-animation="fade-in" data-duration="1.5"><span class="left-shape"></span>Our Features</h4>
                <h2 class="section-title mb-0" data-text-animation data-split="word" data-duration="1">Unique Features We Provide</h2>
            </div>
            <div class="row gy-lg-0 gy-4 justify-content-center fade-wrapper">
                <div class="col-lg-4 col-md-6 fade-top">
                    <div class="feature-item">
                        <div class="bg-img"><img src="{{asset('assets/img/images/feature-img-1.png')}}" alt="feature"></div>
                        <div class="icon"><img src="{{asset('assets/img/icon/feature-1.png')}}" alt="feature"></div>
                        <div class="feature-content">
                            <h3 class="title">Quality Assurance</h3>
                            <p>Cardinate premier technology without sustainable leadership work...</p>
                            <a href="service-details.html" class="read-more">Read Details <i class="fa-regular fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 fade-top">
                    <div class="feature-item">
                        <div class="bg-img"><img src="{{asset('assets/img/images/feature-img-2.png')}}" alt="feature"></div>
                        <div class="icon"><img src="{{asset('assets/img/icon/feature-2.png')}}" alt="feature"></div>
                        <div class="feature-content">
                            <h3 class="title">Clients Satisfaction</h3>
                            <p>Cardinate premier technology without sustainable leadership work...</p>
                            <a href="service-details.html" class="read-more">Read Details <i class="fa-regular fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 fade-top">
                    <div class="feature-item">
                        <div class="bg-img"><img src="{{asset('assets/img/images/feature-img-1.png')}}" alt="feature"></div>
                        <div class="icon"><img src="{{asset('assets/img/icon/process-2.png')}}" alt="feature"></div>
                        <div class="feature-content">
                            <h3 class="title">Planning & Strategy</h3>
                            <p>Cardinate premier technology without sustainable leadership work...</p>
                            <a href="service-details.html" class="read-more">Read Details <i class="fa-regular fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ./ feature-section -->

    {{-- <section class="testimonial-section pt-120 pb-120" data-background="assets/img/bg-img/testi-bg.png')}}">
        <div class="container">
            <div class="section-heading text-center">
                <h4 class="sub-heading" data-text-animation="fade-in" data-duration="1.5"><span class="left-shape"></span>Client’s Feedbacks</h4>
                <h2 class="section-title mb-0" data-text-animation data-split="word" data-duration="1">What Our Customers Have to Say</h2>
            </div>
            <div class="testi-carousel swiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="testi-item">
                            <div class="testi-top">
                                <div class="testi-author">
                                    <img src="{{asset('assets/img/images/testi-author-1.png')}}" alt="img">
                                    <h4 class="name">Henry Oliver <span>IT Customer</span></h4>
                                </div>
                                <div class="quote"><img src="{{asset('assets/img/icon/quote.png')}}" alt="quote"></div>
                            </div>
                            <p>“Quickly formulate high yield web services before functional process improvements enable premier with e-business customer service.”</p>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="testi-item">
                            <div class="testi-top">
                                <div class="testi-author">
                                    <img src="{{asset('assets/img/images/testi-author-2.png')}}" alt="img">
                                    <h4 class="name">Thomas William <span>IT Customer</span></h4>
                                </div>
                                <div class="quote"><img src="{{asset('assets/img/icon/quote.png')}}" alt="quote"></div>
                            </div>
                            <p>“Quickly formulate high yield web services before functional process improvements enable premier with e-business customer service.”</p>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section> 
    <!-- ./ testimonial-section -->

    <section class="faq-section pt-120 pb-120">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="faq-content">
                        <div class="section-heading mb-30">
                            <h4 class="sub-heading" data-text-animation="fade-in" data-duration="1.5"><span class="left-shape"></span>Frequently Asked Questions</h4>
                            <h2 class="section-title mb-0" data-text-animation data-split="word" data-duration="1">Why Should You Love To Work With Us?</h2>
                        </div>
                        <div class="faq-accordion fade-wrapper">
                            <div class="accordion" id="accordionExample">
                                <div class="accordion-item fade-top">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                            What is Monotech?
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            Monotech is a payment gateway provider in Pakistan, facilitating online transactions for businesses and individuals.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item fade-top">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            What services does Monotech offer?
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            Monotech offers payment gateway solutions, online payment processing, and merchant services.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item fade-top">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                            How secure is Monotech?
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            Monotech uses PCI-DSS compliant security measures, encryption, and 2-factor authentication to ensure secure transactions.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item fade-top">
                                    <h2 class="accordion-header" id="headingFour">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                            How do I become a Monotech merchant?
                                        </button>
                                    </h2>
                                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            Register on our website, provide required documents, and wait for verification and approval.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item mb-0 fade-top">
                                    <h2 class="accordion-header" id="headingFive">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                            What documents are required for merchant registration?
                                        </button>
                                    </h2>
                                    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            CNIC, business registration documents, and proof of address.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="faq-img img-reveal">
                        <div class="img-overlay"></div>
                        <img src="{{asset('assets/img/images/faq-img.png')}}" alt="faq">
                    </div>
                </div>
            </div>
        </div>
    </section>--}}
    <!-- ./ request-section -->
@endsection