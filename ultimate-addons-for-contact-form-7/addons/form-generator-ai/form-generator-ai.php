<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UACF7_FORM_GENERATOR {

	/*
	 * Construct function
	 */
	public function __construct() {
		//
		define( 'UACF7_FORM_AI_PATH', UACF7_PATH . '/addons/form-generator-ai' );
		// admin scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );


		// add Popup Contact form 7 admin footer
		add_action( 'wpcf7_admin_footer', array( $this, 'uacf7_form_admin_footer_popup' ) );

		// Ai form generator Ajax Function
		add_action( 'wp_ajax_uacf7_form_generator_ai', array( $this, 'uacf7_form_generator_ai' ) );

		// Ai form Get Tag Ajax Function
		add_action( 'wp_ajax_uacf7_form_generator_ai_get_tag', array( $this, 'uacf7_form_generator_ai_get_tag' ) );

		// uacf7_default_wrapper_properties; 
		add_filter( 'wpcf7_contact_form_properties', array( $this, 'uacf7_default_wrapper_properties' ), 10, 2 );
	}



	// Add Admin Scripts
	public function admin_scripts() {
		wp_enqueue_script( 'uacf7-form-generator-ai-choices-js', UACF7_ADDONS . '/form-generator-ai/assets/js/choices.min.js', array(), UACF7_VERSION, true );
		wp_enqueue_script( 'uacf7-form-generator-ai-admin-js', UACF7_ADDONS . '/form-generator-ai/assets/js/admin-form-generator-ai.js', array( 'jquery' ), UACF7_VERSION, true );
		// wp_enqueue_style( 'uacf7-form-generator-ai-choices-css', UACF7_ADDONS . '/form-generator-ai/assets/css/choices.css' ); 

		wp_enqueue_style( 'uacf7-form-generator-ai-admin-css', UACF7_ADDONS . '/form-generator-ai/assets/css/admin-form-generator-ai.css', array(), UACF7_VERSION, 'all' );

		wp_localize_script( 'uacf7-form-generator-ai-admin-js', 'uacf7_form_ai',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'uacf7-form-generator-ai-nonce' ),
				'loader' => UACF7_ADDONS . '/form-generator-ai/assets/images/pre-loader.svg',
			)
		);
	}




	// Add Popup Contact form 7 admin footer
	public function uacf7_form_admin_footer_popup() {
		ob_start();
		?>
		<div class="uacf7-form-ai-popup">
			<div class="uacf7-form-ai-wrap">
				<div class="uacf7-form-ai-inner">
					<div class="close" title="Exit Full Screen">╳</div>

					<div class="uacf7-ai-form-column">
						<div class="uacf7-form-input-wrap">

							<h4><?php echo esc_html__( 'Create a', 'ultimate-addons-for-contact-form-7' ); ?></h4>
							<div class="uacf7-form-input-inner">
								<select class="form-control uacf7-choices" data-trigger name="uacf7-form-generator-ai"
									id="uacf7-form-generator-ai" placeholder="This is a placeholder" multiple>
								</select>
								<button
									class="uacf7_ai_search_button"><?php echo esc_html__( 'Generate With AI', 'ultimate-addons-for-contact-form-7' ); ?></button>
							</div>

						</div>
						<div class="uacf7-doc-notice">
							<?php echo wp_kses_post( sprintf(
								/* Translators: 1: documentation link */
								__( 'Not sure how to use this? Check our step by step  %1s.', 'ultimate-addons-for-contact-form-7' ),
								'<a href="'.esc_url('https://themefic.com/docs/uacf7/free-addons/ai-form-generator/').'" target="_blank">'.esc_html__('documentation', 'ultimate-addons-for-contact-form-7').'</a>'
							) ); ?>
						</div>
					</div>
					<div class="uacf7-ai-form-column">
						<div class="uacf7-ai-codeblock">
							<div class="uacf7-ai-navigation">
								<span class="uacf7-ai-code-reset"> <?php echo esc_html__( 'Reset', 'ultimate-addons-for-contact-form-7' ); ?></span>
								<span class="uacf7-ai-code-copy"> <?php echo esc_html__( 'Copy', 'ultimate-addons-for-contact-form-7' ); ?></span>
								<span class="uacf7-ai-code-insert"> <?php echo esc_html__( 'Insert', 'ultimate-addons-for-contact-form-7' ); ?></span>
							</div>
							<textarea name="uacf7_ai_code_content" id="uacf7_ai_code_content"></textarea>
						</div>
					</div>

				</div>
			</div>
		</div>
		<?php
		
		$popup_markup = ob_get_clean();
		$allowed_popup_html = array(
			'div'      => array(
				'class' => true,
				'title' => true,
			),
			'h4'       => array(),
			'span'     => array(
				'class' => true,
			),
			'select'   => array(
				'class'        => true,
				'data-trigger' => true,
				'name'         => true,
				'id'           => true,
				'placeholder'  => true,
				'multiple'     => true,
			),
			'button'   => array(
				'class' => true,
			),
			'textarea' => array(
				'name' => true,
				'id'   => true,
			),
			'a'        => array(
				'href'   => true,
				'target' => true,
			),
		);

		// Output securely using wp_kses
		echo wp_kses( $popup_markup, $allowed_popup_html );
	}

	public function uacf7_form_generator_ai_get_tag() {
		$ajax_nonce = isset( $_POST['ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ajax_nonce'] ) ) : '';
		if ( empty( $ajax_nonce ) || ! wp_verify_nonce( $ajax_nonce, 'uacf7-form-generator-ai-nonce' ) ) {
			exit( esc_html__( "Security error", 'ultimate-addons-for-contact-form-7' ) );
		}
		$tag_generator = WPCF7_TagGenerator::get_instance( 'panel', true );

		$reflector = new ReflectionClass( 'WPCF7_TagGenerator' );
		$property = $reflector->getProperty( 'panels' );
		$property->setAccessible( true );

		$panels = $property->getValue( $tag_generator );
		$tag_data = [];
		foreach ( $panels as $key => $value ) {
			if ( $key !== 'uacf7_conversational_start' && $key != 'uacf7_conversational_end' && $key != 'uacf7_step_start' && $key != 'uacf7_step_end' && $key != 'conditional' && $key != 'repeater' ) {
				$tag_value['value'] = $key;
				$tag_value['label'] = $value['title'];
				$tag_data[] = $tag_value;
			}

		}
		// $form_booking =  apply_filters('uacf7_booking_ai_form_dropdown', ["value" => "booking", "label" => "Booking (Pro)", "disabled" => "false"]);

		$secend_option_form = [ 
			[ "value" => "basis-contact-form", "label" => "Basis Contact Form" ],
			[ "value" => "newslettertow", "label" => "Newsletter Style 2" ],
			[ "value" => "blog-newsletter", "label" => "Blog Newsletter" ],
			[ "value" => "support-form", "label" => "Support form" ],
			[ "value" => "polling", "label" => "Polling Form" ],
			[ "value" => "survey-form", "label" => "Client Satisfaction Survey Form" ],
			[ "value" => "complaint-form", "label" => "Customer Complaint Form" ],
			[ "value" => "service-order", "label" => "Service Order Form" ],
			[ "value" => "proposal", "label" => "Conference Proposal" ],
			[ "value" => "volunteer-sign-up-form", "label" => "Volunteer sign up form" ],
			[ "value" => "donation-form", "label" => "Donation Form" ],

			[ "value" => "blood-donation", "label" => "Blood Donation" ],
			[ "value" => "charity-dinner", "label" => "Charity Dinner" ],
			[ "value" => "volunteer-application", "label" => "Volunteer Application" ],
			[ "value" => "graphic-designer-contact-form", "label" => "Graphic Designer Contact Form" ],
			[ "value" => "hardware-request-form", "label" => "Hardware Request Form" ],
			[ "value" => "it-service-req", "label" => "IT Service Request" ],
			[ "value" => "request-for-quote", "label" => "Request for Quote" ],
			[ "value" => "report-a-bug", "label" => "Report a Bug" ],
			[ "value" => "check-request", "label" => "Check Request" ],

			[ "value" => "multistep", "label" => "Multistep" ],
			apply_filters( 'uacf7_booking_ai_form_dropdown', [ "value" => "booking", "label" => "Booking (Pro)" ] ),
			[ "value" => "conditional", "label" => "Conditional" ],
			[ "value" => "subscription", "label" => "Subscription" ],
			apply_filters( 'uacf7_repeater_ai_form_dropdown', [ "value" => "repeater", "label" => "Repeater (Pro)" ] ),
			apply_filters( 'uacf7_blog_submission_ai_form_dropdown', [ "value" => "blog", "label" => "Blog Submission (Pro)" ] ),
			[ "value" => "feedback", "label" => "Feedback" ],
			[ "value" => "application", "label" => "Application" ],
			[ "value" => "inquiry", "label" => "Inquiry" ],
			[ "value" => "survey", "label" => "Survey" ],
			[ "value" => "address", "label" => "Address" ],
			[ "value" => "event", "label" => "Event Registration" ],
			[ "value" => "newsletter", "label" => "Newsletter" ],
			[ "value" => "donation", "label" => "Donation" ],
			[ "value" => "product-review", "label" => "Product Review" ],
			apply_filters( 'uacf7_service_booking_form_dropdown', [ "value" => "service-booking", "label" => "Service Booking (Pro)" ] ),
			apply_filters( 'uacf7_appointment_form_dropdown', [ "value" => "appointment-form", "label" => "Appointment (Pro)" ] ),
			apply_filters( 'uacf7_conversational_appointment_form_dropdown', [ "value" => "conversational-appointment-form", "label" => "Conversational Appointment Booking  (Pro)" ] ),
			apply_filters( 'uacf7_conversational_interview_form_dropdown', [ "value" => "conversational-interview-form", "label" => "Conversational Interview Process (Pro)" ] ),
			[ "value" => "rating", "label" => "Rating" ],
		];

		$data = [ 
			'status' => 'success',
			'value_tag' => $tag_data,
			'value_form' => $secend_option_form,
		];

		wp_send_json( $data );
		die();

	}

	// Ai form Get Tag Ajax Function
	public function uacf7_form_generator_ai() {
		$ajax_nonce = isset( $_POST['ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ajax_nonce'] ) ) : '';
		if ( empty( $ajax_nonce ) || ! wp_verify_nonce( $ajax_nonce, 'uacf7-form-generator-ai-nonce' ) ) {
			exit( esc_html__( "Security error", 'ultimate-addons-for-contact-form-7' ) );
		}
		$vaue = '';
		$raw_uacf7_default = isset( $_POST['searchValue'] ) ? map_deep( wp_unslash( $_POST['searchValue'] ), 'sanitize_text_field' ) : '';
		if ( is_array( $raw_uacf7_default ) ) {
			$uacf7_default = array_map( 'sanitize_text_field', $raw_uacf7_default );
		} else {
			$uacf7_default = sanitize_text_field( $raw_uacf7_default );
		}

		if ( is_array( $uacf7_default ) && count( $uacf7_default ) > 0 && $uacf7_default[0] === 'form' ) {
			$value = require_once apply_filters( 'uacf7_ai_form_generator_template', UACF7_FORM_AI_PATH . '/templates/uacf7-forms.php' );
		} elseif ( is_array( $uacf7_default ) && count( $uacf7_default ) > 0 && $uacf7_default[0] === 'tag' ) {
			$value = require_once apply_filters( 'uacf7_ai_form_generator_template', UACF7_FORM_AI_PATH . '/templates/uacf7-tags.php' );
		}
		$data = [ 
			'status' => 'success',
			'value' => $value,
		];
		wp_send_json( $data );
		die();
	}


	public function uacf7_default_wrapper_properties( $properties, $CF7form ) {
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

			$form = $properties['form'];

			// Check for null or empty forms to avoid unnecessary processing
			if ( empty( $form ) ) {
				return $properties;
			}

			// Use a robust regex to capture the custom tags and content
			$pattern = '/(\[\/?uacf7-default-wrapper(?:\]|\s[^\]]*?\]))/';
			$form_parts = preg_split( $pattern, $form, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );

			ob_start();

			$open_wrapper = false; // Track if a wrapper is open
			foreach ( $form_parts as $form_part ) {
				if ( preg_match( '/^\[uacf7-default-wrapper\]/', $form_part ) ) {
					if ( $open_wrapper ) {
						echo '</div>'; // Close any open wrapper to prevent nesting issues
					}
					echo '<div id="uacf7-form-wrapper" class="uacf7-wrapper-default">';
					$open_wrapper = true;
				} elseif ( preg_match( '/^\[\/uacf7-default-wrapper\]/', $form_part ) ) {
					if ( $open_wrapper ) {
						echo '</div>'; // Close the wrapper
						$open_wrapper = false;
					}
				} else {
					echo wp_kses_post( $form_part ); // Output the content, sanitized
				}
			}

			// Ensure no unclosed wrappers
			if ( $open_wrapper ) {
				echo '</div>';
			}

			$properties['form'] = ob_get_clean();
		}

		return $properties;
	}
}

new UACF7_FORM_GENERATOR();


?>