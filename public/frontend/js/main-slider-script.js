var tpj = jQuery;
var revapi486;
var isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent); // Detect iOS devices

tpj(document).ready(function() {
    var sliderSettings = {
        sliderType: "standard",
        jsFileLocation: "plugins/revolution/js/",
        sliderLayout: "auto",
        dottedOverlay: "none",
        delay: 15000,
        navigation: {
            keyboardNavigation: "off",
            keyboard_direction: "horizontal",
            mouseScrollNavigation: "off",
            mouseScrollReverse: "default",
            onHoverStop: "off",
            touch: {
                touchenabled: "on", // Ensure touch is enabled
                touchOnDesktop: "off",
                swipe_threshold: 75,
                swipe_min_touches: 1,
                swipe_direction: "horizontal",
                drag_block_vertical: false
            },
            arrows: {
                style: "metis",
                enable: true,
                hide_onmobile: true,
                hide_under: 600,
                hide_onleave: false,
                tmp: '',
                left: {
                    h_align: "left",
                    v_align: "center",
                    h_offset: 0,
                    v_offset: 0
                },
                right: {
                    h_align: "right",
                    v_align: "center",
                    h_offset: 0,
                    v_offset: 0
                }
            }
        },
        responsiveLevels: [1200, 1040, 802, 480],
        visibilityLevels: [1200, 1040, 802, 480],
        gridwidth: [1200, 1040, 802, 480],
        gridheight: [710, 600, 500, 400], // Adjusted height for mobile devices
        lazyType: "smart", // Enable lazy loading for better performance
        parallax: {
            type: isIOS ? "off" : "mouse", // Disable parallax on iOS devices
            origo: "enterpoint",
            speed: 1000,
            levels: [1, 2, 3, 4, 5]
        },
        shadow: 0,
        spinner: "off",
        stopLoop: "off",
        stopAfterLoops: -1,
        stopAtSlide: -1,
        shuffle: "off",
        autoHeight: "off",
        hideThumbsOnMobile: "off",
        hideSliderAtLimit: 0,
        hideCaptionAtLimit: 0,
        hideAllCaptionAtLilmit: 0,
        debugMode: false,
        fallbacks: {
            simplifyAll: "off",
            nextSlideOnWindowFocus: "off",
            disableFocusListener: false
        }
    };

    // Initialize rev_slider_one
    if (tpj("#rev_slider_one").revolution == undefined) {
        revslider_showDoubleJqueryError("#rev_slider_one");
    } else {
        if (isIOS) {
            sliderSettings.touch.touchenabled = "on"; // Force touch on for iOS
        }
        revapi486 = tpj("#rev_slider_one").show().revolution(sliderSettings);
    }

    // Initialize rev_slider_two
    if (tpj("#rev_slider_two").revolution == undefined) {
        revslider_showDoubleJqueryError("#rev_slider_two");
    } else {
        if (typeof sliderSettings !== 'undefined') {
            sliderSettings.touch = sliderSettings.touch || {}; // Ensure touch object is defined
            sliderSettings.touch.touchenabled = "on";
        }
        sliderSettings.gridheight = [850, 600, 500, 400]; // Adjust heights for slider two
        revapi486 = tpj("#rev_slider_two").show().revolution(sliderSettings);
    }
});
