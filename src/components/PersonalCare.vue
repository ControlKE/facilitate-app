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
    <section class="page-title" :style="{ backgroundImage: `url(${cmsValue('personalcare', 'hero', 'background_image_url', '/frontend/images/background/3.jpg')})` }">
    	<div class="auto-container">
        	<h2>{{ cmsValue('personalcare', 'hero', 'headline', 'Personal Care Service') }}</h2>
            <ul class="page-breadcrumb">
            	<li><a href="/">home</a></li>
                <li>{{ cmsValue('personalcare', 'hero', 'headline', 'Personal Care Service') }}</li>
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
								<h2>{{ cmsValue('personalcare', 'content', 'headline', 'Personal Care Services') }}</h2>
								<div class="styled-text">{{ cmsValue('personalcare', 'content', 'subheadline', 'Comprehensive Care for Your Daily Needs') }}</div>
								<p>{{ cmsValue('personalcare', 'content', 'overview_text_1', 'Personal care refers to the assistance provided to individuals who require support with daily activities that are essential for maintaining personal hygiene, health, and overall well-being. At Facilitate Care Services, we place immense value on providing care that not only addresses your physical needs but also nurtures your sense of comfort and dignity. Our Personal Care Services are a testament to our commitment to deliver exceptional care within the sanctuary of your home. We understand that there\'s no place like home, and our goal is to make it possible for you to stay there as long as you wish, surrounded by memories and the warmth of familiarity.') }}</p>
									<div class="styled-text service-contact-box">{{ cmsValue('personalcare', 'content', 'testimonial_quote', '" Facilitate took over the care of my uncle recently after a number of failings by the previous carers. The difference in care was amazing. The carers provided wonderful end of life care in terms of both personal and social care, spending time to listen to my uncle\'s stories and hold his hand. Highly recomended " Nigel J.') }}</div>
								<p>{{ cmsValue('personalcare', 'content', 'overview_text_2', 'Our approach to care is holistic and patient-centered. We recognise that each individual has their own set of preferences, routines, and needs. This understanding drives our mission to offer care that is as unique as you are. Whether it\'s your favorite breakfast routine or a specific bedtime ritual, our caregivers are attentive to those little details that make a big difference in your daily life.') }}</p>
								<!-- <p>Let our experienced caregivers help your family</p> -->
								<div class="two-column">
									<div class="row clearfix">
										<div class="column col-lg-4 col-md-4 col-sm-12">
											<div class="image">
												<img :src="cmsValue('personalcare', 'content', 'primary_image_url', '/frontend/images/resource/service-24.jpg')" alt="" />
											</div>
										</div>
										<div class="column col-lg-8 col-md-8 col-sm-12">
											<h4>{{ cmsValue('personalcare', 'content', 'secondary_headline', 'Personalised Home Care Services') }}</h4>
											<p>{{ cmsValue('personalcare', 'content', 'secondary_text', 'Living comfortably at home is a cherished preference, but maintaining independence can be challenging. We believe needing extra help shouldn\'t mean leaving home. Our personalised care services provide the necessary support for individuals to live independently and with dignity. Our goal is to enhance independence, ensuring daily life is enjoyed with confidence and comfort in familiar surroundings.') }}</p>
										</div>
									</div>
								</div>
								<h3>{{ cmsValue('personalcare', 'content', 'services_headline', 'Our Personal Care Services Include:') }}</h3>
								<ul class="list-style-three">
									<li>{{ cmsValue('personalcare', 'content', 'services_item_1', 'Assistance with personal grooming, bathing, and toileting') }}</li>
									<li>{{ cmsValue('personalcare', 'content', 'services_item_2', 'Help with mobility and transfers') }}</li>
									<li>{{ cmsValue('personalcare', 'content', 'services_item_3', 'Medication management and reminders') }}</li>
									<li>{{ cmsValue('personalcare', 'content', 'services_item_4', 'Companionship and emotional support') }}</li>
									<li>{{ cmsValue('personalcare', 'content', 'services_item_5', 'Light housekeeping and meal preparation') }}</li>
									<li>{{ cmsValue('personalcare', 'content', 'services_item_6', 'Running errands and shopping') }}</li>
                  					<li>{{ cmsValue('personalcare', 'content', 'services_item_7', 'Assistance with bedtime routines') }}</li>
								</ul>
								<!-- <h3>Inclusive Care for Diverse Needs</h3>
								<p>We are committed to providing inclusive care services. Our caregivers are equipped to support not only the elderly but also individuals with disabilities, those recovering from surgery, or living with chronic conditions. We focus on enhancing your quality of life, regardless of your care needs.</p> -->
                <h3>{{ cmsValue('personalcare', 'content', 'domestic_support_headline', 'Domestic Care Support for a Comfortable Lifestyle') }}</h3>
                <p>{{ cmsValue('personalcare', 'content', 'domestic_support_text', 'Understanding that personal care is just one aspect of maintaining a comfortable lifestyle, we extend our services to include thorough domestic care support. This service is ideal for those who manage their personal care effectively but could benefit from some extra help with household tasks. Our domestic care provides comprehensive assistance right in your home, ensuring a clean, organized, and well-maintained living space.') }}</p>
				<h3>{{ cmsValue('personalcare', 'content', 'additional_assistance_headline', 'Additional Home Assistance Services') }}</h3>
				<p>{{ cmsValue('personalcare', 'content', 'additional_assistance_text', 'Our dedicated team offers a wide array of services to make your daily life easier, including:') }}</p>
                				<ul class="list-style-three">
									<li>{{ cmsValue('personalcare', 'content', 'additional_item_1', 'Thorough house cleaning to keep your living space pleasant and hygienic.') }}</li>
									<li>{{ cmsValue('personalcare', 'content', 'additional_item_2', 'Laundry services, ensuring your clothes are always fresh and ready to wear.') }}</li>
									<li>{{ cmsValue('personalcare', 'content', 'additional_item_3', 'Bed making for a comfortable and inviting sleep environment.') }}</li>
									<li>{{ cmsValue('personalcare', 'content', 'additional_item_4', 'Grocery shopping, bringing the necessities of life right to your doorstep.') }}</li>
									<li>{{ cmsValue('personalcare', 'content', 'additional_item_5', 'General washing tasks, maintaining a tidy and sanitary home environment.') }}</li>
									<li>{{ cmsValue('personalcare', 'content', 'additional_item_6', 'Assistance with settling back into your home after a hospital stay, helping you re-adjust with ease and comfort.') }}<br><br></li>
								</ul>

								<div class="service-contact-box">
                  {{ cmsValue('personalcare', 'content', 'service_cta_text', 'Allow our skilled caregivers to support your family today.') }}
                  <br>
                  <router-link to="/contact">{{ cmsValue('personalcare', 'content', 'service_cta_link_text', 'Contact Us Today') }}</router-link>
                  {{ cmsValue('personalcare', 'content', 'service_cta_suffix', 'to learn more!') }}
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
				                <li class="active"><router-link to="/personalcare">Personal Care</router-link></li>
								<li><router-link to="/elderlyservice">Elderly Care Service</router-link></li>
								<li><router-link to="/respitecare">Respite Care</router-link></li>
								<li><router-link to="/livein">Live In Care</router-link></li>
								<!--li><a href="surgery.html">24/7 Day Support</a></li-->
								<li><router-link to="/discharge">Hospital Discharge</router-link></li>
								<li><router-link to="/care">Social Care</router-link></li>
								<li><router-link to="/chronical">Palliative Care</router-link></li>
								<li><router-link to="/support">Supported Living</router-link></li>
								<!--li><a href="support.html">After Surgery Care</a></li>
								<li><a href="life-care.html">End of Life Care</a></li-->
								<li><router-link to="/specialcare">Special Needs Care</router-link></li>
							</ul>

						</div>

						<!-- Sidebar Widget / Contact Widget-->
                        <div class="sidebar-widget contact-widget">
							<div class="widget-content" style="background-image: url(/frontend/images/resource/contact-2.jpg);">
								<h3>{{ cmsValue('personalcare', 'sidebar', 'headline', 'Find Care Today') }}</h3>
								<router-link to="/contact" class="theme-btn contact-btn">{{ cmsValue('personalcare', 'sidebar', 'button_text', 'contact us') }}</router-link>
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
                        <!-- <div class="big-column col-lg-6 col-md-12 col-sm-12">
                            <div class="row clearfix"> -->

                                <!--Footer Column-->
                                <div class="footer-column col-lg-4 col-md-4 col-sm-12">
                                    <div class="footer-widget logo-widget">
    									<div class="logo">
											<v-avatar class="company-avatar" size="150" >
												<a href="/"><img :src="cmsValue('global', 'header', 'logo_url', '/frontend/images/logo.png')" alt="" /></a>
											</v-avatar>
                                        	<a href="/"><img :src="cmsValue('home', 'footer', 'logo_url', '/frontend/images/footer-logo.png')" alt="" /></a>
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




