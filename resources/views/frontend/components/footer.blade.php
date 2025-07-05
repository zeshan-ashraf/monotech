<footer class="footer-section footer-2 overflow-hidden" style="--bz-color-theme-primary: #EC281C">
    <div class="shapes">
        <div class="shape shape-1"><img src="assets/img/shapes/footer-shape-1.png" alt="footer"></div>
        <div class="shape shape-2"><img src="assets/img/shapes/footer-shape-2.png" alt="footer"></div>
    </div>
    <div class="container">
        <div class="row footer-wrap">
            <div class="col-lg-3 col-md-6">
                <div class="footer-widget">
                    <div class="widget-header header-2">
                        <div class="footer-logo">
                            <h3 class="text-white"><img src="{{ asset('assets/img/logo/logo-6.png')}}" alt="" style="margin-top: -10px;"> Monotech </h3>
                        </div>
                    </div>
                    <p>Shop#5 Central Plaza Barkat Market Garden Town, Lahore Pakistan.</p>
                    <ul class="footer-social">
                        <li><a href="#"><i class="fa-brands fa-facebook-f"></i></a></li>
                        <li><a href="#"><i class="fa-brands fa-behance"></i></a></li>
                        <li><a href="#"><i class="fa-brands fa-linkedin"></i></a></li>
                        <li><a href="#"><i class="fa-brands fa-pinterest-p"></i></a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="footer-widget widget-space">
                    <div class="widget-header header-2">
                        <h3 class="widget-title">Quick Links</h3>
                    </div>
                    <ul class="footer-list">
                        <li><a href="{{ route('home')}}">Home</a></li>
                        <li><a href="{{ route('aboutus')}}">About us</a></li>
                        <li><a href="{{ route('contactus')}}">Contact Us</a></li>
                        <li><a href="{{ route('services')}}">Our Services</a></li>
                        <li><a href="{{route('faqs')}}">Faq's</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="footer-widget">
                    <div class="widget-header header-2">
                        <h3 class="widget-title">Information</h3>
                    </div>
                    <ul class="footer-list">
                        <li><a href="{{ route('products')}}">Our Products</a></li>
                        <li><a href="{{ route('policy')}}">Privacy Policy</a></li>
                        <li><a href="{{ route('refund')}}">Refund Policy</a></li>
                        <li><a href="{{ route('terms')}}">Term & Condition</a></li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="footer-widget widget-space">
                    <div class="widget-header header-2">
                        <h3 class="widget-title">Our Newsletter</h3>
                    </div>
                    <p>Sign up to Private's weekly newsletter to get the latest updates.</p>
                    <div class="footer-form form-2 mb-20">
                        <form action="#" class="rr-subscribe-form">
                            <input class="form-control" type="email" name="email" placeholder="Email address">
                            <input type="hidden" name="action" value="mailchimpsubscribe">
                            <button class="submit">Subscribe</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="copyright-area area-2">
        <div class="container">
            <div class="row copyright-content">
                <div class="col-md-6">
                    <p>© 2025 Monotech. All Rights Reserved.</p>
                </div>
            </div>
        </div>
    </div>
</footer>