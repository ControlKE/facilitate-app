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
    <section class="page-title" :style="{ backgroundImage: `url(${cmsValue('caregiver', 'hero', 'background_image_url', '/frontend/images/background/3.jpg')})` }">
    	<div class="auto-container">
        	<h2>{{ cmsValue('caregiver', 'hero', 'headline', 'CareGiver Jobs') }}</h2>
             <ul class="page-breadcrumb">
             	<li><a href="/">home</a></li>
                <li>{{ cmsValue('caregiver', 'hero', 'headline', 'Recruitment') }}</li>
            </ul>
        </div>
    </section>
    <!--End Page Title-->

	<!-- Caregiver Container -->
    <div class="caregiver-container">
    	<div class="auto-container">
			<div class="upper-content">
				<h2>{{ cmsValue('caregiver', 'content', 'headline', 'Caregiver Jobs - Caregivers and Companions Needed') }}</h2>
				<!-- <div class="style-text">Caregivers Vote Visiting Angels The Best Place to Work </div> -->
				<div class="text">{{ cmsValue('caregiver', 'content', 'intro_text_1', 'We know how important our caregivers are. They are the backbone of our business. Without our team of compassionate caregivers, we would not be able to help our clients the way we do. We try to make a difference for everyone we are involved with; the people who use our service, our staff and our strategic partners. The approach is one based on open and honest communication, transparency and the highest professional standards aiming to making a difference in caring at home.') }}</div>
				<div class="text">{{ cmsValue('caregiver', 'content', 'intro_text_2', 'If you keen to be involved in a caring role that provides quality support to people at home, and you have compassion for individuals in a diverse range of situations, we would really like you to join our growing team.') }}</div>
				<div class="image">
					<img :src="cmsValue('caregiver', 'content', 'primary_image_url', '/frontend/images/resource/care-4.jpg')" alt="" />
				</div>
			</div>
      <div class="lower-content">
        <h3>{{ cmsValue('caregiver', 'content', 'offer_headline', 'What Facilitate can offer.') }}</h3>
        <ul class="list-style-four">
          <li>{{ cmsValue('caregiver', 'content', 'offer_item_1', 'Supportive working environment.') }}</li>
          <li>{{ cmsValue('caregiver', 'content', 'offer_item_2', 'Full training to undertake the role and meet legislation.') }}</li>
          <li>{{ cmsValue('caregiver', 'content', 'offer_item_3', 'Flexible working hours available, if required.') }}</li>
          <li>{{ cmsValue('caregiver', 'content', 'offer_item_4', 'Training towards a recognised qualification in care.') }}</li>
          <li>{{ cmsValue('caregiver', 'content', 'offer_item_5', 'Competitive rates of pay (please contact our office for enquiries).') }}</li>
        </ul>
    </div>
    <div class="text">
      {{ cmsValue('caregiver', 'content', 'requirements_text_1', 'Experience would be an advantage but it not essential. Comprehensive and paid training will be provided for you during the first two weeks of the recruitment process.') }}
      <br>{{ cmsValue('caregiver', 'content', 'requirements_text_2', 'The job role requires you to: provide personal care, perform domestic duties, promote independence, dignity & well-being and generally support people who use our service in their own homes.') }}
      <br>{{ cmsValue('caregiver', 'content', 'requirements_text_3', 'Driving license and own transport would be advantageous if you looking for a greater number of hours. Successful applicants are required to provide a DBS Enhanced Disclosure.') }}
      <br>{{ cmsValue('caregiver', 'content', 'requirements_text_4', 'Facilitate Care Services Limited is an Equal Opportunities employer.') }}
    </div>

			<div class="lower-content">
				<h3>{{ cmsValue('caregiver', 'content', 'roles_headline', 'Caregiver roles and responsibilities.') }}</h3>
                <div class="text">{{ cmsValue('caregiver', 'content', 'roles_text_1', 'The role of the care/support worker is to promote the opportunity for service users to live in the community as long as possible, by providing care and support to the individuals and their families, and to enable service users to live as independently, comfortably and securely as possible.') }}
                    <br><br>{{ cmsValue('caregiver', 'content', 'roles_text_2', 'We expect our care/support workers to take individual responsibility for the delivery of personal and practical care needs of service users, in line with the practices, procedures and policies of Facilitate Care Services.') }}
                    <br><br>{{ cmsValue('caregiver', 'content', 'roles_text_3', 'The post holder will undertake all care work with the sensitivity and empathy required to provide services in a way which will preserve the dignity, privacy, choice, independence, fulfilment and rights of the service user and his/her usual carer/members of family.') }}
                    <br><br>{{ cmsValue('caregiver', 'content', 'roles_text_4', 'Please note that we fully vet and check all our employees credentials and a criminal disclosure is undertaken prior to commencement of employment at a fee.') }}
                    <br><br>{{ cmsValue('caregiver', 'content', 'roles_text_5', 'It will also be necessary for us to obtain two written references with one being from your current or most recent employer.') }}
                </div>
				<div class="text">{{ cmsValue('caregiver', 'content', 'duties_intro_text', 'Our caregivers provide compassionate support, helping clients with daily living activities while ensuring their dignity and well-being. As a caregiver, your role may include the following tasks:') }}
				</div>
				<ul class="list-style-four">
					<li>{{ cmsValue('caregiver', 'content', 'duty_item_1', 'Assisting clients with personal care services such as oral care, bathing, toileting, dressing, and grooming') }}</li>
					<li>{{ cmsValue('caregiver', 'content', 'duty_item_2', 'Medication reminders') }}</li>
					<li>{{ cmsValue('caregiver', 'content', 'duty_item_3', 'Light housekeeping including laundry, dishes and vacuuming') }}</li>
					<li>{{ cmsValue('caregiver', 'content', 'duty_item_4', 'Planning, preparing and serving meals') }}</li>
					<li>{{ cmsValue('caregiver', 'content', 'duty_item_5', 'Assistance with transfers, walking and physical activity') }}</li>
					<li>{{ cmsValue('caregiver', 'content', 'duty_item_6', 'Assistance with transportation to appointments, activities, errands, and shopping') }}</li>
					<li>{{ cmsValue('caregiver', 'content', 'duty_item_7', 'Providing companionship and cheerful, positive assistance at all times') }}</li>
				</ul>
		    </div>
            	<!-- Call To Action Section Two -->
	<section class="call-to-action-section-two style-three">
		<div class="auto-container">
			<div class="inner-container">
				<h2>{{ cmsValue('caregiver', 'content', 'apply_headline', 'How To Apply') }}</h2>
				<div class="text">{{ cmsValue('caregiver', 'content', 'apply_text', 'Interested in joining our team? Click the button below to fill out a quick application form and share your qualifications with us. We look forward to learning more about you!') }}</div>
				<!--a href="contact.html" class="find">Find our office near you.</a-->
        <div class="outer-box clearfix">
            <!-- Button Box -->
            <div class="btn-box">
                <div class="pa-0 text-center">
                    <v-dialog v-model="jobapplicationDialog" max-width="600px">
                        <template v-slot:activator="{ props: activatorProps }">
                            <div class="theme-btn btn-style-one" v-bind="activatorProps"><span class="txt">{{ cmsValue('caregiver', 'content', 'apply_button_text', 'Apply Here') }}</span></div>
                        </template>

                        <v-card max-width="600px" >
                            <v-toolbar color="#AB207D" title="Care Assistant Job Application"></v-toolbar>
                            <v-card-text>
                                <v-row dense>
                                    <v-col cols="12" md="6" sm="4">
                                        <v-combobox v-model="userTitle" label="Your Title*" :items="['Mr.', 'Mrs.', 'Ms.', 'Miss']" variant="outlined"></v-combobox>
                                    </v-col>
                                    <v-col cols="12" md="6" sm="4">
                                        <v-text-field v-model="userNames" label="Full Names*" variant="outlined" required></v-text-field>
                                    </v-col>
                                </v-row>
                                <v-row dense>
                                    <v-col cols="12" md="6" sm="4">
                                        <v-text-field v-model="userPhone" label="Your PhoneNumber*" variant="outlined" required></v-text-field>
                                    </v-col>
                                    <v-col cols="12" md="6" sm="4">
                                        <v-text-field v-model="userEmail" label="Your Email" required variant="outlined"></v-text-field>
                                    </v-col>
                                </v-row>
								
                                <v-select v-model="jobtype" :items="type"  label="Job Type" persistent-hint required variant="outlined"></v-select>
                                <v-select v-model="jobexperience" :items="experience"  label="Do you have experience in Domiciliary Care?" required variant="outlined"></v-select>
                                <v-text-field v-model="userExperience" v-if="jobexperience === 'YES'" label="How many months/years of experience?" variant="outlined" required ></v-text-field>
                                <v-row dense>
                                    <v-col cols="12" md="6" sm="4">
                                        <v-select v-model="driverlicense" :items="license"  label="Do you have a valid drivers license?" required variant="outlined"></v-select>
                                    </v-col>
                                    <v-col cols="12" md="6" sm="4">
                                        <v-select v-if="driverlicense === 'YES'" v-model="licensetype" :items="typelicense"  label="License Type" required variant="outlined"></v-select>
                                    </v-col>
                                </v-row>
								<v-date-input v-model="expiryDate" v-if="driverlicense === 'YES' && licensetype === 'INTERNATIONAL LICENSE'" label="Please enter the expiry date of your international license?" prepend-icon="" prepend-inner-icon="$calendar" variant="outlined"></v-date-input>
                                <v-select v-if="driverlicense === 'YES' && licensetype === 'UK LICENSE'" v-model="uktype" :items="uklicensetype" label="Please select the type of UK License?" variant="outlined" required ></v-select>
                                <v-select v-model="city" :items="citylist"  label="Where do you currently live?" required variant="outlined"></v-select>
                                <v-text-field v-model="userResidence" v-if="city === 'Other'" label="Please enter your area of residence?" variant="outlined" required ></v-text-field>

                                <v-text-field v-model="residenceDuration" label="How long have you lived at your current location?" required variant="outlined" ></v-text-field>
                            </v-card-text>

                            <v-divider></v-divider>
                            
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color="primary" text="CANCEL"  @click="jobapplicationDialog = false"></v-btn>
                                <v-btn color="success" text="SEND" :loading="savingJobApplication" :disabled="savingJobApplication" @click="submitJobApplication"></v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                </div>
                <!-- <router-link to="/contact" class="theme-btn btn-style-one"><span class="txt">Apply Here</span></router-link> -->
            </div>
        </div>
			</div>
		</div>
	</section>
	<!-- End Call To Action Section Two -->

	<!-- Mileage Submission Section -->
	<section class="call-to-action-section-two style-three">
		<div class="auto-container">
			<div class="inner-container">
				<h2>Already Working With Us? Submit Your Mileage</h2>
				<div class="text">Log your mileage day by day, matching your paper mileage log -- no account or login needed. The office will review your submission before payment.</div>
				<div class="outer-box clearfix">
					<div class="btn-box">
						<div class="pa-0 text-center">
							<v-dialog v-model="mileageDialog" max-width="920px">
								<template v-slot:activator="{ props: activatorProps }">
									<div class="theme-btn btn-style-one" v-bind="activatorProps"><span class="txt">Submit Mileage</span></div>
								</template>

								<v-card max-width="920px">
									<v-toolbar color="#AB207D" title="Submit Your Mileage"></v-toolbar>
									<v-card-text>
										<v-row dense>
											<v-col cols="12" md="6">
												<v-text-field v-model="mileageDriverName" label="Your Full Name*" variant="outlined" required></v-text-field>
											</v-col>
											<v-col cols="12" md="6">
												<v-text-field v-model="mileagePhone" label="Phone Number" variant="outlined"></v-text-field>
											</v-col>
										</v-row>
										<v-text-field v-model="mileageEmail" label="Email (optional, for confirmation)" variant="outlined"></v-text-field>

										<div class="text-subtitle-2 mb-2">Add one row per day, matching your paper mileage log, then submit the whole batch. Only Date and Mileage are required -- the rest are optional.</div>
										<v-row dense>
											<v-col cols="12" md="3">
												<v-text-field v-model="mileageWorkDate" label="Date*" type="date" variant="outlined" density="compact" hide-details></v-text-field>
											</v-col>
											<v-col cols="12" md="3">
												<v-text-field v-model="mileageStartingLocation" label="Starting Location" variant="outlined" density="compact" hide-details></v-text-field>
											</v-col>
											<v-col cols="12" md="3">
												<v-text-field v-model="mileageDestination" label="Destination" variant="outlined" density="compact" hide-details></v-text-field>
											</v-col>
											<v-col cols="12" md="3">
												<v-text-field v-model="mileageMiles" label="Mileage*" type="number" min="0" variant="outlined" density="compact" hide-details></v-text-field>
											</v-col>
											<v-col cols="12" md="3">
												<v-text-field v-model.number="mileageOdometerStart" label="Odometer Start" type="number" min="0" variant="outlined" density="compact" hide-details></v-text-field>
											</v-col>
											<v-col cols="12" md="3">
												<v-text-field v-model.number="mileageOdometerEnd" label="Odometer End" type="number" min="0" variant="outlined" density="compact" hide-details></v-text-field>
											</v-col>
											<v-col cols="12" md="5">
												<v-text-field v-model="mileageNotes" label="Description / Notes" variant="outlined" density="compact" hide-details></v-text-field>
											</v-col>
											<v-col cols="12" md="1" class="text-center">
												<v-btn color="primary" variant="tonal" icon="mdi-plus" title="Add entry" @click="addMileageEntryRow"></v-btn>
											</v-col>
										</v-row>

										<div v-if="mileageEntries.length" class="mt-4" style="overflow-x: auto">
											<v-table density="compact">
												<thead><tr><th>Date</th><th>From</th><th>To</th><th>Odo Start</th><th>Odo End</th><th>Mileage</th><th>Notes</th><th style="width:48px"></th></tr></thead>
												<tbody>
													<tr v-for="(row, i) in mileageEntries" :key="i">
														<td>{{ row.workDate }}</td>
														<td>{{ row.startingLocation }}</td>
														<td>{{ row.destination }}</td>
														<td>{{ row.odometerStart }}</td>
														<td>{{ row.odometerEnd }}</td>
														<td>{{ row.mileage }}</td>
														<td>{{ row.notes }}</td>
														<td><v-btn size="small" variant="text" icon="mdi-delete" @click="mileageEntries.splice(i, 1)"></v-btn></td>
													</tr>
												</tbody>
											</v-table>
										</div>
										<div v-else class="text-caption text-medium-emphasis mt-3">
											No entries added yet -- fill in a date and mileage above, then click the + button.
										</div>
									</v-card-text>

									<v-divider></v-divider>

									<v-card-actions>
										<v-spacer></v-spacer>
										<v-btn color="primary" text="CANCEL" @click="mileageDialog = false"></v-btn>
										<v-btn
											color="success"
											:text="`SUBMIT (${mileageEntries.length})`"
											:loading="savingMileage"
											:disabled="savingMileage"
											@click="submitDriverMileageForm"
										></v-btn>
									</v-card-actions>
								</v-card>
							</v-dialog>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	<!-- End Mileage Submission Section -->

    <div class="lower-content">
    <!-- <h3>How to apply.</h3>
    <div class="light-text">If you feel you have the right attributes to work for us and desire to succeed within the friendly team here at Facilitate Care Services then why not contact us by using one of the following methods:
