<template>
<v-app>
  <div class="page-wrapper">
    <!-- Preloader-->
    <!-- <div class="preloader"></div> -->

    <header class="main-header">
        <!--Header Top-->
        <div class="header-top">
            <div class="auto-container clearfix">
                <div v-if="isCmsFieldVisible('global', 'header', 'phone')" class="top-left clearfix">
                    <div class="text"><span class="icon flaticon-phone-receiver"></span> Need help? Call Us Now : <a :href="phoneHref()" class="number">{{ cmsValue('global', 'header', 'phone', '024 7623 1188') }}</a></div>
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
                        <!--Mobile Navigation Toggler For Mobile-->
						<div class="mobile-nav-toggler" @click="openMobileMenu"><span class="icon flaticon-menu-button"></span></div>

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
														<v-text-field
															v-model="password"
															label="Password*"
															:type="showPassword ? 'text' : 'password'"
															:append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
															@click:append-inner="togglePasswordVisibility"
															@keydown.enter.prevent="Login"
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
            <div class="menu-backdrop" @click="closeMobileMenu"></div>
            <div class="close-btn" @click="closeMobileMenu"><span class="icon flaticon-cancel-1"></span></div> 

            <!--Here Menu Will Come Automatically Via Javascript / Same Menu as in Header-->
            <nav class="menu-box">
            	<div v-if="isCmsFieldVisible('global', 'header', 'logo_url')" class="nav-logo"><a href="/"><img :src="cmsValue('global', 'header', 'logo_url', '/frontend/images/logo.png')" alt="" title=""></a></div>
								<ul class="navigation clearfix"></ul>
            </nav>
        </div> 
		<!-- End Mobile Menu-->

    </header>
    <!-- End Main Header -->


    <!--Main Slider-->
    <section class="main-slider">

        <div class="rev_slider_wrapper fullwidthbanner-container"  id="rev_slider_one_wrapper" data-source="gallery">
            <div class="rev_slider fullwidthabanner" id="rev_slider_one" data-version="5.4.1">
                <ul>

                    <li data-transition="parallaxvertical" data-description="Slide Description" data-easein="default" data-easeout="default" data-fsmasterspeed="1500" data-fsslotamount="7" data-fstransition="fade" data-hideafterloop="0" data-hideslideonmobile="off" data-index="rs-1688" data-masterspeed="default" data-param1="" data-param10="" data-param2="" data-param3="" data-param4="" data-param5="" data-param6="" data-param7="" data-param8="" data-param9="" data-rotate="0" data-saveperformance="off" data-slotamount="default" :data-thumb="cmsValue('home', 'hero', 'slide_1_image_url', '/frontend/images/main-slider/1.jpg')" data-title="Slide Title">
                    <img alt="" class="rev-slidebg" data-bgfit="cover" data-bgparallax="10" data-bgposition="center center" data-bgrepeat="no-repeat" data-no-retina="" :src="cmsValue('home', 'hero', 'slide_1_image_url', '/frontend/images/main-slider/1.jpg')" style="background-attachment: scroll;">

					<div class="tp-caption"
                    data-paddingbottom="[0,0,0,0]"
                    data-paddingleft="[0,0,0,0]"
                    data-paddingright="[0,0,0,0]"
                    data-paddingtop="[0,0,0,0]"
                    data-responsive_offset="on"
                    data-type="text"
                    data-height="none"
                    data-width="['700','650','650','450']"
                    data-whitespace="normal"
                    data-hoffset="['15','15','15','15']"
                    data-voffset="['-80','-110','-110','-110']"
                    data-x="['left','left','left','left']"
                    data-y="['middle','middle','middle','middle']"
                    data-textalign="['top','top','top','top']"
                    data-frames='[{"delay":0,"speed":1500,"frame":"0","from":"y:[-100%];z:0;rX:0deg;rY:0;rZ:0;sX:1;sY:1;skX:0;skY:0;","mask":"x:0px;y:0px;s:inherit;e:inherit;","to":"o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"Power3.easeInOut"}]'>
                        <h2>{{ cmsValue('home', 'hero', 'slide_1_heading', 'Expert Care & Personalised Support') }}</h2>
                    </div>

                    <div class="tp-caption"
                    data-paddingbottom="[0,0,0,0]"
                    data-paddingleft="[0,0,0,0]"
                    data-paddingright="[0,0,0,0]"
                    data-paddingtop="[0,0,0,0]"
                    data-responsive_offset="on"
                    data-type="text"
                    data-height="none"
                    data-width="['650','650','650','450']"
                    data-whitespace="normal"
                    data-hoffset="['15','15','15','15']"
                    data-voffset="['35','10','10','10']"
                    data-x="['left','left','left','left']"
                    data-y="['middle','middle','middle','middle']"
                    data-textalign="['top','top','top','top']"
                    data-frames='[{"delay":500,"speed":1500,"frame":"0","from":"y:[-100%];z:0;rX:0deg;rY:0;rZ:0;sX:1;sY:1;skX:0;skY:0;","mask":"x:0px;y:0px;s:inherit;e:inherit;","to":"o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"Power3.easeInOut"}]'>
                        <div class="text hero-subheader">{{ cmsValue('home', 'hero', 'slide_1_body', 'Delivering expert care and compassionate support tailored to you.') }}</div>
                    </div>

                    <div class="tp-caption"
                    data-paddingbottom="[0,0,0,0]"
                    data-paddingleft="[0,0,0,0]"
                    data-paddingright="[0,0,0,0]"
                    data-paddingtop="[0,0,0,0]"
                    data-responsive_offset="on"
                    data-type="text"
                    data-height="none"
                    data-whitespace="normal"
                    data-width="['650','650','650','450']"
                    data-hoffset="['15','15','15','15']"
                    data-voffset="['125','100','120','120']"
                    data-x="['left','left','left','left']"
                    data-y="['middle','middle','middle','middle']"
                    data-textalign="['top','top','top','top']"
                    data-frames='[{"delay":1000,"speed":2000,"frame":"0","from":"y:[100%];z:0;rX:0deg;rY:0;rZ:0;sX:1;sY:1;skX:0;skY:0;opacity:0;","to":"o:1;","ease":"Power4.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"Power3.easeInOut"}]'>
                        <div v-if="isCmsFieldVisible('home', 'hero', 'cta_text')" class="link-box hero-cta">
                            <router-link :to="cmsValue('home', 'hero', 'cta_url', '/contact')" class="theme-btn btn-style-one"><span class="txt">{{ cmsValue('home', 'hero', 'cta_text', 'Book A Consultation') }}</span></router-link>
                        </div>
                    </div>

					</li>

                    <li data-transition="parallaxvertical" data-description="Slide Description" data-easein="default" data-easeout="default" data-fsmasterspeed="1500" data-fsslotamount="7" data-fstransition="fade" data-hideafterloop="0" data-hideslideonmobile="off" data-index="rs-1689" data-masterspeed="default" data-param1="" data-param10="" data-param2="" data-param3="" data-param4="" data-param5="" data-param6="" data-param7="" data-param8="" data-param9="" data-rotate="0" data-saveperformance="off" data-slotamount="default" :data-thumb="cmsValue('home', 'hero', 'slide_2_image_url', '/frontend/images/main-slider/2.jpg')" data-title="Slide Title">
                    <img alt="" class="rev-slidebg" data-bgfit="cover" data-bgparallax="10" data-bgposition="center center" data-bgrepeat="no-repeat" data-no-retina="" :src="cmsValue('home', 'hero', 'slide_2_image_url', '/frontend/images/main-slider/2.jpg')" style="background-attachment: scroll;">

                    <div class="tp-caption"
                    data-paddingbottom="[0,0,0,0]"
                    data-paddingleft="[0,0,0,0]"
                    data-paddingright="[0,0,0,0]"
                    data-paddingtop="[0,0,0,0]"
                    data-responsive_offset="on"
                    data-type="text"
                    data-height="none"
                    data-width="['700','650','650','450']"
                    data-whitespace="normal"
                    data-hoffset="['15','15','15','15']"
                    data-voffset="['-80','-110','-110','-110']"
                    data-x="['left','left','left','left']"
                    data-y="['middle','middle','middle','middle']"
                    data-textalign="['top','top','top','top']"
                    data-frames='[{"delay":0,"speed":1500,"frame":"0","from":"y:[-100%];z:0;rX:0deg;rY:0;rZ:0;sX:1;sY:1;skX:0;skY:0;","mask":"x:0px;y:0px;s:inherit;e:inherit;","to":"o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"Power3.easeInOut"}]'>
                        <h2>{{ cmsValue('home', 'hero', 'slide_2_heading', 'Elevating Spirits with Exceptional At-Home Care') }}</h2>
                    </div>

                    <div class="tp-caption"
                    data-paddingbottom="[0,0,0,0]"
                    data-paddingleft="[0,0,0,0]"
                    data-paddingright="[0,0,0,0]"
                    data-paddingtop="[0,0,0,0]"
                    data-responsive_offset="on"
                    data-type="text"
                    data-height="none"
                    data-width="['650','650','650','450']"
                    data-whitespace="normal"
                    data-hoffset="['15','15','15','15']"
                    data-voffset="['35','10','10','10']"
                    data-x="['left','left','left','left']"
                    data-y="['middle','middle','middle','middle']"
                    data-textalign="['top','top','top','top']"
                    data-frames='[{"delay":500,"speed":1500,"frame":"0","from":"y:[-100%];z:0;rX:0deg;rY:0;rZ:0;sX:1;sY:1;skX:0;skY:0;","mask":"x:0px;y:0px;s:inherit;e:inherit;","to":"o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"Power3.easeInOut"}]'>
                        <div class="text hero-subheader">{{ cmsValue('home', 'hero', 'slide_2_body', 'Our in-home care services are designed to uplift and nurture the human spirit.') }}</div>
                    </div>

                    <div class="tp-caption"
                    data-paddingbottom="[0,0,0,0]"
                    data-paddingleft="[0,0,0,0]"
                    data-paddingright="[0,0,0,0]"
                    data-paddingtop="[0,0,0,0]"
                    data-responsive_offset="on"
                    data-type="text"
                    data-height="none"
                    data-whitespace="normal"
                    data-width="['650','650','650','450']"
                    data-hoffset="['15','15','15','15']"
                    data-voffset="['125','100','120','120']"
                    data-x="['left','left','left','left']"
                    data-y="['middle','middle','middle','middle']"
                    data-textalign="['top','top','top','top']"
                    data-frames='[{"delay":1000,"speed":2000,"frame":"0","from":"y:[100%];z:0;rX:0deg;rY:0;rZ:0;sX:1;sY:1;skX:0;skY:0;opacity:0;","to":"o:1;","ease":"Power4.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"Power3.easeInOut"}]'>
                        <!-- <div class="link-box">
                            <router-link to="/about" class="theme-btn btn-style-one"><span class="txt">Learn more</span></router-link>
                        </div> -->
                    </div>

                    </li>

					<li data-transition="parallaxvertical" data-description="Slide Description" data-easein="default" data-easeout="default" data-fsmasterspeed="1500" data-fsslotamount="7" data-fstransition="fade" data-hideafterloop="0" data-hideslideonmobile="off" data-index="rs-1690" data-masterspeed="default" data-param1="" data-param10="" data-param2="" data-param3="" data-param4="" data-param5="" data-param6="" data-param7="" data-param8="" data-param9="" data-rotate="0" data-saveperformance="off" data-slotamount="default" :data-thumb="cmsValue('home', 'hero', 'slide_3_image_url', '/frontend/images/main-slider/3.jpg')" data-title="Slide Title">
                    <img alt="" class="rev-slidebg" data-bgfit="cover" data-bgparallax="10" data-bgposition="center center" data-bgrepeat="no-repeat" data-no-retina="" :src="cmsValue('home', 'hero', 'slide_3_image_url', '/frontend/images/main-slider/3.jpg')" style="background-attachment: scroll;">

                    <div class="tp-caption"
                    data-paddingbottom="[0,0,0,0]"
                    data-paddingleft="[0,0,0,0]"
                    data-paddingright="[0,0,0,0]"
                    data-paddingtop="[0,0,0,0]"
                    data-responsive_offset="on"
                    data-type="text"
                    data-height="none"
                    data-width="['700','650','650','450']"
                    data-whitespace="normal"
                    data-hoffset="['15','15','15','15']"
                    data-voffset="['-80','-110','-110','-110']"
                    data-x="['left','left','left','left']"
                    data-y="['middle','middle','middle','middle']"
                    data-textalign="['top','top','top','top']"
                    data-frames='[{"delay":0,"speed":1500,"frame":"0","from":"y:[-100%];z:0;rX:0deg;rY:0;rZ:0;sX:1;sY:1;skX:0;skY:0;","mask":"x:0px;y:0px;s:inherit;e:inherit;","to":"o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"Power3.easeInOut"}]'>
                        <h2>{{ cmsValue('home', 'hero', 'slide_3_heading', 'Quality Home Care Service, You Can Trust.') }}</h2>
                    </div>

                    <div class="tp-caption"
                    data-paddingbottom="[0,0,0,0]"
                    data-paddingleft="[0,0,0,0]"
                    data-paddingright="[0,0,0,0]"
                    data-paddingtop="[0,0,0,0]"
                    data-responsive_offset="on"
                    data-type="text"
                    data-height="none"
                    data-width="['650','650','650','450']"
                    data-whitespace="normal"
                    data-hoffset="['15','15','15','15']"
                    data-voffset="['35','10','10','10']"
                    data-x="['left','left','left','left']"
                    data-y="['middle','middle','middle','middle']"
                    data-textalign="['top','top','top','top']"
                    data-frames='[{"delay":500,"speed":1500,"frame":"0","from":"y:[-100%];z:0;rX:0deg;rY:0;rZ:0;sX:1;sY:1;skX:0;skY:0;","mask":"x:0px;y:0px;s:inherit;e:inherit;","to":"o:1;","ease":"Power3.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"Power3.easeInOut"}]'>
                        <div class="text hero-subheader">{{ cmsValue('home', 'hero', 'slide_3_body', 'Experience top-quality home care services you can rely on without hesitation.') }}</div>
                    </div>

                    <div class="tp-caption"
                    data-paddingbottom="[0,0,0,0]"
                    data-paddingleft="[0,0,0,0]"
                    data-paddingright="[0,0,0,0]"
                    data-paddingtop="[0,0,0,0]"
                    data-responsive_offset="on"
                    data-type="text"
                    data-height="none"
                    data-whitespace="normal"
                    data-width="['650','650','650','450']"
                    data-hoffset="['15','15','15','15']"
                    data-voffset="['125','100','120','120']"
                    data-x="['left','left','left','left']"
                    data-y="['middle','middle','middle','middle']"
                    data-textalign="['top','top','top','top']"
                    data-frames='[{"delay":1000,"speed":2000,"frame":"0","from":"y:[100%];z:0;rX:0deg;rY:0;rZ:0;sX:1;sY:1;skX:0;skY:0;opacity:0;","to":"o:1;","ease":"Power4.easeInOut"},{"delay":"wait","speed":300,"frame":"999","to":"auto:auto;","ease":"Power3.easeInOut"}]'>
                        <!-- <div class="link-box">
                            <router-link to="/about" class="theme-btn btn-style-one"><span class="txt">Learn more</span></router-link>
                        </div> -->
                    </div>

                    </li>

                </ul>
            </div>
        </div>
    </section>
    <!--End Main Slider-->

	<!-- Services Section -->
	<section id="about" class="services-section">
		<div class="auto-container">
			<!-- Title Box -->
			<div class="title-box">
				<h2>{{ cmsValue('home', 'services_intro', 'headline', 'Comprehensive and Personalised Home Care Services') }}</h2>
				<div class="bold-text">{{ cmsValue('home', 'services_intro', 'subheadline', 'Our dedicated team of experienced healthcare experts delivers specialized in-home care, tailored to optimize and oversee your recovery process in the comfort of your own space.') }}</div>
				<div class="text">{{ cmsValue('home', 'services_intro', 'body_text', 'Registered with the Care Quality Commission, we pride ourselves on giving a truly helpful service that will enhance your quality of life. Our flexible service provides options from a one-off visit to several visits daily on an ongoing basis.') }}</div>
			</div>
			<div class="clearfix">

				<!-- Service Block -->
				<div class="service-block col-lg-4 col-md-6 col-sm-12">
					<div class="inner-box">
						<div class="image">
							<img :src="cmsValue('home', 'services_feature_cards', 'card_1_image_url', '/frontend/images/resource/service-1.jpg')" alt="" />
							<div class="overlay-box">
								<div class="overlay-inner">
									<div class="content">
										<div class="content-inner">
											<h3>{{ cmsValue('home', 'services_feature_cards', 'card_1_title', 'We Enrich') }}</h3>
											<div class="text">{{ cmsValue('home', 'services_feature_cards', 'card_1_text', 'Comfort, support, and a sense of belonging for all.') }}</div>
											<!-- <div class="btn-box">
												<router-link to="/care" class="theme-btn care-btn">Type of care <span class="icon flaticon-logout"></span></router-link>
											</div> -->
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Service Block -->
				<div class="service-block col-lg-4 col-md-6 col-sm-12">
					<div class="inner-box">
						<div class="image">
							<img :src="cmsValue('home', 'services_feature_cards', 'card_2_image_url', '/frontend/images/resource/service-2.jpg')" alt="" />
							<div class="overlay-box">
								<div class="overlay-inner">
									<div class="content">
										<div class="content-inner">
											<h3>{{ cmsValue('home', 'services_feature_cards', 'card_2_title', 'We Empower') }}</h3>
											<div class="text">{{ cmsValue('home', 'services_feature_cards', 'card_2_text', 'Regain independence and thrive in the comfort of your home.') }}</div>
											<!-- <div class="btn-box">
												<router-link to="/elderlyservice" class="theme-btn care-btn">Type of care <span class="icon flaticon-logout"></span></router-link>
											</div> -->
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Service Block -->
				<div class="service-block col-lg-4 col-md-6 col-sm-12">
					<div class="inner-box">
						<div class="image">
							<img :src="cmsValue('home', 'services_feature_cards', 'card_3_image_url', '/frontend/images/resource/service-3.jpg')" alt="" />
							<div class="overlay-box">
								<div class="overlay-inner">
									<div class="content">
										<div class="content-inner">
											<h3>{{ cmsValue('home', 'services_feature_cards', 'card_3_title', 'We Engage') }}</h3>
											<div class="text">{{ cmsValue('home', 'services_feature_cards', 'card_3_text', 'Personalised, dignified care for your unique needs and well-being.') }}</div>
											<!-- <div class="btn-box">
												<router-link to="/personalcare" class="theme-btn care-btn">Type of care <span class="icon flaticon-logout"></span></router-link>
											</div> -->
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</section>
	<!-- End Services Section -->

	<!-- Services Section Two -->
	<section id="services" class="services-section-two">
		<div class="auto-container">

			<!-- Sec Title -->
			<div class="sec-title">
				<h2>{{ cmsValue('home', 'services_catalog', 'headline', 'Explore Our Range of Services') }}</h2>
				<div class="text">{{ cmsValue('home', 'services_catalog', 'subheadline', 'Discover a range of specialised services designed to provide comfort, support, and tailored care for you or your loved ones.') }}</div>
			</div>
			
			<div class="services-carousel owl-carousel owl-theme">

				<!-- Service Block Two -->
				<div class="service-block-two">
					<div class="inner-box">
						<div class="image">
							<img :src="cmsValue('home', 'services_catalog_items', 'item_1_image_url', '/frontend/images/resource/service-4.jpg')" alt="" />
						</div>
						<div class="lower-content">
							<h3><router-link to="/personalcare">{{ cmsValue('home', 'services_catalog_items', 'item_1_title', 'Personal Care') }}</router-link></h3>
							<div class="text">{{ cmsValue('home', 'services_catalog_items', 'item_1_text', 'Assistance with daily activities to enhance comfort and well-being of the service user.') }}</div>
						</div>
					</div>
				</div>

				<!-- Service Block Two -->
				<div class="service-block-two">
					<div class="inner-box">
						<div class="image">
							<img :src="cmsValue('home', 'services_catalog_items', 'item_2_image_url', '/frontend/images/resource/service-5.jpg')" alt="" />
						</div>
						<div class="lower-content">
							<h3><router-link to="/care">{{ cmsValue('home', 'services_catalog_items', 'item_2_title', 'Social Care') }}</router-link></h3>
							<div class="text">{{ cmsValue('home', 'services_catalog_items', 'item_2_text', 'Companionship and support to maintain an active and fulfilling social life.') }}</div>
						</div>
					</div>
				</div>

				<!-- Service Block Two -->
				<div class="service-block-two">
					<div class="inner-box">
						<div class="image">
							<img :src="cmsValue('home', 'services_catalog_items', 'item_3_image_url', '/frontend/images/resource/service-6.jpg')" alt="" />
						</div>
						<div class="lower-content">
							<h3><router-link to="/livein">{{ cmsValue('home', 'services_catalog_items', 'item_3_title', 'Live In Care') }}</router-link></h3>
							<div class="text">{{ cmsValue('home', 'services_catalog_items', 'item_3_text', '24/7 personalised care ensuring safety, comfort, and peace of mind at home.') }}</div>
						</div>
					</div>
				</div>

				<!-- Service Block Two -->
				<div class="service-block-two">
					<div class="inner-box">
						<div class="image">
							<img :src="cmsValue('home', 'services_catalog_items', 'item_4_image_url', '/frontend/images/resource/service-7.jpg')" alt="" />
						</div>
						<div class="lower-content">
							<h3><router-link to="/discharge">{{ cmsValue('home', 'services_catalog_items', 'item_4_title', 'Hospital Discharge') }}</router-link></h3>
							<div class="text">{{ cmsValue('home', 'services_catalog_items', 'item_4_text', 'Smooth transition from hospital to home with post-hospitalization support and care.') }}</div>
						</div>
					</div>
				</div>

				<!-- Service Block Two -->
				<div class="service-block-two">
					<div class="inner-box">
						<div class="image">
							<img :src="cmsValue('home', 'services_catalog_items', 'item_5_image_url', '/frontend/images/resource/service-8.jpg')" alt="" />
						</div>
						<div class="lower-content">
							<h3><router-link to="/elderlyservice">{{ cmsValue('home', 'services_catalog_items', 'item_5_title', 'Elderly Care Service') }}</router-link></h3>
							<div class="text">{{ cmsValue('home', 'services_catalog_items', 'item_5_text', 'Tailored care for seniors, addressing their unique needs and preferences.') }}</div>
						</div>
					</div>
				</div>

				<!-- Service Block Two -->
				<div class="service-block-two">
					<div class="inner-box">
						<div class="image">
							<img :src="cmsValue('home', 'services_catalog_items', 'item_6_image_url', '/frontend/images/resource/service-9.jpg')" alt="" />
						</div>
						<div class="lower-content">
							<h3><router-link to="/respitecare">{{ cmsValue('home', 'services_catalog_items', 'item_6_title', 'Respite Care') }}</router-link></h3>
							<div class="text">{{ cmsValue('home', 'services_catalog_items', 'item_6_text', 'Temporary relief for caregivers, ensuring continuous quality care for your loved one.') }}</div>
						</div>
					</div>
				</div>

				<!-- Service Block Two -->
				<div class="service-block-two">
					<div class="inner-box">
						<div class="image">
							<img :src="cmsValue('home', 'services_catalog_items', 'item_7_image_url', '/frontend/images/resource/services-10.jpg')" alt="" />
						</div>
						<div class="lower-content">
							<h3><router-link to="/chronical">{{ cmsValue('home', 'services_catalog_items', 'item_7_title', 'Palliative Care') }}</router-link></h3>
							<div class="text">{{ cmsValue('home', 'services_catalog_items', 'item_7_text', 'Comfort-focused care and emotional support for individuals with serious illnesses.') }}</div>
						</div>
					</div>
				</div>

				<!-- Service Block Two -->
				<div class="service-block-two">
					<div class="inner-box">
						<div class="image">
							<img :src="cmsValue('home', 'services_catalog_items', 'item_8_image_url', '/frontend/images/resource/services-11.jpg')" alt="" />
						</div>
						<div class="lower-content">
							<h3><router-link to="/specialcare">{{ cmsValue('home', 'services_catalog_items', 'item_8_title', 'Special Needs Care') }}</router-link></h3>
							<div class="text">{{ cmsValue('home', 'services_catalog_items', 'item_8_text', 'Customized assistance for individuals with unique requirements, ensuring their well-being.') }}</div>
						</div>
					</div>
				</div>

				<!-- Service Block Two -->
				<div class="service-block-two">
					<div class="inner-box">
						<div class="image">
							<img :src="cmsValue('home', 'services_catalog_items', 'item_9_image_url', '/frontend/images/resource/service-23.jpg')" alt="" />
						</div>
						<div class="lower-content">
							<h3><router-link to="/support">{{ cmsValue('home', 'services_catalog_items', 'item_9_title', 'Supported Living') }}</router-link></h3>
							<div class="text">{{ cmsValue('home', 'services_catalog_items', 'item_9_text', 'Personalised support for individuals with learning, physical, or mental challenges to live independently.') }}</div>
						</div>
					</div>
				</div>

			</div>

		</div>
	</section>
	<!-- End Services Section Two -->

	<!-- Services Section Three -->
	<section class="services-section-three">
		<div class="auto-container">
			<!-- Title Box -->
			<div class="title-box">
				<h2>{{ cmsValue('home', 'care_tasks', 'headline', 'We provide In Home Care Services For Everyone.') }}</h2>
				<div class="text">{{ cmsValue('home', 'care_tasks', 'subheadline', 'Discover how professional caregivers approach caring for your loved one in order to engage') }}</div>
			</div>

			<div class="row clearfix">

				<!-- Service Block Three -->
				<div class="service-block-three col-lg-4 col-md-6 col-sm-12">
					<div class="inner-box">
						<div class="overlay-box">
							<div class="overlay-inner">
								<div class="overlay-content">
									<div class="content">
										<div class="icon-box">
											<span class="icon flaticon-hair"></span>
										</div>
										<h3>{{ cmsValue('home', 'care_tasks', 'item_1_title', 'Personal Grooming Like Bathing or Getting Dressed') }}</h3>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Service Block Three -->
				<div class="service-block-three col-lg-4 col-md-6 col-sm-12">
					<div class="inner-box">
						<div class="overlay-box">
							<div class="overlay-inner">
								<div class="overlay-content">
									<div class="content">
										<div class="icon-box">
											<span class="icon flaticon-elder"></span>
										</div>
										<h3>{{ cmsValue('home', 'care_tasks', 'item_2_title', 'Helping a Person with Dementia by Grounding and Orienting Them') }}</h3>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Service Block Three -->
				<div class="service-block-three col-lg-4 col-md-6 col-sm-12">
					<div class="inner-box">
						<div class="overlay-box">
							<div class="overlay-inner">
								<div class="overlay-content">
									<div class="content">
										<div class="icon-box">
											<span class="icon flaticon-medicine"></span>
										</div>
										<h3>{{ cmsValue('home', 'care_tasks', 'item_3_title', 'Take Care of Medication Reminders') }}</h3>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Service Block Three -->
				<div class="service-block-three col-lg-4 col-md-6 col-sm-12">
					<div class="inner-box">
						<div class="overlay-box">
							<div class="overlay-inner">
								<div class="overlay-content">
									<div class="content">
										<div class="icon-box">
											<span class="icon flaticon-walk"></span>
										</div>
										<h3>{{ cmsValue('home', 'care_tasks', 'item_4_title', 'Moving Around. Getting in and out of the Bed or Shower') }}</h3>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Service Block Three -->
				<div class="service-block-three col-lg-4 col-md-6 col-sm-12">
					<div class="inner-box">
						<div class="overlay-box">
							<div class="overlay-inner">
								<div class="overlay-content">
									<div class="content">
										<div class="icon-box">
											<span class="icon flaticon-groceries"></span>
										</div>
										<h3>{{ cmsValue('home', 'care_tasks', 'item_5_title', 'Errands Like Grocery Shopping and Picking up Prescriptions') }}</h3>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Service Block Three -->
				<div class="service-block-three col-lg-4 col-md-6 col-sm-12">
					<div class="inner-box">
						<div class="overlay-box">
							<div class="overlay-inner">
								<div class="overlay-content">
									<div class="content">
										<div class="icon-box">
											<span class="icon flaticon-deal"></span>
										</div>
										<h3>{{ cmsValue('home', 'care_tasks', 'item_6_title', 'Keeping Them Safe and Comfortable') }}</h3>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</section>
	<!-- End Services Section Three -->

	<!-- Fluid Section One -->
    <section class="fluid-section-one">
    	<div class="outer-container clearfix">

			<!--Content Column-->
			<div class="content-column">
				<div class="image-layer" :style="{ backgroundImage: `url(${cmsValue('home', 'mental_wellbeing', 'pattern_image_url', '/frontend/images/background/pattern-1.png')})` }"></div>
				<div class="content-box">
					<h2>{{ cmsValue('home', 'mental_wellbeing', 'headline', 'Supporting Mental Well-being') }}</h2>
					<div class="text">{{ cmsValue('home', 'mental_wellbeing', 'body_text', 'We understand the importance of maintaining mental health in elderly individuals. Our caregivers are trained not only in providing exceptional care but also in fostering positive relationships.') }}</div>
					<ul class="list-style-one">
						<li>{{ cmsValue('home', 'mental_wellbeing', 'list_item_1', 'Addressing age-related challenges') }}</li>
						<li>{{ cmsValue('home', 'mental_wellbeing', 'list_item_2', 'Cognitive health and memory support') }}</li>
						<li>{{ cmsValue('home', 'mental_wellbeing', 'list_item_3', 'Companionship and social engagement') }}</li>
						<li>{{ cmsValue('home', 'mental_wellbeing', 'list_item_4', 'Post-surgery recovery') }}</li>
						<li>{{ cmsValue('home', 'mental_wellbeing', 'list_item_5', 'Assisting with chronic conditions') }}</li>
						<li>{{ cmsValue('home', 'mental_wellbeing', 'list_item_6', 'Flexible care options for peace of mind') }}</li>
					</ul>
					<div class="bold-text">{{ cmsValue('home', 'mental_wellbeing', 'bold_text', 'Let us help you prioritise mental well-being for your loved one!') }}</div>
				</div>
			</div>

			<!--Image Column-->
        	<div class="image-column" :style="{ backgroundImage: `url(${cmsValue('home', 'mental_wellbeing', 'video_image_url', '/frontend/images/resource/video-img.jpg')})` }">
				<div class="inner-column">
					<div class="image">
						<img :src="cmsValue('home', 'mental_wellbeing', 'video_image_url', '/frontend/images/resource/video-img.jpg')" alt="">
					</div>
					<a :href="cmsValue('home', 'mental_wellbeing', 'video_url', 'https://www.youtube.com/watch?v=z-Ag8jll5nA')" class="overlay-link lightbox-image">
						<div class="icon-box">
							<span class="icon flaticon-play-button"></span>
						</div>
					</a>
				</div>
            </div>
            <!--End Image Column-->
		</div>
	</section>

	<!-- Call To Action Section -->
	<section class="call-to-action-section" :style="{ backgroundImage: `url(${cmsValue('home', 'movement_cta', 'background_image_url', '/frontend/images/background/1.png')})` }">
		<div class="auto-container">
			<h2>{{ cmsValue('home', 'movement_cta', 'headline', 'Share Your Cares. Inspire Others.') }}</h2>
			<div class="text">{{ cmsValue('home', 'movement_cta', 'subheadline', 'Join our movement to make the world a better place for seniors.') }}</div>
			<router-link :to="cmsValue('home', 'movement_cta', 'button_url', '/contact')" class="theme-btn btn-style-two">
				<span class="txt">{{ cmsValue('home', 'movement_cta', 'button_text', 'Contact Us') }}</span>
			</router-link>

			<!-- <a href="/contact" class="theme-btn btn-style-two"><span class="txt">contact us</span></a> -->
		</div>
	</section>
	<!-- End Call To Action Section -->

	    <!--====== Brand Slider Start ======-->
    <section class="brand-section pt-80 pb-80 ">
        <div class="container">
            <div class="brand-slider row">
				<v-col  v-for="(company, index) in companies" :key="index" cols="12" md="4" lg="3" class="d-flex justify-center">
					
					<v-avatar class="company-avatar" size="200" @click="openDialog(company)">
						<img :src="company.logo" :alt="company.name" />
					</v-avatar>

					<!-- Company Info Dialog -->
					<v-dialog v-model="dialog" max-width="400px">
						<v-card>
							<v-card-title>{{ selectedCompany.name }}</v-card-title>
							<v-card-text>
							<p>{{ selectedCompany.description }}</p>
							</v-card-text>
							<v-card-actions>
							<v-btn text @click="dialog = false">Close</v-btn>
							</v-card-actions>
						</v-card>
					</v-dialog>
				</v-col>
            </div>
        </div>
    </section>

	<!-- Social values Section -->
	<!-- <section class="socialvalues-section pt-80 pb-80 soft-blue-bg">
		<div class="container">  
				<div class="carousel-container">
					<v-carousel v-model="activeCarouselItem" hide-delimiter-background show-arrows-on-hover cycle interval="3000">
						<v-carousel-item v-for="(company, index) in companies" :key="index" cover>
							<v-avatar class="company-avatar" size="200" @click="openDialog(company)">
								<img :src="company.logo" :alt="company.name" />
							</v-avatar>
						</v-carousel-item>
					</v-carousel>
				</div> -->

				<!-- Company Info Dialog -->
				<!-- <v-dialog v-model="dialog" max-width="400px">
					<v-card>
						<v-card-title>{{ selectedCompany.name }}</v-card-title>
						<v-card-text>
						<p>{{ selectedCompany.description }}</p>
						</v-card-text>
						<v-card-actions>
						<v-btn text @click="dialog = false">Close</v-btn>
						</v-card-actions>
					</v-card>
				</v-dialog>
		</div>
	</section> -->

	<!-- Contact Form Section -->
	<section id="contact" class="contact-form-section">
		<div class="auto-container">

			<!-- Title Box -->
			<div class="title-box">
				<h2>{{ cmsValue('home', 'contact_section', 'headline', 'Get In Touch') }}</h2>
				<div class="bold-text">{{ cmsValue('home', 'contact_section', 'subheadline', 'For further details on any of our services or to arrange a free initial consultation you can reach us via any of the methods below') }}</div>
				<!-- <div class="required">Fields marked with an * are required</div> -->
			</div>

			<div class="row clearfix">

				<!-- Form Column -->
				<div class="form-column col-lg-6 col-md-12 col-sm-12">
					<div class="inner-column">

						<!-- Default Form -->
						<div class="default-form contact-form">
							<form @submit.prevent="sendEmail" >

								<div class="form-group">
									<!-- <input type="text" name="username" value="" placeholder="Name*" required> -->
									<v-text-field v-model="formData.username" hide-details="auto" label="Names" placeholder="John Doe" type="text" required></v-text-field>
								</div>

								<div class="form-group">
									<!-- <input type="text" name="phone" value="" placeholder="Phone Number*" required> -->
									<v-text-field v-model="formData.phone" label="Phone Number" type="text" required></v-text-field>
								</div>

								<div class="form-group">
									<!-- <input type="text" name="email" value="" placeholder="Email*" required> -->
									<v-text-field v-model="formData.email" hide-details="auto" label="Email address" placeholder="johndoe@gmail.com" type="email" required></v-text-field>
								</div>

								<div class="form-group">
									<!-- <textarea name="message" placeholder="Your Message"></textarea> -->
									<v-textarea v-model="formData.message" hide-details="auto" placeholder="Your Message" type="email" required></v-textarea>
								</div>

								<!--div class="form-group">
									<span class="captcha">8 + 4*</span>
									<input type="text" name="subject" value="" placeholder="" required>
								</div-->

								<div class="form-group">
									<button type="submit" class="theme-btn btn-style-one" ><span class="txt">{{ cmsValue('home', 'contact_section', 'button_text', 'Send Message') }}</span></button>
								</div>
							</form>
							
							<div v-if="responseMessage">{{ responseMessage }}</div>
						</div>
						<!--End Default Form-->

					</div>
				</div>

				<!-- Info Column -->
				<div class="info-column col-lg-6 col-md-12 col-sm-12">
					<div class="inner-column">
						<div class="image">
							<img :src="cmsValue('home', 'contact_section', 'info_image_url', '/frontend/images/resource/contact-1.jpg')" alt="" />
						</div>
						<h3>{{ cmsValue('home', 'contact_section', 'office_heading', 'Head Office:') }}</h3>
						<div class="text">{{ cmsValue('home', 'contact_section', 'office_address', 'Ground Floor, suite A, 53-55 Butts Road, Coventry CV1 3BH') }}</div>
						<ul>
							<li>Tel: <a :href="`tel:${String(cmsValue('home', 'contact_section', 'office_phone', '024 7623 1188')).replace(/\s+/g, '')}`">{{ cmsValue('home', 'contact_section', 'office_phone', '024 7623 1188') }}</a></li>
                            <li>Email :<a :href="`mailto:${cmsValue('home', 'contact_section', 'office_email', 'enquiries@facilitatecareservice.co.uk')}`"> {{ cmsValue('home', 'contact_section', 'office_email', 'enquiries@facilitatecareservice.co.uk') }}</a></li>
                            <li>Opening Hours: <a href="">{{ cmsValue('home', 'contact_section', 'office_hours', '9 am to 5 pm Monday to Friday. (24 hr on call service)') }}</a></li>
						</ul>
					</div>
				</div>

			</div>
		</div>
	</section>
	<!-- End Contact Form Section -->
	<!-- Postal Section -->
	<!-- <section class="postal-section margin-top" style="background-image: url(/frontend/images/background/2.png);">
		<div class="auto-container">
			<div class="clearfix">
				<div class="post-image">
					<img src="/frontend/images/resource/post-1.jpg" alt="" />
				</div>
				<div class="post-image image-two">
					<img src="/frontend/images/resource/post-2.jpg" alt="" />
				</div> -->
				<!-- Postal Box -->
				<!-- <div class="post-box">
					<h3>Request a call back.</h3>
					<div class="postal-form">
						<form method="post" action="contact.html">
              <div class="form-group">
								<input type="text" name="text" value="" placeholder="Phone Number" required>
								<button type="submit" class="theme-btn">Go</button>
							</div>
						</form>
					</div> -->
					<!--div class="text">See full List of Locations</div-->
				<!-- </div>

			</div>
		</div>
	</section> -->
	<!-- End Postal Section -->

	<!-- Map Section -->
    <section class="map-section">
        <div class="outer-container">
            <div class="map-outer">
                <div class="map-canvas">
                    <iframe
                        :src="homeMapEmbedUrl()"
                        title="Facilitate Care Services location"
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
                                        	<a href="/"><img :src="footerValue('logo_url', '/frontend/images/footer-logo.png')" alt="" /></a>
                                        </div>
                                        <div class="text">{{ footerValue('tagline', 'Suporting Your Independence.') }}</div>
                                        <ul class="social-icons">
                                            <li><a href="https://www.facebook.com/p/Facilitate-Care-Services-100070370806760/" target="_blank"><span class="fab fa-facebook-f"></span></a></li>
                                            <li><a href="https://www.linkedin.com/in/facilitate-care-services-672aa1327/" target="_blank"><span class="fab fa-linkedin-in"></span></a></li>
                                            <li><a href="https://x.com/facilitatecare/" target="_blank"><span class="fab fa-twitter"></span></a></li>
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


