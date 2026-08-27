<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UACF7_DYNAMIC_TEXT {

	/*
	 * Construct function
	 */
	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'uacf7_dynamic_text_admin_enqueue_scripts' ), 1 );

		add_action( 'wpcf7_init', array( $this, 'add_shortcodes' ) );

		add_action( 'admin_init', array( $this, 'tag_generator' ) );

		add_filter( 'wpcf7_validate_uacf7_dynamic_text', array( $this, 'uacf7_dynamic_text_validation_filter' ), 10, 2 );

		add_filter( 'wpcf7_validate_uacf7_dynamic_text*', array( $this, 'uacf7_dynamic_text_validation_filter' ), 10, 2 );


		//Require Shortcode
		require_once( 'inc/shortcode.php' );

	}

	public function uacf7_dynamic_text_admin_enqueue_scripts($screen){

		$tf_options_screens = array(
			'toplevel_page_wpcf7',
			'contact_page_wpcf7-new',
		);

		if ( in_array( $screen, $tf_options_screens )) {
			wp_enqueue_script( 'uacf7-dynamic-text', UACF7_URL . 'addons/dynamic-text/assets/js/uacf7-dynamic-text.js', array( 'jquery'), UACF7_VERSION, true );
		}
	}

	/*
	 * Form tag
	 */
	public function add_shortcodes() {

		wpcf7_add_form_tag( array( 'uacf7_dynamic_text', 'uacf7_dynamic_text*' ),
			array( $this, 'uacf7_dynamic_text_tag_handler_callback' ), array( 'name-attr' => true ) );
	}

	/*
	 * Form tag shortcode
	 */
	public function uacf7_dynamic_text_tag_handler_callback( $tag ) {
		if ( empty( $tag->name ) ) {
			return '';
		}
		
		$validation_error = wpcf7_get_validation_error( $tag->name );

		$class = wpcf7_form_controls_class( $tag->type );

		if ( $validation_error ) {
			$class .= ' wpcf7-not-valid';
		}

		$atts = array();

		$atts['class'] = $tag->get_class_option( $class );
		$atts['id'] = $tag->get_id_option();
		$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );

		if ( $tag->is_required() ) {
			$atts['aria-required'] = 'true';
		}

		$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

		$atts['name'] = $tag->name;

		// input size
		$size = $tag->get_option( 'size', 'int', true );
		if ( $size ) {
			$atts['size'] = $size;
		} else {
			$atts['size'] = 40;
		}

		// Visibility
		$visibility = $tag->get_option( 'visibility', '', true );
		if ( $visibility == 'show' ) {
			$atts['type'] = 'text';
		} elseif ( $visibility == 'disabled' ) {
			$atts['type'] = 'text';
			$atts['disabled'] = 'disabled';
		} elseif ( $visibility == 'hidden' ) {
			$atts['type'] = 'hidden';
		}


		$values = $tag->values;
		$key = $tag->get_option( 'key', '', true );

		// Short Code
		$shortcode = '';
		if ( ! empty( $values ) ) {
			$shortcode = do_shortcode( '[' . esc_attr( $values[0] ) . ' attr="' . esc_attr( $key ) . '"]' );
		}
		$atts['value'] = esc_attr( $shortcode );

		$atts = wpcf7_format_atts( $atts );
		ob_start();

		?>
		<span class="wpcf7-form-control-wrap <?php echo sanitize_html_class( esc_attr( $tag->name ) ); ?>"
			data-name="<?php echo sanitize_html_class( esc_attr( $tag->name ) ); ?>">

			<input id="uacf7_<?php echo esc_attr( $tag->name ); ?>" <?php echo esc_attr( $atts ); ?>>
			<span><?php echo wp_kses_post( $validation_error ); ?></span>
		</span>
		<?php

		$countries = ob_get_clean();

		return $countries;
	}

	/*
	* Form tag validation.
	*/
	public function uacf7_dynamic_text_validation_filter( $result, $tag ) {

		$name = $tag->name;
		$posted_value = null;

		$submission = WPCF7_Submission::get_instance();

		if ( $submission ) {
			$posted_data = $submission->get_posted_data();

			if ( is_array( $posted_data ) && array_key_exists( $name, $posted_data ) ) {
				$posted_value = map_deep( $posted_data[ $name ], 'sanitize_text_field' );

				if ( is_array( $posted_value ) ) {
					$posted_value = array_filter( $posted_value, static function ( $value ) {
						return '' !== $value;
					} );
				}
			}
		}

		$empty = null === $posted_value || '' === $posted_value || array() === $posted_value;

		if ( $tag->is_required() && $empty ) {
			$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
		}

		return $result;
	}

	/*
	 * Generate tag - conditional
	 */
	public function tag_generator() {

		$tag_generator = WPCF7_TagGenerator::get_instance();

		$tag_generator->add(
			'uacf7_dynamic_text',
			__( 'Dynamic Text', 'ultimate-addons-for-contact-form-7' ),
			[ $this, 'tg_pane_uacf7_dynamic_text' ],
			array( 'version' => '2' )
		);

	}


	static function tg_pane_uacf7_dynamic_text( $contact_form, $options ) {

		$field_types = array(
			'uacf7_dynamic_text' => array(
				'display_name' => __( 'Dynamic Text', 'ultimate-addons-for-contact-form-7' ),
				'heading' => __( 'Generate a Dynamic Text.', 'ultimate-addons-for-contact-form-7' ),
				'description' => '',
			),
		);

		$tgg = new WPCF7_TagGeneratorGenerator( $options['content'] );

		?>
		<header class="description-box">
			<h3><?php
			echo esc_html( $field_types['uacf7_dynamic_text']['heading'] );
			?></h3>

			<p><?php
			echo wp_kses(
				$field_types['uacf7_dynamic_text']['description'],
				array(
					'a' => array( 'href' => true ),
					'strong' => array(),
				),
				array( 'http', 'https' )
			);

			?></p>
			<div class="uacf7-doc-notice">
				<?php echo sprintf(
					/* Translators: 1: Dynamic Text */
					esc_html__( 'Confused? Check our Documentation on  %1s.', 'ultimate-addons-for-contact-form-7' ),
					'<a href="'.esc_url('https://themefic.com/docs/uacf7/free-addons/contact-form-7-dynamic-text-extension/').'" target="_blank">' . esc_html__( 'Dynamic Text', 'ultimate-addons-for-contact-form-7' ) . '</a>'
				); ?>
			</div>

		</header>

		<div class="control-box uacf7-control-box">
			<?php

			$tgg->print( 'field_type', array(
				'with_required' => true,
				'select_options' => array(
					'uacf7_dynamic_text' => $field_types['uacf7_dynamic_text']['display_name'],
				),
			) );

			$tgg->print( 'field_name' );

			?>

			<fieldset>
				<legend>
					<?php echo esc_html__( 'Field Visibility', 'ultimate-addons-for-contact-form-7' ); ?>
				</legend>

				<select data-tag-part="option" data-tag-option="visibility:">
					<option value="show">
						<?php echo esc_html__( 'Show', 'ultimate-addons-for-contact-form-7' ); ?>
					</option>
					<option value="disabled">
						<?php echo esc_html__( 'Disabled', 'ultimate-addons-for-contact-form-7' ); ?>
					</option>
					<option value="hidden">
						<?php echo esc_html__( 'Hidden', 'ultimate-addons-for-contact-form-7' ); ?>
					</option>
				</select>

			</fieldset>

			<fieldset>
				<legend>
					<?php echo esc_html__( 'Choose Field', 'ultimate-addons-for-contact-form-7' ); ?>
				</legend>

				<input
					type="text"
					id="uacf7-choose-field"
					list="uacf7-field-options"
					data-tag-part="value"
					placeholder="Select a value"
					autocomplete="off"
				/>

				<datalist id="uacf7-field-options">
					<option value="UACF7_URL">
						<?php echo esc_html__( 'Current URL', 'ultimate-addons-for-contact-form-7' ); ?>
					</option>
					<option value="UACF7_URL part=host">
						<?php echo esc_html__('Current URL Host (Domain)', 'ultimate-addons-for-contact-form-7'); ?>
					</option>
					<option value="UACF7_URL part=query">
						<?php echo esc_html__('Current URL Query String', 'ultimate-addons-for-contact-form-7'); ?>
					</option>
					<option value="UACF7_URL part=path">
						<?php echo esc_html__('Current URL Path', 'ultimate-addons-for-contact-form-7'); ?>
					</option>
					<option value="UACF7_URL_WITH_PERAMETERS">
						<?php echo esc_html__( 'Current URL with Perameters', 'ultimate-addons-for-contact-form-7' ); ?>
					</option>
					<option value="UACF7_BLOGINFO">
						<?php echo esc_html__( 'Blog Info', 'ultimate-addons-for-contact-form-7' ); ?>
					</option>
					<option value="UACF7_POSTINFO">
						<?php echo esc_html__( 'Current post info', 'ultimate-addons-for-contact-form-7' ); ?>
					</option>
					<option value="UACF7_USERINFO">
						<?php echo esc_html__( 'Current User info', 'ultimate-addons-for-contact-form-7' ); ?>
					</option>
					<option value="UACF7_CUSTOM_FIELDS">
						<?php echo esc_html__( 'Custom fields', 'ultimate-addons-for-contact-form-7' ); ?>
					</option>
				</datalist>
			</fieldset>

			<fieldset id="uacf7-dynamic-arg-fieldset" style="display:none;">
				<legend><?php echo esc_html__( 'Dynamic Arg', 'ultimate-addons-for-contact-form-7' ); ?></legend>
				<input type="text" id="uacf7-dynamic-arg" placeholder="e.g. ref" />
				<input type="hidden" id="uacf7-dynamic-arg-hidden" data-tag-part="value" />
			</fieldset>

			<fieldset>
				<legend>
					<?php echo esc_html__( 'Dynamic key', 'ultimate-addons-for-contact-form-7' ); ?>
				</legend>
				<input type="text" data-tag-part="option" data-tag-option="key:" placeholder="Dynamic key" >			
			</fieldset>

			<?php
				$tgg->print( 'class_attr' );
			?>
		</div>

		<footer class="insert-box">
			<?php
			$tgg->print( 'insert_box_content' );

			$tgg->print( 'mail_tag_tip' );
			?>
		</footer>

		<?php
	}
}
new UACF7_DYNAMIC_TEXT();