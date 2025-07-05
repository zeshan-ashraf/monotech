@extends('layouts.main')
@section('title', ' - Term and Condition')
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
                    <h1 class="title">Term and Condition</h1>
                    <h4 class="sub-title">
                        <span class="home">
                            <a href="{{ route('home') }}">
                                <span>Home</span>
                            </a>
                        </span>
                        <span class="icon">/</span>
                        <span class="inner">
                            <span>Term and Condition</span>
                        </span>
                    </h4>
                </div>
            </div>
        </section>

        <div class="tp-page-area page-padding">
            <div class="container">
                <div class="row">
                    <div class="col-md-12 mt-5">
                        <h3>Introduction</h3>
                        <p>Welcome to Monotech. By accessing or using our website and services, you agree to comply with these Terms and Conditions. Please read them carefully. If you do not agree to any part of these terms, you may not use our services.</p>
                        
                        <h4>1. Services Overview</h4>
                        <p>Monotech provides a comprehensive payment platform allowing businesses to manage cash flows, present digital invoices, and receive payments through various methods, including digital wallets (JazzCash, Easypaisa), bank transfers, and debit/credit cards.</p>
                        
                        <h4>2. User Responsibilities</h4>
                        <ul>
                            <li>You must provide accurate, complete, and up-to-date information during registration and while using our services.</li>
                            <li>You are responsible for maintaining the confidentiality of your account and password.</li>
                            <li>You agree to use our platform only for lawful purposes and in accordance with applicable laws and regulations.</li>
                        </ul>
                        
                        <h4>3. Payment Terms</h4>
                        <ul>
                            <li>All transactions processed through Monotech are subject to applicable fees, which will be clearly communicated at the time of service usage.</li>
                            <li>We do not guarantee uninterrupted or error-free operation of payment gateways as they are managed by third-party providers.</li>
                            <li>You agree to promptly resolve any disputes with your clients or customers regarding transactions facilitated through Monotech.</li>
                        </ul>
      
                        <h4>4. Refund Policy</h4>
                        <p>Our refund policy is outlined <a href="{{ url('/refund-policy') }}">here</a>. By using our platform, you agree to the conditions stated in the refund policy.</p>
                        
                        <h4>5. Intellectual Property</h4>
                        <ul>
                            <li>All content, trademarks, logos, and intellectual property on this website are owned by Monotech or licensed to us.</li>
                            <li>You may not reproduce, distribute, or modify any content without prior written consent.</li>
                        </ul>
                        
                        <h4>6. Limitation of Liability</h4>
                        <p>Monotech will not be held liable for any direct, indirect, incidental, or consequential damages resulting from:</p>
                        <ul>
                            <li>Your use or inability to use the platform.</li>
                            <li>Unauthorized access to or alteration of your data.</li>
                            <li>Third-party services or external payment gateway failures.</li>
                        </ul>
                        
                        <h4>7. Termination</h4>
                        <p>We reserve the right to suspend or terminate your account and access to our services without prior notice if you violate these terms or engage in fraudulent or abusive behavior.</p>
                        
                        <h4>8. Governing Law</h4>
                        <p>These Terms and Conditions are governed by the laws of Pakistan. Any disputes arising out of these terms will be subject to the exclusive jurisdiction of the courts of Pakistan.</p>
                        
                        <h4>9. Changes to Terms</h4>
                        <p>Monotech reserves the right to modify or update these terms at any time without prior notice. We recommend reviewing this page periodically to stay informed about our terms.</p>
                        
                        <h4>10. Contact Us</h4>
                        <p>If you have any questions or concerns regarding these Terms and Conditions, please contact us at <a href="mailto:info@monotech.pk">info@monotech.pk</a>.</p>
                    </div>
                </div>
            </div>
        </div>

    @endsection
