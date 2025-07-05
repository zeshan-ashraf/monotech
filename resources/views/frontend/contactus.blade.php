@extends('layouts.main')
@section('title',' - Contact us')
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
                <h1 class="title">Contact Us</h1>
                <h4 class="sub-title">
                    <span class="home">
                        <a href="{{ route('home')}}">
                            <span>Home</span>
                        </a>
                    </span>
                    <span class="icon">/</span>
                    <span class="inner">
                        <span>Contact Us</span>
                    </span>
                </h4>
            </div>
        </div>
    </section>
    <!-- ./ page-header -->

    <section class="contact-section pt-130 pb-130">
        <div class="container">
            <div class="row gy-lg-0 gy-5">
                <div class="col-lg-7">
                    <div class="blog-contact-form">
                        <h2 class="title mb-0">Get In Touch</h2>
                        <p class="mb-30 mt-10">Reach out with your questions, feedback or any assistance you require.</p>
                        <div class="request-form">
                            <form action="mail.php" method="post" id="ajax_contact" class="form-horizontal">
                                <div class="form-group row">
                                    <div class="col-md-6">
                                        <div class="form-item">
                                            <input type="text" id="fullname" name="fullname" class="form-control" placeholder="Your Name">
                                            <div class="icon"><i class="fa-regular fa-user"></i></div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-item">
                                            <input type="email" id="email" name="email" class="form-control" placeholder="Your Email">
                                            <div class="icon"><i class="fa-sharp fa-regular fa-envelope"></i></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-md-12">
                                        <div class="form-item">
                                            <div class="nice-select select-control form-control country" tabindex="0"><span class="current">Select Subject</span><ul class="list"><li data-value="" class="option selected focus">Select Subject</li><li data-value="vdt" class="option">Plan One</li><li data-value="can" class="option">Plan Two</li><li data-value="uk" class="option">Plan Three</li></ul></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-md-12">
                                        <div class="form-item message-item">
                                            <textarea id="message" name="message" cols="30" rows="5" class="form-control address" placeholder="Message"></textarea>
                                            <div class="icon"><i class="fa-light fa-messages"></i></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="submit-btn">
                                    <button id="submit" class="bz-primary-btn" type="submit">Submit Message</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 col-md-12">
                    <div class="contact-content">
                        <div class="contact-top">
                            <h3 class="title">Office Information</h3>
                            <p>Build strong, always-available communities using reliable and effective standards.</p>
                        </div>
                        <div class="contact-list">
                            <div class="list-item">
                                <div class="icon">
                                    <i class="fa-sharp fa-solid fa-phone"></i>
                                </div>
                                <div class="content">
                                    <h4 class="title">Phone Number & Email</h4>
                                    <span><a href="tel:+65485965789">(+92) 327 4920565</a></span>
                                    <span><a href="mailto:hello@bizan.com">info@monotech.com</a></span>
                                </div>
                            </div>
                            <div class="list-item">
                                <div class="icon">
                                    <i class="fa-sharp fa-solid fa-location-dot"></i>
                                </div>
                                <div class="content">
                                    <h4 class="title">Our Office Address</h4>
                                    <p>Shop#5 Central Plaza Barkat Market Garden Town, Lahore Pakistan.</p>
                                </div>
                            </div>
                            <div class="list-item">
                                <div class="icon">
                                    <i class="fa-sharp fa-solid fa-clock"></i>
                                </div>
                                <div class="content">
                                    <h4 class="title">Official Work Time</h4>
                                    <span>Monday - Friday: 09:00 - 20:00</span>
                                    <span>Sunday & Saturday: 10:30 - 22:00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ./ contact-section -->
@endsection