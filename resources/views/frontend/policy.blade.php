@extends('layouts.main')
@section('title', ' - Privacy Policy')
@push('css')
    <style>

    </style>
    @section('content')

        <section class="page-header">
            <div class="bg-img" data-background="{{asset('assets/img/bg-img/page-header-bg.jpg')}}"></div>
            <div class="overlay"></div>
            <div class="shapes">
                <div class="shape shape-1"><img src="{{ asset('assets/img/shapes/pager-header-shape-1.png') }}" alt="shape">
                </div>
                <div class="shape shape-2"><img src="{{ asset('assets/img/shapes/pager-header-shape-2.png') }}" alt="shape">
                </div>
            </div>
            <div class="container">
                <div class="page-header-content">
                    <h1 class="title">Privacy Policy</h1>
                    <h4 class="sub-title">
                        <span class="home">
                            <a href="{{ route('home') }}">
                                <span>Home</span>
                            </a>
                        </span>
                        <span class="icon">/</span>
                        <span class="inner">
                            <span>Privacy Policy</span>
                        </span>
                    </h4>
                </div>
            </div>
        </section>

        <div class="tp-page-area page-padding">
            <div class="container">
                <div class="row">
                    <div class="col-md-12 mt-5">
                        <p>Welcome to <strong>Monotech</strong> <a href="https://monotech.pk/" target="_blank">(https://monotech.pk/)</a>. Your privacy is of utmost importance to us, and we are committed to protecting the personal information you share with us. This Privacy Policy outlines how we collect, use, and safeguard your information.</p>
                        
                        <h4>1. Information We Collect</h4>
                        <ul>
                            <li><strong>Personal Information:</strong> Name, email address, phone number, and other contact details. Billing and financial information for transactions.</li>
                            <li><strong>Non-Personal Information:</strong> Browser type, device information, and IP address. Website usage data, such as pages visited and time spent on our site.</li>
                            <li><strong>Payment Information:</strong> We collect payment details to process transactions securely.</li>
                        </ul>
                        
                        <h4>2. How We Use Your Information</h4>
                        <p>We use your information to provide and improve our services, process payments, communicate with you, and comply with legal requirements.</p>
                        
                        <h4>3. Information Sharing and Disclosure</h4>
                        <p>We do not sell or rent your personal information. However, we may share information with third parties for the following reasons:</p>
                        <ul>
                            <li>To process payments via trusted payment gateways.</li>
                            <li>To comply with legal obligations.</li>
                            <li>To protect the rights, property, or safety of Monotech, its users, or others.</li>
                        </ul>
                        
                        <h4>4. Data Security</h4>
                        <p>We use appropriate security measures to protect your personal information. However, no online system can guarantee absolute security.</p>
                        
                        <h4>5. Your Rights</h4>
                        <p>You have the right to access, update, or delete your personal information. Please contact us at <a href="mailto:info@monotech.pk">info@monotech.pk</a> for assistance.</p>
                        
                        <h4>6. Changes to This Policy</h4>
                        <p>We may update this Privacy Policy from time to time. Any changes will be reflected on this page with an updated 10/11/2024</p>
                        
                        <h4>7. Contact Us</h4>
                        <p>If you have any questions or concerns about this Privacy Policy, please contact us at <a href="mailto:info@monotech.pk">info@monotech.pk</a>.</p>
                    </div>
                </div>
            </div>
        </div>

    @endsection
