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
    <section class="page-title" :style="{ backgroundImage: `url(${cmsValue('blog', 'hero', 'background_image_url', '/frontend/images/background/3.jpg')})` }">
    	<div class="auto-container">
        	<h2>{{ cmsValue('blog', 'hero', 'headline', 'Blog') }}</h2>
            <ul class="page-breadcrumb">
            	<li><a href="/">home</a></li>
                <li>{{ cmsValue('blog', 'hero', 'headline', 'Blog') }}</li>
            </ul>
        </div>
    </section>
    <!--End Page Title-->
	
	<!--Sidebar Page Container-->
    <div class="sidebar-page-container">
    	<div class="auto-container">
        	<div class="row clearfix">
            	
                <!--Content Side / Blog Classic -->
                <div class="content-side col-xl-9 col-lg-8 col-md-12 col-sm-12">
                	<div class="blog-classic padding-right">
						
						<!--News Block Two -->
						<div class="news-block-two">
							<div class="inner-box wow fadeInLeft" data-wow-delay="0ms" data-wow-duration="1500ms">
								<div class="image">
									<router-link to="/blogdetail"><img :src="cmsValue('blog', 'content', 'post_1_image_url', '/frontend/images/resource/news-4.jpg')" alt="" /></router-link>
								</div>
								<div class="lower-content">
									<div class="upper-box clearfix">
										<div class="pull-left">
											<div class="posted-date">{{ cmsValue('blog', 'content', 'post_1_date', '12 Feb. 2019') }}</div>
										</div>
										<div class="pull-right">
											<ul class="post-meta">
												<li>{{ cmsValue('blog', 'content', 'post_1_meta_author', 'By :  Admin') }}</li>
												<li>{{ cmsValue('blog', 'content', 'post_1_meta_category', 'Care, Senior Health') }}</li>
												<li>{{ cmsValue('blog', 'content', 'post_1_meta_comments', 'Comments: 7') }}</li>
											</ul>
										</div>
									</div>
									<div class="lower-box">
										<h3><router-link to="/blogdetail">{{ cmsValue('blog', 'content', 'post_1_title', 'The Magic of Quality Care') }}</router-link></h3>
										<div class="text">{{ cmsValue('blog', 'content', 'post_1_excerpt', 'Proactively envisioned multimedia based expertise and cross-media growth strategies. Seamlessly visualize quality intellectual capital without superior collaboration and idea-sharing. Holistically pontificate installed base portals after maintainable products. Interactively coordinate proactive e-commerce via process-centric "outside the box" thinking. Completely pursue scalable customer service ....') }}</div>
										<router-link to="/blogdetail" class="read-more">{{ cmsValue('blog', 'content', 'read_more_text', 'Continue Reading') }}</router-link>
									</div>
								</div>
							</div>
						</div>
						
						<!--News Block Two -->
						<div class="news-block-two">
							<div class="inner-box wow fadeInRight" data-wow-delay="0ms" data-wow-duration="1500ms">
								<div class="image">
									<router-link to="/blogdetail"><img :src="cmsValue('blog', 'content', 'post_2_image_url', '/frontend/images/resource/news-5.jpg')" alt="" /></router-link>
								</div>
								<div class="lower-content">
									<div class="upper-box clearfix">
										<div class="pull-left">
											<div class="posted-date">{{ cmsValue('blog', 'content', 'post_2_date', '12 Feb. 2019') }}</div>
										</div>
										<div class="pull-right">
											<ul class="post-meta">
												<li>{{ cmsValue('blog', 'content', 'post_2_meta_author', 'By :  Admin') }}</li>
												<li>{{ cmsValue('blog', 'content', 'post_2_meta_category', 'Care, Senior Health') }}</li>
												<li>{{ cmsValue('blog', 'content', 'post_2_meta_comments', 'Comments: 7') }}</li>
											</ul>
										</div>
									</div>
									<div class="lower-box">
										<h3><router-link to="/blogdetail">{{ cmsValue('blog', 'content', 'post_2_title', 'Top 5 Tips for Caregivers During the Holidays') }}</router-link></h3>
										<div class="text">{{ cmsValue('blog', 'content', 'post_2_excerpt', 'Proactively envisioned multimedia based expertise and cross-media growth strategies. Seamlessly visualize quality intellectual capital without superior collaboration and idea-sharing. Holistically pontificate installed base portals after maintainable products. Interactively coordinate proactive e-commerce via process-centric "outside the box" thinking. Completely pursue scalable customer service ....') }}</div>
										<router-link to="/blogdetail" class="read-more">{{ cmsValue('blog', 'content', 'read_more_text', 'Continue Reading') }}</router-link>
									</div>
								</div>
							</div>
						</div>
						
						<!--News Block Two -->
						<div class="news-block-two">
							<div class="inner-box wow fadeInLeft" data-wow-delay="0ms" data-wow-duration="1500ms">
								<div class="image">
									<router-link to="/blogdetail"><img :src="cmsValue('blog', 'content', 'post_3_image_url', '/frontend/images/resource/news-6.jpg')" alt="" /></router-link>
								</div>
								<div class="lower-content">
									<div class="upper-box clearfix">
										<div class="pull-left">
											<div class="posted-date">{{ cmsValue('blog', 'content', 'post_3_date', '12 Feb. 2019') }}</div>
										</div>
										<div class="pull-right">
											<ul class="post-meta">
												<li>{{ cmsValue('blog', 'content', 'post_3_meta_author', 'By :  Admin') }}</li>
												<li>{{ cmsValue('blog', 'content', 'post_3_meta_category', 'Care, Senior Health') }}</li>
												<li>{{ cmsValue('blog', 'content', 'post_3_meta_comments', 'Comments: 7') }}</li>
											</ul>
										</div>
									</div>
									<div class="lower-box">
										<h3><router-link to="/blogdetail">{{ cmsValue('blog', 'content', 'post_3_title', 'Nationally Endorsed Care') }}</router-link></h3>
										<div class="text">{{ cmsValue('blog', 'content', 'post_3_excerpt', 'Proactively envisioned multimedia based expertise and cross-media growth strategies. Seamlessly visualize quality intellectual capital without superior collaboration and idea-sharing. Holistically pontificate installed base portals after maintainable products. Interactively coordinate proactive e-commerce via process-centric "outside the box" thinking. Completely pursue scalable customer service ....') }}</div>
										<router-link to="/blogdetail" class="read-more">{{ cmsValue('blog', 'content', 'read_more_text', 'Continue Reading') }}</router-link>
									</div>
								</div>
							</div>
						</div>
						
						<!--News Block Two -->
						<div class="news-block-two">
							<div class="inner-box wow fadeInRight" data-wow-delay="0ms" data-wow-duration="1500ms">
								<div class="image">
									<router-link to="/blogdetail"><img :src="cmsValue('blog', 'content', 'post_4_image_url', '/frontend/images/resource/news-7.jpg')" alt="" /></router-link>
								</div>
								<div class="lower-content">
									<div class="upper-box clearfix">
										<div class="pull-left">
											<div class="posted-date">{{ cmsValue('blog', 'content', 'post_4_date', '12 Feb. 2019') }}</div>
										</div>
										<div class="pull-right">
											<ul class="post-meta">
												<li>{{ cmsValue('blog', 'content', 'post_4_meta_author', 'By :  Admin') }}</li>
												<li>{{ cmsValue('blog', 'content', 'post_4_meta_category', 'Care, Senior Health') }}</li>
												<li>{{ cmsValue('blog', 'content', 'post_4_meta_comments', 'Comments: 7') }}</li>
											</ul>
										</div>
									</div>
									<div class="lower-box">
										<h3><router-link to="/blogdetail">{{ cmsValue('blog', 'content', 'post_4_title', 'Table for One? Food Safety and Dining Solo') }}</router-link></h3>
										<div class="text">{{ cmsValue('blog', 'content', 'post_4_excerpt', 'Proactively envisioned multimedia based expertise and cross-media growth strategies. Seamlessly visualize quality intellectual capital without superior collaboration and idea-sharing. Holistically pontificate installed base portals after maintainable products. Interactively coordinate proactive e-commerce via process-centric "outside the box" thinking. Completely pursue scalable customer service ....') }}</div>
										<router-link to="/blogdetail" class="read-more">{{ cmsValue('blog', 'content', 'read_more_text', 'Continue Reading') }}</router-link>
									</div>
								</div>
							</div>
						</div>
						
						<!--Styled Pagination-->
						<ul class="styled-pagination">
							<li><a href="#" class="active">{{ cmsValue('blog', 'content', 'pagination_page_1_text', '1') }}</a></li>
							<li><a href="#">{{ cmsValue('blog', 'content', 'pagination_page_2_text', '2') }}</a></li>
							<li><a href="#"><span class="fa fa-angle-right"></span></a></li>
						</ul>                
						<!--End Styled Pagination-->
						
					</div>
				</div>
				
				<!--Sidebar Side-->
               <div class="sidebar-side col-xl-3 col-lg-4 col-md-12 col-sm-12">
					<aside class="sidebar style-two">
						
						<!-- Search -->
                        <div class="sidebar-widget search-box">
                        	<form method="post" action="/contact">
                                <div class="form-group">
                                    <input type="search" name="search-field" value="" :placeholder="cmsValue('blog', 'sidebar', 'search_placeholder', 'Enter Search Keywords')" required>
                                    <button type="submit"><span class="icon fa fa-search"></span></button>
                                </div>
                            </form>
						</div>
						
						<!--Blog Category Widget-->
                        <div class="sidebar-widget sidebar-blog-category">
                            <div class="sidebar-title">
                                <h2>{{ cmsValue('blog', 'sidebar', 'categories_heading', 'Categories') }}</h2>
                            </div>
                            <ul class="cat-list">
                                <li><a href="#">{{ cmsValue('blog', 'sidebar', 'category_item_1', 'Care Options') }}</a></li>
                                <li><a href="#">{{ cmsValue('blog', 'sidebar', 'category_item_2', 'Why In-Hom Care') }}</a></li>
                                <li><a href="#">{{ cmsValue('blog', 'sidebar', 'category_item_3', 'Senior Health & Well-Being') }}</a></li>
                                <li><a href="#">{{ cmsValue('blog', 'sidebar', 'category_item_4', 'Selecting Care') }}</a></li>
                                <li><a href="#">{{ cmsValue('blog', 'sidebar', 'category_item_5', 'Family CareGiver Support') }}</a></li>
                                <li><a href="#">{{ cmsValue('blog', 'sidebar', 'category_item_6', 'Medical Care Service') }}</a></li>
                            </ul>
                        </div>
						
						<!-- Popular Posts -->
                        <div class="sidebar-widget popular-posts">
                            <div class="sidebar-title"><h2>{{ cmsValue('blog', 'sidebar', 'recent_news_heading', 'Recent News') }}</h2></div>

                            <article class="post">
                            	<figure class="post-thumb"><router-link to="/blogdetail"><img :src="cmsValue('blog', 'sidebar', 'recent_news_1_image_url', '/frontend/images/resource/post-thumb-1.jpg')" alt=""></router-link></figure>
                                <div class="text"><router-link to="/blogdetail">{{ cmsValue('blog', 'sidebar', 'recent_news_1_title', 'Override the digital divide additional.') }}</router-link></div>
                                <div class="post-info">{{ cmsValue('blog', 'sidebar', 'recent_news_1_date', '08 Feb. 2019') }}</div>
                            </article>

                            <article class="post">
                            	<figure class="post-thumb"><router-link to="/blogdetail"><img :src="cmsValue('blog', 'sidebar', 'recent_news_2_image_url', '/frontend/images/resource/post-thumb-2.jpg')" alt=""></router-link></figure>
                                <div class="text"><router-link to="/blogdetail">{{ cmsValue('blog', 'sidebar', 'recent_news_2_title', 'At the end of the day, going, a new...') }}</router-link></div>
                                <div class="post-info">{{ cmsValue('blog', 'sidebar', 'recent_news_2_date', '08 Feb. 2019') }}</div>
                            </article>
                            
                            <article class="post">
                            	<figure class="post-thumb"><router-link to="/blogdetail"><img :src="cmsValue('blog', 'sidebar', 'recent_news_3_image_url', '/frontend/images/resource/post-thumb-3.jpg')" alt=""></router-link></figure>
                                <div class="text"><router-link to="/blogdetail">{{ cmsValue('blog', 'sidebar', 'recent_news_3_title', 'Information will close the loop on...') }}</router-link></div>
                                <div class="post-info">{{ cmsValue('blog', 'sidebar', 'recent_news_3_date', '08 Feb. 2019') }}</div>
                            </article>
                            
                            <article class="post">
                            	<figure class="post-thumb"><router-link to="/blogdetail"><img :src="cmsValue('blog', 'sidebar', 'recent_news_4_image_url', '/frontend/images/resource/post-thumb-4.jpg')" alt=""></router-link></figure>
                                <div class="text"><router-link to="/blogdetail">{{ cmsValue('blog', 'sidebar', 'recent_news_4_title', 'User generated in real-time will have.') }}</router-link></div>
                                <div class="post-info">{{ cmsValue('blog', 'sidebar', 'recent_news_4_date', '08 Feb. 2019') }}</div>
                            </article>

                        </div>
						
						<!-- Popular Tags -->
                        <div class="sidebar-widget popular-tags">
                            <div class="sidebar-title"><h2>{{ cmsValue('blog', 'sidebar', 'tags_heading', 'Tags') }}</h2></div>
                            <a href="#">{{ cmsValue('blog', 'sidebar', 'tag_1', 'Senior care') }}</a>
                            <a href="#">{{ cmsValue('blog', 'sidebar', 'tag_2', 'Analysis') }}</a>
                            <a href="#">{{ cmsValue('blog', 'sidebar', 'tag_3', 'Gallery') }}</a>
                            <a href="#">{{ cmsValue('blog', 'sidebar', 'tag_4', 'Medical Care') }}</a>
                            <a href="#">{{ cmsValue('blog', 'sidebar', 'tag_5', 'Care Skills') }}</a>
                            <a href="#">{{ cmsValue('blog', 'sidebar', 'tag_6', 'Aging Factor') }}</a>
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
												<li><router-link to="/">Home</router-link></li>
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
											<a :href="footerCqcHref()" target="_blank" rel="noopener">
												<img :src="footerValue('cqc_badge_image_url', '/frontend/images/CQC rating.jpg')" alt="CQC rating" />
											</a>
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
