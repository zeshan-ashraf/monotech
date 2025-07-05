<!DOCTYPE html>
<html class="no-js" lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">

        <!-- Site Title -->
        <title>Monotech @yield('title') </title>

        <!-- Place favicon.ico in the root directory -->
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/img/favicon-2.png')}}">

        <!-- CSS here -->
        <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css')}}">
        <link rel="stylesheet" href="{{ asset('assets/css/fontawesome.min.css')}}">
        <link rel="stylesheet" href="{{ asset('assets/css/venobox.min.css')}}">
        <link rel="stylesheet" href="{{ asset('assets/css/animate.min.css')}}">
        <link rel="stylesheet" href="{{ asset('assets/css/keyframe-animation.css')}}">
        <link rel="stylesheet" href="{{ asset('assets/css/daterangepicker.css')}}">
        <link rel="stylesheet" href="{{ asset('assets/css/magnific-popup.css')}}">
        <link rel="stylesheet" href="{{ asset('assets/css/odometer.min.css')}}">
        <link rel="stylesheet" href="{{ asset('assets/css/nice-select.css')}}">
        <link rel="stylesheet" href="{{ asset('assets/css/swiper.min.css')}}">
        <link rel="stylesheet" href="{{ asset('assets/css/slick.min.css')}}">
        <link rel="stylesheet" href="{{ asset('assets/css/main.css')}}">
        @yield('css')
    </head>

    <body>
        
        <div id="preloader">
            <div class="loading" data-loading-text="Monotech"></div>
        </div>

        @include('frontend.components.header')

        @yield('content')

        @include('frontend.components.footer')
        <!-- ./ footer-section -->

        <div id="scroll-percentage" style="--bz-color-theme-primary: #EC281C"><span id="scroll-percentage-value"></span></div>
        <!--scrollup-->

        <!-- JS here -->
        <script src="{{asset('assets/js/vendor/jquary-3.6.0.min.js')}}"></script>
        <script src="{{asset('assets/js/vendor/bootstrap-bundle.js')}}"></script>
        <script src="{{asset('assets/js/vendor/imagesloaded-pkgd.js')}}"></script>
        <script src="{{asset('assets/js/vendor/waypoints.min.js')}}"></script>
        <script src="{{asset('assets/js/vendor/venobox.min.js')}}"></script>
        <script src="{{asset('assets/js/vendor/odometer.min.js')}}"></script>
        <script src="{{asset('assets/js/vendor/meanmenu.js')}}"></script>
        <script src="{{asset('assets/js/vendor/smooth-scroll.js')}}"></script>
        <script src="{{asset('assets/js/vendor/jquery.isotope.js')}}"></script>
        <script src="{{asset('assets/js/vendor/magnific-popup.min.js')}}"></script>
        <script src="{{asset('assets/js/vendor/wow.min.js')}}"></script>
        <script src="{{asset('assets/js/vendor/swiper.min.js')}}"></script>
        <script src="{{asset('assets/js/vendor/slick.min.js')}}"></script>
        <script src="{{asset('assets/js/vendor/moment.min.js')}}"></script>
        <script src="{{asset('assets/js/vendor/daterangepicker.min.js')}}"></script>
        <script src="{{asset('assets/js/vendor/split-type.min.js')}}"></script>
        <script src="{{asset('assets/js/vendor/gsap.min.js')}}"></script>
        <script src="{{asset('assets/js/vendor/scroll-trigger.min.js')}}"></script>
        <script src="{{asset('assets/js/vendor/nice-select.js')}}"></script>
        <script src="{{asset('assets/js/contact.js')}}"></script>
        <script src="{{asset('assets/js/main.js')}}"></script>

        @yield('js')
    </body>
</html>