<!-- Footer Column -->
<div class="footer-column col-lg-4 col-md-4 col-sm-12" aria-labelledby="cqc-heading">
  <div class="footer-widget newsletter-widget">
    <h2 id="cqc-heading">{{ footerValue('cqc_heading', 'Care Quality Commission') }}</h2>

    <div class="logo">
      <a :href="footerCqcHref()" target="_blank" rel="noopener">
        <img :src="footerValue('cqc_badge_image_url', '/frontend/images/CQC rating.jpg')" alt="" />
      </a>
    </div>

    <div class="text">{{ footerValue('cqc_text', 'Latest Inspection 25 May 2021') }}</div>
    <div v-if="footerValue('cqc_secondary_text', '')" class="text">{{ footerValue('cqc_secondary_text', '') }}</div>

    <div class="newsletter-form">
      <div class="btn-box">
        <a :href="footerCqcHref()"
           class="theme-btn btn-style-one"
           target="_blank" rel="noopener">
          <span class="txt">{{ footerValue('cqc_button_text', 'See Report') }}</span>
        </a>
      </div>
    </div>
  </div>
</div>

    				</div>
    			</div>

    			<!--Footer Bottom-->
                <div class="footer-bottom clearfix">
                    <div class="pull-left">
                        <div class="copyright">{{ footerValue('copyright_text', '\u00a9 Copyright Facilitate care services 2024. All right reserved.') }}</div>
                    </div>
                </div>

    		</div>
    	</footer>
