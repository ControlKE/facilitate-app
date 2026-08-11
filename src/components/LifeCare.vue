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
                                <router-link to="/contact" class="theme-btn btn-style-one"><span class="txt">{{ cmsValue('global', 'header', 'primary_cta_text', 'Ask A Question') }}</span></router-link>
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
														<v-text-field v-model="password" label="Password*" :type="showPassword ? 'text' : 'password'" :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'" @click:append-inner="showPassword = !showPassword" required></v-text-field>
													</v-col>
												</v-row>
											</v-container>
										</v-card-text>
										<v-card-actions>
										<v-spacer></v-spacer>
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
            	<div class="nav-logo"><a href="/"><img :src="cmsValue('global', 'header', 'logo_url', '/frontend/images/logo.png')" alt="" title=""></a></div>
								<ul class="navigation clearfix"></ul>
            </nav>
        </div> 
		<!-- End Mobile Menu-->

    </header>
    <!-- End Main Header -->

	<!--Page Title-->
    <section class="page-title" :style="{ backgroundImage: `url(${cmsValue('lifecare', 'hero', 'background_image_url', '/frontend/images/background/3.jpg')})` }">
    	<div class="auto-container">
        	<h2>{{ cmsValue('lifecare', 'hero', 'headline', 'End of Life Care') }}</h2>
            <ul class="page-breadcrumb">
            	<li><a href="/">home</a></li>
                <li>{{ cmsValue('lifecare', 'hero', 'headline', 'End of Life Care') }}</li>
            </ul>
        </div>
    </section>
    <!--End Page Title-->

	<!--Sidebar Page Container-->
    <div class="sidebar-page-container">
    	<div class="auto-container">
        	<div class="row clearfix">

				<!--Content Side / Services Detail -->
                <div class="content-side col-lg-8 col-md-12 col-sm-12">
                	<div class="services-detail">
						<div class="inner-box">
							<div class="lower-content">
								<h2>{{ cmsValue('lifecare', 'content', 'headline', 'End of Life Care Service') }}</h2>
								<div class="styled-text">{{ cmsValue('lifecare', 'content', 'subheadline', 'Day-to-Day Care for Your Daily Needs') }}</div>
								<p>{{ cmsValue('lifecare', 'content', 'intro_text', 'Do you or a loved one need a little bit of extra help in your home?') }}</p>
								<p>{{ cmsValue('lifecare', 'content', 'overview_text', 'With our experienced and caring staff, your loved one can remain in their home and continue living independently for longer. Our customized care plans will match your loved one needs exactly, so they can enjoy care services that are uniquely tailored to them. Our compassionate caregivers are here to provide your loved one with exceptional support, while giving you the peace of mind you deserve.') }}</p>
								<p>{{ cmsValue('lifecare', 'content', 'tagline_text', 'Let our experienced caregivers help your family') }}</p>
								<div class="two-column">
									<div class="row clearfix">
										<div class="column col-lg-4 col-md-4 col-sm-12">
											<div class="image">
												<img :src="cmsValue('lifecare', 'content', 'primary_image_url', '/frontend/images/resource/service-17.jpg')" alt="" />
											</div>
										</div>
										<div class="column col-lg-8 col-md-8 col-sm-12">
											<h4>{{ cmsValue('lifecare', 'content', 'secondary_headline', 'End of Life Care Support for your elders') }}</h4>
											<p>{{ cmsValue('lifecare', 'content', 'secondary_text', 'Many seniors prefer to age in their own home, but this can grow more and more difficult as the years pass. Your loved one doesn\'t need to give up on the familiarity and comfort of their home, simply because they cannot remain completely independent. Our personal care services are specially designed to provide additional assistance to enhance your loved one\'s independence.') }}</p>
										</div>
									</div>
								</div>
								<h3>{{ cmsValue('lifecare', 'content', 'services_headline', 'Our personal caregivers offer assistance with activities such as:') }}</h3>
								<ul class="list-style-three">
									<li>{{ cmsValue('lifecare', 'content', 'services_item_1', 'Personal grooming, bathing, toileting, and hygiene tasks') }}</li>
									<li>{{ cmsValue('lifecare', 'content', 'services_item_2', 'Mobility and transfers') }}</li>
									<li>{{ cmsValue('lifecare', 'content', 'services_item_3', 'Medication reminders and monitoring') }}</li>
									<li>{{ cmsValue('lifecare', 'content', 'services_item_4', 'Companionship') }}</li>
									<li>{{ cmsValue('lifecare', 'content', 'services_item_5', 'Light housekeeping, meal planning, and meal preparation') }}</li>
									<li>{{ cmsValue('lifecare', 'content', 'services_item_6', 'Shopping and errands') }}</li>
								</ul>
								<h3>{{ cmsValue('lifecare', 'content', 'all_needs_headline', 'End of Life Care for All Needs') }}</h3>
								<p>{{ cmsValue('lifecare', 'content', 'closing_text', 'Our caregivers are ready to provide quality services to everyone in need of assistance, including elders, those who are living with a disability, and those who are in recovery from an injury or living with a chronic condition. We focus on your individual needs, regardless of the reason you need some extra help. Let us help your day-to-day life be simple, comfortable, and safe.') }}</p>
								<div class="service-contact-box">
                  {{ cmsValue('lifecare', 'content', 'service_cta_text', 'Let our experienced caregivers help your family today.') }}
                  <br>
                  <router-link to="/contact">{{ cmsValue('lifecare', 'content', 'service_cta_link_text', 'Contact our team') }}</router-link>
                  {{ cmsValue('lifecare', 'content', 'service_cta_suffix', 'to learn more!') }}
                </div>
							</div>
						</div>
					</div>
				</div>

				<!--Sidebar Side-->
				<div class="sidebar-side col-lg-4 col-md-12 col-sm-12">
					<aside class="sidebar padding-left">

						<!-- Sidebar Widget / List Widget-->
                        <div class="sidebar-widget list-widget">
							<!-- Services List -->
							<ul class="services-list">
								<li><router-link to="/personalcare">Personal Care</router-link></li>
								<li><router-link to="/elderlyservice">Elderly Care Service</router-link></li>
								<li><router-link to="/respitecare">Respite Care</router-link></li>
								<li><router-link to="/livein">Skilled Nursing</router-link></li>
								<!-- <li><router-link to="/surgery">24/7 Day Support</router-link></li> -->
								<li><router-link to="/discharge">Hospital Discharge</router-link></li>
								<li><router-link to="/care">Companion Care</router-link></li>
								<li><router-link to="/chronical">Chronical Condition Care</router-link></li>
								<li><router-link to="/support">Supported Living</router-link></li>
								<!-- <li class="active"><router-link to="/lifecare">End of Life Care</router-link></li> -->
								<li><router-link to="/specialcare">Special Needs Care</router-link></li>
							</ul>
						</div>

						<!-- Sidebar Widget / Contact Widget-->
                        <div class="sidebar-widget contact-widget">
							<div class="widget-content" style="background-image: url(/frontend/images/resource/contact-2.jpg);">
								<h3>{{ cmsValue('lifecare', 'sidebar', 'headline', 'Find Care Today') }}</h3>
								<router-link to="/contact" class="theme-btn contact-btn">{{ cmsValue('lifecare', 'sidebar', 'button_text', 'contact us') }}</router-link>
							</div>
						</div>

					</aside>
				</div>

			</div>
		</div>
	</div>


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
                                            <li><a href="https://www.facebook.com/p/Facilitate-Care-Services-100070370806760/" target="_blank"><span class="fab fa-facebook-f"></span></a></li>
                                            <!-- <li><a href="#"><span class="fab fa-linkedin-in"></span></a></li>
                                            <li><a href="#"><span class="fab fa-twitter"></span></a></li> -->
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

