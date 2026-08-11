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
														<v-text-field v-model="password" label="Password*" :type="showPassword ? 'text' : 'password'" :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'" @click:append-inner="showPassword = !showPassword" @keydown.enter.prevent="Login" required></v-text-field>
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
            <div class="close-btn"><span class="icon flaticon-cancel-1"></span></div> 

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
    <section class="page-title" :style="{ backgroundImage: `url(${cmsValue('contact', 'hero', 'background_image_url', '/frontend/images/background/3.jpg')})` }">
    	<div class="auto-container">
        	<h2>{{ cmsValue('contact', 'hero', 'headline', 'Contact Us') }}</h2>
            <ul class="page-breadcrumb">
            	<li><a href="/">home</a></li>
                <li>{{ cmsValue('contact', 'hero', 'headline', 'Contact Us') }}</li>
            </ul>
        </div>
    </section>
    <!--End Page Title-->

	<!-- Contact Form Section -->
	<section class="contact-form-section">
		<div class="auto-container">

			<!-- Title Box -->
			<div class="title-box">
				<h2>{{ cmsValue('contact', 'content', 'headline', 'Get In Touch') }}</h2>
				<div class="bold-text">{{ cmsValue('contact', 'content', 'subheadline', 'For further details on any of our services or to arrange a free initial consultation you can reach us via any of the methods below') }}</div>
				<!-- <div class="required">Fields marked with an * are required</div> -->
			</div>

			<div class="row clearfix">

				<!-- Form Column -->
				<div class="form-column col-lg-6 col-md-12 col-sm-12">
					<div class="inner-column">

						<!-- Default Form -->
						<div class="default-form contact-form">
							<form @submit.prevent="submitContactForm" id="contact-form">

								<div class="form-group">
									<input v-model="contactForm.name" type="text" name="name" placeholder="Names*" required>
								</div>

								<div class="form-group">
									<input v-model="contactForm.phone" type="text" name="phone" placeholder="Phone Number*" required>
								</div>

								<div class="form-group">
									<input v-model="contactForm.email" type="email" name="email" placeholder="Email*" required>
								</div>

								<div class="form-group">
									<textarea v-model="contactForm.message" name="message" placeholder="Your Message" required></textarea>
								</div>

								<!--div class="form-group">
									<span class="captcha">8 + 4*</span>
									<input type="text" name="subject" value="" placeholder="" required>
								</div-->

								<div class="form-group">
									<button type="submit" class="theme-btn btn-style-one" :disabled="sendingContact"><span class="txt">{{ sendingContact ? 'Sending...' : cmsValue('contact', 'content', 'submit_button_text', 'Submit now') }}</span></button>
								</div>

							</form>
							<div v-if="contactResponseMessage" class="contact-form-response">{{ contactResponseMessage }}</div>
						</div>
						<!--End Default Form-->

					</div>
				</div>

				<!-- Info Column -->
				<div class="info-column col-lg-6 col-md-12 col-sm-12">
					<div class="inner-column">
						<div class="image">
							<img :src="cmsValue('contact', 'content', 'office_image_url', '/frontend/images/resource/contact-1.jpg')" alt="" />
						</div>
						<h3>{{ cmsValue('contact', 'content', 'office_heading', 'Head Office:') }}</h3>
						<div class="text" v-html="cmsValue('contact', 'content', 'office_address', 'Ground Floor, suite A, 53-55 Butts Road, Coventry CV1 3BH')"></div>
						<ul>
							<li>Tel: <a href="">{{ cmsValue('contact', 'content', 'office_phone', '024 7623 1188') }}</a></li>
                            <li>Email :<a href="mailto:enquiries@facilitatecareservices.co.uk"> {{ cmsValue('contact', 'content', 'office_email', 'enquiries@facilitatecareservices.co.uk') }}</a></li>
                            <li>Opening Hours: <a href="">{{ cmsValue('contact', 'content', 'office_hours', '9 am to 5 pm Monday to Friday. (24 hr on call service)') }}</a></li>
						</ul>
					</div>
				</div>

			</div>
		</div>
	</section>
	<!-- End Contact Form Section -->

	<!-- Map Section -->
    <section class="map-section">
        <div class="outer-container">
            <div class="map-outer">
                <div class="map-canvas">
                    <iframe
                        :src="contactMapEmbedUrl()"
                        title="Facilitate Care Services contact location"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen
                        style="border:0; width:100%; height:100%;"
                    ></iframe>
                </div>
            </div>
        </div>
    </section>
    <!-- End Map Section -->

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
                                        <div class="text">{{ footerValue('tagline', 'Supporting Your Independence.') }}</div>
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

