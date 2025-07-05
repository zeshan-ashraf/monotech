@extends('layouts.main')
@section('title', ' - Refund & Returns Policy')
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
                    <h1 class="title">Refund & Returns Policy</h1>
                    <h4 class="sub-title">
                        <span class="home">
                            <a href="{{ route('home') }}">
                                <span>Home</span>
                            </a>
                        </span>
                        <span class="icon">/</span>
                        <span class="inner">
                            <span>Refund & Returns Policy</span>
                        </span>
                    </h4>
                </div>
            </div>
        </section>

        <div class="tp-page-area page-padding">
            <div class="container">
                <div class="row">
                    <div class="col-md-12 mt-5">
                        <h3>Overview</h3>
                        <p>Monotech is committed to providing reliable and efficient payment solutions. However, if you encounter any issues with our services, we offer a refund policy under certain conditions, as outlined below.</p>
                        
                        <h4>Refund Eligibility</h4>
                        <p>Our refund policy applies only to payments made for services provided by Monotech. If you believe a transaction was made in error or without your authorization, please contact us at <a href="mailto:info@monotech.pk">info@monotech.pk</a> within 30 days of the transaction date to request a review.</p>
                        <p>Refunds are processed under the following conditions:</p>
                        <ul>
                            <li>A duplicate payment was made for the same service.</li>
                            <li>The service was not delivered as described or was unavailable due to technical issues.</li>
                            <li>An unauthorized transaction occurred (subject to verification).</li>
                        </ul>
      
                        <h4>Non-Refundable Items</h4>
                        <p>We cannot process refunds for the following:</p>
                        <ul>
                            <li>Fees for successfully completed transactions.</li>
                            <li>Payments made through third-party payment gateways unless the issue is directly related to Monotech’s services.</li>
                            <li>Any charges associated with optional features or upgrades.</li>
                        </ul>
      
                        <h4>Refund Process</h4>
                        <p>Once your refund request is approved, the refund will be processed, and a credit will automatically be applied to your original payment method within 10 business days. We will notify you via email once the refund has been initiated.</p>
      
                        <h4>Late or Missing Refunds</h4>
                        <p>If you haven’t received a refund after the specified timeframe, please:</p>
                        <ol>
                            <li>Check your bank account or payment method again.</li>
                            <li>Contact your payment provider or financial institution, as there may be a delay in processing the refund.</li>
                        </ol>
                        <p>If the issue persists, contact us at <a href="mailto:info@monotech.pk">info@monotech.pk</a>.</p>
      
                        <h4>Disputed Transactions</h4>
                        <p>For disputes regarding payments made through our platform, we encourage you to contact us directly before initiating a chargeback with your bank or payment provider. Resolving disputes through Monotech is often faster and ensures a smoother process.</p>
      
                        <h4>Need Help?</h4>
                        <p>If you have questions or need assistance regarding refunds, please contact us at <a href="mailto:info@monotech.pk">info@monotech.pk</a>.</p>
                    </div>
                </div>
            </div>
        </div>

    @endsection
