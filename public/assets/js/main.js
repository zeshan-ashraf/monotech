/* ============ Main JS ============ */

(function ($) {
    "use strict";
    
        //Toggle Js
		$('.rr-checkout-login-form-reveal-btn').on('click', function () {
			$('#rrReturnCustomerLoginForm').slideToggle(400);
		});

        $('.rr-checkout-coupon-form-reveal-btn').on('click', function () {
			$('#rrCheckoutCouponForm').slideToggle(400);
		});


    /*======================================
        Preloader activation
    ========================================*/
    $(window).on("load", function (event) {
        $("#preloader").delay(1000).fadeOut(500);
        // Text Animation
        setTimeout(() => {
        var hasAnim = $(".anim-text");
            hasAnim.each(function () {
                var $this = $(this);
                var splitto = new SplitType($this, {
                types: "lines, chars",
                className: "char",
                });
                var chars = $this.find(".char");
                gsap.fromTo(
                chars,
                { y: "100%" },
                {
                    y: "0%",
                    duration: 0.9,
                    stagger: 0.03,
                    ease: "power2.out",
                }
                );
            });
        }, 1000);
    });

    $(".preloader-close").on("click", function () {
        $("#preloader").delay(0).fadeOut(500);
    });

    $(document).ready(function () {


        if(navigator.userAgent.toLowerCase().indexOf('firefox') > -1){
            $('body').addClass('firefox');
        }
        
        var header = $(".header"),
            stickyHeader = $(".primary-header");

        function menuSticky(w) {
            if (w.matches) {
                
                $(window).on("scroll", function () {
                    var scroll = $(window).scrollTop();
                    if (scroll >= 110) {
                        stickyHeader.addClass("fixed");
                    } else {
                        stickyHeader.removeClass("fixed");
                    }
                });
                if ($(".header").length > 0) {    
                    var  headerHeight = document.querySelector(".header"),
                        setHeaderHeight = headerHeight.offsetHeight;	
                    header.each(function () {
                        $(this).css({
                            'height' : setHeaderHeight + 'px'
                        });
                    });
                }
            }
        }

        var minWidth = window.matchMedia("(min-width: 992px)");
        if (header.hasClass("sticky-active")) {
            menuSticky(minWidth);
        }

        //Mobile Menu Js
        $(".mobile-menu-items").meanmenu({
            meanMenuContainer: ".side-menu-wrap",
            meanScreenWidth: "992",
            meanMenuCloseSize: "30px",
            meanRemoveAttrs: true,
            meanExpand: ['<i class="fa-solid fa-caret-down"></i>'],
        });

        // Mobile Sidemenu
        $(".mobile-side-menu-toggle").on("click", function () {
            $(".mobile-side-menu, .mobile-side-menu-overlay").toggleClass("is-open");
        });

        $(".mobile-side-menu-close, .mobile-side-menu-overlay").on("click", function () {
            $(".mobile-side-menu, .mobile-side-menu-overlay").removeClass("is-open");
        });

        // Popup Search Box
        $(function () {
            $("#popup-search-box").removeClass("toggled");

            $(".dl-search-icon").on("click", function (e) {
                e.stopPropagation();
                $("#popup-search-box").toggleClass("toggled");
                $("#popup-search").focus();
            });

            $("#popup-search-box input").on("click", function (e) {
                e.stopPropagation();
            });

            $("#popup-search-box, body").on("click", function () {
                $("#popup-search-box").removeClass("toggled");
            });
        });

        // Popup Sidebox
        function sideBox() {
            $("body").removeClass("open-sidebar");
            $(document).on("click", ".sidebar-trigger", function (e) {
                e.preventDefault();
                $("body").toggleClass("open-sidebar");
            });
            $(document).on("click", ".sidebar-trigger.close, #sidebar-overlay", function (e) {
                e.preventDefault();
                $("body.open-sidebar").removeClass("open-sidebar");
            });
        }

        sideBox();

        // Venobox Active
        $('.venobox').venobox({
            bgcolor: 'transparent',
            spinner: 'spinner-pulse',
            numeration: true,
            infinigall: true
        });
        
        // Data Background
        $("[data-background").each(function () {
            $(this).css("background-image", "url( " + $(this).attr("data-background") + "  )");
        });

        // Custom Cursor
        $("body").append('<div class="mt-cursor"></div>');
        var cursor = $(".mt-cursor"),
            linksCursor = $("a, .swiper-nav, button, .cursor-effect"),
            crossCursor = $(".cross-cursor");

        $(window).on("mousemove", function (e) {
            cursor.css({
                transform: "translate(" + (e.clientX - 15) + "px," + (e.clientY - 15) + "px)",
                visibility: "inherit",
            });
        });

        /* Odometer */
        $(".odometer").waypoint(
            function () {
                var odo = $(".odometer");
                odo.each(function () {
                    var countNumber = $(this).attr("data-count");
                    $(this).html(countNumber);
                });
            },
            {
                offset: "80%",
                triggerOnce: true,
            }
        );

        // Wow JS Active
        new WOW().init();

        // Nice Select Js
        $("select").niceSelect();

        // Isotop
        $(".filter-items").imagesLoaded(function () {
            // Add isotope click function
            $(".project-filter li").on("click", function () {
                $(".project-filter li").removeClass("active");
                $(this).addClass("active");

                var selector = $(this).attr("data-filter");
                $(".filter-items").isotope({
                    filter: selector,
                    animationOptions: {
                        duration: 750,
                        easing: "linear",
                        queue: false,
                    },
                });
                return false;
            });

            $(".filter-items").isotope({
                itemSelector: ".single-item",
                layoutMode: "fitRows",
                fitRows: {
                    gutter: 0,
                },
            });
        });
        
        // Service Carousel
        var swiperProject = new Swiper(".project-carousel", {
            slidesPerView: 2,
            spaceBetween: 30,
            slidesPerGroup: 1,
            loop: true,
            autoplay: true,
            grabcursor: true,
            speed: 600,
            grabcursor: true,
            navigation: {
                nextEl: ".project-section .swiper-prev",
                prevEl: ".project-section .swiper-next",
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            breakpoints: {
                320: {
                    slidesPerView: 1,
                    slidesPerGroup: 1,
                    spaceBetween: 30,
                },
                767: {
                    slidesPerView: 2,
                    slidesPerGroup: 1,
                    spaceBetween: 30,
                },
                1024: {
                    slidesPerView: 2,
                    slidesPerGroup: 1,
                },
            },
        });

        // Sponsor Carousel
        var swiperSponsor = new Swiper(".sponsor-carousel", {
            slidesPerView: 45,
            spaceBetween: 50,
            slidesPerGroup: 1,
            loop: true,
            autoplay: true,
            grabCursor: true,
            speed: 400,
            breakpoints: {
                320: {
                    slidesPerView: 2,
                    slidesPerGroup: 1,
                    spaceBetween: 25,
                },
                767: {
                    slidesPerView: 3,
                    slidesPerGroup: 1,
                    spaceBetween: 30,
                },
                1024: {
                    slidesPerView: 5,
                    slidesPerGroup: 1,
                },
            },
        });

        // Testi Carousel
        var swiperTesti = new Swiper(".testi-carousel", {
            slidesPerView: 2,
            spaceBetween: 30,
            slidesPerGroup: 1,
            loop: true,
            autoplay: true,
            grabcursor: true,
            speed: 600,
            grabcursor: true,
            navigation: {
                nextEl: ".testi-section .swiper-prev",
                prevEl: ".testi-section .swiper-next",
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            breakpoints: {
                320: {
                    slidesPerView: 1,
                    slidesPerGroup: 1,
                    spaceBetween: 30,
                },
                767: {
                    slidesPerView: 1,
                    slidesPerGroup: 1,
                    spaceBetween: 30,
                },
                1024: {
                    slidesPerView: 2,
                    slidesPerGroup: 1,
                },
            },
        });

        // Testi Carousel
        var swiperTesti = new Swiper(".testi-carousel-2", {
            slidesPerView: 2,
            spaceBetween: 30,
            slidesPerGroup: 1,
            loop: true,
            autoplay: true,
            grabcursor: true,
            speed: 600,
            grabcursor: true,
            navigation: {
                nextEl: ".testi-section .swiper-prev",
                prevEl: ".testi-section .swiper-next",
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            breakpoints: {
                320: {
                    slidesPerView: 1,
                    slidesPerGroup: 1,
                    spaceBetween: 30,
                },
                767: {
                    slidesPerView: 1,
                    slidesPerGroup: 1,
                    spaceBetween: 30,
                },
                1024: {
                    slidesPerView: 2,
                    slidesPerGroup: 1,
                },
            },
        });

        // Testi Carousel
        var swiperTesti = new Swiper(".testi-carousel-3", {
            slidesPerView: 3,
            spaceBetween: 30,
            slidesPerGroup: 1,
            loop: true,
            autoplay: true,
            grabcursor: true,
            speed: 600,
            grabcursor: true,
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            breakpoints: {
                320: {
                    slidesPerView: 1,
                    slidesPerGroup: 1,
                    spaceBetween: 30,
                },
                767: {
                    slidesPerView: 2,
                    slidesPerGroup: 1,
                    spaceBetween: 30,
                },
                1024: {
                    slidesPerView: 3,
                    slidesPerGroup: 1,
                },
            },
        });

        // Testi Carousel
        var swiperPostthumb= new Swiper(".post-thumb-carousel", {
            slidesPerView: 1,
            spaceBetween: 10,
            slidesPerGroup: 1,
            loop: true,
            autoplay: true,
            grabcursor: true,
            speed: 600,
            grabcursor: true,
            navigation: {
                nextEl: ".post-thumb-carousel .swiper-prev",
                prevEl: ".post-thumb-carousel .swiper-next",
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            breakpoints: {
                320: {
                    slidesPerView: 1,
                    slidesPerGroup: 1,
                    spaceBetween: 10,
                },
                767: {
                    slidesPerView: 1,
                    slidesPerGroup: 1,
                    spaceBetween: 10,
                },
                1024: {
                    slidesPerView: 1,
                    slidesPerGroup: 1,
                },
            },
        });

        // Date Range Picker
        $(function () {
            $('input[name="daterange"]').daterangepicker(
                {
                    opens: "center",
                },
                function (start, end, label) {
                    console.log(
                        "A new date selection was made: " + start.format("YYYY-MM-DD") + " to " + end.format("YYYY-MM-DD")
                    );
                }
            );
        });

        $(function () {
            $('input[name="birthday"]').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
            });
        });


        //Swiper Slider For Shop
        var swiper = new Swiper(".product-gallary-thumb", {
            spaceBetween: 10,
            slidesPerView: 3,
            freeMode: true,
            watchSlidesProgress: true,
        });
        var swiper2 = new Swiper(".product-gallary", {
            spaceBetween: 10,
            loop: true,
            navigation: {
                nextEl: ".swiper-nav-next",
                prevEl: ".swiper-nav-prev",
            },
            thumbs: {
                swiper: swiper,
            },
        });

        // Stroke Text

        $(function(){
            let container_svg = $('.container-svg');
            let that, svg, text, bbox, width, height, calc_ratio, stroke_dasharray, new_value_stroke;
            let is_safari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);
            let is_retina = false;
            if (matchMedia("(-webkit-min-device-pixel-ratio: 2), (min-device-pixel-ratio: 2), (min-resolution: 192dpi)").matches){
                is_retina = true;
            }
            container_svg.each(function(){
                that = $(this);
                // Set viewBox size
                svg = $('svg', that);
                text = $('text', that);
                bbox = text[0].getBBox();
                width = that.width();
                height = bbox.height;
                svg.attr('viewBox', '0 0 '+width+' '+height);
                // Set container height with ratio
                calc_ratio = (height*100/width);
                that.css('padding-bottom', calc_ratio+'%');
                if(is_safari){ // Safari fix
                    text.attr('y', '1em');
                }
                    if(is_retina){
                    stroke_dasharray = text.css('stroke-dasharray');
                    new_value_stroke = retina_stroke_dasharray(stroke_dasharray);
                    text.css('stroke-dasharray', new_value_stroke);
                }
            })
        })
        
        function retina_stroke_dasharray(value){
            let array = value.split(",");
            for(let i = 0; i < array.length; i++){
                array[i] = (parseInt(array[i])*2)+'px';
            }
            return array.join(', ');
        }

        //Running Animated Text
        const scrollers = document.querySelectorAll(".scroller");

        if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
            addAnimation();
        }

        function addAnimation() {
            scrollers.forEach((scroller) => {
                scroller.setAttribute("data-animated", true);

                const scrollerInner = scroller.querySelector(".scroller__inner");
                const scrollerContent = Array.from(scrollerInner.children);

                scrollerContent.forEach((item) => {
                    const duplicatedItem = item.cloneNode(true);
                    duplicatedItem.setAttribute("aria-hidden", true);
                    scrollerInner.appendChild(duplicatedItem);
                });
            });
        }

        // Image Reveal
        
        gsap.registerPlugin(ScrollTrigger);

        let revealContainers = document.querySelectorAll(".reveal");

        revealContainers.forEach((container) => {
        let image = container.querySelector("img");
        let tl = gsap.timeline({
            scrollTrigger: {
            trigger: container,
            toggleActions: "restart none none reset"
            }
        });

        tl.set(container, { autoAlpha: 1 });
        tl.from(container, 1.5, {
            xPercent: -100,
            ease: Power2.out
        });
        tl.from(image, 1.5, {
            xPercent: 100,
            scale: 1.3,
            delay: -1.5,
            ease: Power2.out
        });
        });

        const images = document.querySelectorAll(".img-reveal");

        const removeOverlay = overlay => {
            let tl = gsap.timeline();

            tl.to(overlay, {
                duration: 1.4,
                ease: "Power2.easeInOut",
                width: "0%"
            });

            return tl;
        };

        const scaleInImage = image => {
            let tl = gsap.timeline();

            tl.from(image, {
                duration: 1.4,
                scale: 1.4,
                ease: "Power2.easeInOut"
            });

            return tl;
        };

        images.forEach(image => {
        
            gsap.set(image, {
                visibility: "visible"
            });
        
            const overlay = image.querySelector('.img-overlay');
            const img = image.querySelector("img");

            const masterTL = gsap.timeline({ paused: true });
            masterTL
            .add(removeOverlay(overlay))
            .add(scaleInImage(img), "-=1.4");
        
        
        let options = {
            threshold: 0
        }

            const io = new IntersectionObserver((entries, options) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        masterTL.play();
                    } else {
                masterTL.progress(0).pause()
            }
                });
            }, options);

            io.observe(image);
        });


        // Scroll Animation

        let typeSplit = new SplitType("[data-text-animation]", {
            types: "lines,words, chars",
            className: "line",
        });
        var text_animations = document.querySelectorAll(
            "[data-text-animation]"
            );
            
            function createScrollTrigger(triggerElement, timeline) {
            // Play tl when scrolled into view (60% from top of screen)
            ScrollTrigger.create({
                trigger: triggerElement,
                start: "top 80%",
                onEnter: () => timeline.play(),
                toggleClass: {targets: triggerElement, className: "active"} 
            });
        }

            text_animations.forEach((animation) => {
            let type = "slide-up",
            duration = 0.75,
            offset = 80,
            stagger = 0.6,
            delay = 0,
            scroll = 1,
            split = "line",
            ease = "power2.out";
        // Set attribute
        if (animation.getAttribute("data-stagger")) {
            stagger = animation.getAttribute("data-stagger");
        }
        if (animation.getAttribute("data-duration")) {
            duration = animation.getAttribute("data-duration");
        }
        if (animation.getAttribute("data-text-animation")) {
            type = animation.getAttribute("data-text-animation");
        }
        if (animation.getAttribute("data-delay")) {
            delay = animation.getAttribute("data-delay");
        }
        if (animation.getAttribute("data-ease")) {
            ease = animation.getAttribute("data-ease");
        }
        if (animation.getAttribute("data-scroll")) {
            scroll = animation.getAttribute("data-scroll");
        }
        if (animation.getAttribute("data-offset")) {
            offset = animation.getAttribute("data-offset");
        }
        if (animation.getAttribute("data-split")) {
            split = animation.getAttribute("data-split");
        }
        if (scroll == 1) {
            if (type == "slide-up") {
            let tl = gsap.timeline({ paused: true });
            tl.from(animation.querySelectorAll(`.${split}`), {
                yPercent: offset,
                duration,
                ease,
                opacity: 0,
                stagger: { amount: stagger },
            });
            createScrollTrigger(animation, tl);
            }
            if (type == "slide-down") {
            let tl = gsap.timeline({ paused: true });
            tl.from(animation.querySelectorAll(`.${split}`), {
                yPercent: -offset,
                duration,
                ease,
                opacity: 0,
                stagger: { amount: stagger },
            });
            createScrollTrigger(animation, tl);
            }
            if (type == "rotate-in") {
                let tl = gsap.timeline({ paused: true });
                tl.set(animation.querySelectorAll(`.${split}`), {
                    transformPerspective: 400,
                });
            tl.from(animation.querySelectorAll(`.${split}`), {
                rotationX: -offset,
                duration,
                ease,
                force3D: true,
                opacity: 0,
                transformOrigin: "top center -50",
                stagger: { amount: stagger },
            });
            createScrollTrigger(animation, tl);
            }
            if (type == "slide-from-left") {
                let tl = gsap.timeline({ paused: true });
                tl.from(animation.querySelectorAll(`.${split}`), {
                    opacity: 0,
                    xPercent: -offset,
                    duration,
                    opacity: 0,
                    ease,
                    stagger: { amount: stagger },
                });
            createScrollTrigger(animation, tl);
            }
            if (type == "slide-from-right") {
                let tl = gsap.timeline({ paused: true });
                tl.from(animation.querySelectorAll(`.${split}`), {
                    opacity: 0,
                    xPercent: offset,
                    duration,
                    opacity: 0,
                    ease,
                    stagger: { amount: stagger },
                });
            createScrollTrigger(animation, tl);
            }
            if (type == "fade-in") {
                let tl = gsap.timeline({ paused: true });
                tl.from(animation.querySelectorAll(`.${split}`), {
                    opacity: 0,
                    duration,
                    ease,
                    opacity: 0,
                    stagger: { amount: stagger },
                });
                createScrollTrigger(animation, tl);
            }
            if (type == "fade-in-random") {
                let tl = gsap.timeline({ paused: true });
                tl.from(animation.querySelectorAll(`.${split}`), {
                    opacity: 0,
                    duration,
                    ease,
                    opacity: 0,
                    stagger: { amount: stagger, from: "random" },
                });
            createScrollTrigger(animation, tl);
            }
            if (type == "scrub") {
                let tl = gsap.timeline({
                    scrollTrigger: {
                    trigger: animation,
                    start: "top 90%",
                    end: "top center",
                    scrub: true,
                    },
                });
            tl.from(animation.querySelectorAll(`.${split}`), {
                opacity: 0.2,
                duration,
                ease,
                stagger: { amount: stagger },
            });
            }

            // Avoid flash of unstyled content
            gsap.set("[data-text-animation]", { opacity: 1 });
        } else {
            if (type == "slide-up") {
                let tl = gsap.timeline({ paused: true });
                tl.from(animation.querySelectorAll(`.${split}`), {
                    yPercent: offset,
                    duration,
                    ease,
                    opacity: 0,
                });
            }
            if (type == "slide-down") {
                let tl = gsap.timeline({ paused: true });
                tl.from(animation.querySelectorAll(`.${split}`), {
                    yPercent: -offset,
                    duration,
                    ease,
                    opacity: 0,
                });
            }
            if (type == "rotate-in") {
                let tl = gsap.timeline({ paused: true });
                tl.set(animation.querySelectorAll(`.${split}`), {
                    transformPerspective: 400,
                });
                tl.from(animation.querySelectorAll(`.${split}`), {
                    rotationX: -offset,
                    duration,
                    ease,
                    force3D: true,
                    opacity: 0,
                    transformOrigin: "top center -50",
                });
            }
            if (type == "slide-from-right") {
                let tl = gsap.timeline({ paused: true });
                tl.from(animation.querySelectorAll(`.${split}`), {
                    opacity: 0,
                    xPercent: offset,
                    duration,
                    opacity: 0,
                    ease,
                });
            }
            if (type == "fade-in") {
                let tl = gsap.timeline({ paused: true });
                tl.from(animation.querySelectorAll(`.${split}`), {
                    opacity: 0,
                    duration,
                    ease,
                    opacity: 0,
                });
            }
            if (type == "text-slide-effect") {
                let tl = gsap.timeline({ paused: true });
                tl.from(animation.querySelectorAll(`.${split}`), {
                    opacity: 0,
                    duration,
                    ease,
                    opacity: 0,
                });
            }
            if (type == "fade-in-random") {
                let tl = gsap.timeline({ paused: true });
                tl.from(animation.querySelectorAll(`.${split}`), {
                    opacity: 0,
                    duration,
                    ease,
                    opacity: 0,
                    stagger: { amount: stagger, from: "random" },
                });
            }
            if (type == "scrub") {
                tl.from(animation.querySelectorAll(`.${split}`), {
                    opacity: 0.2,
                    duration,
                    ease,
                });
            }
        }
        });

        function textAnimationEffect(){
            let TextAnim = gsap.timeline();
            let splitText = new SplitType( ".text-animation-effect", { types: 'chars' });
            if( $('.text-animation-effect .char').length ){
                TextAnim.from(".text-animation-effect .char", { duration: 1, x: 100, autoAlpha: 0, stagger: 0.1 }, "-=1");
            }
        }
        
        window.addEventListener("load", (event) => {
            textAnimationEffect();
        });

        if ($(".fade-wrapper").length > 0) {
            $(".fade-wrapper").each(function () {
                var section = $(this);
                var fadeItems = section.find(".fade-top");
        
                fadeItems.each(function (index, element) {
                var delay = index * 0.15;
        
                gsap.set(element, {
                    opacity: 0,
                    y: 100,
                });
        
                ScrollTrigger.create({
                    trigger: element,
                    start: "top 100%",
                    end: "bottom 20%",
                    scrub: 0.5,
                    onEnter: function () {
                    gsap.to(element, {
                        opacity: 1,
                        y: 0,
                        duration: 1,
                        delay: delay,
                    });
                    },
                    once: true,
                });
                });
            });
        }

        // Page Scroll Percentage
        function scrollTopPercentage() {
            const scrollPercentage = () => {
                const scrollTopPos = document.documentElement.scrollTop;
                const calcHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                const scrollValue = Math.round((scrollTopPos / calcHeight) * 100);
                const scrollElementWrap = $("#scroll-percentage");

                scrollElementWrap.css("background", `conic-gradient( var(--bz-color-theme-primary) ${scrollValue}%, var(--bz-color-common-white) ${scrollValue}%)`);
                
                // ScrollProgress
                if ( scrollTopPos > 100 ) {
                    scrollElementWrap.addClass("active");
                } else {
                    scrollElementWrap.removeClass("active");
                }

                if( scrollValue < 96 ) {
                    $("#scroll-percentage-value").text(`${scrollValue}%`);
                } else {
                    $("#scroll-percentage-value").html('<i class="fa-sharp fa-regular fa-arrow-up-long"></i>');
                }
            }
            window.onscroll = scrollPercentage;
            window.onload = scrollPercentage;

            // Back to Top
            function scrollToTop() {
                document.documentElement.scrollTo({
                    top: 0,
                    behavior: "smooth"
                });
            }
            
            $("#scroll-percentage").on("click", scrollToTop);
        }
        scrollTopPercentage();
    });

   

    
    $(".popup-image").magnificPopup({
        type: "image",
        gallery: {
            enabled: true,
        },
    });

    // testimonial-4__active start
    var testimonial = new Swiper('.testimonial-4__active', {
        slidesPerView: 3,
        loop: true,
        autoplay: true,
        arrow: true,
        spaceBetween: 30,
        speed: 1500,
        centeredSlides: true,
        pagination: {
            el: ".testimonial-4__pagination",
            clickable: true,
        },
        breakpoints: {
            320: {
                slidesPerView: 1,
            },
            575: {
                slidesPerView: 2,
                centeredSlides: false,
                spaceBetween: 20,
            },
            767: {
                slidesPerView: 2,
            },
            1200: {
                slidesPerView: 3,
            },
        },
    });

    // feedback-5__active start
    var services = new Swiper('.feedback-5__active', {
        slidesPerView: 2,
        loop: true,
        autoplay: true,
        arrow: false,
        spaceBetween: 30,
        speed: 1500,
        centeredSlides: false,
        breakpoints: {
            320: {
                slidesPerView: 1,
            },
            575: {
                slidesPerView: 2,
            },
            992: {
                slidesPerView: 2,
            },
            1200: {
                slidesPerView: 2,
            },
        },
    });

    // project-5__active start
    var project_5__active = new Swiper(".project-5__active", {
        spaceBetween: 1,
        slidesPerView: 3,
        centeredSlides: true,
        roundLengths: true,
        loop: true,
        autoplay: true,
        speed: 1500,
        loopAdditionalSlides: 30,

        breakpoints: {
            320: {
                slidesPerView: 1,
            },
            575: {
                slidesPerView: 1,
            },
            993: {
                slidesPerView: 2,
            },
            1200: {
                slidesPerView: 3,
            },
        },
      });

   //client-testimonial start
    $(function () {
        var galleryTop, galleryThumbs;
    
        function initSwiper() {
            // Destroy existing Swiper instances if they exist
            if (galleryTop) {
                galleryTop.destroy(true, true);
            }
            if (galleryThumbs) {
                galleryThumbs.destroy(true, true);
            }
    
            // Check if the required elements exist
            if ($(".mySwiperDesktop").length || $(".mySwiper").length || $(".mySwiper2").length) {
                if ($(window).width() > 768) {
                    // Initialize Swiper for desktop
                    galleryTop = new Swiper(".mySwiperDesktop", {
                        spaceBetween: 10,
                        slidesPerView: 9,
                        direction: 'horizontal', // Default slides per view for desktop
                        centeredSlides: true,
                        loop: true,
                        watchSlidesProgress: true,
                        breakpoints: {
                            320: { slidesPerView: 1 },
                            575: { slidesPerView: 5 },
                            993: { slidesPerView: 7 },
                            1200: { slidesPerView: 9 },
                        },
                    });
                    galleryThumbs = new Swiper(".mySwiper2", {
                        spaceBetween: 10,
                        navigation: {
                            nextEl: ".swiper-button-next",
                            prevEl: ".swiper-button-prev",
                        },
                        a11y: {
                            prevSlideMessage: "Previous slide",
                            nextSlideMessage: "Next slide",
                        },
                        thumbs: { swiper: galleryTop },
                    });
                } else {
                    // Initialize Swiper for mobile
                    galleryTop = new Swiper(".mySwiper", {
                        spaceBetween: 10,
                        slidesPerView: 9,
                        freeMode: false,
                        centeredSlides: true,
                        loop: true,
                        watchSlidesProgress: true,
                        breakpoints: {
                            320: { slidesPerView: 3 },
                            575: { slidesPerView: 4 },
                            993: { slidesPerView: 7 },
                            1200: { slidesPerView: 9 },
                        },
                    });
                    galleryThumbs = new Swiper(".mySwiper2", {
                        spaceBetween: 10,
                        navigation: {
                            nextEl: ".swiper-button-next",
                            prevEl: ".swiper-button-prev",
                        },
                        a11y: {
                            prevSlideMessage: "Previous slide",
                            nextSlideMessage: "Next slide",
                        },
                        thumbs: { swiper: galleryTop },
                    });
                }
    
                // Sync the slide change between galleryTop and galleryThumbs
                galleryTop.on("slideChangeTransitionStart", function () {
                    galleryThumbs.slideTo(galleryTop.activeIndex);
                });
                galleryThumbs.on("transitionStart", function () {
                    galleryTop.slideTo(galleryThumbs.activeIndex);
                });
            }
        }
    
        initSwiper();
    
        // Reinitialize Swiper on window resize
        $(window).resize(function () {
            initSwiper();
        });
    });
    
      

})(jQuery);