<v-snackbar
	v-model="snackbar"
	:color="snackColor"
	timeout="3500"
	location="bottom right"
>
	{{ snackbarText }}
	<template #actions>
		<v-btn color="white" variant="text" @click="snackbar = false">Close</v-btn>
	</template>
</v-snackbar>
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
			contactForm: {
				name: '',
				phone: '',
				email: '',
				message: '',
			},
			sendingContact: false,
			contactResponseMessage: '',
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

		contactMapNumber(fieldKey, fallback) {
			const rawValue = Number.parseFloat(this.cmsValue('contact', 'map_section', fieldKey, String(fallback)));
			return Number.isFinite(rawValue) ? rawValue : fallback;
		},

		contactMapZoom() {
			const rawZoom = Number.parseInt(this.cmsValue('contact', 'map_section', 'zoom', '18'), 10);
			if (!Number.isFinite(rawZoom)) {
				return 18;
			}
			return Math.min(20, Math.max(3, rawZoom));
		},

		contactMapType() {
			const rawType = String(this.cmsValue('contact', 'map_section', 'map_type', 'satellite') || '').trim().toLowerCase();
			return rawType === 'roadmap' ? 'roadmap' : 'satellite';
		},

		contactMapEmbedUrl() {
			const latitude = this.contactMapNumber('latitude', 52.4056402);
			const longitude = this.contactMapNumber('longitude', -1.5236883);
			const zoom = this.contactMapZoom();
			const mapType = this.contactMapType();
			const satelliteParam = mapType === 'satellite' ? '&t=k' : '';
			return `https://www.google.com/maps?q=${encodeURIComponent(`${latitude},${longitude}`)}&z=${zoom}${satelliteParam}&output=embed`;
		},

		openCaregiverDialog() {
            this.caregiverDialog = true;
        },

		showPublicSnackbar(message, type = 'success') {
			this.snackbarText = String(message || '').trim() || 'Request completed.';
			this.snackColor = type === 'error' ? 'error' : 'success';
			this.snackbar = true;
		},

		async submitContactForm() {
			this.sendingContact = true;
			this.contactResponseMessage = '';

			try {
				const result = await this.$store.dispatch('saveContactForm', {
					UserName: this.contactForm.name,
					Phonenumber: this.contactForm.phone,
					Email: this.contactForm.email,
					Message: this.contactForm.message,
					Date: new Date(),
				});

				this.contactResponseMessage = result?.message || 'An error occurred. Please try again later.';
				if (!result || result.success === false) {
					this.showPublicSnackbar(this.contactResponseMessage, 'error');
					return;
				}

				this.contactForm = {
					name: '',
					phone: '',
					email: '',
					message: '',
				};
				this.showPublicSnackbar(result?.message || 'Message sent successfully.', 'success');
			} catch (error) {
				console.error('Failed to submit contact form:', error);
				this.contactResponseMessage = 'An error occurred. Please try again later.';
				this.showPublicSnackbar(this.contactResponseMessage, 'error');
			} finally {
				this.sendingContact = false;
			}
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

.contact-form-response {
	margin-top: 16px;
	color: #4f5563;
	font-size: 0.95rem;
}

.v-dialog {
  text-align: center;
}
</style>



