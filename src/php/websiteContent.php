<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, X-Auth-Token, Authorization, Accept');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

require_once __DIR__ . '/db.php';

const DEFAULT_FIELD_TYPE = 'text';

function jsonResponse($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function getJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function stringValue($value): string
{
    return trim((string) $value);
}

function boolValue($value, bool $default = false): bool
{
    if ($value === null) {
        return $default;
    }
    if (is_bool($value)) {
        return $value;
    }

    $normalized = strtolower(stringValue($value));
    if (in_array($normalized, ['1', 'true', 'yes', 'y'], true)) {
        return true;
    }
    if (in_array($normalized, ['0', 'false', 'no', 'n'], true)) {
        return false;
    }

    return $default;
}

function intValue($value, int $default = 0): int
{
    if ($value === null || $value === '' || !is_numeric($value)) {
        return $default;
    }
    return (int) $value;
}

function normalizeKey(string $value): string
{
    $normalized = strtolower(stringValue($value));
    $normalized = str_replace(' ', '_', $normalized);
    $normalized = preg_replace('/[^a-z0-9_-]/', '', $normalized);
    return stringValue((string) $normalized);
}

function normalizeFieldType($value): string
{
    $type = strtolower(stringValue($value));
    $allowed = ['text', 'textarea', 'image', 'url', 'richtext'];
    if (!in_array($type, $allowed, true)) {
        return DEFAULT_FIELD_TYPE;
    }
    return $type;
}

function ensureWebsiteContentTable(mysqli $conn): void
{
    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS website_content (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page_key VARCHAR(80) NOT NULL,
    section_key VARCHAR(80) NOT NULL,
    field_key VARCHAR(80) NOT NULL,
    field_type VARCHAR(20) NOT NULL DEFAULT 'text',
    content_value MEDIUMTEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_by VARCHAR(160) NOT NULL DEFAULT '',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY website_content_unique (page_key, section_key, field_key),
    KEY website_content_page_idx (page_key),
    KEY website_content_section_idx (section_key),
    KEY website_content_active_idx (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($sql)) {
        throw new RuntimeException('Failed to initialize website content table.');
    }
}

function seedDefaultWebsiteContent(mysqli $conn): void
{
    $seedRows = [
        ['global', 'header', 'phone', 'text', '024 7623 1188', 1],
        ['global', 'header', 'email', 'text', 'info@facilitatecareservices.co.uk', 1],
        ['global', 'header', 'logo_url', 'image', '/frontend/images/logo.png', 1],
        ['global', 'header', 'primary_cta_text', 'text', 'Ask A Question', 1],
        ['global', 'footer', 'tagline', 'text', 'Suporting Your Independence.', 1],
        ['global', 'footer', 'logo_url', 'image', '/frontend/images/footer-logo.png', 1],
        ['global', 'footer', 'quick_links_heading', 'text', 'Quick links', 1],
        ['global', 'footer', 'contact_heading', 'text', 'Contact Info', 1],
        ['global', 'footer', 'contact_phone', 'text', '024 7623 1188', 1],
        ['global', 'footer', 'contact_address', 'textarea', 'Office GE13, 101 Lockhurst Lane,<br> Coventry, CV6 5sf', 1],
        ['global', 'footer', 'contact_email_label', 'text', 'Email', 1],
        ['global', 'footer', 'contact_email', 'text', 'info@facilitatecareservices.co.uk', 1],
        ['global', 'footer', 'whatsapp_number', 'text', '', 1],
        ['global', 'footer', 'whatsapp_label', 'text', 'Chat on WhatsApp', 1],
        ['global', 'footer', 'whatsapp_message', 'textarea', 'Hello, I would like to enquire about your care services.', 1],
        ['global', 'footer', 'cqc_heading', 'text', 'Care Quality Commission', 1],
        ['global', 'footer', 'cqc_badge_image_url', 'image', '/frontend/images/CQC rating.jpg', 1],
        ['global', 'footer', 'cqc_text', 'textarea', 'Latest Inspection 25 May 2021', 1],
        ['global', 'footer', 'cqc_secondary_text', 'text', 'Latest Review 6 July 2023', 1],
        ['global', 'footer', 'cqc_button_text', 'text', 'See Report', 1],
        ['global', 'footer', 'cqc_url', 'url', 'https://www.cqc.org.uk/location/1-2131286214', 1],
        ['global', 'footer', 'copyright_text', 'text', '(c) Copyright Facilitate care services 2024. All right reserved.', 1],

        ['home', 'hero', 'headline', 'text', 'Compassionate Care At Home', 1],
        ['home', 'hero', 'subheadline', 'textarea', 'Trusted support tailored to your loved one\'s needs.', 1],
        ['home', 'hero', 'background_image_url', 'image', '/frontend/images/main-slider/1.jpg', 1],
        ['home', 'hero', 'cta_text', 'text', 'Book A Consultation', 1],
        ['home', 'hero', 'cta_url', 'url', '/contact', 1],
        ['home', 'hero', 'slide_1_heading', 'text', 'Expert Care & Personalised Support', 1],
        ['home', 'hero', 'slide_1_body', 'textarea', 'Delivering expert care and compassionate support tailored to you.', 1],
        ['home', 'hero', 'slide_1_image_url', 'image', '/frontend/images/main-slider/1.jpg', 1],
        ['home', 'hero', 'slide_2_heading', 'text', 'Elevating Spirits with Exceptional At-Home Care', 1],
        ['home', 'hero', 'slide_2_body', 'textarea', 'Our in-home care services are designed to uplift and nurture the human spirit.', 1],
        ['home', 'hero', 'slide_2_image_url', 'image', '/frontend/images/main-slider/2.jpg', 1],
        ['home', 'hero', 'slide_3_heading', 'text', 'Quality Home Care Service, You Can Trust.', 1],
        ['home', 'hero', 'slide_3_body', 'textarea', 'Experience top-quality home care services you can rely on without hesitation.', 1],
        ['home', 'hero', 'slide_3_image_url', 'image', '/frontend/images/main-slider/3.jpg', 1],

        ['home', 'services_intro', 'headline', 'text', 'Comprehensive and Personalised Home Care Services', 1],
        ['home', 'services_intro', 'subheadline', 'textarea', 'Our dedicated team of experienced healthcare experts delivers specialized in-home care, tailored to optimize and oversee your recovery process in the comfort of your own space.', 1],
        ['home', 'services_intro', 'body_text', 'textarea', 'Registered with the Care Quality Commission, we pride ourselves on giving a truly helpful service that will enhance your quality of life. Our flexible service provides options from a one-off visit to several visits daily on an ongoing basis.', 1],

        ['home', 'services_feature_cards', 'card_1_image_url', 'image', '/frontend/images/resource/service-1.jpg', 1],
        ['home', 'services_feature_cards', 'card_1_title', 'text', 'We Enrich', 1],
        ['home', 'services_feature_cards', 'card_1_text', 'textarea', 'Comfort, support, and a sense of belonging for all.', 1],
        ['home', 'services_feature_cards', 'card_2_image_url', 'image', '/frontend/images/resource/service-2.jpg', 1],
        ['home', 'services_feature_cards', 'card_2_title', 'text', 'We Empower', 1],
        ['home', 'services_feature_cards', 'card_2_text', 'textarea', 'Regain independence and thrive in the comfort of your home.', 1],
        ['home', 'services_feature_cards', 'card_3_image_url', 'image', '/frontend/images/resource/service-3.jpg', 1],
        ['home', 'services_feature_cards', 'card_3_title', 'text', 'We Engage', 1],
        ['home', 'services_feature_cards', 'card_3_text', 'textarea', 'Personalised, dignified care for your unique needs and well-being.', 1],

        ['home', 'services_catalog', 'headline', 'text', 'Explore Our Range of Services', 1],
        ['home', 'services_catalog', 'subheadline', 'textarea', 'Discover a range of specialised services designed to provide comfort, support, and tailored care for you or your loved ones.', 1],

        ['home', 'services_catalog_items', 'item_1_image_url', 'image', '/frontend/images/resource/service-4.jpg', 1],
        ['home', 'services_catalog_items', 'item_1_title', 'text', 'Personal Care', 1],
        ['home', 'services_catalog_items', 'item_1_text', 'textarea', 'Assistance with daily activities to enhance comfort and well-being of the service user.', 1],
        ['home', 'services_catalog_items', 'item_2_image_url', 'image', '/frontend/images/resource/service-5.jpg', 1],
        ['home', 'services_catalog_items', 'item_2_title', 'text', 'Social Care', 1],
        ['home', 'services_catalog_items', 'item_2_text', 'textarea', 'Companionship and support to maintain an active and fulfilling social life.', 1],
        ['home', 'services_catalog_items', 'item_3_image_url', 'image', '/frontend/images/resource/service-6.jpg', 1],
        ['home', 'services_catalog_items', 'item_3_title', 'text', 'Live In Care', 1],
        ['home', 'services_catalog_items', 'item_3_text', 'textarea', '24/7 personalised care ensuring safety, comfort, and peace of mind at home.', 1],
        ['home', 'services_catalog_items', 'item_4_image_url', 'image', '/frontend/images/resource/service-7.jpg', 1],
        ['home', 'services_catalog_items', 'item_4_title', 'text', 'Hospital Discharge', 1],
        ['home', 'services_catalog_items', 'item_4_text', 'textarea', 'Smooth transition from hospital to home with post-hospitalization support and care.', 1],
        ['home', 'services_catalog_items', 'item_5_image_url', 'image', '/frontend/images/resource/service-8.jpg', 1],
        ['home', 'services_catalog_items', 'item_5_title', 'text', 'Elderly Care Service', 1],
        ['home', 'services_catalog_items', 'item_5_text', 'textarea', 'Tailored care for seniors, addressing their unique needs and preferences.', 1],
        ['home', 'services_catalog_items', 'item_6_image_url', 'image', '/frontend/images/resource/service-9.jpg', 1],
        ['home', 'services_catalog_items', 'item_6_title', 'text', 'Respite Care', 1],
        ['home', 'services_catalog_items', 'item_6_text', 'textarea', 'Temporary relief for caregivers, ensuring continuous quality care for your loved one.', 1],
        ['home', 'services_catalog_items', 'item_7_image_url', 'image', '/frontend/images/resource/services-10.jpg', 1],
        ['home', 'services_catalog_items', 'item_7_title', 'text', 'Palliative Care', 1],
        ['home', 'services_catalog_items', 'item_7_text', 'textarea', 'Comfort-focused care and emotional support for individuals with serious illnesses.', 1],
        ['home', 'services_catalog_items', 'item_8_image_url', 'image', '/frontend/images/resource/services-11.jpg', 1],
        ['home', 'services_catalog_items', 'item_8_title', 'text', 'Special Needs Care', 1],
        ['home', 'services_catalog_items', 'item_8_text', 'textarea', 'Customized assistance for individuals with unique requirements, ensuring their well-being.', 1],
        ['home', 'services_catalog_items', 'item_9_image_url', 'image', '/frontend/images/resource/service-23.jpg', 1],
        ['home', 'services_catalog_items', 'item_9_title', 'text', 'Supported Living', 1],
        ['home', 'services_catalog_items', 'item_9_text', 'textarea', 'Personalised support for individuals with learning, physical, or mental challenges to live independently.', 1],

        ['home', 'care_tasks', 'headline', 'text', 'We provide In Home Care Services For Everyone.', 1],
        ['home', 'care_tasks', 'subheadline', 'textarea', 'Discover how professional caregivers approach caring for your loved one in order to engage', 1],
        ['home', 'care_tasks', 'item_1_title', 'text', 'Personal Grooming Like Bathing or Getting Dressed', 1],
        ['home', 'care_tasks', 'item_2_title', 'text', 'Helping a Person with Dementia by Grounding and Orienting Them', 1],
        ['home', 'care_tasks', 'item_3_title', 'text', 'Take Care of Medication Reminders', 1],
        ['home', 'care_tasks', 'item_4_title', 'text', 'Moving Around. Getting in and out of the Bed or Shower', 1],
        ['home', 'care_tasks', 'item_5_title', 'text', 'Errands Like Grocery Shopping and Picking up Prescriptions', 1],
        ['home', 'care_tasks', 'item_6_title', 'text', 'Keeping Them Safe and Comfortable', 1],

        ['home', 'mental_wellbeing', 'pattern_image_url', 'image', '/frontend/images/background/pattern-1.png', 1],
        ['home', 'mental_wellbeing', 'headline', 'text', 'Supporting Mental Well-being', 1],
        ['home', 'mental_wellbeing', 'body_text', 'textarea', 'We understand the importance of maintaining mental health in elderly individuals. Our caregivers are trained not only in providing exceptional care but also in fostering positive relationships.', 1],
        ['home', 'mental_wellbeing', 'list_item_1', 'text', 'Addressing age-related challenges', 1],
        ['home', 'mental_wellbeing', 'list_item_2', 'text', 'Cognitive health and memory support', 1],
        ['home', 'mental_wellbeing', 'list_item_3', 'text', 'Companionship and social engagement', 1],
        ['home', 'mental_wellbeing', 'list_item_4', 'text', 'Post-surgery recovery', 1],
        ['home', 'mental_wellbeing', 'list_item_5', 'text', 'Assisting with chronic conditions', 1],
        ['home', 'mental_wellbeing', 'list_item_6', 'text', 'Flexible care options for peace of mind', 1],
        ['home', 'mental_wellbeing', 'bold_text', 'textarea', 'Let us help you prioritise mental well-being for your loved one!', 1],
        ['home', 'mental_wellbeing', 'video_image_url', 'image', '/frontend/images/resource/video-img.jpg', 1],
        ['home', 'mental_wellbeing', 'video_url', 'url', 'https://www.youtube.com/watch?v=z-Ag8jll5nA', 1],

        ['home', 'movement_cta', 'background_image_url', 'image', '/frontend/images/background/1.png', 1],
        ['home', 'movement_cta', 'headline', 'text', 'Share Your Cares. Inspire Others.', 1],
        ['home', 'movement_cta', 'subheadline', 'textarea', 'Join our movement to make the world a better place for seniors.', 1],
        ['home', 'movement_cta', 'button_text', 'text', 'Contact Us', 1],
        ['home', 'movement_cta', 'button_url', 'url', '/contact', 1],

        ['home', 'partners', 'company_1_name', 'text', 'Coventry Food Bank', 1],
        ['home', 'partners', 'company_1_logo_url', 'image', '/frontend/images/resource/Coventry-Foodbank.png', 1],
        ['home', 'partners', 'company_1_description', 'textarea', 'Info about Company A', 1],
        ['home', 'partners', 'company_2_name', 'text', 'Circus Starr', 1],
        ['home', 'partners', 'company_2_logo_url', 'image', '/frontend/images/resource/circus-starr.png', 1],
        ['home', 'partners', 'company_2_description', 'textarea', 'Info about Company B', 1],
        ['home', 'partners', 'company_3_name', 'text', 'Macmillan Cancer Support', 1],
        ['home', 'partners', 'company_3_logo_url', 'image', '/frontend/images/resource/macmillan-cancer-support.png', 1],
        ['home', 'partners', 'company_3_description', 'textarea', 'Info about Company C', 1],

        ['home', 'contact_section', 'headline', 'text', 'Get In Touch', 1],
        ['home', 'contact_section', 'subheadline', 'textarea', 'For further details on any of our services or to arrange a free initial consultation you can reach us via any of the methods below', 1],
        ['home', 'contact_section', 'button_text', 'text', 'Send Message', 1],
        ['home', 'contact_section', 'info_image_url', 'image', '/frontend/images/resource/contact-1.jpg', 1],
        ['home', 'contact_section', 'office_heading', 'text', 'Head Office:', 1],
        ['home', 'contact_section', 'office_address', 'textarea', 'Office GE13, 101 Lockhurst lane, Coventry CV6 5SF', 1],
        ['home', 'contact_section', 'office_phone', 'text', '024 7623 1188', 1],
        ['home', 'contact_section', 'office_email', 'text', 'enquiries@facilitatecareservice.co.uk', 1],
        ['home', 'contact_section', 'office_hours', 'text', '9 am to 5 pm Monday to Friday. (24 hr on call service)', 1],

        ['home', 'footer', 'tagline', 'text', 'Suporting Your Independence.', 1],
        ['home', 'footer', 'logo_url', 'image', '/frontend/images/footer-logo.png', 1],
        ['home', 'footer', 'quick_links_heading', 'text', 'Quick links', 1],
        ['home', 'footer', 'copyright_text', 'text', '(c) Copyright Facilitate care services 2024. All right reserved.', 1],
        ['home', 'map_section', 'title', 'text', '', 1],
        ['home', 'map_section', 'popup_content', 'textarea', 'Office GE13, 101 Lockhurst lane, Coventry, CV6 5SF <br> 024 7623 1188 <br> Mon-Fri: 9.00am - 5.00pm <br> Sunday closed', 1],
        ['home', 'map_section', 'latitude', 'text', '52.4056402', 1],
        ['home', 'map_section', 'longitude', 'text', '-1.5236883', 1],
        ['home', 'map_section', 'zoom', 'text', '18', 1],
        ['home', 'map_section', 'map_type', 'text', 'satellite', 1],
        ['home', 'map_section', 'place_label', 'text', 'Facilitate Care Services', 1],

        ['testimonial', 'hero', 'headline', 'text', 'What Families Say About Us', 1],
        ['testimonial', 'hero', 'subheadline', 'textarea', 'Real feedback from people we support every day.', 1],
        ['testimonial', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],

        ['about', 'hero', 'headline', 'text', 'About us', 1],
        ['about', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['contact', 'hero', 'headline', 'text', 'Contact Us', 1],
        ['contact', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['contact', 'map_section', 'title', 'text', '', 1],
        ['contact', 'map_section', 'popup_content', 'textarea', 'Earlsdon Park, 53-55 Butts Road, Coventry, CV1 3BH', 1],
        ['contact', 'map_section', 'latitude', 'text', '52.4056402', 1],
        ['contact', 'map_section', 'longitude', 'text', '-1.5236883', 1],
        ['contact', 'map_section', 'zoom', 'text', '18', 1],
        ['contact', 'map_section', 'map_type', 'text', 'satellite', 1],
        ['contact', 'map_section', 'place_label', 'text', 'Facilitate Care Services', 1],
        ['care', 'hero', 'headline', 'text', 'Social and Companion Care', 1],
        ['care', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['caregiver', 'hero', 'headline', 'text', 'CareGiver Jobs', 1],
        ['caregiver', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['chronical', 'hero', 'headline', 'text', 'Palliative Care', 1],
        ['chronical', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['discharge', 'hero', 'headline', 'text', 'Hospital Discharge', 1],
        ['discharge', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['lifecare', 'hero', 'headline', 'text', 'End of Life Care', 1],
        ['lifecare', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['livein', 'hero', 'headline', 'text', 'Skilled Live In Care', 1],
        ['livein', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['personalcare', 'hero', 'headline', 'text', 'Personal Care Service', 1],
        ['personalcare', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['respitecare', 'hero', 'headline', 'text', 'Respite Care', 1],
        ['respitecare', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['specialcare', 'hero', 'headline', 'text', 'Special Needs Care', 1],
        ['specialcare', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['started', 'hero', 'headline', 'text', 'Care Planning Process', 1],
        ['started', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['support', 'hero', 'headline', 'text', 'Supported Living', 1],
        ['support', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['surgery', 'hero', 'headline', 'text', '24/7 Day Support', 1],
        ['surgery', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['elderlyservice', 'hero', 'headline', 'text', 'Elder Care Service', 1],
        ['elderlyservice', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['gallery', 'hero', 'headline', 'text', 'Our Gallery', 1],
        ['gallery', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['team', 'hero', 'headline', 'text', 'Our Team', 1],
        ['team', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['faq', 'hero', 'headline', 'text', 'Faq', 1],
        ['faq', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['blog', 'hero', 'headline', 'text', 'Blog', 1],
        ['blog', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],
        ['blogdetail', 'hero', 'headline', 'text', 'Blog', 1],
        ['blogdetail', 'hero', 'background_image_url', 'image', '/frontend/images/background/3.jpg', 1],

        // Auto-seeded defaults from cmsValue bindings across frontend pages.
        ['about', 'content', 'cta_button_text', 'text', 'contact us', 1],
        ['about', 'content', 'cta_headline', 'text', 'Share Your Cares. Inspire Others.', 1],
        ['about', 'content', 'cta_text', 'textarea', 'If you or your loved one is in need of caregiving services, we encourage you to reach out for a complimentary evaluation so we can customise an appropriate plan of care.', 1],
        ['about', 'content', 'headline', 'text', 'Personalised Care Plans for your Needs', 1],
        ['about', 'content', 'intro_text', 'textarea', 'At Facilitate Care Services we aim to provide people with the highest standards of care that is individually designed to meet each individual needs and preferences. Our care service is planned with the active involvement of our service users and their relatives, friends or medical professionals where appropriate.', 1],
        ['about', 'content', 'management_headline', 'text', 'Our Management Team', 1],
        ['about', 'content', 'management_text_1', 'textarea', 'Facilitate Care is managed by its founders, Rose and Lucy, who are qualified and registered nurses with over 20 years experience in the NHS and private health care provision.', 1],
        ['about', 'content', 'management_text_2', 'textarea', 'At Facilitate Care Services, we believe that the heart of great care begins with great leadership. Our dedicated management team is the driving force behind our compassionate, client-focused home care services. With a diverse background in healthcare, management, and client support, our leaders bring together a wealth of expertise to ensure the highest standard of care for every individual we serve.', 1],
        ['about', 'content', 'management_text_3', 'textarea', 'Our team is committed to fostering a culture of excellence, innovation, and empathy. From the meticulous selection of our care professionals to the personalised design of our care plans, they oversee every aspect of our operations with a keen eye for detail and a deep understanding of the needs of those in our care.', 1],
        ['about', 'content', 'management_text_4', 'textarea', 'Get to know the passionate individuals who guide our mission, uphold our values, and tirelessly work to make a difference in the lives of our clients and their families. We are proud to introduce the pillars of Facilitate Care Services, who stand united in their goal to deliver outstanding domiciliary care with dignity, respect, and warmth.', 1],
        ['about', 'content', 'mission_headline', 'text', 'Our Mission', 1],
        ['about', 'content', 'mission_image_url', 'image', '/frontend/images/resource/mission.jpg', 1],
        ['about', 'content', 'mission_text', 'textarea', 'Our primary aim is to promote the independence and dignity of our service users. We focus on developing personalised care plans tailored to the unique needs and interests of each client. We offer person-centred care packages, ranging from 30-minute \'pop-in\' services to 24-hour care in the comfort of your own home.', 1],
        ['about', 'content', 'why_choose_headline', 'text', 'Why choose us.', 1],
        ['about', 'content', 'why_choose_item_1', 'textarea', 'Our care at home services are delivered by a workforce of qualified and experienced healthcare professionals including Registered Nurses, Healthcare Assistants and Support Workers.', 1],
        ['about', 'content', 'why_choose_item_2', 'textarea', 'Registered with the Care Quality Commission, we pride ourselves on giving a truly helpful service that will enhance your quality of life.', 1],
        ['about', 'content', 'why_choose_item_3', 'textarea', 'We treat all our clients as individuals with unique needs and interests and tailor care packages to suit their individual requirements.', 1],
        ['about', 'content', 'why_choose_item_4', 'textarea', 'We regard it as a special privilege to look after people with care and compassion.', 1],
        ['about', 'content', 'why_choose_item_5', 'textarea', 'Our dedicated care management team understands that circumstances change and therefore communicate regularly with clients and their families to adapt care packages to changing needs.', 1],
        ['about', 'content', 'why_choose_item_6', 'textarea', 'We believe that companionship and maintaining social networks is important to many people. That\'s why we also provide companionship services, taking our clients out for outings and appointments, if requested.', 1],
        ['about', 'content', 'why_choose_item_7', 'textarea', 'We are determined that all clients and their families get the support they need regardless of religion, ethnicity, social background, sexual orientation, age, gender or disability.', 1],
        ['about', 'content', 'why_choose_item_8', 'textarea', 'We understand the importance of an active mind in a healthy lifestyle and aim to keep our clients active, healthy and independent by maintaining the quality of life they are used to. We work with our clients to maintain their flexibility, balance and mobility by planning physical activities to help keep them active. We also provide our clients with a range of activities to inspire and invigorate their minds.', 1],
        ['about', 'content', 'why_choose_footer_text_1', 'textarea', 'People struggle to understand the options available to them when a family member needs a special level of care. Families are faced with having to make big decisions on short notice and quickly find that deciding on what best is not often clear or simple.', 1],
        ['about', 'content', 'why_choose_footer_text_2', 'textarea', 'FCS can help clarify what options are available for the specific needs of those who need domiciliary care. We believe each individual needs are unique and we strive to provide the professional and friendly approach to assist with your care options.', 1],
        ['about', 'content', 'why_choose_image_1_url', 'image', '/frontend/images/resource/care-1.jpg', 1],
        ['about', 'content', 'why_choose_image_2_url', 'image', '/frontend/images/resource/care-2.jpg', 1],
        ['about', 'content', 'why_choose_image_3_url', 'image', '/frontend/images/resource/care-3.jpg', 1],
        ['about', 'content', 'why_choose_image_4_url', 'image', '/frontend/images/resource/care-4.jpg', 1],
        ['blog', 'content', 'post_1_image_url', 'image', '/frontend/images/resource/news-4.jpg', 1],
        ['blog', 'content', 'post_1_date', 'text', '12 Feb. 2019', 1],
        ['blog', 'content', 'post_1_meta_author', 'text', 'By :  Admin', 1],
        ['blog', 'content', 'post_1_meta_category', 'text', 'Care, Senior Health', 1],
        ['blog', 'content', 'post_1_meta_comments', 'text', 'Comments: 7', 1],
        ['blog', 'content', 'post_1_title', 'text', 'The Magic of Quality Care', 1],
        ['blog', 'content', 'post_1_excerpt', 'textarea', 'Proactively envisioned multimedia based expertise and cross-media growth strategies. Seamlessly visualize quality intellectual capital without superior collaboration and idea-sharing. Holistically pontificate installed base portals after maintainable products. Interactively coordinate proactive e-commerce via process-centric "outside the box" thinking. Completely pursue scalable customer service ....', 1],
        ['blog', 'content', 'post_2_image_url', 'image', '/frontend/images/resource/news-5.jpg', 1],
        ['blog', 'content', 'post_2_date', 'text', '12 Feb. 2019', 1],
        ['blog', 'content', 'post_2_meta_author', 'text', 'By :  Admin', 1],
        ['blog', 'content', 'post_2_meta_category', 'text', 'Care, Senior Health', 1],
        ['blog', 'content', 'post_2_meta_comments', 'text', 'Comments: 7', 1],
        ['blog', 'content', 'post_2_title', 'text', 'Top 5 Tips for Caregivers During the Holidays', 1],
        ['blog', 'content', 'post_2_excerpt', 'textarea', 'Proactively envisioned multimedia based expertise and cross-media growth strategies. Seamlessly visualize quality intellectual capital without superior collaboration and idea-sharing. Holistically pontificate installed base portals after maintainable products. Interactively coordinate proactive e-commerce via process-centric "outside the box" thinking. Completely pursue scalable customer service ....', 1],
        ['blog', 'content', 'post_3_image_url', 'image', '/frontend/images/resource/news-6.jpg', 1],
        ['blog', 'content', 'post_3_date', 'text', '12 Feb. 2019', 1],
        ['blog', 'content', 'post_3_meta_author', 'text', 'By :  Admin', 1],
        ['blog', 'content', 'post_3_meta_category', 'text', 'Care, Senior Health', 1],
        ['blog', 'content', 'post_3_meta_comments', 'text', 'Comments: 7', 1],
        ['blog', 'content', 'post_3_title', 'text', 'Nationally Endorsed Care', 1],
        ['blog', 'content', 'post_3_excerpt', 'textarea', 'Proactively envisioned multimedia based expertise and cross-media growth strategies. Seamlessly visualize quality intellectual capital without superior collaboration and idea-sharing. Holistically pontificate installed base portals after maintainable products. Interactively coordinate proactive e-commerce via process-centric "outside the box" thinking. Completely pursue scalable customer service ....', 1],
        ['blog', 'content', 'post_4_image_url', 'image', '/frontend/images/resource/news-7.jpg', 1],
        ['blog', 'content', 'post_4_date', 'text', '12 Feb. 2019', 1],
        ['blog', 'content', 'post_4_meta_author', 'text', 'By :  Admin', 1],
        ['blog', 'content', 'post_4_meta_category', 'text', 'Care, Senior Health', 1],
        ['blog', 'content', 'post_4_meta_comments', 'text', 'Comments: 7', 1],
        ['blog', 'content', 'post_4_title', 'text', 'Table for One? Food Safety and Dining Solo', 1],
        ['blog', 'content', 'post_4_excerpt', 'textarea', 'Proactively envisioned multimedia based expertise and cross-media growth strategies. Seamlessly visualize quality intellectual capital without superior collaboration and idea-sharing. Holistically pontificate installed base portals after maintainable products. Interactively coordinate proactive e-commerce via process-centric "outside the box" thinking. Completely pursue scalable customer service ....', 1],
        ['blog', 'content', 'read_more_text', 'text', 'Continue Reading', 1],
        ['blog', 'content', 'pagination_page_1_text', 'text', '1', 1],
        ['blog', 'content', 'pagination_page_2_text', 'text', '2', 1],
        ['blog', 'sidebar', 'categories_heading', 'text', 'Categories', 1],
        ['blog', 'sidebar', 'search_placeholder', 'text', 'Enter Search Keywords', 1],
        ['blog', 'sidebar', 'category_item_1', 'text', 'Care Options', 1],
        ['blog', 'sidebar', 'category_item_2', 'text', 'Why In-Hom Care', 1],
        ['blog', 'sidebar', 'category_item_3', 'text', 'Senior Health & Well-Being', 1],
        ['blog', 'sidebar', 'category_item_4', 'text', 'Selecting Care', 1],
        ['blog', 'sidebar', 'category_item_5', 'text', 'Family CareGiver Support', 1],
        ['blog', 'sidebar', 'category_item_6', 'text', 'Medical Care Service', 1],
        ['blog', 'sidebar', 'recent_news_1_image_url', 'image', '/frontend/images/resource/post-thumb-1.jpg', 1],
        ['blog', 'sidebar', 'recent_news_1_title', 'text', 'Override the digital divide additional.', 1],
        ['blog', 'sidebar', 'recent_news_1_date', 'text', '08 Feb. 2019', 1],
        ['blog', 'sidebar', 'recent_news_2_image_url', 'image', '/frontend/images/resource/post-thumb-2.jpg', 1],
        ['blog', 'sidebar', 'recent_news_2_title', 'text', 'At the end of the day, going, a new...', 1],
        ['blog', 'sidebar', 'recent_news_2_date', 'text', '08 Feb. 2019', 1],
        ['blog', 'sidebar', 'recent_news_3_image_url', 'image', '/frontend/images/resource/post-thumb-3.jpg', 1],
        ['blog', 'sidebar', 'recent_news_3_title', 'text', 'Information will close the loop on...', 1],
        ['blog', 'sidebar', 'recent_news_3_date', 'text', '08 Feb. 2019', 1],
        ['blog', 'sidebar', 'recent_news_4_image_url', 'image', '/frontend/images/resource/post-thumb-4.jpg', 1],
        ['blog', 'sidebar', 'recent_news_4_title', 'text', 'User generated in real-time will have.', 1],
        ['blog', 'sidebar', 'recent_news_4_date', 'text', '08 Feb. 2019', 1],
        ['blog', 'sidebar', 'recent_news_heading', 'text', 'Recent News', 1],
        ['blog', 'sidebar', 'tag_1', 'text', 'Senior care', 1],
        ['blog', 'sidebar', 'tag_2', 'text', 'Analysis', 1],
        ['blog', 'sidebar', 'tag_3', 'text', 'Gallery', 1],
        ['blog', 'sidebar', 'tag_4', 'text', 'Medical Care', 1],
        ['blog', 'sidebar', 'tag_5', 'text', 'Care Skills', 1],
        ['blog', 'sidebar', 'tag_6', 'text', 'Aging Factor', 1],
        ['blog', 'sidebar', 'tags_heading', 'text', 'Tags', 1],
        ['blog', 'footer', 'tagline', 'text', 'Your well being, our priority.', 1],
        ['blog', 'footer', 'contact_heading', 'text', 'Contact Info', 1],
        ['blog', 'footer', 'contact_phone', 'text', '024 7623 1188', 1],
        ['blog', 'footer', 'contact_address', 'textarea', 'Office GE13, 101 Lockhurst Lane, Coventry, CV6 5sf', 1],
        ['blog', 'footer', 'contact_email_label', 'text', 'Email', 1],
        ['blog', 'footer', 'contact_email', 'text', 'info@facilitatecareservices.co.uk', 1],
        ['blog', 'footer', 'cqc_heading', 'text', 'Care Quality Commission', 1],
        ['blog', 'footer', 'cqc_text', 'text', 'CQC inspection report as at 25 May 2021', 1],
        ['blog', 'footer', 'cqc_button_text', 'text', 'See Report', 1],
        ['blogdetail', 'content', 'comments_heading', 'text', 'Comments 4', 1],
        ['blogdetail', 'content', 'feature_image_url', 'image', '/frontend/images/resource/news-4.jpg', 1],
        ['blogdetail', 'content', 'post_date', 'text', '12 Feb. 2019', 1],
        ['blogdetail', 'content', 'post_meta_author', 'text', 'By :  Admin', 1],
        ['blogdetail', 'content', 'post_meta_category', 'text', 'Care, Senior Health', 1],
        ['blogdetail', 'content', 'post_meta_comments', 'text', 'Comments: 7', 1],
        ['blogdetail', 'content', 'headline', 'text', 'The Magic of Quality Care', 1],
        ['blogdetail', 'content', 'body_intro_text', 'textarea', 'Objectively innovate empowered manufactured products whereas parallel platforms. Holisticly predominate extensible testing procedures for reliable supply chains. Dramatically engage top-line web services vis-a-vis cutting-edge deliverables. Proactively envisioned multimedia based expertise and cross-media growth strategies. Seamlessly visualize quality intellectual. Objectively innovate empowered manufactured products whereas parallel platforms. Holisticly predominate extensible testing procedures for reliable supply chains. Dramatically engage top-line web services vis-a-vis cutting-edge deliverables.', 1],
        ['blogdetail', 'content', 'column_text_1', 'textarea', 'Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est.', 1],
        ['blogdetail', 'content', 'column_text_2', 'textarea', 'Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est.', 1],
        ['blogdetail', 'content', 'body_closing_text', 'textarea', 'Here is main text quis nostrud exercitation ullamco laboris nisi here is itealic text ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla rure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat here is link cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.', 1],
        ['blogdetail', 'content', 'comment_1_name', 'text', 'Michale john', 1],
        ['blogdetail', 'content', 'comment_1_text', 'textarea', 'Capitalize on low hanging fruit to identify a ballpark value added activity to beta test. Override the digital divide with additional clickthroughs from DevOps.', 1],
        ['blogdetail', 'content', 'comment_1_date', 'text', '08 Feb, 2019', 1],
        ['blogdetail', 'content', 'comment_1_reply_text', 'text', 'Reply', 1],
        ['blogdetail', 'content', 'comment_2_name', 'text', 'Robert', 1],
        ['blogdetail', 'content', 'comment_2_text', 'textarea', 'Capitalize on low hanging fruit to identify a ballpark value added activity to beta test. Override the digital divide with additional clickthroughs from DevOps.', 1],
        ['blogdetail', 'content', 'comment_2_date', 'text', '08 Feb, 2019', 1],
        ['blogdetail', 'content', 'comment_2_reply_text', 'text', 'Reply', 1],
        ['blogdetail', 'content', 'comment_3_name', 'text', 'Michale john', 1],
        ['blogdetail', 'content', 'comment_3_text', 'textarea', 'Capitalize on low hanging fruit to identify a ballpark value added activity to beta test. Override the digital divide with additional clickthroughs from DevOps.', 1],
        ['blogdetail', 'content', 'comment_3_date', 'text', '08 Feb, 2019', 1],
        ['blogdetail', 'content', 'comment_3_reply_text', 'text', 'Reply', 1],
        ['blogdetail', 'content', 'comment_reply_1_name', 'text', 'Robert', 1],
        ['blogdetail', 'content', 'comment_reply_1_text', 'textarea', 'Capitalize on low hanging fruit to identify a ballpark value added activity to beta test. Override the digital divide with additional clickthroughs from DevOps.', 1],
        ['blogdetail', 'content', 'comment_reply_1_date', 'text', '08 Feb, 2019', 1],
        ['blogdetail', 'content', 'comment_reply_1_reply_text', 'text', 'Reply', 1],
        ['blogdetail', 'content', 'comment_4_name', 'text', 'Michale john', 1],
        ['blogdetail', 'content', 'comment_4_text', 'textarea', 'Capitalize on low hanging fruit to identify a ballpark value added activity to beta test. Override the digital divide with additional clickthroughs from DevOps.', 1],
        ['blogdetail', 'content', 'comment_4_date', 'text', '08 Feb, 2019', 1],
        ['blogdetail', 'content', 'comment_4_reply_text', 'text', 'Reply', 1],
        ['blogdetail', 'content', 'leave_comment_heading', 'text', 'Leave a Comment', 1],
        ['blogdetail', 'content', 'comment_form_name_placeholder', 'text', 'Your name', 1],
        ['blogdetail', 'content', 'comment_form_email_placeholder', 'text', 'Email address', 1],
        ['blogdetail', 'content', 'comment_form_message_placeholder', 'text', 'Write message', 1],
        ['blogdetail', 'content', 'comment_form_submit_text', 'text', 'Submit now', 1],
        ['blogdetail', 'content', 'subheadline', 'text', 'Two Column Text Sample', 1],
        ['blogdetail', 'sidebar', 'categories_heading', 'text', 'Categories', 1],
        ['blogdetail', 'sidebar', 'search_placeholder', 'text', 'Enter Search Keywords', 1],
        ['blogdetail', 'sidebar', 'category_item_1', 'text', 'Care Options', 1],
        ['blogdetail', 'sidebar', 'category_item_2', 'text', 'Why In-Hom Care', 1],
        ['blogdetail', 'sidebar', 'category_item_3', 'text', 'Senior Health & Well-Being', 1],
        ['blogdetail', 'sidebar', 'category_item_4', 'text', 'Selecting Care', 1],
        ['blogdetail', 'sidebar', 'category_item_5', 'text', 'Family CareGiver Support', 1],
        ['blogdetail', 'sidebar', 'category_item_6', 'text', 'Medical Care Service', 1],
        ['blogdetail', 'sidebar', 'recent_news_1_image_url', 'image', '/frontend/images/resource/post-thumb-1.jpg', 1],
        ['blogdetail', 'sidebar', 'recent_news_1_title', 'text', 'Override the digital divide additional.', 1],
        ['blogdetail', 'sidebar', 'recent_news_1_date', 'text', '08 Feb. 2019', 1],
        ['blogdetail', 'sidebar', 'recent_news_2_image_url', 'image', '/frontend/images/resource/post-thumb-2.jpg', 1],
        ['blogdetail', 'sidebar', 'recent_news_2_title', 'text', 'At the end of the day, going, a new...', 1],
        ['blogdetail', 'sidebar', 'recent_news_2_date', 'text', '08 Feb. 2019', 1],
        ['blogdetail', 'sidebar', 'recent_news_3_image_url', 'image', '/frontend/images/resource/post-thumb-3.jpg', 1],
        ['blogdetail', 'sidebar', 'recent_news_3_title', 'text', 'Information will close the loop on...', 1],
        ['blogdetail', 'sidebar', 'recent_news_3_date', 'text', '08 Feb. 2019', 1],
        ['blogdetail', 'sidebar', 'recent_news_4_image_url', 'image', '/frontend/images/resource/post-thumb-4.jpg', 1],
        ['blogdetail', 'sidebar', 'recent_news_4_title', 'text', 'User generated in real-time will have.', 1],
        ['blogdetail', 'sidebar', 'recent_news_4_date', 'text', '08 Feb. 2019', 1],
        ['blogdetail', 'sidebar', 'recent_news_heading', 'text', 'Recent News', 1],
        ['blogdetail', 'sidebar', 'tag_1', 'text', 'Senior care', 1],
        ['blogdetail', 'sidebar', 'tag_2', 'text', 'Analysis', 1],
        ['blogdetail', 'sidebar', 'tag_3', 'text', 'Gallery', 1],
        ['blogdetail', 'sidebar', 'tag_4', 'text', 'Medical Care', 1],
        ['blogdetail', 'sidebar', 'tag_5', 'text', 'Care Skills', 1],
        ['blogdetail', 'sidebar', 'tag_6', 'text', 'Aging Factor', 1],
        ['blogdetail', 'sidebar', 'tags_heading', 'text', 'Tags', 1],
        ['blogdetail', 'footer', 'tagline', 'text', 'Your well being, our priority.', 1],
        ['blogdetail', 'footer', 'contact_heading', 'text', 'Contact Info', 1],
        ['blogdetail', 'footer', 'contact_phone', 'text', '024 7623 1188', 1],
        ['blogdetail', 'footer', 'contact_address', 'textarea', 'Office GE13, 101 Lockhurst Lane, Coventry, CV6 5sf', 1],
        ['blogdetail', 'footer', 'contact_email_label', 'text', 'Email', 1],
        ['blogdetail', 'footer', 'contact_email', 'text', 'info@facilitatecareservices.co.uk', 1],
        ['blogdetail', 'footer', 'cqc_heading', 'text', 'Care Quality Commission', 1],
        ['blogdetail', 'footer', 'cqc_text', 'text', 'CQC inspection report as at 25 May 2021', 1],
        ['blogdetail', 'footer', 'cqc_button_text', 'text', 'See Report', 1],
        ['care', 'content', 'cta_headline', 'text', 'Get Started Today', 1],
        ['care', 'content', 'headline', 'text', 'Companionship', 1],
        ['care', 'content', 'intro_text', 'textarea', 'Our carers will spend time with you to provide companionship in activities or hobbies that you enjoy. These may include:', 1],
        ['care', 'content', 'overview_text_1', 'textarea', 'Companionship focuses on providing social interaction and support for those who may feel isolated or lonely. Unlike personal care, which involves physical assistance with daily activities, companion care emphasizes emotional well-being and social engagement. Our caregivers at Facilitate Care Services engage clients in meaningful activities such as conversation, playing games, listening to music, and accompanying them to appointments and social events.', 1],
        ['care', 'content', 'overview_text_2', 'textarea', 'Social isolation and loneliness can significantly impact mental and physical health, increasing the risk of conditions such as depression, anxiety, and cognitive decline. Companion care helps mitigate these risks by providing regular social interaction and emotional support, fostering a sense of belonging and purpose.', 1],
        ['care', 'content', 'primary_image_url', 'image', '/frontend/images/resource/service-12.jpg', 1],
        ['care', 'content', 'secondary_headline', 'text', 'Compassionate approach to well-being', 1],
        ['care', 'content', 'secondary_text', 'textarea', 'At Facilitate Care Services, we prioritize the dignity and individuality of each client by selecting caregivers known for their compassion, professionalism, and dedication. Embracing a holistic approach that nurtures the mind, body, and spirit, we enhance overall well-being through active engagement. This approach transforms daily activities into opportunities for connection, fostering a sense of achievement and fulfillment.', 1],
        ['care', 'content', 'secondary_image_url', 'image', '/frontend/images/resource/service-13.jpg', 1],
        ['care', 'content', 'services_headline', 'text', 'Personalized Companionship', 1],
        ['care', 'content', 'service_item_1', 'textarea', 'Conversation and Companionship: Engaging in meaningful conversations to provide mental stimulation and companionship.', 1],
        ['care', 'content', 'service_item_2', 'textarea', 'Meal Preparation and Dining Companionship: Assisting with grocery shopping and meal preparation, sharing meals for a social experience.', 1],
        ['care', 'content', 'service_item_3', 'textarea', 'Light Housekeeping: Helping with chores like laundry, dusting, and organizing for a clean environment.', 1],
        ['care', 'content', 'service_item_4', 'textarea', 'Transportation and Errands: Providing transportation to appointments, social events, and running errands.', 1],
        ['care', 'content', 'service_item_5', 'textarea', 'Medication Reminders: Ensuring timely medication intake and managing schedules.', 1],
        ['care', 'content', 'service_item_6', 'textarea', 'Personalized Care Plans: Creating and regularly updating customized care plans to meet unique needs.', 1],
        ['care', 'content', 'cta_text', 'textarea', 'Choosing the right care provider can be tough, but Facilitate Care Services is here to ensure a seamless and reassuring experience. Contact us today to schedule a free consultation and see how our social and companion care services can help your loved one. Let us be your trusted partner in providing caring and supportive services.', 1],
        ['care', 'sidebar', 'button_text', 'text', 'contact us', 1],
        ['care', 'sidebar', 'headline', 'text', 'Find Care Today', 1],
        ['caregiver', 'content', 'apply_button_text', 'text', 'Apply Here', 1],
        ['caregiver', 'content', 'apply_headline', 'text', 'How To Apply', 1],
        ['caregiver', 'content', 'apply_text', 'textarea', 'Interested in joining our team? Click the button below to fill out a quick application form and share your qualifications with us. We look forward to learning more about you!', 1],
        ['caregiver', 'content', 'headline', 'text', 'Caregiver Jobs - Caregivers and Companions Needed', 1],
        ['caregiver', 'content', 'offer_headline', 'text', 'What Facilitate can offer.', 1],
        ['caregiver', 'content', 'primary_image_url', 'image', '/frontend/images/resource/care-4.jpg', 1],
        ['caregiver', 'content', 'roles_headline', 'text', 'Caregiver roles and responsibilities.', 1],
        ['chronical', 'content', 'getting_started_headline', 'text', 'Getting Started', 1],
        ['chronical', 'content', 'goals_headline', 'text', 'Goals of Palliative Care', 1],
        ['chronical', 'content', 'headline', 'text', 'Palliative Care', 1],
        ['chronical', 'content', 'intro_text', 'textarea', 'With our experienced and caring staff, your loved one can remain in their home and continue living independently for longer.', 1],
        ['chronical', 'content', 'overview_text', 'textarea', 'Palliative care is a specialized medical approach aimed at improving the quality of life for patients with serious, life-threatening illnesses. It focuses on providing relief from the symptoms, pain, and stress associated with these conditions. The primary goal is to enhance the quality of life for both the patient and their family, addressing not just physical symptoms but also psychological, social and spiritual issues.', 1],
        ['chronical', 'content', 'testimonial_quote', 'textarea', '" Facilitate Care looked after and cared for my husband Don in the last weeks of his life . They were amazing very gentle spoke to Don as they were caring for him I was very happy with the care they gave him . Ekta and Eric were very special people and I thank them with all my heart . "  Theresa B.', 1],
        ['chronical', 'content', 'primary_image_url', 'image', '/frontend/images/resource/service-14.jpg', 1],
        ['chronical', 'content', 'secondary_headline', 'text', 'Comprehensive Palliative Care Services', 1],
        ['chronical', 'content', 'secondary_text', 'textarea', 'Palliative care with us begins with a comprehensive conversation to understand the patient symptoms and needs. Our caregivers follow recommendations from healthcare professionals to tailor treatments to individual comfort levels. We regularly communicate with patients to assess their pain and other symptoms, collaborating with professionals to determine the best treatment options. If a patient is in pain or uncomfortable, we promptly report to the relevant professionals for assessment, ensuring personalized and compassionate care that enhances the patient quality of life.', 1],
        ['chronical', 'content', 'getting_started_text_1', 'textarea', 'To get started with palliative care at Facilitate Care Services, the first step is to schedule an initial consultation with our palliative care team. During this meeting, we will discuss your symptoms, medical history, and care goals, allowing us to create a personalized care plan tailored to your specific needs and comfort levels.', 1],
        ['chronical', 'content', 'getting_started_text_2', 'textarea', 'Once the initial assessment is complete, our team will collaborate with you, your family, and other healthcare providers to develop a comprehensive care plan. This plan will focus on managing pain, alleviating symptoms, and providing emotional support to improve your quality of life. Regular reviews and adjustments of the care plan ensure that it continues to meet your evolving needs and preferences.', 1],
        ['chronical', 'content', 'getting_started_text_3', 'textarea', 'Palliative care at Facilitate Care Services is an ongoing process. We will continuously support you by coordinating with other medical professionals and offering guidance and assistance to you and your family. Our goal is to ensure that you receive the best possible care and maintain a high quality of life throughout your journey.', 1],
        ['chronical', 'content', 'subheadline', 'text', 'Personalized Care for Your Daily Needs', 1],
        ['chronical', 'sidebar', 'button_text', 'text', 'contact us', 1],
        ['chronical', 'sidebar', 'headline', 'text', 'Find Care Today', 1],
        ['contact', 'content', 'headline', 'text', 'Get In Touch', 1],
        ['contact', 'content', 'office_address', 'textarea', 'Office GE13, 101 Lockhurst Lane, Coventry <br> CV6 5SF', 1],
        ['contact', 'content', 'office_email', 'text', 'enquiries@facilitatecareservices.co.uk', 1],
        ['contact', 'content', 'office_heading', 'text', 'Head Office:', 1],
        ['contact', 'content', 'office_hours', 'text', '9 am to 5 pm Monday to Friday. (24 hr on call service)', 1],
        ['contact', 'content', 'office_image_url', 'image', '/frontend/images/resource/contact-1.jpg', 1],
        ['contact', 'content', 'office_phone', 'text', '024 7623 1188', 1],
        ['contact', 'content', 'subheadline', 'textarea', 'For further details on any of our services or to arrange a free initial consultation you can reach us via any of the methods below', 1],
        ['contact', 'content', 'submit_button_text', 'text', 'Submit now', 1],
        ['discharge', 'content', 'benefits_headline', 'text', 'Benefits of Our Hospital Discharge Service', 1],
        ['discharge', 'content', 'care_coordination_headline', 'text', 'Personalized Care Coordination', 1],
        ['discharge', 'content', 'care_coordination_text', 'textarea', 'Upon discharge, our professional caregivers provide personalized assistance to meet the unique needs of each patient. Services include:', 1],
        ['discharge', 'content', 'closing_text', 'textarea', 'At Facilitate Care Services, our goal is to provide a seamless and compassionate transition from hospital to home, ensuring that our clients receive the highest quality care during their recovery journey. Trust us to be your partner in health and well-being.', 1],
        ['discharge', 'content', 'headline', 'text', 'Supporting your return from hospital.', 1],
        ['discharge', 'content', 'intro_text', 'text', 'We recognize that the transition from hospital to home can be overwhelming.', 1],
        ['discharge', 'content', 'monitoring_text', 'textarea', 'We provide ongoing follow-up and monitoring to ensure that the patient is progressing well and adhering to their recovery plan. Our caregivers are trained to recognize signs of complications and communicate with healthcare providers to address any concerns promptly.', 1],
        ['discharge', 'content', 'monitoring_headline', 'text', 'Follow-Up and Monitoring', 1],
        ['discharge', 'content', 'overview_text', 'textarea', 'Facilitate Care Services excels in providing the essential support needed for a smooth recovery at home. Whether it\'s short-term care following a hospital discharge, rehabilitation care to aid in recovery and the relearning of daily tasks, or long-term solutions such as visiting or live-in care, we customize our services to meet your specific needs.', 1],
        ['discharge', 'content', 'primary_image_url', 'image', '/frontend/images/resource/service-22.jpg', 1],
        ['discharge', 'content', 'secondary_headline', 'text', 'Comprehensive Discharge Planning', 1],
        ['discharge', 'content', 'secondary_text', 'textarea', 'Our dedicated team works closely with hospital staff, patients, and families to develop a personalized discharge plan that addresses medical and emotional needs. We ensure that every aspect of the transition is carefully coordinated, minimizing stress and maximizing comfort.', 1],
        ['discharge', 'content', 'subheadline', 'text', 'Home care after hospital discharge.', 1],
        ['discharge', 'content', 'support_text', 'textarea', 'If you or a loved one is approaching a hospital discharge and requires post-hospital care, whether for a brief period or on an ongoing basis, Facilitate Care Services is here to offer the assistance and support necessary for a successful recovery.', 1],
        ['discharge', 'content', 'testimonial_quote', 'textarea', '" After a nasty fall and a week stay in hospital, I was nervous about having a care package at home, especially as a nurse and only just being 60. I had no need the 2 ladies who come are wonderful, cope with my wobbles and I feel so cared for." Debbie S.', 1],
        ['discharge', 'sidebar', 'button_text', 'text', 'contact us', 1],
        ['discharge', 'sidebar', 'headline', 'text', 'Find Care Today', 1],
        ['elderlyservice', 'content', 'all_needs_headline', 'text', 'Elder Care for All Needs', 1],
        ['elderlyservice', 'content', 'closing_text', 'textarea', 'Our team of experienced care professionals is passionate about making a positive difference in the lives of the elderly. They are trained to handle a variety of needs, from basic assistance with daily tasks to more specialized medical care. With a keen understanding of the challenges faced by older adults, they bring not just skills, but also empathy and respect to their work.', 1],
        ['elderlyservice', 'content', 'headline', 'text', 'Elder Care Service', 1],
        ['elderlyservice', 'content', 'intro_text', 'textarea', 'Let us help you provide the best possible care for the elders in your life. Explore our services, meet our team, and discover how Facilitate Care Services can be your trusted partner in elder care.', 1],
        ['elderlyservice', 'content', 'overview_text_1', 'textarea', 'At Facilitate Care Services, we understand that growing older is a journey best navigated with care, compassion, and expertise. That\'s why we\'re dedicated to providing exceptional domiciliary care services, tailored to meet the unique needs of each elder in the comfort of their own home.', 1],
        ['elderlyservice', 'content', 'overview_text_2', 'textarea', 'Our elder care services are designed to ensure that your loved ones can enjoy their golden years with dignity and independence. We offer a holistic approach to care, combining professional medical support with heartfelt companionship and practical assistance in daily life.', 1],
        ['elderlyservice', 'content', 'primary_image_url', 'image', '/frontend/images/resource/service-15.jpg', 1],
        ['elderlyservice', 'content', 'secondary_headline', 'text', 'Our Elder Care Services Support You to our Clients', 1],
        ['elderlyservice', 'content', 'secondary_text', 'textarea', 'Many seniors prefer to age in their own home, but this can grow more and more difficult as the years pass. Your loved one doesn\'t need to give up on the familiarity and comfort of their home, simply because they cannot remain completely independent. Our personal care services are specially designed to provide additional assistance to enhance your loved one\'s independence.', 1],
        ['elderlyservice', 'content', 'secondary_image_url', 'image', '/frontend/images/resource/service-16.jpg', 1],
        ['elderlyservice', 'content', 'services_headline', 'text', 'Our personal caregivers offer assistance with activities such as:', 1],
        ['elderlyservice', 'content', 'testimonial_quote', 'textarea', '" Facilitate cared exceptionally well for my mother in law. I had peace of mind that she was well looked after. I would not hesitate to recommend them. " Gillian P.', 1],
        ['elderlyservice', 'sidebar', 'button_text', 'text', 'contact us', 1],
        ['elderlyservice', 'sidebar', 'headline', 'text', 'Find Care Today', 1],
        ['faq', 'content', 'headline', 'text', 'Frequently Asked Questions', 1],
        ['faq', 'content', 'subheadline', 'text', 'Discover your question from underneath or present your inquiry from the submit box.', 1],
        ['faq', 'content', 'submit_button_text', 'text', 'Submit now', 1],
        ['faq', 'content', 'submit_headline', 'text', 'Didn\'t find your answer? Submit your question', 1],
        ['gallery', 'content', 'image_1_url', 'image', '/frontend/images/gallery/11.jpg', 1],
        ['gallery', 'content', 'image_2_url', 'image', '/frontend/images/gallery/12.jpg', 1],
        ['gallery', 'content', 'image_3_url', 'image', '/frontend/images/gallery/13.jpg', 1],
        ['gallery', 'content', 'image_4_url', 'image', '/frontend/images/gallery/14.jpg', 1],
        ['gallery', 'content', 'image_5_url', 'image', '/frontend/images/gallery/15.jpg', 1],
        ['gallery', 'content', 'image_6_url', 'image', '/frontend/images/gallery/16.jpg', 1],
        ['gallery', 'content', 'image_7_url', 'image', '/frontend/images/gallery/17.jpg', 1],
        ['gallery', 'content', 'image_8_url', 'image', '/frontend/images/gallery/18.jpg', 1],
        ['gallery', 'content', 'image_9_url', 'image', '/frontend/images/gallery/19.jpg', 1],
        ['lifecare', 'content', 'all_needs_headline', 'text', 'End of Life Care for All Needs', 1],
        ['lifecare', 'content', 'closing_text', 'textarea', 'Our caregivers are ready to provide quality services to everyone in need of assistance, including elders, those who are living with a disability, and those who are in recovery from an injury or living with a chronic condition. We focus on your individual needs, regardless of the reason you need some extra help. Let us help your day-to-day life be simple, comfortable, and safe.', 1],
        ['lifecare', 'content', 'headline', 'text', 'End of Life Care Service', 1],
        ['lifecare', 'content', 'intro_text', 'text', 'Do you or a loved one need a little bit of extra help in your home?', 1],
        ['lifecare', 'content', 'overview_text', 'textarea', 'With our experienced and caring staff, your loved one can remain in their home and continue living independently for longer. Our customized care plans will match your loved one needs exactly, so they can enjoy care services that are uniquely tailored to them. Our compassionate caregivers are here to provide your loved one with exceptional support, while giving you the peace of mind you deserve.', 1],
        ['lifecare', 'content', 'primary_image_url', 'image', '/frontend/images/resource/service-17.jpg', 1],
        ['lifecare', 'content', 'secondary_headline', 'text', 'End of Life Care Support for your elders', 1],
        ['lifecare', 'content', 'secondary_text', 'textarea', 'Many seniors prefer to age in their own home, but this can grow more and more difficult as the years pass. Your loved one doesn\'t need to give up on the familiarity and comfort of their home, simply because they cannot remain completely independent. Our personal care services are specially designed to provide additional assistance to enhance your loved one\'s independence.', 1],
        ['lifecare', 'content', 'services_headline', 'text', 'Our personal caregivers offer assistance with activities such as:', 1],
        ['lifecare', 'content', 'subheadline', 'text', 'Day-to-Day Care for Your Daily Needs', 1],
        ['lifecare', 'content', 'tagline_text', 'text', 'Let our experienced caregivers help your family', 1],
        ['lifecare', 'sidebar', 'button_text', 'text', 'contact us', 1],
        ['lifecare', 'sidebar', 'headline', 'text', 'Find Care Today', 1],
        ['livein', 'content', 'headline', 'text', 'Live In Care', 1],
        ['livein', 'content', 'intro_text', 'textarea', 'At Facilitate Care Services, we are committed to providing exceptional live-in care that prioritizes your comfort, independence, and well-being.', 1],
        ['livein', 'content', 'needs_headline', 'text', 'When Might You Need Live-In Care?', 1],
        ['livein', 'content', 'needs_item_1', 'textarea', 'Hospital Discharge: Ensuring a smooth transition from hospital to home, with continued care and support.', 1],
        ['livein', 'content', 'needs_item_2', 'textarea', 'Returning from a Care Home: Facilitating the move back to your own home after staying in a care facility.', 1],
        ['livein', 'content', 'needs_item_3', 'textarea', 'Cost-Efficient for Couples: Allowing couples to stay together while sharing the cost of a single caregiver.', 1],
        ['livein', 'content', 'needs_item_4', 'textarea', 'Long-Term Illness: Offering consistent care for those dealing with chronic health conditions.', 1],
        ['livein', 'content', 'needs_item_5', 'textarea', 'Retirement at Home: Supporting those who wish to retire in the comfort of their own home.', 1],
        ['livein', 'content', 'overview_text', 'textarea', 'Live-in care is a comprehensive solution for those wishing to maintain their independence while receiving 24-hour support in their own home. Facilitate Care Services provides live-in carers who move into your home to assist with daily activities, health management, and companionship. This allows you to continue living according to your chosen lifestyle in the comfort of familiar surroundings. Our dedicated carers offer round-the-clock care, ensuring you have a helping hand at a moment notice. They not only provide practical and physical assistance but also offer valuable companionship and emotional support.', 1],
        ['livein', 'content', 'primary_image_url', 'image', '/frontend/images/resource/service-18.jpg', 1],
        ['livein', 'content', 'secondary_headline', 'text', 'Personalized Home Care for Lasting Comfort', 1],
        ['livein', 'content', 'secondary_text', 'textarea', 'Our carers are handpicked for their kind and empathetic personalities, and your local care manager will match you with someone who shares your interests, ensuring a harmonious living environment. By limiting the number of carers within a care package, we foster stronger relationships and provide continuity, which is particularly beneficial for those with dementia. Our live-in care gives you and your family peace of mind, knowing that expert care tailored to your needs is delivered by qualified healthcare professionals in your own home.', 1],
        ['livein', 'content', 'subheadline', 'text', 'Daily Support for Your Everyday Needs', 1],
        ['livein', 'content', 'closing_text', 'textarea', 'With Facilitate Care Services, you receive expert, carefully matched care in the comfort of your home. Our approach ensures strong relationships and continuity of care, providing a flexible, stress-free experience. Contact us today to discover how we can deliver the exceptional care you deserve.', 1],
        ['livein', 'content', 'why_choose_headline', 'text', 'Why Choose Us', 1],
        ['livein', 'content', 'why_choose_item_1', 'textarea', 'We provide expert care tailored to your specific needs.', 1],
        ['livein', 'content', 'why_choose_item_2', 'textarea', 'Enjoy care in familiar surroundings, offering a more personalized, flexible experience that enhances comfort, reduces stress, and maintains independence.', 1],
        ['livein', 'content', 'why_choose_item_3', 'textarea', 'Our carers are carefully matched to meet your needs, ensuring comfort and compatibility.', 1],
        ['livein', 'content', 'why_choose_item_4', 'textarea', 'Limiting the number of carers in each package fosters stronger relationships and higher quality care.', 1],
        ['livein', 'content', 'why_choose_item_5', 'textarea', 'Our approach is particularly beneficial for those with dementia, emphasizing familiarity and continuity.', 1],
        ['livein', 'sidebar', 'button_text', 'text', 'contact us', 1],
        ['livein', 'sidebar', 'headline', 'text', 'Find Care Today', 1],
        ['personalcare', 'content', 'additional_assistance_headline', 'text', 'Additional Home Assistance Services', 1],
        ['personalcare', 'content', 'additional_assistance_text', 'textarea', 'Our dedicated team offers a wide array of services to make your daily life easier, including:', 1],
        ['personalcare', 'content', 'domestic_support_headline', 'text', 'Domestic Care Support for a Comfortable Lifestyle', 1],
        ['personalcare', 'content', 'domestic_support_text', 'textarea', 'Understanding that personal care is just one aspect of maintaining a comfortable lifestyle, we extend our services to include thorough domestic care support. This service is ideal for those who manage their personal care effectively but could benefit from some extra help with household tasks. Our domestic care provides comprehensive assistance right in your home, ensuring a clean, organized, and well-maintained living space.', 1],
        ['personalcare', 'content', 'headline', 'text', 'Personal Care Services', 1],
        ['personalcare', 'content', 'overview_text_1', 'textarea', 'Personal care refers to the assistance provided to individuals who require support with daily activities that are essential for maintaining personal hygiene, health, and overall well-being. At Facilitate Care Services, we place immense value on providing care that not only addresses your physical needs but also nurtures your sense of comfort and dignity. Our Personal Care Services are a testament to our commitment to deliver exceptional care within the sanctuary of your home. We understand that there\'s no place like home, and our goal is to make it possible for you to stay there as long as you wish, surrounded by memories and the warmth of familiarity.', 1],
        ['personalcare', 'content', 'overview_text_2', 'textarea', 'Our approach to care is holistic and patient-centered. We recognise that each individual has their own set of preferences, routines, and needs. This understanding drives our mission to offer care that is as unique as you are. Whether it\'s your favorite breakfast routine or a specific bedtime ritual, our caregivers are attentive to those little details that make a big difference in your daily life.', 1],
        ['personalcare', 'content', 'primary_image_url', 'image', '/frontend/images/resource/service-24.jpg', 1],
        ['personalcare', 'content', 'secondary_headline', 'text', 'Personalised Home Care Services', 1],
        ['personalcare', 'content', 'secondary_text', 'textarea', 'Living comfortably at home is a cherished preference, but maintaining independence can be challenging. We believe needing extra help shouldn\'t mean leaving home. Our personalised care services provide the necessary support for individuals to live independently and with dignity. Our goal is to enhance independence, ensuring daily life is enjoyed with confidence and comfort in familiar surroundings.', 1],
        ['personalcare', 'content', 'services_headline', 'text', 'Our Personal Care Services Include:', 1],
        ['personalcare', 'content', 'subheadline', 'text', 'Comprehensive Care for Your Daily Needs', 1],
        ['personalcare', 'content', 'testimonial_quote', 'textarea', '" Facilitate took over the care of my uncle recently after a number of failings by the previous carers. The difference in care was amazing. The carers provided wonderful end of life care in terms of both personal and social care, spending time to listen to my uncle\'s stories and hold his hand. Highly recomended " Nigel J.', 1],
        ['personalcare', 'sidebar', 'button_text', 'text', 'contact us', 1],
        ['personalcare', 'sidebar', 'headline', 'text', 'Find Care Today', 1],
        ['respitecare', 'content', 'headline', 'text', 'Respite Care Service', 1],
        ['respitecare', 'content', 'overview_text_1', 'textarea', 'Respite care is a temporary arrangement designed to provide relief for primary caregivers. Whether you need support for a short-term basis due to your regular caregiver being unavailable, or you require assistance following surgery or hospitalization, respite care offers the perfect solution. This service ensures that your loved ones continue to receive the highest quality care while allowing primary caregivers to take a much-needed break.', 1],
        ['respitecare', 'content', 'overview_text_2', 'textarea', 'We understand the challenges of caregiving and are committed to providing the break you need to rejuvenate, while ensuring your loved ones continue to live comfortably and independently in their own homes.', 1],
        ['respitecare', 'content', 'primary_image_url', 'image', '/frontend/images/resource/service-19.jpg', 1],
        ['respitecare', 'content', 'secondary_headline', 'text', 'Tailored Respite Care Services', 1],
        ['respitecare', 'content', 'secondary_text', 'textarea', 'Our respite care services are designed to give you, as a caregiver, a break from your daily duties so you can recharge. Recognizing your vital role, we emphasize self-care while ensuring your loved one receives the best possible attention at home. Our tailored care plans meet each individual\'s unique needs, with compassionate, skilled caregivers dedicated to delivering exceptional support and providing you with assurance and peace of mind.', 1],
        ['respitecare', 'content', 'secondary_image_url', 'image', '/frontend/images/resource/service-20.jpg', 1],
        ['respitecare', 'content', 'services_headline', 'text', 'Personalized Respite Care Services', 1],
        ['respitecare', 'content', 'services_intro_text', 'textarea', 'At Facilitate Care Services, we understand the importance of continuity and familiarity in care. Our respite care is designed to fit seamlessly into your existing routine, ensuring that you or your loved one feels comfortable and secure. Here\'s what you can expect from our comprehensive respite care services:', 1],
        ['respitecare', 'content', 'service_item_1', 'textarea', 'Personalized Support: We tailor our care plans to meet the unique needs and preferences of each individual. This includes assistance with personal grooming, bathing, and toileting.', 1],
        ['respitecare', 'content', 'service_item_2', 'textarea', 'Mobility and Transfers: Our caregivers are trained to help with mobility, ensuring safe transfers from bed to chair, and assisting with walking.', 1],
        ['respitecare', 'content', 'service_item_3', 'textarea', 'Medication Management: We provide reliable medication reminders and management to ensure health and well-being.', 1],
        ['respitecare', 'content', 'service_item_4', 'textarea', 'Companionship: Emotional support and companionship are key components of our care, helping to alleviate loneliness and provide social interaction.', 1],
        ['respitecare', 'content', 'service_item_5', 'textarea', 'Housekeeping and Meal Preparation: Our caregivers can assist with light housekeeping tasks and prepare nutritious meals to suit dietary requirements.', 1],
        ['respitecare', 'content', 'service_item_6', 'textarea', 'Errands and Shopping: We can run errands and handle shopping needs, bringing convenience to your doorstep.', 1],
        ['respitecare', 'content', 'service_item_7', 'textarea', 'Bedtime Routines: Assistance with evening routines ensures a comfortable and restful night.', 1],
        ['respitecare', 'content', 'trust_headline', 'text', 'Respite Care you can trust', 1],
        ['respitecare', 'content', 'trust_text', 'textarea', 'Our team commitment to providing compassionate, skilled, and personalized care makes a significant difference in the lives of those we serve. By choosing our respite care service, you are ensuring that your loved one is in capable and caring hands. Our flexible care plans adapt to your loved one needs, offering peace of mind for you and quality care for them. Trust us to provide the support needed to maintain their well-being and happiness.', 1],
        ['respitecare', 'sidebar', 'button_text', 'text', 'contact us', 1],
        ['respitecare', 'sidebar', 'headline', 'text', 'Find Care Today', 1],
        ['specialcare', 'content', 'all_needs_headline', 'text', 'Special Needs Care for All Needs', 1],
        ['specialcare', 'content', 'closing_text', 'textarea', 'Our caregivers are ready to provide quality services to everyone in need of assistance, including elders, those who are living with a disability, and those who are in recovery from an injury or living with a chronic condition. We focus on your individual needs, regardless of the reason you need some extra help. Let us help your day-to-day life be simple, comfortable, and safe.', 1],
        ['specialcare', 'content', 'headline', 'text', 'Special Needs Care', 1],
        ['specialcare', 'content', 'overview_text_1', 'textarea', 'Special Needs Care encompasses services designed to support individuals with disabilities, focusing on improving their quality of life and maximizing their potential. This includes assistance with daily activities, medical management, and therapeutic interventions like physical and occupational therapy. It also offers educational support, behavioral strategies, respite care for caregivers, and emotional and social support to foster community integration. Utilizing adaptive equipment and technology, special needs care involves a multidisciplinary team to address the holistic needs of individuals.', 1],
        ['specialcare', 'content', 'overview_text_2', 'textarea', 'At Facilitate Care Services, we are dedicated to providing exceptional special needs care, ensuring the highest quality of life for your loved one. Our highly trained and certified caregivers deliver personalized care tailored to each individual\'s unique requirements. Our approach includes comprehensive daily assistance, medical management, and specialized therapies to meet your loved one\'s holistic needs. We also implement positive behavioral strategies and encourage community involvement to promote social and emotional well-being.', 1],
        ['specialcare', 'content', 'primary_image_url', 'image', '/frontend/images/resource/service-26.jpg', 1],
        ['specialcare', 'content', 'secondary_headline', 'text', 'Special Needs Care Services to our Clients', 1],
        ['specialcare', 'content', 'secondary_text', 'textarea', 'Many seniors prefer to age in their own home, but this can grow more and more difficult as the years pass. Your loved one doesn\'t need to give up on the familiarity and comfort of their home, simply because they cannot remain completely independent. Our personal care services are specially designed to provide additional assistance to enhance your loved one\'s independence.', 1],
        ['specialcare', 'content', 'secondary_image_url', 'image', '/frontend/images/resource/service-25.jpg', 1],
        ['specialcare', 'content', 'services_headline', 'text', 'Our personal caregivers offer assistance with activities such as:', 1],
        ['specialcare', 'sidebar', 'button_text', 'text', 'contact us', 1],
        ['specialcare', 'sidebar', 'headline', 'text', 'Find Care Today', 1],
        ['started', 'content', 'headline', 'text', 'Care Planning Process.', 1],
        ['started', 'content', 'primary_image_url', 'image', '/frontend/images/resource/started.jpg', 1],
        ['started', 'content', 'steps_headline', 'text', 'The following steps are commonly taken during the care planning process.', 1],
        ['started', 'content', 'subheadline', 'text', 'Learn How To Get Started with our Care services.', 1],
        ['started', 'cta', 'button_text', 'text', 'Contact Us', 1],
        ['started', 'cta', 'headline', 'text', 'Get Started Today', 1],
        ['started', 'cta', 'subheadline', 'textarea', 'If you or your loved one is in need of caregiving services, we encourage you to reach out for a complimentary evaluation so we can customize an appropriate plan of care.', 1],
        ['support', 'content', 'primary_image_url', 'image', '/frontend/images/resource/service-10.png', 1],
        ['support', 'content', 'overview_text', 'textarea', 'Supported Living is a tailored service designed to help individuals with various needs, including physical disabilities, learning disabilities, mental health challenges, and age-related conditions, to live independently in their own homes or communities. This approach focuses on providing personalized support that empowers individuals to maintain control over their lives while enhancing their quality of life.', 1],
        ['support', 'content', 'secondary_image_url', 'image', '/frontend/images/resource/service-23.jpg', 1],
        ['support', 'content', 'secondary_text', 'textarea', 'We are dedicated to offering comprehensive supported living services that are adaptable to the unique requirements of each person we serve. Our aim is to foster independence, dignity, and community integration for all our clients. We understand that every individual has different needs and aspirations, and we strive to provide the support that empowers them to live life on their terms. Whether it is assisting with daily activities, helping to manage health and wellness, or facilitating social and community engagement, our tailored approach ensures that each client receives the personalized care and support they deserve.', 1],
        ['support', 'content', 'services_headline', 'text', 'Our supported living services.', 1],
        ['support', 'content', 'service_item_1', 'textarea', 'Personalized Support Plans: We work closely with you to create a support plan that reflects your personal preferences, needs, and goals. This plan is reviewed regularly to ensure it continues to meet your evolving requirements.', 1],
        ['support', 'content', 'service_item_2', 'textarea', 'Daily Living Assistance: Our dedicated team can assist with a variety of daily tasks, including personal care, meal preparation, household chores, and managing medication.', 1],
        ['support', 'content', 'service_item_3', 'textarea', 'Community Integration: We encourage and support you to participate in community activities, social events, and employment opportunities, helping you to build and maintain meaningful relationships.', 1],
        ['support', 'content', 'service_item_4', 'textarea', 'Health and Wellbeing: Our staff can support you with healthcare appointments, exercise routines, and accessing mental health services to ensure your overall wellbeing.', 1],
        ['support', 'content', 'testimonial_quote', 'textarea', '" Outstanding on all levels. Mum carers are reliable, professional, well trained and friendly. In spite of the fact that she has Alzheimers, she feels really secure with them. They have worked hard to make her feel safe and her face lights up when she sees them. They never rush her and are really thorough with her personal care and aware of the importance of maintaining her dignity. We have no hesitation in recommending Facilitate Care to any family who have a loved one they want to be treated with genuine care and a high level of professionalism. "  Yvonne S.', 1],
        ['support', 'content', 'closing_text', 'textarea', 'At Facilitate Care Services, you are at the heart of everything we do. Our person-centered approach ensures we listen to your needs and work collaboratively to provide the support you want. Our highly trained and compassionate staff are dedicated to delivering high-quality care, matching you with caregivers who understand your specific needs and preferences. We offer flexible and adaptive services, recognizing that your needs may change over time, and we adjust our support accordingly to continue providing the best care. Our commitment to independence empowers you to take control of your life, make your own decisions, and achieve your personal goals.', 1],
        ['support', 'sidebar', 'button_text', 'text', 'contact us', 1],
        ['support', 'sidebar', 'headline', 'text', 'Find Care Today', 1],
        ['surgery', 'content', 'all_needs_headline', 'text', '24/7 Day Support for All Needs', 1],
        ['surgery', 'content', 'closing_text', 'textarea', 'Our caregivers are ready to provide quality services to everyone in need of assistance, including elders, those who are living with a disability, and those who are in recovery from an injury or living with a chronic condition. We focus on your individual needs, regardless of the reason you need some extra help. Let us help your day-to-day life be simple, comfortable, and safe.', 1],
        ['surgery', 'content', 'headline', 'text', 'We Give 24/7 Day Support', 1],
        ['surgery', 'content', 'overview_text_1', 'textarea', 'Do you or a loved one need a little bit of extra help in your home? Chores, errands, and personal tasks can be difficult or unsafe for many individuals to perform on their own. When your loved one requires assistance with bathing and dressing, our personal care services are designed just for their needs.', 1],
        ['surgery', 'content', 'overview_text_2', 'textarea', 'With our experienced and caring staff, your loved one can remain in their home and continue living independently for longer. Our customized care plans will match your loved one needs exactly, so they can enjoy care services that are uniquely tailored to them. Our compassionate caregivers are here to provide your loved one with exceptional support, while giving you the peace of mind you deserve.', 1],
        ['surgery', 'content', 'primary_image_url', 'image', '/frontend/images/resource/service-8.jpg', 1],
        ['surgery', 'content', 'secondary_headline', 'text', '24/7 Day Care Services Support You to our Clients', 1],
        ['surgery', 'content', 'secondary_text', 'textarea', 'Many seniors prefer to age in their own home, but this can grow more and more difficult as the years pass. Your loved one doesn\'t need to give up on the familiarity and comfort of their home, simply because they cannot remain completely independent. Our personal care services are specially designed to provide additional assistance to enhance your loved one\'s independence.', 1],
        ['surgery', 'content', 'secondary_image_url', 'image', '/frontend/images/resource/service-9.jpg', 1],
        ['surgery', 'content', 'services_headline', 'text', 'Our personal caregivers offer assistance with activities such as:', 1],
        ['surgery', 'content', 'tagline_text', 'text', 'Let our experienced caregivers help your family', 1],
        ['surgery', 'sidebar', 'button_text', 'text', 'contact us', 1],
        ['surgery', 'sidebar', 'headline', 'text', 'Find Care Today', 1],
        ['team', 'content', 'headline', 'text', 'Our CareGivers', 1],
        ['team', 'content', 'member_1_image_url', 'image', '/frontend/images/resource/team-1.jpg', 1],
        ['team', 'content', 'member_1_name', 'text', 'Merry Desulva', 1],
        ['team', 'content', 'member_1_role', 'text', 'Caregiver for Elders', 1],
        ['team', 'content', 'member_2_image_url', 'image', '/frontend/images/resource/team-2.jpg', 1],
        ['team', 'content', 'member_2_name', 'text', 'Roseen', 1],
        ['team', 'content', 'member_2_role', 'text', 'Take care of Nursing', 1],
        ['team', 'content', 'member_3_image_url', 'image', '/frontend/images/resource/team-3.jpg', 1],
        ['team', 'content', 'member_4_image_url', 'image', '/frontend/images/resource/team-4.jpg', 1],
        ['team', 'content', 'subheadline', 'text', 'Our caregivers are trained specifically to provide in-home care.', 1],
        ['caregiver', 'content', 'contact_address_line_1', 'text', 'Office GE13, 101 Lockhurst Lane', 1],
        ['caregiver', 'content', 'contact_address_line_2', 'text', 'Coventry', 1],
        ['caregiver', 'content', 'contact_address_line_3', 'text', 'CV6 5SF', 1],
        ['caregiver', 'content', 'contact_area_text', 'textarea', 'Our office is located in Coventry and we provide care services in Kenilworth, Leamington Spa, Warwick, Coventry, Bedworth, Stratford & Nuneaton.', 1],
        ['caregiver', 'content', 'contact_email_text', 'text', 'Email: enquiries@facilitatecareservices.co.uk', 1],
        ['caregiver', 'content', 'contact_headline', 'text', 'Contact Us.', 1],
        ['caregiver', 'content', 'contact_hours_text', 'text', 'Opening Hours: 9 am to 5 pm Monday to Friday', 1],
        ['caregiver', 'content', 'contact_intro_text', 'textarea', 'For further details on any of our services or to arrange a free initial consultation you can reach us via any of the methods below:', 1],
        ['caregiver', 'content', 'contact_on_call_text', 'text', '24hr on call service available', 1],
        ['caregiver', 'content', 'contact_phone_text', 'text', 'Tel: 024 7623 1188', 1],
        ['caregiver', 'content', 'contact_support_text', 'textarea', 'Our Care Managers are skilled at assessing personal and social needs and can advise you on the best support for your particular needs.', 1],
        ['caregiver', 'content', 'duties_intro_text', 'textarea', 'Our caregivers provide compassionate support, helping clients with daily living activities while ensuring their dignity and well-being. As a caregiver, your role may include the following tasks:', 1],
        ['caregiver', 'content', 'duty_item_1', 'text', 'Assisting clients with personal care services such as oral care, bathing, toileting, dressing, and grooming', 1],
        ['caregiver', 'content', 'duty_item_2', 'text', 'Medication reminders', 1],
        ['caregiver', 'content', 'duty_item_3', 'text', 'Light housekeeping including laundry, dishes and vacuuming', 1],
        ['caregiver', 'content', 'duty_item_4', 'text', 'Planning, preparing and serving meals', 1],
        ['caregiver', 'content', 'duty_item_5', 'text', 'Assistance with transfers, walking and physical activity', 1],
        ['caregiver', 'content', 'duty_item_6', 'text', 'Assistance with transportation to appointments, activities, errands, and shopping', 1],
        ['caregiver', 'content', 'duty_item_7', 'text', 'Providing companionship and cheerful, positive assistance at all times', 1],
        ['caregiver', 'content', 'intro_text_1', 'textarea', 'We know how important our caregivers are. They are the backbone of our business. Without our team of compassionate caregivers, we would not be able to help our clients the way we do. We try to make a difference for everyone we are involved with; the people who use our service, our staff and our strategic partners. The approach is one based on open and honest communication, transparency and the highest professional standards aiming to making a difference in caring at home.', 1],
        ['caregiver', 'content', 'intro_text_2', 'textarea', 'If you keen to be involved in a caring role that provides quality support to people at home, and you have compassion for individuals in a diverse range of situations, we would really like you to join our growing team.', 1],
        ['caregiver', 'content', 'offer_item_1', 'text', 'Supportive working environment.', 1],
        ['caregiver', 'content', 'offer_item_2', 'text', 'Full training to undertake the role and meet legislation.', 1],
        ['caregiver', 'content', 'offer_item_3', 'text', 'Flexible working hours available, if required.', 1],
        ['caregiver', 'content', 'offer_item_4', 'text', 'Training towards a recognised qualification in care.', 1],
        ['caregiver', 'content', 'offer_item_5', 'text', 'Competitive rates of pay (please contact our office for enquiries).', 1],
        ['caregiver', 'content', 'requirements_text_1', 'textarea', 'Experience would be an advantage but it not essential. Comprehensive and paid training will be provided for you during the first two weeks of the recruitment process.', 1],
        ['caregiver', 'content', 'requirements_text_2', 'textarea', 'The job role requires you to: provide personal care, perform domestic duties, promote independence, dignity & well-being and generally support people who use our service in their own homes.', 1],
        ['caregiver', 'content', 'requirements_text_3', 'textarea', 'Driving license and own transport would be advantageous if you looking for a greater number of hours. Successful applicants are required to provide a DBS Enhanced Disclosure.', 1],
        ['caregiver', 'content', 'requirements_text_4', 'text', 'Facilitate Care Services Limited is an Equal Opportunities employer.', 1],
        ['caregiver', 'content', 'roles_text_1', 'textarea', 'The role of the care/support worker is to promote the opportunity for service users to live in the community as long as possible, by providing care and support to the individuals and their families, and to enable service users to live as independently, comfortably and securely as possible.', 1],
        ['caregiver', 'content', 'roles_text_2', 'textarea', 'We expect our care/support workers to take individual responsibility for the delivery of personal and practical care needs of service users, in line with the practices, procedures and policies of Facilitate Care Services.', 1],
        ['caregiver', 'content', 'roles_text_3', 'textarea', 'The post holder will undertake all care work with the sensitivity and empathy required to provide services in a way which will preserve the dignity, privacy, choice, independence, fulfilment and rights of the service user and his/her usual carer/members of family.', 1],
        ['caregiver', 'content', 'roles_text_4', 'textarea', 'Please note that we fully vet and check all our employees credentials and a criminal disclosure is undertaken prior to commencement of employment at a fee.', 1],
        ['caregiver', 'content', 'roles_text_5', 'textarea', 'It will also be necessary for us to obtain two written references with one being from your current or most recent employer.', 1],
        ['chronical', 'content', 'getting_started_cta_link_text', 'text', 'Contact our team', 1],
        ['chronical', 'content', 'getting_started_cta_suffix', 'text', 'to learn more!', 1],
        ['chronical', 'content', 'getting_started_cta_text', 'textarea', 'Starting palliative care with us involves open communication, thorough planning, and continuous support.', 1],
        ['chronical', 'content', 'goals_item_1', 'text', 'Aligning treatment outcomes with the patient values and preferences', 1],
        ['chronical', 'content', 'goals_item_2', 'text', 'Improving quality of life for both the patient and the family', 1],
        ['chronical', 'content', 'goals_item_3', 'text', 'Minimizing pain and discomfort', 1],
        ['chronical', 'content', 'goals_item_4', 'text', 'Alleviating emotional distress, anxiety, or depression', 1],
        ['chronical', 'content', 'goals_item_5', 'text', 'Assisting with safety, mobility, and equipment', 1],
        ['chronical', 'content', 'goals_item_6', 'text', 'Empowering patients and caregivers to make decisions that are right for them', 1],
        ['discharge', 'content', 'benefits_item_1', 'text', 'Enhanced Recovery: Professional care that promotes quicker and more effective recovery.', 1],
        ['discharge', 'content', 'benefits_item_2', 'text', 'Peace of Mind: Knowing that your loved one is receiving expert care and support.', 1],
        ['discharge', 'content', 'benefits_item_3', 'text', 'Reduced Re-admission Rates: Proper post-hospital care reduces the likelihood of re-admission.', 1],
        ['discharge', 'content', 'benefits_item_4', 'text', 'Convenience: Comprehensive service that handles all aspects of the discharge process.', 1],
        ['discharge', 'content', 'care_coordination_item_1', 'text', 'Medication Management: Ensuring that prescriptions are filled and administered correctly.', 1],
        ['discharge', 'content', 'care_coordination_item_2', 'text', 'Personal Care Assistance: Helping with daily activities such as bathing, dressing, and grooming.', 1],
        ['discharge', 'content', 'care_coordination_item_3', 'text', 'Nutritional Support: Preparing meals that meet dietary requirements and support recovery.', 1],
        ['discharge', 'content', 'care_coordination_item_4', 'text', 'Emotional Support: Offering companionship and emotional encouragement to promote overall well-being.', 1],
        ['discharge', 'content', 'service_cta_link_text', 'text', 'Contact our team', 1],
        ['discharge', 'content', 'service_cta_suffix', 'text', 'to learn more!', 1],
        ['discharge', 'content', 'service_cta_text', 'text', 'Let our experienced caregivers help your family today.', 1],
        ['elderlyservice', 'content', 'service_cta_link_text', 'text', 'Contact our team', 1],
        ['elderlyservice', 'content', 'service_cta_suffix', 'text', 'to learn more!', 1],
        ['elderlyservice', 'content', 'service_cta_text', 'text', 'Let our experienced caregivers help your family today.', 1],
        ['elderlyservice', 'content', 'services_item_1', 'text', 'Personal grooming, bathing, toileting, and hygiene tasks', 1],
        ['elderlyservice', 'content', 'services_item_2', 'text', 'Mobility and transfers', 1],
        ['elderlyservice', 'content', 'services_item_3', 'text', 'Medication reminders and monitoring', 1],
        ['elderlyservice', 'content', 'services_item_4', 'text', 'Companionship', 1],
        ['elderlyservice', 'content', 'services_item_5', 'text', 'Light housekeeping, meal planning, and meal preparation', 1],
        ['elderlyservice', 'content', 'services_item_6', 'text', 'Shopping and errands', 1],
        ['faq', 'content', 'answer_1', 'textarea', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.', 1],
        ['faq', 'content', 'answer_2', 'textarea', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.', 1],
        ['faq', 'content', 'answer_3', 'textarea', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.', 1],
        ['faq', 'content', 'answer_4', 'textarea', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.', 1],
        ['faq', 'content', 'answer_5', 'textarea', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.', 1],
        ['faq', 'content', 'answer_6', 'textarea', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.', 1],
        ['faq', 'content', 'answer_7', 'textarea', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.', 1],
        ['faq', 'content', 'answer_8', 'textarea', 'Lorem ipsum dolor sit amet, vix an natum labitur eleifd, mel am laoreet menandri. Ei justo complectitur duo. Ei mundi solet utos soletu possit quo. Sea cu justo laudem. An utinam consulatu eos, est facilis.', 1],
        ['faq', 'content', 'question_1', 'text', 'Bring to the table win-win survival strategies?', 1],
        ['faq', 'content', 'question_2', 'text', 'Override the digital divide with additional clickthroughs from DevOps?', 1],
        ['faq', 'content', 'question_3', 'text', 'At the end of the day, going forward?', 1],
        ['faq', 'content', 'question_4', 'text', 'Information highway will close the loop on?', 1],
        ['faq', 'content', 'question_5', 'text', 'User generated content in real-time will have?', 1],
        ['faq', 'content', 'question_6', 'text', 'Normal that has evolved from generation on the runway heading towards?', 1],
        ['faq', 'content', 'question_7', 'text', 'Focusing solely on the bottom line?', 1],
        ['faq', 'content', 'question_8', 'text', 'Multiple touchpoints for offshoring?', 1],
        ['lifecare', 'content', 'service_cta_link_text', 'text', 'Contact our team', 1],
        ['lifecare', 'content', 'service_cta_suffix', 'text', 'to learn more!', 1],
        ['lifecare', 'content', 'service_cta_text', 'text', 'Let our experienced caregivers help your family today.', 1],
        ['lifecare', 'content', 'services_item_1', 'text', 'Personal grooming, bathing, toileting, and hygiene tasks', 1],
        ['lifecare', 'content', 'services_item_2', 'text', 'Mobility and transfers', 1],
        ['lifecare', 'content', 'services_item_3', 'text', 'Medication reminders and monitoring', 1],
        ['lifecare', 'content', 'services_item_4', 'text', 'Companionship', 1],
        ['lifecare', 'content', 'services_item_5', 'text', 'Light housekeeping, meal planning, and meal preparation', 1],
        ['lifecare', 'content', 'services_item_6', 'text', 'Shopping and errands', 1],
        ['personalcare', 'content', 'additional_item_1', 'text', 'Thorough house cleaning to keep your living space pleasant and hygienic.', 1],
        ['personalcare', 'content', 'additional_item_2', 'text', 'Laundry services, ensuring your clothes are always fresh and ready to wear.', 1],
        ['personalcare', 'content', 'additional_item_3', 'text', 'Bed making for a comfortable and inviting sleep environment.', 1],
        ['personalcare', 'content', 'additional_item_4', 'text', 'Grocery shopping, bringing the necessities of life right to your doorstep.', 1],
        ['personalcare', 'content', 'additional_item_5', 'text', 'General washing tasks, maintaining a tidy and sanitary home environment.', 1],
        ['personalcare', 'content', 'additional_item_6', 'text', 'Assistance with settling back into your home after a hospital stay, helping you re-adjust with ease and comfort.', 1],
        ['personalcare', 'content', 'service_cta_link_text', 'text', 'Contact Us Today', 1],
        ['personalcare', 'content', 'service_cta_suffix', 'text', 'to learn more!', 1],
        ['personalcare', 'content', 'service_cta_text', 'text', 'Allow our skilled caregivers to support your family today.', 1],
        ['personalcare', 'content', 'services_item_1', 'text', 'Assistance with personal grooming, bathing, and toileting', 1],
        ['personalcare', 'content', 'services_item_2', 'text', 'Help with mobility and transfers', 1],
        ['personalcare', 'content', 'services_item_3', 'text', 'Medication management and reminders', 1],
        ['personalcare', 'content', 'services_item_4', 'text', 'Companionship and emotional support', 1],
        ['personalcare', 'content', 'services_item_5', 'text', 'Light housekeeping and meal preparation', 1],
        ['personalcare', 'content', 'services_item_6', 'text', 'Running errands and shopping', 1],
        ['personalcare', 'content', 'services_item_7', 'text', 'Assistance with bedtime routines', 1],
        ['specialcare', 'content', 'service_cta_link_text', 'text', 'Contact our team', 1],
        ['specialcare', 'content', 'service_cta_suffix', 'text', 'to learn more!', 1],
        ['specialcare', 'content', 'service_cta_text', 'text', 'Let our experienced caregivers help your family today.', 1],
        ['specialcare', 'content', 'services_item_1', 'text', 'Personal grooming, bathing, toileting, and hygiene tasks', 1],
        ['specialcare', 'content', 'services_item_2', 'text', 'Mobility and transfers', 1],
        ['specialcare', 'content', 'services_item_3', 'text', 'Medication reminders and monitoring', 1],
        ['specialcare', 'content', 'services_item_4', 'text', 'Companionship', 1],
        ['specialcare', 'content', 'services_item_5', 'text', 'Light housekeeping, meal planning, and meal preparation', 1],
        ['specialcare', 'content', 'services_item_6', 'text', 'Shopping and errands', 1],
        ['started', 'content', 'continuity_text', 'textarea', 'We assign each client regular carers. This continuity provides stability, allowing you and your carer to get to know each other and create a stable, trusted and happier home environment.', 1],
        ['started', 'content', 'intro_text', 'textarea', 'We believe that rigorous care planning with you and your family is the first step to achieving excellent care. A senior member of our care management team will be responsible for designing a personal care plan. We will meet with you before your care commences and will encourage you to have your family member or supporter present during all of our care planning meetings. This meeting includes a detailed review of care needed, schedule request (if known), specific requests of family or client, and any other concerns. This is an excellent opportunity to ask questions of Visiting CareGiver and it is an excellent opportunity for us to get to know you as well. If you are feeling overwhelmed, we can make recommendations as we have much experience in understanding what works in certain situations.', 1],
        ['started', 'content', 'step_item_1', 'textarea', 'We will arrange a mutually convenient time to meet with you. During this meeting we will get to know you and your requirements and talk to you about the solutions we could provide.', 1],
        ['started', 'content', 'step_item_2', 'textarea', 'Should you decide that we are the right care agency for you we would arrange a second meeting in order to have a more detailed discussion about your requirements. During this meeting we will complete a care planning document which will enable us to formulate a comprehensive care plan.', 1],
        ['started', 'content', 'step_item_3', 'textarea', 'Information provided by you at our meetings will be used by the care manager to create your personalised care plan. We will arrange a further meeting to discuss the care plan we have designed and confirm that we have fully understood the care you require. Any changes you require to the care plan will be made at this stage.', 1],
        ['started', 'content', 'step_item_4', 'textarea', 'Only when you are completely satisfied with the care plan will we arrange a time for your care to commence and we will introduce you to your care team.', 1],
        ['started', 'content', 'step_item_5', 'textarea', 'We understand that your care needs may change therefore we will conduct a review of the services we provide. This will be done regularly to suit your changing needs and ensure that you are satisfied with the level and quality of our support.', 1],
        ['surgery', 'content', 'service_cta_link_text', 'text', 'Contact our team', 1],
        ['surgery', 'content', 'service_cta_suffix', 'text', 'to learn more!', 1],
        ['surgery', 'content', 'service_cta_text', 'text', 'Let our experienced caregivers help your family today.', 1],
        ['surgery', 'content', 'services_item_1', 'text', 'Personal grooming, bathing, toileting, and hygiene tasks', 1],
        ['surgery', 'content', 'services_item_2', 'text', 'Mobility and transfers', 1],
        ['surgery', 'content', 'services_item_3', 'text', 'Medication reminders and monitoring', 1],
        ['surgery', 'content', 'services_item_4', 'text', 'Companionship', 1],
        ['surgery', 'content', 'services_item_5', 'text', 'Light housekeeping, meal planning, and meal preparation', 1],
        ['surgery', 'content', 'services_item_6', 'text', 'Shopping and errands', 1],
        ['testimonial', 'content', 'item_1_name', 'text', 'Dave A', 1],
        ['testimonial', 'content', 'item_1_quote', 'textarea', 'I am just sending this to say my wife F. Adams has been moved into a care home as her health has deteriorated. I would just like to say how grateful I have been to the help your carers have been to Frances. I cannot fault them. If you could say a special thanks to Patricia, Franca and Thoko for the wonderful job they did with Frances. Many thanks for all your help.', 1],
        ['testimonial', 'content', 'item_2_name', 'text', 'Linda W', 1],
        ['testimonial', 'content', 'item_2_quote', 'textarea', 'My husband was in your care for 10 days. I found all the carers who came out to the house were very professional, competent and friendly. The two carers that helped when my husband passed away were caring, efficient and knew exactly what I needed to do. They both went the extra mile quietly and unobtrusively and I really do not know how I would have managed without them. Thank you to all the carers that I was fortunate enough to meet.', 1],
        ['testimonial', 'content', 'item_3_name', 'text', 'Nigel J', 1],
        ['testimonial', 'content', 'item_3_quote', 'textarea', 'Facilitate took over the care of my uncle recently after a number of failings by the previous carers. The difference in care was amazing. The carers provided wonderful end of life care in terms of both personal and social care, spending time to listen to my uncle\'s stories and hold his hand. Highly recommended.', 1],
        ['testimonial', 'content', 'item_4_name', 'text', 'Therese B', 1],
        ['testimonial', 'content', 'item_4_quote', 'textarea', 'Facilitate Care looked after and cared for my husband Don in the last weeks of his life. They were amazing, very gentle, and spoke to Don as they were caring for him. I was very happy with the care they gave him. Ekta and Eric were very special people and I thank them with all my heart.', 1],
        ['testimonial', 'content', 'item_5_name', 'text', 'Carol T', 1],
        ['testimonial', 'content', 'item_5_quote', 'textarea', 'Can\'t praise the care enough that Jane and Adam gave my brother in his last couple of weeks of his life. Such caring, thoughtful people, always treated my brother with such respect and kindness and such beautiful kind words to all the family on every visit they made. These pair are truly what carers should be and the whole family thanks them.', 1],
        ['testimonial', 'content', 'item_6_name', 'text', 'Debbie S', 1],
        ['testimonial', 'content', 'item_6_quote', 'textarea', 'After a nasty fall and a week stay in hospital, I was nervous about having a care package at home, especially as a nurse and only just being 60. I had no need to worry; the two ladies who come are wonderful, cope with my wobbles, and I feel so cared for.', 1],
    ];

    // Backfill only missing defaults; keep existing user-managed values unchanged.
    $stmt = $conn->prepare('INSERT IGNORE INTO website_content (page_key, section_key, field_key, field_type, content_value, is_active, updated_by)
                            VALUES (?, ?, ?, ?, ?, ?, "")');
    if ($stmt === false) {
        return;
    }

    foreach ($seedRows as $row) {
        $pageKey = $row[0];
        $sectionKey = $row[1];
        $fieldKey = $row[2];
        $fieldType = $row[3];
        $contentValue = $row[4];
        $isActive = (int) $row[5];
        $stmt->bind_param('sssssi', $pageKey, $sectionKey, $fieldKey, $fieldType, $contentValue, $isActive);
        $stmt->execute();
    }

    $stmt->close();
}

function hasWebsiteContentSection(mysqli $conn, string $pageKey, string $sectionKey): bool
{
    $sql = 'SELECT 1
            FROM website_content
            WHERE page_key = ? AND section_key = ?
            LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return false;
    }

    $stmt->bind_param('ss', $pageKey, $sectionKey);
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $result = $stmt->get_result();
    $exists = $result instanceof mysqli_result && (bool) $result->fetch_row();
    $stmt->close();

    return $exists;
}

function fetchWebsiteContentField(mysqli $conn, string $pageKey, string $sectionKey, string $fieldKey): ?array
{
    $sql = 'SELECT field_type, content_value, is_active, updated_by
            FROM website_content
            WHERE page_key = ? AND section_key = ? AND field_key = ?
            LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return null;
    }

    $stmt->bind_param('sss', $pageKey, $sectionKey, $fieldKey);
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $result = $stmt->get_result();
    $row = $result instanceof mysqli_result ? $result->fetch_assoc() : null;
    $stmt->close();

    return is_array($row) ? $row : null;
}

function upsertWebsiteContentSeedValue(
    mysqli $conn,
    string $pageKey,
    string $sectionKey,
    string $fieldKey,
    string $fieldType,
    string $contentValue,
    int $isActive = 1,
    string $updatedBy = ''
): void {
    $sql = 'INSERT INTO website_content (page_key, section_key, field_key, field_type, content_value, is_active, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                field_type = VALUES(field_type),
                content_value = VALUES(content_value),
                is_active = VALUES(is_active),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return;
    }

    $stmt->bind_param('sssssis', $pageKey, $sectionKey, $fieldKey, $fieldType, $contentValue, $isActive, $updatedBy);
    $stmt->execute();
    $stmt->close();
}

function migrateLegacyFooterContentToGlobal(mysqli $conn): void
{
    $fieldMappings = [
        ['fieldKey' => 'tagline', 'fieldType' => 'text', 'defaultValue' => 'Suporting Your Independence.', 'sources' => [
            ['home', 'footer', 'tagline'],
            ['blog', 'footer', 'tagline'],
            ['blogdetail', 'footer', 'tagline'],
        ]],
        ['fieldKey' => 'logo_url', 'fieldType' => 'image', 'defaultValue' => '/frontend/images/footer-logo.png', 'sources' => [
            ['home', 'footer', 'logo_url'],
        ]],
        ['fieldKey' => 'quick_links_heading', 'fieldType' => 'text', 'defaultValue' => 'Quick links', 'sources' => [
            ['home', 'footer', 'quick_links_heading'],
        ]],
        ['fieldKey' => 'contact_heading', 'fieldType' => 'text', 'defaultValue' => 'Contact Info', 'sources' => [
            ['blog', 'footer', 'contact_heading'],
            ['blogdetail', 'footer', 'contact_heading'],
        ]],
        ['fieldKey' => 'contact_phone', 'fieldType' => 'text', 'defaultValue' => '024 7623 1188', 'sources' => [
            ['blog', 'footer', 'contact_phone'],
            ['blogdetail', 'footer', 'contact_phone'],
        ]],
        ['fieldKey' => 'contact_address', 'fieldType' => 'textarea', 'defaultValue' => 'Office GE13, 101 Lockhurst Lane,<br> Coventry, CV6 5sf', 'sources' => [
            ['blog', 'footer', 'contact_address'],
            ['blogdetail', 'footer', 'contact_address'],
        ]],
        ['fieldKey' => 'contact_email_label', 'fieldType' => 'text', 'defaultValue' => 'Email', 'sources' => [
            ['blog', 'footer', 'contact_email_label'],
            ['blogdetail', 'footer', 'contact_email_label'],
        ]],
        ['fieldKey' => 'contact_email', 'fieldType' => 'text', 'defaultValue' => 'info@facilitatecareservices.co.uk', 'sources' => [
            ['blog', 'footer', 'contact_email'],
            ['blogdetail', 'footer', 'contact_email'],
        ]],
        ['fieldKey' => 'whatsapp_number', 'fieldType' => 'text', 'defaultValue' => '', 'sources' => []],
        ['fieldKey' => 'whatsapp_label', 'fieldType' => 'text', 'defaultValue' => 'Chat on WhatsApp', 'sources' => []],
        ['fieldKey' => 'whatsapp_message', 'fieldType' => 'textarea', 'defaultValue' => 'Hello, I would like to enquire about your care services.', 'sources' => []],
        ['fieldKey' => 'cqc_heading', 'fieldType' => 'text', 'defaultValue' => 'Care Quality Commission', 'sources' => [
            ['blog', 'footer', 'cqc_heading'],
            ['blogdetail', 'footer', 'cqc_heading'],
        ]],
        ['fieldKey' => 'cqc_badge_image_url', 'fieldType' => 'image', 'defaultValue' => '/frontend/images/CQC rating.jpg', 'sources' => [
            ['blog', 'footer', 'cqc_badge_image_url'],
            ['blogdetail', 'footer', 'cqc_badge_image_url'],
        ]],
        ['fieldKey' => 'cqc_text', 'fieldType' => 'textarea', 'defaultValue' => 'Latest Inspection 25 May 2021', 'sources' => [
            ['blog', 'footer', 'cqc_text'],
            ['blogdetail', 'footer', 'cqc_text'],
        ]],
        ['fieldKey' => 'cqc_secondary_text', 'fieldType' => 'text', 'defaultValue' => 'Latest Review 6 July 2023', 'sources' => []],
        ['fieldKey' => 'cqc_button_text', 'fieldType' => 'text', 'defaultValue' => 'See Report', 'sources' => [
            ['blog', 'footer', 'cqc_button_text'],
            ['blogdetail', 'footer', 'cqc_button_text'],
        ]],
        ['fieldKey' => 'cqc_url', 'fieldType' => 'url', 'defaultValue' => 'https://www.cqc.org.uk/location/1-2131286214', 'sources' => []],
        ['fieldKey' => 'copyright_text', 'fieldType' => 'text', 'defaultValue' => '(c) Copyright Facilitate care services 2024. All right reserved.', 'sources' => [
            ['home', 'footer', 'copyright_text'],
        ]],
    ];

    foreach ($fieldMappings as $mapping) {
        $fieldType = $mapping['fieldType'];
        $contentValue = $mapping['defaultValue'];
        $isActive = 1;
        $updatedBy = '';

        foreach ($mapping['sources'] as $source) {
            $sourceRow = fetchWebsiteContentField($conn, $source[0], $source[1], $source[2]);
            if (!is_array($sourceRow)) {
                continue;
            }

            $fieldType = stringValue($sourceRow['field_type'] ?? $fieldType) ?: $fieldType;
            $contentValue = (string) ($sourceRow['content_value'] ?? $contentValue);
            $isActive = (int) ($sourceRow['is_active'] ?? $isActive);
            $updatedBy = stringValue((string) ($sourceRow['updated_by'] ?? $updatedBy));
            break;
        }

        upsertWebsiteContentSeedValue(
            $conn,
            'global',
            'footer',
            $mapping['fieldKey'],
            $fieldType,
            $contentValue,
            $isActive,
            $updatedBy
        );
    }
}

function mapWebsiteContentRow(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'pageKey' => $row['page_key'],
        'sectionKey' => $row['section_key'],
        'fieldKey' => $row['field_key'],
        'fieldType' => $row['field_type'],
        'contentValue' => $row['content_value'] ?? '',
        'isActive' => (int) $row['is_active'] === 1,
        'updatedBy' => $row['updated_by'] ?? '',
        'createdAt' => $row['created_at'],
        'updatedAt' => $row['updated_at'],
    ];
}

function fetchWebsiteContent(mysqli $conn, ?string $pageKey = null, bool $onlyActive = false): array
{
    $sql = 'SELECT id, page_key, section_key, field_key, field_type, content_value, is_active, updated_by, created_at, updated_at
            FROM website_content';
    $conditions = [];
    $params = [];
    $types = '';

    if ($pageKey !== null && $pageKey !== '') {
        $conditions[] = 'page_key = ?';
        $params[] = $pageKey;
        $types .= 's';
    }
    if ($onlyActive) {
        $conditions[] = 'is_active = 1';
    }

    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY page_key ASC, section_key ASC, field_key ASC, id ASC';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare website content query.');
    }

    if (!empty($params)) {
        $bindParams = [];
        $bindParams[] = $types;
        foreach ($params as $index => $param) {
            $bindParams[] = &$params[$index];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }

    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Failed to fetch website content.');
    }

    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = mapWebsiteContentRow($row);
    }

    $stmt->close();
    return $rows;
}

function buildPublicContentTree(array $entries): array
{
    $tree = [];
    foreach ($entries as $entry) {
        $pageKey = stringValue($entry['pageKey'] ?? '');
        $sectionKey = stringValue($entry['sectionKey'] ?? '');
        $fieldKey = stringValue($entry['fieldKey'] ?? '');
        if ($pageKey === '' || $sectionKey === '' || $fieldKey === '') {
            continue;
        }

        if (!isset($tree[$pageKey])) {
            $tree[$pageKey] = [];
        }
        if (!isset($tree[$pageKey][$sectionKey])) {
            $tree[$pageKey][$sectionKey] = [];
        }

        $tree[$pageKey][$sectionKey][$fieldKey] = [
            'value' => $entry['contentValue'] ?? '',
            'type' => $entry['fieldType'] ?? DEFAULT_FIELD_TYPE,
        ];
    }

    return $tree;
}

function validateContentInput(array $payload): array
{
    $pageKey = normalizeKey((string) ($payload['pageKey'] ?? ''));
    $sectionKey = normalizeKey((string) ($payload['sectionKey'] ?? ''));
    $fieldKey = normalizeKey((string) ($payload['fieldKey'] ?? ''));
    $fieldType = normalizeFieldType($payload['fieldType'] ?? DEFAULT_FIELD_TYPE);
    $contentValue = (string) ($payload['contentValue'] ?? '');
    $isActive = boolValue($payload['isActive'] ?? true, true);
    $updatedBy = stringValue((string) ($payload['updatedBy'] ?? ''));

    if ($pageKey === '' || $sectionKey === '' || $fieldKey === '') {
        throw new InvalidArgumentException('Page, section, and field keys are required.');
    }

    if (strlen($pageKey) > 80 || strlen($sectionKey) > 80 || strlen($fieldKey) > 80) {
        throw new InvalidArgumentException('Page, section, and field keys must be 80 characters or less.');
    }

    return [
        'pageKey' => $pageKey,
        'sectionKey' => $sectionKey,
        'fieldKey' => $fieldKey,
        'fieldType' => $fieldType,
        'contentValue' => $contentValue,
        'isActive' => $isActive ? 1 : 0,
        'updatedBy' => $updatedBy,
    ];
}

function upsertContentEntry(mysqli $conn, array $payload): void
{
    $validated = validateContentInput($payload);
    $sql = 'INSERT INTO website_content (page_key, section_key, field_key, field_type, content_value, is_active, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                field_type = VALUES(field_type),
                content_value = VALUES(content_value),
                is_active = VALUES(is_active),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare content save query.');
    }

    $stmt->bind_param(
        'sssssis',
        $validated['pageKey'],
        $validated['sectionKey'],
        $validated['fieldKey'],
        $validated['fieldType'],
        $validated['contentValue'],
        $validated['isActive'],
        $validated['updatedBy']
    );

    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Failed to save content entry.');
    }

    $stmt->close();
}

function updateContentEntryById(mysqli $conn, int $id, array $payload): void
{
    $validated = validateContentInput($payload);
    $sql = 'UPDATE website_content
            SET page_key = ?, section_key = ?, field_key = ?, field_type = ?, content_value = ?, is_active = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
            LIMIT 1';
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare content update query.');
    }

    $stmt->bind_param(
        'sssssisi',
        $validated['pageKey'],
        $validated['sectionKey'],
        $validated['fieldKey'],
        $validated['fieldType'],
        $validated['contentValue'],
        $validated['isActive'],
        $validated['updatedBy'],
        $id
    );

    if (!$stmt->execute()) {
        $errno = (int) $stmt->errno;
        $stmt->close();
        if ($errno === 1062) {
            throw new RuntimeException('Duplicate key conflict for this page/section/field.');
        }
        throw new RuntimeException('Failed to update content entry.');
    }

    $stmt->close();
}

function safePathSegment(string $value, string $fallback): string
{
    $normalized = normalizeKey($value);
    return $normalized !== '' ? $normalized : $fallback;
}

function resolveCmsImageRootDirectory(): string
{
    $candidates = [
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'images',
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'images',
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'frontend' . DIRECTORY_SEPARATOR . 'images',
    ];

    foreach ($candidates as $candidate) {
        if (is_dir($candidate)) {
            return rtrim($candidate, '\\/');
        }
    }

    $fallback = $candidates[0];
    if (!is_dir($fallback) && !mkdir($fallback, 0775, true) && !is_dir($fallback)) {
        throw new RuntimeException('Unable to initialize image storage directory.');
    }

    return rtrim($fallback, '\\/');
}

function resolveExistingImageDirectory(string $existingPath): ?string
{
    $normalized = str_replace('\\', '/', stringValue($existingPath));
    if ($normalized === '' || strpos($normalized, '/frontend/images/') !== 0) {
        return null;
    }

    $relativePath = trim(substr($normalized, strlen('/frontend/images/')), '/');
    if ($relativePath === '') {
        return null;
    }

    $segments = explode('/', $relativePath);
    array_pop($segments); // remove filename
    if (empty($segments)) {
        return '';
    }

    foreach ($segments as $segment) {
        if ($segment === '' || $segment === '.' || $segment === '..') {
            return null;
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $segment)) {
            return null;
        }
    }

    return implode('/', $segments);
}

function storeCmsImageUpload(array $file, string $pageKey, string $sectionKey, string $fieldKey, string $existingPath = ''): string
{
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Image upload failed. Please choose a valid file.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new InvalidArgumentException('Uploaded image could not be read.');
    }

    $size = (int) ($file['size'] ?? 0);
    $maxBytes = 10 * 1024 * 1024;
    if ($size <= 0 || $size > $maxBytes) {
        throw new InvalidArgumentException('Image size must be between 1 byte and 10 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = strtolower((string) $finfo->file($tmpName));
    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
    ];
    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new InvalidArgumentException('Unsupported image format. Use JPG, PNG, WEBP, GIF, or SVG.');
    }

    $extension = $allowedMimeTypes[$mimeType];
    $pageSegment = safePathSegment($pageKey, 'page');
    $sectionSegment = safePathSegment($sectionKey, 'section');
    $fieldSegment = safePathSegment($fieldKey, 'image');

    $imagesRoot = resolveCmsImageRootDirectory();
    $resolvedDirectory = resolveExistingImageDirectory($existingPath);
    $relativeDirectory = $resolvedDirectory !== null ? $resolvedDirectory : ('cms/' . $pageSegment . '/' . $sectionSegment);
    $targetDirectory = $imagesRoot;
    if ($relativeDirectory !== '') {
        $targetDirectory .= DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);
    }

    if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
        throw new RuntimeException('Unable to create target image directory.');
    }

    $uniqueSuffix = substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
    $filename = $fieldSegment . '-' . date('YmdHis') . '-' . $uniqueSuffix . '.' . $extension;
    $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Unable to save uploaded image.');
    }

    $prefix = '/frontend/images';
    if ($relativeDirectory !== '') {
        $prefix .= '/' . $relativeDirectory;
    }

    return $prefix . '/' . $filename;
}

$body = getJsonBody();
$action = $_GET['action'] ?? '';
$source = normalizeDbSource((string) ($_GET['source'] ?? ($body['source'] ?? 'auto')));

try {
    $conn = createDatabaseConnection($source);
    ensureWebsiteContentTable($conn);
    $hasGlobalFooterEntries = hasWebsiteContentSection($conn, 'global', 'footer');
    seedDefaultWebsiteContent($conn);
    if (!$hasGlobalFooterEntries) {
        migrateLegacyFooterContentToGlobal($conn);
    }
} catch (Throwable $exception) {
    jsonResponse(['success' => false, 'message' => 'Failed to initialize website content service.'], 500);
}

if ($action === 'getBootstrap' && ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST')) {
    try {
        $entries = fetchWebsiteContent($conn, null, false);
        $conn->close();
        jsonResponse([
            'success' => true,
            'entries' => $entries,
            'config' => [
                'fieldTypes' => ['text', 'textarea', 'image', 'url', 'richtext'],
                'pages' => [
                    'global',
                    'home',
                    'about',
                    'contact',
                    'care',
                    'caregiver',
                    'chronical',
                    'discharge',
                    'lifecare',
                    'livein',
                    'personalcare',
                    'respitecare',
                    'specialcare',
                    'started',
                    'support',
                    'surgery',
                    'elderlyservice',
                    'gallery',
                    'team',
                    'faq',
                    'blog',
                    'blogdetail',
                    'testimonial',
                ],
            ],
        ]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to load website content.'], 500);
    }
}

if ($action === 'getPublic' && ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST')) {
    $pageKey = normalizeKey((string) ($_GET['page'] ?? ($body['page'] ?? '')));
    try {
        $entries = fetchWebsiteContent($conn, $pageKey !== '' ? $pageKey : null, true);
        $tree = buildPublicContentTree($entries);
        $conn->close();
        jsonResponse([
            'success' => true,
            'entries' => $entries,
            'content' => $tree,
        ]);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to load public content.'], 500);
    }
}

if ($action === 'uploadImage' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pageKey = normalizeKey((string) ($_POST['pageKey'] ?? ''));
    $sectionKey = normalizeKey((string) ($_POST['sectionKey'] ?? ''));
    $fieldKey = normalizeKey((string) ($_POST['fieldKey'] ?? ''));
    $existingPath = stringValue((string) ($_POST['existingPath'] ?? ''));

    if ($pageKey === '' || $sectionKey === '' || $fieldKey === '') {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Page, section, and field keys are required for image upload.'], 422);
    }

    $imageFile = $_FILES['image'] ?? null;
    if (!is_array($imageFile)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Image file is required.'], 422);
    }

    try {
        $path = storeCmsImageUpload($imageFile, $pageKey, $sectionKey, $fieldKey, $existingPath);
        $conn->close();
        jsonResponse([
            'success' => true,
            'path' => $path,
        ]);
    } catch (InvalidArgumentException $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => $exception->getMessage()], 422);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to upload image.'], 500);
    }
}

if ($action === 'saveEntry' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intValue($body['id'] ?? 0, 0);
    try {
        if ($id > 0) {
            updateContentEntryById($conn, $id, $body);
        } else {
            upsertContentEntry($conn, $body);
        }

        $entries = fetchWebsiteContent($conn, null, false);
        $conn->close();
        jsonResponse([
            'success' => true,
            'entries' => $entries,
        ]);
    } catch (InvalidArgumentException $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => $exception->getMessage()], 422);
    } catch (Throwable $exception) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => $exception->getMessage()], 500);
    }
}

if ($action === 'saveBulk' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $entries = $body['entries'] ?? null;
    if (!is_array($entries) || empty($entries)) {
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Entries payload is required.'], 422);
    }

    $conn->begin_transaction();
    try {
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $id = intValue($entry['id'] ?? 0, 0);
            if ($id > 0) {
                updateContentEntryById($conn, $id, $entry);
            } else {
                upsertContentEntry($conn, $entry);
            }
        }
        $conn->commit();

        $fresh = fetchWebsiteContent($conn, null, false);
        $conn->close();
        jsonResponse([
            'success' => true,
            'entries' => $fresh,
        ]);
    } catch (InvalidArgumentException $exception) {
        $conn->rollback();
        $conn->close();
        jsonResponse(['success' => false, 'message' => $exception->getMessage()], 422);
    } catch (Throwable $exception) {
        $conn->rollback();
        $conn->close();
        jsonResponse(['success' => false, 'message' => 'Failed to save content changes.'], 500);
    }
}

$conn->close();
jsonResponse(['success' => false, 'message' => 'Invalid action.'], 400);
?>
