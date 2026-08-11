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
                                          <li><router-link to="/specialcare">Special Needs Care</router-link></li>
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
    <section class="page-title" :style="{ backgroundImage: `url(${cmsValue('team', 'hero', 'background_image_url', '/frontend/images/background/3.jpg')})` }">
    	<div class="auto-container">
        	<h2>{{ cmsValue('team', 'hero', 'headline', 'Our Team') }}</h2>
            <ul class="page-breadcrumb">
            	<li><a href="/">home</a></li>
                <li>{{ cmsValue('team', 'hero', 'headline', 'Our Team') }}</li>
            </ul>
        </div>
    </section>
    <!--End Page Title-->

	<!-- Team Section -->
	<section class="team-section team-page-section">
		<div class="auto-container">

			<!-- Sec Title -->
			<div class="sec-title style-two">
				<h2>{{ cmsValue('team', 'content', 'headline', 'Our CareGivers') }}</h2>
				<div class="text">{{ cmsValue('team', 'content', 'subheadline', 'Our caregivers are trained specifically to provide in-home care.') }}</div>
			</div>

			<div class="clearfix">

				<!-- Team Block -->
				<div class="team-block col-lg-3 col-md-6 col-sm-12">
					<div class="inner-box wow fadeInUp" data-wow-delay="0ms" data-wow-duration="1500ms">
						<div class="image">
							<img :src="cmsValue('team', 'content', 'member_1_image_url', '/frontend/images/resource/team-1.jpg')" alt="" />
							<div class="overlay-box">
								<ul class="social-icons">
									<li><a href="#"><i class="fab fa-facebook"></i></a></li>
									<li><a href="#"><i class="fab fa-linkedin"></i></a></li>
									<li><a href="#"><i class="fab fa-twitter-square"></i></a></li>
									<li><a href="#"><i class="fab fa-skype"></i></a></li>
								</ul>
							</div>
						</div>
						<div class="lower-content">
							<h3><router-link to="/team">{{ cmsValue('team', 'content', 'member_1_name', 'Merry Desulva') }}</router-link></h3>
							<div class="designation">{{ cmsValue('team', 'content', 'member_1_role', 'Caregiver for Elders') }}</div>
						</div>
					</div>
				</div>

				<!-- Team Block -->
				<div class="team-block col-lg-3 col-md-6 col-sm-12">
					<div class="inner-box wow fadeInUp" data-wow-delay="300ms" data-wow-duration="1500ms">
						<div class="image">
							<img :src="cmsValue('team', 'content', 'member_2_image_url', '/frontend/images/resource/team-2.jpg')" alt="" />
							<div class="overlay-box">
								<ul class="social-icons">
									<li><a href="#"><i class="fab fa-facebook"></i></a></li>
									<li><a href="#"><i class="fab fa-linkedin"></i></a></li>
									<li><a href="#"><i class="fab fa-twitter-square"></i></a></li>
									<li><a href="#"><i class="fab fa-skype"></i></a></li>
								</ul>
							</div>
						</div>
						<div class="lower-content">
							<h3><router-link to="/team">{{ cmsValue('team', 'content', 'member_2_name', 'Roseen') }}</router-link></h3>
							<div class="designation">{{ cmsValue('team', 'content', 'member_2_role', 'Take care of Nursing') }}</div>
						</div>
					</div>
				</div>

				<!-- Team Block -->
				<div class="team-block col-lg-3 col-md-6 col-sm-12">
					<div class="inner-box wow fadeInUp" data-wow-delay="600ms" data-wow-duration="1500ms">
						<div class="image">
							<img :src="cmsValue('team', 'content', 'member_3_image_url', '/frontend/images/resource/team-3.jpg')" alt="" />
							<div class="overlay-box">
								<ul class="social-icons">
									<li><a href="#"><i class="fab fa-facebook"></i></a></li>
									<li><a href="#"><i class="fab fa-linkedin"></i></a></li>
									<li><a href="#"><i class="fab fa-twitter-square"></i></a></li>
									<li><a href="#"><i class="fab fa-skype"></i></a></li>
								</ul>
							</div>
						</div>
						<div class="lower-content">
							<h3><router-link to="/team">{{ cmsValue('team', 'content', 'member_1_name', 'Merry Desulva') }}</router-link></h3>
							<div class="designation">{{ cmsValue('team', 'content', 'member_1_role', 'Caregiver for Elders') }}</div>
						</div>
					</div>
				</div>

				<!-- Team Block -->
				<div class="team-block col-lg-3 col-md-6 col-sm-12">
					<div class="inner-box wow fadeInUp" data-wow-delay="900ms" data-wow-duration="1500ms">
						<div class="image">
							<img :src="cmsValue('team', 'content', 'member_4_image_url', '/frontend/images/resource/team-4.jpg')" alt="" />
							<div class="overlay-box">
								<ul class="social-icons">
									<li><a href="#"><i class="fab fa-facebook"></i></a></li>
									<li><a href="#"><i class="fab fa-linkedin"></i></a></li>
									<li><a href="#"><i class="fab fa-twitter-square"></i></a></li>
									<li><a href="#"><i class="fab fa-skype"></i></a></li>
								</ul>
							</div>
						</div>
						<div class="lower-content">
							<h3><router-link to="/team">{{ cmsValue('team', 'content', 'member_2_name', 'Roseen') }}</router-link></h3>
							<div class="designation">{{ cmsValue('team', 'content', 'member_2_role', 'Take care of Nursing') }}</div>
						</div>
					</div>
				</div>

				<!-- Team Block -->
				<div class="team-block col-lg-3 col-md-6 col-sm-12">
					<div class="inner-box wow fadeInUp" data-wow-delay="0ms" data-wow-duration="1500ms">
						<div class="image">
							<img :src="cmsValue('team', 'content', 'member_1_image_url', '/frontend/images/resource/team-1.jpg')" alt="" />
							<div class="overlay-box">
								<ul class="social-icons">
									<li><a href="#"><i class="fab fa-facebook"></i></a></li>
									<li><a href="#"><i class="fab fa-linkedin"></i></a></li>
									<li><a href="#"><i class="fab fa-twitter-square"></i></a></li>
									<li><a href="#"><i class="fab fa-skype"></i></a></li>
								</ul>
							</div>
						</div>
						<div class="lower-content">
							<h3><router-link to="/team">{{ cmsValue('team', 'content', 'member_1_name', 'Merry Desulva') }}</router-link></h3>
							<div class="designation">{{ cmsValue('team', 'content', 'member_1_role', 'Caregiver for Elders') }}</div>
						</div>
					</div>
				</div>

				<!-- Team Block -->
				<div class="team-block col-lg-3 col-md-6 col-sm-12">
					<div class="inner-box wow fadeInUp" data-wow-delay="300ms" data-wow-duration="1500ms">
						<div class="image">
							<img :src="cmsValue('team', 'content', 'member_2_image_url', '/frontend/images/resource/team-2.jpg')" alt="" />
							<div class="overlay-box">
								<ul class="social-icons">
									<li><a href="#"><i class="fab fa-facebook"></i></a></li>
									<li><a href="#"><i class="fab fa-linkedin"></i></a></li>
									<li><a href="#"><i class="fab fa-twitter-square"></i></a></li>
									<li><a href="#"><i class="fab fa-skype"></i></a></li>
								</ul>
							</div>
						</div>
						<div class="lower-content">
							<h3><router-link to="/team">{{ cmsValue('team', 'content', 'member_2_name', 'Roseen') }}</router-link></h3>
							<div class="designation">{{ cmsValue('team', 'content', 'member_2_role', 'Take care of Nursing') }}</div>
						</div>
					</div>
				</div>

				<!-- Team Block -->
				<div class="team-block col-lg-3 col-md-6 col-sm-12">
					<div class="inner-box wow fadeInUp" data-wow-delay="600ms" data-wow-duration="1500ms">
						<div class="image">
							<img :src="cmsValue('team', 'content', 'member_3_image_url', '/frontend/images/resource/team-3.jpg')" alt="" />
							<div class="overlay-box">
								<ul class="social-icons">
									<li><a href="#"><i class="fab fa-facebook"></i></a></li>
									<li><a href="#"><i class="fab fa-linkedin"></i></a></li>
									<li><a href="#"><i class="fab fa-twitter-square"></i></a></li>
									<li><a href="#"><i class="fab fa-skype"></i></a></li>
								</ul>
							</div>
						</div>
						<div class="lower-content">
							<h3><router-link to="/team">{{ cmsValue('team', 'content', 'member_1_name', 'Merry Desulva') }}</router-link></h3>
							<div class="designation">{{ cmsValue('team', 'content', 'member_1_role', 'Caregiver for Elders') }}</div>
						</div>
					</div>
				</div>

			</div>

		</div>
	</section>
	<!-- End Team Section -->

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
