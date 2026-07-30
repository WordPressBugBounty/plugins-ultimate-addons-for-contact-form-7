<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UACF7_WEB_HOOK {

	/*
	 * Construct function
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_webhook_style' ) );

		add_filter( 'uacf7_post_meta_options', array( $this, 'uacf7_post_meta_options_webhook' ), 12, 2 );

		add_action( 'wpcf7_before_send_mail', array( $this, 'uacf7_send_data_by_web_hook' ) );
		// add_filter( 'wpcf7_load_js', '__return_false' );
	}


	public function enqueue_webhook_style() {
		wp_enqueue_style( 'uacf7-web-hook', UACF7_ADDONS . '/web-hook/css/web-hook.css' );
		wp_enqueue_script( 'uacf7-web-hook-script', UACF7_ADDONS . '/web-hook/js/web-hook.js', array( 'jquery' ), '', true );
	}

	// Add Web Hook Options
	public function uacf7_post_meta_options_webhook( $value, $post_id ) {

		$WebHook = apply_filters( 'uacf7_post_meta_options_webhook', $data = array(
			'title' => __( 'Webhook', 'ultimate-addons-cf7' ),
			'icon' => 'fa-solid fa-code-compare',
			'checked_field'   => 'uacf7_enable_web_hook',
			'fields' => [ 
				'uacf7_Web_hook_heading' => [ 
					'id' => 'uacf7_web_hook_heading',
					'type' => 'heading',
					'label' => __( 'Webhook (Pabbly/Zapier) Settings', 'ultimate-addons-cf7' ),
					'subtitle' => sprintf(
                        __( 'Transfer form data to third-party services like Pabbly or Zapier via webhooks. See Demo %1s.', 'ultimate-addons-cf7' ),
                         '<a href="#" target="_blank">Example</a>'
                    )
				],
				'webhook_docs' => [ 
					'id'      => 'webhook_docs',
					'type'    => 'notice',
					'style'   => 'success',
					'content' => sprintf( 
                        __( 'Confused? Check our Documentation on  %1s.', 'ultimate-addons-cf7' ),
                        '<a href="https://themefic.com/docs/uacf7/free-addons/contact-form-7-webhook/" target="_blank">Webhook Setup</a>'
                    )
				],
				'uacf7_enable_web_hook' => [ 
					'id' => 'uacf7_enable_web_hook',
					'type' => 'switch',
					'label' => __( ' Enable Webhook ', 'ultimate-addons-cf7' ),
					'label_on' => __( 'Yes', 'ultimate-addons-cf7' ),
					'label_off' => __( 'No', 'ultimate-addons-cf7' ),
					'field_width' => 50,
					'default' => false
				],
				'uacf7_enable_web_hook_condition' => [ 
					'id' => 'uacf7_enable_web_hook_condition',
					'type' => 'switch',
					'label' => __( ' Enable Webhook Conditions', 'ultimate-addons-cf7' ),
					'label_on' => __( 'Yes', 'ultimate-addons-cf7' ),
					'label_off' => __( 'No', 'ultimate-addons-cf7' ),
					'field_width' => 50,
					'default' => false,
					'is_pro' => true
				],

				'uacf7_webhook_conditional_form_options_heading' => array(
					'id' => 'uacf7_webhook_conditional_form_options_heading',
					'type' => 'heading',
					'label' => __( 'Conditional Option ', 'ultimate-addons-cf7' ),
					'dependency' => [ 'uacf7_enable_web_hook_condition', '==', 1 ],
				),
				'uacf7_webhook_conditional_repeater' => array(
					'id' => 'uacf7_webhook_conditional_repeater',
					'type' => 'repeater',
					'label' => __( 'Setup your Conditional Logic', 'ultimate-addons-cf7' ),
					'subtitle' => __( "The webhook will send data to the endpoint only if the conditional logic matches. Select a field and determine whether the data should be sent when any or all specified conditions are met.", 'ultimate-addons-cf7' ),
					'class' => 'tf-field-class',
					'fields' => array(
						'uacf7_cf_hs' => array(
							'id' => 'uacf7_cf_hs',
							'type' => 'select',
							'label' => __( 'Condition', 'ultimate-addons-cf7' ),
							'subtitle' => "Select whether this field's value should be sent to the webhook or not when the condition below is met.",
							'class' => 'tf-field-class',
							'options' => array(
								'send' => 'Send to webhook',
								'hide' => 'Skip sending to webhook',
							),
							'field_width' => '50',
						),
						'uacf7_cf_condition_for' => array(
							'id' => 'uacf7_cf_condition_for',
							'type' => 'select',
							'label' => __( 'If', 'ultimate-addons-cf7' ),
							'subtitle' => "Choose the trigger for the condition: it should activate if 'any' one of the conditions is met or when 'all' conditions.",
							'class' => 'tf-field-class',
							'options' => array(
								'any' => 'Any',
								'all' => 'All',
							),
							'field_width' => '50',

						),
						'uacf7_cf_conditions' => array(
							'id' => 'uacf7_cf_conditions',
							'type' => 'repeater',
							'label' => __( 'Add Condition', 'ultimate-addons-cf7' ),
							'class' => 'tf-field-class',
							'fields' => array(

								'uacf7_cf_tn' => array(
									'id' => 'uacf7_cf_tn',
									'type' => 'select',
									'label' => __( 'Conditional Field', 'ultimate-addons-cf7' ),
									'class' => 'tf-field-class',
									'options' => 'uacf7',
									'query_args' => array(
										'post_id' => $post_id,
										'exclude' => [ 'submit', 'conditional' ],
									),
									'field_width' => '50',
								),
								'uacf7_cf_operator' => array(
									'id' => 'uacf7_cf_operator',
									'type' => 'select',
									'label' => __( 'is', 'ultimate-addons-cf7' ),
									'class' => 'tf-field-class',
									'options' => array(
										'equal' => 'equal',
										'not_equal' => 'Not Equal',
										'greater_than' => 'Greater than',
										'less_than' => 'Less than',
										'greater_than_or_equal_to' => 'Greater than or equal to',
										'less_than_or_equal_to' => 'Less than or equal to',
										'starts_with' => 'Starts with',
										'ends_with' => 'Ends With',
										'contains' => 'Contains',
										'does_not_contain' => 'Does not contain'
									),
									'field_width' => '50',
								),
								'uacf7_cf_val' => array(
									'id' => 'uacf7_cf_val',
									'type' => 'text',
									'label' => 'Conditional Value',
									'subtitle' => 'Input the specific value that will trigger the condition.',
									'description' => '',
									'class' => 'tf-field-class',
								)
							),
						)
					),
					'dependency' => [ 'uacf7_enable_web_hook_condition', '==', 1 ],
					'is_pro' => true
				),

				'web_hook_form_options_heading' => array(
					'id'        => 'web_hook_form_options_heading',
					'type'      => 'heading',
					'label'     => __( 'Webhook Option ', 'ultimate-addons-cf7' ),
				),

				'uacf7_web_hook_api' => [ 
					'id' => 'uacf7_web_hook_api',
					'type' => 'text',
					'label' => __( 'Request URL', 'ultimate-addons-cf7' ),
					'placeholder' => __( 'Enter a Request URL', 'ultimate-addons-cf7' ),
					'dependency' => [ 'uacf7_enable_web_hook', '==', 1 ],
				],

				'uacf7_web_hook_request_method' => [ 
					'id' => 'uacf7_web_hook_request_method',
					'type' => 'select',
					'label' => __( 'Request Method', 'ultimate-addons-cf7' ),
					'options' => array(
						'GET' => 'GET',
						'POST' => 'POST',
						'PUT' => 'PUT',
						'DELETE' => 'DELETE',
						'PATCH' => 'PATCH',
					),
					'field_width' => 50,
					'dependency' => [ 'uacf7_enable_web_hook', '==', 1 ],
				],

				'uacf7_web_hook_request_format' => [ 
					'id' => 'uacf7_web_hook_request_format',
					'type' => 'select',
					'label' => __( 'Request Format', 'ultimate-addons-cf7' ),
					'options' => array(
						'json' => 'JSON',
						'formdata' => 'FORMDATA',
					),
					'field_width' => 50,
					'dependency' => [ 'uacf7_enable_web_hook', '==', 1 ],
				],

				'uacf7_web_hook_header_request' => [ 
					'id' => 'uacf7_web_hook_header_request',
					'type' => 'repeater',
					'label' => __( 'Request Headers', 'ultimate-addons-cf7' ),
					'dependency' => [ 'uacf7_enable_web_hook', '==', 1 ],
					'fields' => [ 

						'uacf7_web_hook_header_request_custom' => [ 
							'id' => 'uacf7_web_hook_header_request_custom',
							'type' => 'radio',
							'class' => 'padding-bottom0',
							'label' => __( 'Enable Custom Value', 'ultimate-addons-cf7' ),
							'options' => [ 
								'form' => 'Form Data',
								'custom' => 'Custom Value',
							],
							'default' => 'form',
							'inline' => true,
						],

						'uacf7_web_hook_header_request_value' => [ 
							'id' => 'uacf7_web_hook_header_request_value',
							'class' => 'padding-top0',
							'type' => 'text',
							'placeholder' => __( 'Enter a parameter key', 'ultimate-addons-cf7' ),
							'field_width' => 50,
						],

						'uacf7_web_hook_header_request_parameter' => [ 
							'id' => 'uacf7_web_hook_header_request_parameter',
							'class' => 'padding-top0',
							'type' => 'select',
							// 'label' => __( 'Request Format', 'ultimate-addons-cf7' ),
							'options' => 'uacf7',
							'query_args' => array(
								'post_id' => $post_id,
								'exclude' => [ 'submit', 'conditional' ],
							),
							'dependency' => array( 'uacf7_web_hook_header_request_custom', '==', 'form' ),
							'field_width' => 50,
						],

						'uacf7_web_hook_header_request_parameter_custom' => [ 
							'id' => 'uacf7_web_hook_header_request_parameter_custom',
							'class' => 'padding-top0',
							'type' => 'text',
							'placeholder' => __( 'Custom value', 'ultimate-addons-cf7' ),
							// 'label' => __( 'Request Format', 'ultimate-addons-cf7' ),
							'dependency' => array( 'uacf7_web_hook_header_request_custom', '==', 'custom' ),
							'field_width' => 50,
						],
					]

				],

				'uacf7_web_hook_body_request' => [ 
					'id' => 'uacf7_web_hook_body_request',
					'type' => 'repeater',
					'label' => __( 'Request Body', 'ultimate-addons-cf7' ),
					'dependency' => [ 'uacf7_enable_web_hook', '==', 1 ],
					'fields' => [ 
						'uacf7_web_hook_body_request_value' => [ 
							'id' => 'uacf7_web_hook_body_request_value',
							'type' => 'text',
							'placeholder' => __( 'Enter a parameter key', 'ultimate-addons-cf7' ),
							'field_width' => 50,
						],

						'uacf7_web_hook_body_request_parameter' => [ 
							'id' => 'uacf7_web_hook_body_request_parameter',
							'type' => 'select',
							// 'label' => __( 'Request Format', 'ultimate-addons-cf7' ),
							'options' => 'uacf7',
							'query_args' => array(
								'post_id' => $post_id,
								'exclude' => [ 'submit', 'conditional' ],
							),
							'field_width' => 50,
						],
					]

				]
			],
		), $post_id );

		$value['Web_hook'] = $WebHook;
		return $value;
	}

	/**
	 * Check whether a switch value saved by the settings framework is enabled.
	 *
	 * @param mixed $value Saved option value.
	 *
	 * @return bool
	 */
	private function uacf7_webhook_option_is_enabled( $value ) {
		if ( true === $value || 1 === $value ) {
			return true;
		}

		return in_array( strtolower( (string) $value ), array( '1', 'yes', 'true', 'on' ), true );
	}

	/**
	 * Decide whether the current submission should be sent to the webhook.
	 *
	 * A matching "hide" rule always skips the webhook. When one or more
	 * "send" rules exist, at least one of them must match. If the configuration
	 * contains only "hide" rules, the webhook is sent unless one matches.
	 *
	 * @param array $rules       Conditional repeater rows.
	 * @param array $posted_data Contact Form 7 submitted values.
	 *
	 * @return bool
	 */
	private function uacf7_should_send_webhook( $rules, $posted_data ) {
		if ( ! is_array( $rules ) || empty( $rules ) ) {
			return false;
		}

		$has_valid_rule   = false;
		$has_send_rule    = false;
		$matched_send_rule = false;

		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}

			$action = isset( $rule['uacf7_cf_hs'] )
				? strtolower( (string) $rule['uacf7_cf_hs'] )
				: 'send';

			if ( ! in_array( $action, array( 'send', 'hide' ), true ) ) {
				continue;
			}

			$has_valid_rule = true;

			if ( 'send' === $action ) {
				$has_send_rule = true;
			}

			if ( ! $this->uacf7_webhook_rule_matches( $rule, $posted_data ) ) {
				continue;
			}

			if ( 'hide' === $action ) {
				return false;
			}

			$matched_send_rule = true;
		}

		if ( ! $has_valid_rule ) {
			return false;
		}

		return $has_send_rule ? $matched_send_rule : true;
	}

	/**
	 * Evaluate one conditional repeater row using its Any/All setting.
	 *
	 * @param array $rule        Conditional repeater row.
	 * @param array $posted_data Contact Form 7 submitted values.
	 *
	 * @return bool
	 */
	private function uacf7_webhook_rule_matches( $rule, $posted_data ) {
		$conditions = isset( $rule['uacf7_cf_conditions'] )
			? $rule['uacf7_cf_conditions']
			: array();

		if ( ! is_array( $conditions ) || empty( $conditions ) ) {
			return false;
		}

		$match_type = isset( $rule['uacf7_cf_condition_for'] )
			? strtolower( (string) $rule['uacf7_cf_condition_for'] )
			: 'any';

		$match_type          = 'all' === $match_type ? 'all' : 'any';
		$has_valid_condition = false;

		foreach ( $conditions as $condition ) {
			if ( ! is_array( $condition ) ) {
				continue;
			}

			$field_name = isset( $condition['uacf7_cf_tn'] )
				? rtrim( (string) $condition['uacf7_cf_tn'], '[]' )
				: '';
			$operator = isset( $condition['uacf7_cf_operator'] )
				? strtolower( (string) $condition['uacf7_cf_operator'] )
				: '';

			$allowed_operators = array(
				'equal',
				'not_equal',
				'greater_than',
				'less_than',
				'greater_than_or_equal_to',
				'less_than_or_equal_to',
				'starts_with',
				'ends_with',
				'contains',
				'does_not_contain',
			);

			if ( '' === $field_name || ! in_array( $operator, $allowed_operators, true ) ) {
				continue;
			}

			$has_valid_condition = true;
			$expected_value      = isset( $condition['uacf7_cf_val'] )
				? (string) $condition['uacf7_cf_val']
				: '';
			$posted_value        = array_key_exists( $field_name, $posted_data )
				? $posted_data[ $field_name ]
				: '';
			$matches             = $this->uacf7_webhook_condition_matches(
				$posted_value,
				$operator,
				$expected_value
			);

			if ( 'any' === $match_type && $matches ) {
				return true;
			}

			if ( 'all' === $match_type && ! $matches ) {
				return false;
			}
		}

		return $has_valid_condition && 'all' === $match_type;
	}

	/**
	 * Compare one submitted field against a configured condition.
	 *
	 * Checkbox and multi-select values are evaluated item by item. Negative
	 * operators match only when none of the submitted items violate them.
	 *
	 * @param mixed  $posted_value  Submitted field value.
	 * @param string $operator      Configured comparison operator.
	 * @param string $expected_value Configured comparison value.
	 *
	 * @return bool
	 */
	private function uacf7_webhook_condition_matches( $posted_value, $operator, $expected_value ) {
		$values = array();

		if ( ! is_array( $posted_value ) ) {
			$posted_value = array( $posted_value );
		}

		array_walk_recursive(
			$posted_value,
			static function ( $value ) use ( &$values ) {
				if ( is_scalar( $value ) || null === $value ) {
					$values[] = (string) $value;
				}
			}
		);

		if ( empty( $values ) ) {
			$values[] = '';
		}

		if ( 'not_equal' === $operator ) {
			foreach ( $values as $value ) {
				if ( $value === $expected_value ) {
					return false;
				}
			}

			return true;
		}

		if ( 'does_not_contain' === $operator ) {
			foreach ( $values as $value ) {
				if ( false !== strpos( $value, $expected_value ) ) {
					return false;
				}
			}

			return true;
		}

		foreach ( $values as $value ) {
			switch ( $operator ) {
				case 'equal':
					$matches = $value === $expected_value;
					break;

				case 'greater_than':
					$matches = is_numeric( $value )
						&& is_numeric( $expected_value )
						&& (float) $value > (float) $expected_value;
					break;

				case 'less_than':
					$matches = is_numeric( $value )
						&& is_numeric( $expected_value )
						&& (float) $value < (float) $expected_value;
					break;

				case 'greater_than_or_equal_to':
					$matches = is_numeric( $value )
						&& is_numeric( $expected_value )
						&& (float) $value >= (float) $expected_value;
					break;

				case 'less_than_or_equal_to':
					$matches = is_numeric( $value )
						&& is_numeric( $expected_value )
						&& (float) $value <= (float) $expected_value;
					break;

				case 'starts_with':
					$matches = 0 === strpos( $value, $expected_value );
					break;

				case 'ends_with':
					$matches = '' === $expected_value
						|| substr( $value, -strlen( $expected_value ) ) === $expected_value;
					break;

				case 'contains':
					$matches = false !== strpos( $value, $expected_value );
					break;

				default:
					$matches = false;
					break;
			}

			if ( $matches ) {
				return true;
			}
		}

		return false;
	}

	public function uacf7_send_data_by_web_hook( $form ) {

		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return;
		}

		$contact_form_data = $submission->get_posted_data();
		$Web_hook = uacf7_get_form_option( $form->id(), 'Web_hook' );

		if ( ! is_array( $contact_form_data ) || ! is_array( $Web_hook ) ) {
			return;
		}

		//Admin Option
		$web_hook_enable = isset( $Web_hook['uacf7_enable_web_hook'] ) ? $Web_hook['uacf7_enable_web_hook'] : false;
		$web_hook_condition_enable = isset( $Web_hook['uacf7_enable_web_hook_condition'] ) ? $Web_hook['uacf7_enable_web_hook_condition'] : false;
		$conditional_rules = isset( $Web_hook['uacf7_webhook_conditional_repeater'] ) ? $Web_hook['uacf7_webhook_conditional_repeater'] : array();
		$request_api     = isset( $Web_hook['uacf7_web_hook_api'] ) ? $Web_hook['uacf7_web_hook_api'] : '';
		$request_method  = isset( $Web_hook['uacf7_web_hook_request_method'] ) ? $Web_hook['uacf7_web_hook_request_method'] : '';
		$request_format  = isset( $Web_hook['uacf7_web_hook_request_format'] ) ? $Web_hook['uacf7_web_hook_request_format'] : '';
		$header_request  = isset( $Web_hook['uacf7_web_hook_header_request'] ) ? $Web_hook['uacf7_web_hook_header_request'] : '';
		$body_request    = isset( $Web_hook['uacf7_web_hook_body_request'] ) ? $Web_hook['uacf7_web_hook_body_request'] : '';

		$api_endpoint       = $request_api;
		$api_request_method = $request_method;

		// Return if not enable
		if ( ! $this->uacf7_webhook_option_is_enabled( $web_hook_enable ) ) {
			return;
		}

		// When conditional logic is enabled, send only when its rules allow it.
		if (
			$this->uacf7_webhook_option_is_enabled( $web_hook_condition_enable )
			&& ! $this->uacf7_should_send_webhook( $conditional_rules, $contact_form_data )
		) {
			return;
		}
		// Return API Not Fill
		if ( empty( $api_endpoint ) ) {
			return;
		}
		// Return If post type not seleted
		if ( empty( $api_request_method ) ) {
			return;
		}

		// Define the data to send in the POST request
		$header_data = array();
		$body_data = array();


		// Check if $header_request is an array
		if ( is_array( $header_request ) ) {
			// Loop through each item in the array
			foreach ( $header_request as $header ) {
				// Access individual values using keys
				if ( $header['uacf7_web_hook_header_request_custom'] === 'custom' ) {
					$customKey = $header['uacf7_web_hook_header_request_parameter_custom'];
					if ( isset( $customKey ) ) {
						$header_value = $header['uacf7_web_hook_header_request_value'];
						$header_parameter = $customKey;
					}
				} else {
					$header_value = $header['uacf7_web_hook_header_request_value'];
					$param_key = $header['uacf7_web_hook_header_request_parameter'];
					$param_key = rtrim( $param_key, '[]' );
					$header_parameter = isset( $contact_form_data[ $param_key ] ) ? $contact_form_data[ $param_key ] : '';
					
					if ( is_array( $header_parameter ) ) {
						$header_parameter = implode( ', ', $header_parameter );
					}
				}
				// Add data to the $post_data array
				$header_data[ $header_value ] = $header_parameter;
			}
		}

		// Check if $body_request is an array
		if ( is_array( $body_request ) ) {
			// Loop through each item in the array
			
			foreach ( $body_request as $body ) {

				$body_value = $body['uacf7_web_hook_body_request_value'];
				$param_key  = $body['uacf7_web_hook_body_request_parameter'];
				// Remove [] from checkbox field names
    			$param_key = rtrim( $param_key, '[]' );

				$body_parameter = isset( $contact_form_data[ $param_key ] ) ? $contact_form_data[ $param_key ] : '';
				
				// Handle checkbox / multi-select fields
				if ( is_array( $body_parameter ) ) {
					$body_parameter = implode( ', ', $body_parameter );
				}

				$body_data[ $body_value ] = $body_parameter;
			}
		}

		if ( $request_format === 'json' ) {
			$body_data = json_encode( $body_data);
		} if ( $request_format === 'formdata' ) {
			$body_data = http_build_query($body_data);
		}

		// Set up the request arguments
		$request_args = array(
			'body' => $body_data,
			'headers' => array_merge(
				[
					'Content-Type' => $request_format === 'json'
						? 'application/json'
						: 'application/x-www-form-urlencoded'
				],
				$header_data,
			),
			'method' => $api_request_method,
		);

		// Make the POST request
		$response = wp_remote_request( $api_endpoint, $request_args );

		// Check if the request was successful
		if ( is_wp_error( $response ) ) {
			// Handle error
			// echo 'Error: ' . $response->get_error_message();
		} else {
			// Request was successful, and $response contains the API response
			// $api_response = wp_remote_retrieve_body( $response );
			// echo 'API Response: ' . $api_response;
		}

	}

}
new UACF7_WEB_HOOK();