<!--Scroll to top-->
</template>
<script>
import axios from 'axios'
import publicInquiryDialogsMixin from '../mixins/publicInquiryDialogs'

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

				async Login() {
			try {
				const response = await axios.post('https://facilitatecareservices.co.uk/php/login.php?action=login', {
				// const response = await axios.post('https://facilitatecareservices.co.uk/src/php/login.php?action=login', {
					email: this.email,
					password: this.password,
				});

				if (response.data.success) {
					this.$store.commit('showSnackbar', {
						message: 'Login successful.',
						type: 'success'
					});
					this.$router.push('/dashboard');
				} else {
					this.$store.commit('showSnackbar', {
						message: 'Wrong username or password. Please try again.',
						type: 'error'
					});
					window.alert('Wrong username or password. Please try again.');
this.error = response.data.message;
				}
			} catch (error) {
				this.$store.commit('showSnackbar', {
					message: 'An error occurred. Please try again.',
					type: 'error'
				});
				window.alert('An error occurred. Please try again.');
this.error = "An error occurred. Please try again.";
			}
		}
	}
	 
};
</script>
<style>
    .btn-box {
        display: flex;
        justify-content: space-around;
        align-items: center;
    }

    .btn-box .theme-btn {
        margin: 0 20px;
    }
</style>





