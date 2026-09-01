<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class UACF7_MAILCHIMP {

	public  $mailchimlConnection = '';
	public  static $mailchimp    = null;
	private $mailchimp_api       = '';

	public function __construct() {
		require_once( 'inc/functions.php' );
		add_action( "wpcf7_before_send_mail", array( $this, 'send_data' ) );
		add_filter( 'uacf7_post_meta_options', array( $this, 'uacf7_post_meta_options_mailchimp' ), 17, 2 );
		add_filter( 'uacf7_settings_options', array( $this, 'uacf7_settings_options_mailchimp' ), 17, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'wp_enqueue_admin_script' ) );
		add_action( 'wp_ajax_uacf7_ajax_mailchimp', array( $this, 'uacf7_ajax_mailchimp' ) );

		$this->get_api_key();
		require_once( 'inc/functions.php' );

		// add_filter( 'wpcf7_load_js', '__return_false' );
	}

	/*
	 * Enqueue script Backend
	 */
	public function wp_enqueue_admin_script() {
		wp_enqueue_script( 'uacf7-mailchimp_admin', UACF7_ADDONS . '/mailchimp/assets/js/mailchimp_admin.js', array( 'jquery' ), UACF7_VERSION, true );
		wp_localize_script(
			'uacf7-mailchimp_admin',
			'uacf7_mailchimp_peram',
			array(
				'admin_url' => get_admin_url() . 'admin.php',
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'plugin_dir_url' => plugin_dir_url( __FILE__ ),
				'nonce' => wp_create_nonce( 'uacf7_mailchimp_admin_nonce' ),
			)
		);
	}

	public function uacf7_ajax_mailchimp() {
		// Capability check
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'You do not have permission to perform this action.' );
			wp_die(); // Terminate execution
		}

		// Verify nonce
		$ajax_nonce = isset( $_POST['ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ajax_nonce'] ) ) : '';
		if ( empty( $ajax_nonce ) || ! wp_verify_nonce( $ajax_nonce, 'uacf7_mailchimp_admin_nonce' ) ) {
			wp_send_json_error( esc_html__( "Security error", 'ultimate-addons-for-contact-form-7' ) );
			wp_die(); // Terminate execution
		}

		// Check if POST data is set and not empty
		$input_key = isset( $_POST['inputKey'] ) ? sanitize_text_field( wp_unslash( $_POST['inputKey'] ) ) : '';
		if ( empty( $input_key ) ) {
			wp_send_json_error( 'No API key provided.' );
			wp_die(); // Terminate execution
		}

		$api_key = '';
		if ( $input_key ) {
			$api_key = $input_key;
		}

		$status = '';
		if ( $api_key != '' ) {

			$response = $this->set_config( $api_key, 'ping' );
			$response = json_decode( $response );

			if ( $response !== null ) {
				$status .= '<span class="status-title"><strong>' . esc_html__( 'Status: ', 'ultimate-addons-for-contact-form-7' ) . '</strong>';


				if ( isset( $response->health_status ) ) { //Display success message
					$status .= '<span class="status-success">' . esc_html( $response->health_status, 'ultimate-addons-for-contact-form-7' ) . '</span>';
				}

				if ( isset( $response->title ) ) { //Display error title
					$status .= '<span class="status-error">' . esc_html( $response->title, 'ultimate-addons-for-contact-form-7' ) . '</span>';
				}

				$status .= '</span>';

				if ( isset( $response->detail ) ) { //Display error mdetails
					$status .= '<span class="status-details status-error">' . esc_html( $response->detail, 'ultimate-addons-for-contact-form-7' ) . '</span>';
				}
			} else {
				$status .= '<span class="status-error">' . esc_html( 'Not Connected! invalid API Key', 'ultimate-addons-for-contact-form-7' ) . '</span>';
			}

		} else {
			$status .= '<span class="status-error">' . esc_html( 'Empty! Please fill the API key', 'ultimate-addons-for-contact-form-7' ) . '</span>';
		}

		// Send response back to the AJAX request
		wp_send_json_success(
			array(
				'status' => $status,
				'res' => $response
			)
		);

		wp_die(); // Terminate execution
	}

	function uacf7_settings_options_mailchimp( $value ) {
		$status = $this->connection_status();
		$value['mailchimp']['fields']['uacf7_mailchimp_api_status'] = array(
			'id'       => 'uacf7_mailchimp_api_status',
			'type'     => 'callback',
			'function' => 'uacf7_mailchimp_api_status_callback',
			'argument' => $status,

		);
		return $value;
	}

	public function uacf7_post_meta_options_mailchimp( $value, $post_id ) {
		$status = $this->connection_status();

		// Get all Mailchimp audiences
		$api_key  = $this->mailchimp_api;
		$audience = array();

		if ( $api_key !== '' ) {
			$offset = 0;
			$count  = 100; // Max allowed per request
			$total_items = null;

			do {
				// Fetch a page of results
				$path     = "lists?offset={$offset}&count={$count}";
				$response = $this->set_config( $api_key, $path );

				$response = json_decode( $response, true );
				if ( isset( $response['lists'] ) && is_array( $response['lists'] ) ) {
					foreach ( $response['lists'] as $list ) {
						$audience[ $list['id'] ] = $list['name'];
					}
				}

				// Set total_items on first loop
				if ( $total_items === null && isset( $response['total_items'] ) ) {
					$total_items = (int) $response['total_items'];
				}

				$offset += $count;

			} while ( $offset < $total_items );
		}

		$fields = array(
			'uacf7_mailchimp_label' => array(
				'id' => 'uacf7_mailchimp_label',
				'type' => 'heading',
				'label' => __( 'Mailchimp Integration', 'ultimate-addons-for-contact-form-7' ),
				'subtitle' => sprintf(
					/* translators: %1$s: demo link */
					__( 'Send form submissions to your Mailchimp lists automatically. See Demo %1s.', 'ultimate-addons-for-contact-form-7' ),
					'<a href="https://cf7addons.com/preview/mailchimp-for-contact-form-7/" target="_blank" rel="noopener">'.esc_html__( 'here', 'ultimate-addons-for-contact-form-7' ).'</a>'
				)
			),

			'uacf7_mailchimp_form_enable' => array(
				'id' => 'uacf7_mailchimp_form_enable',
				'type' => 'switch',
				'label' => __( ' Enable Mailchimp ', 'ultimate-addons-for-contact-form-7' ),
				'label_on' => __( 'Yes', 'ultimate-addons-for-contact-form-7' ),
				'label_off' => __( 'No', 'ultimate-addons-for-contact-form-7' ),
				'field_width' => '50',
				'subtitle' => sprintf(
					/* translators: %1$s: demo link */
					__( 'Before enabling, ensure you have added your Mailchimp API key %1s.', 'ultimate-addons-for-contact-form-7' ),
					'<a href="admin.php?page=uacf7_settings#tab=mailchimp" target="_blank" rel="noopener">'.esc_html__( 'here', 'ultimate-addons-for-contact-form-7' ).'</a>'
				),
				'default' => false
			),
			'mailchimp_docs' => array(
				'id' => 'mailchimp_docs',
				'class' => 'mailchimp_docs_notice',
				'type' => 'notice',
				'style' => 'success',
				'content' => sprintf(
					/* translators: %1$s: demo link */
					__( 'Confused? Check our Documentation on  %1s.', 'ultimate-addons-for-contact-form-7' ),
					'<a href="https://themefic.com/docs/uacf7/free-addons/contact-form-7-mailchimp/" target="_blank" rel="noopener">'.esc_html__( 'Mailchimp Integration', 'ultimate-addons-for-contact-form-7' ).'</a>'
				)
			),

			'uacf7_mailchimp_form_options_heading' => array(
				'id' => 'uacf7_mailchimp_form_options_heading',
				'type' => 'heading',
				'label' => __( 'Mailchimp Option ', 'ultimate-addons-for-contact-form-7' ),
				'dependency' => [ 'uacf7_mailchimp_form_enable', '==', '1' ],
			),

			'mailchimp_uacf7_help' => array(
				'id' => 'mailchimp_uacf7_help',
				'type' => 'notice',
				'style' => 'success',
				'content' => sprintf(
					__( 'Note: If you dont see the field names in the field selection, please save the form and try again.', 'ultimate-addons-for-contact-form-7' )
				),
				'dependency' => [ 'uacf7_mailchimp_form_enable', '==', '1' ],
			),

			'uacf7_mailchimp_api_status' => array(
				'id' => 'uacf7_mailchimp_api_status',
				'type' => 'callback',
				'function' => 'uacf7_mailchimp_api_status_callback',
				'argument' => $status,
				'dependency' => [ 'uacf7_mailchimp_form_enable', '==', '1' ],
			),

			'uacf7_mailchimp_form_type' => array(
				'id' => 'uacf7_mailchimp_form_type',
				'type' => 'radio',
				'label' => __( 'Type of Form', 'ultimate-addons-for-contact-form-7' ),
				// 'field_width' => '50',
				'options' => array(
					'subscribe' => 'Subscription Form',
					// 'unsubscribe' => 'Unsubscribe Form',
				),
				'default' => 'subscribe',
				'inline' => true,
				'dependency' => [ 'uacf7_mailchimp_form_enable', '==', '1' ],
			),
			'uacf7_mailchimp_audience' => array(
				'id' => 'uacf7_mailchimp_audience',
				'type' => 'select',
				'label' => __( ' Select Mailchimp Audience ', 'ultimate-addons-for-contact-form-7' ),
				'field_width' => '25',
				'options' => $audience,
				'dependency' => [ 'uacf7_mailchimp_form_enable', '==', '1' ],
			),
			'uacf7_mailchimp_subscriber_email' => array(
				'id' => 'uacf7_mailchimp_subscriber_email',
				'type' => 'select',
				'label' => __( ' Subscriber Email ', 'ultimate-addons-for-contact-form-7' ),
				'query_args' => array(
					'post_id' => $post_id,
					'specific' => 'email',
				),
				'options' => 'uacf7',
				'field_width' => '25',
				'dependency' => [ 'uacf7_mailchimp_form_enable', '==', '1' ],
			),
			'uacf7_mailchimp_subscriber_fname' => array(
				'id' => 'uacf7_mailchimp_subscriber_fname',
				'type' => 'select',
				'label' => __( ' Subscriber First Name ', 'ultimate-addons-for-contact-form-7' ),
				'query_args' => array(
					'post_id' => $post_id,
					'specific' => 'text',
				),
				'options' => 'uacf7',
				'field_width' => '25',
				'dependency' => [ 'uacf7_mailchimp_form_enable', '==', '1' ],
			),
			'uacf7_mailchimp_subscriber_lname' => array(
				'id' => 'uacf7_mailchimp_subscriber_lname',
				'type' => 'select',
				'label' => __( ' Subscriber Last Name ', 'ultimate-addons-for-contact-form-7' ),
				'query_args' => array(
					'post_id' => $post_id,
					'specific' => 'text',
				),
				'options' => 'uacf7',
				'field_width' => '25',
				'dependency' => [ 'uacf7_mailchimp_form_enable', '==', '1' ],
			),
			'uacf7_mailchimp_merge_fields' => array(
				'id' => 'uacf7_mailchimp_merge_fields',
				'type' => 'repeater',
				'label' => 'Add New Custom Field',
				'subtitle' => 'Use this option to send your additional field data to Mailchimp, excluding Email and Name.',
				'class' => 'tf-field-class',
				'fields' => array(
					'mailtag' => array(
						'id' => 'mailtag',
						'label' => 'Contact Form Tag',
						'subtitle' => 'Contact Form Tag to Mailchimp fields and *|MERGE|* tags',
						'type' => 'select',
						'field_width' => '50',
						'query_args' => array(
							'post_id' => $post_id,
							'exclude_types' => [ 'submit' ]
						),
						'options' => 'uacf7',
					),
					'mergefield' => array(
						'id' => 'mergefield',
						'label' => 'Mailchimp Field',
						'subtitle' => 'Audience fields and *|MERGE|* tags, Put those tag here',
						'type' => 'text',
						'field_width' => '50',
					),
				),
				'dependency' => [ 'uacf7_mailchimp_form_enable', '==', '1' ],
			),
		);

		/**
		 * Allow extensions to add/modify Mailchimp fields.
		 */
		$fields = apply_filters(
			'uacf7_mailchimp_fields',
			$fields,
			$post_id
		);

		$mailchimp = apply_filters( 'uacf7_post_meta_options_mailchimp_pro', $data = array(
			'title' => __( 'Mailchimp', 'ultimate-addons-for-contact-form-7' ),
			'icon' => 'fa-brands fa-mailchimp',
			'checked_field' => 'uacf7_mailchimp_form_enable',
			'fields' => $fields,
		), $post_id );

		$value['mailchimp'] = $mailchimp;
		return $value;
	}


	/* Get mailchimp api key */
	public function get_api_key() {

		$uacf7_mailchimp_api_key = uacf7_settings( 'uacf7_mailchimp_api_key' );

		if ( $uacf7_mailchimp_api_key != false ) {
			return $this->mailchimp_api = $uacf7_mailchimp_api_key;
		}

		$this->mailchimp_connection();

	}

	/* mailchimp Connection check */
	public function mailchimp_connection() {

		$api_key = $this->mailchimp_api;

		if ( $api_key != '' ) {

			$response = $this->set_config( $api_key, 'ping' );
			$response = json_decode( $response );

			if ( isset( $response->health_status ) ) { //Display success message
				$this->mailchimlConnection = true;
			} else {
				$this->mailchimlConnection = false;
			}
		}
	}

	/* Mailchimp config set */
	private function set_config( $api_key = '', $path = '' ) {
		if ( empty( $api_key ) || empty( $path ) ) {
			return '';
		}

		$server_prefix = explode( "-", $api_key );

		if ( ! isset( $server_prefix[1] ) ) {
			return '';
		}
		$server_prefix = $server_prefix[1];

		$url = "https://$server_prefix.api.mailchimp.com/3.0/$path";

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'sslverify' => false,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		return wp_remote_retrieve_body( $response );
	}

	/* Mailchimp connection status */
	public function connection_status() {
		$api_key = $this->mailchimp_api;
		$status = '';
		if ( $api_key != '' ) {

			$response = $this->set_config( $api_key, 'ping' );
			$response = json_decode( $response );

			$status .= '<span class="status-title"><strong>' . esc_html__( 'Status: ', 'ultimate-addons-for-contact-form-7' ) . '</strong>';

			if ( isset( $response->health_status ) ) { //Display success message
				$status .= '<span class="status-success">' . esc_html( $response->health_status, 'ultimate-addons-for-contact-form-7' ) . '</span>';
			}

			if ( isset( $response->title ) ) { //Display error title
				$status .= '<span class="status-error">' . esc_html( $response->title, 'ultimate-addons-for-contact-form-7' ) . '</span>';
			}

			$status .= '</span>';

			if ( isset( $response->detail ) ) { //Display error mdetails
				$status .= '<span class="status-details status-error">' . esc_html( $response->detail, 'ultimate-addons-for-contact-form-7' ) . '</span>';
			}
		} else {
			$status .= '<span class="status-details status-error">' . esc_html( 'Please configure your Mailchimp API Key from', 'ultimate-addons-for-contact-form-7' ) . ' <strong>' . esc_html( 'Addons -> Settings -> Mailchimp API', 'ultimate-addons-for-contact-form-7' ) . ' </strong>' . '</span>';
		}

		return $status;
	}

	/* Add members to mailchimp */
	public function add_members( $id, $audience, $posted_data ) {
		$this->mailchimp_connection();
		$api_key = $this->mailchimp_api;

		// get mailchimp Post Data
		$mailchimp = uacf7_get_form_option( $id, 'mailchimp' );

		$subscriber_email = isset( $mailchimp['uacf7_mailchimp_subscriber_email'] ) ? $mailchimp['uacf7_mailchimp_subscriber_email'] : '';

		$subscriber_email = ! empty( $subscriber_email ) ? $posted_data[ $subscriber_email ] : '';

		if ( $this->mailchimlConnection && ! empty( $api_key ) && ! empty( $subscriber_email ) ) {
			$server_prefix = explode( "-", $api_key );
			$server_prefix = $server_prefix[1];

			$subscriber_fname = isset( $mailchimp['uacf7_mailchimp_subscriber_fname'] ) ? $mailchimp['uacf7_mailchimp_subscriber_fname'] : '';
			$subscriber_fname = ! empty( $subscriber_fname ) ? $posted_data[ $subscriber_fname ] : '';

			$subscriber_lname = isset( $mailchimp['uacf7_mailchimp_subscriber_lname'] ) ? $mailchimp['uacf7_mailchimp_subscriber_lname'] : '';
			$subscriber_lname = ! empty( $subscriber_lname ) ? $posted_data[ $subscriber_lname ] : '';
			$extra_fields     = isset( $mailchimp['uacf7_mailchimp_merge_fields'] ) && is_array( $mailchimp['uacf7_mailchimp_merge_fields'] ) ? $mailchimp['uacf7_mailchimp_merge_fields'] : array();
			
			$extra_merge_fields = '';
			foreach ( $extra_fields as $extra_field ) {
				$mailtag = str_replace( '[]', '', $extra_field['mailtag'] );
				$value = isset( $posted_data[ $mailtag ] ) ? $posted_data[ $mailtag ] : '';
			
				if ( is_array( $value ) ) {
					$value = implode( ', ', $value );
				}
			
				$extra_merge_fields .= '"' . $extra_field['mergefield'] . '": "' . esc_js( $value ) . '",';
			}
			
			$extra_merge_fields = trim( $extra_merge_fields, ',' );

			if ( $extra_merge_fields != '' ) {
				$extra_merge_fields = ',' . $extra_merge_fields;
			}

			$url = "https://$server_prefix.api.mailchimp.com/3.0/lists/" . $audience . "/members";

			//Mailchimp data
			$data = '{"email_address":"' . sanitize_email( $subscriber_email ) . '","status":"subscribed","merge_fields":{"FNAME": "' . sanitize_text_field( $subscriber_fname ) . '", "LNAME": "' . sanitize_text_field( $subscriber_lname ) . '"' . $extra_merge_fields . '},"vip":false,"location":{"latitude":0,"longitude":0}}';

			$response = wp_remote_post(
				$url,
				array(
					'timeout' => 30,
					'method' => 'POST',
					'sslverify' => false,
					'headers' => array(
						'Authorization' => 'Bearer ' . $api_key,
						'Content-Type' => 'application/json',
					),
					'body' => $data,
				)
			);

			if ( is_wp_error( $response ) ) {
				return '';
			}

			$resp = wp_remote_retrieve_body( $response );

			/**
			 * Allow extensions to perform additional work
			 * after a Mailchimp member has been subscribed.
			 */
			do_action(
				'uacf7_mailchimp_member_subscribed',
				$id,
				$audience,
				$subscriber_email,
				array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				$server_prefix
			);

			return $resp;
		} else {
			return '';
		}

	}

	public function send_data( $cf7 ) {

		$wpcf = WPCF7_Submission::get_instance();

		if ( ! $wpcf ) {
			return;
		}

		$posted_data = $wpcf->get_posted_data();
		$id          = $cf7->id();

		// Get Mailchimp settings from the form options.
		$mailchimp = uacf7_get_form_option( $id, 'mailchimp' );

		$form_enable = isset( $mailchimp['uacf7_mailchimp_form_enable'] ) ? $mailchimp['uacf7_mailchimp_form_enable'] : '';

		$form_type = isset( $mailchimp['uacf7_mailchimp_form_type']  ) ? $mailchimp['uacf7_mailchimp_form_type'] : '';

		$audience = isset( $mailchimp['uacf7_mailchimp_audience'] ) ? $mailchimp['uacf7_mailchimp_audience'] : '';


		/**
		 * Allow extensions to determine whether
		 * this submission should be subscribed.
		 */
		$should_subscribe = apply_filters( 'uacf7_mailchimp_should_subscribe', true, $id, $wpcf, $mailchimp );

		if (
			$form_enable &&
			$form_type === 'subscribe' &&
			! empty( $audience ) &&
			$should_subscribe
		) {

			$this->add_members( $id, $audience, $posted_data );
		}
	}


}
new UACF7_MAILCHIMP();