<br><br>Email your CV direct to info@facilitatecareservices.co.uk
<br><br>Call us on 024 7623 1188 to discuss the opportunities available or to simply request that an application form be sent to you.</div>-->
</div> 
<div class="lower-content">
<h3>{{ cmsValue('caregiver', 'content', 'contact_headline', 'Contact Us.') }}</h3>
<div class="text">
  {{ cmsValue('caregiver', 'content', 'contact_intro_text', 'For further details on any of our services or to arrange a free initial consultation you can reach us via any of the methods below:') }}
  <br><br>{{ cmsValue('caregiver', 'content', 'contact_address_line_1', 'Office GE13, 101 Lockhurst Lane') }}
  <br>{{ cmsValue('caregiver', 'content', 'contact_address_line_2', 'Coventry') }}
  <br>{{ cmsValue('caregiver', 'content', 'contact_address_line_3', 'CV6 5SF') }}
  <br><br>{{ cmsValue('caregiver', 'content', 'contact_phone_text', 'Tel: 024 7623 1188') }}
  <br>{{ cmsValue('caregiver', 'content', 'contact_email_text', 'Email: enquiries@facilitatecareservices.co.uk') }}
  <br>{{ cmsValue('caregiver', 'content', 'contact_hours_text', 'Opening Hours: 9 am to 5 pm Monday to Friday') }}
  <br>{{ cmsValue('caregiver', 'content', 'contact_on_call_text', '24hr on call service available') }}
