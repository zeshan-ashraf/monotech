<header class="header header-3 sticky-active" style="--bz-color-theme-primary: #EC281C">
    <div class="overlay"></div>
    <div class="primary-header">
        <div class="container-2">
            <div class="primary-header-inner">
                <div class="inner-left">
                    <div class="header-logo">
                        <a href="{{ route('home')}}">
                            <h3><img src="{{ asset('assets/img/logo/logo-6.png')}}" alt="" style="margin-top: -5px;"> Monotech </h3>
                        </a>
                    </div>
                    <div class="header-menu-wrap">
                        <div class="mobile-menu-items">
                            <ul>
                                <li><a href="{{route('home')}}">Home</a></li>
                                <li><a href="{{route('services')}}">Our Services</a></li>
                                <li><a href="{{route('products')}}">Our Products</a></li>
                                <li><a href="{{route('aboutus')}}">About Us</a></li>
                                <li><a href="{{route('contactus')}}">Contact</a></li>
                                <li><a href="{{route('faqs')}}">Faq's</a></li>
                            </ul>
                        </div>
                    </div>
                    <!-- /.header-menu-wrap -->
                </div>
                <div class="header-right-wrap">
                    <div class="header-right">
                        <a href="{{route('login')}}" class="bz-primary-btn">Want to Login?</a>
                    </div>
                    <!-- /.header-right -->
                </div>
            </div>
            <!-- /.primary-header-inner -->
        </div>
    </div>
</header>