<?php
  // don't load directly
defined( 'ABSPATH' ) || exit;

if ( file_exists( UACF7_PATH . 'admin/tf-options/options/tf-menu-icon.php' ) ) {

    $uacf7_menu_icon = UACF7_URL . 'assets/admin/images/icon.png';
} else {
    $uacf7_menu_icon = 'dashicons-palmtree';
}

UACF7_Settings::option(
    'uacf7_settings',
    array(
        'title'    => __( 'CF7 Addons', 'ultimate-addons-for-contact-form-7' ),
        'icon'     => $uacf7_menu_icon,
        'position' => 30.01,
        'sections' =>
            apply_filters(
                'uacf7_settings_options',
                array(
                    'addons_settings' => array(
                        'title'  => __( 'Addons Settings', 'ultimate-addons-for-contact-form-7' ),
                        'icon'   => 'fa fa-cog',
                        'fields' => array(
                        ),
                    ),
                    'general_addons' => array(
                        'title'  => __( 'General Addons', 'ultimate-addons-for-contact-form-7' ),
                        'parent' => 'addons_settings',
                        'icon'   => 'fa fa-cog',
                        'fields' => apply_filters(
                            'uacf7_general_addons_fields',
                            array(
                                'uacf7_enable_redirection' => array(
                                    'id' => 'uacf7_enable_redirection',
                                    'type'               => 'switch',
                                    'label'              => __( 'Redirection ', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Redirection.png',
                                    'subtitle'           => __( 'Redirect users to a Thank You or External page upon form submission.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/redirection-for-contact-form-7/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/redirection-for-contact-form-7/',
                                    'default'            => false,
                                ),
                                'uacf7_enable_conditional_field' => array(
                                    'id' => 'uacf7_enable_conditional_field',
                                    'type'               => 'switch',
                                    'label'              => __( 'Conditional Field', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Conditional-Field2x.png',
                                    'subtitle'           => __( 'Show or hide Contact Form 7 fields based on Conditional Logic.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-conditional-fields/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-conditional-fields/',
                                    'default'            => false,

                                ),
                                'uacf7_enable_field_column' => array(
                                    'id' => 'uacf7_enable_field_column',
                                    'type'               => 'switch',
                                    'label'              => __( 'Column or Grid', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Column-or-Grid-Layout2x.png',
                                    'subtitle'           => __( 'Easily create two columns, three Columns; even Four columns form.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-columns-or-grid/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-columns/',
                                    'label_on'           => __( 'Yes', 'ultimate-addons-for-contact-form-7' ),
                                    'label_off'          => __( 'No', 'ultimate-addons-for-contact-form-7' ),
                                    'default'            => false,

                                ),
                                'uacf7_enable_placeholder' => array(
                                    'id'                 => 'uacf7_enable_placeholder',
                                    'type'               => 'switch',
                                    'label'              => __( 'Placeholder Styling', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Placeholder-Styling.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Style form placeholders, like text color and background color, without writing any CSS. ', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-placeholder-styling/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-placeholder-styling/',
                                ),
                                'uacf7_enable_uacf7style' => array(
                                    'id'                 => 'uacf7_enable_uacf7style',
                                    'type'               => 'switch',
                                    'label'              => __( 'Form Styler (Single)', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Form-Styler.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Style your entire form without any CSS coding, including colors, margins, button styles, and font sizes.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-style-addon/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-style/',
                                ),
                                'uacf7_enable_multistep' => array(
                                    'id'                 => 'uacf7_enable_multistep',
                                    'type'               => 'switch',
                                    'label'              => __( 'Multi-step Form', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Multi-Step-Form.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Create stunning multi-step forms with Contact Form 7. Ideal for long forms and surveys.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-multi-step-forms/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-multi-step-forms/',
                                ),
                                'uacf7_enable_hydra_booking_form' => array(
                                    'id'                 => 'uacf7_enable_hydra_booking_form',
                                    'type'               => 'switch',
                                    'label'              => __( 'Booking/Appointment with Hydra', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Booking-or-Appointment-Form2x.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Hydra Booking is a separate standalone plugin for advanced booking and appointments. It works with Contact Form 7 and supports calendar, time slots, and payments.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://demo.hydrabooking.com/',
                                    'documentation_link' => 'https://themefic.com/docs/hydrabooking/',
                                ),
                                'uacf7_enable_mailchimp' => array(
                                    'id'                 => 'uacf7_enable_mailchimp',
                                    'type'               => 'switch',
                                    'label'              => __( 'Mailchimp Integration', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Connect-with-Mailchimp2x.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Integrate Contact Form 7 with Mailchimp. Add submissions to Mailchimp lists automatically.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/mailchimp-for-contact-form-7/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-mailchimp/',
                                ),
                                'uacf7_enable_database_field' => array(
                                    'id' => 'uacf7_enable_database_field',
                                    'type'               => 'switch',
                                    'label'              => __( 'Database ', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Save-to-Database.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Store form data, view data in the admin backend, and export data in CSV format. ', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-database/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-database/',
                                ),


                                'uacf7_enable_pdf_generator_field' => array(
                                    'id'                 => 'uacf7_enable_pdf_generator_field',
                                    'type'               => 'switch',
                                    'label'              => __( 'PDF Generate', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Send-PDF-Using-Contact-form-8.png',
                                    'default'            => false,
                                    'subtitle'           => __( "Generate PDFs upon form submission; PDFs are sent to the admin and submitter email. ", 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-pdf-generator/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-pdf-generator/',
                                ),
                                'uacf7_enable_form_generator_ai_field' => array(
                                    'id'                 => 'uacf7_enable_form_generator_ai_field',
                                    'type'               => 'switch',
                                    'label'              => __( 'AI Form Generator', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Generate-Al-Forms.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'The Form Generator Addon helps generating categorized contact forms with the power of AI.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/ai-form-generator/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/ai-form-generator/',
                                ),
                                'uacf7_enable_submission_id_field' => array(
                                    'id'                 => 'uacf7_enable_submission_id_field',
                                    'type'               => 'switch',
                                    'label'              => __( 'Submission ID', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Unique-Submission-ID.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Add a unique ID to every form submission. The ID can be added on the "Subject Line" of your form.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/unique-id-for-contact-form-7/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/unique-id-for-contact-form-7/',
                                ),
                                'uacf7_enable_telegram_field' => array(
                                    'id'                 => 'uacf7_enable_telegram_field',
                                    'type'               => 'switch',
                                    'label'              => __( 'Telegram Integration', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Telegram-Integration-1.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Forward form submission data to Telegram.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-telegram/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-telegram/',
                                ),
                                'uacf7_enable_signature_field' => array(
                                    'id'                 => 'uacf7_enable_signature_field',
                                    'type'               => 'switch',
                                    'label'              => __( 'Digital Signature', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/digital-signature.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Add a digital signature feature to your forms.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-signature-addon/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-signature-addon/',
                                ),
                                'uacf7_enable_opt_web_hook' => array(
                                    'id'                 => 'uacf7_enable_opt_web_hook',
                                    'type'               => 'switch',
                                    'label'              => __( 'Pabbly/Zapier (Webhook)', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Zapier-Webhook.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Transfer form data to third-party services like Pabbly or Zapier via webhooks. ', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/pabbly-zapier-webhook/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-webhook/',
                                ),
                            )
                        ),
                    ),
                    'extra_fields_addons' => array(
                        'title'  => __( 'Extra Fields Addons', 'ultimate-addons-for-contact-form-7' ),
                        'parent' => 'addons_settings',
                        'icon'   => 'fa fa-cog',
                        'fields' => apply_filters(
                            'uacf7_extra_fields_addons_fields',
                            array(
                                'uacf7_enable_dynamic_text' => array(
                                    'id'                 => 'uacf7_enable_dynamic_text',
                                    'type'               => 'switch',
                                    'label'              => __( 'Dynamic Text ', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Dynamic-Text-Editor.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Retrieve dynamic data from a website to be used in hidden fields, including URL, blog, post, user info, and custom fields. ', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-dynamic-text-extension/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-dynamic-text-extension/',
                                ),
                                'uacf7_enable_pre_populate_field' => array(
                                    'id'                 => 'uacf7_enable_pre_populate_field',
                                    'type'               => 'switch',
                                    'label'              => __( 'Pre-populate Field', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Woocomerce-Product-Dropdown.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Send data from one form to another, after the first form submission.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-pre-populate-fields/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-pre-populate-fields/',
                                ),
                                'uacf7_enable_star_rating' => array(
                                    'id' => 'uacf7_enable_star_rating',
                                    'type'               => 'switch',
                                    'label'              => __( 'Star Rating', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Star-Rating-Field.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Get customer feedback by adding a star rating field to your Contact Form 7. ', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-star-rating/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-star-rating-field/',
                                ),
                                'uacf7_enable_range_slider' => array(
                                    'id' => 'uacf7_enable_range_slider',
                                    'type'               => 'switch',
                                    'label'              => __( 'Range Slider', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Range-Slider.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Add beautiful Range slider fields to Contact Form 7.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-range-slider/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-range-slider/',
                                ),
                                'uacf7_enable_country_dropdown_field' => array(
                                    'id' => 'uacf7_enable_country_dropdown_field',
                                      // 'child_field' => 'uacf7_enable_ip_geo_fields',
                                    'type'               => 'switch',
                                    'label'              => __( 'Country Dropdown Field', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/All-Country-List-with-Flag2x.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Add a country dropdown list with flags to your form, automatically populating with country names. ', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-country-dropdown/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-country-dropdown-with-flag/',
                                ),

                                'uacf7_enable_spam_protection_field' => array(
                                    'id' => 'uacf7_enable_spam_protection_field',
                                    'type'               => 'switch',
                                    'label'              => __( 'Spam Protection', 'ultimate-addons-for-contact-form-7' ),
                                    'label_on'           => __( 'Yes', 'ultimate-addons-for-contact-form-7' ),
                                    'label_off'          => __( 'No', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/spam_protection.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'This feature is highly effective in preventing spam submissions on websites, ensuring the integrity and reliability of the submitted data.', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/spam-protection/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/spam-protection/',
                                    'field_width'        => 33,
                                ),

                            )
                        ),
                    ),
                    'wooCommerce_integration' => array(
                        'title'  => __( 'WooCommerce Integration', 'ultimate-addons-for-contact-form-7' ),
                        'parent' => 'addons_settings',
                        'icon'   => 'fa fa-cog',
                        'fields' => apply_filters(
                            'uacf7_woocommerce_addons_fields',
                            array(
                                'uacf7_enable_product_dropdown' => array(
                                    'id' => 'uacf7_enable_product_dropdown',
                                    'type'               => 'switch',
                                    'label'              => __( 'WooCommerce Product Dropdown', 'ultimate-addons-for-contact-form-7' ),
                                    'image_url'          => UACF7_URL . 'assets/admin/images/addons/Woocomerce-Product-Dropdown.png',
                                    'default'            => false,
                                    'subtitle'           => __( 'Easily show WooCommerce products on forms with a dropdown, allowing customers to select and inquire about products. ', 'ultimate-addons-for-contact-form-7' ),
                                    'demo_link'          => 'https://cf7addons.com/preview/contact-form-7-woocommerce/',
                                    'documentation_link' => 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-woocommerce/',
                                ),
                            )
                        ),
                    ),

                    'api_integration' => array(
                        'title'  => __( 'API Integration', 'ultimate-addons-for-contact-form-7' ),
                        'icon'   => 'fa fa-circle-nodes',
                        'fields' => array(
                        ),
                    ),
                    'mailchimp' => array(
                        'title'  => __( 'Mailchimp API', 'ultimate-addons-for-contact-form-7' ),
                        'icon'   => 'fa fa-mailchimp',
                        'parent' => 'api_integration',
                        'fields' => array(
                            'uacf7_mailchimp_api_key' => array(
                                'id'       => 'uacf7_mailchimp_api_key',
                                'type'     => 'password',
                                'label'    => __( 'Mailchimp API', 'ultimate-addons-for-contact-form-7' ),
                                'subtitle' => sprintf(
                                    /* translators: %1$s: Link to article. */
                                    __( 'Please enter your Mailchimp API key. If you are not sure how to get the API Key, follow this %1s.', 'ultimate-addons-for-contact-form-7' ),
                                    '<a href="https://mailchimp.com/help/about-api-keys/" target="_blank" rel="noopener">'.esc_html__( 'article', 'ultimate-addons-for-contact-form-7' ).'</a>'
                                )
                            ),
                            'uacf7_mailchimp_api_status' => array(
                                'id'     => 'uacf7_mailchimp_api_status',
                                'type'   => 'notice',
                                'notice' => 'info',
                                'title'  => __( 'To begin, you must enable the Mailchimp add-on.', 'ultimate-addons-for-contact-form-7' ),
                            ),
                        ),
                    ),
                    /**
                     * Miscellaneous
                     * Main menu.
                     */
                    'uacf7_import_export_data' => array(
                        'title'  => __( 'Miscellaneous', 'ultimate-addons-for-contact-form-7' ),
                        'icon'   => 'fa-solid fa-shuffle',
                        'fields' => array(
                        ),
                    ),
                    /**
                     * Import/Export
                     * Parent menu: Miscellaneous.
                     */
                    'uacf7_import_export' => array(
                        'title'  => __( 'Import/Export', 'ultimate-addons-for-contact-form-7' ),
                        'parent' => 'uacf7_import_export_data',
                        'icon'   => 'fa fa-download',
                        'fields' => array(
                            'uacf7_import_export_backup' => array(
                                'id'       => 'uacf7_import_export_backup',
                                'type'     => 'backup',
                                'label'    => __( 'Import/Export', 'ultimate-addons-for-contact-form-7' ),
                                'subtitle' => sprintf(
                                    __( 'Import and export all options associated with this settings panel. Please save it first in order to generate the export file. ', 'ultimate-addons-for-contact-form-7' )
                                )
                            ),
                        ),
                    ),
                ),
            )
    )
);