</div>
<div class="text">{{ cmsValue('caregiver', 'content', 'contact_area_text', 'Our office is located in Coventry and we provide care services in Kenilworth, Leamington Spa, Warwick, Coventry, Bedworth, Stratford & Nuneaton.') }}</div>
<div class="text">{{ cmsValue('caregiver', 'content', 'contact_support_text', 'Our Care Managers are skilled at assessing personal and social needs and can advise you on the best support for your particular needs.') }}</div>
</div>
</div>
</div>

	<!-- End Caregiver Container -->

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
                        <div class="copyright">{{ cmsValue('home', 'footer', 'copyright_text', '� Copyright Facilitate care services 2024. All right reserved.') }}</div>
                    </div>
                </div>

    		</div>
    	</footer>

</div>
<!--End pagewrapper-->

<!--Scroll to top-->
<div class="scroll-to-top scroll-to-target" data-target="html"><span class="fa fa-angle-up"></span></div>

<v-snackbar
	v-model="formSnackbar.show"
	:color="formSnackbar.type"
	timeout="3500"
	location="bottom right"
>
	{{ formSnackbar.text }}
	<template #actions>
		<v-btn color="white" variant="text" @click="formSnackbar.show = false">Close</v-btn>
	</template>
</v-snackbar>
</template>

 
<script setup>
import { VDateInput } from 'vuetify/labs/VDateInput'
</script>
<script >
import axios from 'axios'
import { submitDriverMileage } from '../services/driverMileageService'


