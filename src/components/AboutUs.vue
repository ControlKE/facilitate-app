<template>
    <div class="page-wrapper">
    <!-- Preloader -->
    <!-- <div class="preloader"></div> -->

    <header class="main-header">
        <!--Header Top-->
        <div class="header-top">
            <div class="auto-container clearfix">
                <div class="top-left clearfix">
                    <div class="text"><span class="icon flaticon-phone-receiver"></span> Need help? Call Us Now : <a href="tel: 024 7623 1188" class="number">024 7623 1188</a></div>

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
                        <div class="logo"><a href="/"><img src="/frontend/images/logo.png" alt="" title=""></a></div>
                    </div>

                    <!--Nav Box-->
                    <div class="nav-outer clearfix">
                        <!--Mobile Navigation Toggler For Mobile-->
						<div class="mobile-nav-toggler"><span class="icon flaticon-menu-button"></span></div>

                        <nav class="main-menu navbar-expand-md navbar-light">
                            <div class="navbar-header">
                                <!-- Toggle Button -->
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
											<!-- <li><router-link to="/team">Our Team</router-link></li> -->
											<!-- <li><router-link to="/faq">Faq's</router-link></li> -->
                                            <li><router-link to="/testimonial">Testimonial</router-link></li>
                                        </ul>
                                    </li>
									<li class="dropdown"><a href="#">Our Services</a>
                                      <ul>
										  <li><router-link to="/personalcare">Personal Care</router-link></li>
                                          <li><router-link to="/elderlyservice">Elderly Care Service</router-link></li>
                                          <li><router-link to="/respitecare">Respite Care</router-link></li>
                                          <li><router-link to="/livein">Live In Care</router-link></li>
                                          <li><router-link to="/discharge">Hospital Discharge</router-link></li>
                                          <li><router-link to="/care">Social Care</router-link></li>
                                          <li><router-link to="/chronical">Palliative Care</router-link></li>
                                          <li><router-link to="/specialcare">Special Needs Care</router-link></li>
										  <li><router-link to="/support">Supported Living</router-link></li>
                                      </ul>
                                    </li>
									<li><router-link to="/started">Getting Started</router-link></li>
									<li><router-link to="/caregiver">Careers</router-link></li>
                                        <!-- <ul> -->
                                            <!-- <li><router-link to="/blog">Company News</router-link></li>
                                            <li><router-link to="/gallery">Our Gallery</router-link></li> -->
                                            <!-- <li><router-link to="/started">Getting Started</router-link></li> -->
											<!-- <li><router-link to="/caregiver">Careers</router-link></li> -->
                                        <!-- </ul> -->
                                    <!-- </li> -->
									<li class="dropdown"><a href="#">Contact Us</a>
                                        <ul>
                                            <li><router-link to="/contact">General Enquiries</router-link></li>
											<li><a href="#" @click.prevent="openCaregiverDialog">Thank a Caregiver</a></li>
											<li><a href="#" @click.prevent="openComplaintDialog">Raise a Concern</a></li>
                                        </ul>
                                    </li>
																		
									<div class="pa-0 text-center">
									<v-dialog v-model="complaintDialog" max-width="600px">
										<!-- <template v-slot:activator="{ props: activatorProps }">
											<div class="theme-btn btn-style-one" v-bind="activatorProps"><span class="txt">File A Complaint</span></div>
										</template> -->

										<v-card max-width="600px" >
											<v-toolbar color="#AB207D" title="Raise a Concern"></v-toolbar>
											<v-card-text>
												<v-combobox v-model="complaintTitle" label="Title*"
												:items="['Mr.', 'Mrs.', 'Ms.', 'Miss', 'Dr.', 'Prof.']"
												variant="outlined"></v-combobox>
												<v-row dense>
													<v-col cols="12" md="6" sm="4">
														<v-text-field v-model="complaintFName" label="First Name*" required class="uppercase-input"></v-text-field>
													</v-col>
													<v-col cols="12" md="6" sm="4">
														<v-text-field v-model="complaintSName" label="Last Name*" required class="uppercase-input"></v-text-field>
													</v-col>
												</v-row>
												
												<v-text-field v-model="complaintEmail" label="Email" ></v-text-field>
												<v-text-field v-model="complaintPhone" label="PhoneNumber*" required></v-text-field>
												<v-textarea v-model="complaintMsg" clear-icon="mdi-close-circle" label="Message*" clearable ></v-textarea>
												<small class="text-caption text-medium-emphasis">*indicates required field</small>
											</v-card-text>

											<v-divider></v-divider>

											<v-card-actions>
												<v-spacer></v-spacer>
												<v-btn color="primary" text="CANCEL"  @click="complaintDialog = false"></v-btn>
												<v-btn color="success" text="SEND"  @click="complaintSave()"></v-btn>
											</v-card-actions>
										</v-card>
									</v-dialog>
								</div>
								<div class="pa-0 text-center">
									<v-dialog v-model="caregiverDialog" max-width="600px">
										<!-- <template v-slot:activator="{ props: activatorProps }">
											<div class="theme-btn btn-style-one" v-bind="activatorProps"><span class="txt">File A Complaint</span></div>
										</template> -->

										<v-card max-width="600px" >
											<v-toolbar color="#AB207D" title="Thank a Caregiver"></v-toolbar>
											<v-card-text>
												<v-combobox v-model="titlecb" label="Your Title*"
												:items="['Mr.', 'Mrs.', 'Ms.', 'Miss', 'Dr.', 'Prof.']"
												variant="outlined"></v-combobox>
												<v-row dense>
													<v-col cols="12" md="6" sm="4">
														<v-text-field v-model="FNametc" label="Your First Name*" required class="uppercase-input"></v-text-field>
													</v-col>
													<v-col cols="12" md="6" sm="4">
														<v-text-field v-model="SNametc" label="Your Last Name*" required class="uppercase-input"></v-text-field>
													</v-col>
												</v-row>
												
												<v-text-field  v-model="mailtc" label="Your Email" ></v-text-field>
												<v-text-field  v-model="phonetc" label="Your PhoneNumber*" required></v-text-field>
												<v-text-field v-model="cnametc" label="Carer Names*" required></v-text-field>
												<v-textarea v-model="messagetc" clear-icon="mdi-close-circle" label="Message*" clearable ></v-textarea>
												<small class="text-caption text-medium-emphasis">*indicates required field</small>
											</v-card-text>

											<v-divider></v-divider>

											<v-card-actions>
												<v-spacer></v-spacer>
												<v-btn color="primary" text="CANCEL"  @click="caregiverDialog = false"></v-btn>
												<v-btn color="success" text="SEND"  @click="thanksSave()"></v-btn>
											</v-card-actions>
										</v-card>
									</v-dialog>
								</div>
                                </ul>
                            </div>
                        </nav>
                        <!-- Main Menu End-->

                        <!-- Main Menu End-->
                        <div class="outer-box clearfix">
							
                            <div class="btn-box">
                                <router-link to="/contact" class="theme-btn btn-style-one"><span class="txt">Ask A Question</span></router-link>
								<v-dialog v-model="logindialog" persistent max-width="600px">
									<template v-slot:activator="{ props }">
										<v-btn icon="mdi-account" size="small" v-bind="props" color="#AB207D"></v-btn>
										<!-- <a class="btn outline radius-no white" v-bind="props">Login</a> -->
									</template>
									<v-card>
										
										<v-toolbar color="#AB207D" title="Staff Login"></v-toolbar>
										<v-card-text>
											<v-container>
												<v-row>
													<v-col cols="12" >
														<v-text-field v-model="email" hide-details="auto" label="Email address" placeholder="johndoe@gmail.com" type="email" required></v-text-field>
													</v-col>
													<v-col cols="12" >
														<v-text-field
															v-model="password"
															label="Password*"
															:type="showPassword ? 'text' : 'password'"
															:append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
															@click:append-inner="togglePasswordVisibility"
															required
														></v-text-field>
													</v-col>
												</v-row>
											</v-container>
										</v-card-text>
										<v-card-actions>
										<v-spacer></v-spacer>
										<v-btn color="primary" variant="text" @click="openForgotPassword">Forgot password?</v-btn>
										<v-btn outlined color="blue-darken-1" variant="text" @click="Login">Login</v-btn>
										<v-btn color="black" variant="text" @click="logindialog = false">Close</v-btn>
										</v-card-actions>
									</v-card>
								</v-dialog>
								
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
            	<div class="nav-logo"><a href="/"><img src="/frontend/images/logo.png" alt="" title=""></a></div>
								<ul class="navigation clearfix"></ul>
            </nav>
        </div> 
		<!-- End Mobile Menu-->

    </header>
    <!-- End Main Header -->

	<!--Page Title-->
    <section class="page-title" style="background-image:url(/frontend/images/background/3.jpg)">
    	<div class="auto-container">
        	<h2>About us</h2>
            <ul class="page-breadcrumb">
            	<li><a href="/">home</a></li>
                <li>About us</li>
            </ul>
        </div>
    </section>
    <!--End Page Title-->

	<!-- Mission Section -->
    <section class="mission-section style-two">
		<div class="auto-container">
			<!-- Title Box -->
			<div class="title-box">
				<h2>Personalised Care Plans for your Needs</h2>
				<div class="bold-text"></div>
				<div class="text">At Facilitate Care Services we aim to provide people with the highest standards of care that is individually designed to meet each individual’s needs and preferences.
          Our care service is planned with the active involvement of our service users and their relatives, friends or medical professionals where appropriate.</div>
			</div>
			<div class="row clearfix">

				<!-- Image Column -->
				<div class="image-column col-lg-3 col-md-4 col-sm-12">
					<div class="inner-column">
						<div class="image">
							<img src="/frontend/images/resource/mission.jpg" alt="" />
						</div>
					</div>
				</div>

				<!-- Content Column -->
				<div class="content-column col-lg-9 col-md-8 col-sm-12">
					<div class="inner-column">
						<h2>Our Mission</h2>
						<!-- <div class="bold-text">Everything we do at Facilitate Care Services is driven by our universal mission: To empower individuals to live with dignity and independence in the comfort of their own homes.</div> -->
						<div class="bold-text">Our primary aim is to promote the independence and dignity of our service users. We focus on developing personalised care plans tailored to the unique needs and interests of each client. 
							We offer person-centred care packages, ranging from 30-minute 'pop-in' services to 24-hour care in the comfort of your own home.</div>
						<!-- <h3>Changing the Face of Aging</h3>
						<ul class="mission-list">
							<li>The old face of aging was the fear of getting old.</li>
							<li>The new face is hope and promise.​</li>
						</ul>
						<ul class="mission-list">
							<li>The old face of aging was the fear of getting old.</li>
							<li>The new face is hope and promise.​</li>
						</ul> -->
					</div>
				</div>

			</div>
		</div>
	</section>
	<!-- End Mission Section -->

	<!-- Call To Action Section -->
	<section class="call-to-action-section" style="background-image: url(/frontend/images/background/1.png);">
		<div class="auto-container">
			<h2>Share Your Cares. Inspire Others.</h2>
			<div class="text">If you or your loved one is in need of caregiving services, we encourage you to reach out for a complimentary evaluation so we can customise an appropriate plan of care.</div>
			<router-link to="/contact" class="theme-btn btn-style-two"><span class="txt">contact us</span></router-link>
		</div>
	</section>
	<!-- End Call To Action Section -->

	<!-- Healthcare Section -->
	<section class="healthcare-section">
		<div class="auto-container">
			<div class="row clearfix">

				<!-- Content Column -->
				<div class="content-column col-lg-8 col-md-12 col-sm-12">
					<div class="inner-column">
						<h2>Why choose us.</h2>
            <ul class="list-style-four">
              <li>Our care at home services are delivered by a workforce of qualified and experienced healthcare professionals including Registered Nurses, Healthcare Assistants and Support Workers.</li>
			  <li>Registered with the Care Quality Commission, we pride ourselves on giving a truly helpful service that will enhance your quality of life.</li>
              <li>We treat all our clients as individuals with unique needs and interests and tailor care packages to suit their individual requirements.</li>
			  <li>We regard it as a special privilege to look after people with care and compassion.</li>
              <li>Our dedicated care management team understands that circumstances change and therefore communicate regularly with clients and their families to adapt care packages to changing needs.</li>
              <li>We believe that companionship and maintaining social networks is important to many people. That’s why we also provide companionship services, taking our clients out for outings and appointments, if requested.</li>
              <li>We are determined that all clients and their families get the support they need regardless of religion, ethnicity, social background, sexual orientation, age, gender or disability.</li>
              <li>We understand the importance of an active mind in a healthy lifestyle and aim to keep our
                 clients active, healthy and independent by maintaining the quality of life they are used to. We work with our clients to maintain their flexibility, balance and mobility by planning physical
                 activities to help keep them active. We also provide our clients with a range of activities to inspire and invigorate their minds.</li>
            </ul>
						<div class="text">
						<!--div class="bold-text">If you or your loved one is in need of caregiving services, we encourage you to reach out for a complimentary evaluation so we can customize an appropriate plan of care. <a href="contact.html">Contact us</a> for more information.</div-->
					</div>
         <!--div class="bold-text">If you or your loved one is in need of caregiving services, we encourage you to reach out for a complimentary evaluation so we can customize an appropriate plan of care. <a href="contact.html">Contact us</a> for more information.</div-->
        </div>
      </div>

				<!-- Image Column -->
				<div class="image-column col-lg-4 col-md-12 col-sm-12">
					<div class="inner-column">
						<div class="image wow fadeInDown" data-wow-delay="0ms" data-wow-duration="1500ms">
							<img src="/frontend/images/resource/care-1.jpg" alt="" />
						</div>
						<div class="row clearfix">
							<div class="column col-lg-6 col-md-6 col-sm-12">
								<div class="image image-2 wow fadeInLeft" data-wow-delay="0ms" data-wow-duration="1500ms">
									<img src="/frontend/images/resource/care-2.jpg" alt="" />
								</div>
							</div>
							<div class="column col-lg-6 col-md-6 col-sm-12">
								<div class="image image-3 wow fadeInRight" data-wow-delay="0ms" data-wow-duration="1500ms">
									<img src="/frontend/images/resource/care-3.jpg" alt="" />
								</div>
							</div>
						</div>
            <div class="image wow fadeInDown" data-wow-delay="0ms" data-wow-duration="1500ms">
							<img src="/frontend/images/resource/care-4.jpg" alt="" />
						</div>
					</div>
				</div>
       </div>
       <div class="text">﻿Many people struggle to understand the options available to them when a family member needs a special level of care. Families are faced with having to make big decisions on short notice and quickly find that deciding on what’s best is not often clear or simple.
