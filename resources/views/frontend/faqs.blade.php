@extends('layouts.main')
@section('title',' - Faqs)
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
                <h1 class="title">Frequently Asked Questions</h1>
                <h4 class="sub-title">
                    <span class="home">
                        <a href="{{ route('home')}}">
                            <span>Home</span>
                        </a>
                    </span>
                    <span class="icon">/</span>
                    <span class="inner">
                        <span>Frequently Asked Questions</span>
                    </span>
                </h4>
            </div>
        </div>
    </section>

    <section class="faq-section pt-120 pb-120">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
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
                                            Mono Tech is a secure and reliable payment gateway in Pakistan that allows merchants to accept payments through JazzCash, Easypaisa, and Credit/Debit Cards directly on their websites or mobile apps.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item fade-top">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                            Which payment methods does Mono Tech support?
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            We currently support the following payment methods:
                                            <ol>
                                                <li>
                                                    JazzCash
                                                </li>
                                                <li>
                                                    Easypaisa
                                                </li>
                                                <li>
                                                    Visa and MasterCard (Debit and Credit Cards)
                                                </li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item fade-top">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                            How can I integrate Mono Tech with my website or app?
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            You can integrate Mono Tech via our RESTful APIs and SDKs available for major platforms. We provide complete developer documentation and integration support to help you get started quickly.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item fade-top">
                                    <h2 class="accordion-header" id="headingFour">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                            Is Mono Tech secure?
                                        </button>
                                    </h2>
                                    <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            Yes, we use industry-standard encryption and are compliant with PCI DSS standards to ensure secure transaction processing and data protection.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item fade-top">
                                    <h2 class="accordion-header" id="headingFive">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                            How long does it take to settle payments to merchants?
                                        </button>
                                    </h2>
                                    <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            Payment settlements are typically processed within 1-2 business days, depending on the payment method and your merchant agreement.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item fade-top">
                                    <h2 class="accordion-header" id="headingSix">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                            Is there a fee to use Mono Tech's services?
                                        </button>
                                    </h2>
                                    <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            Yes, we charge a small transaction fee for each successful payment. Our pricing is competitive and varies based on payment method and volume. Please contact us for detailed pricing.

                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item fade-top">
                                    <h2 class="accordion-header" id="headingSeven">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                                            Can I view transaction history and reports?

                                        </button>
                                    </h2>
                                    <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            Absolutely. Our merchant dashboard provides real-time access to all your transactions, settlements, and detailed reporting tools.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item fade-top">
                                    <h2 class="accordion-header" id="headingEight">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                                            What should I do if a customer reports a failed or delayed transaction?
                                        </button>
                                    </h2>
                                    <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            If a customer experiences a failed or delayed transaction, they can reach out to your support team, and you can escalate the issue to our technical support team with the transaction reference number. We investigate and resolve such issues promptly.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item fade-top">
                                    <h2 class="accordion-header" id="headingNine">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
                                            Do I need a bank account to use Mono Tech?
                                        </button>
                                    </h2>
                                    <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            Yes, a valid Pakistani bank account is required to receive settlements. We transfer your earnings directly to your registered bank account.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item mb-0 fade-top">
                                    <h2 class="accordion-header" id="headingTen">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTen" aria-expanded="false" aria-controls="collapseTen">
                                            How do I sign up as a merchant with Mono Tech?
                                        </button>
                                    </h2>
                                    <div id="collapseTen" class="accordion-collapse collapse" aria-labelledby="headingTen" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            You can sign up by filling out our merchant registration form on our website. After verifying your business details, our onboarding team will guide you through the next steps.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="faq-img img-reveal">
                        <div class="img-overlay"></div>
                        <img src="{{asset('assets/img/images/faq-img.png')}}" alt="faq">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ./ request-section -->
@endsection