export default {
	data() {
		return {
            email: null,
			password: null,
			showPassword: false,
			error: '',
			caregiverDialog: false,
			complaintDialog: false,
			logindialog: false,
            dialog: false,
            jobapplicationDialog: false,
			savingJobApplication: false,
			formSnackbar: {
				show: false,
				text: '',
				type: 'success',
			},

			mileageDialog: false,
			savingMileage: false,
			mileageDriverName: '',
			mileagePhone: '',
			mileageEmail: '',
			mileageWorkDate: new Date().toISOString().slice(0, 10),
			mileageStartingLocation: '',
			mileageDestination: '',
			mileageOdometerStart: null,
			mileageOdometerEnd: null,
			mileageMiles: null,
			mileageNotes: '',
			mileageEntries: [],

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

			userTitle: '',
			userNames: '',
			userPhone: '',
			userEmail: '',
			jobtype: '',
			jobexperience: '',
			userExperience: '',
			driverlicense: '',
			licensetype: '',
			expiryDate: null,
			uktype: '',
			city: '',
			userResidence: '',
			residenceDuration: '',

            citylist: [
                'Coventry', 'Nuneaton', 'Bedworth', 'Leamington Spa', 'Warwick', 'Stratford Upon Avon', 'Rugby', 'Kenilworth', 'Other'
            ],
            license:[
                'YES', 'NO'
            ],
            experience: [
                'YES', 'NO'
            ],
            type: [
            'FULL TIME', 'PART TIME'
            ],
            typelicense:[
                'UK LICENSE', 'INTERNATIONAL LICENSE'
            ],
			uklicensetype: [
				'MANUAL', 'AUTOMATIC'
			],

		}
	},
	methods: {
        openComplaintDialog() {
            this.complaintDialog = true;
        },

		openCaregiverDialog() {
            this.caregiverDialog = true;
        },

		showFormFeedback(message, type = 'success') {
			this.formSnackbar.text = String(message || '').trim() || 'Request completed.';
			this.formSnackbar.type = type === 'error' ? 'error' : 'success';
			this.formSnackbar.show = true;
		},

		resetJobApplicationForm() {
			this.userTitle = '';
			this.userNames = '';
			this.userPhone = '';
			this.userEmail = '';
			this.jobtype = '';
			this.jobexperience = '';
			this.userExperience = '';
			this.driverlicense = '';
			this.licensetype = '';
			this.expiryDate = null;
			this.uktype = '';
			this.city = '';
			this.userResidence = '';
			this.residenceDuration = '';
		},

		formatDateForApi(value) {
			if (!value) {
				return null;
			}

			if (value instanceof Date && !Number.isNaN(value.getTime())) {
				return value.toISOString().slice(0, 10);
			}

			const parsed = new Date(value);
			if (Number.isNaN(parsed.getTime())) {
				return null;
			}

			return parsed.toISOString().slice(0, 10);
		},

		async submitJobApplication() {
			if (!this.userNames || !this.userPhone || !this.jobtype) {
				this.showFormFeedback('Full names, phone number, and job type are required.', 'error');
				return;
			}

			if (this.jobexperience === 'YES' && !this.userExperience) {
				this.showFormFeedback('Please enter your care experience duration.', 'error');
				return;
			}

			if (this.driverlicense === 'YES' && !this.licensetype) {
				this.showFormFeedback('Please select your license type.', 'error');
				return;
			}

			if (this.driverlicense === 'YES' && this.licensetype === 'INTERNATIONAL LICENSE' && !this.expiryDate) {
				this.showFormFeedback('Please provide your international license expiry date.', 'error');
				return;
			}

			if (this.driverlicense === 'YES' && this.licensetype === 'UK LICENSE' && !this.uktype) {
				this.showFormFeedback('Please select your UK license type.', 'error');
				return;
			}

			if (this.city === 'Other' && !this.userResidence) {
				this.showFormFeedback('Please enter your area of residence.', 'error');
				return;
			}

			this.savingJobApplication = true;
			try {
				const payload = {
					Title: this.userTitle,
					FullName: this.userNames,
					PhoneNumber: this.userPhone,
					Email: this.userEmail,
					JobType: this.jobtype,
					HasDomiciliaryExperience: this.jobexperience,
					ExperienceDuration: this.jobexperience === 'YES' ? this.userExperience : '',
					HasDriverLicense: this.driverlicense,
					LicenseType: this.driverlicense === 'YES' ? this.licensetype : '',
					InternationalLicenseExpiry: this.driverlicense === 'YES' && this.licensetype === 'INTERNATIONAL LICENSE'
						? this.formatDateForApi(this.expiryDate)
						: null,
					UkLicenseType: this.driverlicense === 'YES' && this.licensetype === 'UK LICENSE' ? this.uktype : '',
					City: this.city,
					ResidenceArea: this.city === 'Other' ? this.userResidence : '',
					ResidenceDuration: this.residenceDuration,
				};

				const result = await this.$store.dispatch('saveJobApplication', payload);
				if (!result || result.success === false) {
					this.showFormFeedback(result?.message || 'Failed to submit job application.', 'error');
					return;
				}

				this.jobapplicationDialog = false;
				this.resetJobApplicationForm();
				this.showFormFeedback(result?.message || 'Job application submitted successfully.', 'success');
			} catch (error) {
				console.error('Failed to submit job application:', error);
				const message = error?.response?.data?.message || error?.message || 'Failed to submit job application. Please try again.';
				this.showFormFeedback(message, 'error');
			} finally {
				this.savingJobApplication = false;
			}
		},

		resetMileageForm() {
			this.mileageDriverName = '';
			this.mileagePhone = '';
			this.mileageEmail = '';
			this.mileageWorkDate = new Date().toISOString().slice(0, 10);
			this.mileageStartingLocation = '';
			this.mileageDestination = '';
			this.mileageOdometerStart = null;
			this.mileageOdometerEnd = null;
			this.mileageMiles = null;
			this.mileageNotes = '';
			this.mileageEntries = [];
		},

		addMileageEntryRow() {
			if (!this.mileageWorkDate) {
				this.showFormFeedback('Please select the date for this entry.', 'error');
				return;
			}
			if (!this.mileageMiles || Number(this.mileageMiles) <= 0) {
				this.showFormFeedback('Please enter the mileage for this entry.', 'error');
				return;
			}

			this.mileageEntries.push({
				workDate: this.mileageWorkDate,
				startingLocation: this.mileageStartingLocation || '',
				destination: this.mileageDestination || '',
				odometerStart: this.mileageOdometerStart ?? '',
				odometerEnd: this.mileageOdometerEnd ?? '',
				mileage: Number(this.mileageMiles),
				notes: this.mileageNotes || '',
			});

			// Keep the driver's name/phone/email, but clear the row inputs so
			// the next day's entry starts fresh.
			this.mileageStartingLocation = '';
			this.mileageDestination = '';
			this.mileageOdometerStart = null;
			this.mileageOdometerEnd = null;
			this.mileageMiles = null;
			this.mileageNotes = '';
		},

		buildDriverMileageFormData(entry) {
			const formData = new FormData();
			formData.append('submissionType', 'single');
			formData.append('driverName', this.mileageDriverName);
			formData.append('phone', this.mileagePhone || '');
			formData.append('email', this.mileageEmail || '');
			formData.append('workDate', entry.workDate);
			formData.append('startingLocation', entry.startingLocation || '');
			formData.append('destination', entry.destination || '');
			formData.append('driverOdometerStart', entry.odometerStart === '' ? '' : String(entry.odometerStart));
			formData.append('driverOdometerEnd', entry.odometerEnd === '' ? '' : String(entry.odometerEnd));
			formData.append('mileage', String(entry.mileage));
			formData.append('notes', entry.notes || '');
			return formData;
		},

		async submitDriverMileageForm() {
			if (!this.mileageDriverName) {
				this.showFormFeedback('Please enter your name.', 'error');
				return;
			}

			// Convenience: if the driver filled in the row fields but forgot
			// to click the + button, add it automatically before submitting.
			if (!this.mileageEntries.length && this.mileageWorkDate && this.mileageMiles) {
				this.addMileageEntryRow();
			}
			if (!this.mileageEntries.length) {
				this.showFormFeedback('Please add at least one entry before submitting.', 'error');
				return;
			}

			this.savingMileage = true;
			try {
				let submittedCount = 0;

				// Submit sequentially and drop each row from the table only
				// once it has actually saved -- if one fails partway through,
				// the remaining un-submitted rows stay in the table so the
				// driver can retry without resending entries twice.
				while (this.mileageEntries.length) {
					const entry = this.mileageEntries[0];
					await submitDriverMileage(this.buildDriverMileageFormData(entry));
					this.mileageEntries.shift();
					submittedCount += 1;
				}

				this.mileageDialog = false;
				this.resetMileageForm();
				this.showFormFeedback(`${submittedCount} mileage ${submittedCount === 1 ? 'entry' : 'entries'} submitted successfully.`, 'success');
			} catch (error) {
				console.error('Failed to submit mileage:', error);
				const responseData = error?.response?.data;
				// debugError/debugErrorFile are only populated for localhost dev
				// origins (see submitMileageForm.php) -- safe to surface here.
				const debugDetail = responseData?.debugError
					? ` [${responseData.debugError}${responseData.debugErrorFile ? ' @ ' + responseData.debugErrorFile : ''}]`
					: '';
				const message = (responseData?.message || error?.message || 'Failed to submit mileage. Please try again.') + debugDetail;
				this.showFormFeedback(message, 'error');
			} finally {
				this.savingMileage = false;
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
        },

		closethanksDialog() {
			this.caregiverDialog = false;
			this.titlecb = '';
			this.FNametc = '';
			this.SNametc = '';
			this.mailtc = '';
			this.phonetc = '';
			this.cnametc = '';
			this.messagetc = '';
		},

		closecomplaintDialog() {
			this.complaintDialog = false;
			this.complaintTitle = '';
			this.complaintFName = '';
			this.complaintSName = '';
			this.complaintEmail = '';
			this.complaintPhone = '';
			this.complaintMsg = '';
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
					this.showFormFeedback(result?.message || 'Failed to send thank you message.', 'error');
					return;
				}
				this.closethanksDialog();
				this.showFormFeedback(result?.message || 'Thank you message sent successfully.', 'success');
			} catch (error) {
				console.error('Failed to save message:', error);
				this.showFormFeedback('Failed to send thank you message. Please try again.', 'error');
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
				Date: new Date(),
			};

			try {
				const result = await this.$store.dispatch('saveComplaint', complaintData);
				if (result && result.success === false) {
					this.showFormFeedback(result?.message || 'Failed to submit concern.', 'error');
					return;
				}
				this.closecomplaintDialog();
				this.showFormFeedback(result?.message || 'Concern submitted successfully.', 'success');
			} catch (error) {
				console.error('Failed to save complaint:', error);
				this.showFormFeedback('Failed to submit concern. Please try again.', 'error');
			}
		},
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
