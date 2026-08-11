<template>
    <div class="page-wrapper">
    <!-- Preloader -->
    <!-- <div class="preloader"></div> -->

    <header class="main-header">
        <!--Header Top-->
        <div class="header-top">
            <div class="auto-container clearfix">
                <div v-if="isCmsFieldVisible('global', 'header', 'phone')" class="top-left clearfix">
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
                    <div v-if="isCmsFieldVisible('global', 'header', 'logo_url')" class="logo-outer">
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
														<v-text-field v-model="complaintFName" label="First Name*" required></v-text-field>
													</v-col>
													<v-col cols="12" md="6" sm="4">
														<v-text-field v-model="complaintSName" label="Last Name*" required></v-text-field>
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
														<v-text-field v-model="FNametc" label="Your First Name*" required></v-text-field>
													</v-col>
													<v-col cols="12" md="6" sm="4">
														<v-text-field v-model="SNametc" label="Your Last Name*" required></v-text-field>
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
                                <router-link v-if="isCmsFieldVisible('global', 'header', 'primary_cta_text')" to="/contact" class="theme-btn btn-style-one"><span class="txt">{{ cmsValue('global', 'header', 'primary_cta_text', 'Ask A Question') }}</span></router-link>
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
            	<div v-if="isCmsFieldVisible('global', 'header', 'logo_url')" class="nav-logo"><a href="/"><img :src="cmsValue('global', 'header', 'logo_url', '/frontend/images/logo.png')" alt="" title=""></a></div>
								<ul class="navigation clearfix"></ul>
            </nav>
        </div> 
		<!-- End Mobile Menu-->

    </header>
    <!-- End Main Header -->

	<!--Page Title-->
    <section class="page-title" :style="{ backgroundImage: `url(${cmsValue('testimonial', 'hero', 'background_image_url', '/frontend/images/background/3.jpg')})` }">
    	<div class="auto-container">
        	<h2>{{ cmsValue('testimonial', 'hero', 'headline', 'Testimonials') }}</h2>
            <ul class="page-breadcrumb">
            	<li><a href="/">home</a></li>
                <li>{{ cmsValue('testimonial', 'hero', 'headline', 'Testimonials') }}</li>
            </ul>
        </div>
    </section>
    <!--End Page Title-->

	<!-- Testimonial Section -->
	<section class="testimonial-page-section">
		<div class="auto-container">

			<!-- Sec Title -->
			<div class="sec-title">
				<h2>{{ cmsValue('testimonial', 'hero', 'headline', "What Our Client's Say") }}</h2>
				<div v-if="cmsValue('testimonial', 'hero', 'subheadline', '')" class="text">
					{{ cmsValue('testimonial', 'hero', 'subheadline', '') }}
				</div>
			</div>

			<div class="row clearfix">

				<!-- Testimonial Block -->
				<div class="testimonial-block style-two col-lg-6 col-md-12 col-sm-12">
					<div class="inner-box">
						<div class="content-box">
							<div class="text">{{ cmsValue('testimonial', 'content', 'item_1_quote', 'I am just sending this to say my wife F. Adams has been moved into a care home as her health has deteriorated. I would just like to say how grateful I have been to the help your carers have been to Frances. I cannot fault them. If you could say a special thanks to Patricia, Franca and Thoko for the wonderful job they did with Frances. Many thanks for all your help.') }}
							</div>
						</div>
						<div class="lower-box">
							<div class="box-inner">
								<!-- <div class="image">
									<img src="/frontend/images/resource/author-1.jpg" alt="" />
								</div> -->
								<h3>{{ cmsValue('testimonial', 'content', 'item_1_name', 'Dave A') }}</h3>
								<div class="rating">
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Testimonial Block -->
				<div class="testimonial-block style-two col-lg-6 col-md-12 col-sm-12">
					<div class="inner-box">
						<div class="content-box">
							<div class="text">{{ cmsValue('testimonial', 'content', 'item_2_quote', 'My husband was in your care for 10 days. I found all the carers who came out to the house were very professional, competent and friendly. The two carers that helped when my husband passed away were caring, efficient and knew exactly what I needed to do. They both went the extra mile quietly and unobtrusively and I really do not know how I would have managed without them. Thank you to all the carers that I was fortunate enough to meet.') }}</div>
						</div>
						<div class="lower-box">
							<div class="box-inner">
								<!-- <div class="image">
									<img src="/frontend/images/resource/author-1.jpg" alt="" />
								</div> -->
								<h3>{{ cmsValue('testimonial', 'content', 'item_2_name', 'Linda W') }}</h3>
								<div class="rating">
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Testimonial Block -->
				<div class="testimonial-block style-two col-lg-6 col-md-12 col-sm-12">
					<div class="inner-box">
						<div class="content-box">
							<div class="text">{{ cmsValue('testimonial', 'content', 'item_3_quote', 'Facilitate took over the care of my uncle recently after a number of failings by the previous carers. The difference in care was amazing. The carers provided wonderful end of life care in terms of both personal and social care, spending time to listen to my uncle\'s stories and hold his hand. Highly recommended.') }}</div>
						</div>
						<div class="lower-box">
							<div class="box-inner">
								<!-- <div class="image">
									<img src="/frontend/images/resource/author-1.jpg" alt="" />
								</div> -->
								<h3>{{ cmsValue('testimonial', 'content', 'item_3_name', 'Nigel J') }}</h3>
								<div class="rating">
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Testimonial Block -->
				<div class="testimonial-block style-two col-lg-6 col-md-12 col-sm-12">
					<div class="inner-box">
						<div class="content-box">
							<div class="text">{{ cmsValue('testimonial', 'content', 'item_4_quote', 'Facilitate Care looked after and cared for my husband Don in the last weeks of his life. They were amazing, very gentle, and spoke to Don as they were caring for him. I was very happy with the care they gave him. Ekta and Eric were very special people and I thank them with all my heart.') }}</div>
						</div>
						<div class="lower-box">
							<div class="box-inner">
								<!-- <div class="image">
									<img src="/frontend/images/resource/author-1.jpg" alt="" />
								</div> -->
								<h3>{{ cmsValue('testimonial', 'content', 'item_4_name', 'Therese B') }}</h3>
								<div class="rating">
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Testimonial Block -->
				<div class="testimonial-block style-two col-lg-6 col-md-12 col-sm-12">
					<div class="inner-box">
						<div class="content-box">
							<div class="text">{{ cmsValue('testimonial', 'content', 'item_5_quote', 'Can\'t praise the care enough that Jane and Adam gave my brother in his last couple of weeks of his life. Such caring, thoughtful people, always treated my brother with such respect and kindness and such beautiful kind words to all the family on every visit they made. These pair are truly what carers should be and the whole family thanks them.') }}</div>
						</div>
						<div class="lower-box">
							<div class="box-inner">
								<!-- <div class="image">
									<img src="/frontend/images/resource/author-1.jpg" alt="" />
								</div> -->
								<h3>{{ cmsValue('testimonial', 'content', 'item_5_name', 'Carol T') }}</h3>
								<div class="rating">
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Testimonial Block -->
				<div class="testimonial-block style-two col-lg-6 col-md-12 col-sm-12">
					<div class="inner-box">
						<div class="content-box">
							<div class="text">{{ cmsValue('testimonial', 'content', 'item_6_quote', 'After a nasty fall and a week stay in hospital, I was nervous about having a care package at home, especially as a nurse and only just being 60. I had no need to worry; the two ladies who come are wonderful, cope with my wobbles, and I feel so cared for.') }}</div>
						</div>
						<div class="lower-box">
							<div class="box-inner">
								<!-- <div class="image">
									<img src="/frontend/images/resource/author-1.jpg" alt="" />
								</div> -->
								<h3>{{ cmsValue('testimonial', 'content', 'item_6_name', 'Debbie S') }}</h3>
								<div class="rating">
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
									<span class="fa fa-star"></span>
								</div>
							</div>
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
                        <!-- <div class="big-column col-lg-6 col-md-12 col-sm-12">
                            <div class="row clearfix"> -->

                                <!--Footer Column-->
                                <div class="footer-column col-lg-4 col-md-4 col-sm-12">
                                    <div class="footer-widget logo-widget">
    									<div class="logo">
											<v-avatar class="company-avatar" size="150" >
												<a href="/"><img :src="cmsValue('global', 'header', 'logo_url', '/frontend/images/logo.png')" alt="" /></a>
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
import axios from 'axios'
import publicInquiryDialogsMixin from '../mixins/publicInquiryDialogs'

