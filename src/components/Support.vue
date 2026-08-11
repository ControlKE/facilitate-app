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
    <section class="page-title" :style="{ backgroundImage: `url(${cmsValue('support', 'hero', 'background_image_url', '/frontend/images/background/3.jpg')})` }">
    	<div class="auto-container">
        	<h2>{{ cmsValue('support', 'hero', 'headline', 'Supported Living') }}</h2>
            <ul class="page-breadcrumb">
            	<li><a href="/">home</a></li>
                <li>{{ cmsValue('support', 'hero', 'headline', 'Supported Living') }}</li>
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
							<div class="image">
								<img :src="cmsValue('support', 'content', 'primary_image_url', '/frontend/images/resource/service-10.png')" alt="" />
							</div>
							<div class="lower-content">
								<!-- <h2>After Surgery Care</h2> -->
								<p>{{ cmsValue('support', 'content', 'overview_text', 'Supported Living is a tailored service designed to help individuals with various needs, including physical disabilities, learning disabilities, mental health challenges, and age-related conditions, to live independently in their own homes or communities. This approach focuses on providing personalized support that empowers individuals to maintain control over their lives while enhancing their quality of life.') }}</p>
								<!-- <p>Let our experienced caregivers help your family</p> -->
								<div class="two-column">
									<div class="row clearfix">
										<div class="column col-lg-4 col-md-4 col-sm-12">
											<div class="image">
												<img :src="cmsValue('support', 'content', 'secondary_image_url', '/frontend/images/resource/service-23.jpg')" alt="" />
											</div>
										</div>
										<div class="column col-lg-8 col-md-8 col-sm-12">
											<!-- <h4>After Surgery Care Services to our Clients</h4> -->
										<p>{{ cmsValue('support', 'content', 'secondary_text', 'We are dedicated to offering comprehensive supported living services that are adaptable to the unique requirements of each person we serve. Our aim is to foster independence, dignity, and community integration for all our clients. We understand that every individual has different needs and aspirations, and we strive to provide the support that empowers them to live life on their terms. Whether it is assisting with daily activities, helping to manage health and wellness, or facilitating social and community engagement, our tailored approach ensures that each client receives the personalized care and support they deserve.') }}</p>
										</div>
									</div>
								</div>
								<div class="styled-text service-contact-box">{{ cmsValue('support', 'content', 'testimonial_quote', '" Outstanding on all levels. Mum carers are reliable, professional, well trained and friendly. In spite of the fact that she has Alzheimers, she feels really secure with them. They have worked hard to make her feel safe and her face lights up when she sees them. They never rush her and are really thorough with her personal care and aware of the importance of maintaining her dignity. We have no hesitation in recommending Facilitate Care to any family who have a loved one they want to be treated with genuine care and a high level of professionalism. " Yvonne S.') }}</div>
								<h2>{{ cmsValue('support', 'content', 'services_headline', 'Our supported living services.') }}</h2>
								<ul class="list-style-three">
									<li>{{ cmsValue('support', 'content', 'service_item_1', 'Personalized Support Plans: We work closely with you to create a support plan that reflects your personal preferences, needs, and goals. This plan is reviewed regularly to ensure it continues to meet your evolving requirements.') }}</li>
									<li>{{ cmsValue('support', 'content', 'service_item_2', 'Daily Living Assistance: Our dedicated team can assist with a variety of daily tasks, including personal care, meal preparation, household chores, and managing medication.') }}</li>
									<li>{{ cmsValue('support', 'content', 'service_item_3', 'Community Integration: We encourage and support you to participate in community activities, social events, and employment opportunities, helping you to build and maintain meaningful relationships.') }}</li>
									<li>{{ cmsValue('support', 'content', 'service_item_4', 'Health and Wellbeing: Our staff can support you with healthcare appointments, exercise routines, and accessing mental health services to ensure your overall wellbeing.') }}</li>
									
								</ul>
								<!-- <h3>After Surgery Care Services to our Clients</h3> -->
								<p>{{ cmsValue('support', 'content', 'closing_text', 'At Facilitate Care Services, you are at the heart of everything we do. Our person-centered approach ensures we listen to your needs and work collaboratively to provide the support you want. Our highly trained and compassionate staff are dedicated to delivering high-quality care, matching you with caregivers who understand your specific needs and preferences. We offer flexible and adaptive services, recognizing that your needs may change over time, and we adjust our support accordingly to continue providing the best care. Our commitment to independence empowers you to take control of your life, make your own decisions, and achieve your personal goals.') }}</p>
								<div class="service-contact-box"> Let our experienced caregivers help your family today. <br> <router-link to="/contact">Contact our team</router-link> to learn more! </div>
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
								<li><router-link to="/livein">Live In Care</router-link></li>
								<!-- <li class="active"><router-link to="/surgery">24/7 Day Support</router-link></li> -->
								<li><router-link to="/discharge">Hospital Discharge</router-link></li>
								<li><router-link to="/care">Companion Care</router-link></li>
								<li><router-link to="/chronical">Chronical Condition Care</router-link></li>
								<li class="active"><router-link to="/support">Supported Living</router-link></li>
								<!-- <li><router-link to="/lifecare">End of Life Care</router-link></li> -->
								<li><router-link to="/specialcare">Special Needs Care</router-link></li>
							</ul>
						</div>
						
						<!-- Sidebar Widget / Contact Widget-->
                        <div class="sidebar-widget contact-widget">
							<div class="widget-content" style="background-image: url(/frontend/images/resource/contact-2.jpg);">
								<h3>{{ cmsValue('support', 'sidebar', 'headline', 'Find Care Today') }}</h3>
								<router-link to="/contact" class="theme-btn contact-btn">{{ cmsValue('support', 'sidebar', 'button_text', 'contact us') }}</router-link>
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
			logindialog: false,
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