<br><br> FCS can help clarify what options are available for the specific needs of those who need domiciliary care. We believe each individual’s needs are unique and we strive to provide the professional and friendly approach to assist with you care options.</div>
			</div>

	</section>
	<!-- End Healthcare Section -->

	<!-- Testimonial Section -->
	<section class="testimonial-section">
		<div class="auto-container">
			<!-- Sec Title Two -->
			<!-- <div class="sec-title-two light centered">
				<h2>What Our Clients Says</h2>
			</div> -->



		</div>
	</section>

	<!-- Team Section -->
	<section class="team-section style-two">
		<div class="auto-container">

			<!-- Sec Title -->
			<div class="sec-title style-two">
				<h2>Our Management Team</h2>
				<div class="text">Facilitate Care is managed by its founders, Rose & Lucy who are  qualified and registered nurses with over 20 years experience in the NHS and private health care provision <br><br>At Facilitate Care Services, we believe that the heart of great care begins with great leadership. Our dedicated management team is the driving force behind our compassionate, 
							client-focused home care services. With a diverse background in healthcare, management, and client support, our leaders bring together a wealth of expertise to ensure the highest standard of care for every individual we serve.
							<br><br>Our team is committed to fostering a culture of excellence, innovation, and empathy. From the meticulous selection of our care professionals to the personalised design of our care plans, they oversee every aspect of our operations with a keen eye for detail and a deep understanding of the needs of those in our care.
							<br><br>Get to know the passionate individuals who guide our mission, uphold our values, and tirelessly work to make a difference in the lives of our clients and their families. We are proud to introduce the pillars of Facilitate Care Services, who stand united in their goal to deliver outstanding domiciliary care with dignity, respect, and warmth.</div>
			</div>

			<div class="row clearfix">

				<!-- Team Block -->
				<!-- <div class="team-block col-lg-3 col-md-6 col-sm-12">
					<div class="inner-box">
						<div class="image">
							<img src="/frontend/images/resource/team-5.jpg" alt="" />
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
							<h3><router-link to="/team">Rose</router-link></h3>
							<div class="designation">Founder & Managing Director</div>
						</div>
					</div>
				</div> -->

				<!-- Team Block -->
				<!-- <div class="team-block col-lg-3 col-md-6 col-sm-12">
					<div class="inner-box">
						<div class="image">
							<img src="/frontend/images/resource/team-5.jpg" alt="" />
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
							<h3><router-link to="/team">Lucy</router-link></h3>
							<div class="designation">Co-Founder & Managing Director</div>
						</div>
					</div>
				</div> -->
				<!-- Team Block -->
				<!-- <div class="team-block col-lg-3 col-md-6 col-sm-12">
					<div class="inner-box">
						<div class="image">
							<img src="/frontend/images/resource/team-4.jpg" alt="" />
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
							<h3><router-link to="/team">Francis</router-link></h3>
							<div class="designation">Co-Founder & Managing Director</div>
						</div>
					</div>
				</div> -->
			</div>
		</div>
	</section>
	<!-- End Team Section -->

      <!--Main Footer-->
      <!--Main Footer-->
	<footer class="main-footer">
    		<div class="auto-container">
            	<!--Widgets Section-->
                <div class="widgets-section">
                	<div class="row clearfix">

                        <!--big column-->
                        <!-- <div class="big-column col-lg-6 col-md-12 col-sm-12">
                            <div class="row clearfix"> -->

                                <!--Footer Column-->
                                <div class="footer-column col-lg-4 col-md-4 col-sm-12">
                                    <div class="footer-widget logo-widget">
    									<div class="logo">
											<v-avatar class="company-avatar" size="150" >
												<a href="/"><img src="/frontend/images/logo.png" alt="" /></a>
											</v-avatar>
                                        	<a href="/"><img :src="footerValue('logo_url', '/frontend/images/footer-logo.png')" alt="" /></a>
                                        </div>
                                        <div class="text">{{ footerValue('tagline', 'Suporting Your Independence.') }}</div>
                                        <ul class="social-icons">
                                            <li><a href="https://www.facebook.com/p/Facilitate-Care-Services-100070370806760/" target="_blank"><span class="fab fa-facebook-f"></span></a></li>
                                            <li><a href="#"><span class="fab fa-linkedin-in"></span></a></li>
                                            <li><a href="#"><span class="fab fa-twitter"></span></a></li>
                                            <!--li><a href="#"><span class="fab fa-google-plus-g"></span></a></li-->
                                        </ul>
    								</div>
    							</div>

    							<!--Footer Column-->
                                <div class="footer-column col-lg-4 col-md-4 col-sm-12 ">
                                  <div class="footer-widget links-widget">
                                    	<h2>{{ footerValue('quick_links_heading', 'Quick links') }}</h2>
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
    						<!-- </div>
    					</div> -->


						<!--Footer Column-->
						<div class="footer-column col-lg-4 col-md-4 col-sm-12">
							<div class="footer-widget newsletter-widget">
								<h2>{{ footerValue('cqc_heading', 'Care Quality Commission') }}</h2>
								<div class="logo">
									<a :href="footerCqcHref()" target="_blank" rel="noopener"><img :src="footerValue('cqc_badge_image_url', '/frontend/images/CQC rating.jpg')" alt="" /></a>
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

    			<!--Footer Bottom-->
                <div class="footer-bottom clearfix">
                    <div class="pull-left">
                        <div class="copyright">{{ footerValue('copyright_text', '© Copyright Facilitate care services 2024. All right reserved.') }}</div>
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
import publicInquiryDialogsMixin from '../mixins/publicInquiryDialogs';
import { describeAuthApiError, loginUser } from '../services/authApi';
import { firstAllowedRouteName } from '../utils/accessControl';