const isLocalHost =
  typeof window !== 'undefined' &&
  (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')

const LOCAL_API_BASE = 'http://localhost/facilitate/src/php'
const LIVE_API_BASE = 'https://facilitatecareservices.co.uk/php'
const API_BASE = isLocalHost ? LOCAL_API_BASE : LIVE_API_BASE

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
			cmsContent: {},
			cmsLoaded: false,
		}
	},
	created() {
		this.loadCmsContent()
	},
	methods: {
		apiUrl(action) {
			return `${API_BASE}/websiteContent.php?action=${action}`
		},
		applyCmsTree(tree) {
			this.cmsContent = tree && typeof tree === 'object' ? tree : {}
		},
		resolveCmsField(pageKey, sectionKey, fieldKey) {
			const normalizedPageKey = String(pageKey || '').trim().toLowerCase()
			const normalizedSectionKey = String(sectionKey || '').trim().toLowerCase()
			const normalizedFieldKey = String(fieldKey || '')
			const candidates = []

			if (normalizedSectionKey === 'footer' && normalizedPageKey !== 'global') {
				candidates.push(['global', 'footer'])
			}
			candidates.push([normalizedPageKey, normalizedSectionKey])

			for (const [candidatePageKey, candidateSectionKey] of candidates) {
				const page = this.cmsContent[candidatePageKey]
				const section = page ? page[candidateSectionKey] : null
				const field = section ? section[normalizedFieldKey] : null
				if (typeof field !== 'undefined' && field !== null) {
					return field
				}
			}

			return null
		},
		hasCmsField(pageKey, sectionKey, fieldKey) {
			return Boolean(this.resolveCmsField(pageKey, sectionKey, fieldKey))
		},
		isCmsFieldVisible(pageKey, sectionKey, fieldKey) {
			return !this.cmsLoaded || this.hasCmsField(pageKey, sectionKey, fieldKey)
		},
		cmsValue(pageKey, sectionKey, fieldKey, fallback = '') {
			const field = this.resolveCmsField(pageKey, sectionKey, fieldKey)
			const value = field && typeof field.value !== 'undefined' ? field.value : fallback
			return typeof value === 'string' ? value : String(value !== null && typeof value !== 'undefined' ? value : fallback)
		},
		footerValue(fieldKey, fallback = '') {
			return this.cmsValue('global', 'footer', fieldKey, fallback)
		},
		footerPhoneHref() {
			const phone = this.footerValue('contact_phone', '024 7623 1188')
			const normalizedPhone = String(phone || '').replace(/\s+/g, '')
			return `tel:${normalizedPhone || '02476231188'}`
		},
		footerMailtoHref() {
			const email = this.footerValue('contact_email', 'info@facilitatecareservices.co.uk')
			return `mailto:${String(email || 'info@facilitatecareservices.co.uk')}`
		},
		footerCqcHref() {
			return this.footerValue('cqc_url', 'https://www.cqc.org.uk/location/1-2131286214')
		},
		phoneHref() {
			const phone = this.cmsValue('global', 'header', 'phone', '024 7623 1188')
			const normalizedPhone = String(phone || '').replace(/\s+/g, '')
			return `tel:${normalizedPhone || '02476231188'}`
		},
		async loadCmsContent() {
			try {
				const response = await axios.get(this.apiUrl('getPublic'))
				const payload = response && response.data ? response.data : {}
				if (payload.success) {
					this.applyCmsTree(payload.content || {})
				}
			} catch (error) {
				console.warn('Unable to load public CMS content for Testimonial page.', error)
			} finally {
				this.cmsLoaded = true
			}
		},
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