</div>
<!--End pagewrapper-->

<!--Scroll to top-->
<div class="scroll-to-top scroll-to-target" data-target="html"><span class="fa fa-angle-up"></span></div>
<v-snackbar
	v-model="publicSnackbar.show"
	:color="publicSnackbar.color"
	timeout="3500"
	location="bottom right"
>
	{{ publicSnackbar.text }}
	<template #actions>
		<v-btn color="white" variant="text" @click="publicSnackbar.show = false">Close</v-btn>
	</template>
</v-snackbar>
</v-app>
</template>

<script>
import axios from 'axios';
import { mapActions } from 'vuex';
import { describeAuthApiError, loginUser } from '../services/authApi';
import { firstAllowedRouteName } from '../utils/accessControl';

const isLocalHost =
	typeof window !== 'undefined' &&
	(window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1');

const LOCAL_API_BASE = 'http://localhost/facilitate/src/php';
const LIVE_API_BASE = 'https://facilitatecareservices.co.uk/php';
const API_BASE = isLocalHost ? LOCAL_API_BASE : LIVE_API_BASE;

export default {
	name: 'Home',
	data() {
		return {
			formData: {
				email: '',
				username: '',
				phone: '',
				message: '',
			},

			titlecb: '',
			FNametc: '',
			SNametc: '',
			mailtc: '',
			phonetc: '',
			cnametc: '',
			messagetc: '',
				
			complaintTitle: '',
			complaintFName: '',
			complaintSName: '',
			complaintEmail: '',
			complaintPhone: '',
			complaintMsg: '',

			email: null,
       		password: null,
			showPassword: false,
			responseMessage: '',
			publicSnackbar: {
				show: false,
				text: '',
				color: 'success',
			},
			error: "",
			dialog: false,
			complaintDialog: false,
			caregiverDialog: false,
			logindialog: false,

			activeCarouselItem: 0,
			dialog: false,
			selectedCompany: {},
			companies: [
				{ name: "Coventry Food Bank", logo: "/frontend/images/resource/Coventry-Foodbank.png", description: "Info about Company A" },
				{ name: "Circus Starr", logo: "/frontend/images/resource/circus-starr.png", description: "Info about Company B" },
				{ name: "Macmillan Cancer Support", logo: "/frontend/images/resource/macmillan-cancer-support.png", description: "Info about Company C" },
			],
			cmsContent: {},
			cmsLoaded: false,
		}
	},

	created() {
		this.loadCmsContent();
	},

	beforeUnmount() {
		this.closeMobileMenu();
	},

	watch: {
		$route() {
			this.closeMobileMenu();
		},
	},

	methods: {

		...mapActions(['saveComplaint', 'saveContactForm']),

		apiUrl(action) {
			return `${API_BASE}/websiteContent.php?action=${action}`;
		},

		applyCmsTree(tree) {
			this.cmsContent = tree && typeof tree === 'object' ? tree : {};
		},

		resolveCmsField(pageKey, sectionKey, fieldKey) {
			const normalizedPageKey = String(pageKey || '').trim().toLowerCase();
			const normalizedSectionKey = String(sectionKey || '').trim().toLowerCase();
			const normalizedFieldKey = String(fieldKey || '');
			const candidates = [];

			if (normalizedSectionKey === 'footer' && normalizedPageKey !== 'global') {
				candidates.push(['global', 'footer']);
			}
			candidates.push([normalizedPageKey, normalizedSectionKey]);

			for (const [candidatePageKey, candidateSectionKey] of candidates) {
				const page = this.cmsContent[candidatePageKey];
				const section = page ? page[candidateSectionKey] : null;
				const field = section ? section[normalizedFieldKey] : null;
				if (typeof field !== 'undefined' && field !== null) {
					return field;
				}
			}

			return null;
		},

		hasCmsField(pageKey, sectionKey, fieldKey) {
			return Boolean(this.resolveCmsField(pageKey, sectionKey, fieldKey));
		},

		isCmsFieldVisible(pageKey, sectionKey, fieldKey) {
			return !this.cmsLoaded || this.hasCmsField(pageKey, sectionKey, fieldKey);
		},

		cmsValue(pageKey, sectionKey, fieldKey, fallback = '') {
			const field = this.resolveCmsField(pageKey, sectionKey, fieldKey);
			const value = field && typeof field.value !== 'undefined' ? field.value : fallback;
			return typeof value === 'string' ? value : String(value !== null && typeof value !== 'undefined' ? value : fallback);
		},

		footerValue(fieldKey, fallback = '') {
			return this.cmsValue('global', 'footer', fieldKey, fallback);
		},

		footerPhoneHref() {
			const phone = this.footerValue('contact_phone', '024 7623 1188');
			const normalizedPhone = String(phone || '').replace(/\s+/g, '');
			return `tel:${normalizedPhone || '02476231188'}`;
		},

		footerMailtoHref() {
			const email = this.footerValue('contact_email', 'info@facilitatecareservices.co.uk');
			return `mailto:${String(email || 'info@facilitatecareservices.co.uk')}`;
		},

		footerCqcHref() {
			return this.footerValue('cqc_url', 'https://www.cqc.org.uk/location/1-2131286214');
		},

		homeMapNumber(fieldKey, fallback) {
			const rawValue = Number.parseFloat(this.cmsValue('home', 'map_section', fieldKey, String(fallback)));
			return Number.isFinite(rawValue) ? rawValue : fallback;
		},

		homeMapZoom() {
			const rawZoom = Number.parseInt(this.cmsValue('home', 'map_section', 'zoom', '18'), 10);
			if (!Number.isFinite(rawZoom)) {
				return 18;
			}
			return Math.min(20, Math.max(3, rawZoom));
		},

		homeMapType() {
			const rawType = String(this.cmsValue('home', 'map_section', 'map_type', 'satellite') || '').trim().toLowerCase();
			return rawType === 'roadmap' ? 'roadmap' : 'satellite';
		},

		homeMapEmbedUrl() {
			const latitude = this.homeMapNumber('latitude', 52.4056402);
			const longitude = this.homeMapNumber('longitude', -1.5236883);
			const zoom = this.homeMapZoom();
			const mapType = this.homeMapType();
			const satelliteParam = mapType === 'satellite' ? '&t=k' : '';
			return `https://www.google.com/maps?q=${encodeURIComponent(`${latitude},${longitude}`)}&z=${zoom}${satelliteParam}&output=embed`;
		},

		phoneHref() {
			const phone = this.cmsValue('global', 'header', 'phone', '024 7623 1188');
			const normalizedPhone = String(phone || '').replace(/\s+/g, '');
			return `tel:${normalizedPhone || '02476231188'}`;
		},

		async loadCmsContent() {
			try {
				const response = await axios.get(this.apiUrl('getPublic'));
				const payload = response && response.data ? response.data : {};
				if (payload.success) {
					this.applyCmsTree(payload.content || {});
					this.syncCompanyContent();
				}
			} catch (error) {
				console.warn('Unable to load public CMS content for Home page.', error);
			} finally {
				this.cmsLoaded = true;
			}
		},

		syncCompanyContent() {
			this.companies = [
				{
					name: this.cmsValue('home', 'partners', 'company_1_name', 'Coventry Food Bank'),
					logo: this.cmsValue('home', 'partners', 'company_1_logo_url', '/frontend/images/resource/Coventry-Foodbank.png'),
					description: this.cmsValue('home', 'partners', 'company_1_description', 'Info about Company A'),
				},
				{
					name: this.cmsValue('home', 'partners', 'company_2_name', 'Circus Starr'),
					logo: this.cmsValue('home', 'partners', 'company_2_logo_url', '/frontend/images/resource/circus-starr.png'),
					description: this.cmsValue('home', 'partners', 'company_2_description', 'Info about Company B'),
				},
				{
					name: this.cmsValue('home', 'partners', 'company_3_name', 'Macmillan Cancer Support'),
					logo: this.cmsValue('home', 'partners', 'company_3_logo_url', '/frontend/images/resource/macmillan-cancer-support.png'),
					description: this.cmsValue('home', 'partners', 'company_3_description', 'Info about Company C'),
				},
			];
		},

		openComplaintDialog() {
            this.complaintDialog = true;
        },

		openMobileMenu() {
			if (typeof document !== 'undefined') {
				document.body.classList.add('mobile-menu-visible');
			}
		},

		closeMobileMenu() {
			if (typeof document !== 'undefined') {
				document.body.classList.remove('mobile-menu-visible');
			}
		},

		togglePasswordVisibility() {
			this.showPassword = !this.showPassword;
		},

		openForgotPassword() {
			this.logindialog = false;
			this.$router.push({ path: '/login', query: { mode: 'forgot' } });
		},

		openCaregiverDialog() {
            this.caregiverDialog = true;
        },

		showPublicSnackbar(message, type = 'success') {
			this.publicSnackbar = {
				show: true,
				text: String(message || '').trim() || 'Request completed.',
				color: type === 'error' ? 'error' : 'success',
			};
		},

		closethanksDialog() {
			this.caregiverDialog = false;
			this.titlecb= '';
			this.FNametc= '';
			this.SNametc= '';
			this.mailtc= '';
			this.phonetc= '';
			this.cnametc= '';
			this.messagetc= ''
		},

		closecomplaintDialog() {
			this.complaintDialog = false;
			this.complaintTitle= '';
			this.complaintFName= '';
			this.complaintSName= '';
			this.complaintEmail= '';
			this.complaintPhone= '';
			this.complaintMsg= ''
		},

		openDialog(company) {
			this.selectedCompany = company;
			this.dialog = true;
		},
	
		async sendEmail() {
			const result = await this.saveContactForm({
				UserName: this.formData.username,
				Phonenumber: this.formData.phone,
				Email: this.formData.email,
				Message: this.formData.message,
				Date: new Date(),
			});

			this.responseMessage = result?.message || 'An error occurred. Please try again later.';
			if (!result || result.success === false) {
				this.showPublicSnackbar(this.responseMessage, 'error');
				return;
			}

			this.formData = {
				email: '',
				username: '',
				phone: '',
				message: '',
			};
			this.showPublicSnackbar(result?.message || 'Message sent successfully.', 'success');
		},

		async contactSave() {
			await this.sendEmail();
        },

		async Login() {
			try {
				const result = await loginUser({
					identifier: this.email,
					password: this.password,
				});
				const landingRoute = firstAllowedRouteName(result.user);

				this.$store.commit('showSnackbar', {
					message: result.message || 'Login successful.',
					type: 'success',
				});
				this.logindialog = false;
				if (landingRoute) {
					this.$router.push({ name: landingRoute });
				} else {
					this.$router.push('/dashboard');
				}
			} catch (error) {
				const message = describeAuthApiError(error, 'An error occurred. Please try again.');
				this.$store.commit('showSnackbar', {
					message,
					type: 'error',
				});
				window.alert(message);
				this.error = message;
			}
		},

		async thanksSave() {
			 const thanksData = {
				Title: this.titlecb,
                FirstName: this.FNametc,
				SecondName: this.SNametc,
                Email: this.mailtc,
                Phonenumber: this.phonetc,
				Carername: this.cnametc,
                Message: this.messagetc,
                Date: new Date()
			};

			try {
				const result = await this.$store.dispatch('saveThanks', thanksData);
				if (result && result.success === false) {
					this.showPublicSnackbar(result?.message || 'Failed to send thank you message.', 'error');
					return;
				}
				this.closethanksDialog();
				this.showPublicSnackbar(result?.message || 'Thank you message sent successfully.', 'success');
			} catch (error) {
				console.error('Failed to save message:', error);
				this.showPublicSnackbar('Failed to send thank you message. Please try again.', 'error');
			}
        },

		async complaintSave() {
			const complaintData = {
				Title: this.complaintTitle,
				FirstName: this.complaintFName,
				SecondName: this.complaintSName,
				Email: this.complaintEmail,
				Phonenumber: this.complaintPhone,
				Message: this.complaintMsg,
				Date: new Date()
			};

			try {
				const result = await this.saveComplaint(complaintData);
				if (result && result.success === false) {
					this.showPublicSnackbar(result?.message || 'Failed to submit concern.', 'error');
					return;
				}
				this.closecomplaintDialog();
				this.showPublicSnackbar(result?.message || 'Concern submitted successfully.', 'success');

			} catch (error) {
				console.error('Failed to save complaint:', error);
				this.showPublicSnackbar('Failed to submit concern. Please try again.', 'error');
			}
        }
	}
	 
};
</script>
<style scoped>
.uppercase-input input {
  text-transform: uppercase;
}

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

@media only screen and (max-width: 768px) {
  .hero-subheader {
    display: none;
  }

  .hero-cta {
    display: none;
  }
}

</style>
