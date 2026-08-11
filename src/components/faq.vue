<template>
    <div class="page-wrapper">
    <!-- Preloader -->
    <!-- <div class="preloader"></div> -->

    <header class="main-header">
        <!--Header Top-->
        <div class="header-top">
            <div class="auto-container clearfix">
                <div class="top-left clearfix">
                    <div class="text"><span class="icon flaticon-phone-receiver"></span> Need help? Call Us Now : <span class="number">{{ cmsValue('global', 'header', 'phone', '024 7623 1188') }}</span></div>

                </div>
                <div class="top-right clearfix">
                    <!-- Info List -->
					<ul class="info-list">
						<!-- <li><a href="/about">Our Story</a></li>
						<li><a href="/contact">Our Location</a></li> -->
						<li><router-link to="/about">Our Story</router-link></li>
						<li><router-link to="/contact">Our Location</router-link></li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- End Header Top -->

        <!-- Header Upper -->
        <div class="header-upper">
            <div class="inner-container">
                <div class="auto-container clearfix">
                    <!--Info-->
                    <div class="logo-outer">
                        <div class="logo"><a href="/"><img :src="cmsValue('global', 'header', 'logo_url', '/frontend/images/logo.png')" alt="" title=""></a></div>
                    </div>

                    <!--Nav Box-->
                    <div class="nav-outer clearfix">
                        <!--Mobile Navigation Toggler For Mobile--><div class="mobile-nav-toggler"><span class="icon flaticon-menu-button"></span></div>
                        <nav class="main-menu navbar-expand-md navbar-light">
                            <div class="navbar-header">
                                <!-- Togg le Button -->
                                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                                    <span class="icon flaticon-menu"></span>
                                </button>
                            </div>

                            <div class="collapse navbar-collapse clearfix" id="navbarSupportedContent">
                                <ul class="navigation clearfix">
								  <li><a href="/">Home</a></li>
								  <li class="dropdown"><a href="#">About us</a>
                                        <ul>
                                            <li><router-link to="/about">About Us</router-link></li>
											<li><router-link to="/team">Our Team</router-link></li>
											<li><router-link to="/faq">Faq's</router-link></li>
                                            <li><router-link to="/testimonial">Testimonial</router-link></li>
                                        </ul>
                                    </li>
									<li class="dropdown"><a href="#">Our Services</a>
                                      <ul>
                                          <li><router-link to="/elderlyservice">Elderly Care Service</router-link></li>
                                          <li><router-link to="/personalcare">Personal Care</router-link></li>
                                          <li><router-link to="/respitecare">Respite Care</router-link></li>
                                          <li><router-link to="/nursing">Live In Care</router-link></li>
                                          <li><router-link to="/discharge">Hospital Discharge</router-link></li>
                                          <li><router-link to="/care">Social Care</router-link></li>
                                          <li><router-link to="/chronical">Palliative Care</router-link></li>
                                          <li><router-link to="/specialcare">Special Need Care</router-link></li>
                                      </ul>
                                    </li>
									<li class="dropdown"><a href="#">Knowledge Center</a>
                                        <ul>
                                            <li><router-link to="/blog">Company News</router-link></li>
                                            <li><router-link to="/gallery">Our Gallery</router-link></li>
                                            <li><router-link to="/started">Getting Started</router-link></li>
											<li><router-link to="/caregiver">Careers</router-link></li>
                                        </ul>
                                    </li>
                                    <li><router-link to="/contact">Contact</router-link></li>
                                </ul>
                            </div>
                        </nav>
                        <!-- Main Menu End-->

                        <!-- Main Menu End-->
                        <div class="outer-box clearfix">
                            <!-- Button Box -->
                            <div class="btn-box">
                                <router-link to="/contact" class="theme-btn btn-style-one"><span class="txt">{{ cmsValue('global', 'header', 'primary_cta_text', 'Ask A Question') }}</span></router-link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--End Header Upper-->

    	<!-- Mobile Menu  -->
        <div class="mobile-menu">
            <div class="menu-backdrop"></div>
            <div class="close-btn"><span class="icon flaticon-cancel-1"></span></div> -->

            <!--Here Menu Will Come Automatically Via Javascript / Same Menu as in Header-->
            <nav class="menu-box">
            	<div class="nav-logo"><a href="/"><img :src="cmsValue('global', 'header', 'logo_url', '/frontend/images/logo.png')" alt="" title=""></a></div>
								<ul class="navigation clearfix">
                                </ul>
                
            </nav>
        </div> 
		<!-- End Mobile Menu-->

    </header>
    <!-- End Main Header -->

	<!--Page Title-->
    <section class="page-title" :style="{ backgroundImage: `url(${cmsValue('faq', 'hero', 'background_image_url', '/frontend/images/background/3.jpg')})` }">
    	<div class="auto-container">
        	<h2>{{ cmsValue('faq', 'hero', 'headline', 'Faq') }}</h2>
            <ul class="page-breadcrumb">
            	<li><a href="/">home</a></li>
                <li>{{ cmsValue('faq', 'hero', 'headline', 'Faq') }}</li>
            </ul>
        </div>
    </section>
    <!--End Page Title-->

	<!--Faq Section-->
    <section class="faq-section">
    	<div class="auto-container">
        	<div class="title-box">
        		<h2>{{ cmsValue('faq', 'content', 'headline', 'Frequently Asked Questions') }}</h2>
            	<div class="text">{{ cmsValue('faq', 'content', 'subheadline', 'Discover your question from underneath or present your inquiry from the submit box.') }}</div>
            </div>
            <!-- faq Form -->
            <!--div class="faq-search-box">
                <form method="post" action="contact.html">
                    <div class="form-group">
                        <input type="search" name="search-field" value="" placeholder="Search Your Answer" required>
                        <button type="submit"><span class="icon fa fa-search"></span></button>
                    </div>
                </form>
            </div-->

            <div class="row clearfix">

                <div class="column col-lg-6 col-md-12 col-sm-12">
                    <!--Accordian Box-->
                    <ul class="accordion-box faq">

                        <!--Block-->
                        <li class="accordion block">
                            <div class="acc-btn"><div class="icon-outer"><span class="icon icon-plus fas fa-plus"></span> <span class="icon icon-minus fas fa-minus"></span></div>{{ cmsValue('faq', 'content', 'question_1', 'Bring to the table win-win survival strategies?') }}</div>
                            <div class="acc-content">
                                <div class="content">
                                    <div class="text">{{ cmsValue('faq', 'content', 'answer_1', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.') }}</div>
                                </div>
                            </div>
                        </li>

                        <!--Block-->
                        <li class="accordion block">
                            <div class="acc-btn active"><div class="icon-outer"><span class="icon icon-plus fas fa-plus"></span> <span class="icon icon-minus fas fa-minus"></span></div>{{ cmsValue('faq', 'content', 'question_2', 'Override the digital divide with additional clickthroughs from DevOps?') }}</div>
                            <div class="acc-content current">
                                <div class="content">
                                    <div class="text">{{ cmsValue('faq', 'content', 'answer_2', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.') }}</div>
                                </div>
                            </div>
                        </li>

                        <!--Block-->
                        <li class="accordion block">
                            <div class="acc-btn"><div class="icon-outer"><span class="icon icon-plus fas fa-plus"></span> <span class="icon icon-minus fas fa-minus"></span></div>{{ cmsValue('faq', 'content', 'question_3', 'At the end of the day, going forward?') }}</div>
                            <div class="acc-content">
                                <div class="content">
                                    <div class="text">{{ cmsValue('faq', 'content', 'answer_3', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.') }}</div>
                                </div>
                            </div>
                        </li>

                        <!--Block-->
                        <li class="accordion block">
                            <div class="acc-btn"><div class="icon-outer"><span class="icon icon-plus fas fa-plus"></span> <span class="icon icon-minus fas fa-minus"></span></div>{{ cmsValue('faq', 'content', 'question_4', 'Information highway will close the loop on?') }}</div>
                            <div class="acc-content">
                                <div class="content">
                                    <div class="text">{{ cmsValue('faq', 'content', 'answer_4', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.') }}</div>
                                </div>
                            </div>
                        </li>

                    </ul>

                </div>

                <div class="column col-lg-6 col-md-12 col-sm-12">

                    <!--Accordian Box-->
                    <ul class="accordion-box faq">

                        <!--Block-->
                        <li class="accordion block">
                            <div class="acc-btn"><div class="icon-outer"><span class="icon icon-plus fas fa-plus"></span> <span class="icon icon-minus fas fa-minus"></span></div>{{ cmsValue('faq', 'content', 'question_5', 'User generated content in real-time will have?') }}</div>
                            <div class="acc-content">
                                <div class="content">
                                    <div class="text">{{ cmsValue('faq', 'content', 'answer_5', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.') }}</div>
                                </div>
                            </div>
                        </li>

                        <!--Block-->
                        <li class="accordion block">
                            <div class="acc-btn"><div class="icon-outer"><span class="icon icon-plus fas fa-plus"></span> <span class="icon icon-minus fas fa-minus"></span></div>{{ cmsValue('faq', 'content', 'question_6', 'Normal that has evolved from generation on the runway heading towards?') }}</div>
                            <div class="acc-content">
                                <div class="content">
                                    <div class="text">{{ cmsValue('faq', 'content', 'answer_6', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.') }}</div>
                                </div>
                            </div>
                        </li>

                        <!--Block-->
                        <li class="accordion block">
                            <div class="acc-btn"><div class="icon-outer"><span class="icon icon-plus fas fa-plus"></span> <span class="icon icon-minus fas fa-minus"></span></div>{{ cmsValue('faq', 'content', 'question_7', 'Focusing solely on the bottom line?') }}</div>
                            <div class="acc-content">
                                <div class="content">
                                    <div class="text">{{ cmsValue('faq', 'content', 'answer_7', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.') }}</div>
                                </div>
                            </div>
                        </li>

                        <!--Block-->
                        <li class="accordion block">
                            <div class="acc-btn"><div class="icon-outer"><span class="icon icon-plus fas fa-plus"></span> <span class="icon icon-minus fas fa-minus"></span></div>{{ cmsValue('faq', 'content', 'question_8', 'Multiple touchpoints for offshoring?') }}</div>
                            <div class="acc-content">
                                <div class="content">
                                    <div class="text">{{ cmsValue('faq', 'content', 'answer_8', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.') }}</div>
                                </div>
                            </div>
                        </li>

                    </ul>

                </div>
            </div>

        </div>
    </section>
    <!--End Faq Section-->

    <!--Faq Form Section-->
    <section class="faq-form-section">
        <div class="auto-container">
        	<h2>{{ cmsValue('faq', 'content', 'submit_headline', 'Didn\'t find your answer? Submit your question') }}</h2>
            <!--Form Outer-->
            <div class="form-outer">
                <!--Faq Form-->
                <div class="faq-form-box">
                    <!--Contact Form-->
                    <form method="post" action="contact.html">

                        <div class="form-group">
                            <input type="email" name="email" value="" placeholder="Email Address*" required>
                        </div>

                        <div class="form-group">
                            <input type="text" name="subject" value="" placeholder="Subject*" required>
                        </div>

                        <div class="form-group">
                            <textarea name="message" placeholder="Your Question*"></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="theme-btn btn-style-one"><span class="txt">{{ cmsValue('faq', 'content', 'submit_button_text', 'Submit now') }}</span></button>
                        </div>

                    </form>
                </div>
                <!--End Faq Form-->
            </div>

        </div>
    </section>

      <!--Main Footer-->
        <footer class="main-footer">
    		<div class="auto-container">
            	<!--Widgets Section-->
                <div class="widgets-section">
                	<div class="row clearfix">

                        <!--big column-->
                        <div class="big-column col-lg-6 col-md-12 col-sm-12">
                            <div class="row clearfix">

                                <!--Footer Column-->
                                <div class="footer-column col-lg-7 col-md-6 col-sm-12">
                                    <div class="footer-widget logo-widget">
    									                  <div class="logo">
                                        	<a href="/"><img :src="cmsValue('home', 'footer', 'logo_url', '/frontend/images/footer-logo.png')" alt="" /></a>
                                        </div>
                                        <div class="text">{{ footerValue('tagline', 'Suporting Your Independence.') }}</div>
                                        <ul class="social-icons">
                                            <li><a href="#"><span class="fab fa-facebook-f"></span></a></li>
                                            <li><a href="#"><span class="fab fa-linkedin-in"></span></a></li>
                                            <li><a href="#"><span class="fab fa-twitter"></span></a></li>
                                            <!--li><a href="#"><span class="fab fa-google-plus-g"></span></a></li-->
                                        </ul>
    								                </div>
    							              </div>

    							<!--Footer Column-->
                                <div class="footer-column col-lg-5 col-md-6 col-sm-12">
                                  <div class="footer-widget links-widget">
                                    	<h2>{{ cmsValue('home', 'footer', 'quick_links_heading', 'Quick links') }}</h2>
											<div class="widget-content">
												<ul class="list">
												<li><a href="/">Home</a></li>
												<li><router-link to="/about">About Us</router-link></li>
												<li><router-link to="/elderlyservice">Our Services</router-link></li>
												<li><router-link to="/contact">Contact Us</router-link></li>
												</ul>
											</div>
    								</div>
    							</div>

    						</div>
    					</div>

    					<!--big column-->
                        <div class="big-column col-lg-6 col-md-12 col-sm-12">
                            <div class="row clearfix">

                                <!--Footer Column-->
                                <div class="footer-column col-lg-5 col-md-6 col-sm-12">
                                    <div class="footer-widget contact-widget">
    									<h2>{{ footerValue('contact_heading', 'Contact Info') }}</h2>
    									<div class="widget-content">
    										<a :href="footerPhoneHref()" class="contact-number">{{ footerValue('contact_phone', '024 7623 1188') }}</a>
    										<ul>
    											<li v-html="footerValue('contact_address', 'Office GE13, 101 Lockhurst Lane,<br> Coventry, CV6 5sf')"></li>
    											<li>{{ footerValue('contact_email_label', 'Email') }} :<a :href="footerMailtoHref()"> {{ footerValue('contact_email', 'info@facilitatecareservices.co.uk') }}</a></li>
    										</ul>
    									</div>
    								</div>
    							</div>
								<!--Footer Column-->
                                <div class="footer-column col-lg-7 col-md-6 col-sm-12">
                                    <div class="footer-widget newsletter-widget">
                                    	<h2>{{ footerValue('cqc_heading', 'Care Quality Commission') }}</h2>
										<div class="logo">
											<a :href="footerCqcHref()" target="_blank" rel="noopener"><img :src="footerValue('cqc_badge_image_url', '/frontend/images/CQC rating.jpg')" alt="CQC rating" /></a>
										</div>
    									<div class="text">{{ footerValue('cqc_text', 'Latest Inspection 25 May 2021') }}</div>
										<div v-if="footerValue('cqc_secondary_text', '')" class="text">{{ footerValue('cqc_secondary_text', '') }}</div>
    									<!-- Newsletter Form -->
    									<div class="newsletter-form">
											<div class="btn-box">
												<a :href="footerCqcHref()" class="theme-btn btn-style-one" target="_blank" rel="noopener"><span class="txt">{{ footerValue('cqc_button_text', 'See Report') }}</span></a>
											</div>
    									</div>
    								</div>
    							</div>

    						</div>
    					</div>
    				</div>
    			</div>

    			<!--Footer Bottom-->
                <div class="footer-bottom clearfix">
                    <div class="pull-left">
                        <div class="copyright">{{ cmsValue('home', 'footer', 'copyright_text', '© Copyright Facilitate care services 2024. All right reserved.') }}</div>
                    </div>
                </div>

    		</div>
    	</footer>

</div>
<!--End pagewrapper-->

<!--Scroll to top-->
<div class="scroll-to-top scroll-to-target" data-target="html"><span class="fa fa-angle-up"></span></div>

</template>
<script>
export default {
    
}
</script>
