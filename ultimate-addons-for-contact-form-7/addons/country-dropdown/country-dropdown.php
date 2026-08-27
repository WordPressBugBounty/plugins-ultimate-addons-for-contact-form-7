<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class UACF7_COUNTRY_DROPDOWN {

	/*
	 * Construct function
	 */
	public function __construct() {

		add_action( 'wpcf7_init', array( $this, 'add_shortcodes' ) );

		add_action( 'admin_init', array( $this, 'tag_generator' ) );

		add_filter( 'wpcf7_validate_uacf7_country_dropdown', array( $this, 'wpcf7_country_dropdown_validation_filter' ), 10, 2 );

		add_filter( 'wpcf7_validate_uacf7_country_dropdown*', array( $this, 'wpcf7_country_dropdown_validation_filter' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_script' ) );
	}

	public function wp_enqueue_script() {

		wp_enqueue_style( 'uacf7-country-select-main', UACF7_ADDONS . '/country-dropdown/assets/css/countrySelect.min.css', array(), UACF7_VERSION, 'all' );
		wp_enqueue_style( 'uacf7-country-select-style', UACF7_ADDONS . '/country-dropdown/assets/css/style.css', array(), UACF7_VERSION, 'all' );

		wp_enqueue_script( 'uacf7-country-select-library', UACF7_ADDONS . '/country-dropdown/assets/js/countrySelect.js', array( 'jquery' ), UACF7_VERSION, true );
		wp_enqueue_script( 'uacf7-country-select-script', UACF7_ADDONS . '/country-dropdown/assets/js/script.js', array( 'jquery', 'uacf7-country-select-library' ), UACF7_VERSION, true );
	}

	/*
	 * Form tag
	 */
	public function add_shortcodes() {

		wpcf7_add_form_tag( array( 'uacf7_country_dropdown', 'uacf7_country_dropdown*' ),
			array( $this, 'tag_handler_callback' ), array( 'name-attr' => true ) );
	}

	public function tag_handler_callback( $tag ) {

		if ( empty( $tag->name ) ) {
			return '';
		}

		$validation_error = wpcf7_get_validation_error( $tag->name );

		$class = wpcf7_form_controls_class( $tag->type );

		if ( $validation_error ) {
			$class .= ' wpcf7-not-valid';
		}

		$class .= ' uacf7_country_dropdown_with_flag';

		$atts = array();

		$atts['id'] = $tag->get_id_option();
		$atts['tabindex'] = $tag->get_option( 'tabindex', 'signed_int', true );

		if ( $tag->is_required() ) {
			$atts['aria-required'] = 'true';
		}

		$atts['aria-invalid'] = $validation_error ? 'true' : 'false';
		$atts['name'] = $tag->name;

		$size = $tag->get_option( 'size', 'int', true );

		if ( $size ) {
			$atts['size'] = $size;
		}

		$field_state = apply_filters(
			'uacf7_country_dropdown_field_state',
			array(
				'class' => $class,
				'atts' => $atts,
			),
			$tag
		);

		$class = isset( $field_state['class'] ) ? $field_state['class'] : $class;
		$atts = isset( $field_state['atts'] ) && is_array( $field_state['atts'] ) ? $field_state['atts'] : $atts;

		$atts['class'] = $tag->get_class_option( $class );
		$atts = wpcf7_format_atts( $atts );

		ob_start();
		?>
		<span id="uacf7_country_select" class="wpcf7-form-control-wrap <?php echo sanitize_html_class( $tag->name ); ?>">

			<input id="uacf7_countries_<?php echo esc_attr( $tag->name ); ?>" type="text" <?php echo wp_kses_post( $atts ); ?>>

			<span><?php echo wp_kses_post( $validation_error ); ?></span>

			<div style="display:none;">
				<input type="hidden" id="uacf7_countries_<?php echo esc_attr( $tag->name ); ?>_code" data-countrycodeinput="1" readonly="readonly" placeholder="Selected country code will appear here" />
			</div>

		</span>
		<?php

		return ob_get_clean();
	}


	public function wpcf7_country_dropdown_validation_filter( $result, $tag ) {
		$name = $tag->name;
		$submission = WPCF7_Submission::get_instance();

		if ( ! $submission ) {
			return $result;
		}

		$post_value = $submission->get_posted_data( $name );

		if ( is_array( $post_value ) ) {
			$post_value = array_map( 'sanitize_text_field', $post_value );
			$post_value = array_filter( $post_value, static function( $value ) {
				return '' !== $value;
			} );
		} elseif ( null !== $post_value ) {
			$post_value = sanitize_text_field( $post_value );
		}

		$empty = null === $post_value || '' === $post_value || array() === $post_value;

		if ( $tag->is_required() and $empty ) {
			$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
		}

		return $result;
	}

	/*
	 * Generate tag
	 */
	public function tag_generator() {
		if ( ! function_exists( 'wpcf7_add_tag_generator' ) ) {
			return;
		}

		wpcf7_add_tag_generator(
			'uacf7_country_dropdown',
			__( 'Country Dropdown', 'ultimate-addons-for-contact-form-7' ),
			'uacf7-tg-pane-country-dropdown',
			array( $this, 'tg_pane_country_dropdown' ),
			array( 'version' => '2' )
		);
	}

	static function tg_pane_country_dropdown( $contact_form, $args ) {
		$args = wp_parse_args( $args, array() );
		$tgg = new WPCF7_TagGeneratorGenerator( $args['content'] );
		?>
		<header class="description-box">
			<h3><?php echo esc_html__( 'Country Dropdown', 'ultimate-addons-for-contact-form-7' ); ?></h3>

			<div class="uacf7-doc-notice">
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %1$s: Link to Country Dropdown documentation. */
						__( 'Confused? Check our Documentation on %1$s.', 'ultimate-addons-for-contact-form-7' ),
						'<a href="' . esc_url( 'https://themefic.com/docs/uacf7/free-addons/contact-form-7-country-dropdown-with-flag/' ) . '" target="_blank" rel="noopener noreferrer">' .
							esc_html__( 'Country Dropdown', 'ultimate-addons-for-contact-form-7' ) .
						'</a>'
					)
				);
				?>
			</div>
		</header>

		<div class="control-box">
			<?php

			$tgg->print( 'field_type', array(
				'select_options' => array(
					'uacf7_country_dropdown' => 'Country Dropdown',
				),
				'with_required' => true,
			) );

			$tgg->print( 'field_name' );
			$tgg->print( 'class_attr' );

			do_action( 'uacf7_country_dropdown_tag_generator_fields', $tgg, $contact_form, $args );
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

new UACF7_COUNTRY_DROPDOWN();
