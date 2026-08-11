(function($) {
	
	"use strict";
	
	
	//Hide Loading Box (Preloader)
	function handlePreloader() {
		if($('.preloader').length){
			$('.preloader').delay(200).fadeOut(500);
		}
	}
	
	
	//Update Header Style and Scroll to Top
	function headerStyle() {
		if($('.main-header').length){
			var windowpos = $(window).scrollTop();
			var siteHeader = $('.main-header');
			var sticky_header = $('.main-header .sticky-header');
			var scrollLink = $('.scroll-to-top');
			if (windowpos > 100) {
				siteHeader.addClass('fixed-header');
				sticky_header.addClass("animated slideInDown");
				scrollLink.fadeIn(300);
			} else {
				siteHeader.removeClass('fixed-header');
				sticky_header.removeClass("animated slideInDown");
				scrollLink.fadeOut(300);
			}
		}
	}
	
	headerStyle();

	function initMainMenuDropdown() {
		if (!$('.main-header li.dropdown ul').length) {
			return;
		}

		$('.main-header .navigation li.dropdown').each(function() {
			if (!$(this).children('.dropdown-btn').length) {
				$(this).append('<div class="dropdown-btn"><span class="fa fa-angle-down"></span></div>');
			}
		});

		$('.main-header .navigation li.dropdown .dropdown-btn')
			.off('click.mainMenuDropdown')
			.on('click.mainMenuDropdown', function(e) {
				e.preventDefault();
				$(this).prev('ul').slideToggle(500);
			});

		$('.main-header .navigation li.dropdown > a')
			.off('click.mainMenuParent')
			.on('click.mainMenuParent', function(e) {
				e.preventDefault();
			});
	}

	function initMobileMenu() {
		if (!$('.mobile-menu').length) {
			return;
		}

		var $sourceNavigation = $('.main-header .nav-outer .main-menu .navigation').first();
		var $mobileNavigation = $('.mobile-menu .navigation').first();

		if ($sourceNavigation.length && $mobileNavigation.length) {
			var $navClone = $sourceNavigation.clone();
			$navClone.find('.dropdown-btn').remove();
			$mobileNavigation.html($navClone.html());
		}

		if (!$mobileNavigation.find('a[data-dialog-action="login"]').length) {
			$mobileNavigation.append('<li class="mobile-login-link"><a href="#" data-dialog-action="login">Staff Login</a></li>');
		}

		$('.mobile-menu .navigation li.dropdown').each(function() {
			if (!$(this).children('.dropdown-btn').length) {
				$(this).append('<div class="dropdown-btn"><span class="fa fa-angle-down"></span></div>');
			}
		});

		$('.mobile-menu .close-btn, .mobile-menu .menu-backdrop')
			.off('click.mobileMenuState')
			.on('click.mobileMenuState', function() {
				$('body').removeClass('mobile-menu-visible');
			});

		$('.mobile-nav-toggler')
			.off('click.mobileMenuState')
			.on('click.mobileMenuState', function() {
				$('body').addClass('mobile-menu-visible');
			});

		$('.mobile-menu .navigation')
			.off('click.mobileMenuDropdown', 'li.dropdown .dropdown-btn')
			.on('click.mobileMenuDropdown', 'li.dropdown .dropdown-btn', function(e) {
				e.preventDefault();
				$(this).prev('ul').slideToggle(500);
			});

		$(document)
			.off('click.mobileMenuNav', '.mobile-menu .navigation a[href]')
			.on('click.mobileMenuNav', '.mobile-menu .navigation a[href]', function(event) {
				var href = $(this).attr('href');
				if (!href) {
					return;
				}

				var dialogAction = String($(this).attr('data-dialog-action') || '').trim().toLowerCase();
				if (!dialogAction) {
					var linkText = String($(this).text() || '').trim().toLowerCase();
					if (linkText === 'thank a caregiver' || linkText === 'thank a carer') {
						dialogAction = 'caregiver';
					} else if (linkText === 'raise a concern' || linkText === 'file a complaint') {
						dialogAction = 'complaint';
					} else if (linkText === 'staff login' || linkText === 'login') {
						dialogAction = 'login';
					}
				}

				if (dialogAction) {
					event.preventDefault();
					$('body').removeClass('mobile-menu-visible');
					window.dispatchEvent(new CustomEvent('facilitate:open-dialog', { detail: { action: dialogAction } }));
					return;
				}

				if (href === '#' || href.indexOf('javascript:') === 0) {
					event.preventDefault();
					return;
				}

				if (href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) {
					$('body').removeClass('mobile-menu-visible');
					return;
				}

				var targetUrl;
				try {
					targetUrl = new URL(href, window.location.origin);
				} catch (error) {
					return;
				}

				if (targetUrl.origin !== window.location.origin) {
					return;
				}

				var targetPath = targetUrl.pathname + targetUrl.search + targetUrl.hash;
				if (!targetPath || targetPath.charAt(0) !== '/') {
					return;
				}

				event.preventDefault();
				$('body').removeClass('mobile-menu-visible');

				if (window.__facilitateRouter && typeof window.__facilitateRouter.push === 'function') {
					window.__facilitateRouter.push(targetPath).catch(function() {});
				} else {
					window.location.assign(targetPath);
				}
			});
	}

	function initRevolutionSlider() {
		var $slider = $('#rev_slider_one');
		if (!$slider.length || typeof $slider.revolution !== 'function') {
			return;
		}

		if ($slider.hasClass('revslider-initialised')) {
			try {
				$slider.revredraw();
			} catch (error) {}
			return;
		}

		$slider.show().revolution({
			sliderType: 'standard',
			jsFileLocation: '/frontend/plugins/revolution/js/',
			sliderLayout: 'auto',
			dottedOverlay: 'none',
			delay: 9000,
			navigation: {
				keyboardNavigation: 'on',
				keyboard_direction: 'horizontal',
				mouseScrollNavigation: 'off',
				onHoverStop: 'off',
				touch: {
					touchenabled: 'on',
					swipe_threshold: 75,
					swipe_min_touches: 1,
					swipe_direction: 'horizontal',
					drag_block_vertical: false
				},
				arrows: {
					style: 'zeus',
					enable: true,
					hide_onmobile: true,
					hide_under: 600,
					hide_onleave: true,
					hide_delay: 200,
					hide_delay_mobile: 1200,
					tmp: '<div class="tp-title-wrap"> <div class="tp-arr-imgholder"></div> </div>',
					left: {
						h_align: 'left',
						v_align: 'center',
						h_offset: 30,
						v_offset: 0
					},
					right: {
						h_align: 'right',
						v_align: 'center',
						h_offset: 30,
						v_offset: 0
					}
				}
			},
			visibilityLevels: [1240, 1024, 778, 480],
			gridwidth: 1170,
			gridheight: 700,
			lazyType: 'none',
			shadow: 0,
			spinner: 'off',
			stopLoop: 'off',
			stopAfterLoops: -1,
			stopAtSlide: -1,
			shuffle: 'off',
			autoHeight: 'off',
			disableProgressBar: 'on',
			hideThumbsOnMobile: 'off',
			hideSliderAtLimit: 0,
			hideCaptionAtLimit: 0,
			hideAllCaptionAtLilmit: 0,
			debugMode: false,
			fallbacks: {
				simplifyAll: 'off',
				nextSlideOnWindowFocus: 'off',
				disableFocusListener: false,
			}
		});
	}

	function initOwlCarousel(selector, config) {
		if (typeof $.fn.owlCarousel !== 'function') {
			return;
		}

		$(selector).each(function() {
			var $carousel = $(this);
			if ($carousel.hasClass('owl-loaded')) {
				try {
					$carousel.trigger('refresh.owl.carousel');
				} catch (error) {}
				return;
			}

			$carousel.owlCarousel(config);
		});
	}

	function initCarousels() {
		initOwlCarousel('.banner-carousel', {
			animateOut: 'slideOutDown',
			animateIn: 'fadeIn',
			loop: true,
			margin: 0,
			nav: true,
			singleItem: true,
			smartSpeed: 500,
			autoHeight: false,
			autoplay: true,
			autoplayTimeout: 10000,
			navText: ['<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>'],
			responsive: {
				0: { items: 1 },
				600: { items: 1 },
				1024: { items: 1 },
			}
		});

		initOwlCarousel('.services-carousel', {
			animateOut: 'slideOutDown',
			animateIn: 'fadeIn',
			loop: true,
			margin: 30,
			nav: true,
			singleItem: true,
			smartSpeed: 500,
			autoHeight: false,
			autoplay: true,
			autoplayTimeout: 10000,
			navText: ['<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>'],
			responsive: {
				0: { items: 1 },
				600: { items: 2 },
				800: { items: 3 },
				1024: { items: 4 },
				1280: { items: 4 }
			}
		});

		initOwlCarousel('.testimonial-carousel', {
			animateOut: 'slideOutDown',
			animateIn: 'fadeIn',
			loop: true,
			margin: 32,
			nav: true,
			singleItem: true,
			smartSpeed: 500,
			autoHeight: false,
			autoplay: true,
			autoplayTimeout: 10000,
			navText: ['<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>'],
			responsive: {
				0: { items: 1 },
				600: { items: 1 },
				800: { items: 2 },
				1024: { items: 2 },
				1280: { items: 2 }
			}
		});

		initOwlCarousel('.gallery-carousel', {
			animateOut: 'slideOutDown',
			animateIn: 'fadeIn',
			loop: true,
			margin: 30,
			nav: true,
			singleItem: true,
			smartSpeed: 500,
			autoHeight: false,
			autoplay: true,
			autoplayTimeout: 10000,
			navText: ['<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>'],
			responsive: {
				0: { items: 1 },
				600: { items: 2 },
				800: { items: 3 },
				1024: { items: 4 },
				1280: { items: 5 }
			}
		});

		initOwlCarousel('.project-carousel', {
			loop: true,
			margin: 30,
			nav: true,
			smartSpeed: 700,
			autoplay: 5000,
			navText: ['<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>'],
			responsive: {
				0: { items: 1 },
				600: { items: 2 },
				800: { items: 2 },
				1024: { items: 3 },
				1200: { items: 4 },
				1400: { items: 5 }
			}
		});
	}

	function initFancybox() {
		if (typeof $.fn.fancybox !== 'function' || !$('.lightbox-image').length) {
			return;
		}

		$('.lightbox-image').fancybox({
			openEffect: 'fade',
			closeEffect: 'fade',
			helpers: {
				media: {}
			}
		});
	}

	function initScrollTargets() {
		$(document)
			.off('click.scrollTarget', '.scroll-to-target')
			.on('click.scrollTarget', '.scroll-to-target', function() {
				var target = $(this).attr('data-target');
				if (!target || !$(target).length) {
					return;
				}

				$('html, body').animate({
					scrollTop: $(target).offset().top
				}, 1500);
			});
	}

	function runDynamicInitializers() {
		initMainMenuDropdown();
		initMobileMenu();
		initRevolutionSlider();
		initCarousels();
		initFancybox();
		initScrollTargets();
		headerStyle();
	}
	

	// Submenu Dropdown Toggle
	$(document).ready(function() {
		initMainMenuDropdown();
	});
	
	// Submenu Dropdown Toggle
	// $(document).ready(function() {
	// 	if ($('.main-header li.dropdown ul').length) {
	// 		$('.main-header .navigation li.dropdown').append('<div class="dropdown-btn" data-route-path=""><span class="fa fa-angle-down"></span></div>');

	// 		// Dropdown Button
	// 		$('.main-header .navigation li.dropdown .dropdown-btn').on('click', function() {
	// 			const routePath = $(this).data('route-path');
	// 			if (routePath) {
	// 				// Programmatically navigate using Vue Router
	// 				router.push(routePath);
	// 			} else {
	// 				$(this).prev('ul').slideToggle(500);
	// 			}
	// 		});
	// 	}
	// });

	
	// Mobile Nav Hide Show
	$(document).ready(function() {
		initMobileMenu();
	});
	
	$(document).ready(function() {
		initRevolutionSlider();
	});
	

	//Banner Carousel
	$(document).ready(function() {
		if ($('.banner-carousel').length) {
			$('.banner-carousel').owlCarousel({
				animateOut: 'slideOutDown',
				animateIn: 'fadeIn',
				loop: true,
				margin: 0,
				nav: true,
				singleItem: true,
				smartSpeed: 500,
				autoHeight: false,
				autoplay: true,
				autoplayTimeout:10000,
				navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
				responsive:{
					0:{
						items:1
					},
					600:{
						items:1
					},
					1024:{
						items:1
					},
				}
			});    		
		}
	});
	
	
	// Services Carousel
	$(document).ready(function() {
	if ($('.services-carousel').length) {
		$('.services-carousel').owlCarousel({
			animateOut: 'slideOutDown',
    		animateIn: 'fadeIn',
			loop:true,
			margin:30,
			nav:true,
			singleItem:true,
			smartSpeed: 500,
			autoHeight: false,
			autoplay: true,
			autoplayTimeout:10000,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:2
				},
				800:{
					items:3
				},
				1024:{
					items:4
				},
				1280:{
					items:4
				}
			}
		});    		
	}
});
	
	
	// Testimonial Carousel
	$(document).ready(function() {
	if ($('.testimonial-carousel').length) {
		$('.testimonial-carousel').owlCarousel({
			animateOut: 'slideOutDown',
    		animateIn: 'fadeIn',
			loop:true,
			margin:32,
			nav:true,
			singleItem:true,
			smartSpeed: 500,
			autoHeight: false,
			autoplay: true,
			autoplayTimeout:10000,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:1
				},
				800:{
					items:2
				},
				1024:{
					items:2
				},
				1280:{
					items:2
				}
			}
		});    		
	}
});
	
	
	// Gallery Carousel
	$(document).ready(function() {
	if ($('.gallery-carousel').length) {
		$('.gallery-carousel').owlCarousel({
			animateOut: 'slideOutDown',
    		animateIn: 'fadeIn',
			loop:true,
			margin:30,
			nav:true,
			singleItem:true,
			smartSpeed: 500,
			autoHeight: false,
			autoplay: true,
			autoplayTimeout:10000,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:2
				},
				800:{
					items:3
				},
				1024:{
					items:4
				},
				1280:{
					items:5
				}
			}
		});    		
	}
});
	
	
	//Product Tabs
	$(document).ready(function() {
	if($('.project-tab').length){
		$('.project-tab .product-tab-btns .p-tab-btn').on('click', function(e) {
			e.preventDefault();
			var target = $($(this).attr('data-tab'));
			
			if ($(target).hasClass('actve-tab')){
				return false;
			}else{
				$('.project-tab .product-tab-btns .p-tab-btn').removeClass('active-btn');
				$(this).addClass('active-btn');
				$('.project-tab .p-tabs-content .p-tab').removeClass('active-tab');
				$(target).addClass('active-tab');
			}
		});
	}
});
	
	
	//Product Carousel
	$(document).ready(function() {
	if ($('.project-carousel').length) {
		$('.project-carousel').owlCarousel({
			loop:true,
			margin:30,
			nav:true,
			smartSpeed: 700,
			autoplay: 5000,
			navText: [ '<span class="fa fa-angle-left"></span>', '<span class="fa fa-angle-right"></span>' ],
			responsive:{
				0:{
					items:1
				},
				600:{
					items:2
				},
				800:{
					items:2
				},
				1024:{
					items:3
				},
				1200:{
					items:4
				},
				1400:{
					items:5
				}
			}
		});    		
	}
});
	
	//Gallery Filters
	 if($('.filter-list').length){
	 	 $('.filter-list').mixItUp({});
	 }
	 
	 //Accordion Box
	if($('.accordion-box').length){
		$(".accordion-box").on('click', '.acc-btn', function() {
			
			var outerBox = $(this).parents('.accordion-box');
			var target = $(this).parents('.accordion');
			
			if($(this).hasClass('active')!==true){
				$(outerBox).find('.accordion .acc-btn').removeClass('active');
			}
			
			if ($(this).next('.acc-content').is(':visible')){
				return false;
			}else{
				$(this).addClass('active');
				$(outerBox).children('.accordion').removeClass('active-block');
				$(outerBox).find('.accordion').children('.acc-content').slideUp(300);
				target.addClass('active-block');
				$(this).next('.acc-content').slideDown(300);	
			}
		});	
	}
	
	
	//Time Picker
	if($('.timepicker').length){
		$('.timepicker').timepicker();
	}
	
	//Date Picker
	if($('.datepicker').length){
		$( '.datepicker' ).datepicker();
	}
	
	
	//LightBox / Fancybox
	$(document).ready(function() {
		initFancybox();
 });

	
	//Contact Form Validation
	$(document).ready(function() {
		if($('#contact-form').length){
			$('#contact-form').validate({
				rules: {
					username: {
						required: true
					},
					email: {
						required: true,
						email: true
					},
					phone: {
						required: true
					},
					message: {
						required: true
					}
				}
			});
		}
	});
	
	// Scroll to a Specific Div
	$(document).ready(function() {
		initScrollTargets();
	 });
	
	
	// Elements Animation
	$(document).ready(function() {
		if($('.wow').length){
			var wow = new WOW(
			{
				boxClass:     'wow',      // animated element css class (default is wow)
				animateClass: 'animated', // animation css class (default is animated)
				offset:       0,          // distance to the element when triggering the animation (default is 0)
				mobile:       true,       // trigger animations on mobile devices (default is true)
				live:         true       // act on asynchronously loaded content (default is true)
			}
			);
			wow.init();
		}
	});

	// Re-run UI initializers after SPA route transitions.
	$(window).on('facilitate:route-changed', function() {
		window.setTimeout(function() {
			runDynamicInitializers();
		}, 50);
	});

	$(document).ready(function() {
		runDynamicInitializers();
	});


/* ==========================================================================
   When document is Scrollig, do
   ========================================================================== */
$(document).ready(function() {
	$(window).on('scroll', function() {
		headerStyle();
	});
 });
	
/* ==========================================================================
   When document is loading, do
   ========================================================================== */
$(document).ready(function() {
	$(window).on('load', function() {
		handlePreloader();
	});	
 });

})(window.jQuery);