export default {
	mixins: [publicInquiryDialogsMixin],
	data() {
		return {
			email: null,
      		password: null,
			showPassword: false,
			snackbar: false,
			snackbarText: '',
			snackColor: '',
			error: "",
			dialog: false,
			complaintDialog: false,
			caregiverDialog: false,
			logindialog: false
		}
	},
	methods: {
		openComplaintDialog() {
            this.complaintDialog = true;
        },

		openCaregiverDialog() {
            this.caregiverDialog = true;
        },

		togglePasswordVisibility() {
			this.showPassword = !this.showPassword;
		},

		openForgotPassword() {
			this.logindialog = false;
			this.$router.push({ path: '/login', query: { mode: 'forgot' } });
		},

		showLoginMessage(type, message) {
			const normalizedType = type === 'success' ? 'success' : 'error';
			if (this.$store && typeof this.$store.commit === 'function') {
				this.$store.commit('showSnackbar', {
					message,
					type: normalizedType,
				});
			}
			this.snackColor = normalizedType;
			this.snackbarText = message;
			this.snackbar = true;
		},

		async Login() {
			try {
				const result = await loginUser({
					identifier: this.email,
					password: this.password,
				});
				const landingRoute = firstAllowedRouteName(result.user);
				this.showLoginMessage('success', result.message || 'Login successful.');
				this.logindialog = false;
				if (landingRoute) {
					this.$router.push({ name: landingRoute });
				} else {
					this.$router.push('/dashboard');
				}
			} catch (error) {
				const message = describeAuthApiError(error, 'An error occurred. Please try again.');
				this.showLoginMessage('error', message);
				this.error = message;
			}
		}
	}
	 
};
</script>
<style scoped>
.btn-box {
	display: flex;
	justify-content: space-around;
	align-items: center;
}

.btn-box .theme-btn {
	margin: 0 20px;
}

.company-avatar img {
  border-radius: 50%;
}

.avatar-carousel .v-carousel-item {
  display: flex;
  justify-content: center;
  align-items: center; /* Vertically align avatars in the center */
}

.company-avatar {
  cursor: pointer;
  background-color: white;
  border-radius: 50%;
  border: 2px solid #ddd; /* Optional border */
  padding: 10px; /* Adjust padding for better appearance */
  transition: transform 0.3s ease;
}

.company-avatar:hover {
  transform: scale(1.1);
}

.v-dialog {
  text-align: center;
}
</style>
