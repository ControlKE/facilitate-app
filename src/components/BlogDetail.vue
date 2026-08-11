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
    <section class="page-title" :style="{ backgroundImage: `url(${cmsValue('blogdetail', 'hero', 'background_image_url', '/frontend/images/background/3.jpg')})` }">
    	<div class="auto-container">
        	<h2>{{ cmsValue('blogdetail', 'hero', 'headline', 'Blog') }}</h2>
            <ul class="page-breadcrumb">
            	<li><a href="/">home</a></li>
                <li>{{ cmsValue('blogdetail', 'hero', 'headline', 'Blog') }}</li>
            </ul>
        </div>
    </section>
    <!--End Page Title-->
	
	<!--Sidebar Page Container-->
    <div class="sidebar-page-container">
    	<div class="auto-container">
        	<div class="row clearfix">
            	
                <!--Content Side / Blog Classic -->
                <div class="content-side col-lg-9 col-md-12 col-sm-12">
                	
					<div class="blog-single padding-right">
						<div class="inner-box">
							<div class="image">
								<img :src="cmsValue('blogdetail', 'content', 'feature_image_url', '/frontend/images/resource/news-4.jpg')" alt="" />
							</div>
							<div class="lower-content">
								<div class="upper-box clearfix">
									<div class="posted-date pull-left">{{ cmsValue('blogdetail', 'content', 'post_date', '12 Feb. 2019') }}</div>
									<ul class="post-meta pull-right">
										<li>{{ cmsValue('blogdetail', 'content', 'post_meta_author', 'By :  Admin') }}</li>
										<li>{{ cmsValue('blogdetail', 'content', 'post_meta_category', 'Care, Senior Health') }}</li>
										<li>{{ cmsValue('blogdetail', 'content', 'post_meta_comments', 'Comments: 7') }}</li>
									</ul>
								</div>
								<div class="lower-box">
									<h3>{{ cmsValue('blogdetail', 'content', 'headline', 'The Magic of Quality Care') }}</h3>
									<div class="text">
										<p>{{ cmsValue('blogdetail', 'content', 'body_intro_text', 'Objectively innovate empowered manufactured products whereas parallel platforms. Holisticly predominate extensible testing procedures for reliable supply chains. Dramatically engage top-line web services vis-a-vis cutting-edge deliverables. Proactively envisioned multimedia based expertise and cross-media growth strategies. Seamlessly visualize quality intellectual. Objectively innovate empowered manufactured products whereas parallel platforms. Holisticly predominate extensible testing procedures for reliable supply chains. Dramatically engage top-line web services vis-a-vis cutting-edge deliverables.') }}</p>
										<h4>{{ cmsValue('blogdetail', 'content', 'subheadline', 'Two Column Text Sample') }}</h4>
										<div class="row clearfix">
											<div class="col-md-6 col-sm-6 col-xs-12">
												<p>{{ cmsValue('blogdetail', 'content', 'column_text_1', 'Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est.') }}</p>
											</div>
											<div class="col-md-6 col-sm-6 col-xs-12">
												<p>{{ cmsValue('blogdetail', 'content', 'column_text_2', 'Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est.') }}</p>
											</div>
										</div>
										<p>{{ cmsValue('blogdetail', 'content', 'body_closing_text', 'Here is main text quis nostrud exercitation ullamco laboris nisi here is itealic text ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla rure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat here is link cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.') }}</p>
									</div>
								</div>
							</div>
						</div>
                        
                    	
                        <!--Comments Area-->
                        <div class="comments-area">
                            <div class="group-title"><h2>{{ cmsValue('blogdetail', 'content', 'comments_heading', 'Comments 4') }}</h2></div>
                            <div class="inner-box">
                                <!--Comment Box-->
                                <div class="comment-box">
                                    <div class="comment">
                                        <div class="author-thumb"><img src="/frontend/images/resource/author-2.jpg" alt=""></div>
                                        <div class="comment-inner">
                                            <div class="comment-info clearfix"><strong>{{ cmsValue('blogdetail', 'content', 'comment_1_name', 'Michale john') }}</strong></div>
                                            <div class="text">{{ cmsValue('blogdetail', 'content', 'comment_1_text', 'Capitalize on low hanging fruit to identify a ballpark value added activity to beta test. Override the digital divide with additional clickthroughs from DevOps.') }}</div>
                                            <ul class="post-info">
                                                <li>{{ cmsValue('blogdetail', 'content', 'comment_1_date', '08 Feb, 2019') }}</li>
                                                <li><a href="#">{{ cmsValue('blogdetail', 'content', 'comment_1_reply_text', 'Reply') }}</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <!--Comment Box-->
                                <div class="comment-box">
                                    <div class="comment">
                                        <div class="author-thumb"><img src="/frontend/images/resource/author-2.jpg" alt=""></div>
                                        <div class="comment-inner">
                                            <div class="comment-info clearfix"><strong>{{ cmsValue('blogdetail', 'content', 'comment_2_name', 'Robert') }}</strong></div>
                                            <div class="text">{{ cmsValue('blogdetail', 'content', 'comment_2_text', 'Capitalize on low hanging fruit to identify a ballpark value added activity to beta test. Override the digital divide with additional clickthroughs from DevOps.') }}</div>
                                            <ul class="post-info">
                                                <li>{{ cmsValue('blogdetail', 'content', 'comment_2_date', '08 Feb, 2019') }}</li>
                                                <li><a href="#">{{ cmsValue('blogdetail', 'content', 'comment_2_reply_text', 'Reply') }}</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <!--Comment Box-->
                                <div class="comment-box">
                                    <div class="comment">
                                        <div class="author-thumb"><img src="/frontend/images/resource/author-2.jpg" alt=""></div>
                                        <div class="comment-inner">
                                            <div class="comment-info clearfix"><strong>{{ cmsValue('blogdetail', 'content', 'comment_3_name', 'Michale john') }}</strong></div>
                                            <div class="text">{{ cmsValue('blogdetail', 'content', 'comment_3_text', 'Capitalize on low hanging fruit to identify a ballpark value added activity to beta test. Override the digital divide with additional clickthroughs from DevOps.') }}</div>
                                            <ul class="post-info">
                                                <li>{{ cmsValue('blogdetail', 'content', 'comment_3_date', '08 Feb, 2019') }}</li>
                                                <li><a href="#">{{ cmsValue('blogdetail', 'content', 'comment_3_reply_text', 'Reply') }}</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="comment reply-comment">
                                        <div class="author-thumb"><img src="/frontend/images/resource/author-2.jpg" alt=""></div>
                                        <div class="comment-inner">
                                            <div class="comment-info clearfix"><strong>{{ cmsValue('blogdetail', 'content', 'comment_reply_1_name', 'Robert') }}</strong></div>
                                            <div class="text">{{ cmsValue('blogdetail', 'content', 'comment_reply_1_text', 'Capitalize on low hanging fruit to identify a ballpark value added activity to beta test. Override the digital divide with additional clickthroughs from DevOps.') }}</div>
                                            <ul class="post-info">
                                                <li>{{ cmsValue('blogdetail', 'content', 'comment_reply_1_date', '08 Feb, 2019') }}</li>
                                                <li><a href="#">{{ cmsValue('blogdetail', 'content', 'comment_reply_1_reply_text', 'Reply') }}</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                </div>
                                
                                <!--Comment Box-->
                                <div class="comment-box">
                                    <div class="comment">
                                        <div class="author-thumb"><img src="/frontend/images/resource/author-2.jpg" alt=""></div>
                                        <div class="comment-inner">
                                            <div class="comment-info clearfix"><strong>{{ cmsValue('blogdetail', 'content', 'comment_4_name', 'Michale john') }}</strong></div>
                                            <div class="text">{{ cmsValue('blogdetail', 'content', 'comment_4_text', 'Capitalize on low hanging fruit to identify a ballpark value added activity to beta test. Override the digital divide with additional clickthroughs from DevOps.') }}</div>
                                            <ul class="post-info">
                                                <li>{{ cmsValue('blogdetail', 'content', 'comment_4_date', '08 Feb, 2019') }}</li>
                                                <li><a href="#">{{ cmsValue('blogdetail', 'content', 'comment_4_reply_text', 'Reply') }}</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                        
                        <!-- Comment Form -->
                        <div class="comment-form">
                                
                            <div class="group-title"><h2>{{ cmsValue('blogdetail', 'content', 'leave_comment_heading', 'Leave a Comment') }}</h2></div>
                            <div class="form-inner">
                                <!--Comment Form-->
                                <form method="post" action="blog.html">
                                    <div class="row clearfix">
                                        <div class="col-md-6 col-sm-6 col-xs-12 form-group">
                                            <input type="text" name="username" :placeholder="cmsValue('blogdetail', 'content', 'comment_form_name_placeholder', 'Your name')" required>
                                        </div>
                                        
                                        <div class="col-md-6 col-sm-6 col-xs-12 form-group">
                                            <input type="email" name="email" :placeholder="cmsValue('blogdetail', 'content', 'comment_form_email_placeholder', 'Email address')" required>
                                        </div>
                                        
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 form-group">
                                            <textarea name="message" :placeholder="cmsValue('blogdetail', 'content', 'comment_form_message_placeholder', 'Write message')"></textarea>
                                        </div>
                                        
                                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 form-group">
                                            <button class="theme-btn btn-style-one" type="submit" name="submit-form"><span class="txt">{{ cmsValue('blogdetail', 'content', 'comment_form_submit_text', 'Submit now') }}</span></button>
                                        </div>
                                        
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!--End Comment Form -->
                        
                    </div>
					
				</div>
				
				<!--Sidebar Side-->
               <div class="sidebar-side col-lg-3 col-md-12 col-sm-12">
					<aside class="sidebar style-two">
						
						<!-- Search -->
                        <div class="sidebar-widget search-box">
                        	<form method="post" action="contact.html">
                                <div class="form-group">
                                    <input type="search" name="search-field" value="" :placeholder="cmsValue('blogdetail', 'sidebar', 'search_placeholder', 'Enter Search Keywords')" required>
                                    <button type="submit"><span class="icon fa fa-search"></span></button>
                                </div>
                            </form>
						</div>
						
						<!--Blog Category Widget-->
                        <div class="sidebar-widget sidebar-blog-category">
                            <div class="sidebar-title">
                                <h2>{{ cmsValue('blogdetail', 'sidebar', 'categories_heading', 'Categories') }}</h2>
                            </div>
                            <ul class="cat-list">
                                <li><a href="#">{{ cmsValue('blogdetail', 'sidebar', 'category_item_1', 'Care Options') }}</a></li>
                                <li><a href="#">{{ cmsValue('blogdetail', 'sidebar', 'category_item_2', 'Why In-Hom Care') }}</a></li>
                                <li><a href="#">{{ cmsValue('blogdetail', 'sidebar', 'category_item_3', 'Senior Health & Well-Being') }}</a></li>
                                <li><a href="#">{{ cmsValue('blogdetail', 'sidebar', 'category_item_4', 'Selecting Care') }}</a></li>
                                <li><a href="#">{{ cmsValue('blogdetail', 'sidebar', 'category_item_5', 'Family CareGiver Support') }}</a></li>
                                <li><a href="#">{{ cmsValue('blogdetail', 'sidebar', 'category_item_6', 'Medical Care Service') }}</a></li>
                            </ul>
                        </div>
						
						<!-- Popular Posts -->
                        <div class="sidebar-widget popular-posts">
                            <div class="sidebar-title"><h2>{{ cmsValue('blogdetail', 'sidebar', 'recent_news_heading', 'Recent News') }}</h2></div>

                            <article class="post">
                            	<figure class="post-thumb"><router-link to="/blogdetail"><img :src="cmsValue('blogdetail', 'sidebar', 'recent_news_1_image_url', '/frontend/images/resource/post-thumb-1.jpg')" alt=""></router-link></figure>
                                <div class="text"><router-link to="/blogdetail">{{ cmsValue('blogdetail', 'sidebar', 'recent_news_1_title', 'Override the digital divide additional.') }}</router-link></div>
                                <div class="post-info">{{ cmsValue('blogdetail', 'sidebar', 'recent_news_1_date', '08 Feb. 2019') }}</div>
                            </article>

                            <article class="post">
                            	<figure class="post-thumb"><router-link to="/blogdetail"><img :src="cmsValue('blogdetail', 'sidebar', 'recent_news_2_image_url', '/frontend/images/resource/post-thumb-2.jpg')" alt=""></router-link></figure>
                                <div class="text"><router-link to="/blogdetail">{{ cmsValue('blogdetail', 'sidebar', 'recent_news_2_title', 'At the end of the day, going, a new...') }}</router-link></div>
                                <div class="post-info">{{ cmsValue('blogdetail', 'sidebar', 'recent_news_2_date', '08 Feb. 2019') }}</div>
                            </article>
                            
                            <article class="post">
                            	<figure class="post-thumb"><router-link to="/blogdetail"><img :src="cmsValue('blogdetail', 'sidebar', 'recent_news_3_image_url', '/frontend/images/resource/post-thumb-3.jpg')" alt=""></router-link></figure>
                                <div class="text"><router-link to="/blogdetail">{{ cmsValue('blogdetail', 'sidebar', 'recent_news_3_title', 'Information will close the loop on...') }}</router-link></div>
                                <div class="post-info">{{ cmsValue('blogdetail', 'sidebar', 'recent_news_3_date', '08 Feb. 2019') }}</div>
                            </article>
                            
                            <article class="post">
                            	<figure class="post-thumb"><router-link to="/blogdetail"><img :src="cmsValue('blogdetail', 'sidebar', 'recent_news_4_image_url', '/frontend/images/resource/post-thumb-4.jpg')" alt=""></router-link></figure>
                                <div class="text"><router-link to="/blogdetail">{{ cmsValue('blogdetail', 'sidebar', 'recent_news_4_title', 'User generated in real-time will have.') }}</router-link></div>
                                <div class="post-info">{{ cmsValue('blogdetail', 'sidebar', 'recent_news_4_date', '08 Feb. 2019') }}</div>
                            </article>

                        </div>
						
						<!-- Popular Tags -->
                        <div class="sidebar-widget popular-tags">
                            <div class="sidebar-title"><h2>{{ cmsValue('blogdetail', 'sidebar', 'tags_heading', 'Tags') }}</h2></div>
                            <a href="#">{{ cmsValue('blogdetail', 'sidebar', 'tag_1', 'Senior care') }}</a>
                            <a href="#">{{ cmsValue('blogdetail', 'sidebar', 'tag_2', 'Analysis') }}</a>
                            <a href="#">{{ cmsValue('blogdetail', 'sidebar', 'tag_3', 'Gallery') }}</a>
                            <a href="#">{{ cmsValue('blogdetail', 'sidebar', 'tag_4', 'Medical Care') }}</a>
                            <a href="#">{{ cmsValue('blogdetail', 'sidebar', 'tag_5', 'Care Skills') }}</a>
                            <a href="#">{{ cmsValue('blogdetail', 'sidebar', 'tag_6', 'Aging Factor') }}</a>
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
